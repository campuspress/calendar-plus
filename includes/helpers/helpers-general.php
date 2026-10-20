<?php
/**
 * Calendar Plus: includes/helpers/helpers-general.php.
 *
 * @package Calendar_Plus
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


/**
 * Retrieve countries dropdown markup.
 *
 * @param array $args {.
 * @type string $selected Selected country
 * @type string $show_empty Display an option with empty value
 * @type string $name select tag name
 * @type string $id select tag id
 * @type string $class select tag class
 * }
 *
 * @return mixed Calendar operation result.
 */
function calendarp_countries_dropdown( $args ) { // phpcs:ignore Universal.NamingConventions.NoReservedKeywordParameterNames.defaultFound -- Preserve this existing public API or native callback signature; compatibility includes parameter names and the historical class/property name.
	$defaults = array(
		'selected'   => '',
		'show_empty' => true,
		'name'       => 'countries',
		'id'         => false,
		'class'      => '',
	);

	$args = wp_parse_args( $args, $defaults );

	if ( ! $args['id'] ) {
		$args['id'] = $args['name'];
	}

	if ( ! $args['selected'] ) {
		$settings         = calendarp_get_settings();
		$args['selected'] = $settings['country'];
	}

	$countries = calendarp_get_countries();

	?>
	<select name="<?php echo esc_attr( $args['name'] ); ?>" id="<?php echo esc_attr( $args['id'] ); ?>" class="<?php echo esc_attr( $args['class'] ); ?>">
		<?php if ( $args['show_empty'] ) : ?>
			<option value="" <?php selected( empty( $args['selected'] ) ); ?>><?php esc_html_e( '-- Select a country --', 'calendar-plus' ); ?></option>
		<?php endif; ?>

		<?php foreach ( $countries as $country_key => $country ) : ?>
			<option value="<?php echo esc_attr( $country_key ); ?>" <?php selected( $country_key === $args['selected'] ); ?>><?php echo esc_html( $country ); ?></option>
		<?php endforeach; ?>
	</select>
	<?php
}

/**
 * Return a list of available countries.
 *
 * @return array
 */
function calendarp_get_countries() {
	return array(
		'AF' => __( 'Afghanistan', 'calendar-plus' ),
		'AL' => __( 'Albania', 'calendar-plus' ),
		'DZ' => __( 'Algeria', 'calendar-plus' ),
		'AS' => __( 'American Samoa', 'calendar-plus' ),
		'AD' => __( 'Andorra', 'calendar-plus' ),
		'AO' => __( 'Angola', 'calendar-plus' ),
		'AI' => __( 'Anguilla', 'calendar-plus' ),
		'AQ' => __( 'Antarctica', 'calendar-plus' ),
		'AG' => __( 'Antigua and Barbuda', 'calendar-plus' ),
		'AR' => __( 'Argentina', 'calendar-plus' ),
		'AM' => __( 'Armenia', 'calendar-plus' ),
		'AW' => __( 'Aruba', 'calendar-plus' ),
		'AU' => __( 'Australia', 'calendar-plus' ),
		'AT' => __( 'Austria', 'calendar-plus' ),
		'AZ' => __( 'Azerbaijan', 'calendar-plus' ),
		'BS' => __( 'Bahamas', 'calendar-plus' ),
		'BH' => __( 'Bahrain', 'calendar-plus' ),
		'BD' => __( 'Bangladesh', 'calendar-plus' ),
		'BB' => __( 'Barbados', 'calendar-plus' ),
		'BY' => __( 'Belarus', 'calendar-plus' ),
		'BE' => __( 'Belgium', 'calendar-plus' ),
		'BZ' => __( 'Belize', 'calendar-plus' ),
		'BJ' => __( 'Benin', 'calendar-plus' ),
		'BM' => __( 'Bermuda', 'calendar-plus' ),
		'BT' => __( 'Bhutan', 'calendar-plus' ),
		'BO' => __( 'Bolivia', 'calendar-plus' ),
		'BA' => __( 'Bosnia and Herzegovina', 'calendar-plus' ),
		'BW' => __( 'Botswana', 'calendar-plus' ),
		'BV' => __( 'Bouvet Island', 'calendar-plus' ),
		'BR' => __( 'Brazil', 'calendar-plus' ),
		'BQ' => __( 'British Antarctic Territory', 'calendar-plus' ),
		'IO' => __( 'British Indian Ocean Territory', 'calendar-plus' ),
		'VG' => __( 'British Virgin Islands', 'calendar-plus' ),
		'BN' => __( 'Brunei', 'calendar-plus' ),
		'BG' => __( 'Bulgaria', 'calendar-plus' ),
		'BF' => __( 'Burkina Faso', 'calendar-plus' ),
		'BI' => __( 'Burundi', 'calendar-plus' ),
		'KH' => __( 'Cambodia', 'calendar-plus' ),
		'CM' => __( 'Cameroon', 'calendar-plus' ),
		'CA' => __( 'Canada', 'calendar-plus' ),
		'CT' => __( 'Canton and Enderbury Islands', 'calendar-plus' ),
		'CV' => __( 'Cape Verde', 'calendar-plus' ),
		'KY' => __( 'Cayman Islands', 'calendar-plus' ),
		'CF' => __( 'Central African Republic', 'calendar-plus' ),
		'TD' => __( 'Chad', 'calendar-plus' ),
		'CL' => __( 'Chile', 'calendar-plus' ),
		'CN' => __( 'China', 'calendar-plus' ),
		'CX' => __( 'Christmas Island', 'calendar-plus' ),
		'CC' => __( 'Cocos [Keeling] Islands', 'calendar-plus' ),
		'CO' => __( 'Colombia', 'calendar-plus' ),
		'KM' => __( 'Comoros', 'calendar-plus' ),
		'CG' => __( 'Congo - Brazzaville', 'calendar-plus' ),
		'CD' => __( 'Congo - Kinshasa', 'calendar-plus' ),
		'CK' => __( 'Cook Islands', 'calendar-plus' ),
		'CR' => __( 'Costa Rica', 'calendar-plus' ),
		'HR' => __( 'Croatia', 'calendar-plus' ),
		'CU' => __( 'Cuba', 'calendar-plus' ),
		'CY' => __( 'Cyprus', 'calendar-plus' ),
		'CZ' => __( 'Czech Republic', 'calendar-plus' ),
		'CI' => __( 'Côte d’Ivoire', 'calendar-plus' ),
		'DK' => __( 'Denmark', 'calendar-plus' ),
		'DJ' => __( 'Djibouti', 'calendar-plus' ),
		'DM' => __( 'Dominica', 'calendar-plus' ),
		'DO' => __( 'Dominican Republic', 'calendar-plus' ),
		'NQ' => __( 'Dronning Maud Land', 'calendar-plus' ),
		'DD' => __( 'East Germany', 'calendar-plus' ),
		'EC' => __( 'Ecuador', 'calendar-plus' ),
		'EG' => __( 'Egypt', 'calendar-plus' ),
		'SV' => __( 'El Salvador', 'calendar-plus' ),
		'GQ' => __( 'Equatorial Guinea', 'calendar-plus' ),
		'ER' => __( 'Eritrea', 'calendar-plus' ),
		'EE' => __( 'Estonia', 'calendar-plus' ),
		'ET' => __( 'Ethiopia', 'calendar-plus' ),
		'FK' => __( 'Falkland Islands', 'calendar-plus' ),
		'FO' => __( 'Faroe Islands', 'calendar-plus' ),
		'FJ' => __( 'Fiji', 'calendar-plus' ),
		'FI' => __( 'Finland', 'calendar-plus' ),
		'FR' => __( 'France', 'calendar-plus' ),
		'GF' => __( 'French Guiana', 'calendar-plus' ),
		'PF' => __( 'French Polynesia', 'calendar-plus' ),
		'TF' => __( 'French Southern Territories', 'calendar-plus' ),
		'FQ' => __( 'French Southern and Antarctic Territories', 'calendar-plus' ),
		'GA' => __( 'Gabon', 'calendar-plus' ),
		'GM' => __( 'Gambia', 'calendar-plus' ),
		'GE' => __( 'Georgia', 'calendar-plus' ),
		'DE' => __( 'Germany', 'calendar-plus' ),
		'GH' => __( 'Ghana', 'calendar-plus' ),
		'GI' => __( 'Gibraltar', 'calendar-plus' ),
		'GR' => __( 'Greece', 'calendar-plus' ),
		'GL' => __( 'Greenland', 'calendar-plus' ),
		'GD' => __( 'Grenada', 'calendar-plus' ),
		'GP' => __( 'Guadeloupe', 'calendar-plus' ),
		'GU' => __( 'Guam', 'calendar-plus' ),
		'GT' => __( 'Guatemala', 'calendar-plus' ),
		'GG' => __( 'Guernsey', 'calendar-plus' ),
		'GN' => __( 'Guinea', 'calendar-plus' ),
		'GW' => __( 'Guinea-Bissau', 'calendar-plus' ),
		'GY' => __( 'Guyana', 'calendar-plus' ),
		'HT' => __( 'Haiti', 'calendar-plus' ),
		'HM' => __( 'Heard Island and McDonald Islands', 'calendar-plus' ),
		'HN' => __( 'Honduras', 'calendar-plus' ),
		'HK' => __( 'Hong Kong SAR China', 'calendar-plus' ),
		'HU' => __( 'Hungary', 'calendar-plus' ),
		'IS' => __( 'Iceland', 'calendar-plus' ),
		'IN' => __( 'India', 'calendar-plus' ),
		'ID' => __( 'Indonesia', 'calendar-plus' ),
		'IR' => __( 'Iran', 'calendar-plus' ),
		'IQ' => __( 'Iraq', 'calendar-plus' ),
		'IE' => __( 'Ireland', 'calendar-plus' ),
		'IM' => __( 'Isle of Man', 'calendar-plus' ),
		'IL' => __( 'Israel', 'calendar-plus' ),
		'IT' => __( 'Italy', 'calendar-plus' ),
		'JM' => __( 'Jamaica', 'calendar-plus' ),
		'JP' => __( 'Japan', 'calendar-plus' ),
		'JE' => __( 'Jersey', 'calendar-plus' ),
		'JT' => __( 'Johnston Island', 'calendar-plus' ),
		'JO' => __( 'Jordan', 'calendar-plus' ),
		'KZ' => __( 'Kazakhstan', 'calendar-plus' ),
		'KE' => __( 'Kenya', 'calendar-plus' ),
		'KI' => __( 'Kiribati', 'calendar-plus' ),
		'KW' => __( 'Kuwait', 'calendar-plus' ),
		'KG' => __( 'Kyrgyzstan', 'calendar-plus' ),
		'LA' => __( 'Laos', 'calendar-plus' ),
		'LV' => __( 'Latvia', 'calendar-plus' ),
		'LB' => __( 'Lebanon', 'calendar-plus' ),
		'LS' => __( 'Lesotho', 'calendar-plus' ),
		'LR' => __( 'Liberia', 'calendar-plus' ),
		'LY' => __( 'Libya', 'calendar-plus' ),
		'LI' => __( 'Liechtenstein', 'calendar-plus' ),
		'LT' => __( 'Lithuania', 'calendar-plus' ),
		'LU' => __( 'Luxembourg', 'calendar-plus' ),
		'MO' => __( 'Macau SAR China', 'calendar-plus' ),
		'MK' => __( 'Macedonia', 'calendar-plus' ),
		'MG' => __( 'Madagascar', 'calendar-plus' ),
		'MW' => __( 'Malawi', 'calendar-plus' ),
		'MY' => __( 'Malaysia', 'calendar-plus' ),
		'MV' => __( 'Maldives', 'calendar-plus' ),
		'ML' => __( 'Mali', 'calendar-plus' ),
		'MT' => __( 'Malta', 'calendar-plus' ),
		'MH' => __( 'Marshall Islands', 'calendar-plus' ),
		'MQ' => __( 'Martinique', 'calendar-plus' ),
		'MR' => __( 'Mauritania', 'calendar-plus' ),
		'MU' => __( 'Mauritius', 'calendar-plus' ),
		'YT' => __( 'Mayotte', 'calendar-plus' ),
		'FX' => __( 'Metropolitan France', 'calendar-plus' ),
		'MX' => __( 'Mexico', 'calendar-plus' ),
		'FM' => __( 'Micronesia', 'calendar-plus' ),
		'MI' => __( 'Midway Islands', 'calendar-plus' ),
		'MD' => __( 'Moldova', 'calendar-plus' ),
		'MC' => __( 'Monaco', 'calendar-plus' ),
		'MN' => __( 'Mongolia', 'calendar-plus' ),
		'ME' => __( 'Montenegro', 'calendar-plus' ),
		'MS' => __( 'Montserrat', 'calendar-plus' ),
		'MA' => __( 'Morocco', 'calendar-plus' ),
		'MZ' => __( 'Mozambique', 'calendar-plus' ),
		'MM' => __( 'Myanmar [Burma]', 'calendar-plus' ),
		'NA' => __( 'Namibia', 'calendar-plus' ),
		'NR' => __( 'Nauru', 'calendar-plus' ),
		'NP' => __( 'Nepal', 'calendar-plus' ),
		'NL' => __( 'Netherlands', 'calendar-plus' ),
		'AN' => __( 'Netherlands Antilles', 'calendar-plus' ),
		'NT' => __( 'Neutral Zone', 'calendar-plus' ),
		'NC' => __( 'New Caledonia', 'calendar-plus' ),
		'NZ' => __( 'New Zealand', 'calendar-plus' ),
		'NI' => __( 'Nicaragua', 'calendar-plus' ),
		'NE' => __( 'Niger', 'calendar-plus' ),
		'NG' => __( 'Nigeria', 'calendar-plus' ),
		'NU' => __( 'Niue', 'calendar-plus' ),
		'NF' => __( 'Norfolk Island', 'calendar-plus' ),
		'KP' => __( 'North Korea', 'calendar-plus' ),
		'VD' => __( 'North Vietnam', 'calendar-plus' ),
		'MP' => __( 'Northern Mariana Islands', 'calendar-plus' ),
		'NO' => __( 'Norway', 'calendar-plus' ),
		'OM' => __( 'Oman', 'calendar-plus' ),
		'PC' => __( 'Pacific Islands Trust Territory', 'calendar-plus' ),
		'PK' => __( 'Pakistan', 'calendar-plus' ),
		'PW' => __( 'Palau', 'calendar-plus' ),
		'PS' => __( 'Palestinian Territories', 'calendar-plus' ),
		'PA' => __( 'Panama', 'calendar-plus' ),
		'PZ' => __( 'Panama Canal Zone', 'calendar-plus' ),
		'PG' => __( 'Papua New Guinea', 'calendar-plus' ),
		'PY' => __( 'Paraguay', 'calendar-plus' ),
		'YD' => __( "People's Democratic Republic of Yemen", 'calendar-plus' ),
		'PE' => __( 'Peru', 'calendar-plus' ),
		'PH' => __( 'Philippines', 'calendar-plus' ),
		'PN' => __( 'Pitcairn Islands', 'calendar-plus' ),
		'PL' => __( 'Poland', 'calendar-plus' ),
		'PT' => __( 'Portugal', 'calendar-plus' ),
		'PR' => __( 'Puerto Rico', 'calendar-plus' ),
		'QA' => __( 'Qatar', 'calendar-plus' ),
		'RO' => __( 'Romania', 'calendar-plus' ),
		'RU' => __( 'Russia', 'calendar-plus' ),
		'RW' => __( 'Rwanda', 'calendar-plus' ),
		'RE' => __( 'Réunion', 'calendar-plus' ),
		'BL' => __( 'Saint Barthélemy', 'calendar-plus' ),
		'SH' => __( 'Saint Helena', 'calendar-plus' ),
		'KN' => __( 'Saint Kitts and Nevis', 'calendar-plus' ),
		'LC' => __( 'Saint Lucia', 'calendar-plus' ),
		'MF' => __( 'Saint Martin', 'calendar-plus' ),
		'PM' => __( 'Saint Pierre and Miquelon', 'calendar-plus' ),
		'VC' => __( 'Saint Vincent and the Grenadines', 'calendar-plus' ),
		'WS' => __( 'Samoa', 'calendar-plus' ),
		'SM' => __( 'San Marino', 'calendar-plus' ),
		'SA' => __( 'Saudi Arabia', 'calendar-plus' ),
		'SN' => __( 'Senegal', 'calendar-plus' ),
		'RS' => __( 'Serbia', 'calendar-plus' ),
		'CS' => __( 'Serbia and Montenegro', 'calendar-plus' ),
		'SC' => __( 'Seychelles', 'calendar-plus' ),
		'SL' => __( 'Sierra Leone', 'calendar-plus' ),
		'SG' => __( 'Singapore', 'calendar-plus' ),
		'SK' => __( 'Slovakia', 'calendar-plus' ),
		'SI' => __( 'Slovenia', 'calendar-plus' ),
		'SB' => __( 'Solomon Islands', 'calendar-plus' ),
		'SO' => __( 'Somalia', 'calendar-plus' ),
		'ZA' => __( 'South Africa', 'calendar-plus' ),
		'GS' => __( 'South Georgia and the South Sandwich Islands', 'calendar-plus' ),
		'KR' => __( 'South Korea', 'calendar-plus' ),
		'ES' => __( 'Spain', 'calendar-plus' ),
		'LK' => __( 'Sri Lanka', 'calendar-plus' ),
		'SD' => __( 'Sudan', 'calendar-plus' ),
		'SR' => __( 'Suriname', 'calendar-plus' ),
		'SJ' => __( 'Svalbard and Jan Mayen', 'calendar-plus' ),
		'SZ' => __( 'Swaziland', 'calendar-plus' ),
		'SE' => __( 'Sweden', 'calendar-plus' ),
		'CH' => __( 'Switzerland', 'calendar-plus' ),
		'SY' => __( 'Syria', 'calendar-plus' ),
		'ST' => __( 'São Tomé and Príncipe', 'calendar-plus' ),
		'TW' => __( 'Taiwan', 'calendar-plus' ),
		'TJ' => __( 'Tajikistan', 'calendar-plus' ),
		'TZ' => __( 'Tanzania', 'calendar-plus' ),
		'TH' => __( 'Thailand', 'calendar-plus' ),
		'TL' => __( 'Timor-Leste', 'calendar-plus' ),
		'TG' => __( 'Togo', 'calendar-plus' ),
		'TK' => __( 'Tokelau', 'calendar-plus' ),
		'TO' => __( 'Tonga', 'calendar-plus' ),
		'TT' => __( 'Trinidad and Tobago', 'calendar-plus' ),
		'TN' => __( 'Tunisia', 'calendar-plus' ),
		'TR' => __( 'Turkey', 'calendar-plus' ),
		'TM' => __( 'Turkmenistan', 'calendar-plus' ),
		'TC' => __( 'Turks and Caicos Islands', 'calendar-plus' ),
		'TV' => __( 'Tuvalu', 'calendar-plus' ),
		'UM' => __( 'U.S. Minor Outlying Islands', 'calendar-plus' ),
		'PU' => __( 'U.S. Miscellaneous Pacific Islands', 'calendar-plus' ),
		'VI' => __( 'U.S. Virgin Islands', 'calendar-plus' ),
		'UG' => __( 'Uganda', 'calendar-plus' ),
		'UA' => __( 'Ukraine', 'calendar-plus' ),
		'SU' => __( 'Union of Soviet Socialist Republics', 'calendar-plus' ),
		'AE' => __( 'United Arab Emirates', 'calendar-plus' ),
		'GB' => __( 'United Kingdom', 'calendar-plus' ),
		'US' => __( 'United States', 'calendar-plus' ),
		'ZZ' => __( 'Unknown || Invalid Region', 'calendar-plus' ),
		'UY' => __( 'Uruguay', 'calendar-plus' ),
		'UZ' => __( 'Uzbekistan', 'calendar-plus' ),
		'VU' => __( 'Vanuatu', 'calendar-plus' ),
		'VA' => __( 'Vatican City', 'calendar-plus' ),
		'VE' => __( 'Venezuela', 'calendar-plus' ),
		'VN' => __( 'Vietnam', 'calendar-plus' ),
		'WK' => __( 'Wake Island', 'calendar-plus' ),
		'WF' => __( 'Wallis and Futuna', 'calendar-plus' ),
		'EH' => __( 'Western Sahara', 'calendar-plus' ),
		'YE' => __( 'Yemen', 'calendar-plus' ),
		'ZM' => __( 'Zambia', 'calendar-plus' ),
		'ZW' => __( 'Zimbabwe', 'calendar-plus' ),
		'AX' => __( 'Åland Islands', 'calendar-plus' ),
	);
}

/**
 * Return the default events per page parameter.
 *
 * @return int
 */
function calendarp_get_events_per_page() {
	return apply_filters( 'calendarp_events_per_page', 10 );
}

/**
 * Handle calendarp hours selector.
 *
 * @param array $args Args.
 *
 * @return mixed Calendar operation result.
 */
function calendarp_hours_selector( $args = array() ) {
	$defaults = array(
		'selected' => '00',
		'echo'     => true,
		'name'     => 'hour',
		'id'       => false,
	);

	$args     = wp_parse_args( $args, $defaults );
	$selected = $args['selected'];
	$echo     = $args['echo'];
	$name     = $args['name'];
	$id       = $args['id'];

	if ( ! $id ) {
		$id = $name;
	}

	if ( ! $echo ) {
		ob_start();
	}

	$time_format = calendarp_get_setting( 'time_format' );
	?>

	<select class="calendarp-time-selector calendarp-hours-selector" name="<?php echo esc_attr( $name ); ?>" id="<?php echo esc_attr( $id ); ?>">
		<?php if ( '24h' === $time_format ) : ?>
			<?php for ( $i = 0; $i <= 24; $i++ ) : ?>
				<?php $hour = str_pad( $i, 2, '0', STR_PAD_LEFT ); ?>
				<option value="<?php echo esc_attr( $hour ); ?>" <?php selected( $hour, $selected ); ?>><?php echo esc_html( $hour ); ?></option>
			<?php endfor; ?>
		<?php else : ?>
			<?php for ( $i = 1; $i <= 12; $i++ ) : ?>
				<?php $hour = str_pad( $i, 2, '0', STR_PAD_LEFT ); ?>
				<option value="<?php echo esc_attr( $hour ); ?>" <?php selected( $hour, $selected ); ?>><?php echo absint( $hour ); ?></option>
			<?php endfor; ?>
		<?php endif; ?>
	</select>
	<?php

	if ( ! $echo ) {
		return ob_get_clean();
	}
}

/**
 * Handle calendarp minutes selector.
 *
 * @param array $args Args.
 *
 * @return mixed Calendar operation result.
 */
function calendarp_minutes_selector( $args = array() ) {
	$defaults = array(
		'selected' => '00',
		'echo'     => true,
		'name'     => 'minute',
		'id'       => false,
	);

	$args         = wp_parse_args( $args, $defaults );
		$selected = $args['selected'];
	$echo         = $args['echo'];
	$name         = $args['name'];
	$id           = $args['id'];

	if ( ! $id ) {
		$id = $name;
	}

	if ( ! $echo ) {
		ob_start();
	}
	?>

	<select class="calendarp-time-selector calendarp-minutes-selector" name="<?php echo esc_attr( $name ); ?>" id="<?php echo esc_attr( $id ); ?>">
		<?php for ( $i = 0; $i < 60; $i = $i + 5 ) : ?>
			<?php $minute = str_pad( $i, 2, '0', STR_PAD_LEFT ); ?>
			<option value="<?php echo esc_attr( $minute ); ?>" <?php selected( $minute, $selected ); ?>><?php echo esc_html( $minute ); ?></option>
		<?php endfor; ?>
	</select>
	<?php

	if ( ! $echo ) {
		return ob_get_clean();
	}
}

/**
 * Handle calendarp am pm selector.
 *
 * @param array $args Args.
 *
 * @return mixed Calendar operation result.
 */
function calendarp_am_pm_selector( $args = array() ) {
	$defaults = array(
		'selected' => 'AM',
		'echo'     => true,
		'name'     => 'am_pm',
		'id'       => false,
	);

	$args         = wp_parse_args( $args, $defaults );
		$selected = $args['selected'];
	$echo         = $args['echo'];
	$name         = $args['name'];
	$id           = $args['id'];

	if ( ! $id ) {
		$id = $name;
	}

	if ( ! $echo ) {
		ob_start();
	}
	?>

	<select class="calendarp-time-selector calendarp-am-pm-selector" name="<?php echo esc_attr( $name ); ?>" id="<?php echo esc_attr( $id ); ?>">
		<option value="am" <?php selected( 'am', $selected ); ?>>
			AM
		</option>
		<option value="pm" <?php selected( 'pm', $selected ); ?>>
			PM
		</option>
	</select>
	<?php

	if ( ! $echo ) {
		return ob_get_clean();
	}
}

/**
 * Renders a time selector.
 *
 * @param array $args Args.
 * @return [type]        [description]
 */
function calendarp_time_selector( $args = array() ) {
	$defaults = array(
		'selected'     => '00:00',
		'echo'         => true,
		'hours_name'   => 'hour',
		'hours_id'     => false,
		'minutes_name' => 'minute',
		'minutes_id'   => false,
		'am_pm_name'   => 'am_pm',
		'am_pm_id'     => false,
	);

	$args         = wp_parse_args( $args, $defaults );
		$selected = $args['selected'];
	$echo         = $args['echo'];
	$hours_name   = $args['hours_name'];
	$hours_id     = $args['hours_id'];
	$minutes_name = $args['minutes_name'];
	$minutes_id   = $args['minutes_id'];
	$am_pm_name   = $args['am_pm_name'];
	$am_pm_id     = $args['am_pm_id'];

	$time_format = calendarp_get_setting( 'time_format' );

	$selected = explode( ':', empty( $args['selected'] ) ? '00:00' : $args['selected'] );
	$hours    = $selected[0];
	$minutes  = $selected[1];

	if ( ! $args['echo'] ) {
		ob_start();
	}

	if ( 'AM/PM' === $time_format ) {
		$hours = absint( $hours );
		$am_pm = 'am';
		if ( 0 === $hours ) {
			$hours = '12';
		} elseif ( 12 === $hours ) {
			$hours = (string) $hours;
			$am_pm = 'pm';
		} elseif ( $hours > 12 ) {
			$hours = (string) ( $hours - 12 );
			$am_pm = 'pm';
		}
		$hours = str_pad( $hours, 2, '0', STR_PAD_LEFT );

		calendarp_hours_selector(
			array(
				'selected' => $hours,
				'echo'     => true,
				'name'     => $hours_name,
				'id'       => $hours_id,
			)
		);
		echo '<span>:</span>';
		calendarp_minutes_selector(
			array(
				'selected' => $minutes,
				'echo'     => true,
				'name'     => $minutes_name,
				'id'       => $minutes_id,
			)
		);
		calendarp_am_pm_selector(
			array(
				'selected' => $am_pm,
				'echo'     => true,
				'name'     => $am_pm_name,
				'id'       => $am_pm_id,
			)
		);
	} else {
		calendarp_hours_selector(
			array(
				'selected' => $hours,
				'echo'     => true,
				'name'     => $hours_name,
				'id'       => $hours_id,
			)
		);
		echo '<span>:</span>';
		calendarp_minutes_selector(
			array(
				'selected' => $minutes,
				'echo'     => true,
				'name'     => $minutes_name,
				'id'       => $minutes_id,
			)
		);
	}

	if ( ! $echo ) {
		return ob_get_clean();
	}
}

/**
 * Handle calendarp am pm to 24h.
 *
 * @param mixed $value Value.
 * @param mixed $am_pm Am pm.
 *
 * @return mixed Calendar operation result.
 */
function calendarp_am_pm_to_24h( $value, $am_pm ) {
	$value = absint( $value );
	if ( 'pm' === $am_pm && 12 !== $value ) {
		$value += 12;
	} elseif ( 'am' === $am_pm && 12 === $value ) {
		$value = 0;
	}

	$value = (string) $value;
	$value = str_pad( $value, 2, '0', STR_PAD_LEFT );

	return $value;
}

/**
 * Handle calendarp 24h to timestamp.
 *
 * @param mixed $time Time.
 *
 * @return mixed Calendar operation result.
 */
function calendarp_24h_to_timestamp( $time ) {
	$date = gmdate( 'Y-m-d', calendarp_wall_time() );
	$date = $date . ' ' . $time;

	return strtotime( $date );
}

/**
 * Handle calendarp get total days in a month.
 *
 * @param int $month Month.
 * @param int $year Year.
 *
 * @return mixed Calendar operation result.
 */
function calendarp_get_total_days_in_a_month( $month, $year ) {
	$month = absint( $month );
	$year  = (string) absint( $year );
	$month = absint( $month );
	$year  = (string) absint( $year );
	if ( $month < 1 || $month > 12 ) {
		return 0;
	} elseif ( ! is_numeric( $year ) || strlen( $year ) !== 4 ) {
		$year = gmdate( 'Y' );
	}

	if ( defined( 'CAL_GREGORIAN' ) ) {
		if ( function_exists( 'cal_days_in_month' ) ) {
			return cal_days_in_month( CAL_GREGORIAN, $month, $year );
		} else {
			return 2 === $month ? ( $year % 4 ? 28 : ( $year % 100 ? 29 : ( $year % 400 ? 28 : 29 ) ) ) : ( ( $month - 1 ) % 7 % 2 ? 30 : 31 );
		}
	}

	if ( $year >= 1970 ) {
		return (int) gmdate( 't', mktime( 12, 0, 0, $month, 1, $year ) );
	}

	if ( 2 === $month ) {
		if ( 0 === $year % 400 || ( 0 === $year % 4 && 0 !== $year % 100 ) ) {
			return 29;
		}
	}

	$days_in_month = array( 31, 28, 31, 30, 31, 30, 31, 31, 30, 31, 30, 31 );

	return $days_in_month[ $month - 1 ];
}

/**
 * Handle calendarp get formatted date.
 *
 * @param mixed $date Date.
 *
 * @return mixed Calendar operation result.
 */
function calendarp_get_formatted_date( $date ) {
	$_date = explode( '-', $date );
	if ( count( $_date ) !== 3 ) {
		return '';
	}

	if ( ! checkdate( absint( $_date[1] ), absint( $_date[2] ), absint( $_date[0] ) ) ) {
		return '';
	}

	$timestamp = strtotime( $date );

	return date_i18n( get_option( 'date_format' ), $timestamp );
}

/**
 * Handle calendarp get formatted time.
 *
 * @param mixed $time Time.
 *
 * @return mixed Calendar operation result.
 */
function calendarp_get_formatted_time( $time ) {
	$time = calendarp_24h_to_timestamp( $time );

	return date_i18n( get_option( 'time_format' ), $time );
}

/**
 * Handle calendarp enqueue google maps scripts.
 *
 * @return mixed Calendar operation result.
 */
function calendarp_enqueue_google_maps_scripts() {
	$api_key = calendarp_get_setting( 'gmaps_api_key' );
	if ( ! $api_key ) {
		// this will trigger a controlled error in JS.
		$api_key = 'false';
	}

	$js  = 'https://maps.googleapis.com/maps/api/js?libraries=places';
	$js .= '&amp;key=' . $api_key;

	$settings = calendarp_get_settings();
	if ( $settings['country'] ) {
		$js .= '&amp;language=' . $settings['country'];
	}

	add_filter(
		'script_loader_tag',
		function ( $tag, $handle ) {
			if ( 'gmaps-api' !== $handle ) {
				return $tag;
			}

			return str_replace( ' src', ' async defer src', $tag );
		},
		10,
		2
	);

	wp_enqueue_script( 'gmaps-api', $js, array(), calendarp_get_version(), true );
}

/**
 * Handle calendarp enqueue public script and styles.
 *
 * @return mixed Calendar operation result.
 */
function calendarp_enqueue_public_script_and_styles() {

	$suffix = ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ) ? '' : '.min';

	wp_enqueue_style( 'dashicons' );

	wp_enqueue_style(
		'calendarp-jquery-ui-theme',
		calendarp_get_plugin_url() . 'includes/css/jquery-ui/jquery-ui.min.css',
		array(),
		calendarp_get_version()
	);

	wp_enqueue_script( 'jquery-ui-datepicker' );
	wp_enqueue_script( 'jquery-ui-tooltip' );

	if ( is_singular( 'calendar_event' ) ) {

		$event    = Calendar_Plus_Event::get_instance( get_the_ID() );
		$location = $event->get_location();

		if ( $location && $location->has_map() ) {
			calendarp_enqueue_google_maps_scripts();
		}
	}

	wp_localize_script(
		'calendar-plus',
		'calendar_i18n',
		array(
			'ajaxurl' => admin_url( 'admin-ajax.php' ),
		)
	);
}

/**
 * Handle calendarp enqueue public styles.
 *
 * @return mixed Calendar operation result.
 */
function calendarp_enqueue_public_styles() {
	wp_enqueue_style(
		'calendar-plus-calendar',
		calendarp_get_plugin_url() . 'public/css/calendar-plus.css',
		array(),
		calendarp_get_version()
	);

	wp_enqueue_script(
		'calendar-plus-calendar',
		calendarp_get_plugin_url() . 'public/js/calendar-plus.js',
		array( 'jquery' ),
		calendarp_get_version(),
		true
	);
}

/**
 * Handle calendarp get default capabilities.
 *
 * @return mixed Calendar operation result.
 */
function calendarp_get_default_capabilities() {
	return array(
		'manage_calendar_plus',

		// Events.
		'edit_calendar_event',
		'read_calendar_event',
		'delete_calendar_event',
		'edit_calendar_events',
		'edit_others_calendar_events',
		'publish_calendar_events',
		'read_private_calendar_events',
		'delete_calendar_events',
		'delete_private_calendar_events',
		'delete_published_calendar_events',
		'delete_others_calendar_events',
		'edit_private_calendar_events',
		'edit_published_calendar_events',
		'manage_calendar_event_terms',
		'edit_calendar_event_terms',
		'delete_calendar_event_terms',
		'assign_calendar_event_terms',

		// Locations.
		'edit_calendar_location',
		'read_calendar_location',
		'delete_calendar_location',
		'edit_calendar_locations',
		'edit_others_calendar_locations',
		'publish_calendar_locations',
		'read_private_calendar_locations',
		'delete_calendar_locations',
		'delete_private_calendar_locations',
		'delete_published_calendar_locations',
		'delete_others_calendar_locations',
		'edit_private_calendar_locations',
		'edit_published_calendar_locations',
		'manage_calendar_location_terms',
		'edit_calendar_location_terms',
		'delete_calendar_location_terms',
		'assign_calendar_location_terms',
	);
}

/**
 * Handle calendarp get assignable capabilities.
 *
 * @return mixed Calendar operation result.
 */
function calendarp_get_assignable_capabilities() {

	return array(
		'manage_calendar_plus'                => __( 'Manage Calendar Plus', 'calendar-plus' ),

		// Events.
		'publish_calendar_events'             => __( 'Publish Events', 'calendar-plus' ),
		'read_private_calendar_events'        => __( 'Read Private Events', 'calendar-plus' ),

		// Editing Events.
		'edit_calendar_events'                => __( 'Edit Events', 'calendar-plus' ),
		'edit_others_calendar_events'         => __( 'Edit Others’ Events', 'calendar-plus' ),
		'edit_private_calendar_events'        => __( 'Edit Private Events', 'calendar-plus' ),
		'edit_published_calendar_events'      => __( 'Edit Published Events', 'calendar-plus' ),

		// Deleting Events.
		'delete_calendar_events'              => __( 'Delete Events', 'calendar-plus' ),
		'delete_private_calendar_events'      => __( 'Delete Private Events', 'calendar-plus' ),
		'delete_published_calendar_events'    => __( 'Delete Published Events', 'calendar-plus' ),
		'delete_others_calendar_events'       => __( 'Delete Others’ Events', 'calendar-plus' ),

		// Managing Event Terms.
		'manage_calendar_event_terms'         => __( 'Manage Event Terms', 'calendar-plus' ),
		'edit_calendar_event_terms'           => __( 'Edit Event Terms', 'calendar-plus' ),
		'delete_calendar_event_terms'         => __( 'Delete Event Terms', 'calendar-plus' ),
		'assign_calendar_event_terms'         => __( 'Assign Event Terms', 'calendar-plus' ),

		// Locations.
		'publish_calendar_locations'          => __( 'Publish Locations', 'calendar-plus' ),
		'read_private_calendar_locations'     => __( 'Read Private Locations', 'calendar-plus' ),

		// Editing Locations.
		'edit_calendar_locations'             => __( 'Edit Locations', 'calendar-plus' ),
		'edit_others_calendar_locations'      => __( 'Edit Others’ Locations', 'calendar-plus' ),
		'edit_private_calendar_locations'     => __( 'Edit Private Locations', 'calendar-plus' ),
		'edit_published_calendar_locations'   => __( 'Edit Published Locations', 'calendar-plus' ),

		// Deleting Locations.
		'delete_calendar_locations'           => __( 'Delete Locations', 'calendar-plus' ),
		'delete_private_calendar_locations'   => __( 'Delete Private Locations', 'calendar-plus' ),
		'delete_published_calendar_locations' => __( 'Delete Published Locations', 'calendar-plus' ),
		'delete_others_calendar_locations'    => __( 'Delete Others’ Locations', 'calendar-plus' ),

		// Managing Location Terms.
		'manage_calendar_location_terms'      => __( 'Manage Location Terms', 'calendar-plus' ),
		'edit_calendar_location_terms'        => __( 'Edit Location Terms', 'calendar-plus' ),
		'delete_calendar_location_terms'      => __( 'Delete Location Terms', 'calendar-plus' ),
		'assign_calendar_location_terms'      => __( 'Assign Location Terms', 'calendar-plus' ),
	);
}

/**
 * Checks if a user is removable from list of users.
 * that are allowed to manage Calendar Plus
 *
 * @param int $user_id User id.
 *
 * @return mixed Calendar operation result.
 */
function calendarp_is_user_removable( $user_id ) {
	$user = get_userdata( $user_id );
	if ( ! $user ) {
		return false;
	}

	$removable = true;
	if ( is_multisite() && is_super_admin( $user_id ) ) {
		$removable = false;
	}
	if ( in_array( 'administrator', $user->roles, true ) ) {
		$removable = false;
	}
	if ( get_current_user_id() === $user->ID ) {
		$removable = false;
	}

	return $removable;
}

/**
 * Return a list of users that are allowed to manage Calendar+.
 *
 * @return mixed Calendar operation result.
 */
function calendarp_get_allowed_users() {
	$admins = get_users(
		array(
			'role' => 'administrator',
		)
	);

	$managers = get_users(
		array(
			'role' => 'calendarp_events_manager',
		)
	);

	return array_merge( $admins, $managers );
}

/**
 * Handle calendarp is date cell queried.
 *
 * @return mixed Calendar operation result.
 */
function calendarp_is_date_cell_queried() {
	return ( null !== calendarp_form_input( 'event-date', null, 'get' ) && calendarp_get_event_cell( (int) calendarp_form_input( 'event-date', '', 'get' ) ) );
}

/**
 * Return the queried cell if it was queried through URL.
 *
 * @param bool $event_id Event id.
 * @return object|boolean
 */
function calendarp_get_queried_date_cell( $event_id ) {
	$event = calendarp_get_event( $event_id );
	if ( ! $event ) {
		return false;
	}

	$date_id = null !== calendarp_form_input( 'event-date', null, 'get' ) ? absint( calendarp_form_input( 'event-date', '', 'get' ) ) : false;
	if ( $date_id ) {
		// There's a date ID specified in URL, let's get it.
		$date = calendarp_get_event_cell( $date_id );
		if ( ! empty( $date->event_id ) && absint( $date->event_id ) !== absint( $event_id ) ) {
			$date = false;
		}
	} else {
		// Let's get the  first date found.
		$dates = $event->get_dates_list();
		$date  = current( $dates );
		$date  = (object) $date;
	}

	if ( ! $date ) {
		return false;
	}

	return $date;
}

/**
 * Checks whether we're in a REST API request.
 *
 * @return bool
 */
function calendarp_is_rest_api_request() {
	return defined( 'REST_REQUEST' ) && 'REST_REQUEST';
}

/**
 * Returns formatted error message HTML.
 *
 * Used to output block editor errors.
 *
 * @param string $msg Plain-text message string.
 * @return string HTML message.
 */
function calendarp_block_error_msg( $msg ) {
	return '<div class="components-placeholder">' .
		'<div class="components-placeholder__fieldset">' . esc_html( $msg ) . '</div>' .
	'</div>';
}

/**
 * Returns true if a taxonomy is assigned to multiple post types.
 *
 * @param WP_Term|WP_Post_Type|WP_Post|WP_User|null $queried_object The queried object.
 * @return boolean
 */
function calendarp_is_taxonomy_assigned_to_multiple( $queried_object ) {
	if (
		! $queried_object ||
		empty( $queried_object->taxonomy ) ||
		! ( $queried_object instanceof WP_Term )
	) {
		return false;
	}

	$tax_object = get_taxonomy( $queried_object->taxonomy );

	if ( ! $tax_object ) {
		return false;
	}

	return is_countable( $tax_object->object_type ) && count( $tax_object->object_type ) > 1;
}

/**
 * Read a scalar || array of form text without accepting objects || nested scalar mismatches.
 * Callers of write actions must verify a nonce and capability before using this helper.
 * Public GET filters are read-only and intentionally work with anonymous, cached pages.
 *
 * @param string $key Input key.
 * @param mixed  $default Expected scalar || array default.
 * @param string $method post, get || request.
 * @return mixed Sanitized input with the expected shape.
 */
function calendarp_form_input( $key, $default = '', $method = 'post' ) {
	// phpcs:disable WordPress.Security.NonceVerification -- Verification belongs to the write-action caller; GET filters do not mutate state.
	$source = 'get' === $method ? $_GET : ( 'request' === $method ? $_REQUEST : $_POST );
	// phpcs:enable WordPress.Security.NonceVerification
	if ( ! isset( $source[ $key ] ) || is_array( $default ) !== is_array( $source[ $key ] ) ) {
		return $default;
	}
	$value = wp_unslash( $source[ $key ] );
	return is_array( $value ) ? map_deep( $value, 'sanitize_text_field' ) : sanitize_text_field( $value );
}

/**
 * Handle calendarp get allowed calendar html.
 *
 * @return mixed Calendar operation result.
 */
function calendarp_get_allowed_calendar_html() {
	$allowed           = wp_kses_allowed_html( 'post' );
	$attributes        = array_fill_keys( array( 'id', 'class', 'name', 'value', 'type', 'title', 'style', 'role', 'aria-*', 'data-*', 'disabled', 'checked', 'selected', 'multiple', 'min', 'max', 'step', 'tabindex' ), true );
	$allowed['input']  = $attributes;
	$allowed['button'] = $attributes;
	$allowed['select'] = $attributes;
	$allowed['option'] = $attributes;
	$allowed['form']   = array(
		'id'     => true,
		'class'  => true,
		'action' => true,
		'method' => true,
	);
	return $allowed;
}

/**
 * Return a wall-clock number for the legacy engine's separate date/time columns.
 * This is deliberately an offset value for gmdate arithmetic, not an absolute Unix timestamp.
 * New API/export paths use timezone-aware DateTime objects instead.
 *
 * @return int Current local wall-clock seconds used only by the native schedule engine.
 */
function calendarp_wall_time() {
	$now = current_datetime();
	return $now->getTimestamp() + $now->getOffset();
}
