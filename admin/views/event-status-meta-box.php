<select name="event_status[_status]" id="event-status">
	<?php foreach ( calendarp_get_event_statuses() as $status_slug => $label ) : ?>
		<option value="<?php echo esc_attr( $status_slug ); ?>" <?php selected( $event->get_status(), $status_slug ); ?>><?php echo $label; ?></option>
	<?php endforeach; ?>
</select>

<?php do_action( 'calendarp_' . $meta_box_slug . '_meta_box', $event ); ?>

<?php wp_nonce_field( 'calendarp_' . $meta_box_slug . '_meta_box', 'calendarp_' . $meta_box_slug . '_nonce' ); ?>
