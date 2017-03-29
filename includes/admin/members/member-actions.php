<?php
/**
 * Member Actions
 *
 * @package     restrict-content-pro
 * @subpackage  Admin/Member Actions
 * @copyright   Copyright (c) 2017, Restrict Content Pro
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       2.9
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Edit a member
 *
 * @since 2.9
 * @return void
 */
function rcp_process_edit_member() {

	if ( ! wp_verify_nonce( $_POST['rcp_edit_member_nonce'], 'rcp_edit_member_nonce' ) ) {
		wp_die( __( 'Nonce verification failed.', 'rcp' ) );
	}

	if ( ! current_user_can( 'rcp_manage_members' ) ) {
		wp_die( __( 'You do not have permission to perform this action.', 'rcp' ) );
	}

	$levels     = new RCP_Levels();
	$user_id    = absint( $_POST['user'] );
	$member     = new RCP_Member( $user_id );
	$email      = sanitize_text_field( $_POST['email'] );
	$status     = sanitize_text_field( $_POST['status'] );
	$level_id   = absint( $_POST['level'] );
	$expiration = isset( $_POST['expiration'] ) ? sanitize_text_field( $_POST['expiration'] ) : 'none';
	$expiration = 'none' !== $expiration ? date( 'Y-m-d 23:59:59', strtotime( $_POST['expiration'], current_time( 'timestamp' ) ) ) : $expiration;

	if ( isset( $_POST['notes'] ) ) {
		update_user_meta( $user_id, 'rcp_notes', wp_kses( $_POST['notes'], array() ) );
	}

	if ( ! empty( $_POST['expiration'] ) ) {
		$member->set_expiration_date( $expiration );
	}

	if ( isset( $_POST['level'] ) ) {

		$current_id = rcp_get_subscription_id( $user_id );
		$new_level  = $levels->get_level( $level_id );
		$old_level  = $levels->get_level( $current_id );

		if ( $current_id != $level_id ) {

			$member->set_subscription_id( $level_id );

			// Remove the old user role
			$role = ! empty( $old_level->role ) ? $old_level->role : 'subscriber';
			$member->remove_role( $role );

			// Add the new user role
			$role = ! empty( $new_level->role ) ? $new_level->role : 'subscriber';
			$member->add_role( $role );

			// Set joined date for the new subscription
			$member->set_joined_date( '', $level_id );

		}
	}

	if ( isset( $_POST['recurring'] ) ) {
		$member->set_recurring( true );
	} else {
		$member->set_recurring( false );
	}

	if ( isset( $_POST['trialing'] ) ) {
		update_user_meta( $user_id, 'rcp_is_trialing', 'yes' );
	} else {
		delete_user_meta( $user_id, 'rcp_is_trialing' );
	}

	if ( isset( $_POST['signup_method'] ) ) {
		update_user_meta( $user_id, 'rcp_signup_method', $_POST['signup_method'] );
	}

	if ( isset( $_POST['cancel_subscription'] ) && $member->can_cancel() ) {
		$member->cancel_payment_profile();
	}

	if ( $status !== $member->get_status() ) {
		$member->set_status( $status );
	}

	if ( isset( $_POST['payment-profile-id'] ) ) {
		$member->set_payment_profile_id( $_POST['payment-profile-id'] );
	}

	if ( $email != $member->user_email ) {
		wp_update_user( array( 'ID' => $user_id, 'user_email' => $email ) );
	}

	wp_safe_redirect( admin_url( 'admin.php?page=rcp-members&edit_member=' . $user_id . '&rcp_message=user_updated' ) );
	exit;

}
add_action( 'rcp_edit-member', 'rcp_process_edit_member' );

/**
 * Add a subscription to an existing member
 *
 * @since 2.9
 * @return void
 */
function rcp_process_add_member_subscription() {

	if ( ! wp_verify_nonce( $_POST['rcp_add_member_nonce'], 'rcp_add_member_nonce' ) ) {
		wp_die( __( 'Nonce verification failed.', 'rcp' ) );
	}

	if ( ! current_user_can( 'rcp_manage_members' ) ) {
		wp_die( __( 'You do not have permission to perform this action.', 'rcp' ) );
	}

	if ( empty( $_POST['level'] ) || empty( $_POST['user'] ) ) {
		wp_die( __( 'Please fill out all fields.', 'rcp' ) );
	}

	// Don't add if chosen expiration date is in the past.
	if ( isset( $_POST['expiration'] ) && strtotime( 'NOW', current_time( 'timestamp' ) ) > strtotime( $_POST['expiration'], current_time( 'timestamp' ) ) && 'none' !== $_POST['expiration'] ) {
		wp_safe_redirect( admin_url( 'admin.php?page=rcp-members&rcp_message=user_not_added' ) );
		exit;
	}

	$levels = new RCP_Levels();
	$user   = get_user_by( 'login', $_POST['user'] );

	if ( ! $user ) {
		wp_die( __( 'You entered a username that does not exist.', 'rcp' ) );
	}

	$member       = new RCP_Member( $user->ID );
	$expiration   = isset( $_POST['expiration'] ) ? sanitize_text_field( $_POST['expiration'] ) : 'none';
	$level_id     = absint( $_POST['level'] );
	$subscription = $levels->get_level( $level_id );

	if ( ! $subscription ) {
		wp_die( __( 'Please supply a valid subscription level.', 'rcp' ) );
	}

	$member->set_expiration_date( $expiration );

	$new_subscription = get_user_meta( $user->ID, '_rcp_new_subscription', true );

	if ( empty( $new_subscription ) ) {
		update_user_meta( $user->ID, '_rcp_new_subscription', '1' );
	}

	update_user_meta( $user->ID, 'rcp_signup_method', 'manual' );

	$member->set_subscription_id( $level_id );

	$status = $subscription->price == 0 ? 'free' : 'active';

	$member->set_status( $status );

	// Add the new user role
	$role = ! empty( $subscription->role ) ? $subscription->role : 'subscriber';
	$user->add_role( $role );

	// Set joined date for the new subscription
	$member->set_joined_date( '', $level_id );

	if ( isset( $_POST['recurring'] ) ) {
		update_user_meta( $user->ID, 'rcp_recurring', 'yes' );
	} else {
		delete_user_meta( $user->ID, 'rcp_recurring' );
	}

	wp_safe_redirect( admin_url( 'admin.php?page=rcp-members&rcp_message=user_added' ) );
	exit;

}
add_action( 'rcp_add-subscription', 'rcp_process_add_member_subscription' );

/**
 * Process bulk edit members
 *
 * @since 2.9
 * @return void
 */
function rcp_process_bulk_edit_members() {

	if ( ! wp_verify_nonce( $_POST['rcp_bulk_edit_nonce'], 'rcp_bulk_edit_nonce' ) ) {
		wp_die( __( 'Nonce verification failed.', 'rcp' ) );
	}

	if ( ! current_user_can( 'rcp_manage_members' ) ) {
		wp_die( __( 'You do not have permission to perform this action.', 'rcp' ) );
	}

	if ( empty( $_POST['member-ids'] ) ) {
		wp_die( __( 'Please select at least one member to edit.', 'rcp' ) );
	}

	$member_ids = array_map( 'absint', $_POST['member-ids'] );
	$action     = ! empty( $_POST['rcp-bulk-action'] ) ? sanitize_text_field( $_POST['rcp-bulk-action'] ) : false;

	foreach ( $member_ids as $member_id ) {

		$member = new RCP_Member( $member_id );

		if ( ! empty( $_POST['expiration'] ) && 'delete' !== $action ) {
			$member->set_expiration_date( date( 'Y-m-d H:i:s', strtotime( $_POST['expiration'], current_time( 'timestamp' ) ) ) );
		}

		if ( $action ) {

			switch ( $action ) {

				case 'mark-active' :

					$member->set_status( 'active' );

					break;

				case 'mark-expired' :

					$member->set_status( 'expired' );

					break;

				case 'mark-cancelled' :

					$member->cancel();

					break;

			}

		}

	}

	wp_safe_redirect( admin_url( 'admin.php?page=rcp-members&rcp_message=members_updated' ) );
	exit;

}
add_action( 'rcp_bulk_edit_members', 'rcp_process_bulk_edit_members' );

/**
 * Cancel a member from the Members table
 *
 * @since 2.9
 * @return void
 */
function rcp_process_cancel_member() {

	if ( ! current_user_can( 'rcp_manage_members' ) ) {
		wp_die( __( 'You do not have permission to perform this action.', 'rcp' ) );
	}

	if ( ! isset( $_GET['member_id'] ) ) {
		wp_die( __( 'Please select a member.', 'rcp' ) );
	}

	rcp_cancel_member_payment_profile( urldecode( absint( $_GET['member_id'] ) ) );
	wp_safe_redirect( admin_url( add_query_arg( 'rcp_message', 'member_cancelled', 'admin.php?page=rcp-members' ) ) );
	exit;

}
add_action( 'rcp_cancel_member', 'rcp_process_cancel_member' );

/**
 * Re-send a member's verification email
 *
 * @since 2.9
 * @return void
 */
function rcp_process_resend_verification() {

	if ( ! wp_verify_nonce( $_GET['_wpnonce'], 'rcp-verification-nonce' ) ) {
		wp_die( __( 'Nonce verification failed.', 'rcp' ) );
	}

	if ( ! current_user_can( 'rcp_manage_members' ) ) {
		wp_die( __( 'You do not have permission to perform this action.', 'rcp' ) );
	}

	if ( ! isset( $_GET['member_id'] ) ) {
		wp_die( __( 'Please select a member.', 'rcp' ) );
	}

	rcp_send_email_verification( urldecode( absint( $_GET['member_id'] ) ) );
	wp_safe_redirect( admin_url( add_query_arg( 'rcp_message', 'verification_sent', 'admin.php?page=rcp-members' ) ) );
	exit;

}
add_action( 'rcp_send_verification', 'rcp_process_resend_verification' );