<?php
/**
 * Calendar Plus: includes/class-calendar-plus-events-showcase.php.
 *
 * @package Calendar_Plus
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Editable event page templates with bounded native occurrence queries.
 *
 * @package Calendar_Plus
 */

/** The showcase repeats saved blocks, not hardcoded event card markup. */
class Calendar_Plus_Events_Showcase {

	/** Maximum template bytes and blocks processed per request. */
	const TEMPLATE_BYTES  = 65536;
	const TEMPLATE_BLOCKS = 100;

	/**
	 * Handle register.
	 *
	 * @return mixed Calendar operation result.
	 */
	public static function register() {
		$base = calendarp_get_plugin_url() . 'includes/blocks/events-showcase/';
		wp_register_style( 'calendarp-showcase', $base . 'style.css', array(), calendarp_get_version() );
		wp_register_style( 'calendarp-showcase-editor', $base . 'editor.css', array( 'wp-edit-blocks' ), calendarp_get_version() );
		wp_register_script( 'calendarp-showcase-view', $base . 'view.js', array(), calendarp_get_version(), true );
		wp_register_script( 'calendarp-showcase-editor', $base . 'editor.js', array( 'wp-blocks', 'wp-element', 'wp-block-editor', 'wp-components', 'wp-i18n', 'wp-data', 'wp-api-fetch', 'wp-server-side-render' ), calendarp_get_version(), true );
		wp_add_inline_script( 'calendarp-showcase-editor', 'window.calendarpShowcaseTemplates = ' . wp_json_encode( self::templates() ) . ';', 'before' );
		wp_set_script_translations( 'calendarp-showcase-editor', 'calendar-plus' );
		foreach ( array( 'events-showcase', 'showcase-results', 'showcase-section', 'showcase-field' ) as $slug ) {
			register_block_type( calendarp_get_plugin_dir() . 'includes/blocks/' . $slug, array( 'skip_inner_blocks' => 'showcase-results' === $slug ) );
		}
		foreach ( array( 'grid', 'list' ) as $layout ) {
			$defaults                                = self::templates();
			$defaults['shell'][1][2][0][1]['layout'] = $layout;
			$markup                                  = self::markup( array( array( 'calendar-plus/events-showcase', array( 'align' => 'full' ), $defaults['shell'] ) ) );
			register_block_pattern(
				'calendar-plus/events-showcase-' . $layout,
				array(
					'title'       => 'list' === $layout ? __( 'Events Showcase — List', 'calendar-plus' ) : __( 'Events Showcase — Grid', 'calendar-plus' ),
					'description' => __( 'An editable events page with a hero, filters, a featured event and event cards.', 'calendar-plus' ),
					'categories'  => array( 'posts' ),
					'content'     => $markup,
				)
			);
		}
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'template_styles' ) );
		if ( wp_is_block_theme() && function_exists( 'register_block_template' ) ) {
			register_block_template(
				'calendar-plus//events-showcase-page',
				array(
					'title'       => __( 'Events Showcase Page', 'calendar-plus' ),
					'description' => __( 'A complete events page with an editable site header, navigation and footer. Insert Events Showcase in the page content and edit this template in the Site Editor.', 'calendar-plus' ),
					'post_types'  => array( 'page' ),
					'content'     => self::page_template(),
				)
			);
		}
	}

	/**
	 * Handle template styles.
	 *
	 * @return mixed Calendar operation result.
	 */
	public static function template_styles() {
		if ( is_page() && 'events-showcase-page' === get_page_template_slug() ) {
			wp_enqueue_style( 'calendarp-showcase' );
		}
	}

	/**
	 * A fresh page template with native, independently editable site branding.
	 *
	 * @return string Ordinary WordPress template blocks.
	 */
	private static function page_template() {
		return '<!-- wp:group {"className":"calendarp-showcase-site-frame","layout":{"type":"default"}} --><div class="wp-block-group calendarp-showcase-site-frame">'
			. '<!-- wp:group {"className":"calendarp-showcase-site-topbar","layout":{"type":"default"}} --><div class="wp-block-group calendarp-showcase-site-topbar">'
			. '<!-- wp:group {"className":"calendarp-showcase-container","layout":{"type":"default"}} --><div class="wp-block-group calendarp-showcase-container">'
			. '<!-- wp:paragraph --><p>' . esc_html__( 'Discover · Connect · Collaborate', 'calendar-plus' ) . '</p><!-- /wp:paragraph --></div><!-- /wp:group --></div><!-- /wp:group -->'
			. '<!-- wp:group {"tagName":"header","className":"calendarp-showcase-site-header","layout":{"type":"default"}} --><header class="wp-block-group calendarp-showcase-site-header">'
			. '<!-- wp:group {"className":"calendarp-showcase-container calendarp-showcase-site-header-inner","layout":{"type":"default"}} --><div class="wp-block-group calendarp-showcase-container calendarp-showcase-site-header-inner">'
			. '<!-- wp:group {"className":"calendarp-showcase-brand","layout":{"type":"default"}} --><div class="wp-block-group calendarp-showcase-brand">'
			. '<!-- wp:site-logo {"width":42} /--><!-- wp:group {"className":"calendarp-showcase-brand-text","layout":{"type":"default"}} --><div class="wp-block-group calendarp-showcase-brand-text">'
			. '<!-- wp:site-title {"level":0} /--><!-- wp:site-tagline /--></div><!-- /wp:group --></div><!-- /wp:group -->'
			. '<!-- wp:navigation {"overlayMenu":"mobile"} --><!-- wp:home-link ' . wp_json_encode( array( 'label' => __( 'Home', 'calendar-plus' ) ) ) . ' /--><!-- wp:navigation-link ' . wp_json_encode(
				array(
					'label' => __( 'Events', 'calendar-plus' ),
					'url'   => '#calendarp-events-content',
					'kind'  => 'custom',
				)
			) . ' /--><!-- /wp:navigation --></div><!-- /wp:group --></header><!-- /wp:group -->'
			. '<!-- wp:group {"tagName":"main","className":"calendarp-showcase-site-main","anchor":"calendarp-events-content","layout":{"type":"default"}} --><main class="wp-block-group calendarp-showcase-site-main" id="calendarp-events-content"><!-- wp:post-content {"align":"full"} /--></main><!-- /wp:group -->'
			. '<!-- wp:group {"tagName":"footer","className":"calendarp-showcase-site-footer","layout":{"type":"default"}} --><footer class="wp-block-group calendarp-showcase-site-footer">'
			. '<!-- wp:group {"className":"calendarp-showcase-container calendarp-showcase-site-footer-inner","layout":{"type":"default"}} --><div class="wp-block-group calendarp-showcase-container calendarp-showcase-site-footer-inner">'
			. '<!-- wp:site-title {"level":0} /--><!-- wp:paragraph --><p>' . esc_html__( 'Connecting people, ideas and events.', 'calendar-plus' ) . '</p><!-- /wp:paragraph -->'
			. '</div><!-- /wp:group --></footer><!-- /wp:group --></div><!-- /wp:group -->';
	}

	/**
	 * Read translated, shared template definitions.
	 *
	 * @return array Inserter templates.
	 */
	public static function templates() {
		return require calendarp_get_plugin_dir() . 'includes/showcase-templates.php';
	}

	/**
	 * Serialize trusted defaults into ordinary WordPress blocks.
	 *
	 * @param array $definitions InnerBlocks template definitions.
	 * @return string Block markup.
	 */
	public static function markup( $definitions ) {
		$result = '';
		foreach ( $definitions as $definition ) {
			$name     = $definition[0];
			$attrs    = isset( $definition[1] ) ? $definition[1] : array();
			$children = isset( $definition[2] ) ? $definition[2] : array();
			$content  = '';
			if ( 'core/group' === $name ) {
				$content = '<div class="wp-block-group' . ( empty( $attrs['className'] ) ? '' : ' ' . esc_attr( $attrs['className'] ) ) . '">' . self::markup( $children ) . '</div>';
			} elseif ( in_array( $name, array( 'core/paragraph', 'core/heading' ), true ) ) {
				$tag     = 'core/heading' === $name ? 'h' . absint( $attrs['level'] ) : 'p';
				$class   = 'core/heading' === $name ? 'wp-block-heading' : '';
				$class  .= empty( $attrs['className'] ) ? '' : ' ' . $attrs['className'];
				$content = '<' . $tag . ( trim( $class ) ? ' class="' . esc_attr( trim( $class ) ) . '"' : '' ) . '>' . esc_html( $attrs['content'] ) . '</' . $tag . '>';
				unset( $attrs['content'] );
			} else {
				$content = self::markup( $children );
			}
			// Dynamic template containers store their children without an extra saved wrapper.
			$result .= get_comment_delimited_block_content( $name, $attrs, $content );
		}
		return $result;
	}

	/**
	 * Keep native style values from introducing additional CSS declarations.
	 *
	 * @param array $values Structured core style values.
	 * @return array Valid styles, including theme presets and native media URLs.
	 */
	private static function safe_style( $values ) {
		$result = array();
		foreach ( $values as $name => $value ) {
			if ( is_array( $value ) ) {
				$result[ $name ] = self::safe_style( $value );
			} elseif ( is_string( $value ) ) {
				if ( 'url' === $name ) {
					$result[ $name ] = esc_url_raw( $value, array( 'http', 'https' ) );
				} elseif ( ! str_contains( $value, '\\' ) && ! preg_match( '~[;{}<>]|/\*|\b(?:url|expression)\s*\(|javascript\s*:|@import~i', $value ) ) {
					$result[ $name ] = $value;
				}
			} elseif ( is_numeric( $value ) || is_bool( $value ) ) {
				$result[ $name ] = $value;
			}
		}
		return $result;
	}

	/**
	 * Generate escaped core wrapper attributes with sanitized native styles.
	 *
	 * @param array $extra Extra attributes owned by this renderer.
	 * @return string Core wrapper attributes.
	 */
	private static function wrapper( $extra = array() ) {
		$previous = WP_Block_Supports::$block_to_render;
		try {
			if ( isset( WP_Block_Supports::$block_to_render['attrs']['style'] ) && is_array( WP_Block_Supports::$block_to_render['attrs']['style'] ) ) {
				WP_Block_Supports::$block_to_render['attrs']['style'] = self::safe_style( WP_Block_Supports::$block_to_render['attrs']['style'] );
			}
			return get_block_wrapper_attributes( $extra );
		} finally {
			WP_Block_Supports::$block_to_render = $previous;
		}
	}

	/**
	 * Render the editable page shell with a validated colour palette.
	 *
	 * @param array  $attributes Block options.
	 * @param string $content Rendered children.
	 * @return string Safe HTML.
	 */
	public static function shell( $attributes, $content ) {
		$allowed = array( 'ink', 'muted', 'line', 'cream', 'accent', 'accent-dark', 'soft', 'gold', 'hero', 'hero-text', 'card', 'control', 'control-text', 'button-text', 'date', 'past', 'tone-a', 'tone-b', 'tone-c', 'tone-d', 'tone-e', 'tone-f' );
		$styles  = '';
		foreach ( $allowed as $name ) {
			$value = isset( $attributes['palette'][ $name ] ) && is_string( $attributes['palette'][ $name ] ) ? sanitize_hex_color( $attributes['palette'][ $name ] ) : '';
			if ( $value ) {
				$styles .= '--cp-' . $name . ':' . $value . ';';
			}
		}
		$wrapper = self::wrapper(
			array(
				'class' => 'calendarp-showcase',
				'style' => $styles,
			)
		);
		return '<div ' . $wrapper . '>' . $content . '</div>';
	}

	/**
	 * Render a section. Card and featured templates inherit their event context.
	 *
	 * @param array    $attributes Block options.
	 * @param string   $content Rendered children.
	 * @param WP_Block $block Block instance.
	 * @return string Safe HTML.
	 */
	public static function section( $attributes, $content, $block ) {
		$role  = isset( $attributes['role'] ) && in_array( $attributes['role'], array( 'toolbar', 'featured', 'between', 'card', 'footer' ), true ) ? $attributes['role'] : 'card';
		$tag   = in_array( $role, array( 'card', 'featured' ), true ) ? 'article' : 'div';
		$extra = array( 'class' => 'calendarp-showcase-' . $role );
		if ( isset( $block->context['calendarp/occurrence'] ) ) {
			$extra['data-occurrence'] = absint( $block->context['calendarp/occurrence']->ID );
		}
		return '<' . $tag . ' ' . self::wrapper( $extra ) . '>' . $content . '</' . $tag . '>';
	}

	/**
	 * Allow only presentation blocks in a bounded shared event template.
	 *
	 * @param array $blocks Parsed blocks.
	 * @param int   $count Blocks visited.
	 * @param int   $depth Current nesting.
	 * @return bool Whether valid.
	 */
	private static function valid_template( $blocks, &$count, $depth = 0 ) {
		$allowed = array( 'core/group', 'core/columns', 'core/column', 'core/heading', 'core/paragraph', 'core/post-title', 'core/post-terms', 'core/buttons', 'core/button', 'core/separator', 'core/spacer', 'core/image', 'calendar-plus/showcase-section', 'calendar-plus/showcase-field' );
		if ( $depth > 12 ) {
			return false;
		}
		foreach ( $blocks as $item ) {
			if ( null === $item['blockName'] && '' === trim( $item['innerHTML'] ) ) {
				continue;
			}
			if ( ++$count > self::TEMPLATE_BLOCKS || ! in_array( $item['blockName'], $allowed, true ) || ! self::valid_template( $item['innerBlocks'], $count, $depth + 1 ) ) {
				return false;
			}
		}
		return true;
	}

	/**
	 * Read a namespaced, read-only visitor query parameter.
	 *
	 * @param string $key Parameter.
	 * @param string $fallback Default.
	 * @return string Sanitized bounded value.
	 */
	private static function input( $key, $fallback = '' ) {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Public read-only navigation, never mutates site data.
		return isset( $_GET[ $key ] ) && is_string( $_GET[ $key ] ) ? sanitize_text_field( substr( sanitize_text_field( wp_unslash( $_GET[ $key ] ) ), 0, 200 ) ) : $fallback;
	}

	/**
	 * Build a safe link from the current page and allowlisted navigation state.
	 *
	 * @param array $state Showcase navigation state.
	 * @param array $changes Changes for this block.
	 * @return string URL.
	 */
	private static function url( $state, $changes = array() ) {
		return add_query_arg( array_merge( $state['parameters'], $changes ), $state['base'] ) . '#' . $state['anchor'];
	}

	/**
	 * Render real events with editable sections, functional filters and pagination.
	 *
	 * @param array    $a Validated metadata attributes.
	 * @param string   $content Unused; children are repeated in event context.
	 * @param WP_Block $block Parsed card templates.
	 * @return string Safe HTML.
	 */
	public static function results( $a, $content, $block ) {
		$key = substr( sanitize_key( $a['navigationId'] ), 0, 64 );
		$key = $key ? $key : wp_unique_id( 'events-' );
		// Also isolate multiple copies of a saved pattern on one page.
		static $used  = array();
		$used[ $key ] = isset( $used[ $key ] ) ? $used[ $key ] + 1 : 1;
		$key          = $used[ $key ] > 1 ? $key . '-' . $used[ $key ] : $key;
		$keys         = array();
		foreach ( array( 'page', 'search', 'category', 'date', 'layout' ) as $control ) {
			$keys[ $control ] = 'cps_' . $control . '_' . $key;
		}
		$templates = $block->parsed_block['innerBlocks'];
		if ( defined( 'REST_REQUEST' ) && REST_REQUEST && current_user_can( 'edit_posts' ) && ! empty( $a['previewContent'] ) && strlen( $a['previewContent'] ) <= self::TEMPLATE_BYTES ) {
			$templates = parse_blocks( $a['previewContent'] );
		}
		if ( ! $templates ) {
			$defaults  = self::templates();
			$templates = parse_blocks( self::markup( $defaults['results'] ) );
		}
		$count = 0;
		if ( strlen( serialize_blocks( $templates ) ) > self::TEMPLATE_BYTES || ! self::valid_template( $templates, $count ) ) {
			return '<p>' . esc_html__( 'The event template is too large or contains unsupported blocks. Use presentation blocks only.', 'calendar-plus' ) . '</p>';
		}
		$sections = array();
		$fields   = array();
		foreach ( $templates as $template ) {
			if ( 'calendar-plus/showcase-section' === $template['blockName'] ) {
				$role              = isset( $template['attrs']['role'] ) ? $template['attrs']['role'] : 'card';
				$sections[ $role ] = $template;
			}
		}
		self::find_filters( $templates, $fields );
		$search                = ! empty( $fields['showSearch'] ) ? self::input( $keys['search'] ) : '';
		$category              = ! empty( $fields['showCategoryFilter'] ) ? absint( self::input( $keys['category'] ) ) : 0;
		$date                  = ! empty( $fields['showDateFilter'] ) || ! empty( $fields['allowPastLink'] ) ? self::input( $keys['date'] ) : '';
		$date                  = in_array( $date, array( 'upcoming', 'past', 'month', 'all' ), true ) ? $date : '';
		$args                  = $a;
		$args['search']        = trim( $a['search'] . ' ' . $search );
		$args['categoryExtra'] = $category ? array( $category ) : array();
		$args['lightweight']   = true;
		if ( $date && 'range' !== $a['view'] ) {
			$args['view'] = 'month' === $date ? 'range' : $date;
			if ( 'month' === $date ) {
				$args['from'] = wp_date( 'Y-m-01' );
				$args['to']   = wp_date( 'Y-m-t' );
			}
		}
		$rows     = Calendar_Plus_Event_Feed::query( $args );
		$fallback = false;
		if ( ! is_wp_error( $rows ) && ! $rows && 'upcoming' === $args['view'] && $a['pastFallback'] && ! $date && ! $search && ! $category ) {
			$args['view']  = 'past';
			$args['order'] = 'DESC';
			$rows          = Calendar_Plus_Event_Feed::query( $args );
			$fallback      = true;
		}
		$notice = is_wp_error( $rows ) ? $rows->get_error_message() : '';
		$rows   = is_wp_error( $rows ) ? array() : $rows;
		// Keep the public native taxonomy catalogue bounded and independent of active filters.
		$catalogue = get_terms(
			array(
				'taxonomy'   => 'calendar_event_category',
				'hide_empty' => true,
				'number'     => 100,
			)
		);
		$terms     = is_array( $catalogue ) ? wp_list_pluck( $catalogue, 'name', 'term_id' ) : array();
		if ( $category && ! isset( $terms[ $category ] ) ) {
			$selected = get_term( $category, 'calendar_event_category' );
			if ( $selected && ! is_wp_error( $selected ) ) {
				$terms[ $category ] = $selected->name;
			}
		}
		$matches = array();
		foreach ( $rows as $row ) {
			if ( 'range' === $a['view'] && $date && ( ( 'upcoming' === $date && $row->end < time() ) || ( 'past' === $date && $row->end >= time() ) || ( 'month' === $date && ( $row->until_date < wp_date( 'Y-m-01' ) || $row->from_date > wp_date( 'Y-m-t' ) ) ) ) ) {
				continue;
			}
			$matches[] = $row;
		}
		asort( $terms, SORT_NATURAL | SORT_FLAG_CASE );

		$total    = count( $matches );
		$featured = null;
		if ( $a['showFeatured'] && isset( $sections['featured'] ) ) {
			foreach ( $matches as $index => $row ) {
				if ( ! $a['featuredId'] || absint( $a['featuredId'] ) === (int) $row->event_id ) {
					$featured = $row;
					unset( $matches[ $index ] );
					$matches = array_values( $matches );
					break;
				}
			}
		}
		$per_page    = max( 1, min( 50, (int) $a['perPage'] ) );
		$pages       = max( 1, (int) ceil( count( $matches ) / $per_page ) );
		$page        = max( 1, min( $pages, absint( self::input( $keys['page'], '1' ) ) ) );
		$visible     = array_slice( $matches, ( $page - 1 ) * $per_page, $per_page );
		$visible_ids = array_values( array_unique( array_map( 'absint', wp_list_pluck( array_merge( $visible, $featured && 1 === $page ? array( $featured ) : array() ), 'event_id' ) ) ) );
		if ( $visible_ids ) {
			// Load full descriptions, metadata and terms only for the displayed page.
			new WP_Query(
				array(
					'post_type'              => array( 'calendar_event' ),
					'calendarp_native_posts' => true,
					'post_status'            => 'publish',
					'has_password'           => false,
					'post__in'               => $visible_ids,
					'posts_per_page'         => 51,
					'no_found_rows'          => true,
				)
			);
		}
		$related = array();
		foreach ( array_merge( $visible, $featured && 1 === $page ? array( $featured ) : array() ) as $row ) {
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
					// phpcs:ignore WordPress.WP.PostsPerPage.posts_per_page_posts_per_page -- At most 51 visible rows, two related records per row.
					'posts_per_page' => 102,
					'no_found_rows'  => true,
				)
			);
		}
		$parameters = array();
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Preserve only bounded read-only routing and other block controls.
		foreach ( $_GET as $name => $value ) {
			if ( is_string( $value ) && strlen( $value ) <= 200 && preg_match( '/^(?:cps_(?:page|search|category|date|layout)_[a-z0-9_-]{1,70}|cp_(?:page|search)_[a-z0-9_-]+|page_id|p|post_type|lang)$/', $name ) ) {
				$parameters[ $name ] = sanitize_text_field( wp_unslash( $value ) );
			}
		}
		$layout                        = self::input( $keys['layout'], $a['layout'] );
		$layout                        = 'list' === $layout ? 'list' : 'grid';
		$parameters[ $keys['layout'] ] = $layout;
		$state                         = array(
			'key'         => $key,
			'keys'        => $keys,
			'anchor'      => 'calendarp-results-' . $key,
			'base'        => get_permalink(),
			'parameters'  => $parameters,
			'layout'      => $layout,
			'search'      => $search,
			'category'    => $category,
			'date'        => $date,
			'defaultDate' => $a['view'],
			'terms'       => $terms,
			'total'       => $total,
			'page'        => $page,
		);
		$context                       = array(
			'calendarp/showcase' => $state,
			'postId'             => 0,
			'postType'           => 'calendar_event',
		);
		$columns                       = max( 1, min( 4, (int) $a['columns'] ) );
		$gap                           = max( 0, min( 100, (int) $a['gap'] ) );
		$wrapper                       = self::wrapper(
			array(
				'class'              => 'calendarp-showcase-results',
				'id'                 => $state['anchor'],
				'style'              => '--cp-columns:' . $columns . ';--cp-gap:' . $gap . 'px;',
				'data-calendarp-key' => $key,
				'data-layout'        => $layout,
			)
		);
		wp_enqueue_script( 'calendarp-showcase-view' );
		$html  = '<div ' . $wrapper . '>';
		$html .= isset( $sections['toolbar'] ) ? self::repeat( $sections['toolbar'], $context ) : '';
		if ( $notice || $fallback ) {
			$html .= '<p class="calendarp-showcase-notice" role="status">' . esc_html( $notice ? $notice : __( 'No upcoming events. Showing latest past events.', 'calendar-plus' ) ) . '</p>';
		}
		if ( $featured && 1 === $page ) {
			$html .= self::repeat( $sections['featured'], $context, $featured );
		}
		$html .= isset( $sections['between'] ) && $visible ? self::repeat( $sections['between'], $context ) : '';
		$html .= '<div class="calendarp-showcase-cards">';
		if ( ! $total && ! $notice ) {
			$html .= '<p role="status">' . esc_html__( 'No events match these filters.', 'calendar-plus' ) . '</p>';
		}
		foreach ( $visible as $row ) {
			if ( isset( $sections['card'] ) ) {
				$html .= self::repeat( $sections['card'], $context, $row );
			}
		}
		$html .= '</div>';
		if ( $pages > 1 ) {
			$html .= '<nav class="calendarp-showcase-pagination" aria-label="' . esc_attr__( 'Event pages', 'calendar-plus' ) . '">';
			if ( $page > 1 ) {
				$html .= '<a href="' . esc_url( self::url( $state, array( $keys['page'] => $page - 1 ) ) ) . '">' . esc_html__( 'Previous', 'calendar-plus' ) . '</a>';
			}
			/* translators: 1: current page, 2: total pages. */
			$html .= '<span>' . esc_html( sprintf( __( 'Page %1$d of %2$d', 'calendar-plus' ), $page, $pages ) ) . '</span>';
			if ( $page < $pages ) {
				$html .= '<a href="' . esc_url( self::url( $state, array( $keys['page'] => $page + 1 ) ) ) . '">' . esc_html__( 'Next', 'calendar-plus' ) . '</a>';
			}
			$html .= '</nav>';
		}
		$html .= isset( $sections['footer'] ) ? self::repeat( $sections['footer'], $context ) : '';
		return $html . '</div>';
	}

	/**
	 * Detect the saved filter field's visitor permissions.
	 *
	 * @param array $blocks Templates.
	 * @param array $fields Found field options.
	 *
	 * @return mixed Calendar operation result.
	 */
	private static function find_filters( $blocks, &$fields ) {
		foreach ( $blocks as $item ) {
			if ( 'calendar-plus/showcase-field' === $item['blockName'] && isset( $item['attrs']['field'] ) && 'filters' === $item['attrs']['field'] ) {
				$type   = WP_Block_Type_Registry::get_instance()->get_registered( $item['blockName'] );
				$fields = $type->prepare_attributes_for_render( $item['attrs'] );
			}
			if ( 'calendar-plus/showcase-field' === $item['blockName'] && isset( $item['attrs']['field'] ) && 'pastLink' === $item['attrs']['field'] ) {
				$fields['allowPastLink'] = true;
			}
			self::find_filters( $item['innerBlocks'], $fields );
		}
	}

	/**
	 * Sanitize saved presentation HTML without stripping our safe form renderer.
	 *
	 * @param array $template Parsed template, updated in place.
	 *
	 * @return mixed Calendar operation result.
	 */
	private static function clean_template( &$template ) {
		foreach ( array( 'style', 'layout' ) as $key ) {
			if ( isset( $template['attrs'][ $key ] ) && is_array( $template['attrs'][ $key ] ) ) {
				$template['attrs'][ $key ] = self::safe_style( $template['attrs'][ $key ] );
			}
		}
		$template['innerHTML'] = wp_kses_post( $template['innerHTML'] );
		foreach ( $template['innerContent'] as &$chunk ) {
			if ( is_string( $chunk ) ) {
				$chunk = wp_kses_post( $chunk );
			}
		}
		unset( $chunk );
		foreach ( $template['innerBlocks'] as &$child ) {
			self::clean_template( $child );
		}
	}

	/**
	 * Repeat a presentation template with the correct event and occurrence context.
	 *
	 * @param array       $template Parsed template.
	 * @param array       $context Shared context.
	 * @param object|null $row Native occurrence.
	 * @return string Safe template HTML.
	 */
	private static function repeat( $template, $context, $row = null ) {
		if ( $row ) {
			$context['postId']               = (int) $row->event_id;
			$context['calendarp/occurrence'] = $row;
		}
		self::clean_template( $template );
		// Core Post Title intentionally reads the global post, even with block context.
		$loop_keys = array( 'post', 'id', 'authordata', 'currentday', 'currentmonth', 'page', 'pages', 'multipage', 'more', 'numpages' );
		$previous  = array();
		foreach ( $loop_keys as $name ) {
			$previous[ $name ] = array( array_key_exists( $name, $GLOBALS ), isset( $GLOBALS[ $name ] ) ? $GLOBALS[ $name ] : null );
		}
		try {
			if ( $row ) {
				// phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- Core Post Title reads the global post; all loop globals are restored in finally.
				$GLOBALS['post'] = get_post( $row->event_id );
				setup_postdata( $GLOBALS['post'] );
			}
			$rendered = ( new WP_Block( $template, $context ) )->render();
			if ( $row ) {
				$rendered = wp_kses_post( $rendered );
			}
		} finally {
			foreach ( $previous as $name => $value ) {
				if ( $value[0] ) {
					// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- Restore WordPress-owned loop globals, not plugin globals.
					$GLOBALS[ $name ] = $value[1];
				} else {
					unset( $GLOBALS[ $name ] );
				}
			}
		}

		// User-added template anchors must not become duplicate IDs on repeated cards.
		if ( $row && class_exists( 'WP_HTML_Tag_Processor' ) ) {
			$anchors   = array();
			$processor = new WP_HTML_Tag_Processor( $rendered );
			while ( $processor->next_tag() ) {
				$id = $processor->get_attribute( 'id' );
				if ( is_string( $id ) && $id ) {
					$anchors[ $id ] = $id . '-' . absint( $row->ID ) . '-' . sanitize_key( $context['calendarp/showcase']['key'] );
					$processor->set_attribute( 'id', $anchors[ $id ] );
				}
			}
			$links = new WP_HTML_Tag_Processor( $processor->get_updated_html() );
			while ( $links->next_tag() ) {
				$href = $links->get_attribute( 'href' );
				if ( is_string( $href ) && str_starts_with( $href, '#' ) && isset( $anchors[ substr( $href, 1 ) ] ) ) {
					$links->set_attribute( 'href', '#' . $anchors[ substr( $href, 1 ) ] );
				}
			}
			$rendered = $links->get_updated_html();
		}
		return $rendered;
	}

	/**
	 * Render a dynamic field or functional visitor control with core styles.
	 *
	 * @param array    $a Field settings.
	 * @param string   $content Unused.
	 * @param WP_Block $block Context.
	 * @return string Safe HTML.
	 */
	public static function field( $a, $content, $block ) {
		$type  = $a['field'];
		$state = isset( $block->context['calendarp/showcase'] ) ? $block->context['calendarp/showcase'] : null;
		$html  = '';
		if ( $state && in_array( $type, array( 'filters', 'layoutSwitch', 'resultCount', 'pastLink' ), true ) ) {
			$html = self::control( $type, $a, $state );
		} else {
			$id = Calendar_Plus_Event_Feed::context_id( $block );
			if ( ! $id ) {
				return '';
			}
			$row = isset( $block->context['calendarp/occurrence'] ) ? $block->context['calendarp/occurrence'] : null;
			if ( 'summary' === $type ) {
				$html = '<p>' . esc_html( Calendar_Plus_Event_Feed::summary( get_post( $id ), $a['words'] ) ) . '</p>';
			} elseif ( 'image' === $type ) {
				$fit      = 'cover' === $a['imageFit'] ? 'cover' : 'contain';
				$ratio    = in_array( $a['imageRatio'], array( 'square', 'portrait' ), true ) ? $a['imageRatio'] : 'landscape';
				$position = in_array( $a['imagePosition'], array( 'top', 'bottom', 'left', 'right' ), true ) ? $a['imagePosition'] : 'center';
				$html     = has_post_thumbnail( $id ) ? get_the_post_thumbnail(
					$id,
					'medium_large',
					array(
						'loading'  => 'lazy',
						'decoding' => 'async',
						'sizes'    => '(max-width: 650px) 100vw, (max-width: 950px) 50vw, 33vw',
						'style'    => 'object-fit:' . $fit . ';object-position:' . $position . ';',
						'class'    => 'calendarp-showcase-image-' . $ratio,
					)
				) : '<span class="calendarp-showcase-no-image" aria-hidden="true">✳</span>';
			} elseif ( 'location' === $type ) {
				$location = calendarp_get_event( $id )->get_location();
				if ( $location && 'publish' === get_post_status( $location->ID ) && ! post_password_required( $location->ID ) ) {
					$html = '<p><span aria-hidden="true">⌖ </span>' . ( $a['showAddress'] ? Calendar_Plus_Event_Feed::location_html( $location ) : esc_html( get_the_title( $location->ID ) ) ) . '</p>';
				}
			} elseif ( in_array( $type, array( 'categories', 'tags' ), true ) ) {
				$terms = get_the_terms( $id, 'calendar_event_' . ( 'tags' === $type ? 'tag' : 'category' ) );
				if ( is_array( $terms ) ) {
					foreach ( array_slice( $terms, 0, 20 ) as $term ) {
						$html .= '<span>' . esc_html( $term->name ) . '</span> ';
					}
				}
			} elseif ( 'link' === $type ) {
				$html = '<a href="' . esc_url( get_permalink( $id ) ) . '">' . esc_html( $a['label'] ? $a['label'] : __( 'Event details', 'calendar-plus' ) ) . '<span aria-hidden="true"> ↗</span><span class="screen-reader-text">: ' . esc_html( get_the_title( $id ) ) . '</span></a>';
			} elseif ( $row && in_array( $type, array( 'dateBadge', 'date', 'time' ), true ) ) {
				$full     = Calendar_Plus_Event_Feed::interval( $row );
				$datetime = wp_date( DATE_ATOM, $row->start, wp_timezone() );
				if ( 'dateBadge' === $type ) {
					$html = '<time datetime="' . esc_attr( $datetime ) . '"><strong aria-hidden="true">' . esc_html( wp_date( 'd', $row->start ) ) . '</strong><small aria-hidden="true">' . esc_html( wp_date( 'M', $row->start ) ) . '</small><span class="screen-reader-text">' . esc_html( $full ) . '</span></time>';
				} elseif ( 'date' === $type ) {
					$html = '<p><span aria-hidden="true">▦ </span><time datetime="' . esc_attr( $datetime ) . '">' . esc_html( wp_date( get_option( 'date_format' ), $row->start ) . ( wp_date( 'Y-m-d', $row->start ) !== wp_date( 'Y-m-d', $row->end ) ? ' – ' . wp_date( get_option( 'date_format' ), $row->end ) : '' ) ) . '</time></p>';
				} else {
					$text = get_post_meta( $id, '_all_day', true ) ? __( 'All day', 'calendar-plus' ) : wp_date( get_option( 'time_format' ), $row->start ) . ' – ' . wp_date( get_option( 'time_format' ), $row->end );
					$html = '<p><span aria-hidden="true">◷ </span>' . esc_html( $text . ( $a['showTimezone'] ? ' ' . wp_timezone_string() : '' ) ) . '</p>';
				}
			}
		}
		return $html ? '<div ' . self::wrapper( array( 'class' => 'calendarp-showcase-field calendarp-showcase-field-' . sanitize_html_class( $type ) ) ) . '>' . $html . '</div>' : '';
	}

	/**
	 * Render read-only controls without JavaScript or REST dependencies.
	 *
	 * @param string $type Control type.
	 * @param array  $a Appearance and labels.
	 * @param array  $state Navigation state.
	 * @return string Escaped control HTML.
	 */
	private static function control( $type, $a, $state ) {
		$keys = $state['keys'];
		if ( 'resultCount' === $type ) {
			/* translators: %d: number of matching event occurrences. */
			return '<span role="status">' . esc_html( sprintf( _n( '%d event occurrence', '%d event occurrences', $state['total'], 'calendar-plus' ), $state['total'] ) ) . '</span>';
		}
		if ( 'pastLink' === $type ) {
			return '<a href="' . esc_url(
				self::url(
					$state,
					array(
						$keys['date'] => 'past',
						$keys['page'] => false,
					)
				)
			) . '">' . esc_html( $a['label'] ? $a['label'] : __( 'Browse past events', 'calendar-plus' ) ) . '<span aria-hidden="true"> →</span></a>';
		}
		if ( 'layoutSwitch' === $type ) {
			$html = '<nav aria-label="' . esc_attr__( 'Event display layout', 'calendar-plus' ) . '">';
			foreach ( array(
				'grid' => __( 'Grid', 'calendar-plus' ),
				'list' => __( 'List', 'calendar-plus' ),
			) as $layout => $label ) {
				$html .= '<a data-calendarp-layout="' . $layout . '" href="' . esc_url( self::url( $state, array( $keys['layout'] => $layout ) ) ) . '"' . ( $layout === $state['layout'] ? ' aria-current="true"' : '' ) . '><span aria-hidden="true">' . ( 'grid' === $layout ? '▦ ' : '☷ ' ) . '</span>' . esc_html( $label ) . '</a>';
			}
			return $html . '</nav>';
		}
		$html = '<form method="get" action="' . esc_url( $state['base'] . '#' . $state['anchor'] ) . '" role="search">';
		foreach ( $state['parameters'] as $name => $value ) {
			if ( ! in_array( $name, array( $keys['page'], $keys['search'], $keys['category'], $keys['date'] ), true ) ) {
				$html .= '<input type="hidden" name="' . esc_attr( $name ) . '" value="' . esc_attr( $value ) . '">';
			}
		}
		if ( $a['showSearch'] ) {
			$html .= '<label><span class="screen-reader-text">' . esc_html__( 'Search events', 'calendar-plus' ) . '</span><input type="search" name="' . esc_attr( $keys['search'] ) . '" value="' . esc_attr( $state['search'] ) . '" placeholder="' . esc_attr( $a['placeholder'] ? $a['placeholder'] : __( 'Search events by name or topic', 'calendar-plus' ) ) . '" maxlength="200"></label>';
		}
		if ( $a['showCategoryFilter'] ) {
			$html .= '<label><span class="screen-reader-text">' . esc_html( $a['categoryLabel'] ? $a['categoryLabel'] : __( 'Event type', 'calendar-plus' ) ) . '</span><select name="' . esc_attr( $keys['category'] ) . '"><option value="">' . esc_html( $a['allCategoriesLabel'] ? $a['allCategoriesLabel'] : __( 'All event types', 'calendar-plus' ) ) . '</option>';
			foreach ( $state['terms'] as $id => $name ) {
				$html .= '<option value="' . esc_attr( $id ) . '"' . selected( $state['category'], $id, false ) . '>' . esc_html( $name ) . '</option>';
			}
			$html .= '</select></label>';
		}
		if ( $a['showDateFilter'] ) {
			$html .= '<label><span class="screen-reader-text">' . esc_html( $a['dateLabel'] ? $a['dateLabel'] : __( 'Date range', 'calendar-plus' ) ) . '</span><select name="' . esc_attr( $keys['date'] ) . '">';
			foreach ( array(
				''         => __( 'Default dates', 'calendar-plus' ),
				'upcoming' => __( 'Upcoming', 'calendar-plus' ),
				'month'    => __( 'This month', 'calendar-plus' ),
				'past'     => __( 'Past events', 'calendar-plus' ),
				'all'      => __( 'Any date', 'calendar-plus' ),
			) as $value => $label ) {
				$html .= '<option value="' . esc_attr( $value ) . '"' . selected( $state['date'], $value, false ) . '>' . esc_html( $label ) . '</option>';
			}
			$html .= '</select></label>';
		}
		return $html . '<button type="submit">' . esc_html( $a['label'] ? $a['label'] : __( 'Find events', 'calendar-plus' ) ) . '<span aria-hidden="true"> →</span></button></form>';
	}
}
