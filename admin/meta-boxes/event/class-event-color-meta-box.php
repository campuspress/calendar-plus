<?php

class Calendar_Plus_Event_Color_Metabox extends Calendar_Plus_Meta_Box {

	public function __construct() {
		$this->meta_box_slug = 'calendar-event-color';
		$this->meta_box_label = __( 'Event Color', 'calendar-plus' );
		$this->meta_box_context = 'side';
		$this->meta_box_priority = 'high';
		$this->post_type = 'calendar_event';

		parent::__construct();
	}

	public function render( $post ) {
		$event = calendarp_get_event( $post );
		$color = $event->color;
		$meta_box_slug = $this->meta_box_slug;
		include_once( calendarp_get_plugin_dir() . 'admin/views/event-color-meta-box.php' );
	}

	public function save_data( $event_id ) {

		$event = calendarp_get_event( $event_id );
		if ( ! $event ) {
			return;
		}

		$input = $_POST['_event_color'];

		if ( ! empty( $input ) ) {
			update_post_meta( $event->ID, '_color', $input );
		}
	}
}

