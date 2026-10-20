<?php
/**
 * Calendar Plus: includes/helpers/helpers-ical.php.
 *
 * @package Calendar_Plus
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handle calendarp is ical sync cron scheduled.
 *
 * @return mixed Calendar operation result.
 */
function calendarp_is_ical_sync_cron_scheduled() {
	return false !== wp_next_scheduled( 'calendar_plus_sync_ical_events' );
}

/**
 * Handle calendarp schedule ical sync cron.
 *
 * @param mixed $recurrence Recurrence.
 * @param array $args Args.
 *
 * @return mixed Calendar operation result.
 */
function calendarp_schedule_ical_sync_cron( $recurrence, $args = array() ) {
	calendarp_unschedule_ical_sync_cron();

	wp_schedule_event( time(), $recurrence, 'calendar_plus_sync_ical_events', $args );
}

/**
 * Handle calendarp unschedule ical sync cron.
 *
 * @return mixed Calendar operation result.
 */
function calendarp_unschedule_ical_sync_cron() {
	if ( wp_next_scheduled( 'calendar_plus_sync_ical_events' ) ) {
		wp_clear_scheduled_hook( 'calendar_plus_sync_ical_events' );
	}
}

/**
 * Clear legacy scheduled events - i.e. the ones with arguments.
 *
 * @param array $args Arguments for scheduled cron event.
 *
 * @return mixed Calendar operation result.
 */
function calendarp_unschedule_ical_legacy_sync_cron( $args ) {
	if ( wp_next_scheduled( 'calendar_plus_sync_ical_events', $args ) ) {
		wp_clear_scheduled_hook( 'calendar_plus_sync_ical_events', $args );
	}
}

/**
 * Clears *all* cron schedules for remote feeds sync.
 *
 * @return bool
 */
function calendarp_unschedule_all_ical_sync_crons() {
	return false !== wp_unschedule_hook( 'calendar_plus_sync_ical_events' );
}

/**
 * Clears legacy scheduling cron hook handler.
 *
 * Boots up early and takes care of any events scheduled with arguments.
 *
 * @param array $args Optional args - only set for legacy events.
 *
 * @return mixed Calendar operation result.
 */
function calendarp_ical_clear_legacy_scheduling( $args = array() ) {
	if ( empty( $args ) ) {
		// Nothing to do.
		return false;
	}
	$args = func_get_args();
	calendarp_unschedule_ical_legacy_sync_cron( $args );
	return true;
}

/**
 * Handle calendarp ical sync events.
 *
 * @return mixed Calendar operation result.
 */
function calendarp_ical_sync_events() {
	$feeds = get_option( 'calendar_plus_remote_feeds' );

	$legacy_feeds = false === $feeds ? get_option( 'calendar_plus_ical_feeds', false ) : false;
	if ( false !== $legacy_feeds ) {
		$feeds = $legacy_feeds;
		delete_option( 'calendar_plus_ical_feeds' );
		add_option( 'calendar_plus_remote_feeds', $feeds );
	}

	if ( empty( $feeds ) ) {
		return calendarp_unschedule_all_ical_sync_crons();
	}

	foreach ( $feeds as $i => $feed ) {

		if ( ! isset( $feed['type'] ) ) {
			$feed['type']        = 'ical';
			$feeds[ $i ]['type'] = $feed['type'];
		}

		if ( ! isset( $feed['keep_updated'] ) ) {
			$feed['keep_updated']        = 'rss' === $feed['type'] ? '0' : '1';
			$feeds[ $i ]['keep_updated'] = $feed['keep_updated'];
		}

		$feeds[ $i ]['last_sync'] = array(
			'time'   => calendarp_wall_time(),
			'status' => 'incomplete',
			'events' => array(),
		);

		if ( 'ical' === $feed['type'] ) {
			$source = $feed['source'];

			// if a feed was added with the webcal:// protocol, fetch it over http instead.
			$protocol = 'webcal://';
			if ( substr( $source, 0, strlen( $protocol ) ) === $protocol ) {
				$source = 'http://' . substr( $source, strlen( $protocol ) );
			}

			// fetch the file from the remote URL.
			$file = wp_safe_remote_get(
				$source,
				array(
					'redirection'         => 3,
					'limit_response_size' => 8 * MB_IN_BYTES + 1,
				)
			);

			if ( is_wp_error( $file ) ) {
				$feeds[ $i ]['last_sync']['status'] = 'request_error';
				continue;
			}

			// parse the file contents into event fields.
			$content = wp_remote_retrieve_body( $file );
			if ( 200 !== wp_remote_retrieve_response_code( $file ) || strlen( $content ) > 8 * MB_IN_BYTES ) {
				$feeds[ $i ]['last_sync']['status'] = 'request_error';
				continue;
			}

			// check if past events should be excluded.
			$exclude_past = isset( $feed['exclude_past'] ) ? $feed['exclude_past'] : 0;

			try {
				$ical_parser = new Calendar_Plus_iCal_Parser( $content, true, $exclude_past );
				$events      = $ical_parser->parse();
			} catch ( Exception $e ) {
				$feeds[ $i ]['last_sync']['status'] = 'parse_error';
				continue;
			}
		} elseif ( 'rss' === $feed['type'] ) {

			// use the built-in function to fetch and parse the field data.
			if ( ! function_exists( 'fetch_feed' ) ) {
				include_once ABSPATH . WPINC . '/feed.php';
			}

			add_filter( 'wp_feed_cache_transient_lifetime', '__return_false' );

			$rss = fetch_feed( $feed['source'] );

			remove_filter( 'wp_feed_cache_transient_lifetime', '__return_false' );

			if ( is_wp_error( $rss ) ) {
				$feeds[ $i ]['last_sync']['status'] = 'error';
				continue;
			}

			// loop through the SimplePie data and convert it into a format the synchronisation class will accept.
			$items      = $rss->get_items();
			$events     = array();
			$ns_event   = 'http://purl.org/rss/1.0/modules/event/';
			$ns_cal_rss = 'http://sidearmsports.com/schemas/cal_rss/1.0/';

			/** Calendar render context. @var SimplePie_Item $item */
			foreach ( $items as $item ) {
				$event_data = array(
					'uid'          => $item->get_id(),
					'post_title'   => html_entity_decode( $item->get_title() ),
					'post_content' => html_entity_decode( $item->get_content() ),
				);

				$location = $item->get_item_tags( $ns_event, 'location' );
				if ( $location ) {
					$event_data['location'] = $location[0]['data'];
				}

				$start_local     = $item->get_item_tags( $ns_cal_rss, 'localstartdate' );
				$start_event     = $item->get_item_tags( $ns_event, 'startdate' );
				$start_published = $item->get_item_tags( SIMPLEPIE_NAMESPACE_RSS_20, 'pubDate' );
				$end_local       = $item->get_item_tags( $ns_cal_rss, 'localenddate' );
				$end_event       = $item->get_item_tags( $ns_event, 'enddate' );
				$from            = $start_local ? $start_local : ( $start_event ? $start_event : $start_published );
				$to              = $end_local ? $end_local : $end_event;
				if ( $from ) {
					$event_data['from'] = strtotime( $from[0]['data'] );
				}
				$event_data['to'] = $to ? strtotime( $to[0]['data'] ) : ( $event_data['from'] ?? time() );

				if ( isset( $feeds[ $i ]['exclude_past'] ) && -1 === $feeds[ $i ]['exclude_past'] ) {
					if ( isset( $event_data['to'] ) && $event_data['to'] < time() ) {
						continue;
					} elseif ( isset( $event_data['from'] ) && $event_data['from'] < time() + DAY_IN_SECONDS ) {
						continue;
					}
				}

				$events[] = apply_filters( 'calendarp_rss_import_event_data', $event_data, $item );
			}
		} else {
			$feeds[ $i ]['last_sync']['status'] = 'parse_error';
			continue;
		}

		if ( empty( $events ) ) {
			$feeds[ $i ]['last_sync']['status'] = 'complete_empty';
			continue;
		}

		$syncer        = new Calendar_Plus_iCal_Sync( $events, $feed );
		$synced_events = $syncer->sync();

		$feeds[ $i ]['last_sync']['events'] = $synced_events;
		$feeds[ $i ]['last_sync']['status'] = 'complete';
	}

	update_option( 'calendar_plus_remote_feeds', $feeds );
}

/**
 * Handle calendarp import events.
 *
 * @param mixed $file_content File content.
 * @param bool  $import_recurring Whether to import recurring event instances.
 * @return bool|WP_Error
 */
function calendarp_import_events( $file_content, $import_recurring = false ) {
	try {
		$ical_parser = new Calendar_Plus_iCal_Parser(
			$file_content,
			$import_recurring
		);
		$events      = $ical_parser->parse();

	} catch ( Exception $e ) {
		return new WP_Error( 'ical_import_failed', $e->getMessage() );
	}

	if ( empty( $events ) ) {
		return new WP_Error( 'empty-events', __( 'There are no events in this file', 'calendar-plus' ) );
	}

	$syncer = new Calendar_Plus_iCal_Sync( $events );
	$syncer->sync();
	return true;
}
