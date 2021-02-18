<?php
/**
 * Class WC_Payments_Action_Scheduler_Service_Test
 *
 * @package WooCommerce\Payments\Tests
 */

use PHPUnit\Framework\MockObject\MockObject;
use WCPay\Exceptions\API_Exception;

/**
 * WC_Payments_Action_Scheduler_Service unit tests.
 */
class WC_Payments_Action_Scheduler_Service_Test extends WP_UnitTestCase {
	/**
	 * System under test.
	 *
	 * @var WC_Payments_Action_Scheduler_Service
	 */
	private $action_scheduler_service;

	/**
	 * Mock WC_Payments_API_Client.
	 *
	 * @var WC_Payments_API_Client|MockObject
	 */
	private $mock_api_client;

	/**
	 * Pre-test setup
	 */
	public function setUp() {
		parent::setUp();

		$this->mock_api_client = $this->createMock( WC_Payments_API_Client::class );

		$this->action_scheduler_service = new WC_Payments_Action_Scheduler_Service( $this->mock_api_client );
	}

	public function test_track_new_order_action() {
		$order = WC_Helper_Order::create_order();

		$this->mock_api_client->expects( $this->once() )
			->method( 'track_order' )
			->with( $this->get_order_data_mock( $order->get_id() ), false )
			->willReturn( true );

		$this->assertTrue( $this->action_scheduler_service->track_new_order_action( $order->get_id() ) );
	}

	public function test_track_new_order_action_with_invalid_order_id() {
		$this->assertFalse( $this->action_scheduler_service->track_new_order_action( -4 ) );
	}

	public function test_track_new_order_action_with_invalid_input() {
		$order = WC_Helper_Order::create_order();

		$this->assertFalse( $this->action_scheduler_service->track_new_order_action( $order->get_data() ) );
	}

	public function test_track_update_order_action() {
		$order = WC_Helper_Order::create_order();

		$this->mock_api_client->expects( $this->once() )
			->method( 'track_order' )
			->with( $this->get_order_data_mock( $order->get_id() ), true )
			->willReturn( true );

		$this->assertTrue( $this->action_scheduler_service->track_update_order_action( $order->get_id() ) );
	}

	public function test_track_update_order_action_with_invalid_order_id() {
		$this->assertFalse( $this->action_scheduler_service->track_update_order_action( -4 ) );
	}

	public function test_track_update_order_action_with_invalid_input() {
		$order = WC_Helper_Order::create_order();

		$this->assertFalse( $this->action_scheduler_service->track_update_order_action( $order->get_data() ) );
	}

	/**
	 * Get a mock of the order data expected to be passed into the `track_order` function.
	 *
	 * @return array
	 */
	private function get_order_data_mock( $order_id ) {
		$order = wc_get_order( $order_id );

		return array_merge(
			$order->get_data(),
			[ '_intent_id' => $order->get_meta( '_intent_id' ) ]
		);
	}
}
