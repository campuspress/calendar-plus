<?php
/**
 * Modern Search Widget Template
 *
 * @var array $args Widget display arguments
 * @var array $instance Widget settings
 * @var string $from From date value
 * @var string $to To date value
 * @var array $location_args Location dropdown arguments
 * @var array $category_args Category dropdown arguments
 */

defined( 'ABSPATH' ) || exit;

$events_page_id = calendarp_get_setting( 'events_page_id' );
$form_action = $events_page_id && get_post( $events_page_id ) 
	? get_permalink( $events_page_id ) 
	: get_post_type_archive_link( 'calendar_event' );
?>

<form role="search" method="get" class="cal-plus-search-form" action="<?php echo esc_url( $form_action ); ?>">
	<div class="cal-plus-search-form__field">
		<label for="calendarp-search-s" class="show-for-sr">
			<?php echo esc_attr_x( 'Search for events:', 'label', 'calendar-plus' ); ?>
		</label>
		<input type="search" 
		       class="cal-plus-search-form__input" 
		       name="s" 
		       id="calendarp-search-s"
		       placeholder="<?php echo esc_attr_x( 'Search Events &hellip;', 'placeholder', 'calendar-plus' ); ?>"
		       value="<?php echo esc_attr( get_search_query() ); ?>"
		       title="<?php echo esc_attr_x( 'Search for events:', 'label', 'calendar-plus' ); ?>">
	</div>

	<div class="cal-plus-search-form__field">
		<label for="calendarp-search-from" class="cal-plus-search-form__label">
			<?php _e( 'From', 'calendar-plus' ); ?>
		</label>
		<input type="text" 
		       class="calendarp-datepicker cal-plus-search-form__datepicker" 
		       id="calendarp-search-from" 
		       name="from" 
		       value="<?php echo esc_attr( $from ); ?>">
	</div>

	<div class="cal-plus-search-form__field">
		<label for="calendarp-search-to" class="cal-plus-search-form__label">
			<?php _e( 'To', 'calendar-plus' ); ?>
		</label>
		<input type="text" 
		       class="calendarp-datepicker cal-plus-search-form__datepicker" 
		       id="calendarp-search-to" 
		       name="to" 
		       value="<?php echo esc_attr( $to ); ?>">
	</div>

	<div class="cal-plus-search-form__field">
		<?php calendarp_locations_dropdown( $location_args ); ?>
	</div>

	<div class="cal-plus-search-form__field">
		<?php wp_dropdown_categories( $category_args ); ?>
	</div>

	<input type="hidden" name="post_type" value="calendar_event" />
	<input type="hidden" name="calendarp_searchw" value="true" />

	<div class="cal-plus-search-form__submit">
		<button type="submit" class="cal-plus-button cal-plus-search-form__button">
			<?php echo esc_attr_x( 'Search', 'submit button', 'calendar-plus' ); ?>
		</button>
	</div>
</form>
