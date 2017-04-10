<?php
/**
 * Upgrade class
 *
 * This class handles database upgrade routines between versions
 *
 * @package     Restrict Content Pro
 * @copyright   Copyright (c) 2017, Pippin Williamson
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       2.6
 */
class RCP_Upgrades {

	private $version = '';
	private $upgraded = false;

	/**
	 * RCP_Upgrades constructor.
	 *
	 * @access public
	 * @return void
	 */
	public function __construct() {

		$this->version = preg_replace( '/[^0-9.].*/', '', get_option( 'rcp_version' ) );

		add_action( 'admin_init', array( $this, 'init' ), -9999 );

	}

	/**
	 * Trigger updates and maybe update the RCP version number
	 *
	 * @access public
	 * @return void
	 */
	public function init() {

		$this->v26_upgrades();
		$this->v27_upgrades();
		$this->v29_upgrades();

		// If upgrades have occurred or the DB version is differnt from the version constant
		if ( $this->upgraded || $this->version <> RCP_PLUGIN_VERSION ) {
			update_option( 'rcp_version_upgraded_from', $this->version );
			update_option( 'rcp_version', RCP_PLUGIN_VERSION );
		}

	}

	/**
	 * Process 2.6 upgrades
	 *
	 * @access private
	 * @return void
	 */
	private function v26_upgrades() {

		if( version_compare( $this->version, '2.6', '<' ) ) {
			@rcp_options_install();
		}
	}

	/**
	 * Process 2.7 upgrades
	 *
	 * @access private
	 * @return void
	 */
	private function v27_upgrades() {

		if( version_compare( $this->version, '2.7', '<' ) ) {

			global $wpdb, $rcp_discounts_db_name;

			$wpdb->query( "UPDATE $rcp_discounts_db_name SET code = LOWER(code)" );

			@rcp_options_install();

			$this->upgraded = true;
		}
	}

	/**
	 * Process 2.9 upgrades
	 *
	 * @access private
	 * @since 2.9
	 * @return void
	 */
	private function v29_upgrades() {

		if( version_compare( $this->version, '2.9', '<' ) ) {

			global $rcp_options;

			// Migrate expiring soon email to new reminders.
			$period  = rcp_get_renewal_reminder_period();
			$subject = isset( $rcp_options['renewal_subject'] ) ? $rcp_options['renewal_subject'] : '';
			$message = isset( $rcp_options['renew_notice_email'] ) ? $rcp_options['renew_notice_email'] : '';

			if ( 'none' != $period && ! empty( $subject ) && ! empty( $message ) ) {
				$rcp_options['send_expiration_reminders'] = 1;

				$reminders       = new RCP_Reminders();
				$notices         = $reminders->get_notices();
				$allowed_periods = $reminders->get_notice_periods();
				$period          = str_replace( ' ', '', $period );

				$new_notice = array(
					'subject'     => sanitize_text_field( $subject ),
					'message'     => wp_kses( $message, wp_kses_allowed_html( 'post' ) ),
					'send_period' => array_key_exists( $period, $allowed_periods ) ? $period : '+1month',
					'type'        => 'expiration'
				);

				$notices[1] = $new_notice; // [1] is the default expiration notice we'll replace

				update_option( 'rcp_reminder_notices', $notices );

				$this->upgraded = true;
			}

		}

	}


}
new RCP_Upgrades;