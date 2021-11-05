<?php
/**
 * Class WooCommerceDeposits
 *
 * @package WCPay\MultiCurrency\Compatibility
 */

namespace WCPay\MultiCurrency\Compatibility;

use WC_Product;
use WCPay\MultiCurrency\MultiCurrency;
use WCPay\MultiCurrency\Utils;

/**
 * Class that controls Multi Currency Compatibility with WooCommerce Deposits Plugin.
 */
class WooCommerceDeposits {
	/**
	 * MultiCurrency class.
	 *
	 * @var MultiCurrency
	 */
	private $multi_currency;

	/**
	 * Utils class.
	 *
	 * @var Utils
	 */
	private $utils;

	/**
	 * Constructor.
	 *
	 * @param MultiCurrency $multi_currency MultiCurrency class.
	 * @param Utils         $utils Utils class.
	 */
	public function __construct( MultiCurrency $multi_currency, Utils $utils ) {
		$this->multi_currency = $multi_currency;
		$this->utils          = $utils;
		$this->initialize_hooks();
	}

	/**
	 * Adds compatibility filters if the plugin exists and loaded
	 *
	 * @return  void
	 */
	protected function initialize_hooks() {
		if ( class_exists( 'WC_Deposits' ) ) {
			// Add compatibility filters here.
			add_action( 'woocommerce_deposits_create_order', [ $this, 'modify_order_currency' ] );
			add_filter( 'woocommerce_get_cart_contents', [ $this, 'modify_cart_item_deposit_amounts' ] );
			add_filter( MultiCurrency::FILTER_PREFIX . 'should_convert_product_price', [ $this, 'maybe_convert_product_prices_for_deposits' ], 10, 2 );
		}
	}

	/**
	 * Converts the currency for deposit amounts of each cart item, only applies if deposits are enabled on the product.
	 *
	 * @param array $cart_contents The current tax definitions.
	 *
	 * @return array $tax Array of altered taxes.
	 */
	public function modify_cart_item_deposit_amounts( $cart_contents ) {
		foreach ( $cart_contents as $cart_item_key => $cart_item ) {
			if ( isset( $cart_item['is_deposit'] ) && $cart_item['is_deposit'] && isset( $cart_item['deposit_amount'] ) ) {
					$deposit_amount                                    = floatval( $cart_item['deposit_amount'] );
					$cart_contents[ $cart_item_key ]['deposit_amount'] = $this->multi_currency->get_price( $deposit_amount, 'product' );
			}
		}

		return $cart_contents;
	}

	/**
	 * Defines if the product prices need to be converted when calculating totals,
	 * if the product's deposit type is a payment plan, then it shouldn't convert it.
	 *
	 * @param   bool        $result   The previous flag for converting the price.
	 * @param   \WC_Product $product  The product to check for.
	 *
	 * @return  bool                        Whether the price should be converted or not.
	 */
	public function maybe_convert_product_prices_for_deposits( $result, $product ) {
		if ( class_exists( 'WC_Deposits_Product_Manager' )
			&& call_user_func( [ 'WC_Deposits_Product_Manager', 'deposits_enabled' ], $product )
			&& 'plan' === call_user_func( [ 'WC_Deposits_Product_Manager', 'get_deposit_type' ], $product )
			&& $this->utils->is_call_in_backtrace( [ 'WC_Cart->calculate_totals' ] )
		) {
			return false;
		}
		return $result;
	}

	/**
	 * When creating a new order for the remaining amount, forces the new order currency
	 * to be the same with the deposited order currency.
	 *
	 * @param   integer $order_id  The created order ID.
	 *
	 * @return  void
	 */
	public function modify_order_currency( $order_id ) {
		// We need to get the original order from the first item meta.
		$order            = wc_get_order( $order_id );
		$order_items      = $order->get_items();
		$first_order_item = 0 < count( $order_items ) ? reset( $order_items ) : null;

		if ( ! $first_order_item ) {
			return;
		}

		// Check if the original order ID is attached to the order item.
		$original_order_id = wc_get_order_item_meta( $first_order_item->get_id(), '_original_order_id', true );
		if ( ! $original_order_id ) {
			return;
		}

		// Check if the original order still exists.
		$original_order = wc_get_order( $original_order_id );
		if ( ! $original_order ) {
			return;
		}

		// Get the new and old order currencies, and match them if unmatched.
		$saved_currency    = $order->get_currency( 'view' );
		$original_currency = $original_order->get_currency( 'view' );
		if ( $saved_currency !== $original_currency ) {
			$order->set_currency( $original_currency );
			$order->save();
		}
	}
}