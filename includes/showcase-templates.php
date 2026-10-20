<?php
/**
 * Editable showcase defaults, shared by the inserter, patterns and PHP previews.
 *
 * @package Calendar_Plus
 */

if ( ! defined( 'ABSPATH' ) ) {
	return array();
}
$calendarp_showcase_group    = static function ( $css_class, $children ) {
	return array( 'core/group', array( 'className' => $css_class ), $children );
};
$calendarp_showcase_text     = static function ( $content, $css_class = '' ) {
	return array(
		'core/paragraph',
		array(
			'content'   => $content,
			'className' => $css_class,
		),
	);
};
$calendarp_showcase_heading  = static function ( $content, $level, $css_class = '' ) {
	return array(
		'core/heading',
		array(
			'content'   => $content,
			'level'     => $level,
			'className' => $css_class,
		),
	);
};
$calendarp_showcase_field    = static function ( $type, $attributes = array() ) {
	return array( 'calendar-plus/showcase-field', array_merge( array( 'field' => $type ), $attributes ) );
};
$calendarp_showcase_section  = static function ( $role, $children ) {
	return array( 'calendar-plus/showcase-section', array( 'role' => $role ), $children );
};
$calendarp_showcase_meta     = $calendarp_showcase_group( 'calendarp-showcase-meta', array( $calendarp_showcase_field( 'date' ), $calendarp_showcase_field( 'time' ), $calendarp_showcase_field( 'location' ) ) );
$calendarp_showcase_card     = array(
	$calendarp_showcase_group( 'calendarp-showcase-visual', array( $calendarp_showcase_field( 'image' ), $calendarp_showcase_field( 'dateBadge' ), $calendarp_showcase_field( 'categories' ) ) ),
	$calendarp_showcase_group(
		'calendarp-showcase-copy',
		array(
			array(
				'core/post-title',
				array(
					'level'  => 3,
					'isLink' => true,
				),
			),
			$calendarp_showcase_field( 'summary' ),
			$calendarp_showcase_meta,
			$calendarp_showcase_field( 'link', array( 'label' => __( 'Event details', 'calendar-plus' ) ) ),
		)
	),
);
$calendarp_showcase_featured = array(
	$calendarp_showcase_group(
		'calendarp-showcase-visual calendarp-showcase-feature-visual',
		array(
			$calendarp_showcase_field( 'image' ),
			$calendarp_showcase_text( __( 'Featured event', 'calendar-plus' ), 'calendarp-showcase-feature-label' ),
			$calendarp_showcase_heading( __( 'New perspectives. Shared discoveries.', 'calendar-plus' ), 3, 'calendarp-showcase-visual-title' ),
		)
	),
	$calendarp_showcase_group(
		'calendarp-showcase-copy calendarp-showcase-feature-copy',
		array(
			$calendarp_showcase_field( 'categories' ),
			array(
				'core/post-title',
				array(
					'level'  => 3,
					'isLink' => true,
				),
			),
			$calendarp_showcase_field( 'summary', array( 'words' => 32 ) ),
			$calendarp_showcase_meta,
			$calendarp_showcase_field( 'link', array( 'label' => __( 'View event details', 'calendar-plus' ) ) ),
		)
	),
);
$calendarp_showcase_results  = array(
	$calendarp_showcase_section(
		'toolbar',
		array(
			$calendarp_showcase_group(
				'calendarp-showcase-section-top',
				array(
					$calendarp_showcase_group( '', array( $calendarp_showcase_heading( __( 'Explore our events', 'calendar-plus' ), 2 ), $calendarp_showcase_text( __( 'Ideas worth sharing. People worth meeting.', 'calendar-plus' ) ) ) ),
					$calendarp_showcase_field( 'layoutSwitch' ),
				)
			),
			$calendarp_showcase_field( 'filters' ),
		)
	),
	$calendarp_showcase_section( 'featured', $calendarp_showcase_featured ),
	$calendarp_showcase_section( 'between', array( $calendarp_showcase_group( 'calendarp-showcase-list-top', array( $calendarp_showcase_heading( __( 'More events', 'calendar-plus' ), 2 ), $calendarp_showcase_field( 'resultCount' ) ) ) ) ),
	$calendarp_showcase_section( 'card', $calendarp_showcase_card ),
	$calendarp_showcase_section(
		'footer',
		array(
			$calendarp_showcase_group(
				'calendarp-showcase-past',
				array(
					$calendarp_showcase_group( '', array( $calendarp_showcase_heading( __( 'Looking for a previous event?', 'calendar-plus' ), 3 ), $calendarp_showcase_text( __( 'Explore past talks, sessions and gatherings.', 'calendar-plus' ) ) ) ),
					$calendarp_showcase_field( 'pastLink', array( 'label' => __( 'Browse past events', 'calendar-plus' ) ) ),
				)
			),
		)
	),
);
$calendarp_showcase_hero     = $calendarp_showcase_group(
	'calendarp-showcase-hero',
	array(
		$calendarp_showcase_group(
			'calendarp-showcase-container calendarp-showcase-hero-inner',
			array(
				$calendarp_showcase_group(
					'',
					array(
						$calendarp_showcase_text( __( 'Discover · Connect · Collaborate', 'calendar-plus' ), 'calendarp-showcase-eyebrow' ),
						$calendarp_showcase_heading( __( 'Events & gatherings.', 'calendar-plus' ), 1 ),
						$calendarp_showcase_text( __( 'Explore conversations, workshops and opportunities that bring together researchers, students and communities.', 'calendar-plus' ), 'calendarp-showcase-intro' ),
					)
				),
				$calendarp_showcase_group( 'calendarp-showcase-hero-note', array( $calendarp_showcase_text( __( 'Find your next event', 'calendar-plus' ) ), $calendarp_showcase_text( __( 'Browse by topic, type or date', 'calendar-plus' ) ) ) ),
			)
		),
	)
);
return array(
	'shell'   => array( $calendarp_showcase_hero, $calendarp_showcase_group( 'calendarp-showcase-container calendarp-showcase-main', array( array( 'calendar-plus/showcase-results', array(), $calendarp_showcase_results ) ) ) ),
	'results' => $calendarp_showcase_results,
);
