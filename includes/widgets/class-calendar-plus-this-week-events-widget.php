<?php
/**
 * Calendar Plus: includes/widgets/class-calendar-plus-this-week-events-widget.php.
 *
 * @package Calendar_Plus
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


/**
 * New WordPress Widget format
 * WordPress 2.8 and above
 *
 * @see http://codex.wordpress.org/Widgets_API#Developing_Widgets
 */
class Calendar_Plus_This_Week_Events_Widget extends WP_Widget {

	/**
	 * Constructor.
	 *
	 * @return void
	 */
	public function __construct() {
		$widget_ops = array(
			'classname'   => 'calendarp-this-week-events-widget',
			'description' => __( 'A list of calendar events from this week.', 'calendar-plus' ),
		);
		parent::__construct( 'calendarp-this-week-events-widget', __( 'Calendar+ This Week Events', 'calendar-plus' ), $widget_ops );
	}

	/**
	 * Outputs the HTML for this widget.
	 *
	 * @param array $args Args.
	 * @param array $instance Instance.
	 * Outputs the widget markup.
	 */
	public function widget( $args, $instance ) {
		if ( ! function_exists( 'calendarp_events_permalink' ) ) {
			include_once calendarp_get_plugin_dir() . 'public/helpers-templates.php';
		}

		$instance = wp_parse_args( $instance, $this->get_default_settings() );

		$before_widget = $args['before_widget'] ?? '';
		$after_widget  = $args['after_widget'] ?? '';
		$before_title  = $args['before_title'] ?? '';
		$after_title   = $args['after_title'] ?? '';
		echo wp_kses_post( $before_widget );
		echo wp_kses_post( $before_title );
		echo esc_html( $instance['title'] );
		echo wp_kses_post( $after_title );

		$from = calendarp_wall_time();
		$from = gmdate( 'Y-m-d', $from ) . ' 00:00:00';
		$from = strtotime( $from );
		$to   = strtotime( '+1 week', $from );
		$to   = gmdate( 'Y-m-d', $to ) . ' 23:59:59';
		$to   = strtotime( $to );

		$grouped_events = calendarp_get_events_in_date_range( $from, $to );

		$events = array();
		foreach ( $grouped_events as $date => $_events ) {
			foreach ( $_events as $event ) {
				$events[ $event->ID ] = $event;
			}
		}

		if ( ! empty( $events ) ) {
			?>
				<ul class="this-week-events-list">
					<?php foreach ( $events as $event ) : ?>
						<li><a href="<?php echo esc_url( get_permalink( $event->ID ) ); ?>" title="
						<?php
						echo esc_attr(
							sprintf( /* translators: %s: event title or URL. */
								__( 'Permalink to %s', 'calendar-plus' ),
								get_the_title( $event->ID )
							)
						);
						?>
	"><?php echo esc_html( get_the_title( $event->ID ) ); ?></a></li>
					<?php endforeach; ?>

				</ul>
			<?php
		} else {
			?>
				<strong><?php esc_html_e( 'There are no events for this week.', 'calendar-plus' ); ?></strong>
			<?php
		}

		?>
		<br/>
		<?php
		calendarp_events_permalink();

		echo wp_kses_post( $after_widget );
	}

	/**
	 * Deals with the settings when they are saved by the admin. Here is.
	 * where any validation should be dealt with.
	 *
	 * @param mixed $new_instance New instance.
	 * @param mixed $old_instance Old instance.
	 * @return array The validated and (if necessary) amended settings
	 */
	public function update( $new_instance, $old_instance ) {

		// update logic goes here.
		$updated_instance = $old_instance;

		$updated_instance['title'] = sanitize_text_field( $new_instance['title'] );

		return $updated_instance;
	}

	/**
	 * Retrieve default settings.
	 *
	 * @return mixed Calendar operation result.
	 */
	public function get_default_settings() {
		return array(
			'title' => '',
		);
	}

	/**
	 * Displays the form for this widget on the Widgets page of the WP Admin area.
	 *
	 * @param array $instance Instance.
	 * Outputs the widget markup.
	 */
	public function form( $instance ) {
		$defaults = $this->get_default_settings();
		$instance = wp_parse_args( (array) $instance, $defaults );
		?>
			<p>
				<label for="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>"> <?php echo esc_html_x( 'Title', 'Search widget title', 'calendar-plus' ); ?>
					<input type="text" class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'title' ) ); ?>" value="<?php echo esc_attr( $instance['title'] ); ?>">
				</label>
			</p>
		<?php
	}
}

add_action( 'widgets_init', 'calendarp_register_this_week_events_widget' );
/**
 * Handle calendarp register this week events widget.
 *
 * @return mixed Calendar operation result.
 */
function calendarp_register_this_week_events_widget() {
	register_widget( 'Calendar_Plus_This_Week_Events_Widget' );
}
