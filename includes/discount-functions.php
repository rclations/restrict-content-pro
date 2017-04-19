<?php
/**
 * Discount Functions
 *
 * Functions for getting non-member specific info about discount codes.
 *
 * @package     Restrict Content Pro
 * @subpackage  Discount Functions
 * @copyright   Copyright (c) 2017, Restrict Content Pro
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 */

/**
 * Retrieves all discount codes
 *
 * @return array|bool
 */
function rcp_get_discounts() {
	$discounts_db = new RCP_Discounts();
	$discounts    = $discounts_db->get_discounts();
	if( $discounts ) {
		return $discounts;
	}
	return false;
}

/**
 * Check if we have any discounts
 *
 * @return bool
 */
function rcp_has_discounts() {
	$discounts = new RCP_Discounts();
	if( $discounts->get_discounts( array( 'status' => 'active' )) ) {
		return true;
	}
	return false;
}

/**
 * Returns the DB object for a given discount code.
 *
 * @param int $id The ID number of the discount to retrieve data for.
 *
 * @return object
 */
function rcp_get_discount_details( $id ) {
	return new RCP_Discount( $id );
}

/**
 * Returns the DB object for a discount code, based on the code provided.
 *
 * @param string $code The discount code to retrieve all information for.
 *
 * @return object
 */
function rcp_get_discount_details_by_code( $code ) {
	return new RCP_Discount( $code );
}

/**
 * Check whether a given discount code is valid.
 *
 * @param string $code            The discount code to validate.
 * @param int    $subscription_id ID of the subscription level you want to use the code for.
 *
 * @return bool
 */
function rcp_validate_discount( $code, $subscription_id = 0 ) {

	$ret       = false;
	$discount  = new RCP_Discount( $code );

	if( ! empty( $discount ) && $discount->status == 'active' ) {

		// Make sure discount is not expired and not maxed out
		if( ! $discount->is_expired() && ! $discount->is_maxed_out() ) {
			$ret = true;
		}

		// If the discount is restricted to a level, ensure that's the level being signed up for
		if( $discount->has_subscription_id() ) {
			if( $subscription_id != $discount->subscription_id ) {
				$ret = false;
			}
		}

		// Ensure codes match, case insensitive
		if( strcasecmp( $code, $discount->code ) != 0 ) {
			$ret = false;
		}

	}

	return apply_filters( 'rcp_is_discount_valid', $ret, $discount, $subscription_id );
}

/**
 * Get the status of a discount code
 *
 * @param int $code_id The discount code ID.
 *
 * @return string|bool Status name on success, false on failure.
 */
function rcp_get_discount_status( $code_id ) {
	$code = rcp_get_discount_details( $code_id );
	if( $code ) {
		return $code->status;
	}
	return false;
}

/**
 * Checks whether a discount code has any uses left.
 *
 * @param int|string $code_id The ID or code of the discount code to check.
 *
 * @return bool True if uses left, false otherwise.
 */
function rcp_discount_has_uses_left( $code_id ) {

	$discount = new RCP_Discount( $code_id );

	return ! $discount->is_maxed_out();

}

/**
 * Checks whether a discount code has not expired.
 *
 * @param int|string $code_id The ID or code of the discount code to check.
 *
 * @return bool True if not expired, false if expired.
 */
function rcp_is_discount_not_expired( $code_id ) {

	$discount = new RCP_Discount( $code_id );

	return ! $discount->is_expired();

}

/**
 * Calculates a subscription price after applying a discount.
 *
 * @param float  $base_price The original subscription price.
 * @param float  $amount     The discount amount.
 * @param string $type       The kind of discount, either '%' or 'flat'.
 *
 * @return string
 */
function rcp_get_discounted_price( $base_price, $amount, $type ) {

	/**
	 * @var RCP_Discounts $rcp_discounts_db
	 */
	global $rcp_discounts_db;

	return $rcp_discounts_db->calc_discounted_price( $base_price, $amount, $type );
}

/**
 * Stores a discount code in a user's history.
 *
 * @param string $code            The discount code to store.
 * @param int    $user_id         The ID of the user to store the discount for.
 * @param object $discount_object The object containing all info about the discount.
 *
 * @return void
 */
function rcp_store_discount_use_for_user( $code, $user_id, $discount_object ) {

	$discount = new RCP_Discount( $code );
	$discount->add_to_user( $user_id );

}

/**
 * Checks whether a user has used a particular discount code.
 * This is used to prevent users from spamming discount codes.
 *
 * @param int    $user_id The ID of the user to check.
 * @param string $code    The discount code to check against the user ID.
 *
 * @return bool
 */
function rcp_user_has_used_discount( $user_id, $code ) {

	$discount = new RCP_Discount( $code );

	return $discount->user_has_used( $user_id );

}

/**
 * Increase the usage count of a discount code.
 *
 * @param int $code_id The ID of the discount.
 *
 * @return void
 */
function rcp_increase_code_use( $code_id ) {

	$discount = new RCP_Discount( $code_id );
	$discount->increase_uses();

}

/**
 * Returns the number of times a discount code has been used.
 *
 * @param int|string $code The ID or code of the discount.
 *
 * @return int|string The number of times the discount code has been used or the string 'None'.
 */
function rcp_count_discount_code_uses( $code ) {

	$discount = new RCP_Discount( $code );

	if ( ! empty( $discount->use_count ) ) {
		return $discount->use_count;
	} else {
		return __( 'None', 'rcp' );
	}

}

/**
 * Returns a formatted discount amount with a '%' sign appended (percentage-based) or with the
 * currency sign added to the amount (flat discount rate).
 *
 * @param float  $amount Discount amount.
 * @param string $type   Discount amount - either '%' or 'flat'.
 *
 * @return string
 */
function rcp_discount_sign_filter( $amount, $type ) {
	$discount = '';

	if( $type == '%' ) {
		$discount = $amount . '%';
	} elseif( $type == 'flat' ) {
		$discount = rcp_currency_filter( $amount );
	}

	return $discount;
}

/**
 * Check PayPal return price after applying discount.
 *
 * @param float $price
 * @param float $amount
 * @param float $amount2
 * @param int $user_id
 *
 * @return bool
 */
function rcp_check_paypal_return_price_after_discount( $price, $amount, $amount2, $user_id ) {

	// get an array of all discount codes this user has used
	$user_discounts = get_user_meta( $user_id, 'rcp_user_discounts', true );

	if( ! is_array( $user_discounts ) || $user_discounts == '' ) {
		// this user has never used a discount code
		return false;
	}

	foreach( $user_discounts as $discount_code ) {
		if( ! rcp_validate_discount( $discount_code ) ) {
			// discount code is inactive
			return false;
		}

		$code_details     = new RCP_Discount( $discount_code );
		$discounted_price = rcp_get_discounted_price( $price, $code_details->amount, $code_details->unit );

		if( $discounted_price == $amount || $discounted_price == $amount2 ) {
			return true;
		}
	}

	return false;

}