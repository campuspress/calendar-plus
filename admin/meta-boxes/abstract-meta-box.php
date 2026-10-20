<?php
/**
 * Calendar Plus: admin/meta-boxes/abstract-meta-box.php.
 *
 * @package Calendar_Plus
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


/** Calendar component: Calendar_Plus_Meta_Box. */
abstract class Calendar_Plus_Meta_Box {

	/**
	 * Calendar component state.
	 *
	 * @var mixed
	 */
	protected $meta_box_slug = '';
	/**
	 * Calendar component state.
	 *
	 * @var mixed
	 */
	protected $meta_box_label = '';
	/**
	 * Calendar component state.
	 *
	 * @var mixed
	 */
	protected $meta_box_context = '';
	/**
	 * Calendar component state.
	 *
	 * @var mixed
	 */
	protected $meta_box_priority = '';
	/**
	 * Calendar component state.
	 *
	 * @var mixed
	 */
	protected $meta_box_args = '';
	/**
	 * Calendar component state.
	 *
	 * @var mixed
	 */
	protected $post_type = '';

	/**
	 * Initialize this calendar component.
	 */
	public function __construct() {
		add_action( 'add_meta_boxes', array( $this, 'register' ) );
		add_action( 'save_post', array( $this, 'save' ) );
	}

	/**
	 * Remove save post hook.
	 *
	 * @return mixed Calendar operation result.
	 */
	public function remove_save_post_hook() {
	}

	/**
	 * Handle register.
	 *
	 * @return mixed Calendar operation result.
	 */
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

	/**
	 * Handle render.
	 *
	 * @param mixed $post Post.
	 *
	 * @return mixed Calendar operation result.
	 */
	abstract public function render( $post );

	/**
	 * Save data.
	 *
	 * @param mixed $event Event.
	 *
	 * @return mixed Calendar operation result.
	 */
	abstract public function save_data( $event );

	/**
	 * Handle can save.
	 *
	 * @param int $post_id Post id.
	 *
	 * @return mixed Calendar operation result.
	 */
	protected function can_save( $post_id ) {
		$nonce_key = 'calendarp_' . $this->meta_box_slug . '_nonce';
		if ( ! isset( $_POST[ $nonce_key ] ) || ! is_string( $_POST[ $nonce_key ] ) ) {
			return false;
		}
		$nonce = sanitize_text_field( wp_unslash( $_POST[ $nonce_key ] ) );
		return wp_verify_nonce( $nonce, 'calendarp_' . $this->meta_box_slug . '_meta_box' )
			&& ! ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE )
			&& ! wp_is_post_revision( $post_id )
			&& get_post_type( $post_id ) === $this->post_type
			&& current_user_can( 'edit_post', $post_id );
	}

	/**
	 * Handle save.
	 *
	 * @param int $post_id Post id.
	 *
	 * @return mixed Calendar operation result.
	 */
	public function save( $post_id ) {
		if ( ! $this->can_save( $post_id ) ) {
			return;
		}
		$this->save_data( $post_id );
		$event = calendarp_get_event( $post_id );
		do_action( 'calendarp_save_' . $this->meta_box_slug . '_meta_box', $event );
	}
}
