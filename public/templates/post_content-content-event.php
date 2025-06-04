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

		<?php 
		$calendar_link = calendarp_back_to_calendar_link();
		if ( $calendar_link ) : 
			?>
			<p class="cal-plus-event__back-to-calendar">
				<a href="<?php echo esc_url( $calendar_link ); ?>">
					<small><?php _e( 'Back to Calendar', 'calendar-plus' ); ?></small>
				</a>
			</p>
			<?php 
		endif; 
		?>

		<ul class="cal-plus-event__meta">
			<?php
			$event_dates = calendarp_event_human_read_dates( 'date' );
			if ( ! empty( $event_dates ) ) :
				?>
				<li class="cal-plus-event__meta-item cal-plus-event__meta-item--dates">
					<span class="cal-plus-event__meta-item-icon dashicons dashicons-calendar-alt" aria-hidden="true"></span> 
					<span class="cal-plus-event__meta-item-text"><?php echo $event_dates; ?></span>
				</li>
				<?php 
			endif; 
			?>

			<?php
			$event_recurrence = calendarp_event_human_read_dates( 'recurrence' );
			if ( ! empty( $event_recurrence ) ) :
				?>
				<li class="cal-plus-event__meta-item cal-plus-event__meta-item--recurrence">
					<span class="cal-plus-event__meta-item-icon dashicons dashicons-update" aria-hidden="true"></span> 
					<span class="cal-plus-event__meta-item-text"><?php echo $event_recurrence; ?></span>
				</li>
			<?php endif; ?>

			<?php
			$event_time = calendarp_event_human_read_dates( 'time' );
			if ( ! empty( $event_time ) ) :
				?>
				<li class="cal-plus-event__meta-item cal-plus-event__meta-item--time">
					<span class="cal-plus-event__meta-item-icon dashicons dashicons-clock" aria-hidden="true"></span> 
					<span class="cal-plus-event__meta-item-text"><?php echo $event_time; ?></span>
				</li>
				<?php 
			endif; 
			?>

			<?php
			$event_categories_list = calendarp_get_post_content_event_categories_list();
			if ( ! empty( $event_categories_list ) ) :
				?>
				<li class="cal-plus-event__meta-item cal-plus-event__meta-item--categories">
					<?php echo $event_categories_list; ?>
				</li>
				<?php 
			endif; 
			?>

			<li class="cal-plus-event__meta-item cal-plus-event__meta-item--add-to-calendar">
				<?php _e( 'Add to', 'calendar-plus' ); ?>:
				<?php calendarp_event_add_to_calendars_links(); ?>
			</li>
		</ul>

		<?php if ( is_single() && calendarp_event_has_location() ) : ?>
			<address class="cal-plus-event__location">
				<p>
					<span class="dashicons dashicons-location"></span> 
					<?php echo calendarp_the_event_location()->get_full_address(); ?>
				</p>

				<div class="cal-plus-event__location-description">
					<?php echo calendarp_get_location_description(); ?>
				</div>
				<?php echo calendarp_get_google_map_html( calendarp_the_event_location()->ID ); ?>
			</address>
		<?php endif; ?>

		<hr />

		<div class="<?php echo esc_attr( apply_filters( 'calendarp_post_content_event_content_class', 'cal-plus-event__content' ) ); ?>">
			<div class="cal-plus-event__inner-content">
				<?php do_action( 'calendarp_post_content_event_content', calendarp_the_event() ); ?>
			</div>
		</div>
	</div>
</div>
