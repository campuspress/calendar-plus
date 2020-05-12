<?php

class Calendar_Plus_Event_Details_Metabox extends Calendar_Plus_Meta_Box {

	public function __construct() {
		$this->meta_box_slug = 'calendar-event-details';
		$this->meta_box_label = __( 'Event Details', 'calendar-plus' );
		$this->meta_box_context = 'normal';
		$this->meta_box_priority = 'high';
		$this->post_type = 'calendar_event';
		parent::__construct();

		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
		add_filter( 'admin_body_class', array( $this, 'add_calendar_plus_body_class' ) );
	}

	public function add_calendar_plus_body_class( $class ) {
		$screen = get_current_screen();
		if ( 'calendar_event' === $screen->id ) {
			$class .= ' calendarp';
		}

		return $class;
	}

	public function enqueue_scripts( $hook ) {
		if ( ( 'post.php' === $hook || 'post-new.php' === $hook ) && 'calendar_event' === get_post_type() ) {
			wp_enqueue_script( 'jquery-ui-datepicker' );

			wp_enqueue_style(
				'calendarp-jquery-ui-theme',
				calendarp_get_plugin_url() . 'includes/css/jquery-ui/jquery-ui.min.css',
				[], calendarp_get_version()
			);
		}
	}


	public function render( $post ) {
		$event = calendarp_get_event( $post );
		$meta_box_slug = $this->meta_box_slug;

		$formatted_rules = $event->get_rules();

		$recurring_rules = array();
		$regular_rules = array();

		if ( ! isset( $formatted_rules['dates'] ) ) {
			$regular_rules['dates'] = array( 'from' => '', 'until' => '' );
			$recurring_rules['dates'] = array( 'from' => '', 'until' => '' );
		} else {
			$regular_rules['dates'] = $formatted_rules['dates'][0];
			$recurring_rules['dates'] = $formatted_rules['dates'][0];
		}

		$recurring_rules['dows'] = array(
			'from'  => 1,
			'until' => 7,
		);
		$recurring_rules['every'] = array(
			'every' => 1,
			'what'  => 'day',
			'on'    => 1,
		);

		$frequency_type = false;
		if ( isset( $formatted_rules['dows'] ) ) {
			$frequency_type = 'dows';
		} elseif ( isset( $formatted_rules['every'] ) ) {
			$frequency_type = 'every';
		}

		if ( 'dows' === $frequency_type ) {
			$recurring_rules['dows'] = array();

			current( $formatted_rules['dows'][0] );
			$recurring_rules['dows']['from'] = key( $formatted_rules['dows'][0] );

			end( $formatted_rules['dows'][0] );
			$recurring_rules['dows']['until'] = key( $formatted_rules['dows'][0] );

			reset( $formatted_rules['dows'][0] );

		} elseif ( 'every' === $frequency_type ) {
			$recurring_rules['every'] = $formatted_rules['every'][0];
		}

		if ( isset( $formatted_rules['times'] ) ) {
			$regular_rules['times'] = $recurring_rules['times'] = $formatted_rules['times'][0];
		} else {
			$regular_rules['times'] = array( 'from' => '00:00', 'until' => '00:00' );
			$recurring_rules['times'] = array( 'from' => '00:00', 'until' => '00:00' );
		}

		$standard_rules = isset( $formatted_rules['standard'] ) && is_array( $formatted_rules['standard'] ) ? $formatted_rules['standard'] : array();

		if ( isset( $formatted_rules['datespan'] ) && is_array( $formatted_rules['datespan'] ) ) {
			$datespan_rules = $formatted_rules['datespan'][0];
		} else {
			$datespan_rules = array_fill_keys( array( 'from_date', 'until_date', 'from_time', 'until_time' ), '' );
		}

		$calendar = $event->get_dates_list();
		$exclusions = empty( $formatted_rules['exclusions'] ) ? array() : $formatted_rules['exclusions'];

		include_once calendarp_get_plugin_dir() . 'admin/views/event-details-meta-box.php';
	}

	public function save_data( $event_id ) {

		if ( ! $event = calendarp_get_event( $event_id ) ) {
			return;
		}

		$input = $_POST['event_details'];

		$calendar = calendarp_get_event_cells( $event->ID );
		if ( empty( $input['edit_dates'] ) && ! empty( $calendar ) ) {
			return;
		}

		$args = array(
			'recurrence' => $input['_recurrence'],
		);

		if ( isset( $input['datespan'] ) ) {
			$args['datespan'] = $input['datespan'];
		}

		if ( isset( $input['recurring'] ) ) {
			$args['recurring'] = $input['recurring'];
		}

		if ( ! empty( $input['from_date'] ) && is_array( $input['from_date'] ) ) {
			$args['from_date'] = $input['from_date'];
		}

		if ( isset( $input['all_day_event'] ) ) {
			$args['all_day_event'] = true;
		}

		if ( ! empty( $input['from_time_hour'] ) ) {
			$args['from_time_hour'] = $input['from_time_hour'];
		}

		if ( ! empty( $input['from_time_minute'] ) ) {
			$args['from_time_minute'] = $input['from_time_minute'];
		}

		if ( isset( $input['from_time_am_pm'] ) ) {
			$args['from_time_am_pm'] = $input['from_time_am_pm'];
		}

		if ( ! empty( $input['until_time_hour'] ) ) {
			$args['until_time_hour'] = $input['until_time_hour'];
		}

		if ( ! empty( $input['until_time_minute'] ) ) {
			$args['until_time_minute'] = $input['until_time_minute'];
		}

		if ( isset( $input['until_time_am_pm'] ) ) {
			$args['until_time_am_pm'] = $input['until_time_am_pm'];
		}

		calendar_plus_save_event_details( $event_id, $args );
	}
}

function calendar_plus_save_event_details( $event_id, $args ) {

	$event_id = absint( $event_id );
	if ( ! $event = calendarp_get_event( $event_id ) ) {
		return;
	}

	$defaults = array(
		'recurrence'    => 'regular',
		'recurring'     => array(),
		'datespan'      => array(),
		'from_date'     => array(),
		'all_day_event' => false,
	);

	$args = wp_parse_args( $args, $defaults );

	// Recurrence
	$type = in_array( $args['recurrence'], array( 'recurrent', 'datespan' ) ) ? $args['recurrence'] : '';
	calendarp_update_event_type( $event_id, $type );

	if ( 'recurrent' === $event->get_event_type() ) {
		// Event is recurrent
		$errors = array();

		$recurring_defaults = array(
			'from_date'         => '',
			'until_date'        => '',
			'every'             => '',
			'what'              => '',
			'from_time_hour'    => '',
			'from_time_minute'  => '',
			'until_time_hour'   => '',
			'until_time_minute' => '',
			'all_day_event'     => false,
			'exclusions'        => array(),
		);
		$recurring_input = wp_parse_args( $args['recurring'], $recurring_defaults );

		$all_rules = array();

		// 1. Dates
		$all_rules[] = array(
			'rule_type' => 'dates',
			'from'      => $recurring_input['from_date'],
			'until'     => $recurring_input['until_date'],
		);

		$all_rules[] = array(
			'rule_type' => 'every',
			'every'     => $recurring_input['every'],
			'what'      => $recurring_input['what'],
		);

		// 2. Times
		if ( $recurring_input['all_day_event'] ) {
			update_post_meta( $event_id, '_all_day', true );
			$all_rules[] = array(
				'rule_type' => 'times',
				'from'      => '00:00',
				'until'     => '23:59',
			);
		} else {
			delete_post_meta( $event_id, '_all_day' );

			$from_hours = $recurring_input['from_time_hour'];
			$from_minutes = $recurring_input['from_time_minute'];
			$from_am_pm = isset( $recurring_input['from_time_am_pm'] ) ? $recurring_input['from_time_am_pm'] : false;
			if ( $from_am_pm ) {
				$from_hours = calendarp_am_pm_to_24h( $from_hours, $from_am_pm );
			}

			$until_hours = $recurring_input['until_time_hour'];
			$until_minutes = $recurring_input['until_time_minute'];
			$until_am_pm = isset( $recurring_input['until_time_am_pm'] ) ? $recurring_input['until_time_am_pm'] : false;
			if ( $until_am_pm ) {
				$until_hours = calendarp_am_pm_to_24h( $until_hours, $until_am_pm );
			}

			$all_rules[] = array(
				'rule_type' => 'times',
				'from'      => $from_hours . ':' . $from_minutes,
				'until'     => $until_hours . ':' . $until_minutes,
			);
		}

		if ( ! empty( $recurring_input['exclusions'] ) && is_array( $recurring_input['exclusions'] ) ) {
			foreach ( $recurring_input['exclusions'] as $exclusion ) {
				$all_rules[] = array(
					'rule_type' => 'exclusions',
					'date'      => $exclusion,
				);
			}
		}

		calendarp_generate_event_rules_and_dates( $event_id, $all_rules );

	} elseif ( 'datespan' === $event->get_event_type() ) {
		$datespan_defaults = array(
			'from_date'         => '',
			'until_date'        => '',
			'from_time_hour'    => '',
			'from_time_minute'  => '',
			'until_time_hour'   => '',
			'until_time_minute' => '',
			'all_day_event'     => false,
		);
		$datespan_input = wp_parse_args( $args['datespan'], $datespan_defaults );
		$all_rules = array();

		if ( $datespan_input['all_day_event'] ) {
			update_post_meta( $event_id, '_all_day', true );
			$all_rules[] = array(
				'rule_type'  => 'datespan',
				'from_date'  => $datespan_input['from_date'],
				'until_date' => $datespan_input['until_date'],
				'from_time'  => '00:00',
				'until_time' => '23:59',
			);
		} else {
			delete_post_meta( $event_id, '_all_day' );

			$from_hours = $datespan_input['from_time_hour'];
			$from_minutes = $datespan_input['from_time_minute'];
			$from_am_pm = isset( $datespan_input['from_time_am_pm'] ) ? $datespan_input['from_time_am_pm'] : false;
			if ( $from_am_pm ) {
				$from_hours = calendarp_am_pm_to_24h( $from_hours, $from_am_pm );
			}

			$until_hours = $datespan_input['until_time_hour'];
			$until_minutes = $datespan_input['until_time_minute'];
			$until_am_pm = isset( $datespan_input['until_time_am_pm'] ) ? $datespan_input['until_time_am_pm'] : false;
			if ( $until_am_pm ) {
				$until_hours = calendarp_am_pm_to_24h( $until_hours, $until_am_pm );
			}

			$all_rules[] = array(
				'rule_type'  => 'datespan',
				'from_date'  => $datespan_input['from_date'],
				'until_date' => $datespan_input['until_date'],
				'from_time'  => $from_hours . ':' . $from_minutes,
				'until_time' => $until_hours . ':' . $until_minutes,
			);
		}

		calendarp_generate_event_rules_and_dates( $event_id, $all_rules );
	} else {
		// Regular event
		if ( ! empty( $args['from_date'] ) && is_array( $args['from_date'] ) ) {

			if ( $args['all_day_event'] ) {
				update_post_meta( $event_id, '_all_day', true );
			} else {
				delete_post_meta( $event_id, '_all_day' );
			}

			$all_rules = array();
			foreach ( $args['from_date'] as $key => $from_date ) {
				if ( empty( $args['from_time_hour'][ $key ] ) || empty( $args['until_time_hour'][ $key ] ) || empty( $from_date ) ) {
					continue;
				}

				if ( $event->is_all_day_event() ) {
					$all_rules[] = array(
						'rule_type'  => 'standard',
						'from_date'  => $args['from_date'][ $key ],
						'until_date' => $args['from_date'][ $key ],
						'from_time'  => '00:00',
						'until_time' => '23:59',
					);
				} else {
					$from_hours = $args['from_time_hour'][ $key ];
					$from_minutes = $args['from_time_minute'][ $key ];
					$from_am_pm = isset( $args['from_time_am_pm'][ $key ] ) ? $args['from_time_am_pm'][ $key ] : false;
					if ( $from_am_pm ) {
						$from_hours = calendarp_am_pm_to_24h( $from_hours, $from_am_pm );
					}

					$until_hours = $args['until_time_hour'][ $key ];
					$until_minutes = $args['until_time_minute'][ $key ];
					$until_am_pm = isset( $args['until_time_am_pm'][ $key ] ) ? $args['until_time_am_pm'][ $key ] : false;
					if ( $until_am_pm ) {
						$until_hours = calendarp_am_pm_to_24h( $until_hours, $until_am_pm );
					}

					$all_rules[] = array(
						'rule_type'  => 'standard',
						'from_date'  => $args['from_date'][ $key ],
						'until_date' => $args['from_date'][ $key ],
						'from_time'  => $from_hours . ':' . $from_minutes,
						'until_time' => $until_hours . ':' . $until_minutes,
					);
				}
			}
		}

		calendarp_generate_event_rules_and_dates( $event_id, $all_rules );
	}
}

