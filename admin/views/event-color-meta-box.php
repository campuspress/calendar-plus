<input type="text" name="_event_color" id="event-color" value="<?php echo $color; ?>">

<?php do_action( 'calendarp_' . $meta_box_slug . '_meta_box', $event ); ?>

<?php wp_nonce_field( 'calendarp_' . $meta_box_slug . '_meta_box', 'calendarp_' . $meta_box_slug . '_nonce' ); ?>

<script>
	jQuery(document).ready(function($) {
		$(function() {
			$('#event-color').wpColorPicker();
		});
	});
</script>
