<?php
/**
 * Calendar Plus: includes/calendar-buttons/interface-calendar-plus-external-calendar-button.php.
 *
 * @package Calendar_Plus
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


/**
 * Calendar Plus component context.
 *
 * @author WPMUDEV, Ignacio Cruz (igmoweb)
 */

if ( interface_exists( 'Calendar_Plus_External_Calendar_Button' ) ) {
	return;
}

interface Calendar_Plus_External_Calendar_Button {

	/**
	 * Retrieve button link.
	 *
	 * @param array $args Args.
	 * @return string
	 */
	public function get_button_link( $args = array() );
}
