<?php
/**
 * @author: WPMUDEV, Ignacio Cruz (igmoweb)
 * @version:
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


class Calendar_Plus_Google_Calendar_Logger extends Psr\Log\AbstractLogger {

	/**
	 * {@inheritdoc}
	 */
	public function log( $level, $message, array $context = array() ) {
		if ( defined( 'WP_DEBUG_LOG' ) && WP_DEBUG_LOG ) {
			error_log( '[Calendar+] ' . $message );
		}
	}

}
