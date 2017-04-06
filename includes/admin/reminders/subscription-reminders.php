<?php
/**
 * Subscription Reminders Page
 *
 * @package     Restrict Content Pro
 * @subpackage  Admin/Reminders/Subscription Reminders
 * @copyright   Copyright (c) 2017, Restrict Content Pro
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       2.9
 */

/**
 * Removes the Subscription Reminder menu link
 *
 * @since 2.9
 * @return void
 */
function rcp_hide_reminder_page() {
	remove_submenu_page( 'rcp-members', 'rcp-reminder' );
}

/**
 * Renders the add/edit subscription reminder notice screen
 *
 * @since 2.9
 * @return void
 */
function rcp_subscription_reminder_page() {

	include RCP_PLUGIN_DIR . 'includes/admin/reminders/subscription-reminder-view.php';

}

/**
 * Display subscription reminder table
 *
 * @param string $type Type to display (expiration or renewal).
 *
 * @since 2.9
 * @return void
 */
function rcp_subscription_reminder_table( $type = 'expiration' ) {

	$reminders  = new RCP_Reminders();
	$notices    = $reminders->get_notices( $type );
	$type_label = ( 'expiration' == $type ) ? __( 'Expiration', 'rcp' ) : __( 'Renewal', 'rcp' );
	?>
	<table id="rcp-<?php echo esc_attr( $type ); ?>-reminders" class="wp-list-table widefat fixed posts rcp-email-reminders">
		<thead>
		<tr>
			<th scope="col" class="rcp-reminder-subject-col"><?php _e( 'Subject', 'rcp' ); ?></th>
			<th scope="col" class="rcp-reminder-period-col"><?php _e( 'Send Period', 'rcp' ); ?></th>
			<th scope="col" class="rcp-reminder-action-col"><?php _e( 'Actions', 'rcp' ); ?></th>
		</tr>
		</thead>
		<?php if ( ! empty( $notices ) ) : $i = 1; ?>
			<?php foreach ( $notices as $key => $notice ) : $notice = $reminders->get_notice( $key ); ?>
				<tr<?php echo ( 0 == $i % 2 ) ? ' class="alternate"' : ''; ?>>
					<td><?php echo esc_html( $notice['subject'] ); ?></td>
					<td><?php echo esc_html( $reminders->get_notice_period_label( $key ) ); ?></td>
					<td>
						<a href="<?php echo esc_url( admin_url( 'admin.php?page=rcp-reminder&rcp-action=edit_subscription_reminder&notice=' . $key ) ); ?>" class="rcp-edit-reminder-notice" data-key="<?php echo esc_attr( $key ); ?>"><?php _e( 'Edit', 'rcp' ); ?></a>&nbsp;|
						<a href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin.php?page=rcp-reminder&rcp-action=send_test_reminder&notice-id=' . $key ), 'rcp_send_test_reminder' ) ); ?>" class="rcp-send-test-reminder-notice"><?php _e( 'Send Test Email', 'rcp' ); ?></a>&nbsp;|
						<a href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin.php?page=rcp-reminder&rcp-action=delete_subscription_reminder&notice-id=' . $key ), 'rcp_delete_reminder_notice' ) ); ?>" class="rcp-delete rcp-delete-reminder"><?php _e( 'Delete', 'rcp' ); ?></a>
					</td>
				</tr>
				<?php $i ++; endforeach; ?>
		<?php endif; ?>
	</table>
	<p>
		<a href="<?php echo esc_url( admin_url( 'admin.php?page=rcp-reminder&rcp-action=add_subscription_reminder&rcp_reminder_type=' . urlencode( $type ) ) ); ?>" class="button-secondary" id="rcp-add-renewal-notice"><?php printf( __( 'Add %s Reminder', 'rcp' ), $type_label ); ?></a>
	</p>
	<?php

}

/**
 * Add or edit reminder notice
 *
 * @since 2.9
 * @return void
 */
function rcp_process_add_edit_reminder_notice() {

	if ( ! is_admin() ) {
		return;
	}

	if ( ! isset( $_POST['rcp-action'] ) || 'add_edit_reminder_notice' != $_POST['rcp-action'] ) {
		return;
	}

	if ( ! current_user_can( 'rcp_manage_settings' ) ) {
		wp_die( __( 'You do not have permission to add reminder notices', 'rcp' ), __( 'Error', 'rcp' ), array( 'response' => 403 ) );
	}

	if ( ! wp_verify_nonce( $_POST['rcp_add_edit_reminder_nonce'], 'rcp_add_edit_reminder' ) ) {
		wp_die( __( 'Nonce verification failed', 'rcp' ), __( 'Error', 'rcp' ), array( 'response' => 403 ) );
	}

	$notice_id = absint( $_POST['notice-id'] ); // Add a new notice if 0.
	$subject   = isset( $_POST['subject'] ) ? sanitize_text_field( $_POST['subject'] ) : __( 'Your Subscription is About to Renew', 'rcp' );
	$period    = isset( $_POST['period'] ) ? sanitize_text_field( $_POST['period'] ) : '+1month';
	$message   = isset( $_POST['message'] ) ? wp_kses( stripslashes( $_POST['message'] ), wp_kses_allowed_html( 'post' ) ) : false;
	$type      = isset( $_POST['type'] ) ? sanitize_text_field( $_POST['type'] ) : 'renewal';

	if ( empty( $message ) ) {
		$message = 'Hello %name%,

Your subscription for %subscription_name% will renew on %expiration%.';
	}

	$reminders = new RCP_Reminders();
	$notices   = $reminders->get_notices();
	$settings  = array(
		'subject'     => $subject,
		'message'     => $message,
		'send_period' => $period,
		'type'        => $type
	);

	if ( $notice_id ) {
		$notices[ $notice_id ] = $settings;
		$redirect_url          = admin_url( 'admin.php?page=rcp-settings&rcp_message=reminder_added#emails' );
	} else {
		$notices[]    = $settings;
		$redirect_url = admin_url( 'admin.php?page=rcp-settings&rcp_message=reminder_created#emails' );
	}

	update_option( 'rcp_reminder_notices', $notices );

	wp_safe_redirect( $redirect_url );
	exit;

}

add_action( 'admin_init', 'rcp_process_add_edit_reminder_notice' );

/**
 * Delete a reminder notice
 *
 * @since 2.9
 * @return void
 */
function rcp_process_delete_reminder_notice() {

	if ( ! is_admin() ) {
		return;
	}

	if ( ! isset( $_GET['rcp-action'] ) || 'delete_subscription_reminder' != $_GET['rcp-action'] ) {
		return;
	}

	if ( ! current_user_can( 'rcp_manage_settings' ) ) {
		wp_die( __( 'You do not have permission to delete reminder notices', 'rcp' ), __( 'Error', 'rcp' ), array( 'response' => 403 ) );
	}

	if ( ! wp_verify_nonce( $_GET['_wpnonce'], 'rcp_delete_reminder_notice' ) ) {
		wp_die( __( 'Nonce verification failed', 'rcp' ), __( 'Error', 'rcp' ), array( 'response' => 401 ) );
	}

	if ( empty( $_GET['notice-id'] ) && 0 !== (int) $_GET['notice-id'] ) {
		wp_die( __( 'No reminder notice ID was provided', 'rcp' ), __( 'Error', 'rcp' ), array( 'response' => 400 ) );
	}

	$reminders = new RCP_Reminders();
	$notices   = $reminders->get_notices();
	unset( $notices[ absint( $_GET['notice-id'] ) ] );

	update_option( 'rcp_reminder_notices', $notices );

	wp_safe_redirect( admin_url( 'admin.php?page=rcp-settings&rcp_message=reminder_deleted#emails' ) );
	exit;

}

add_action( 'admin_init', 'rcp_process_delete_reminder_notice' );

/**
 * Send a test reminder notice
 *
 * @since 2.9
 * @return void
 */
function rcp_process_send_test_reminder_notice() {

	if ( ! is_admin() ) {
		return;
	}

	if ( ! isset( $_GET['rcp-action'] ) || 'send_test_reminder' != $_GET['rcp-action'] ) {
		return;
	}

	if ( ! current_user_can( 'rcp_manage_settings' ) ) {
		wp_die( __( 'You do not have permission to delete reminder notices', 'rcp' ), __( 'Error', 'rcp' ), array( 'response' => 403 ) );
	}

	if ( ! wp_verify_nonce( $_GET['_wpnonce'], 'rcp_send_test_reminder' ) ) {
		wp_die( __( 'Nonce verification failed', 'rcp' ), __( 'Error', 'rcp' ), array( 'response' => 401 ) );
	}

	if ( empty( $_GET['notice-id'] ) && 0 !== (int) $_GET['notice-id'] ) {
		wp_die( __( 'No reminder notice ID was provided', 'rcp' ), __( 'Error', 'rcp' ), array( 'response' => 400 ) );
	}

	$reminders = new RCP_Reminders();
	$reminders->send_test_notice( absint( $_GET['notice-id'] ) );

	wp_safe_redirect( admin_url( 'admin.php?page=rcp-settings&rcp_message=test_reminder_sent#emails' ) );
	exit;

}

add_action( 'admin_init', 'rcp_process_send_test_reminder_notice' );