<?php
/**
 * Calendar Plus: includes/gcal/endpoints/class-calendar-plus-gcal-api-calendars-endpoint.php.
 *
 * @author WPMUDEV, Ignacio Cruz (igmoweb)
 * @package Calendar_Plus
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'Calendar_Plus_Google_Calendar_API_Calendars_Endpoint' ) ) {
	/** Calendar component: Calendar_Plus_Google_Calendar_API_Calendars_Endpoint. */
	class Calendar_Plus_Google_Calendar_API_Calendars_Endpoint extends Calendar_Plus_Google_Calendar_API_Endpoint {

		/**
		 * Return an array of calendars in user's Google account.
		 *
		 * @return array|WP_Error
		 */
		public function get_calendars_list() {
			$service = $this->api->get_service();
			if ( is_wp_error( $service ) ) {
				return $service;
			}
			try {
				// phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase -- Google SDK resource property is named calendarList.
				$calendars = $service->calendarList->listCalendarList();
			} catch ( Exception $e ) {
				$errors = $e->getErrors();
				return new WP_Error( $errors[0]['reason'], $errors[0]['message'] );
			}

			return $calendars->getItems();
		}

		/**
		 * Get the Google Calendar Data.
		 *
		 * @param string $calendar_id Calendar id.
		 * @return mixed|WP_Error
		 */
		public function get_calendar_details( $calendar_id ) {
			$service = $this->api->get_service();
			if ( is_wp_error( $service ) ) {
				return $service;
			}
			try {
				$details = $service->calendars->get( $calendar_id );
			} catch ( Exception $e ) {
				return new WP_Error( $e->getCode(), $e->getMessage() );
			}

			return $details;
		}
	}
}
