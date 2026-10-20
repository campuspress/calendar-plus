<?php
/**
 * Calendar Plus: admin/meta-boxes/event/class-event-status-meta-box.php.
 *
 * @package Calendar_Plus
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


/** Calendar component: Calendar_Plus_Event_Status_Metabox. */
class Calendar_Plus_Event_Status_Metabox extends Calendar_Plus_Meta_Box {

	/**
	 * Initialize this calendar component.
	 */
	public function __construct() {
		$this->meta_box_slug     = 'calendar-event-status';
		$this->meta_box_label    = __( 'Event Status', 'calendar-plus' );
		$this->meta_box_context  = 'side';
		$this->meta_box_priority = 'high';
		$this->post_type         = 'calendar_event';

		parent::__construct();
	}

	/**
	 * Handle render.
	 *
	 * @param mixed $post Post.
	 *
	 * @return mixed Calendar operation result.
	 */
	public function render( $post ) {
		$event         = calendarp_get_event( $post );
		$meta_box_slug = $this->meta_box_slug;
		include_once calendarp_get_plugin_dir() . 'admin/views/event-status-meta-box.php';
	}

	/**
	 * Save data.
	 *
	 * @param int $event_id Event id.
	 *
	 * @return mixed Calendar operation result.
	 */
	public function save_data( $event_id ) {
		if ( ! $this->can_save( $event_id ) ) {
			return;
		}

		$event = calendarp_get_event( $event_id );
		if ( ! $event ) {
			return;
		}

		$input = calendarp_form_input( 'event_status', array() );

		if ( isset( $input['_status'] ) && is_string( $input['_status'] ) && array_key_exists( $input['_status'], calendarp_get_event_statuses() ) ) {
			update_post_meta( $event->ID, '_status', $input['_status'] );
		}
	}
}
