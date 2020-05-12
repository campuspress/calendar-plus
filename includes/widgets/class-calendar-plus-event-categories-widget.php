<?php

/**
 * new WordPress Widget format
 * WordPress 2.8 and above
 * @see http://codex.wordpress.org/Widgets_API#Developing_Widgets
 */
class Calendar_Plus_Event_Categories_Widget extends WP_Widget {

    /**
     * Constructor
     *
     * @return void
     **/
    function __construct() {
        $widget_ops = array( 'classname' => 'calendarp-event-categories-widget', 'description' => __( 'A list of calendar event categories.', 'calendar-plus' ) );
        parent::__construct( 'calendarp-event-categories-widget', __( 'Calendar+ Event Categories', 'calendar-plus' ), $widget_ops );
    }

    /**
     * Outputs the HTML for this widget.
     *
     * @param array  An array of standard parameters for widgets in this theme
     * @param array  An array of settings for this widget instance
     * @return void Echoes it's output
     **/
    function widget( $args, $instance ) {
        if ( ! function_exists( 'calendarp_events_permalink' ) ) {
            include_once( calendarp_get_plugin_dir() . 'public/helpers-templates.php' );
        }

    	$instance = wp_parse_args( $instance, $this->get_default_settings() );

        extract( $args, EXTR_SKIP );
        echo $before_widget;
        echo $before_title;
        echo $instance['title'];
        echo $after_title;

        $category_args = array(
            'hierarchical' => $instance['show_hierarchy'],
            'taxonomy'     => 'calendar_event_category',
            'title_li'     => false,
        );

        echo '<ul>';
        wp_list_categories( $category_args );
        echo '</ul>';

        echo '<br/>';
        calendarp_events_permalink();

    	echo $after_widget;
    }

    /**
     * Deals with the settings when they are saved by the admin. Here is
     * where any validation should be dealt with.
     *
     * @param array  An array of new settings as submitted by the admin
     * @param array  An array of the previous settings
     * @return array The validated and (if necessary) amended settings
     **/
    function update( $new_instance, $old_instance ) {

        // update logic goes here
        $updated_instance = $old_instance;

        $updated_instance['title'] = sanitize_text_field( $new_instance['title'] );
        $updated_instance['show_hierarchy'] = ! empty( $new_instance['show_hierarchy'] ) ? true : false;

        return $updated_instance;
    }

    function get_default_settings() {
    	return array(
    		'title'          => '',
            'show_hierarchy' => false,
    	);
    }

    /**
     * Displays the form for this widget on the Widgets page of the WP Admin area.
     *
     * @param array  An array of the current settings for this widget
     * @return void Echoes it's output
     **/
    function form( $instance ) {
    	$defaults = $this->get_default_settings();
        $instance = wp_parse_args( (array) $instance, $defaults );
        ?>
			<p>
				<label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php _ex( 'Title', 'Event categories widget', 'calendar-plus' ); ?>
					<input type="text" class="widefat" id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" value="<?php echo esc_attr( $instance['title'] ); ?>">
				</label>
			</p>
            <p>
                <label for="<?php echo $this->get_field_id( 'show_hierarchy' ); ?>">
                    <input type="checkbox" id="<?php echo $this->get_field_id( 'show_hierarchy' ); ?>" name="<?php echo $this->get_field_name( 'show_hierarchy' ); ?>" value="yes" <?php checked( $instance['show_hierarchy'] ); ?> />
                    <?php _ex( 'Show hierarchy', 'event categories widget', 'calendar-plus' ); ?>
                </label>
            </p>
        <?php
    }
}

add_action( 'widgets_init', 'calendarp_register_event_categories_widget' );
function calendarp_register_event_categories_widget() {
	register_widget( 'Calendar_Plus_Event_Categories_Widget' );
}
