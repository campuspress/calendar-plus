<?php

abstract class Calendar_Plus_Meta_Box {

	protected $meta_box_slug = '';
	protected $meta_box_label = '';
	protected $meta_box_context = '';
	protected $meta_box_priority = '';
	protected $meta_box_args = '';
	protected $post_type = '';

	public function __construct() {
		add_action( 'add_meta_boxes', array( $this, 'register' ) );
		add_action( 'save_post', array( $this, 'save' ) );
	}

	public function remove_save_post_hook() {

	}

	public function register() {
		add_meta_box(
			$this->meta_box_slug,
			$this->meta_box_label,
			array( $this, 'render' ),
			$this->post_type,
			$this->meta_box_context,
			$this->meta_box_priority,
			$this->meta_box_args
		);
	}

	public abstract function render( $post );

	public abstract function save_data( $event );

	public function save( $post_id ) {
		// Check if our nonce is set.
		if ( ! isset( $_POST[ 'calendarp_' . $this->meta_box_slug . '_nonce' ] ) ) {
			return;
		}

		// Verify that the nonce is valid.
		if ( ! wp_verify_nonce( $_POST[ 'calendarp_' . $this->meta_box_slug . '_nonce' ], 'calendarp_' . $this->meta_box_slug . '_meta_box' ) ) {
			return;
		}

		// If this is an autosave, our form has not been submitted, so we don't want to do anything.
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		// Check the user's permissions.
		if ( isset( $_POST['post_type'] ) && 'calendar_event' == $_POST['post_type'] ) {
			if ( ! current_user_can( 'edit_post', $post_id ) ) {
				return;
			}
		}

		$this->save_data( $post_id );

		$event = calendarp_get_event( $post_id );

		do_action( 'calendarp_save_' . $this->meta_box_slug . '_meta_box', $event );
	}
}
