<?php

/**
 * @author: WPMUDEV, Ignacio Cruz (igmoweb)
 * @version:
 */

if ( interface_exists( 'Calendar_Plus_External_Calendar_Button' ) ) {
	return;
}

interface Calendar_Plus_External_Calendar_Button {

	/**
	 * @param array $args
	 *
	 * @return string
	 */
	public function get_button_link( $args = array() );

}
