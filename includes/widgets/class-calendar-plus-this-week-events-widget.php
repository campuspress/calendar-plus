<?php

/**
 * new WordPress Widget format
 * WordPress 2.8 and above
 * @see http://codex.wordpress.org/Widgets_API#Developing_Widgets
 */
class Calendar_Plus_This_Week_Events_Widget extends WP_Widget {

    /**
     * Constructor
     *
     * @return void
     **/
    function __construct() {
        $widget_ops = array( 'classname' => 'calendarp-this-week-events-widget', 'description' => __( 'A list of calendar events from this week.', 'calendar-plus' ) );
        parent::__construct( 'calendarp-this-week-events-widget', __( 'Calendar+ This Week Events', 'calendar-plus' ), $widget_ops );
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

        $from = current_time( 'timestamp' );
        $from = date( 'Y-m-d', $from ) . ' 00:00:00';
        $from = strtotime( $from );
        $to = strtotime( '+1 week', $from );
        $to = date( 'Y-m-d', $to ) . ' 23:59:59';
        $to = strtotime( $to );

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
                        <li><a href="<?php echo esc_url( get_permalink( $event->ID ) ); ?>" title="<?php echo esc_attr( sprintf( __( 'Permalink to %s', 'calendar-plus' ), get_the_title( $event->ID ) ) ); ?>"><?php echo get_the_title( $event->ID ); ?></a></li>
                    <?php endforeach; ?>

                </ul>
            <?php
        } else {
            ?>
                <strong><?php _e( 'There are no events for this week.', 'calendar-plus' ); ?></strong>
            <?php
        }

        ?>
        <br/>
        <?php
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
     * @return void Echoes it's output
     **/
    function form( $instance ) {
    	$defaults = $this->get_default_settings();
        $instance = wp_parse_args( (array) $instance, $defaults );
        ?>
			<p>
				<label for="<?php echo $this->get_field_id( 'title' ); ?>"> <?php _ex( 'Title', 'Search widget title', 'calendar-plus' ); ?>
					<input type="text" class="widefat" id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" value="<?php echo esc_attr( $instance['title'] ); ?>">
				</label>
			</p>
        <?php
    }
}

add_action( 'widgets_init', 'calendarp_register_this_week_events_widget' );
function calendarp_register_this_week_events_widget() {
	register_widget( 'Calendar_Plus_This_Week_Events_Widget' );
}
