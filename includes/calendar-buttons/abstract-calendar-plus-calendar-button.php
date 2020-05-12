<?php
/**
 * @author: WPMUDEV, Ignacio Cruz (igmoweb)
 * @version:
 */


if ( ! defined( 'ABSPATH' ) || class_exists( 'Calendar_Plus_Calendar_Button' ) ) {
	return;
}

abstract class Calendar_Plus_Calendar_Button {

	/**
	 * @var Calendar_Plus_Event
	 */
	protected $event;

	/**
	 * Calendar_Plus_Calendar_Button constructor.
	 *
	 * @param Calendar_Plus_Event $event
	 *
	 * @throws Exception
	 */
	public function __construct( Calendar_Plus_Event $event ) {
		$event = calendarp_get_event( $event );

		if ( ! $event ) {
			throw new Exception( __( 'The event does not exist', 'calendar-plus' ) );
		}

		$this->event = $event;
	}
}
