<?php

/**
 * new WordPress Widget format
 * WordPress 2.8 and above
 * @see http://codex.wordpress.org/Widgets_API#Developing_Widgets
 */
class Calendar_Plus_Search_Widget extends WP_Widget {

	/**
	 * Constructor
	 *
	 * @return void
	 **/
	function __construct() {
		$widget_ops = array(
			'classname' => 'calendarp-search-widget',
			'description' => __( 'A form for searching calendar events.', 'calendar-plus' )
		);

		parent::__construct( 'calendarp-search-widget', __( 'Calendar+ Search', 'calendar-plus' ), $widget_ops );
	}


	/**
	 * Outputs the HTML for this widget.
	 *
	 * @param array  An array of standard parameters for widgets in this theme
	 * @param array  An array of settings for this widget instance
	 *
	 * @return void Echoes it's output
	 **/
	function widget( $args, $instance ) {
		$instance = wp_parse_args( $instance, $this->get_default_settings() );

		echo $args['before_widget'];
		echo $args['before_title'];
		echo $instance['title'];
		echo $args['after_title'];

		$location_args = array(
			'name'        => 'location',
			'selected'    => isset( $_GET['location'] ) ? absint( $_GET['location'] ) : '',
			'empty_label' => __( '-- Location: All --', 'calendar-plus' ),
			'class'       => 'postform',
		);

		$category_selected = '';
		$term = get_queried_object();
		if ( ! empty( $term->term_id ) ) {
			$term = get_term( $term->term_id, 'calendar_event_category' );
			if ( ! empty( $term->term_id ) ) {
				$category_selected = $term->term_id;
			}
		}

		$category_args = array(
			'show_option_all' => __( '-- Category: All --', 'calendar-plus' ),
			'selected'        => $category_selected,
			'taxonomy'        => 'calendar_event_category',
		);

		$from = isset( $_GET['from'] ) ? $_GET['from'] : '';
		$_from = explode( '-', $from );
		if ( count( $_from ) != 3 ) {
			$from = '';
		} elseif ( ! wp_checkdate( absint( $_from[1] ), absint( $_from[2] ), absint( $_from[0] ), $from ) ) {
			$from = '';
		}

		$to = isset( $_GET['to'] ) ? $_GET['to'] : '';
		$_to = explode( '-', $to );
		if ( count( $_to ) != 3 ) {
			$to = '';
		} elseif ( ! wp_checkdate( absint( $_to[1] ), absint( $_to[2] ), absint( $_to[0] ), $to ) ) {
			$to = '';
		}

		$past_events = isset( $_GET['show-past-events'] );

		?>
		<form role="search" method="get" class="search-form" action="<?php echo esc_url( get_post_type_archive_link( 'calendar_event' ) ); ?>">
			<p>
				<input type="search" class="search-field" name="s"
				       placeholder="<?php echo esc_attr_x( 'Search Events &hellip;', 'placeholder', 'calendar-plus' ); ?>"
				       value="<?php echo esc_attr( get_search_query() ); ?>"
				       title="<?php echo esc_attr_x( 'Search for events:', 'label', 'calendar-plus' ); ?>">
			</p>

			<p>
				<label for="calendarp-search-from"><?php _e( 'From', 'calendar-plus' ); ?>
					<br />
					<input type="text" class="calendarp-datepicker" id="calendarp-search-from" name="from" value="<?php echo esc_attr( $from ); ?>">
				</label>
			</p>
			<p>
				<label for="calendarp-search-to"><?php _e( 'To', 'calendar-plus' ); ?>
					<br />
					<input type="text" class="calendarp-datepicker" id="calendarp-search-to" name="to" value="<?php echo esc_attr( $to ); ?>">
				</label>
			</p>

			<p><?php calendarp_locations_dropdown( $location_args ); ?></p>

			<p><?php wp_dropdown_categories( $category_args ); ?></p>

			<input type="hidden" name="post_type" value="calendar_event" />
			<input type="hidden" name="calendarp_searchw" value="true" />

			<p>
				<input type="submit" class="search-submit button calendarp-button"
				       value="<?php echo esc_attr_x( 'Search', 'submit button' ); ?>">
			</p>
		</form>
		<?php

		echo $args['after_widget'];
	}

	/**
	 * Deals with the settings when they are saved by the admin. Here is
	 * where any validation should be dealt with.
	 *
	 * @param array  An array of new settings as submitted by the admin
	 * @param array  An array of the previous settings
	 *
	 * @return array The validated and (if necessary) amended settings
	 **/
	function update( $new_instance, $old_instance ) {

		// update logic goes here
		$updated_instance = $old_instance;

		$updated_instance['title'] = sanitize_text_field( $new_instance['title'] );

		return $updated_instance;
	}

	function get_default_settings() {
		return array(
			'title' => '',
		);
	}

	/**
	 * Displays the form for this widget on the Widgets page of the WP Admin area.
	 *
	 * @param array  An array of the current settings for this widget
	 *
	 * @return void Echoes it's output
	 **/
	function form( $instance ) {
		$defaults = $this->get_default_settings();
		$instance = wp_parse_args( (array) $instance, $defaults );
		?>
		<p>
			<label for="<?php echo $this->get_field_id( 'title' ); ?>"> <?php _ex( 'Title', 'Search widget title', 'calendar-plus' ); ?>
				<input type="text" class="widefat" id="<?php echo $this->get_field_id( 'title' ); ?>"
				       name="<?php echo $this->get_field_name( 'title' ); ?>" value="<?php echo esc_attr( $instance['title'] ); ?>">
			</label>
		</p>
		<?php
	}
}

add_action( 'widgets_init', 'calendarp_register_search_widget' );
function calendarp_register_search_widget() {
	register_widget( 'Calendar_Plus_Search_Widget' );
}
