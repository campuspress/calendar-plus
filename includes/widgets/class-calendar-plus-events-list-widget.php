<?php


/**
 * Widget for displaying a customisable list of calendar events
 */
class Calendar_Plus_Events_List_Widget extends WP_Widget {

	/**
	 * Class constructor
	 */
	function __construct() {
		parent::__construct(
			'calendarp-events-list-widget',
			__( 'Events List', 'calendar-plus' ),
			array(
				'classname'   => 'calendarp-events-list-widget',
				'description' => __( 'A customizable list of events', 'calendar-plus' ),
			)
		);
	}

	/**
	 * Register the widget class with WordPress
	 */
	public static function register_widget() {
		register_widget( __CLASS__ );
	}

	/**
	 * Render the widget content
	 *
	 * @param array $args Standard parameters for widgets in theme
	 * @param array $instance Settings specific to this widget instance
	 */
	public function widget( $args, $instance ) {

		if ( ! function_exists( 'calendarp_events_permalink' ) ) {
			include_once calendarp_get_plugin_dir() . 'public/helpers-templates.php';
		}

		$instance = wp_parse_args( $instance, $this->get_default_settings() );

		echo $args['before_widget'];
		echo $args['before_title'], $instance['title'], $args['after_title'];

		$query_args = array(
			'category'        => $instance['categories'],
			'events_per_page' => $instance['count'],
		);

		$events_by_date = calendarp_get_events_in_date_range( current_time( 'timestamp' ), false, $query_args );

		$template_args = array(
			'args'            => $args,
			'instance'        => $instance,
			'events_by_date'  => $events_by_date,
			'display_name'    => in_array( 'name', $instance['event_fields'] ),
			'display_desc'    => in_array( 'description', $instance['event_fields'] ),
			'display_date'    => in_array( 'date', $instance['event_fields'] ),
			'display_time'    => in_array( 'time', $instance['event_fields'] ),
			'display_cats'    => in_array( 'category', $instance['event_fields'] ),
		);

		calendarp_get_template( 'widgets/events-list.php', $template_args );

		echo $args['after_widget'];
	}

	/**
	 * Validate widget settings when they are saved
	 *
	 * @param array $input
	 * @param array $previous
	 *
	 * @return array Validated settings
	 */
	public function update( $input, $previous ) {
		$output = array();
		$defaults = $this->get_default_settings();

		foreach ( $input as $setting => $input_value ) {
			$output_value = null;

			switch ( $setting ) {

				case 'title':
					$output_value = sanitize_text_field( $input_value );
					break;

				case 'count':
					$output_value = absint( $input_value );
					break;

				case 'categories':
					$all_categories = get_terms( array( 'taxonomy' => 'calendar_event_category', 'fields' => 'ids' ) );
					$output_value = array_intersect( $all_categories, array_map( 'intval', $input_value ) );
					break;

				case 'event_fields':
					$all_fields = $defaults['event_fields'];
					$output_value = array_intersect( $all_fields, $input_value );
					break;
			}

			if ( ! is_null( $output_value ) ) {
				$output[ $setting ] = $output_value;
			}
		}

		return $output;
	}

	/**
	 * Retrieve a list of default widget setting values
	 * @return array
	 */
	public function get_default_settings() {
		return array(
			'title'        => __( 'Events', 'calendar-plus' ),
			'count'        => 3,
			'categories'   => array(),
			'event_fields' => array( 'name', 'description', 'time', 'date', 'category' ),
		);
	}

	/**
	 * Render the widget settings form
	 *
	 * @param array $instance
	 */
	public function form( $instance ) {
		$defaults = $this->get_default_settings();
		$instance = wp_parse_args( (array) $instance, $defaults );

		$settings = array(
			'title'        => _x( 'Title:', 'Widget title', 'calendar-plus' ),
			'categories'   => __( 'Only show events from these categories:', 'calendar-plus' ),
			'event_fields' => __( 'Display these fields for each event:', 'calendar-plus' ),
			'count'        => __( 'Number of events to display:', 'calendar-plus' ),
		);

		foreach ( $settings as $setting => $label ) {

			if ( 'title' === $setting || 'count' === $setting ) {
				echo '<p>';
				printf( '<label for="%s">%s</label>', $this->get_field_id( $setting ), esc_html( $label ) );
				echo "\n";

				if ( 'count' === $setting ) {
					echo '<input type="number" class="tiny-text" step="1" min="0" size="3"';

				} else {
					echo '<input type="text" class="widefat"';
				}

				printf( ' id="%s" name="%s" value="%s">',
					$this->get_field_id( $setting ),
					$this->get_field_name( $setting ),
					esc_attr( $instance[ $setting ] )
				);

				echo '</p>';

			} elseif ( 'categories' === $setting || 'event_fields' === $setting ) {
				printf( '<fieldset class="calp-widget-checkbox-list" id="%s">', $this->get_field_id( $setting ) );
				echo '<legend>', esc_html( $label ), '</legend>';

				if ( 'categories' === $setting ) {
					$options = get_terms( array( 'taxonomy' => 'calendar_event_category', 'fields' => 'id=>name' ) );

				} else {
					$options = array(
						'name'        => __( 'Event Name', 'calendar-plus' ),
						'description' => __( 'Event Description', 'calendar-plus' ),
						'time'        => __( 'Event Time', 'calendar-plus' ),
						'date'        => __( 'Event Date', 'calendar-plus' ),
						'category'    => __( 'Event Categories', 'calendar-plus' ),
					);
				}

				foreach ( $options as $option => $option_label ) {
					printf(
						'<label><input type="checkbox" name="%s[]" value="%s"%s>%s</label><br>',
						$this->get_field_name( $setting ),
						$option,
						checked( in_array( $option, $instance[ $setting ] ), true, false ),
						esc_html( $option_label )
					);
				}

				echo '</fieldset>';
			}
		}
	}
}

add_action( 'widgets_init', array( 'Calendar_Plus_Events_List_Widget', 'register_widget' ) );
