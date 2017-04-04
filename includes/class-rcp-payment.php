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
	 * ID of the corresponding subscription level
	 *
	 * @todo   Convert from name to ID in DB.
	 *
	 * @var int
	 * @access public
	 * @since  2.9
	 */
	public $subscription;

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
	 * Name of the gateway that was used to make the purchase
	 *
	 * @var string
	 * @access public
	 * @since  2.9
	 */
	public $gateway;

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
	 * @access public
	 * @since  2.9
	 */
	public $status;

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

}