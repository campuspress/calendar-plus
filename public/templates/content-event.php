<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

?>


	<article id="event-<?php the_ID(); ?>" <?php post_class(); ?>>

		<?php do_action( 'calendarp_before_content_event', calendarp_the_event() ); ?>

		<header class="<?php echo esc_attr( apply_filters( 'calendarp_event_content_header_class', 'event-header entry-header' ) ); ?>">
			<?php if ( calendarp_back_to_calendar_link() ) : ?>
				<div class="row">
					<p class="large-12 columns">
						<a href="<?php echo esc_url( calendarp_back_to_calendar_link() ); ?>"><small><?php _e( 'Back to Calendar', 'calendar-plus' ); ?></small></a>
					</p>
				</div>
			<?php endif; ?>

			<div class="row">

				<div class="event-thumbnail large-5 columns">
					<?php if ( is_single() ) : ?>
						<?php echo calendarp_get_the_event_thumbnail( get_the_ID(), 'event_thumbnail' ); ?>
					<?php else : ?>
						<a href="<?php the_permalink(); ?>" title="<?php esc_attr( sprintf( __( 'Permalink to %s', 'calendar-plus' ), get_permalink() ) ); ?>">
							<?php echo calendarp_get_the_event_thumbnail( get_the_ID(), 'event_thumbnail' ); ?>
						</a>
					<?php endif; ?>

				</div>

				<div class="event-header-subheader large-7 columns">
					<h2 class="event-title entry-title">
						<?php if ( is_single() ) : ?>
							<?php the_title(); ?>
						<?php else : ?>
							<a href="<?php the_permalink(); ?>" title="<?php esc_attr( sprintf( __( 'Permalink to %s', 'calendar-plus' ), get_permalink() ) ); ?>">
								<?php the_title(); ?>
							</a>
						<?php endif; ?>
					</h2>

					<div class="event-meta">

						<?php if ( ! empty( calendarp_event_human_read_dates( 'date' ) ) ) : ?>
							<div class="event-meta-item event-dates"><span class="dashicons dashicons-calendar-alt"></span> <?php echo calendarp_event_human_read_dates( 'date' ); ?></div>
						<?php endif; ?>

						<?php if ( ! empty( calendarp_event_human_read_dates( 'recurrence' ) ) ) : ?>
							<div class="event-meta-item event-recurrence"><span class="dashicons dashicons-update"></span> <?php echo calendarp_event_human_read_dates( 'recurrence' ); ?></div>
						<?php endif; ?>

						<?php if ( ! empty( calendarp_event_human_read_dates( 'time' ) ) ) : ?>
							<div class="event-meta-item event-time"><span class="dashicons dashicons dashicons-clock"></span> <?php echo calendarp_event_human_read_dates( 'time' ); ?></div>
						<?php endif; ?>

						<div class="event-meta-item event-categories">
							<?php calendarp_event_categories_list(); ?>
						</div>

						<div class="event-meta-item">
							<?php _e( 'Add to', 'calendar-plus' ); ?>:
							<?php calendarp_event_add_to_calendars_links(); ?>
						</div>
					</div>
				</div>
			</div>


			<?php if ( is_single() && calendarp_event_has_location() ) : ?>
				<div class="row">
					<div class="event-location large-12 columns panel">
						<p><span class="dashicons dashicons-location"></span> <?php echo calendarp_the_event_location()->get_full_address(); ?></p>
						<div class="event-location-description"><?php echo calendarp_get_location_description(); ?></div>
						<?php echo calendarp_get_google_map_html( calendarp_the_event_location()->ID ); ?>
					</div>
				</div>
			<?php endif; ?>

		</header>

		<div class="<?php echo esc_attr( apply_filters( 'calendarp_event_content_content_class', 'event-content entry-content' ) ); ?>">

			<div class="row event-inner-content">
				<div class="large-12 columns">
					<?php do_action( 'calendarp_content_event_content', calendarp_the_event() ); ?>
				</div>
			</div>

		</div>

		<footer class="<?php echo esc_attr( apply_filters( 'calendarp_event_content_footer_class', 'event-entry-footer event-footer entry-footer' ) ); ?>">
			<div class="row event-inner-footer">
				<div class="large-12 columns">
					<?php edit_post_link( __( 'Edit', 'calendar-plus' ), '<span class="edit-link">', '</span>' ); ?>
					<?php do_action( 'calendarp_content_event_footer', calendarp_the_event() ); ?>
				</div>
			</div>

		</footer>

		<?php do_action( 'calendarp_after_content_event' ); ?>

	</article>
