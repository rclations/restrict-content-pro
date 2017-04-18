<?php
/**
 * Edit Payment Page
 *
 * @package     Restrict Content Pro
 * @subpackage  Admin/Edit Payment
 * @copyright   Copyright (c) 2017, Restrict Content Pro
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 */

$payment_id   = ! empty( $_GET['payment_id'] ) ? absint( $_GET['payment_id'] ) : 0;
$payment      = new RCP_Payment( $payment_id );
$user         = get_userdata( $payment->user_id );
$subscription = rcp_get_subscription_details( $payment->subscription_level_id );
?>
<h1>
	<?php _e( 'Edit Payment', 'rcp' ); ?> -
	<a href="<?php echo admin_url( '/admin.php?page=rcp-payments' ); ?>" class="button-secondary">
		<?php _e( 'Cancel', 'rcp' ); ?>
	</a>
</h1>
<form id="rcp-edit-payment" action="" method="post">
	<table class="form-table">
		<tbody>
		<tr valign="top">
			<th scope="row" valign="top">
				<label for="rcp-user-id"><?php _e( 'User', 'rcp' ); ?></label>
			</th>
			<td>
				<input type="text" name="user" autocomplete="off" id="rcp-user" class="regular-text rcp-user-search" value="<?php echo is_object( $user ) ? esc_attr( $user->user_login ) : ''; ?>"/>
				<img class="rcp-ajax waiting" src="<?php echo admin_url( 'images/wpspin_light.gif' ); ?>" style="display: none;"/>
				<div id="rcp_user_search_results"></div>
				<p class="description"><?php _e( 'The user name this payment belongs to.', 'rcp' ); ?></p>
			</td>
		</tr>
		<tr valign="top">
			<th scope="row" valign="top">
				<label for="rcp-amount"><?php _e( 'Amount', 'rcp' ); ?></label>
			</th>
			<td>
				<input name="amount" id="rcp-amount" pattern="^[+\-]?[0-9]{1,3}(?:,?[0-9]{3})*(\.[0-9]{2})?$" title="<?php _e( 'Please enter a payment amount in the format of 1.99', 'rcp' ); ?>" min="0.00" value="<?php echo esc_attr( $payment->amount ); ?>"/>
				<p class="description"><?php _e( 'The amount of this payment', 'rcp' ); ?></p>
			</td>
		</tr>
		<tr valign="top">
			<th scope="row" valign="top">
				<label for="rcp-subscription-level"><?php _e( 'Subscription Level', 'rcp' ); ?></label>
			</th>
			<td>
				<?php
				if ( ! empty( $subscription ) ) {
					printf( __( '%s - <a href="%s">Edit</a>', 'rcp' ), esc_html( $subscription->name ), esc_url( admin_url( 'admin.php?page=rcp-member-levels&edit_subscription=' . absint( $subscription->id ) ) ) );
				}
				?>
				<p class="description"><?php _e( 'Subscription level this payment was for', 'rcp' ); ?></p>
			</td>
		</tr>
		<tr valign="top">
			<th scope="row" valign="top">
				<label for="rcp-discount-code"><?php _e( 'Discount Code', 'rcp' ); ?></label>
			</th>
			<td>
				<?php echo $payment->discount_code ? esc_html( $payment->discount_code ) : __( 'None', 'rcp' ); ?>
				<p class="description"><?php _e( 'Discount code used when making this payment', 'rcp' ); ?></p>
			</td>
		</tr>
		<tr valign="top">
			<th scope="row" valign="top">
				<label for="rcp-date"><?php _e( 'Payment Date', 'rcp' ); ?></label>
			</th>
			<td>
				<input name="date" id="rcp-date" type="text" class="rcp-datepicker" value="<?php echo esc_attr( date( 'Y-m-d', strtotime( $payment->date, current_time( 'timestamp' ) ) ) ); ?>"/>
				<p class="description"><?php _e( 'The date for this payment in the format of yyyy-mm-dd', 'rcp' ); ?></p>
			</td>
		</tr>
		<tr valign="top">
			<th scope="row" valign="top">
				<label for="rcp-gateway"><?php _e( 'Gateway', 'rcp' ); ?></label>
			</th>
			<td>
				<?php echo $payment->gateway ? ucwords( $payment->gateway ) : __( 'Unknown', 'rcp' ); ?>
				<p class="description"><?php _e( 'Gateway used to make the payment', 'rcp' ); ?></p>
			</td>
		</tr>
		<tr valign="top">
			<th scope="row" valign="top">
				<label for="rcp-date"><?php _e( 'Transaction ID', 'rcp' ); ?></label>
			</th>
			<td>
				<input name="transaction-id" id="rcp-transaction-id" type="text" class="regular-text" value="<?php echo esc_attr( $payment->transaction_id ); ?>"/>
				<p class="description"><?php _e( 'The transaction ID for this payment, if any. Click to view in merchant account:', 'rcp' );
					echo '&nbsp;' . rcp_get_merchant_transaction_id_link( $payment ); ?></p>
			</td>
		</tr>
		<tr valign="top">
			<th scope="row" valign="top">
				<label for="rcp-status"><?php _e( 'Status', 'rcp' ); ?></label>
			</th>
			<td>
				<select name="status" id="rcp-status">
					<option value="pending"<?php selected( $payment->status, 'pending' ); ?>><?php _e( 'Pending', 'rcp' ); ?></option>
					<option value="complete"<?php selected( $payment->status, 'complete' ); ?>><?php _e( 'Complete', 'rcp' ); ?></option>
					<option value="refunded"<?php selected( $payment->status, 'failed' ); ?>><?php _e( 'Failed', 'rcp' ); ?></option>
					<option value="refunded"<?php selected( $payment->status, 'refunded' ); ?>><?php _e( 'Refunded', 'rcp' ); ?></option>
					<option value="refunded"<?php selected( $payment->status, 'abandoned' ); ?>><?php _e( 'Abandoned', 'rcp' ); ?></option>
				</select>
				<p class="description"><?php _e( 'The status of this payment.', 'rcp' ); ?></p>
			</td>
		</tr>
		<tr valign="top">
			<th scope="row" valign="top">
				<label for="rcp-status"><?php _e( 'Invoice', 'rcp' ); ?></label>
			</th>
			<td>
				<a href="<?php echo esc_url( rcp_get_invoice_url( $payment_id ) ); ?>" class="button-secondary" target="_blank"><?php _e( 'View Invoice', 'rcp' ); ?></a>
			</td>
		</tr>
		</tbody>
	</table>
	<p class="submit">
		<input type="hidden" name="rcp-action" value="edit-payment"/>
		<input type="hidden" name="payment-id" value="<?php echo esc_attr( $payment_id ); ?>"/>
		<input type="submit" value="<?php _e( 'Update Payment', 'rcp' ); ?>" class="button-primary"/>
	</p>
	<?php wp_nonce_field( 'rcp_edit_payment_nonce', 'rcp_edit_payment_nonce' ); ?>
</form>
