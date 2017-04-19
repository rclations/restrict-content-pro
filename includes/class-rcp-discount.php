<?php

/**
 * RCP Discount class
 *
 * This class handles interacting with a single discount code.
 *
 * @package     Restrict Content Pro
 * @subpackage  Classes/Discounts
 * @copyright   Copyright (c) 2017, estrict Content Pro
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       2.9
 */
class RCP_Discount {

	/**
	 * ID number of the discount code
	 *
	 * @var int
	 * @access public
	 * @since  2.9
	 */
	public $id = 0;

	/**
	 * Name of the discount code
	 *
	 * @var string
	 * @access public
	 * @since  2.9
	 */
	public $name;

	/**
	 * Discount code description
	 *
	 * @var string
	 * @access public
	 * @since  2.9
	 */
	public $description;

	/**
	 * Amount the discount is for. This will either be a percentage
	 * or a flat value.
	 *
	 * @var int|float
	 * @access public
	 * @since  2.9
	 */
	public $amount;

	/**
	 * Type of discount: either "flat" or "%"
	 *
	 * @var string
	 * @access public
	 * @since  2.9
	 */
	public $unit;

	/**
	 * Discount code
	 *
	 * @var string
	 * @access public
	 * @since  2.9
	 */
	public $code;

	/**
	 * Number of times the discount has been used
	 *
	 * @var int
	 * @access public
	 * @since  2.9
	 */
	public $use_count;

	/**
	 * Maximum uses for this discount, or "0" for unlimited
	 *
	 * @var int
	 * @access public
	 * @since  2.9
	 */
	public $max_uses;

	/**
	 * Status of the discount, either "active" or "disabled"
	 *
	 * @var string
	 * @access public
	 * @since  2.9
	 */
	public $status;

	/**
	 * Date the discount expires (or empty if no expiration date)
	 *
	 * @var string
	 * @access public
	 * @since  2.9
	 */
	public $expiration;

	/**
	 * Subscription level ID the code may be used for, or 0 if it can be used
	 * with any level
	 *
	 * @var int
	 * @access public
	 * @since  2.9
	 */
	public $subscription_id;

	/**
	 * Discounts database object
	 *
	 * @var RCP_Discounts
	 * @access public
	 * @since  2.9
	 */
	public $discounts_db;

	/**
	 * RCP_Discount constructor.
	 *
	 * @param int|string $_id_or_code Discount ID or code.
	 *
	 * @access public
	 * @since  2.9
	 * @return void
	 */
	public function __construct( $_id_or_code = 0 ) {

		$this->discounts_db = new RCP_Discounts();

		if ( is_numeric( $_id_or_code ) ) {
			$discount = $this->discounts_db->get_discount( absint( $_id_or_code ) );
		} else {
			$discount = $this->discounts_db->get_by( 'code', strtolower( $_id_or_code ) );
		}

		if ( empty( $discount ) ) {
			return;
		}

		$this->setup_discount( $discount );

	}

	/**
	 * Setup the discount
	 *
	 * @param object $discount Discount object from the database.
	 *
	 * @access private
	 * @since  2.9
	 * @return void
	 */
	private function setup_discount( $discount ) {

		foreach ( $discount as $key => $value ) {
			$this->$key = $value;
		}

	}

	/**
	 * Updates this discount code
	 *
	 * @param array $args Array of arguments to update.
	 *
	 * @access public
	 * @since  2.9
	 * @return bool Whether or not the update was successful.
	 */
	public function update( $args = array() ) {

		return $this->discounts_db->update( $this->id, $args );

	}

	/**
	 * Deletes this discount code
	 *
	 * @access public
	 * @since  2.9
	 * @return void
	 */
	public function delete() {

		$this->discounts_db->delete( $this->id );

	}

	/**
	 * Checks whether the discount code has a subscription associated
	 *
	 * @access public
	 * @since  2.9
	 * @return bool
	 */
	public function has_subscription_id() {

		return ! empty( $this->subscription_id );

	}

	/**
	 * Increase the use count of the discount by 1
	 *
	 * @access public
	 * @since  2.9
	 * @return void
	 */
	public function increase_uses() {

		$uses = absint( $this->use_count ) + 1;
		$this->update( array( 'use_count' => $uses ) );

	}

	/**
	 * Decrease the use count of the discount by 1
	 *
	 * @access public
	 * @since  2.9
	 * @return void
	 */
	public function decrease_uses() {

		$uses = absint( $this->use_count ) - 1;

		if ( $uses < 0 ) {
			$uses = 0;
		}

		$this->update( array( 'use_count' => $uses ) );

	}

	/**
	 * Check if this discount is maxed out
	 *
	 * @access public
	 * @since  2.9
	 * @return bool
	 */
	public function is_maxed_out() {

		$ret = false;

		if ( ! empty( $this->max_uses ) && $this->max_uses > 0 ) {
			if ( $this->use_count >= $this->max_uses ) {
				$ret = true;
			}
		}

		/**
		 * Filters whether the discount is maxed out
		 *
		 * @param bool $ret       Whether or not the discount is maxed out.
		 * @param int  $id        ID of the discount code being checked.
		 * @param int  $use_count Number of uses so far.
		 * @param int  $max_uses  Maximum uses allowed.
		 */
		$ret = apply_filters( 'rcp_is_discount_maxed_out', $ret, $this->id, $this->use_count, $this->max_uses );

		return (bool) $ret;

	}

	/**
	 * Checks if the discount code is expired
	 *
	 * @access public
	 * @since  2.9
	 * @return bool
	 */
	public function is_expired() {

		$ret = false;

		if ( ! empty( $this->expiration ) ) {

			if ( strtotime( 'NOW', current_time( 'timestamp' ) ) > strtotime( $this->expiration, current_time( 'timestamp' ) ) ) {
				$ret = true;
			}

		}

		/**
		 * Filters whether the discount is expired
		 *
		 * @param bool   $ret        Whether or not the discount is expired.
		 * @param int    $id         ID of the discount code.
		 * @param string $expiration Expiration date.
		 */
		$ret = apply_filters( 'rcp_is_discount_expired', $ret, $this->id, $this->expiration );

		return (bool) $ret;

	}

	/**
	 * Add the discount to a user's history
	 *
	 * @param int $user_id ID of the user to add the discount to.
	 *
	 * @access public
	 * @since  2.9
	 * @return void
	 */
	public function add_to_user( $user_id = 0 ) {

		$user_discounts = get_user_meta( $user_id, 'rcp_user_discounts', true );

		if ( ! is_array( $user_discounts ) ) {
			$user_discounts = array();
		}

		$user_discounts[] = $this->code;

		/**
		 * Triggers before the discount code is stored in the user's meta.
		 *
		 * @param string $code    Discount code being added.
		 * @param int    $user_id ID of the user the code is being added to.
		 */
		do_action( 'rcp_pre_store_discount_for_user', $this->code, $user_id );

		update_user_meta( $user_id, 'rcp_user_discounts', $user_discounts );

		/**
		 * Triggers after the discount code is stored in the user's meta.
		 *
		 * @param string $code    Discount code being added.
		 * @param int    $user_id ID of the user the code is being added to.
		 */
		do_action( 'rcp_store_discount_for_user', $this->code, $user_id );

	}

	/**
	 * Remove this discount from a user's history
	 *
	 * @param int $user_id ID of the user to remove the discount from.
	 *
	 * @access public
	 * @since  2.9
	 * @return bool Whether or not the discount was removed.
	 */
	public function remove_from_user( $user_id ) {

		$user_discounts = get_user_meta( $user_id, 'rcp_user_discounts', true );

		if ( ! is_array( $user_discounts ) ) {
			$user_discounts = array();
		}

		// Reverse the array to remove the last instance of the discount.
		$key = array_search( $this->code, array_reverse( $user_discounts, true ) );

		if ( false !== $key ) {
			unset( $user_discounts[ $key ] );

			/**
			 * Triggers before the discount code is removed from the user's meta.
			 *
			 * @param string $code    Discount code being added.
			 * @param int    $user_id ID of the user the code is being added to.
			 */
			do_action( 'rcp_pre_remove_discount_from_user', $this->code, $user_id );

			if ( empty( $user_discounts ) ) {
				delete_user_meta( $user_id, 'rcp_user_discounts' );
			} else {
				update_user_meta( $user_id, 'rcp_user_discounts', $user_discounts );
			}

			/**
			 * Triggers after the discount code is removed from the user's meta.
			 *
			 * @param string $code    Discount code being added.
			 * @param int    $user_id ID of the user the code is being added to.
			 */
			do_action( 'rcp_remove_discount_from_user', $this->code, $user_id );

			return true;
		}

		return false;

	}

	/**
	 * Checks if a user has used this discount
	 *
	 * @param int $user_id ID of the user to check.
	 *
	 * @access public
	 * @since  2.9
	 * @return bool
	 */
	public function user_has_used( $user_id ) {

		$ret = false;

		$user_discounts = get_user_meta( $user_id, 'rcp_user_discounts', true );

		if ( is_array( $user_discounts ) && in_array( $this->code, $user_discounts ) ) {
			$ret = true;
		}

		$ret = apply_filters( 'rcp_user_has_used_discount', $ret, $user_id, $this->code );

		return (bool) $ret;

	}

}