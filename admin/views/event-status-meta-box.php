<?php
/**
 * Calendar Plus: admin/views/event-status-meta-box.php.
 *
 * @package Calendar_Plus
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<select name="event_status[_status]" id="event-status">
	<?php


	foreach ( calendarp_get_event_statuses() as $calendarp_status_slug => $calendarp_label ) :
		?>
		<option value="<?php echo esc_attr( $calendarp_status_slug ); ?>" <?php selected( $event->get_status(), $calendarp_status_slug ); ?>><?php echo esc_html( $calendarp_label ); ?></option>
		<?php endforeach; ?>
</select>

<?php do_action( 'calendarp_' . $meta_box_slug . '_meta_box', $event ); ?>

<?php wp_nonce_field( 'calendarp_' . $meta_box_slug . '_meta_box', 'calendarp_' . $meta_box_slug . '_nonce' ); ?>
