<?php
/**
 * Safe, editable public details for TEC imports.
 *
 * @package Calendar_Plus
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** Renders allowlisted details without executing source content. */
class Calendar_Plus_TEC_Details {
	/**
	 * Read one scalar source field.
	 *
	 * @param array  $post Source record.
	 * @param string $key Metadata key.
	 * @return string Value.
	 */
	public static function value( $post, $key ) {
		return $post['postmeta'][ $key ][0] ?? '';
	}

	/**
	 * Validate a public website for display, without requesting it.
	 *
	 * @param string $url Source website.
	 * @return string Safe URL or empty string.
	 */
	public static function website( $url ) {
		$parts = wp_parse_url( $url );
		if ( ! $parts || empty( $parts['host'] ) || false === strpos( $parts['host'], '.' ) || isset( $parts['user'] ) || isset( $parts['pass'] ) || ! in_array( strtolower( $parts['scheme'] ?? '' ), array( 'http', 'https' ), true ) ) {
			return '';
		}
		return esc_url_raw( $url, array( 'http', 'https' ) );
	}

	/**
	 * Make a labelled, escaped public field.
	 *
	 * @param string $label Translated label.
	 * @param string $value Source value.
	 * @param string $kind Text, website, email or phone.
	 * @param string $body Existing description for link deduplication.
	 * @return string List item or empty string.
	 */
	private static function field( $label, $value, $kind = 'text', $body = '' ) {
		if ( '' === $value ) {
			return '';
		}
		$text = esc_html( sanitize_text_field( $value ) );
		$url  = '';
		if ( 'website' === $kind ) {
			$url = self::website( $value );
			if ( ! $url || in_array( $url, Calendar_Plus_TEC_Media::urls( $body ), true ) ) {
				return '';
			}
		} elseif ( 'email' === $kind ) {
			if ( ! is_email( $value ) ) {
				return '';
			}
			$url = 'mailto:' . $value;
		} elseif ( 'phone' === $kind ) {
			$number = preg_replace( '/[^0-9+]/', '', $value );
			$url    = preg_match( '/^\+?[0-9]{3,20}$/', $number ) ? 'tel:' . $number : '';
		}
		if ( $url ) {
			$text = '<a href="' . esc_url( $url, array( 'http', 'https', 'mailto', 'tel' ) ) . '">' . $text . '</a>';
		}
		return '<li><strong>' . esc_html( $label ) . ':</strong> ' . $text . '</li>';
	}

	/**
	 * Build ordinary HTML for public details that lack native fields.
	 *
	 * @param array  $post Prepared source event and dependencies.
	 * @param string $body Description without an importer-owned section.
	 * @return string Generated section content.
	 */
	public static function render( $post, $body ) {
		$items = self::field( __( 'Website', 'calendar-plus' ), self::value( $post, '_EventURL' ), 'website', $body );
		foreach ( array(
			'_EventCost'           => __( 'Cost', 'calendar-plus' ),
			'_EventCurrencySymbol' => __( 'Currency', 'calendar-plus' ),
			'_EventPhone'          => __( 'Phone', 'calendar-plus' ),
		) as $key => $label ) {
			if ( '_EventCurrencySymbol' === $key && '' === self::value( $post, '_EventCost' ) ) {
				continue;
			}
			$items .= self::field( $label, self::value( $post, $key ), '_EventPhone' === $key ? 'phone' : 'text' );
		}
		$html = $items ? '<h2>' . esc_html__( 'Details', 'calendar-plus' ) . '</h2><ul>' . $items . '</ul>' : '';
		if ( ! empty( $post['organizer_records'] ) ) {
			$html .= '<h2>' . esc_html__( 'Organiser', 'calendar-plus' ) . '</h2>';
			foreach ( $post['organizer_records'] as $organizer ) {
				$html  .= '<h3>' . esc_html( $organizer['post_title'] ) . '</h3>' . wp_kses_post( $organizer['post_content'] );
				$items  = self::field( __( 'Phone', 'calendar-plus' ), self::value( $organizer, '_OrganizerPhone' ), 'phone' );
				$items .= self::field( __( 'Email', 'calendar-plus' ), self::value( $organizer, '_OrganizerEmail' ), 'email' );
				$items .= self::field( __( 'Website', 'calendar-plus' ), self::value( $organizer, '_OrganizerWebsite' ), 'website', $body . $html );
				$html  .= $items ? '<ul>' . $items . '</ul>' : '';
			}
		}
		if ( ! empty( $post['venue_record'] ) ) {
			$venue   = $post['venue_record'];
			$address = array();
			foreach ( array( '_VenueAddress', '_VenueCity', '_VenueStateProvince', '_VenueZip', '_VenueCountry' ) as $key ) {
				$value = sanitize_text_field( self::value( $venue, $key ) );
				if ( '_VenueStateProvince' === $key && ! $value ) {
					$value = sanitize_text_field( self::value( $venue, '_VenueState' ) );
					$value = $value ? $value : sanitize_text_field( self::value( $venue, '_VenueProvince' ) );
				}
				if ( $value ) {
					$address[] = $value;
				}
			}
			$html  .= '<h2>' . esc_html__( 'Venue', 'calendar-plus' ) . '</h2><p><strong>' . esc_html( $venue['post_title'] ) . '</strong></p>';
			$html  .= wp_kses_post( $venue['post_content'] );
			$html  .= $address ? '<p>' . esc_html( implode( ', ', array_unique( $address ) ) ) . '</p>' : '';
			$items  = self::field( __( 'Phone', 'calendar-plus' ), self::value( $venue, '_VenuePhone' ), 'phone' );
			$items .= self::field( __( 'Website', 'calendar-plus' ), self::value( $venue, '_VenueURL' ), 'website', $body . $html );
			$html  .= $items ? '<ul>' . $items . '</ul>' : '';
			$flags  = array( self::value( $post, '_EventShowMapLink' ), self::value( $venue, '_VenueShowMapLink' ) );
			if ( ! array_intersect( array_map( 'strtolower', $flags ), array( 'false', 'no', '0' ) ) && self::value( $venue, '_VenueAddress' ) && $address ) {
				$html .= '<p><a href="' . esc_url( 'https://maps.google.com/maps?q=' . rawurlencode( implode( ', ', $address ) ) ) . '">' . esc_html__( 'View Google Map', 'calendar-plus' ) . '</a></p>';
			}
		}
		return wp_kses_post( $html );
	}

	/**
	 * Propose a section replacement only when the importer still owns it.
	 *
	 * @param array  $post Prepared source event.
	 * @param string $content Current body.
	 * @param string $receipt Last generated section hash.
	 * @param array  $urls Successful media URL mappings.
	 * @param array  $ids Successful media ID mappings.
	 * @return array|WP_Error Body and receipt, or a local edit conflict.
	 */
	public static function propose( $post, $content, $receipt = '', $urls = array(), $ids = array() ) {
		$start = '<!-- calendarp-tec-details:start -->';
		$end   = '<!-- calendarp-tec-details:end -->';
		$from  = strpos( $content, $start );
		$to    = strpos( $content, $end );
		$old   = '';
		if ( false !== $from || false !== $to || $receipt ) {
			if ( false === $from || false === $to || $to < $from || 1 !== substr_count( $content, $start ) || 1 !== substr_count( $content, $end ) ) {
				return new WP_Error( 'calendarp_section', __( 'The imported details section was removed or changed. Your description was preserved.', 'calendar-plus' ) );
			}
			$old = substr( $content, $from, $to + strlen( $end ) - $from );
			if ( ! $receipt || ! hash_equals( $receipt, hash( 'sha256', $old ) ) ) {
				return new WP_Error( 'calendarp_section', __( 'The imported details section was edited. Your description was preserved.', 'calendar-plus' ) );
			}
		}
		$body    = $old ? str_replace( $old, '', $content ) : $content;
		$html    = self::render( $post, $body );
		$html    = $urls || $ids ? Calendar_Plus_TEC_Media::rewrite( $html, $urls, $ids ) : $html;
		$section = $html ? $start . "\n" . $html . "\n" . $end : '';
		$updated = $old ? str_replace( $old, $section, $content ) : $content . ( $section ? "\n\n" . $section : '' );
		if ( strlen( $updated ) > MB_IN_BYTES ) {
			return new WP_Error( 'calendarp_body_limit', __( 'The combined description and related details exceed 1 MB. Review or simplify this event.', 'calendar-plus' ) );
		}
		return array(
			'content' => $updated,
			'receipt' => $section ? hash( 'sha256', $section ) : '',
		);
	}
}
