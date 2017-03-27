<?php

/**
 * Debug Logging class
 *
 * @package    restrict-content-pro
 * @subpackage Classes/Logging
 * @copyright  Copyright (c) 2017, Restrict Content Pro
 * @license    http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since      2.9
 */
class RCP_Logging {

	/**
	 * Whether or not the file is writable
	 *
	 * @var bool
	 * @access public
	 * @since  2.9
	 */
	public $is_writable = true;

	/**
	 * Name of the file
	 *
	 * @var string
	 * @access public
	 * @since  2.9
	 */
	public $filename = '';

	/**
	 * Full path to the file
	 *
	 * @var string
	 * @access public
	 * @since  2.9
	 */
	public $file = '';

	/**
	 * Get things started
	 *
	 * @access public
	 * @since  2.9
	 * @return void
	 */
	public function __construct() {

		$this->init();

	}

	/**
	 * Get things started
	 *
	 * @access public
	 * @since  2.9
	 * @return void
	 */
	public function init() {

		$upload_dir     = wp_upload_dir();
		$this->filename = 'rcp-debug.log';
		$this->file     = trailingslashit( $upload_dir['basedir'] ) . $this->filename;

		if ( ! is_writeable( $upload_dir['basedir'] ) ) {
			$this->is_writable = false;
		}

	}

	/**
	 * Retrieve the log data
	 *
	 * @access public
	 * @since  2.9
	 * @return string
	 */
	public function get_log() {
		return $this->get_file();
	}

	/**
	 * Log message to file
	 *
	 * @param string $message Message to log.
	 *
	 * @access public
	 * @since  2.9
	 * @return void
	 */
	public function log( $message = '' ) {
		$message = date( 'Y-n-d H:i:s' ) . ' - ' . $message . "\r\n";
		$this->write_to_log( $message );
	}

	/**
	 * Retrieve the file
	 *
	 * @access protected
	 * @since  2.9
	 * @return string
	 */
	protected function get_file() {

		$file = '';

		if ( @file_exists( $this->file ) ) {

			if ( ! is_writeable( $this->file ) ) {
				$this->is_writable = false;
			}

			$file = @file_get_contents( $this->file );

		} else {

			@file_put_contents( $this->file, '' );
			@chmod( $this->file, 0664 );

		}

		return $file;
	}

	/**
	 * Write the log message
	 *
	 * @param string $message Message to log.
	 *
	 * @access protected
	 * @since  2.9
	 * @return void
	 */
	protected function write_to_log( $message = '' ) {

		$file = $this->get_file();
		$file .= $message;
		@file_put_contents( $this->file, $file );

	}

	/**
	 * Clear the log
	 *
	 * @access public
	 * @since  2.9
	 * @return void
	 */
	public function clear_log() {
		@unlink( $this->file );
	}

}

$GLOBALS['rcp_logs'] = new RCP_Logging();