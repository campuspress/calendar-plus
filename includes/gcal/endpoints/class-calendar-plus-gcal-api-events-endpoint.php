<?php
/**
 * @author: WPMUDEV, Ignacio Cruz (igmoweb)
 * @version:
 */


if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'Calendar_Plus_Google_Calendar_API_Events_Endpoint' ) ) {
	class Calendar_Plus_Google_Calendar_API_Events_Endpoint extends Calendar_Plus_Google_Calendar_API_Endpoint {

		/**
		 * Get a Google Event
		 *
		 * @param string $event_id
		 * @param string $calendar_id
		 *
		 * @return Google_Service_Calendar_Event|WP_Error
		 */
		public function get_event( $calendar_id, $event_id ) {
			try {
				$event = $this->api->get_service()->events->get( $calendar_id, $event_id );
			} catch ( Exception $e ) {
				return new WP_Error( $e->getCode(), $e->getMessage() );
			}

			return $event;
		}

		/**
		 * Insert a new event
		 *
		 * @param $event
		 * @param string $calendar_id
		 *
		 * @return WP_Error|string
		 */
		public function insert_event( $calendar_id, $event ) {
			try {
				$created_event = $this->api->get_service()->events->insert( $calendar_id, $event );
				return $created_event->getId();
			} catch ( Exception $e ) {
				return new WP_Error( $e->getCode(), $e->getMessage() );
			}
		}

		/**
		 * Update an event
		 *
		 * @param string $event_id
		 * @param Google_Service_Calendar_Event $event
		 * @param string $calendar_id
		 *
		 * @return WP_Error|string
		 */
		public function update_event( $calendar_id, $event_id, $event ) {
			try {
				$updated_event = $this->api->get_service()->events->update( $calendar_id, $event_id, $event );
				return $updated_event->getId();
			} catch ( Exception $e ) {
				return new WP_Error( $e->getCode(), $e->getMessage() );
			}
		}

		/**
		 * Update an event
		 *
		 * @param $event_id
		 * @param string $calendar_id
		 *
		 * @return WP_Error|string
		 */
		public function delete_event( $calendar_id, $event_id ) {
			try {
				$this->api->get_service()->events->delete( $calendar_id, $event_id );
				return true;
			} catch ( Exception $e ) {
				return new WP_Error( $e->getCode(), $e->getMessage() );
			}
		}
	}}
