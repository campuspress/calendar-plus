<?php
/**
 * Calendar Plus: includes/rest-api/abstract-calendar-plus-rest-controller.php.
 *
 * @author WPMUDEV, Ignacio Cruz (igmoweb)
 * @package Calendar_Plus
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** Calendar component: Calendar_Plus_REST_Controller. */
class Calendar_Plus_REST_Controller extends WP_REST_Controller {

	/**
	 * Endpoint namespace.
	 *
	 * @var string
	 */
	protected $namespace = 'calendar-plus/v1';

	/**
	 * Route base.
	 *
	 * @var string
	 */
	protected $rest_base = '';

	/**
	 * Post type.
	 *
	 * @var string
	 */
	protected $post_type = 'calendar_event';
}
