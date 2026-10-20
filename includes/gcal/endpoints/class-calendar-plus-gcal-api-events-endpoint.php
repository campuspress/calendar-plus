<?php
/**
 * Calendar Plus: includes/gcal/endpoints/class-calendar-plus-gcal-api-events-endpoint.php.
 *
 * @author WPMUDEV, Ignacio Cruz (igmoweb)
 * @package Calendar_Plus
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'Calendar_Plus_Google_Calendar_API_Events_Endpoint' ) ) {
	/** Calendar component: Calendar_Plus_Google_Calendar_API_Events_Endpoint. */
	class Calendar_Plus_Google_Calendar_API_Events_Endpoint extends Calendar_Plus_Google_Calendar_API_Endpoint {

		/**
		 * Get a Google Event.
		 *
		 * @param string $calendar_id Calendar id.
		 * @param string $event_id Event id.
		 * @return Google_Service_Calendar_Event|WP_Error
		 */
		public function get_event( $calendar_id, $event_id ) {
			$service = $this->api->get_service();
			if ( is_wp_error( $service ) ) {
				return $service;
			}
			try {
				$event = $service->events->get( $calendar_id, $event_id );
			} catch ( Exception $e ) {
				return new WP_Error( $e->getCode(), $e->getMessage() );
			}

			return $event;
		}

		/**
		 * Insert a new event.
		 *
		 * @param string $calendar_id Calendar id.
		 * @param mixed  $event Event.
		 * @return WP_Error|string
		 */
		public function insert_event( $calendar_id, $event ) {
			$service = $this->api->get_service();
			if ( is_wp_error( $service ) ) {
				return $service;
			}
			try {
				$created_event = $service->events->insert( $calendar_id, $event );
				return $created_event->getId();
			} catch ( Exception $e ) {
				return new WP_Error( $e->getCode(), $e->getMessage() );
			}
		}

		/**
		 * Update an event.
		 *
		 * @param string                        $calendar_id Calendar id.
		 * @param string                        $event_id Event id.
		 * @param Google_Service_Calendar_Event $event Event.
		 * @return WP_Error|string
		 */
		public function update_event( $calendar_id, $event_id, $event ) {
			$service = $this->api->get_service();
			if ( is_wp_error( $service ) ) {
				return $service;
			}
			try {
				$updated_event = $service->events->update( $calendar_id, $event_id, $event );
				return $updated_event->getId();
			} catch ( Exception $e ) {
				return new WP_Error( $e->getCode(), $e->getMessage() );
			}
		}

		/**
		 * Update an event.
		 *
		 * @param string $calendar_id Calendar id.
		 * @param int    $event_id Event id.
		 * @return WP_Error|string
		 */
		public function delete_event( $calendar_id, $event_id ) {
			$service = $this->api->get_service();
			if ( is_wp_error( $service ) ) {
				return $service;
			}
			try {
				$service->events->delete( $calendar_id, $event_id );
				return true;
			} catch ( Exception $e ) {
				return new WP_Error( $e->getCode(), $e->getMessage() );
			}
		}
	}
}
