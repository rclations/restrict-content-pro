<?php

/**
 * RCP Payment class
 *
 * This class handles interacting with an individual payment record.
 *
 * @package     Restrict Content Pro
 * @subpackage  Classes/Payment
 * @copyright   Copyright (c) 2017, Restrict Content Pro
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       2.9
 */
class RCP_Payment {

	/**
	 * Payment ID number
	 *
	 * @var int
	 * @access public
	 * @since  2.9
	 */
	public $id = 0;

	/**
	 * Name of the corresponding subscription level
	 *
	 * @var string
	 * @access public
	 * @since  2.9
	 */
	public $subscription;

	/**
	 * ID of the corresponding subscription level
	 *
	 * @var int
	 * @access private
	 * @since  2.9
	 */
	private $subscription_level_id;

	/**
	 * Date the payment was made
	 *
	 * @var string
	 * @access public
	 * @since  2.9
	 */
	public $date;

	/**
	 * Amount the payment was for
	 *
	 * @var int|float
	 * @access public
	 * @since  2.9
	 */
	public $amount;

	/**
	 * Discounted amount, from the discount code
	 *
	 * @var int|float
	 * @access public
	 * @since  2.9
	 */
	public $discount_amount;

	/**
	 * Amount of credit applied towards the purchase (proration credits)
	 *
	 * @var int|float
	 * @access public
	 * @since  2.9
	 */
	public $credits;

	/**
	 * Amount of fees
	 *
	 * @var int|float
	 * @access public
	 * @since  2.9
	 */
	public $fees;

	/**
	 * ID of the user who made the payment
	 *
	 * @var int
	 * @access public
	 * @since  2.9
	 */
	public $user_id;

	/**
	 * ID of the gateway that was used to make the purchase
	 *
	 * @var string
	 * @access private
	 * @since  2.9
	 */
	private $gateway;

	/**
	 * Label for the type of payment, i.e. "Credit Card One Time"
	 *
	 * @var string
	 * @access public
	 * @since  2.9
	 */
	public $payment_type;

	/**
	 * Subscription key
	 *
	 * @var string
	 * @access public
	 * @since  2.9
	 */
	public $subscription_key;

	/**
	 * Transaction ID in the gateway
	 *
	 * @var string
	 * @access public
	 * @since  2.9
	 */
	public $transaction_id;

	/**
	 * Discount code that was used for this payment (if any)
	 *
	 * @var string
	 * @access public
	 * @since  2.9
	 */
	public $discount_code;

	/**
	 * Payment status, i.e. 'complete', 'pending', or 'failed'
	 *
	 * @var string
	 * @access private
	 * @since  2.9
	 */
	private $status;

	/**
	 * Payments database object
	 *
	 * @var RCP_Payments
	 * @access private
	 * @since  2.9
	 */
	private $payments_db;

	/**
	 * RCP_Payment constructor.
	 *
	 * @param int|string $payment_or_txn_id A payment or transaction ID.
	 * @param bool       $by_txn            Whether or not to get the payment by the transaction ID.
	 *
	 * @access public
	 * @since  2.9
	 * @return void|false
	 */
	public function __construct( $payment_or_txn_id = 0, $by_txn = false ) {

		$this->payments_db = new RCP_Payments();

		if ( empty( $payment_or_txn_id ) ) {
			return false;
		}

		$field   = $by_txn ? 'transaction_id' : 'id';
		$payment = $this->payments_db->get_payment_by( $field, $payment_or_txn_id );

		if ( empty( $payment ) ) {
			return false;
		}

		$this->setup_payment( $payment );

	}

	/**
	 * Magic __get function
	 *
	 * This is used on a few private properties like `$subscription_level_id` if we need to account for
	 * the value not existing when it should.
	 *
	 * @param $key
	 *
	 * @access public
	 * @since  2.9
	 * @return mixed
	 */
	public function __get( $key ) {

		if ( method_exists( $this, 'get_' . $key ) ) {
			return call_user_func( array( $this, 'get_' . $key ) );
		} elseif ( property_exists( $this, $key ) ) {
			return $this->$key;
		} else {
			return new WP_Error( 'rcp-payment-invalid-property', sprintf( __( 'Can\'t get property %s', 'rcp' ), $key ) );
		}

	}

	/**
	 * Setup the payment
	 *
	 * @param object $payment Payment object from the database.
	 *
	 * @access private
	 * @since  2.0
	 * @return void
	 */
	private function setup_payment( $payment ) {

		foreach ( $payment as $key => $value ) {
			$this->$key = $value;
		}

	}

	/**
	 * Update a payment record
	 *
	 * @param array $args Array of fields to update.
	 *
	 * @access public
	 * @since  2.9
	 * @return int|false The number of rows updated, or false on error.
	 */
	public function update( $args = array() ) {

		$ret = $this->payments_db->update( $this->id, $args );

		if ( array_key_exists( 'status', $args ) && $args['status'] != $this->status ) {
			/**
			 * Triggers when the payment's status is changed.
			 *
			 * @param string      $new_status New status being set.
			 * @param string      $old_status Previous status before the update.
			 * @param int         $payment_id ID of the payment.
			 * @param RCP_Payment $this       Payment object.
			 *
			 * @since 2.9
			 */
			do_action( 'rcp_update_payment_status', $args['status'], $this->status, $this->id, $this );
			do_action( 'rcp_update_payment_status_' . $args['status'], $this->status, $this->id, $this );

			if ( 'complete' == $args['status'] ) {
				/**
				 * Runs only when a payment is updated to "complete". This is to
				 * ensure backwards compatibility from before payments were inserted
				 * as "pending" before payment is taken.
				 *
				 * @see RCP_Payments::insert() - Action is also run here.
				 *
				 * @param int   $payment_id ID of the payment that was just updated.
				 * @param array $args       Array of payment information that was just updated.
				 * @param float $amount     Amount the payment was for.
				 */
				do_action( 'rcp_insert_payment', $this->id, $args, $this->amount );
			}
		}

		return $ret;

	}

	/**
	 * Delete this payment record
	 *
	 * @access public
	 * @since  2.9
	 * @return void
	 */
	public function delete() {
		$this->payments_db->delete( $this->id );
	}

	/**
	 * Get the corresponding subscription level ID
	 *
	 * If the ID doesn't exist in the database, attempt to get the ID from the name
	 * and update the database record.
	 *
	 * @access public
	 * @since  2.9
	 * @return int
	 */
	public function get_subscription_level_id() {

		if ( empty( $this->subscription_level_id ) && ! empty( $this->subscription ) ) {

			// Get the level ID from the name and update it in the database.
			$subscription = rcp_get_subscription_details_by_name( $this->subscription );

			if ( ! empty( $subscription ) ) {
				$this->subscription_level_id = absint( $subscription->id );
				$this->update( array(
					'subscription_level_id' => $this->subscription_level_id
				) );
			}

		}

		return absint( $this->subscription_level_id );

	}

	/**
	 * Get the ID of the payment gateway used to make the payment
	 *
	 * If the gateway doesn't exist in the database, attempt to guess it based
	 * on the payment type/transaction ID and update the database record.
	 *
	 * @access public
	 * @since  2.9
	 * @return string
	 */
	public function get_gateway() {

		if ( empty( $this->gateway ) && ! empty( $this->payment_type ) ) {

			// Attempt to guess gateway from type and update it in the database.
			$type    = strtolower( $this->payment_type );
			$gateway = '';

			switch ( $type ) {

				case 'web_accept' :
				case 'paypal express one time' :
				case 'recurring_payment' :
				case 'subscr_payment' :
				case 'recurring_payment_profile_created' :
					$gateway = 'paypal';
					break;

				case 'credit card' :
				case 'credit card one time' :
					if ( false !== strpos( $this->transaction_id, 'ch_' ) ) {
						$gateway = 'stripe';
					} elseif ( false !== strpos( $this->transaction_id, 'anet_' ) ) {
						$gateway = 'authorizenet';
					} elseif ( is_numeric( $this->transaction_id ) ) {
						$gateway = 'twocheckout';
					}
					break;

				case 'braintree credit card one time' :
				case 'braintree credit card initial payment' :
				case 'braintree credit card' :
					$gateway = 'braintree';
					break;

			}

			if ( ! empty( $gateway ) ) {
				$this->gateway = $gateway;
				$this->update( array(
					'gateway' => $this->gateway
				) );
			}

		}

		return $this->gateway;

	}

	/**
	 * Get the status of the payment
	 *
	 * If the status doesn't exist in the database, set it to 'complete' and
	 * update the database record.
	 *
	 * @access public
	 * @since  2.9
	 * @return string
	 */
	public function get_status() {

		if ( empty( $this->status ) ) {
			$this->status = 'complete';
			$this->update( array(
				'status' => $this->status
			) );
		}

		return $this->status;

	}

}