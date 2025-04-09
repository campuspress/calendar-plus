<?php
/**
 * The template used in modern theme integration method.
 * Used to replace post_content for single events and .
 *
 * @package CalendarPlus
 * @subpackage Templates
 */
?>

<div class="cal-plus-event">
    <div class="cal-plus-event__post-content">
        <?php if ( ! is_single() ) : ?>
            <h2 class="cal-plus-event__title">
                <a href="<?php echo esc_url( get_permalink() ); ?>">
                    <?php the_title(); ?>
                </a>
            </h2>
        <?php endif; ?>

        <?php if ( calendarp_back_to_calendar_link() ) : ?>
            <p class="cal-plus-event__back-to-calendar">
                <a href="<?php echo esc_url( calendarp_back_to_calendar_link() ); ?>">
                    <small><?php _e( 'Back to Calendar', 'calendar-plus' ); ?></small>
                </a>
            </p>
        <?php endif; ?>

        <div class="cal-plus-event__header-subheader">

            <div class="cal-plus-event__meta">

                <?php if ( ! empty( calendarp_event_human_read_dates( 'date' ) ) ) : ?>
                    <div class="cal-plus-event__meta-item cal-plus-event__meta-item--dates">
                        <span class="dashicons dashicons-calendar-alt"></span> <?php echo calendarp_event_human_read_dates( 'date' ); ?>
                    </div>
                <?php endif; ?>

                <?php if ( ! empty( calendarp_event_human_read_dates( 'recurrence' ) ) ) : ?>
                    <div class="cal-plus-event__meta-item cal-plus-event__meta-item--recurrence">
                        <span class="dashicons dashicons-update"></span> <?php echo calendarp_event_human_read_dates( 'recurrence' ); ?>
                    </div>
                <?php endif; ?>

                <?php if ( ! empty( calendarp_event_human_read_dates( 'time' ) ) ) : ?>
                    <div class="cal-plus-event__meta-item cal-plus-event__meta-item--time">
                        <span class="dashicons dashicons-clock"></span> <?php echo calendarp_event_human_read_dates( 'time' ); ?>
                    </div>
                <?php endif; ?>

                <div class="cal-plus-event__meta-item cal-plus-event__meta-item--categories">
                    <?php calendarp_post_content_event_categories_list(); ?>
                </div>

                <div class="cal-plus-event__meta-item cal-plus-event__meta-item--add-to-calendar">
                    <?php _e( 'Add to', 'calendar-plus' ); ?>:
                    <?php calendarp_event_add_to_calendars_links(); ?>
                </div>
            </div>
        </div>

        <?php if ( is_single() && calendarp_event_has_location() ) : ?>
            <div class="cal-plus-event__location">
                <p><span class="dashicons dashicons-location"></span> <?php echo calendarp_the_event_location()->get_full_address(); ?></p>
                <div class="cal-plus-event__location-description"><?php echo calendarp_get_location_description(); ?></div>
                <?php echo calendarp_get_google_map_html( calendarp_the_event_location()->ID ); ?>
            </div>
        <?php endif; ?>

        <div class="<?php echo esc_attr( apply_filters( 'calendarp_post_content_event_content_class', 'cal-plus-event__content' ) ); ?>">
            <div class="cal-plus-event__inner-content">
                <?php do_action( 'calendarp_post_content_event_content', calendarp_the_event() ); ?>
            </div>
        </div>
    </div>
</div>