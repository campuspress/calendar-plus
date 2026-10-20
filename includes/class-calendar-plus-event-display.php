<?php
/**
 * Calendar Plus: includes/class-calendar-plus-event-display.php.
 *
 * @package Calendar_Plus
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Opt-in block-theme event template and context-aware event fields.
 *
 * @package Calendar_Plus
 */

/** Template registration never changes a saved user template. */
class Calendar_Plus_Event_Display {

	/**
	 * Initialize this calendar component.
	 */
	public function __construct() {
		add_action( 'init', array( $this, 'register' ), 20 );
		add_action( 'admin_menu', array( $this, 'menu' ) );
		add_action( 'admin_post_calendarp_event_display', array( $this, 'save' ) );
	}

	/**
	 * Handle register.
	 *
	 * @return mixed Calendar operation result.
	 */
	public function register() {
		$content = '<!-- wp:group {"className":"calendarp-single-pattern","layout":{"type":"constrained"}} --><div class="wp-block-group calendarp-single-pattern">'
			. '<!-- wp:post-title {"level":1} /--><!-- wp:calendar-plus/event-status /--><!-- wp:calendar-plus/event-date /-->'
			. '<!-- wp:post-featured-image /--><!-- wp:calendar-plus/event-location {"showName":true,"hideWhenInContent":true} /-->'
			. '<!-- wp:post-content /--><!-- wp:post-terms {"term":"calendar_event_category"} /--><!-- wp:post-terms {"term":"calendar_event_tag"} /-->'
			. '<!-- wp:calendar-plus/event-add-to /--></div><!-- /wp:group -->';
		register_block_pattern(
			'calendar-plus/single-event',
			array(
				'title'         => __( 'Calendar Plus Single Event', 'calendar-plus' ),
				'description'   => __( 'Event title, dates, image, Location, description and calendar links.', 'calendar-plus' ),
				'categories'    => array( 'posts' ),
				'postTypes'     => array( 'calendar_event' ),
				'templateTypes' => array( 'single-calendar_event' ),
				'content'       => $content,
			)
		);
		if ( wp_is_block_theme() && get_option( 'calendarp_event_block_template' ) && function_exists( 'register_block_template' ) ) {
			register_block_template(
				'calendar-plus//single-calendar_event',
				array(
					'title'       => __( 'Single Event', 'calendar-plus' ),
					'description' => __( 'Calendar Plus event template. Saved theme customisations take priority.', 'calendar-plus' ),
					'content'     => '<!-- wp:template-part {"slug":"header","tagName":"header"} /--><!-- wp:group {"tagName":"main","layout":{"type":"constrained"}} --><main class="wp-block-group">' . $content . '</main><!-- /wp:group --><!-- wp:template-part {"slug":"footer","tagName":"footer"} /-->',
					'post_types'  => array( 'calendar_event' ),
				)
			);
		}
	}

	/**
	 * Handle menu.
	 *
	 * @return mixed Calendar operation result.
	 */
	public function menu() {
		add_submenu_page( 'edit.php?post_type=calendar_event', __( 'Event Display', 'calendar-plus' ), __( 'Event Display', 'calendar-plus' ), 'edit_theme_options', 'calendarp-event-display', array( $this, 'page' ) );
	}

	/**
	 * Handle page.
	 *
	 * @return mixed Calendar operation result.
	 */
	public function page() {
		// phpcs:ignore WordPress.WP.Capabilities.Unknown -- Registered by Calendar_Plus_Activator.
		if ( ! current_user_can( 'edit_theme_options' ) || ! current_user_can( 'manage_calendar_plus' ) ) {
			wp_die( esc_html__( 'You cannot change event display settings.', 'calendar-plus' ), '', array( 'response' => 403 ) );
		}
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Event Display', 'calendar-plus' ); ?></h1>
			<p><?php esc_html_e( 'Insert Events Showcase for the complete editable events page, or use the Events Showcase — Grid and Events Showcase — List patterns. Select Events Showcase Page as the page template for an editable header, navigation and footer in a block theme. Event Feed remains available for a simpler list.', 'calendar-plus' ); ?></p>
			<p><?php esc_html_e( 'The optional template applies to block themes. Your saved template takes priority and is never overwritten. Disabling this option restores the theme fallback; it does not delete your customisations.', 'calendar-plus' ); ?></p>
			<form action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" method="post">
				<input type="hidden" name="action" value="calendarp_event_display">
				<?php wp_nonce_field( 'calendarp_event_display' ); ?>
				<label><input type="checkbox" name="enabled" value="1" <?php checked( (bool) get_option( 'calendarp_event_block_template' ) ); ?> <?php disabled( ! wp_is_block_theme() || ! function_exists( 'register_block_template' ) ); ?>> <?php esc_html_e( 'Enable the Calendar Plus Single Event template', 'calendar-plus' ); ?></label>
				<?php if ( ! function_exists( 'register_block_template' ) ) : ?>
					<p><?php esc_html_e( 'Automatic template registration requires WordPress 6.7 or newer. The pattern remains available.', 'calendar-plus' ); ?></p>
				<?php endif; ?>
				<?php submit_button(); ?>
			</form>
		</div>
		<?php
	}

	/**
	 * Handle save.
	 *
	 * @return mixed Calendar operation result.
	 */
	public function save() {
		// phpcs:ignore WordPress.WP.Capabilities.Unknown -- Registered by Calendar_Plus_Activator.
		if ( ! current_user_can( 'edit_theme_options' ) || ! current_user_can( 'manage_calendar_plus' ) ) {
			wp_die( esc_html__( 'You cannot change event display settings.', 'calendar-plus' ), '', array( 'response' => 403 ) );
		}
		check_admin_referer( 'calendarp_event_display' );
		update_option( 'calendarp_event_block_template', ! empty( $_POST['enabled'] ) && wp_is_block_theme() && function_exists( 'register_block_template' ), false );
		wp_safe_redirect( admin_url( 'edit.php?post_type=calendar_event&page=calendarp-event-display' ) );
		exit;
	}

	/**
	 * Render a context-aware single-event field.
	 *
	 * @param string   $field Field type.
	 * @param array    $attributes Block options.
	 * @param WP_Block $block Block context.
	 * @return string Safe HTML.
	 */
	public static function field( $field, $attributes, $block ) {
		$id = Calendar_Plus_Event_Feed::context_id( $block );
		if ( ! $id ) {
			return '';
		}
		require_once calendarp_get_plugin_dir() . 'public/helpers-templates.php';
		if ( 'date' === $field ) {
			return '<p class="calendarp-block-event-date">' . wp_kses_post( calendarp_get_human_read_dates( $id ) ) . ' <span>' . esc_html( wp_timezone_string() ) . '</span></p>';
		}
		if ( 'location' === $field ) {
			$location = calendarp_get_event( $id )->get_location();
			if ( ! $location || post_password_required( $location->ID ) || ( 'publish' !== get_post_status( $location->ID ) && ! current_user_can( 'read_post', $location->ID ) ) ) {
				return '';
			}
			if ( ! empty( $attributes['hideWhenInContent'] ) && (int) get_post_meta( $id, '_calendarp_tec_location_id', true ) === (int) $location->ID ) {
				$content = get_post_field( 'post_content', $id );
				$receipt = get_post_meta( $id, '_calendarp_tec_section_hash', true );
				if ( $receipt && preg_match( '/<!-- calendarp-tec-details:start -->.*?<!-- calendarp-tec-details:end -->/s', $content, $match ) && hash_equals( $receipt, hash( 'sha256', $match[0] ) ) && str_contains( $match[0], '<h2>' . esc_html__( 'Venue', 'calendar-plus' ) . '</h2>' ) ) {
					return '';
				}
			}
			$highlight = ! empty( $attributes['showName'] );
			return '<p class="calendarp-block-event-location">' . Calendar_Plus_Event_Feed::location_html( $location, $highlight ) . '</p>';
		}
		if ( 'status' === $field ) {
			$rows = calendarp_get_events_in_date_range(
				false,
				false,
				array(
					'event_id'        => $id,
					'events_per_page' => Calendar_Plus_Event_Feed::LIMIT + 1,
					'grouped_by_day'  => false,
					'order'           => 'DESC',
				)
			);
			if ( count( $rows ) > Calendar_Plus_Event_Feed::LIMIT ) {
				return '';
			}
			$latest = 0;
			foreach ( $rows as $row ) {
				$end = Calendar_Plus_Event_Feed::date( $row->until_date . ' ' . ( $row->until_time ? substr( $row->until_time, 0, 5 ) . ':00' : '23:59:59' ) );
				if ( $end ) {
					$latest = max( $latest, $end->getTimestamp() );
				}
			}
			return $latest && $latest < time() ? '<p class="calendarp-event-status">' . esc_html__( 'This event has passed.', 'calendar-plus' ) . '</p>' : '';
		}
		if ( 'add-to' === $field ) {
			ob_start();
			calendarp_event_add_to_calendars_links( $id );
			return '<p class="calendarp-event-add-to">' . esc_html__( 'Add to calendar:', 'calendar-plus' ) . ' ' . wp_kses_post( ob_get_clean() ) . '</p>';
		}
		return '';
	}
}
