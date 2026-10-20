<?php
/**
 * Calendar Plus: includes/class-calendar-plus-event-feed.php.
 *
 * @package Calendar_Plus
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Bounded, public occurrence queries and event presentation.
 *
 * @package Calendar_Plus
 */

/** Event feed rendering uses the native date API, never a second date table. */
class Calendar_Plus_Event_Feed {

	/** Maximum occurrences or filtered candidate posts in one feed query. */
	const LIMIT = 1000;

	/**
	 * Parse a strict site-local date or datetime without normalising invalid dates.
	 *
	 * @param string $value Date or datetime.
	 * @return DateTimeImmutable|false
	 */
	public static function date( $value ) {
		$format = 10 === strlen( $value ) ? 'Y-m-d' : 'Y-m-d H:i:s';
		$date   = DateTimeImmutable::createFromFormat( '!' . $format, $value, wp_timezone() );
		return $date && $date->format( $format ) === $value ? $date : false;
	}

	/**
	 * Get filtered, sorted occurrence rows within explicit resource limits.
	 *
	 * @param array $args View, dates, category/tag IDs, search and ordering.
	 * @param int   $now Optional instant for deterministic callers.
	 * @return array|WP_Error
	 */
	public static function query( $args = array(), $now = 0 ) {
		$args = wp_parse_args(
			$args,
			array(
				'view'          => 'upcoming',
				'from'          => '',
				'to'            => '',
				'category'      => array(),
				'tag'           => array(),
				'categoryExtra' => array(),
				'lightweight'   => false,
				'search'        => '',
				'order'         => '',
			)
		);
		if ( ! in_array( $args['view'], array( 'upcoming', 'past', 'all', 'range' ), true ) ) {
			return new WP_Error( 'calendarp_feed_view', __( 'Choose a supported event view.', 'calendar-plus' ) );
		}
		$now   = $now ? $now : time();
		$today = wp_date( 'Y-m-d', $now, wp_timezone() );
		$from  = 'upcoming' === $args['view'] ? self::date( $today ) : false;
		$to    = 'past' === $args['view'] ? self::date( $today ) : false;
		if ( 'range' === $args['view'] ) {
			$from = self::date( (string) $args['from'] );
			$to   = self::date( (string) $args['to'] );
			if ( ! $from || ! $to || $from > $to || 10 !== strlen( $args['from'] ) || 10 !== strlen( $args['to'] ) ) {
				return new WP_Error( 'calendarp_feed_range', __( 'Enter a valid start and end date in YYYY-MM-DD format.', 'calendar-plus' ) );
			}
			$to = $to->setTime( 23, 59, 59 );
		}
		$tax_query = array( 'relation' => 'AND' );
		foreach ( array( 'category', 'tag', 'categoryExtra' ) as $tax ) {
			$ids = is_array( $args[ $tax ] ) ? $args[ $tax ] : explode( ',', (string) $args[ $tax ] );
			$ids = array_values( array_unique( array_filter( array_map( 'absint', array_slice( $ids, 0, 100 ) ) ) ) );
			if ( $ids ) {
				$tax_query[] = array(
					'taxonomy' => 'calendar_event_' . ( 'categoryExtra' === $tax ? 'category' : $tax ),
					'field'    => 'term_id',
					'terms'    => $ids,
				);
			}
		}
		$search = sanitize_text_field( substr( (string) $args['search'], 0, 200 ) );
		$ids    = array();
		if ( count( $tax_query ) > 1 || $search ) {
			$candidates = new WP_Query(
				array(
					// An array deliberately avoids the legacy scalar calendar query override.
					'post_type'              => array( 'calendar_event' ),
					'calendarp_native_posts' => true,
					'post_status'            => 'publish',
					'has_password'           => false,
					'posts_per_page'         => self::LIMIT + 1,
					'no_found_rows'          => true,
					'fields'                 => 'ids',
					'orderby'                => 'ID',
					'order'                  => 'ASC',
					// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query -- Bounded native taxonomy filtering, no custom SQL.
					'tax_query'              => $tax_query,
					's'                      => $search,
				)
			);
			$ids = $candidates->posts;
			if ( count( $ids ) > self::LIMIT ) {
				return self::limit_error();
			}
			if ( ! $ids ) {
				return array();
			}
		}
		$order = 'DESC' === strtoupper( $args['order'] ) || ( ! $args['order'] && 'past' === $args['view'] ) ? 'DESC' : 'ASC';
		// The native date-only API expects wall-clock timestamps, not UTC instants.
		$native_from = $from ? ( new DateTimeImmutable( $from->format( 'Y-m-d' ), new DateTimeZone( 'UTC' ) ) )->getTimestamp() : false;
		$native_to   = $to ? ( new DateTimeImmutable( $to->format( 'Y-m-d' ), new DateTimeZone( 'UTC' ) ) )->getTimestamp() : false;
		$rows        = calendarp_get_events_in_date_range(
			$native_from,
			$native_to,
			array(
				'include_ids'     => $ids,
				'events_per_page' => self::LIMIT + 1,
				'grouped_by_day'  => false,
				'order'           => $order,
			)
		);
		if ( count( $rows ) > self::LIMIT ) {
			return self::limit_error();
		}
		if ( ! $rows ) {
			return array();
		}
		$posts  = new WP_Query(
			array(
				'post_type'              => array( 'calendar_event' ),
				'calendarp_native_posts' => true,
				'post_status'            => 'publish',
				'has_password'           => false,
				'post__in'               => array_unique( array_map( 'absint', wp_list_pluck( $rows, 'event_id' ) ) ),
				'posts_per_page'         => self::LIMIT,
				'no_found_rows'          => true,
				'fields'                 => $args['lightweight'] ? 'ids' : 'all',
				'update_post_meta_cache' => ! $args['lightweight'],
				'update_post_term_cache' => ! $args['lightweight'],
			)
		);
		$public = array_fill_keys( $args['lightweight'] ? $posts->posts : wp_list_pluck( $posts->posts, 'ID' ), true );
		$result = array();
		foreach ( $rows as $row ) {
			if ( ! isset( $public[ $row->event_id ] ) ) {
				continue;
			}
			$start = self::date( $row->from_date . ' ' . self::clock( $row->from_time, '00:00:00' ) );
			$end   = self::date( $row->until_date . ' ' . self::clock( $row->until_time, '23:59:59' ) );
			if ( ! $start || ! $end || $end < $start ) {
				continue;
			}
			if ( ( 'upcoming' === $args['view'] && $end->getTimestamp() < $now ) || ( 'past' === $args['view'] && $end->getTimestamp() >= $now ) ) {
				continue;
			}
			$row                = clone $row;
			$row->start         = $start->getTimestamp();
			$row->end           = $end->getTimestamp();
			$result[ $row->ID ] = $row;
		}
		usort(
			$result,
			static function ( $a, $b ) use ( $order ) {
				$comparison = array( $a->start, (int) $a->event_id, (int) $a->ID ) <=> array( $b->start, (int) $b->event_id, (int) $b->ID );
				return 'DESC' === $order ? -$comparison : $comparison;
			}
		);
		return $result;
	}

	/**
	 * Normalise a stored native time.
	 *
	 * @param string $value Native time.
	 * @param string $fallback Time if absent.
	 * @return string
	 */
	private static function clock( $value, $fallback ) {
		return $value ? ( 5 === strlen( $value ) ? $value . ':00' : $value ) : $fallback;
	}

	/**
	 * Explain the fixed query bound.
	 *
	 * @return WP_Error Resource limit.
	 */
	private static function limit_error() {
		return new WP_Error( 'calendarp_feed_limit', __( 'This feed exceeds 1,000 events or occurrences. Narrow the date range, category or tag filters.', 'calendar-plus' ) );
	}

	/**
	 * Use real event context rather than another event's global state.
	 *
	 * @param WP_Block|null $block Rendering block.
	 * @return int Readable event ID or zero.
	 */
	public static function context_id( $block = null ) {
		$id   = $block && isset( $block->context['postId'] ) ? absint( $block->context['postId'] ) : get_the_ID();
		$post = get_post( $id );
		if ( ! $post || 'calendar_event' !== $post->post_type || post_password_required( $post ) ) {
			return 0;
		}
		return 'publish' === $post->post_status || current_user_can( 'read_post', $id ) ? $id : 0;
	}

	/**
	 * Safe fallback summary without executing source blocks or shortcodes.
	 *
	 * @param WP_Post $post Event post.
	 * @param int     $words Maximum words.
	 * @return string Plain summary.
	 */
	public static function summary( $post, $words = 40 ) {
		$text = $post->post_excerpt ? $post->post_excerpt : preg_replace( '/<!-- calendarp-tec-details:start -->.*?<!-- calendarp-tec-details:end -->/s', '', $post->post_content );
		$text = preg_replace( '#<(script|style)\b[^>]*>.*?</\1>#is', '', $text );
		$text = strip_shortcodes( $text );
		return wp_trim_words( wp_strip_all_tags( $text ), max( 5, min( 100, (int) $words ) ) );
	}

	/**
	 * Readable occurrence interval in the site's timezone.
	 *
	 * @param object $row Native occurrence with timestamps.
	 * @return string
	 */
	public static function interval( $row ) {
		$date_format = get_option( 'date_format' );
		$time_format = get_option( 'time_format' );
		$all_day     = get_post_meta( $row->event_id, '_all_day', true );
		$format      = $all_day ? $date_format : $date_format . ' ' . $time_format;
		$start       = wp_date( $format, $row->start, wp_timezone() );
		$end         = wp_date( $format, $row->end, wp_timezone() );
		return $start === $end ? $start : $start . ' – ' . $end;
	}

	/**
	 * Format a native Location address with its name highlighted exactly once.
	 *
	 * @param Calendar_Plus_Location $location Native Location.
	 * @param bool                   $highlight Emphasise the name.
	 * @return string Escaped address HTML.
	 */
	public static function location_html( $location, $highlight = true ) {
		$title   = get_the_title( $location->ID );
		$address = $location->get_full_address();
		if ( $highlight && $title && str_starts_with( $address, $title ) ) {
			return '<strong>' . esc_html( $title ) . '</strong>' . esc_html( substr( $address, strlen( $title ) ) );
		}
		return esc_html( $address );
	}

	/**
	 * Render a feed with independent, ordinary link/form navigation.
	 *
	 * @param array $attributes Block settings.
	 * @return string Safe HTML.
	 */
	public static function render( $attributes ) {
		$attributes = wp_parse_args(
			$attributes,
			array(
				'view'          => 'upcoming',
				'from'          => '',
				'to'            => '',
				'category'      => '',
				'tag'           => '',
				'search'        => '',
				'order'         => '',
				'perPage'       => 10,
				'showImage'     => true,
				'showLocation'  => true,
				'showSummary'   => true,
				'showDate'      => true,
				'showReadMore'  => true,
				'monthHeadings' => true,
				'pastFallback'  => false,
				'summaryWords'  => 40,
				'imageFit'      => 'contain',
				'imageRatio'    => 'landscape',
				'imagePosition' => 'right',
				'showSearch'    => false,
				'headingLevel'  => 3,
				'navigationId'  => '',
			)
		);
		$instance   = wp_unique_id( 'calendarp-feed-' );
		$key        = sanitize_key( $attributes['navigationId'] );
		if ( ! $key ) {
			$key = $instance;
		}
		$page_key   = 'cp_page_' . $key;
		$search_key = 'cp_search_' . $key;
		// Public, read-only query controls; no nonce is needed for link navigation.
		// phpcs:disable WordPress.Security.NonceVerification.Recommended
		$page = isset( $_GET[ $page_key ] ) && is_scalar( $_GET[ $page_key ] ) ? max( 1, min( 100, absint( $_GET[ $page_key ] ) ) ) : 1;
		if ( $attributes['showSearch'] && isset( $_GET[ $search_key ] ) && is_string( $_GET[ $search_key ] ) ) {
			$attributes['search'] = substr( sanitize_text_field( wp_unslash( $_GET[ $search_key ] ) ), 0, 200 );
		}
		// phpcs:enable WordPress.Security.NonceVerification.Recommended
		$rows = self::query( $attributes );
		if ( is_wp_error( $rows ) ) {
			return '<p class="calendarp-feed-notice">' . esc_html( $rows->get_error_message() ) . '</p>';
		}
		$fallback = false;
		if ( ! $rows && 'upcoming' === $attributes['view'] && $attributes['pastFallback'] && ! $attributes['search'] ) {
			$past          = $attributes;
			$past['view']  = 'past';
			$past['order'] = 'DESC';
			$rows          = self::query( $past );
			$fallback      = true;
		}
		if ( is_wp_error( $rows ) ) {
			return '<p class="calendarp-feed-notice">' . esc_html( $rows->get_error_message() ) . '</p>';
		}
		$per_page = max( 1, min( 50, (int) $attributes['perPage'] ) );
		$total    = count( $rows );
		$page     = min( $page, max( 1, (int) ceil( $total / $per_page ) ) );
		$rows     = array_slice( $rows, ( $page - 1 ) * $per_page, $per_page );
		// Prime the current page's images and Locations together to avoid per-row queries.
		$related = array();
		foreach ( $rows as $row ) {
			$related[] = get_post_thumbnail_id( $row->event_id );
			$related[] = absint( get_post_meta( $row->event_id, '_location_id', true ) );
		}
		$related = array_values( array_unique( array_filter( $related ) ) );
		if ( $related ) {
			new WP_Query(
				array(
					'post_type'      => array( 'attachment', 'calendar_location' ),
					'post_status'    => array( 'inherit', 'publish' ),
					'has_password'   => false,
					'post__in'       => $related,
					'posts_per_page' => 100,
					'no_found_rows'  => true,
				)
			);
		}
		$fit      = 'cover' === $attributes['imageFit'] ? 'cover' : 'contain';
		$ratio    = in_array( $attributes['imageRatio'], array( 'landscape', 'square', 'portrait' ), true ) ? $attributes['imageRatio'] : 'landscape';
		$position = 'left' === $attributes['imagePosition'] ? 'left' : 'right';
		$level    = max( 2, min( 6, (int) $attributes['headingLevel'] ) );
		$wrapper  = get_block_wrapper_attributes(
			array(
				'class' => 'calendarp-feed calendarp-feed--' . $position . ' calendarp-media--' . $fit . ' calendarp-media--' . $ratio,
				'id'    => $instance,
			)
		);
		ob_start();
		?>
		<div <?php echo $wrapper; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Core escapes wrapper attributes. ?>>
			<?php if ( $attributes['showSearch'] ) : ?>
				<form method="get" class="calendarp-feed-search">
					<?php
					// Preserve other feed controls and plain-permalink routing parameters.
					// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Public read-only search form.
					foreach ( $_GET as $name => $value ) {
						if ( $name !== $page_key && $name !== $search_key && is_scalar( $value ) && strlen( (string) $value ) < 300 && preg_match( '/^(?:cp_(?:page|search)_[a-z0-9_-]+|page_id|p|post_type|lang)$/', $name ) ) {
							echo '<input type="hidden" name="' . esc_attr( $name ) . '" value="' . esc_attr( wp_unslash( $value ) ) . '">';
						}
					}
					?>
					<label for="<?php echo esc_attr( $instance . '-search' ); ?>"><?php esc_html_e( 'Search events', 'calendar-plus' ); ?></label>
					<input type="search" id="<?php echo esc_attr( $instance . '-search' ); ?>" name="<?php echo esc_attr( $search_key ); ?>" value="<?php echo esc_attr( $attributes['search'] ); ?>" maxlength="200">
					<button type="submit"><?php esc_html_e( 'Search', 'calendar-plus' ); ?></button>
				</form>
			<?php endif; ?>
			<?php if ( $fallback ) : ?>
				<p><?php esc_html_e( 'No upcoming events. Showing latest past events.', 'calendar-plus' ); ?></p>
			<?php endif; ?>
			<?php if ( ! $rows ) : ?>
				<p><?php esc_html_e( 'No events match these settings.', 'calendar-plus' ); ?></p>
			<?php endif; ?>
			<?php
			$month = '';
			foreach ( $rows as $row ) :
				$post  = get_post( $row->event_id );
				$event = calendarp_get_event( $row->event_id );
				$group = wp_date( 'F Y', $row->start, wp_timezone() );
				if ( $attributes['monthHeadings'] && $group !== $month ) {
					echo '<h' . esc_attr( max( 2, $level - 1 ) ) . ' class="calendarp-feed-month">' . esc_html( $group ) . '</h' . esc_attr( max( 2, $level - 1 ) ) . '>';
					$month = $group;
				}
				$image = $attributes['showImage'] && has_post_thumbnail( $post ) ? get_the_post_thumbnail(
					$post,
					'medium_large',
					array(
						'class' => 'calendarp-feed-image',
						'sizes' => '(max-width: 600px) 100vw, 320px',
					)
				) : '';
				?>
				<article class="calendarp-feed-row<?php echo $image ? ' calendarp-feed-row--image' : ''; ?><?php echo $attributes['showDate'] ? ' calendarp-feed-row--date' : ''; ?>" data-occurrence="<?php echo esc_attr( $row->ID ); ?>">
					<?php if ( $attributes['showDate'] ) : ?>
						<div class="calendarp-feed-date" aria-hidden="true"><span><?php echo esc_html( wp_date( 'M', $row->start, wp_timezone() ) ); ?></span><strong><?php echo esc_html( wp_date( 'j', $row->start, wp_timezone() ) ); ?></strong></div>
					<?php endif; ?>
					<div class="calendarp-feed-text">
						<p class="calendarp-feed-time"><time datetime="<?php echo esc_attr( wp_date( DATE_ATOM, $row->start, wp_timezone() ) ); ?>"><?php echo esc_html( self::interval( $row ) ); ?></time> <span><?php echo esc_html( wp_timezone_string() ); ?></span></p>
						<?php echo '<h' . esc_attr( $level ) . '><a href="' . esc_url( get_permalink( $post ) ) . '">' . esc_html( get_the_title( $post ) ) . '</a></h' . esc_attr( $level ) . '>'; ?>
						<?php
						$location = $event->get_location();
						if ( $attributes['showLocation'] && $location && 'publish' === get_post_status( $location->ID ) && ! post_password_required( $location->ID ) ) {
							echo '<p class="calendarp-feed-location">' . wp_kses_post( self::location_html( $location ) ) . '</p>';
						}
						if ( $attributes['showSummary'] ) {
							echo '<p>' . esc_html( self::summary( $post, $attributes['summaryWords'] ) ) . '</p>';
						}
						if ( $attributes['showReadMore'] ) {
							echo '<a href="' . esc_url( get_permalink( $post ) ) . '">' . esc_html__( 'Read more', 'calendar-plus' ) . '<span class="screen-reader-text">: ' . esc_html( get_the_title( $post ) ) . '</span></a>';
						}
						?>
					</div>
					<?php if ( $image ) : ?>
						<div class="calendarp-feed-media"><?php echo wp_kses_post( $image ); ?></div>
					<?php endif; ?>
				</article>
			<?php endforeach; ?>
			<?php if ( $total > $per_page ) : ?>
				<nav class="calendarp-feed-pagination" aria-label="<?php esc_attr_e( 'Event feed pages', 'calendar-plus' ); ?>">
					<?php if ( $page > 1 ) : ?>
						<a href="<?php echo esc_url( add_query_arg( $page_key, $page - 1 ) . '#' . $instance ); ?>"><?php esc_html_e( 'Previous', 'calendar-plus' ); ?></a>
					<?php endif; ?>
					<span><?php /* translators: 1: current page, 2: total pages. */ echo esc_html( sprintf( __( 'Page %1$d of %2$d', 'calendar-plus' ), $page, (int) ceil( $total / $per_page ) ) ); ?></span>
					<?php if ( $page * $per_page < $total ) : ?>
						<a href="<?php echo esc_url( add_query_arg( $page_key, $page + 1 ) . '#' . $instance ); ?>"><?php esc_html_e( 'Next', 'calendar-plus' ); ?></a>
					<?php endif; ?>
				</nav>
			<?php endif; ?>
		</div>
		<?php
		return ob_get_clean();
	}
}
