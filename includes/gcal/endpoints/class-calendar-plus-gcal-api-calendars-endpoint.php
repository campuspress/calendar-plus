<?php
/**
 * @author: WPMUDEV, Ignacio Cruz (igmoweb)
 * @version:
 */


if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'Calendar_Plus_Google_Calendar_API_Calendars_Endpoint' ) ) {
	class Calendar_Plus_Google_Calendar_API_Calendars_Endpoint extends Calendar_Plus_Google_Calendar_API_Endpoint {

		/**
		 * Return an array of calendars in user's Google account
		 *
		 * @return array|WP_Error
		 */
		public function get_calendars_list() {
			try {
				$calendars = $this->api->get_service()->calendarList->listCalendarList();
			} catch ( Exception $e ) {
				$errors = $e->getErrors();
				return new WP_Error( $errors[0]['reason'], $errors[0]['message'] );
			}

			return $calendars->getItems();
		}

		/**
		 * Get the Google Calendar Data
		 *
		 * @param string $calendar_id
		 *
		 * @return mixed|WP_Error
		 */
		public function get_calendar_details( $calendar_id ) {
			try {
				$details = $this->api->get_service()->calendars->get( $calendar_id );
			} catch ( Exception $e ) {
				return new WP_Error( $e->getCode(), $e->getMessage() );
			}

			return $details;
		}
	}
}
