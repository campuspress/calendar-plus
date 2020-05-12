<?php

class Calendar_Plus_Event_Status_Metabox extends Calendar_Plus_Meta_Box {

	public function __construct() {
		$this->meta_box_slug = 'calendar-event-status';
		$this->meta_box_label = __( 'Event Status', 'calendar-plus' );
		$this->meta_box_context = 'side';
		$this->meta_box_priority = 'high';
		$this->post_type = 'calendar_event';

		parent::__construct();
	}

	public function render( $post ) {
		$event = calendarp_get_event( $post );
		$meta_box_slug = $this->meta_box_slug;
		include_once( calendarp_get_plugin_dir() . 'admin/views/event-status-meta-box.php' );
	}

	public function save_data( $event_id ) {

		$event = calendarp_get_event( $event_id );
		if ( ! $event ) {
			return;
		}

		$input = $_POST['event_status'];

		if ( ! empty( $input['_status'] ) && array_key_exists( $input['_status'], calendarp_get_event_statuses() ) ) {
			update_post_meta( $event->ID, '_status', $input['_status'] );
		}
	}
}

