<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function calendarp_is_ical_sync_cron_scheduled() {
	return false !== wp_next_scheduled( 'calendar_plus_sync_ical_events' );
}

function calendarp_schedule_ical_sync_cron( $recurrence, $args = array() ) {
	calendarp_unschedule_ical_sync_cron();

	wp_schedule_event( time(), $recurrence, 'calendar_plus_sync_ical_events', $args );
}

function calendarp_unschedule_ical_sync_cron() {
	if ( wp_next_scheduled( 'calendar_plus_sync_ical_events' ) ) {
		wp_clear_scheduled_hook( 'calendar_plus_sync_ical_events' );
	}
}

/**
 * Clear legacy scheduled events - i.e. the ones with arguments
 *
 * @param array $args Arguments for scheduled cron event.
 */
function calendarp_unschedule_ical_legacy_sync_cron( $args ) {
	if ( wp_next_scheduled( 'calendar_plus_sync_ical_events', $args ) ) {
		wp_clear_scheduled_hook( 'calendar_plus_sync_ical_events', $args );
	}
}

/**
 * Clears *all* cron schedules for remote feeds sync
 *
 * @return bool
 */
function calendarp_unschedule_all_ical_sync_crons() {
	return false !== wp_unschedule_hook( 'calendar_plus_sync_ical_events' );
}

/**
 * Clears legacy scheduling cron hook handler
 *
 * Boots up early and takes care of any events scheduled with arguments.
 *
 * @param array $args Optional args - only set for legacy events.
 */
function calendarp_ical_clear_legacy_scheduling( $args = [] ) {
	if ( empty( $args ) ) {
		// Nothing to do.
		return false;
	}
	$args = func_get_args();
	calendarp_unschedule_ical_legacy_sync_cron( $args );
	return true;
}

function calendarp_ical_sync_events() {
	$feeds = get_option( 'calendar_plus_remote_feeds' );

	if ( false === $feeds && false !== ( $feeds = get_option( 'calendar_plus_ical_feeds', false ) ) ) {
		delete_option( 'calendar_plus_ical_feeds' );
		add_option( 'calendar_plus_remote_feeds', $feeds );
	}

	if ( empty( $feeds ) ) {
		return calendarp_unschedule_all_ical_sync_crons();
	}

	foreach ( $feeds as $i => $feed ) {

		if ( ! isset( $feed['type'] ) ) {
			$feed['type'] = $feeds[ $i ]['type'] = 'ical';
		}

		if ( isset( $feeds[ $i ]['last_sync'] ) ) {
			$feeds[ $i ]['prev_sync_time'] = $feeds[ $i ]['last_sync']['time'];
		}

		$feeds[ $i ]['last_sync'] = array(
			'time'   => current_time( 'timestamp' ),
			'status' => 'incomplete',
			'events' => array(),
		);

		if ( 'ical' === $feed['type'] ) {
			$source = $feed['source'];

			// if a feed was added with the webcal:// protocol, fetch it over http instead
			$protocol = 'webcal://';
			if ( substr( $source, 0, strlen( $protocol ) ) === $protocol ) {
				$source = 'http://' . substr( $source, strlen( $protocol ) );
			}

			// fetch the file from the remote URL
			$file = wp_remote_get( $source );

			if ( is_wp_error( $file ) ) {
				$feeds[ $i ]['last_sync']['status'] = 'request_error';
				continue;
			}

			// parse the file contents into event fields
			$content = $file['body'];

			try {
				$ical_parser = new Calendar_Plus_iCal_Parser( $content );
				$events = $ical_parser->parse();
			} catch ( Exception $e ) {
				$feeds[ $i ]['last_sync']['status'] = 'parse_error';
				continue;
			}

		} elseif ( 'rss' === $feed['type'] ) {

			// use the built-in function to fetch and parse the field data
			if ( ! function_exists( 'fetch_feed' ) ) {
				include_once ABSPATH . WPINC . '/feed.php';
			}

			$rss = fetch_feed( $feed['source'] );

			if ( is_wp_error( $rss ) ) {
				$feeds[ $i ]['last_sync']['status'] = 'error';
				continue;
			}

			// loop through the SimplePie data and convert it into a format the synchronisation class will accept
			$items = $rss->get_items();
			$events = [];
			$ns_event = 'http://purl.org/rss/1.0/modules/event/';
			$ns_cal_rss = 'http://sidearmsports.com/schemas/cal_rss/1.0/';

			/** @var SimplePie_Item $item */
			foreach ( $items as $item ) {
				$event_data = [
					'uid'          => $item->get_id(),
					'post_title'   => html_entity_decode( $item->get_title() ),
					'post_content' => html_entity_decode( $item->get_content() ),
				];

				if ( $location = $item->get_item_tags( $ns_event, 'location' ) ) {
					$event_data['location'] = $location[0]['data'];
				}

				if ( $from = $item->get_item_tags( $ns_cal_rss, 'localstartdate' ) ) {
					$event_data['from'] = strtotime( $from[0]['data'] );
				}
				else {
					if ( $from = $item->get_item_tags( $ns_event, 'startdate' ) ) {
						$event_data['from'] = strtotime( $from[0]['data'] );
					}
				}

				if ( $to = $item->get_item_tags( $ns_cal_rss, 'localenddate' ) ) {
					$event_data['to'] = strtotime( $to[0]['data'] );
				}
				else {
					if ( $to = $item->get_item_tags( $ns_event, 'enddate' ) ) {
						$event_data['to'] = strtotime( $to[0]['data'] );
					}
				}
				$events[] = $event_data;
			}

		} else {
			$feeds[ $i ]['last_sync']['status'] = 'parse_error';
			continue;
		}

		if ( empty( $events ) ) {
			$feeds[ $i ]['last_sync']['status'] = 'complete_empty';
			continue;
		}

		$syncer = new Calendar_Plus_iCal_Sync( $events, $feed );
		$synced_events = $syncer->sync();

		$feeds[ $i ]['last_sync']['events'] = $synced_events;
		$feeds[ $i ]['last_sync']['status'] = 'complete';
	}

	update_option( 'calendar_plus_remote_feeds', $feeds );
}

/**
 * @param $file_content
 * @param bool $import_recurring Whether to import recurring event instances.
 *
 * @return bool|WP_Error
 */
function calendarp_import_events( $file_content, $import_recurring = false ) {
	try {
		$ical_parser = new Calendar_Plus_iCal_Parser(
			$file_content,
			$import_recurring
		);
		$events = $ical_parser->parse();

	} catch ( Exception $e ) {
		return new WP_Error( $e->getCode(), $e->getMessage() );
	}

	if ( empty( $events ) ) {
		return new WP_Error( 'empty-events', __( 'There are no events in this file', 'calendarp' ) );
	}

	$syncer = new Calendar_Plus_iCal_Sync( $events );
	$syncer->sync();
	return true;
}
