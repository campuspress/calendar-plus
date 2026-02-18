<?php
/**
 * Legacy Calendar Widget Template
 *
 * @var array $args Widget display arguments
 * @var array $instance Widget settings
 * @var array $calendar_args Arguments for the calendar display
 */

defined( 'ABSPATH' ) || exit;

$gif_url = includes_url( 'images/spinner.gif', is_ssl() ? 'https' : 'http' );
?>

<div id="calendar_wrap" class="calendar_wrap calendarp_calendar_wrap">
	<?php calendarp_get_calendar_widget( true, true, false, $calendar_args ); ?>
</div>

<style>
	.calendarp-backdrop {
		background-image: url( '<?php echo esc_url( $gif_url ); ?>' );
		background-color: white;
		background-repeat: no-repeat;
		background-position: center;
		position: absolute;
		top: 0;
		left: 0;
		right: 0;
		bottom: 0;
		opacity: 0.7;
		z-index: 15000;
		display: none;
	}
	.calendarp_calendar_wrap {
		position: relative;
	}
</style>
