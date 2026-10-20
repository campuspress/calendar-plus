<?php
/**
 * Calendar Plus: includes/gcal/class-calendar-plus-gcal-logger.php.
 *
 * @author WPMUDEV, Ignacio Cruz (igmoweb)
 * @package Calendar_Plus
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


/** Calendar component: Calendar_Plus_Google_Calendar_Logger. */
class Calendar_Plus_Google_Calendar_Logger extends Psr\Log\AbstractLogger {

	/**
	 * {@inheritdoc}.
	 *
	 * @param mixed $level Level.
	 * @param mixed $message Message.
	 * @param array $context Context.
	 *
	 * @return mixed Calendar operation result.
	 */
	public function log( $level, $message, array $context = array() ) {
		// Logging integrations may opt in to this hook; the plugin writes no debug logs.
		do_action( 'calendarp_google_calendar_log', sanitize_key( $level ), sanitize_text_field( (string) $message ) );
	}
}
