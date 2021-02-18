<?php
/**
 * WC_Payments_Action_Scheduler_Service class
 *
 * @package WooCommerce\Payments
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class which handles setting up all ActionScheduler hooks.
 */
class WC_Payments_Action_Scheduler_Service {
	/**
	 * Client for making requests to the WooCommerce Payments API
	 *
	 * @var WC_Payments_API_Client
	 */
	private $payments_api_client;


	/**
	 * Constructor for WC_Payments_Action_Scheduler_Service.
	 *
	 * @param WC_Payments_API_Client $payments_api_client - WooCommerce Payments API client.
	 */
	public function __construct(
		WC_Payments_API_Client $payments_api_client
	) {
		$this->payments_api_client = $payments_api_client;

		$this->add_action_scheduler_hooks();
	}

	/**
	 * Attach hooks for all ActionScheduler actions.
	 *
	 * @return void
	 */
	public function add_action_scheduler_hooks() {
		add_action( 'wcpay_track_new_order', [ $this, 'track_new_order_action' ] );
		add_action( 'wcpay_track_update_order', [ $this, 'track_update_order_action' ] );
	}

	/**
	 * This function is a hook that will be called by ActionScheduler when an order is created.
	 * It will make a request to the Payments API to track this event.
	 *
	 * @param array $order_id  The ID of the order that has been created.
	 *
	 * @return bool
	 */
	public function track_new_order_action( $order_id ) {
		// Get the order details.
		$order = wc_get_order( $order_id );

		if ( ! $order ) {
			return false;
		}

		// Send the order data to the Payments API to track it.
		$result = $this->payments_api_client->track_order(
			array_merge( $order->get_data(), [ '_intent_id' => $order->get_meta( '_intent_id' ) ] ),
			false
		);

		return $result;
	}

	/**
	 * This function is a hook that will be called by ActionScheduler when an order is updated.
	 * It will make a request to the Payments API to track this event.
	 *
	 * @param int $order_id  The ID of the order which has been updated.
	 *
	 * @return bool
	 */
	public function track_update_order_action( $order_id ) {
		// Get the order details.
		$order = wc_get_order( $order_id );

		if ( ! $order ) {
			return false;
		}

		// Send the order data to the Payments API to track it.
		$result = $this->payments_api_client->track_order(
			array_merge( $order->get_data(), [ '_intent_id' => $order->get_meta( '_intent_id' ) ] ),
			true
		);

		return $result;
	}

	/**
	 * Schedule an action scheduler job. Also unschedules (replaces) any previous instances of the same job.
	 * This prevents duplicate jobs, for example when multiple events fire as part of the order update process.
	 * The `as_unschedule_action` function will only replace a job which has the same $hook, $args AND $group.
	 *
	 * @param int    $timestamp - When the job will run.
	 * @param string $hook      - The hook to trigger.
	 * @param array  $args      - An array containing the arguments to be passed to the hook.
	 * @param string $group     - The group to assign this job to.
	 *
	 * @return void
	 */
	public function schedule_job( $timestamp, $hook, $args = [], $group = '' ) {
		// Unschedule any previously scheduled instances of this particular job.
		as_unschedule_action( $hook, $args, $group );

		// Schedule the job.
		as_schedule_single_action( $timestamp, $hook, $args, $group );
	}
}
