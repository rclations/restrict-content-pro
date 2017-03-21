<?php
/**
 * Page for Restricting a Post Type
 *
 * @package     restrict-content-pro
 * @subpackage  Admin/Restrict Post Type
 * @copyright   Copyright (c) 2017, Restrict Content Pro
 * @license     GPL2+
 * @since       2.9
 */

/**
 * Render page for restricting an entire post type.
 *
 * @since 2.9
 * @return void
 */
function rcp_restrict_post_type() {

	$screen            = get_current_screen();
	$post_type         = ! empty( $screen->post_type ) ? $screen->post_type : 'post';
	$post_type_details = get_post_type_object( $post_type );
	?>
	<div class="wrap">
		<h1><?php printf( __( 'Restrict All %s', 'rcp' ), $post_type_details->labels->name ); ?></h1>

		<div class="metabox-holder">
			<div class="postbox">
				<div class="inside">
					<?php
					do_action( 'rcp_restrict_post_type_fields_before' );

					include RCP_PLUGIN_DIR . 'includes/admin/restrict-post-type-view.php';

					do_action( 'rcp_restrict_post_type_fields_after' );
					?>
				</div>
			</div>
		</div>
	</div>
	<?php

}

/**
 * Save post type restrictions
 *
 * @since 2.9
 * @return void
 */
function rcp_save_post_type_restrictions() {

	if ( ! is_admin() ) {
		return;
	}

	if ( ! isset( $_POST['rcp_save_post_type_restrictions_nonce'] ) || ! wp_verify_nonce( $_POST['rcp_save_post_type_restrictions_nonce'], 'rcp_save_post_type_restrictions' ) ) {
		return;
	}

	$post_type = isset( $_POST['rcp_post_type'] ) ? $_POST['rcp_post_type'] : 'post';

	// Check permissions
	if ( 'page' == $post_type ) {
		if ( ! current_user_can( 'edit_pages' ) ) {
			return;
		}
	} elseif ( ! current_user_can( 'edit_posts' ) ) {
		return;
	}

	$is_paid                     = false;
	$restrict_by                 = sanitize_text_field( $_POST['rcp_restrict_by'] );
	$restricted_post_types       = rcp_get_restricted_post_types();
	$this_post_type_restrictions = rcp_get_post_type_restrictions( $post_type );

	if ( ! is_array( $this_post_type_restrictions ) ) {
		$this_post_type_restrictions = array();
	}

	switch ( $restrict_by ) {

		case 'unrestricted' :

			unset( $this_post_type_restrictions['access_level'] );
			unset( $this_post_type_restrictions['subscription_level'] );
			unset( $this_post_type_restrictions['user_level'] );

			break;


		case 'subscription-level' :

			$level_set = sanitize_text_field( $_POST['rcp_subscription_level_any_set'] );

			switch ( $level_set ) {

				case 'any' :

					$this_post_type_restrictions['subscription_level'] = 'any';

					break;

				case 'any-paid' :

					$is_paid                                           = true;
					$this_post_type_restrictions['subscription_level'] = 'any-paid';

					break;

				case 'specific' :

					$levels = array_map( 'absint', $_POST['rcp_subscription_level'] );

					foreach ( $levels as $level ) {

						$price = rcp_get_subscription_price( $level );
						if ( ! empty( $price ) ) {
							$is_paid = true;
							break;
						}

					}

					$this_post_type_restrictions['subscription_level'] = $levels;

					break;

			}

			// Remove unneeded fields
			unset( $this_post_type_restrictions['access_level'] );

			break;


		case 'access-level' :

			$this_post_type_restrictions['access_level'] = absint( $_POST['rcp_access_level'] );

			$levels = rcp_get_subscription_levels();
			foreach ( $levels as $level ) {

				if ( ! empty( $level->price ) ) {
					$is_paid = true;
					break;
				}

			}

			// Remove unneeded fields
			unset( $this_post_type_restrictions['subscription_level'] );

			break;

		case 'registered-users' :

			// Remove unneeded fields
			unset( $this_post_type_restrictions['access_level'] );

			// Remove unneeded fields
			unset( $this_post_type_restrictions['subscription_level'] );

			$levels = rcp_get_subscription_levels();
			foreach ( $levels as $level ) {

				if ( ! empty( $level->price ) ) {
					$is_paid = true;
					break;
				}

			}

			break;

	}


	$user_role = sanitize_text_field( $_POST['rcp_user_level'] );

	if ( 'unrestricted' !== $_POST['rcp_restrict_by'] ) {
		$this_post_type_restrictions['user_level'] = $user_role;
	}

	if ( $is_paid ) {
		$this_post_type_restrictions['is_paid'] = $is_paid;
	} else {
		unset( $this_post_type_restrictions['is_paid'] );
	}

	// Save the restrictions.
	if ( ! empty( $this_post_type_restrictions ) ) {
		$restricted_post_types[ $post_type ] = $this_post_type_restrictions;
	} else {
		unset( $restricted_post_types[ $post_type ] );
	}

	update_option( 'rcp_restricted_post_types', $restricted_post_types );

	do_action( 'rcp_save_post_type_restrictions', $post_type );

	$url = add_query_arg( array(
		'post_type'   => urlencode( $post_type ),
		'page'        => 'rcp-restrict',
		'rcp_message' => 'post-type-updated'
	) );

	wp_safe_redirect( $url );
	exit;

}

add_action( 'admin_init', 'rcp_save_post_type_restrictions' );