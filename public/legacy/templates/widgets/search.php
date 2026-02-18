<?php
/**
 * Legacy Search Widget Template
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

<form role="search" method="get" class="search-form calendarp-search-form" action="<?php echo esc_url( $form_action ); ?>">
	<p>
		<label for="calendarp-search-s" class="show-for-sr">
			<?php echo esc_attr_x( 'Search for events:', 'label', 'calendar-plus' ); ?>
		</label>
		<input type="search" 
		       class="search-field" 
		       name="s" 
		       id="calendarp-search-s"
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
		       value="<?php echo esc_attr_x( 'Search', 'submit button', 'calendar-plus' ); ?>">
	</p>
</form>
