<?php
/**
 * Calendar Plus: includes/calendar-buttons/abstract-calendar-plus-calendar-button.php.
 *
 * @author WPMUDEV, Ignacio Cruz (igmoweb)
 * @package Calendar_Plus
 */

if ( ! defined( 'ABSPATH' ) || class_exists( 'Calendar_Plus_Calendar_Button' ) ) {
	return;
}

/** Calendar component: Calendar_Plus_Calendar_Button. */
abstract class Calendar_Plus_Calendar_Button {

	/**
	 * Calendar component state.
	 *
	 * @var Calendar_Plus_Event
	 */
	protected $event;

	/**
	 * Calendar_Plus_Calendar_Button constructor.
	 *
	 * @param Calendar_Plus_Event $event Event.
	 * @throws Exception When the calendar data is invalid.
	 */
	public function __construct( Calendar_Plus_Event $event ) {
		$event = calendarp_get_event( $event );

		if ( ! $event ) {
			throw new Exception( esc_html( __( 'The event does not exist', 'calendar-plus' ) ) );
		}

		$this->event = $event;
	}
}
