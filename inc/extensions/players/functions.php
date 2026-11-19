<?php
/**
 * Theme-safe `clanbite_players_*()` helpers and player-related utilities.
 *
 * @package clanbite
 */

defined( 'ABSPATH' ) || exit;


/**
 * Get an array of countries.
 *
 * Get translated Countries in WordPress. Runs output through a
 * filter before returning to allow for customization through third
 * party plugins and themes, or for select removal/modification/addition
 * of countries for whatever reason.
 *
 * @return array Countries as Country code => Country name
 */
function clanbite_players_get_countries() {
	$countries = array(
		'AF' => __( 'Afghanistan', 'clanbite' ),
		'AL' => __( 'Albania', 'clanbite' ),
		'DZ' => __( 'Algeria', 'clanbite' ),
		'AS' => __( 'American Samoa', 'clanbite' ),
		'AD' => __( 'Andorra', 'clanbite' ),
		'AO' => __( 'Angola', 'clanbite' ),
		'AI' => __( 'Anguilla', 'clanbite' ),
		'AQ' => __( 'Antarctica', 'clanbite' ),
		'AG' => __( 'Antigua and Barbuda', 'clanbite' ),
		'AR' => __( 'Argentina', 'clanbite' ),
		'AM' => __( 'Armenia', 'clanbite' ),
		'AW' => __( 'Aruba', 'clanbite' ),
		'AU' => __( 'Australia', 'clanbite' ),
		'AT' => __( 'Austria', 'clanbite' ),
		'AZ' => __( 'Azerbaijan', 'clanbite' ),
		'BS' => __( 'Bahamas', 'clanbite' ),
		'BH' => __( 'Bahrain', 'clanbite' ),
		'BD' => __( 'Bangladesh', 'clanbite' ),
		'BB' => __( 'Barbados', 'clanbite' ),
		'BY' => __( 'Belarus', 'clanbite' ),
		'BE' => __( 'Belgium', 'clanbite' ),
		'BZ' => __( 'Belize', 'clanbite' ),
		'BJ' => __( 'Benin', 'clanbite' ),
		'BM' => __( 'Bermuda', 'clanbite' ),
		'BT' => __( 'Bhutan', 'clanbite' ),
		'BO' => __( 'Bolivia', 'clanbite' ),
		'BA' => __( 'Bosnia and Herzegovina', 'clanbite' ),
		'BW' => __( 'Botswana', 'clanbite' ),
		'BV' => __( 'Bouvet Island', 'clanbite' ),
		'BR' => __( 'Brazil', 'clanbite' ),
		'BQ' => __( 'British Antarctic Territory', 'clanbite' ),
		'IO' => __( 'British Indian Ocean Territory', 'clanbite' ),
		'VG' => __( 'British Virgin Islands', 'clanbite' ),
		'BN' => __( 'Brunei', 'clanbite' ),
		'BG' => __( 'Bulgaria', 'clanbite' ),
		'BF' => __( 'Burkina Faso', 'clanbite' ),
		'BI' => __( 'Burundi', 'clanbite' ),
		'KH' => __( 'Cambodia', 'clanbite' ),
		'CM' => __( 'Cameroon', 'clanbite' ),
		'CA' => __( 'Canada', 'clanbite' ),
		'CT' => __( 'Canton and Enderbury Islands', 'clanbite' ),
		'CV' => __( 'Cape Verde', 'clanbite' ),
		'KY' => __( 'Cayman Islands', 'clanbite' ),
		'CF' => __( 'Central African Republic', 'clanbite' ),
		'TD' => __( 'Chad', 'clanbite' ),
		'CL' => __( 'Chile', 'clanbite' ),
		'CN' => __( 'China', 'clanbite' ),
		'CX' => __( 'Christmas Island', 'clanbite' ),
		'CC' => __( 'Cocos [Keeling] Islands', 'clanbite' ),
		'CO' => __( 'Colombia', 'clanbite' ),
		'KM' => __( 'Comoros', 'clanbite' ),
		'CG' => __( 'Congo - Brazzaville', 'clanbite' ),
		'CD' => __( 'Congo - Kinshasa', 'clanbite' ),
		'CK' => __( 'Cook Islands', 'clanbite' ),
		'CR' => __( 'Costa Rica', 'clanbite' ),
		'HR' => __( 'Croatia', 'clanbite' ),
		'CU' => __( 'Cuba', 'clanbite' ),
		'CY' => __( 'Cyprus', 'clanbite' ),
		'CZ' => __( 'Czech Republic', 'clanbite' ),
		'CI' => __( 'Côte d’Ivoire', 'clanbite' ),
		'DK' => __( 'Denmark', 'clanbite' ),
		'DJ' => __( 'Djibouti', 'clanbite' ),
		'DM' => __( 'Dominica', 'clanbite' ),
		'DO' => __( 'Dominican Republic', 'clanbite' ),
		'NQ' => __( 'Dronning Maud Land', 'clanbite' ),
		'DD' => __( 'East Germany', 'clanbite' ),
		'EC' => __( 'Ecuador', 'clanbite' ),
		'EG' => __( 'Egypt', 'clanbite' ),
		'SV' => __( 'El Salvador', 'clanbite' ),
		'GQ' => __( 'Equatorial Guinea', 'clanbite' ),
		'ER' => __( 'Eritrea', 'clanbite' ),
		'EE' => __( 'Estonia', 'clanbite' ),
		'ET' => __( 'Ethiopia', 'clanbite' ),
		'FK' => __( 'Falkland Islands', 'clanbite' ),
		'FO' => __( 'Faroe Islands', 'clanbite' ),
		'FJ' => __( 'Fiji', 'clanbite' ),
		'FI' => __( 'Finland', 'clanbite' ),
		'FR' => __( 'France', 'clanbite' ),
		'GF' => __( 'French Guiana', 'clanbite' ),
		'PF' => __( 'French Polynesia', 'clanbite' ),
		'TF' => __( 'French Southern Territories', 'clanbite' ),
		'FQ' => __( 'French Southern and Antarctic Territories', 'clanbite' ),
		'GA' => __( 'Gabon', 'clanbite' ),
		'GM' => __( 'Gambia', 'clanbite' ),
		'GE' => __( 'Georgia', 'clanbite' ),
		'DE' => __( 'Germany', 'clanbite' ),
		'GH' => __( 'Ghana', 'clanbite' ),
		'GI' => __( 'Gibraltar', 'clanbite' ),
		'GR' => __( 'Greece', 'clanbite' ),
		'GL' => __( 'Greenland', 'clanbite' ),
		'GD' => __( 'Grenada', 'clanbite' ),
		'GP' => __( 'Guadeloupe', 'clanbite' ),
		'GU' => __( 'Guam', 'clanbite' ),
		'GT' => __( 'Guatemala', 'clanbite' ),
		'GG' => __( 'Guernsey', 'clanbite' ),
		'GN' => __( 'Guinea', 'clanbite' ),
		'GW' => __( 'Guinea-Bissau', 'clanbite' ),
		'GY' => __( 'Guyana', 'clanbite' ),
		'HT' => __( 'Haiti', 'clanbite' ),
		'HM' => __( 'Heard Island and McDonald Islands', 'clanbite' ),
		'HN' => __( 'Honduras', 'clanbite' ),
		'HK' => __( 'Hong Kong SAR China', 'clanbite' ),
		'HU' => __( 'Hungary', 'clanbite' ),
		'IS' => __( 'Iceland', 'clanbite' ),
		'IN' => __( 'India', 'clanbite' ),
		'ID' => __( 'Indonesia', 'clanbite' ),
		'IR' => __( 'Iran', 'clanbite' ),
		'IQ' => __( 'Iraq', 'clanbite' ),
		'IE' => __( 'Ireland', 'clanbite' ),
		'IM' => __( 'Isle of Man', 'clanbite' ),
		'IL' => __( 'Israel', 'clanbite' ),
		'IT' => __( 'Italy', 'clanbite' ),
		'JM' => __( 'Jamaica', 'clanbite' ),
		'JP' => __( 'Japan', 'clanbite' ),
		'JE' => __( 'Jersey', 'clanbite' ),
		'JT' => __( 'Johnston Island', 'clanbite' ),
		'JO' => __( 'Jordan', 'clanbite' ),
		'KZ' => __( 'Kazakhstan', 'clanbite' ),
		'KE' => __( 'Kenya', 'clanbite' ),
		'KI' => __( 'Kiribati', 'clanbite' ),
		'KW' => __( 'Kuwait', 'clanbite' ),
		'KG' => __( 'Kyrgyzstan', 'clanbite' ),
		'LA' => __( 'Laos', 'clanbite' ),
		'LV' => __( 'Latvia', 'clanbite' ),
		'LB' => __( 'Lebanon', 'clanbite' ),
		'LS' => __( 'Lesotho', 'clanbite' ),
		'LR' => __( 'Liberia', 'clanbite' ),
		'LY' => __( 'Libya', 'clanbite' ),
		'LI' => __( 'Liechtenstein', 'clanbite' ),
		'LT' => __( 'Lithuania', 'clanbite' ),
		'LU' => __( 'Luxembourg', 'clanbite' ),
		'MO' => __( 'Macau SAR China', 'clanbite' ),
		'MK' => __( 'Macedonia', 'clanbite' ),
		'MG' => __( 'Madagascar', 'clanbite' ),
		'MW' => __( 'Malawi', 'clanbite' ),
		'MY' => __( 'Malaysia', 'clanbite' ),
		'MV' => __( 'Maldives', 'clanbite' ),
		'ML' => __( 'Mali', 'clanbite' ),
		'MT' => __( 'Malta', 'clanbite' ),
		'MH' => __( 'Marshall Islands', 'clanbite' ),
		'MQ' => __( 'Martinique', 'clanbite' ),
		'MR' => __( 'Mauritania', 'clanbite' ),
		'MU' => __( 'Mauritius', 'clanbite' ),
		'YT' => __( 'Mayotte', 'clanbite' ),
		'FX' => __( 'Metropolitan France', 'clanbite' ),
		'MX' => __( 'Mexico', 'clanbite' ),
		'FM' => __( 'Micronesia', 'clanbite' ),
		'MI' => __( 'Midway Islands', 'clanbite' ),
		'MD' => __( 'Moldova', 'clanbite' ),
		'MC' => __( 'Monaco', 'clanbite' ),
		'MN' => __( 'Mongolia', 'clanbite' ),
		'ME' => __( 'Montenegro', 'clanbite' ),
		'MS' => __( 'Montserrat', 'clanbite' ),
		'MA' => __( 'Morocco', 'clanbite' ),
		'MZ' => __( 'Mozambique', 'clanbite' ),
		'MM' => __( 'Myanmar [Burma]', 'clanbite' ),
		'NA' => __( 'Namibia', 'clanbite' ),
		'NR' => __( 'Nauru', 'clanbite' ),
		'NP' => __( 'Nepal', 'clanbite' ),
		'NL' => __( 'Netherlands', 'clanbite' ),
		'AN' => __( 'Netherlands Antilles', 'clanbite' ),
		'NT' => __( 'Neutral Zone', 'clanbite' ),
		'NC' => __( 'New Caledonia', 'clanbite' ),
		'NZ' => __( 'New Zealand', 'clanbite' ),
		'NI' => __( 'Nicaragua', 'clanbite' ),
		'NE' => __( 'Niger', 'clanbite' ),
		'NG' => __( 'Nigeria', 'clanbite' ),
		'NU' => __( 'Niue', 'clanbite' ),
		'NF' => __( 'Norfolk Island', 'clanbite' ),
		'KP' => __( 'North Korea', 'clanbite' ),
		'VD' => __( 'North Vietnam', 'clanbite' ),
		'MP' => __( 'Northern Mariana Islands', 'clanbite' ),
		'NO' => __( 'Norway', 'clanbite' ),
		'OM' => __( 'Oman', 'clanbite' ),
		'PC' => __( 'Pacific Islands Trust Territory', 'clanbite' ),
		'PK' => __( 'Pakistan', 'clanbite' ),
		'PW' => __( 'Palau', 'clanbite' ),
		'PS' => __( 'Palestinian Territories', 'clanbite' ),
		'PA' => __( 'Panama', 'clanbite' ),
		'PZ' => __( 'Panama Canal Zone', 'clanbite' ),
		'PG' => __( 'Papua New Guinea', 'clanbite' ),
		'PY' => __( 'Paraguay', 'clanbite' ),
		'YD' => __( 'People\'s Democratic Republic of Yemen', 'clanbite' ),
		'PE' => __( 'Peru', 'clanbite' ),
		'PH' => __( 'Philippines', 'clanbite' ),
		'PN' => __( 'Pitcairn Islands', 'clanbite' ),
		'PL' => __( 'Poland', 'clanbite' ),
		'PT' => __( 'Portugal', 'clanbite' ),
		'PR' => __( 'Puerto Rico', 'clanbite' ),
		'QA' => __( 'Qatar', 'clanbite' ),
		'RO' => __( 'Romania', 'clanbite' ),
		'RU' => __( 'Russia', 'clanbite' ),
		'RW' => __( 'Rwanda', 'clanbite' ),
		'BL' => __( 'Saint Barthélemy', 'clanbite' ),
		'SH' => __( 'Saint Helena', 'clanbite' ),
		'KN' => __( 'Saint Kitts and Nevis', 'clanbite' ),
		'LC' => __( 'Saint Lucia', 'clanbite' ),
		'MF' => __( 'Saint Martin', 'clanbite' ),
		'PM' => __( 'Saint Pierre and Miquelon', 'clanbite' ),
		'VC' => __( 'Saint Vincent and the Grenadines', 'clanbite' ),
		'WS' => __( 'Samoa', 'clanbite' ),
		'SM' => __( 'San Marino', 'clanbite' ),
		'SA' => __( 'Saudi Arabia', 'clanbite' ),
		'SN' => __( 'Senegal', 'clanbite' ),
		'RS' => __( 'Serbia', 'clanbite' ),
		'CS' => __( 'Serbia and Montenegro', 'clanbite' ),
		'SC' => __( 'Seychelles', 'clanbite' ),
		'SL' => __( 'Sierra Leone', 'clanbite' ),
		'SG' => __( 'Singapore', 'clanbite' ),
		'SK' => __( 'Slovakia', 'clanbite' ),
		'SI' => __( 'Slovenia', 'clanbite' ),
		'SB' => __( 'Solomon Islands', 'clanbite' ),
		'SO' => __( 'Somalia', 'clanbite' ),
		'ZA' => __( 'South Africa', 'clanbite' ),
		'GS' => __( 'South Georgia and the South Sandwich Islands', 'clanbite' ),
		'KR' => __( 'South Korea', 'clanbite' ),
		'ES' => __( 'Spain', 'clanbite' ),
		'LK' => __( 'Sri Lanka', 'clanbite' ),
		'SD' => __( 'Sudan', 'clanbite' ),
		'SR' => __( 'Suriname', 'clanbite' ),
		'SJ' => __( 'Svalbard and Jan Mayen', 'clanbite' ),
		'SZ' => __( 'Swaziland', 'clanbite' ),
		'SE' => __( 'Sweden', 'clanbite' ),
		'CH' => __( 'Switzerland', 'clanbite' ),
		'SY' => __( 'Syria', 'clanbite' ),
		'ST' => __( 'São Tomé and Príncipe', 'clanbite' ),
		'TW' => __( 'Taiwan', 'clanbite' ),
		'TJ' => __( 'Tajikistan', 'clanbite' ),
		'TZ' => __( 'Tanzania', 'clanbite' ),
		'TH' => __( 'Thailand', 'clanbite' ),
		'TL' => __( 'Timor-Leste', 'clanbite' ),
		'TG' => __( 'Togo', 'clanbite' ),
		'TK' => __( 'Tokelau', 'clanbite' ),
		'TO' => __( 'Tonga', 'clanbite' ),
		'TT' => __( 'Trinidad and Tobago', 'clanbite' ),
		'TN' => __( 'Tunisia', 'clanbite' ),
		'TR' => __( 'Turkey', 'clanbite' ),
		'TM' => __( 'Turkmenistan', 'clanbite' ),
		'TC' => __( 'Turks and Caicos Islands', 'clanbite' ),
		'TV' => __( 'Tuvalu', 'clanbite' ),
		'UM' => __( 'U.S. Minor Outlying Islands', 'clanbite' ),
		'PU' => __( 'U.S. Miscellaneous Pacific Islands', 'clanbite' ),
		'VI' => __( 'U.S. Virgin Islands', 'clanbite' ),
		'UG' => __( 'Uganda', 'clanbite' ),
		'UA' => __( 'Ukraine', 'clanbite' ),
		'SU' => __( 'Union of Soviet Socialist Republics', 'clanbite' ),
		'AE' => __( 'United Arab Emirates', 'clanbite' ),
		'GB' => __( 'United Kingdom', 'clanbite' ),
		'US' => __( 'United States', 'clanbite' ),
		'ZZ' => __( 'Unknown or Invalid Region', 'clanbite' ),
		'UY' => __( 'Uruguay', 'clanbite' ),
		'UZ' => __( 'Uzbekistan', 'clanbite' ),
		'VU' => __( 'Vanuatu', 'clanbite' ),
		'VA' => __( 'Vatican City', 'clanbite' ),
		'VE' => __( 'Venezuela', 'clanbite' ),
		'VN' => __( 'Vietnam', 'clanbite' ),
		'WK' => __( 'Wake Island', 'clanbite' ),
		'WF' => __( 'Wallis and Futuna', 'clanbite' ),
		'EH' => __( 'Western Sahara', 'clanbite' ),
		'YE' => __( 'Yemen', 'clanbite' ),
		'ZM' => __( 'Zambia', 'clanbite' ),
		'ZW' => __( 'Zimbabwe', 'clanbite' ),
		'AX' => __( 'Åland Islands', 'clanbite' ),
	);

	/**
	 * Filter the countries before returning
	 *
	 * @param array $countries countries array for filtering
	 */
	$countries = apply_filters( 'clanbite_players_countries_filters', $countries );

	/**
	 * Return the translated and filtered countries
	 */
	return $countries;
}

/**
 * URL of the plugin-bundled default player avatar (used when Players -> default avatar is unset).
 *
 * @return string Empty when `clanbite()` is unavailable.
 */
function clanbite_players_get_bundled_default_avatar_url(): string {
	if ( ! function_exists( 'clanbite' ) ) {
		return '';
	}

	return clanbite()->url . 'assets/img/avatars/default-player-avatar.png';
}

/**
 * URL of the plugin-bundled default player cover (used when Players → default cover is unset).
 *
 * @return string Empty when `clanbite()` is unavailable.
 */
function clanbite_players_get_bundled_default_cover_url(): string {
	if ( ! function_exists( 'clanbite' ) ) {
		return '';
	}

	return clanbite()->url . 'assets/img/covers/default-player-cover-image.png';
}

/**
 * Get the default avatar URL used when a player has not uploaded one.
 *
 * Uses the Players setting `default_avatar` when set, otherwise the bundled image.
 *
 * @param int $player_id Player (user) ID, passed through to the filter.
 * @return string Image URL.
 */
function clanbite_players_get_default_avatar( $player_id ) {
	$loader = clanbite()->extensions;
	if ( null === $loader ) {
		$ext = null;
	} else {
		$ext = $loader->get( 'cp_players' );
	}
	$default_avatar = $ext instanceof \Kernowdev\Clanbite\Extensions\Players
		? $ext->get_setting( 'default_avatar' )
		: '';

	if ( empty( $default_avatar ) ) {
		$default_avatar = clanbite_players_get_bundled_default_avatar_url();
	}

	/**
	 * Filters the default player avatar URL.
	 *
	 * @param string $default_avatar Default avatar URL (setting or bundled image).
	 * @param int    $player_id  Player (user) ID.
	 */
	return apply_filters( 'clanbite_players_get_default_avatar', $default_avatar, $player_id );
}

/**
 * Get the default cover URL used when a player has not uploaded one.
 *
 * Uses the Players setting `default_cover` when set, otherwise the bundled image.
 *
 * @param int $player_id Player (user) ID, passed through to the filter.
 * @return string Image URL.
 */
function clanbite_players_get_default_cover( $player_id ) {
	$loader = clanbite()->extensions;
	if ( null === $loader ) {
		$ext = null;
	} else {
		$ext = $loader->get( 'cp_players' );
	}
	$default_cover = $ext instanceof \Kernowdev\Clanbite\Extensions\Players
		? $ext->get_setting( 'default_cover' )
		: '';

	if ( empty( $default_cover ) ) {
		$default_cover = clanbite_players_get_bundled_default_cover_url();
	}

	/**
	 * Filters the default player cover URL.
	 *
	 * @param string $default_cover Default cover URL (setting or bundled image).
	 * @param int    $player_id  Player (user) ID.
	 */
	return apply_filters( 'clanbite_players_get_default_cover', $default_cover, $player_id );
}

/**
 * Register a player profile subpage (tab) for the front-end player profile.
 *
 * Third-party plugins should call this on `init`.
 *
 * @param string $slug Unique slug (used in the URL).
 * @param array  $args {
 *     @type string $label        Human-readable label for navigation.
 *     @type string $template_id  FSE template identifier (defaults to "clanbite-player-{$slug}").
 *     @type string $default_blocks Optional default block markup to seed the template.
 *     @type string $capability   Capability required to view (default "read").
 *     @type int    $position     Sort order in navigation (lower first).
 * }
 * @return void
 */
function clanbite_register_player_subpage( string $slug, array $args = array() ): void {
	if ( function_exists( 'clanbite_register_profile_subpage' ) ) {
		clanbite_register_profile_subpage( 'player', $slug, $args );
	}
}

/**
 * All registered player profile subpages.
 *
 * @return array<string,array>
 */
function clanbite_get_player_subpages(): array {
	return function_exists( 'clanbite_get_profile_subpages' ) ? clanbite_get_profile_subpages( 'player' ) : array();
}

/**
 * Resolve a single player subpage config by slug.
 *
 * @param string $slug Subpage slug.
 * @return array<string,mixed>|null
 */
function clanbite_get_player_subpage( string $slug ): ?array {
	return function_exists( 'clanbite_get_profile_subpage' ) ? clanbite_get_profile_subpage( 'player', $slug ) : null;
}

/**
 * Resolve a player user ID from the canonical `/players/{nicename}/…` path (see rewrite rules).
 *
 * Uses {@see clanbite_get_canonical_request_path()} so subdirectory installs and `home_path` prefixes
 * match the same routes as {@see \Kernowdev\Clanbite\Extensions\Players::filter_request_for_players_virtual_routes()}.
 *
 * @return int User ID or 0 when the path is not a member profile (directory, settings, etc.).
 */
function clanbite_player_user_id_from_canonical_request_path(): int {
	if ( ! function_exists( 'clanbite_get_canonical_request_path' ) ) {
		return 0;
	}

	$path = clanbite_get_canonical_request_path();
	if ( '' === $path ) {
		return 0;
	}

	// Players directory — not a single profile.
	if ( 'players' === $path || preg_match( '#^players/page/[0-9]+/?$#', $path ) ) {
		return 0;
	}

	if ( str_starts_with( $path, 'players/settings' ) || 'players/settings' === $path ) {
		return 0;
	}

	$nicename = '';
	if ( preg_match( '#^players/(?!settings(?:/|$))([^/]+)/page/([0-9]+)/?$#', $path, $m ) ) {
		$nicename = $m[1];
	} elseif ( preg_match( '#^players/(?!settings(?:/|$))([^/]+)/([^/]+)/?$#', $path, $m ) ) {
		$nicename = $m[1];
	} elseif ( preg_match( '#^players/(?!settings(?:/|$))([^/]+)/?$#', $path, $m ) ) {
		$nicename = $m[1];
	}

	if ( '' === $nicename ) {
		return 0;
	}

	$user = get_user_by( 'slug', $nicename );

	return ( $user instanceof \WP_User ) ? (int) $user->ID : 0;
}

/**
 * User ID for player profile header/nav (author archive, `/players/settings/`, or canonical `/players/{nicename}/`).
 *
 * Resolution order matches the pre–Plugin Check baseline: player settings, main-query author user, then
 * canonical `/players/{nicename}/` from {@see clanbite_player_user_id_from_canonical_request_path()}.
 *
 * @return int
 */
function clanbite_player_profile_context_user_id(): int {
	if ( (int) get_query_var( 'players_settings' ) ) {
		$uid = get_current_user_id();

		return $uid > 0 ? $uid : 0;
	}

	if ( get_queried_object() instanceof \WP_User ) {
		return (int) get_queried_object()->ID;
	}

	$from_path = clanbite_player_user_id_from_canonical_request_path();
	if ( $from_path > 0 ) {
		return $from_path;
	}

	// The block editor's server render previews the editing user's profile.
	if ( function_exists( 'clanbite_is_editor_preview' ) && clanbite_is_editor_preview() ) {
		return (int) get_current_user_id();
	}

	return 0;
}

/**
 * Resolves the subject user ID for Clanbite player blocks on the front end.
 *
 * Uses {@see clanbite_player_profile_context_user_id()} first (author archive, player settings),
 * then the block `postId` context author, then the logged-in user.
 *
 * @param \WP_Block|null $block Optional block instance for post context.
 * @return int User ID or 0 if none resolved.
 */
function clanbite_player_blocks_resolve_subject_user_id( $block = null ): int {
	if ( $block instanceof \WP_Block && ! empty( $block->context['clanbite/playerId'] ) ) {
		$loop_uid = (int) $block->context['clanbite/playerId'];
		if ( $loop_uid > 0 ) {
			return $loop_uid;
		}
	}

	$uid = (int) clanbite_player_profile_context_user_id();
	if ( $uid > 0 ) {
		return $uid;
	}

	if ( $block instanceof \WP_Block && ! empty( $block->context['postId'] ) ) {
		$author_id = (int) get_post_field( 'post_author', (int) $block->context['postId'] );
		if ( $author_id > 0 ) {
			return $author_id;
		}
	}

	if ( is_user_logged_in() ) {
		return (int) get_current_user_id();
	}

	return 0;
}

/**
 * Active player profile sub-route (`settings` or `cp_player_subpage`).
 *
 * @return string
 */
function clanbite_player_profile_route_current_slug(): string {
	if ( (int) get_query_var( 'players_settings' ) ) {
		return 'settings';
	}

	return sanitize_key( (string) get_query_var( 'cp_player_subpage' ) );
}

/**
 * Canonical front-end URL for a player profile (`/players/{nicename}/`).
 *
 * Used by Social Kit (mentions, REST payloads), notifications, block link helpers, and user-nav when present.
 *
 * @param int $user_id WordPress user ID.
 * @return string Absolute URL, or empty string when the user cannot be resolved.
 */
function clanbite_get_player_profile_url( int $user_id ): string {
	if ( $user_id <= 0 ) {
		return '';
	}

	$user = get_userdata( $user_id );
	if ( ! $user instanceof \WP_User ) {
		return '';
	}

	$nicename = isset( $user->user_nicename ) ? (string) $user->user_nicename : '';
	if ( '' === $nicename ) {
		return '';
	}

	$url = trailingslashit( home_url( '/players/' . $nicename ) );

	/**
	 * Filter the canonical player profile URL for a user.
	 *
	 * @param string $url     Profile URL.
	 * @param int    $user_id User ID.
	 */
	return (string) apply_filters( 'clanbite_player_profile_url', $url, $user_id );
}

/**
 * Label map for image size slugs in Players / Teams settings UI.
 *
 * @return array<string, string> Slug => translated label.
 */
function clanbite_players_get_image_size_choices_for_settings(): array {
	$slugs = array_values(
		array_unique(
			array_merge(
				array( 'full' ),
				get_intermediate_image_sizes()
			)
		)
	);
	sort( $slugs );

	$labels = array(
		'full'                        => __( 'Full size', 'clanbite' ),
		'thumbnail'                   => __( 'Thumbnail', 'clanbite' ),
		'medium'                      => __( 'Medium', 'clanbite' ),
		'medium_large'                => __( 'Medium large', 'clanbite' ),
		'large'                       => __( 'Large (WordPress)', 'clanbite' ),
		'clanbite-avatar-large'       => __( 'Clanbite player avatar — large (default preset)', 'clanbite' ),
		'clanbite-avatar-medium'      => __( 'Clanbite player avatar — medium (default preset)', 'clanbite' ),
		'clanbite-avatar-small'       => __( 'Clanbite player avatar — small (default preset)', 'clanbite' ),
		'clanbite-team-avatar-large'  => __( 'Clanbite team avatar — large (default preset)', 'clanbite' ),
		'clanbite-team-avatar-medium' => __( 'Clanbite team avatar — medium (default preset)', 'clanbite' ),
		'clanbite-team-avatar-small'  => __( 'Clanbite team avatar — small (default preset)', 'clanbite' ),
	);

	$out = array();
	foreach ( $slugs as $slug ) {
		$key         = (string) $slug;
		$out[ $key ] = $labels[ $key ] ?? $key;
	}

	/**
	 * Filters selectable image size slugs for Clanbite avatar settings dropdowns.
	 *
	 * @param array<string, string> $out Slug => label.
	 */
	return (array) apply_filters( 'clanbite_players_image_size_choices_for_settings', $out );
}

/**
 * Ensures an image size slug is registered (or `full`) before saving settings.
 *
 * @param string $value    Raw value.
 * @param string $fallback Slug to use when invalid.
 * @return string
 */
function clanbite_players_sanitize_image_size_setting_value( string $value, string $fallback ): string {
	$value    = sanitize_key( $value );
	$fallback = sanitize_key( $fallback );
	$allowed  = array_keys( clanbite_players_get_image_size_choices_for_settings() );

	if ( in_array( $value, $allowed, true ) ) {
		return $value;
	}

	if ( in_array( $fallback, $allowed, true ) ) {
		return $fallback;
	}

	return 'thumbnail';
}

/**
 * Maps a semantic avatar preset to the image size slug from Players settings.
 *
 * @param string $preset One of `large`, `medium`, `small`.
 * @return string Registered size name or `full`.
 */
function clanbite_players_resolve_player_avatar_image_size( string $preset ): string {
	$preset   = sanitize_key( $preset );
	$keys     = array(
		'large'  => 'player_avatar_image_size_large',
		'medium' => 'player_avatar_image_size_medium',
		'small'  => 'player_avatar_image_size_small',
	);
	$defaults = array(
		'large'  => 'clanbite-avatar-large',
		'medium' => 'clanbite-avatar-medium',
		'small'  => 'clanbite-avatar-small',
	);

	$setting_key = $keys[ $preset ] ?? $keys['large'];
	$fallback    = $defaults[ $preset ] ?? $defaults['large'];

	$raw    = '';
	$loader = clanbite()->extensions;
	if ( null !== $loader ) {
		$ext = $loader->get( 'cp_players' );
		if ( $ext instanceof \Kernowdev\Clanbite\Extensions\Players && method_exists( $ext, 'get_setting' ) ) {
			$raw = (string) $ext->get_setting( $setting_key, $fallback );
		}
	}

	$sanitized = clanbite_players_sanitize_image_size_setting_value( $raw, $fallback );

	/**
	 * Filters the resolved image size slug for a player avatar preset.
	 *
	 * @param string $size     Sanitized size slug.
	 * @param string $preset   `large`, `medium`, or `small`.
	 * @param string $raw      Value from settings before sanitization.
	 * @param string $fallback Default slug for this preset.
	 */
	return (string) apply_filters( 'clanbite_players_resolve_player_avatar_image_size', $sanitized, $preset, $raw, $fallback );
}

/**
 * Returns the players display avatar URL.
 *
 * All Clanbite player avatar surfaces should resolve the image URL through this function so third-party
 * code can override output once (XP overlays, rank badges as image, CDN URLs, etc.). Use the optional
 * {@see $context} argument to vary behaviour by surface; it is passed to filters and avatar markup helpers.
 *
 * Pass {@see $avatar_preset} as `large`, `medium`, or `small` to use the image sizes configured under
 * Clanbite → Players (profiles, forums/social feeds, comments/replies). When set, it overrides {@see $size}.
 *
 * @param int          $player_id        The Player/User unique identifier.
 * @param bool         $suppress_filters Disallows filtering of the value.
 * @param string|array $size             Registered size name or array of width and height (ignored when preset is set).
 * @param string       $context          Logical surface key (e.g. `player_avatar_block`, `user_nav`, `notifications`).
 * @param string       $avatar_preset    Optional. `large`, `medium`, or `small` for settings-driven sizes.
 *
 * @return string
 */
function clanbite_players_get_display_avatar( int $player_id = 0, bool $suppress_filters = false, string|array $size = '', string $context = '', string $avatar_preset = '' ): string {
	if ( ! $player_id ) {
		$player_id = get_current_user_id();
	}

	$preset_key        = sanitize_key( $avatar_preset );
	$preset_for_filter = '';

	if ( in_array( $preset_key, array( 'large', 'medium', 'small' ), true ) ) {
		$size              = clanbite_players_resolve_player_avatar_image_size( $preset_key );
		$preset_for_filter = $preset_key;
	} elseif ( '' === $size || ( is_array( $size ) && empty( $size ) ) ) {
		$size              = clanbite_players_resolve_player_avatar_image_size( 'large' );
		$preset_for_filter = 'large';
	}

	$user_avatar = wp_get_attachment_image_url( clanbite_players_get_display_avatar_id( $player_id, $suppress_filters ), $size );

	if ( false === $user_avatar || '' === $user_avatar ) {
		$user_avatar = clanbite_players_get_default_avatar( $player_id );
	}

	if ( $suppress_filters ) {
		return $user_avatar;
	}

	/**
	 * Filters the resolved player avatar image URL after attachment/default resolution.
	 *
	 * Return a URL string; consumers (REST, img[src], notifications) expect a URL, not HTML. Use
	 * {@see clanbite_players_get_player_avatar_img_html()} for markup.
	 *
	 * @param string       $user_avatar      URL.
	 * @param int          $player_id        User ID.
	 * @param string|array $size             Size used for attachment resolution.
	 * @param string       $context          Surface key (empty string when not passed by caller).
	 * @param string       $avatar_preset  `large`, `medium`, `small`, or empty when an explicit size was used.
	 */
	return apply_filters( 'clanbite_players_get_display_avatar', $user_avatar, $player_id, $size, $context, $preset_for_filter );
}

/**
 * Returns the players display avatar attachment identifier.
 *
 * @param int  $player_id The Player/User unique identifier.
 * @param bool $suppress_filters Disallows filtering of the value.
 *
 * @return int The players display avatar attachment identifier.
 */
function clanbite_players_get_display_avatar_id( int $player_id = 0, bool $suppress_filters = false ): int {
	if ( ! $player_id ) {
		$player_id = get_current_user_id();
	}

	$user_avatar_id = get_user_meta( $player_id, 'cp_player_avatar_id', true );

	if ( $suppress_filters ) {
		return $user_avatar_id;
	}

	/**
	 * Allows filtering of the players display avatar attachment ID, this can be bypassed by setting $suppress_filters param
	 * to true.
	 *
	 * @param int $avatar_id Avatar attachment ID (0 when unset).
	 * @param int $player_id Player (user) ID.
	 */
	return apply_filters( 'clanbite_players_get_display_avatar_id', $user_avatar_id, $player_id );
}

/**
 * Builds a single &lt;img&gt; tag for a player avatar using {@see clanbite_players_get_display_avatar()}.
 *
 * Prefer this over hand-built &lt;img&gt; markup in templates and blocks so attribute and inner markup
 * hooks run consistently. Wrap or extend output with {@see clanbite_players_apply_player_avatar_display_markup()}.
 *
 * @param int   $user_id WordPress user ID.
 * @param array $args {
 *     Optional. Arguments.
 *
 *     @type string       $preset           Optional. `large`, `medium`, or `small` (Players settings). Overrides `size` when set.
 *     @type string|array $size             Image size when `preset` is empty. Default resolves to large preset.
 *     @type string       $context          Surface key for URL/img hooks. Default ''.
 *     @type string       $class            Class attribute (full string). Default `clanbite-player-avatar__img`.
 *     @type string       $alt              Alt text. Default from display name + translated “player avatar”.
 *     @type bool         $suppress_filters Same as {@see clanbite_players_get_display_avatar()}. Default false.
 *     @type string       $loading          `loading` attribute. Default `lazy`. Use empty string to omit.
 *     @type string       $decoding         `decoding` attribute. Default `async`. Use empty string to omit.
 *     @type int          $width            If positive, output width attribute.
 *     @type int          $height           If positive, output height attribute.
 *     @type bool         $presentational   When true, `alt=""` and `aria-hidden="true"` (e.g. avatar inside a named button or link with visible text).
 * }
 * @return string HTML fragment or empty string when no URL resolves.
 */
function clanbite_players_get_player_avatar_img_html( int $user_id, array $args = array() ): string {
	if ( $user_id <= 0 ) {
		return '';
	}

	$defaults = array(
		'preset'           => '',
		'size'             => '',
		'context'          => '',
		'class'            => 'clanbite-player-avatar__img',
		'alt'              => '',
		'suppress_filters' => false,
		'loading'          => 'lazy',
		'decoding'         => 'async',
		'width'            => 0,
		'height'           => 0,
		'presentational'   => false,
	);

	$args = wp_parse_args( $args, $defaults );

	$presentational = (bool) $args['presentational'];

	$preset_arg = sanitize_key( (string) $args['preset'] );
	if ( in_array( $preset_arg, array( 'large', 'medium', 'small' ), true ) ) {
		$url = clanbite_players_get_display_avatar( $user_id, (bool) $args['suppress_filters'], '', (string) $args['context'], $preset_arg );
	} else {
		$size_arg = $args['size'];
		if ( '' === $size_arg || ( is_array( $size_arg ) && empty( $size_arg ) ) ) {
			$size_arg = clanbite_players_resolve_player_avatar_image_size( 'large' );
		}
		$url = clanbite_players_get_display_avatar( $user_id, (bool) $args['suppress_filters'], $size_arg, (string) $args['context'], '' );
	}

	if ( '' === $url ) {
		return '';
	}

	if ( ! $presentational && '' === $args['alt'] && function_exists( 'clanbite_players_get_display_name' ) ) {
		$args['alt'] = sprintf(
			/* translators: %s: Player display name. */
			__( '%s player avatar', 'clanbite' ),
			clanbite_players_get_display_name( $user_id )
		);
	}

	/**
	 * Fires before the player avatar &lt;img&gt; is built (URL is resolved).
	 *
	 * @param int    $user_id User ID.
	 * @param array  $args    Arguments passed to {@see clanbite_players_get_player_avatar_img_html()}.
	 * @param string $url     Avatar URL.
	 */
	do_action( 'clanbite_player_avatar_img_before', $user_id, $args, $url );

	$attr = array(
		'class'    => (string) $args['class'],
		'src'      => $url,
		'alt'      => (string) $args['alt'],
		'loading'  => (string) $args['loading'],
		'decoding' => (string) $args['decoding'],
	);

	$w = (int) $args['width'];
	$h = (int) $args['height'];
	if ( $w > 0 ) {
		$attr['width'] = (string) $w;
	}
	if ( $h > 0 ) {
		$attr['height'] = (string) $h;
	}

	/**
	 * Filters attributes for the player avatar &lt;img&gt; element before the tag is built.
	 *
	 * @param array  $attr    Attribute name => value. Omitted or empty values are skipped (except alt may be empty).
	 * @param int    $user_id User ID.
	 * @param array  $args    Arguments from {@see clanbite_players_get_player_avatar_img_html()}.
	 * @param string $url     Avatar URL.
	 */
	$attr = apply_filters( 'clanbite_players_player_avatar_img_attributes', $attr, $user_id, $args, $url );

	if ( $presentational ) {
		$attr['alt']         = '';
		$attr['aria-hidden'] = 'true';
	}

	$parts = array();
	foreach ( $attr as $name => $value ) {
		$name = sanitize_key( (string) $name );
		if ( '' === $name ) {
			continue;
		}
		if ( '' === $value && 'alt' !== $name ) {
			continue;
		}
		if ( 'src' === $name ) {
			$parts[] = sprintf( 'src="%s"', esc_url( (string) $value ) );
			continue;
		}
		$parts[] = sprintf( '%s="%s"', esc_attr( $name ), esc_attr( (string) $value ) );
	}

	$html = '<img ' . implode( ' ', $parts ) . ' />';

	/**
	 * Fires after the player avatar &lt;img&gt; HTML is built.
	 *
	 * @param int    $user_id User ID.
	 * @param array  $args    Arguments from {@see clanbite_players_get_player_avatar_img_html()}.
	 * @param string $url     Avatar URL.
	 * @param string $html    Built &lt;img&gt; markup.
	 */
	do_action( 'clanbite_player_avatar_img_after', $user_id, $args, $url, $html );

	/**
	 * Filters the player avatar &lt;img&gt; HTML fragment.
	 *
	 * @param string $html    Markup.
	 * @param int    $user_id User ID.
	 * @param array  $args    Arguments from {@see clanbite_players_get_player_avatar_img_html()}.
	 * @param string $url     Avatar URL.
	 */
	return (string) apply_filters( 'clanbite_players_player_avatar_img_html', $html, $user_id, $args, $url );
}

/**
 * Filters inner avatar markup (image, placeholder, or empty-state image) for display.
 *
 * Use this to wrap the avatar with rank badges, XP bars, or extra layout. Applies
 * {@see 'clanbite_players_player_avatar_display_markup'}.
 *
 * @param string $inner   HTML inside the avatar area (before profile link wrapping, if any).
 * @param int    $user_id User ID.
 * @param array  $args    Optional. Same `context` / `size` keys as passed to image helpers; extra keys allowed.
 * @return string
 */
function clanbite_players_apply_player_avatar_display_markup( string $inner, int $user_id, array $args = array() ): string {
	/**
	 * Filters inner player avatar markup after core builds the image or empty-state fragment.
	 *
	 * @param string $inner   HTML.
	 * @param int    $user_id User ID.
	 * @param array  $args    Context from the caller (e.g. `context`, `size`).
	 */
	return (string) apply_filters( 'clanbite_players_player_avatar_display_markup', $inner, $user_id, $args );
}

/**
 * Splits large player-avatar block output so markup can sit beside the circular clip (not inside it).
 *
 * @param string               $display_inner Full HTML from {@see clanbite_players_apply_player_avatar_display_markup()}.
 * @param int                  $user_id       User ID.
 * @param array<string, mixed> $args          Avatar args (`context`, `preset`, etc.).
 * @return array{
 *     clip_inner: string,
 *     after_clip: string,
 *     avatar_extra_class: string,
 *     rank_overlay_html: string
 * }
 */
function clanbite_players_apply_player_avatar_block_parts( string $display_inner, int $user_id, array $args ): array {
	$defaults = array(
		'clip_inner'         => $display_inner,
		'after_clip'         => '',
		'avatar_extra_class' => '',
		'rank_overlay_html'  => '',
	);
	/**
	 * Filters layout parts for the player avatar block when preset is `large`.
	 *
	 * @param array<string, string> $parts {
	 *     @type string $clip_inner           Markup inside `.clanbite-player-avatar__clip`.
	 *     @type string $after_clip           Sibling markup after the clip (e.g. rank progress).
	 *     @type string $avatar_extra_class   Extra classes on `.clanbite-player-avatar` (space-separated).
	 *     @type string $rank_overlay_html    Rank icons outside the circular clip (sibling in `.clanbite-player-avatar__media`).
	 * }
	 * @param int                  $user_id       User ID.
	 * @param array<string, mixed> $args          Avatar args from the block.
	 * @param string               $display_inner Markup before splitting (same as default `clip_inner`).
	 */
	$filtered = apply_filters( 'clanbite_players_player_avatar_block_parts', $defaults, $user_id, $args, $display_inner );
	if ( ! is_array( $filtered ) ) {
		return $defaults;
	}
	return array(
		'clip_inner'         => (string) ( $filtered['clip_inner'] ?? $display_inner ),
		'after_clip'         => (string) ( $filtered['after_clip'] ?? '' ),
		'avatar_extra_class' => trim( (string) ( $filtered['avatar_extra_class'] ?? '' ) ),
		'rank_overlay_html'  => (string) ( $filtered['rank_overlay_html'] ?? '' ),
	);
}

/**
 * Returns the players display cover.
 *
 * @param int          $player_id The Player/User unique identifier.
 * @param bool         $suppress_filters Disallows filtering of the value.
 * @param string|array $size The image size, either a registered size, or an array of width and height.
 *
 * @return string
 */
function clanbite_players_get_display_cover( int $player_id = 0, bool $suppress_filters = false, string|array $size = 'clanbite-cover' ): string {
	if ( ! $player_id ) {
		$player_id = get_current_user_id();
	}

	$user_cover = wp_get_attachment_image_url( clanbite_players_get_display_cover_id( $player_id, $suppress_filters ), $size );

	if ( false === $user_cover || '' === $user_cover ) {
		$user_cover = clanbite_players_get_default_cover( $player_id );
	}

	if ( $suppress_filters ) {
		return $user_cover;
	}

	/**
	 * Allows filtering of the players display cover, this can be bypassed by setting $suppress_filters param
	 * to true.
	 *
	 * @param string $cover     Cover image URL.
	 * @param int    $player_id Player (user) ID.
	 */
	return apply_filters( 'clanbite_players_get_display_cover', $user_cover, $player_id );
}

/**
 * Returns the players display cover attachment identifier.
 *
 * @param int  $player_id The Player/User unique identifier.
 * @param bool $suppress_filters Disallows filtering of the value.
 *
 * @return int The players display cover attachment identifier.
 */
function clanbite_players_get_display_cover_id( int $player_id = 0, bool $suppress_filters = false ): int {
	if ( ! $player_id ) {
		$player_id = get_current_user_id();
	}

	$user_cover_id = get_user_meta( $player_id, 'cp_player_cover_id', true );

	if ( $suppress_filters ) {
		return $user_cover_id;
	}

	/**
	 * Allows filtering of the players display cover attachment ID, this can be bypassed by setting $suppress_filters param
	 * to true.
	 *
	 * @param int $cover_id  Cover attachment ID (0 when unset).
	 * @param int $player_id Player (user) ID.
	 */
	return apply_filters( 'clanbite_players_get_display_cover_id', $user_cover_id, $player_id );
}

/**
 * Returns the players display cover position X axis.
 *
 * @param int  $player_id The Player/User unique identifier.
 * @param bool $suppress_filters Disallows filtering of the value.
 *
 * @return float The players display cover attachment identifier.
 */
function clanbite_players_get_display_cover_position_x( int $player_id = 0, bool $suppress_filters = false ): float {
	if ( ! $player_id ) {
		$player_id = get_current_user_id();
	}

	$user_cover_position_x = get_user_meta( $player_id, 'cp_player_cover_position_x', true );

	if ( $suppress_filters ) {
		return $user_cover_position_x;
	}

	/**
	 * Allows filtering of the players display cover position on the X axis, this can be bypassed by setting $suppress_filters param
	 * to true.
	 *
	 * @param float $position  Horizontal focal position, 0 to 1.
	 * @param int   $player_id Player (user) ID.
	 */
	return apply_filters( 'clanbite_players_get_display_cover_position_x', $user_cover_position_x, $player_id );
}

/**
 * Returns the players display cover position Y axis.
 *
 * @param int  $player_id The Player/User unique identifier.
 * @param bool $suppress_filters Disallows filtering of the value.
 *
 * @return float The players display cover attachment identifier.
 */
function clanbite_players_get_display_cover_position_y( int $player_id = 0, bool $suppress_filters = false ): float {
	if ( ! $player_id ) {
		$player_id = get_current_user_id();
	}

	$user_cover_position_y = get_user_meta( $player_id, 'cp_player_cover_position_y', true );

	if ( $suppress_filters ) {
		return $user_cover_position_y;
	}

	/**
	 * Allows filtering of the players display cover position on the Y axis, this can be bypassed by setting $suppress_filters param
	 * to true.
	 *
	 * @param float $position  Vertical focal position, 0 to 1.
	 * @param int   $player_id Player (user) ID.
	 */
	return apply_filters( 'clanbite_players_get_display_cover_position_y', $user_cover_position_y, $player_id );
}

/**
 * Returns how far the player zoomed into their cover image (1 shows the whole cover, up to 4).
 *
 * The zoom is applied around the focal point ({@see clanbite_players_get_display_cover_position_x()} and `_y`).
 *
 * @since 1.6.3
 *
 * @param int  $player_id        The Player/User unique identifier (0 for the current user).
 * @param bool $suppress_filters Disallows filtering of the value.
 * @return float Zoom between 1 and 4.
 */
function clanbite_players_get_display_cover_zoom( int $player_id = 0, bool $suppress_filters = false ): float {
	if ( ! $player_id ) {
		$player_id = get_current_user_id();
	}

	$zoom = min( 4.0, max( 1.0, (float) get_user_meta( $player_id, 'cp_player_cover_zoom', true ) ) );

	if ( $suppress_filters ) {
		return $zoom;
	}

	/**
	 * Filters the zoom of a player's cover image.
	 *
	 * @since 1.6.3
	 *
	 * @param float $zoom      Zoom between 1 and 4.
	 * @param int   $player_id Player (user) ID.
	 */
	return (float) apply_filters( 'clanbite_players_get_display_cover_zoom', $zoom, $player_id );
}

/**
 * Returns the players display name.
 *
 * @param int  $player_id The Player/User unique identifier.
 * @param bool $suppress_filters Disallows filtering of the value.
 *
 * @return string
 */
function clanbite_players_get_display_name( int $player_id = 0, bool $suppress_filters = false ): string {
	if ( ! $player_id ) {
		$player_id = get_current_user_id();
	}

	$player = get_userdata( $player_id );

	if ( ! $player ) {
		return '';
	}

	if ( $suppress_filters ) {
		return $player->display_name;
	}

	/**
	 * Allows filtering of the players display name, this can be bypassed by setting $suppress_filters param
	 * to true.
	 *
	 * @param string   $display_name Display name.
	 * @param int      $player_id    Player (user) ID.
	 * @param \WP_User $player       Player user object.
	 */
	return apply_filters( 'clanbite_players_get_display_name', $player->display_name, $player_id, $player );
}

/**
 * Returns the players display tagline.
 *
 * @param int  $player_id The Player/User unique identifier.
 * @param bool $suppress_filters Disallows filtering of the value.
 *
 * @return string
 */
function clanbite_players_get_display_tagline( int $player_id = 0, bool $suppress_filters = false ): string {
	if ( ! $player_id ) {
		$player_id = get_current_user_id();
	}

	$tagline = get_user_meta( $player_id, 'cp_player_tagline', true );

	if ( $suppress_filters ) {
		return $tagline;
	}

	/**
	 * Allows filtering of the players display tagline, this can be bypassed by setting $suppress_filters param
	 * to true.
	 *
	 * @param string $tagline   Profile tagline.
	 * @param int    $player_id Player (user) ID.
	 */
	return apply_filters( 'clanbite_players_get_display_tagline', $tagline, $player_id );
}

/**
 * Returns the players display bio/description.
 *
 * @param int  $player_id The Player/User unique identifier.
 * @param bool $suppress_filters Disallows filtering of the value.
 *
 * @return string
 */
function clanbite_players_get_display_bio( int $player_id = 0, bool $suppress_filters = false ): string {
	if ( ! $player_id ) {
		$player_id = get_current_user_id();
	}

	$bio = get_user_meta( $player_id, 'cp_player_bio', true );

	if ( $suppress_filters ) {
		return $bio;
	}

	/**
	 * Allows filtering of the players display bio, this can be bypassed by setting $suppress_filters param
	 * to true.
	 *
	 * @param string $bio       Profile description.
	 * @param int    $player_id Player (user) ID.
	 */
	return apply_filters( 'clanbite_players_get_display_bio', $bio, $player_id );
}

/**
 * Returns the players display website.
 *
 * @param int  $player_id The Player/User unique identifier.
 * @param bool $suppress_filters Disallows filtering of the value.
 *
 * @return string
 */
function clanbite_players_get_display_website( int $player_id = 0, bool $suppress_filters = false ): string {
	if ( ! $player_id ) {
		$player_id = get_current_user_id();
	}

	$website = get_user_meta( $player_id, 'cp_player_website', true );

	if ( $suppress_filters ) {
		return $website;
	}

	/**
	 * Allows filtering of the players display website, this can be bypassed by setting $suppress_filters param
	 * to true.
	 *
	 * @param string $website   Profile website.
	 * @param int    $player_id Player (user) ID.
	 */
	return apply_filters( 'clanbite_players_get_display_website', $website, $player_id );
}

/**
 * Meta key for a player social profile field (user meta).
 *
 * @param string $slug Sanitized key from {@see clanbite_players_get_social_profile_field_definitions()}.
 * @return string
 */
function clanbite_players_social_profile_meta_key( string $slug ): string {
	return 'cp_player_social_' . sanitize_key( $slug );
}

/**
 * Registered social profile fields for player settings and user meta.
 *
 * Keys are stable slugs (`facebook`, `x`, …). Extend or replace fields via
 * {@see 'clanbite_players_social_profile_field_definitions'}.
 *
 * @return array<string, array{label: string, placeholder: string}>
 */
function clanbite_players_get_social_profile_field_definitions(): array {
	$definitions = array(
		'facebook'  => array(
			'label'       => __( 'Facebook', 'clanbite' ),
			'placeholder' => __( 'Profile URL or username', 'clanbite' ),
		),
		'x'         => array(
			'label'       => __( 'X', 'clanbite' ),
			'placeholder' => __( 'Profile URL or @handle', 'clanbite' ),
		),
		'instagram' => array(
			'label'       => __( 'Instagram', 'clanbite' ),
			'placeholder' => __( 'Profile URL or @handle', 'clanbite' ),
		),
		'youtube'   => array(
			'label'       => __( 'YouTube', 'clanbite' ),
			'placeholder' => __( 'Channel URL or @handle', 'clanbite' ),
		),
		'twitch'    => array(
			'label'       => __( 'Twitch', 'clanbite' ),
			'placeholder' => __( 'Channel URL or username', 'clanbite' ),
		),
		'discord'   => array(
			'label'       => __( 'Discord', 'clanbite' ),
			'placeholder' => __( 'Username, invite, or server link', 'clanbite' ),
		),
		'steam'     => array(
			'label'       => __( 'Steam', 'clanbite' ),
			'placeholder' => __( 'Profile URL or custom ID', 'clanbite' ),
		),
		'linkedin'  => array(
			'label'       => __( 'LinkedIn', 'clanbite' ),
			'placeholder' => __( 'Profile URL', 'clanbite' ),
		),
		'tiktok'    => array(
			'label'       => __( 'TikTok', 'clanbite' ),
			'placeholder' => __( 'Profile URL or @handle', 'clanbite' ),
		),
		'bluesky'   => array(
			'label'       => __( 'Bluesky', 'clanbite' ),
			'placeholder' => __( 'Profile URL or handle', 'clanbite' ),
		),
		'reddit'    => array(
			'label'       => __( 'Reddit', 'clanbite' ),
			'placeholder' => __( 'Profile URL or u/username', 'clanbite' ),
		),
		'github'    => array(
			'label'       => __( 'GitHub', 'clanbite' ),
			'placeholder' => __( 'Profile URL or username', 'clanbite' ),
		),
		'mastodon'  => array(
			'label'       => __( 'Mastodon', 'clanbite' ),
			'placeholder' => __( 'Full profile URL (@user@instance)', 'clanbite' ),
		),
	);

	/**
	 * Filters social profile fields shown in player settings and registered as user meta.
	 *
	 * @param array<string, array{label: string, placeholder: string}> $definitions Slug => label and placeholder.
	 */
	return (array) apply_filters( 'clanbite_players_social_profile_field_definitions', $definitions );
}

/**
 * Sanitizes a stored social profile field (URL, @handle, or plain text).
 *
 * @param mixed $value Raw input (stringable).
 * @return string
 */
function clanbite_players_sanitize_social_profile_value( $value ): string {
	$value = wp_strip_all_tags( (string) $value );
	$value = sanitize_text_field( $value );

	if ( function_exists( 'mb_substr' ) ) {
		$value = mb_substr( $value, 0, 500 );
	} else {
		$value = substr( $value, 0, 500 );
	}

	return $value;
}

/**
 * Returns a single social profile value for display or editing.
 *
 * @param string $slug            Key from {@see clanbite_players_get_social_profile_field_definitions()}.
 * @param int    $player_id       User ID.
 * @param bool   $suppress_filters When true, skip {@see 'clanbite_players_get_display_social'}.
 * @return string
 */
function clanbite_players_get_display_social( string $slug, int $player_id = 0, bool $suppress_filters = false ): string {
	$slug = sanitize_key( $slug );
	if ( '' === $slug ) {
		return '';
	}

	$definitions = clanbite_players_get_social_profile_field_definitions();
	if ( ! isset( $definitions[ $slug ] ) ) {
		return '';
	}

	if ( ! $player_id ) {
		$player_id = get_current_user_id();
	}

	$value = (string) get_user_meta( $player_id, clanbite_players_social_profile_meta_key( $slug ), true );

	if ( $suppress_filters ) {
		return $value;
	}

	/**
	 * Filters a player’s social profile field after reading user meta.
	 *
	 * @param string $value     Stored value.
	 * @param string $slug      Field slug (e.g. `facebook`, `x`).
	 * @param int    $player_id User ID.
	 */
	return (string) apply_filters( 'clanbite_players_get_display_social', $value, $slug, $player_id );
}

/**
 * Turns a stored social profile value (URL, @handle, username, etc.) into an absolute https URL.
 *
 * Used by the Player Social Links block and available to extensions. Unknown or invalid input
 * returns an empty string.
 *
 * @param string $slug Field slug from {@see clanbite_players_get_social_profile_field_definitions()}.
 * @param string $raw  Stored value (trimmed internally).
 * @return string Escaped-safe absolute URL, or empty string.
 */
function clanbite_players_normalize_social_profile_link_url( string $slug, string $raw ): string {
	$raw = trim( $raw );
	if ( '' === $raw ) {
		return '';
	}

	$slug = sanitize_key( $slug );
	if ( '' === $slug ) {
		return '';
	}

	if ( preg_match( '#^https?://#i', $raw ) ) {
		$u = esc_url_raw( $raw );
		return is_string( $u ) && '' !== $u ? $u : '';
	}

	if ( str_starts_with( $raw, '//' ) ) {
		$u = esc_url_raw( 'https:' . $raw );
		return is_string( $u ) && '' !== $u ? $u : '';
	}

	if ( preg_match( '#^www\.#i', $raw ) ) {
		$u = esc_url_raw( 'https://' . $raw );
		return is_string( $u ) && '' !== $u ? $u : '';
	}

	$handle = preg_replace( '/^@+/u', '', $raw );
	$handle = trim( $handle );
	if ( '' === $handle ) {
		return '';
	}

	$u = '';

	switch ( $slug ) {
		case 'facebook':
			$u = esc_url_raw( 'https://www.facebook.com/' . rawurlencode( $handle ) );
			break;
		case 'x':
			$u = esc_url_raw( 'https://x.com/' . rawurlencode( ltrim( $handle, '@' ) ) );
			break;
		case 'instagram':
			$u = esc_url_raw( 'https://www.instagram.com/' . rawurlencode( ltrim( $handle, '@' ) ) . '/' );
			break;
		case 'youtube':
			if ( preg_match( '/^UC[\w-]{10,}$/', $handle ) ) {
				$u = esc_url_raw( 'https://www.youtube.com/channel/' . rawurlencode( $handle ) );
			} else {
				$h = ltrim( $handle, '@' );
				$u = esc_url_raw( 'https://www.youtube.com/@' . rawurlencode( $h ) );
			}
			break;
		case 'twitch':
			$u = esc_url_raw( 'https://www.twitch.tv/' . rawurlencode( $handle ) );
			break;
		case 'discord':
			if ( preg_match( '#discord\.gg/#i', $raw ) || preg_match( '#discord(?:app)?\.com/invite/#i', $raw ) ) {
				$pref = preg_match( '#^https?://#i', $raw ) ? $raw : 'https://' . $raw;
				$u    = esc_url_raw( $pref );
			} else {
				$u = esc_url_raw( 'https://discord.gg/' . rawurlencode( $handle ) );
			}
			break;
		case 'steam':
			if ( preg_match( '/^\d+$/', $handle ) ) {
				$u = esc_url_raw( 'https://steamcommunity.com/profiles/' . $handle );
			} else {
				$u = esc_url_raw( 'https://steamcommunity.com/id/' . rawurlencode( $handle ) );
			}
			break;
		case 'linkedin':
			$u = esc_url_raw( 'https://www.linkedin.com/in/' . rawurlencode( $handle ) . '/' );
			break;
		case 'tiktok':
			$h = ltrim( $handle, '@' );
			$u = esc_url_raw( 'https://www.tiktok.com/@' . rawurlencode( $h ) );
			break;
		case 'bluesky':
			if ( str_contains( $handle, '/' ) ) {
				return '';
			}
			$u = esc_url_raw( 'https://bsky.app/profile/' . rawurlencode( $handle ) );
			break;
		case 'reddit':
			$h = preg_replace( '#^/?u/#i', '', $handle );
			$h = ltrim( $h, '/' );
			$u = esc_url_raw( 'https://www.reddit.com/user/' . rawurlencode( $h ) . '/' );
			break;
		case 'github':
			$u = esc_url_raw( 'https://github.com/' . rawurlencode( $handle ) );
			break;
		case 'mastodon':
			if ( preg_match( '/^@?([^@\s]+)@([^@\s/]+)$/', $handle, $m ) ) {
				$u = esc_url_raw( 'https://' . $m[2] . '/@' . rawurlencode( $m[1] ) );
			} else {
				$u = '';
			}
			break;
		default:
			$u = '';
			break;
	}

	return is_string( $u ) && '' !== $u ? $u : '';
}

/**
 * Resolved public link URL for a player social field (reads meta + normalizes).
 *
 * @param string $slug      Field slug.
 * @param int    $player_id WordPress user ID.
 * @return string Absolute URL or empty when unset / invalid.
 */
function clanbite_players_get_social_profile_link_url( string $slug, int $player_id ): string {
	if ( $player_id < 1 ) {
		return '';
	}

	$raw = clanbite_players_get_display_social( $slug, $player_id, true );
	$url = clanbite_players_normalize_social_profile_link_url( $slug, $raw );

	/**
	 * Filters the final social profile link URL for a player.
	 *
	 * @param string $url       URL or empty.
	 * @param string $slug      Field slug.
	 * @param int    $player_id User ID.
	 * @param string $raw       Raw meta value.
	 */
	return (string) apply_filters( 'clanbite_players_social_profile_link_url', $url, $slug, $player_id, $raw );
}

/**
 * Inline SVG icon markup for a social profile slug (for the Player Social Links block).
 *
 * @param string $slug Field slug.
 * @return string SVG element HTML (safe, no user input).
 */
function clanbite_players_get_social_profile_svg_icon( string $slug ): string {
	$slug = sanitize_key( $slug );

	$wrap = static function ( string $path_d ): string {
		return '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="24" height="24" fill="currentColor" class="clanbite-player-social-links__svg" aria-hidden="true" focusable="false"><path d="' . $path_d . '"/></svg>';
	};

	$paths = array(
		'facebook'  => 'M9.101 23.691v-7.98H6.627v-3.667h2.474v-1.58c0-4.085 1.848-5.978 5.858-5.978.401 0 .955.042 1.468.103a8.68 8.68 0 0 1 1.141.195v3.325a8.623 8.623 0 0 0-.653-.036 26.805 26.805 0 0 0-.733-.009c-.707 0-1.259.096-1.675.309a1.686 1.686 0 0 0-.679.622c-.258.42-.374.995-.374 1.752v1.297h3.919l-.386 2.103-.287 1.564h-3.246v8.245C19.396 23.238 24 18.179 24 12.044c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.628 3.874 10.35 9.101 11.647Z',
		'x'         => 'M18.901 1.153h3.68l-8.04 9.19L24 22.846h-7.406l-5.8-7.584-6.638 7.584H.474l8.6-9.83L0 1.154h7.594l5.243 6.932ZM17.61 20.644h2.039L6.486 3.24H4.298Z',
		'instagram' => 'M7.0301.084c-1.2768.0602-2.1487.264-2.911.5634-.7888.3075-1.4575.72-2.1228 1.3877-.6652.6677-1.075 1.3368-1.3802 2.127-.2954.7638-.4956 1.6365-.552 2.914-.0564 1.2775-.0689 1.6882-.0626 4.947.0062 3.2586.0206 3.6671.0825 4.9473.061 1.2765.264 2.1482.5635 2.9107.308.7889.72 1.4573 1.388 2.1228.6679.6655 1.3365 1.0743 2.1285 1.38.7632.295 1.6361.4961 2.9134.552 1.2773.056 1.6884.069 4.9462.0627 3.2578-.0062 3.668-.0207 4.9478-.0814 1.28-.0607 2.147-.2652 2.9098-.5633.7889-.3086 1.4578-.72 2.1228-1.3881.665-.6682 1.0745-1.3378 1.3795-2.1284.2957-.7632.4966-1.636.552-2.9124.056-1.2809.0692-1.6898.063-4.948-.0063-3.2583-.021-3.6668-.0817-4.9465-.0607-1.2797-.264-2.1487-.5633-2.9117-.3084-.7889-.72-1.4568-1.3876-2.1228C21.2982 1.33 20.628.9208 19.8378.6165 19.074.321 18.2017.1197 16.9244.0645 15.6471.0093 15.236-.005 11.977.0014 8.718.0076 8.31.0215 7.0301.0839m.1402 21.6932c-1.17-.0509-1.8053-.2453-2.2287-.408-.5606-.216-.96-.4771-1.3819-.895-.422-.4178-.6811-.8186-.9-1.378-.1644-.4234-.3624-1.058-.4171-2.228-.0595-1.2645-.072-1.6442-.079-4.848-.007-3.2037.0053-3.583.0607-4.848.05-1.169.2456-1.805.408-2.2282.216-.5613.4762-.96.895-1.3816.4188-.4217.8184-.6814 1.3783-.9003.423-.1651 1.0575-.3614 2.227-.4171 1.2655-.06 1.6447-.072 4.848-.079 3.2033-.007 3.5835.005 4.8495.0608 1.169.0508 1.8053.2445 2.228.408.5608.216.96.4754 1.3816.895.4217.4194.6816.8176.9005 1.3787.1653.4217.3617 1.056.4169 2.2263.0602 1.2655.0739 1.645.0796 4.848.0058 3.203-.0055 3.5834-.061 4.848-.051 1.17-.245 1.8055-.408 2.2294-.216.5604-.4763.96-.8954 1.3814-.419.4215-.8181.6811-1.3783.9-.4224.1649-1.0577.3617-2.2262.4174-1.2656.0595-1.6448.072-4.8493.079-3.2045.007-3.5825-.006-4.848-.0608M16.953 5.5864A1.44 1.44 0 1 0 18.39 4.144a1.44 1.44 0 0 0-1.437 1.4424M5.8385 12.012c.0067 3.4032 2.7706 6.1557 6.173 6.1493 3.4026-.0065 6.157-2.7701 6.1506-6.1733-.0065-3.4032-2.771-6.1565-6.174-6.1498-3.403.0067-6.156 2.771-6.1496 6.1738M8 12.0077a4 4 0 1 1 4.008 3.9921A3.9996 3.9996 0 0 1 8 12.0077',
		'youtube'   => 'M23.498 6.186a3.016 3.016 0 0 0-2.122-2.136C19.505 3.545 12 3.545 12 3.545s-7.505 0-9.377.505A3.017 3.017 0 0 0 .502 6.186C0 8.07 0 12 0 12s0 3.93.502 5.814a3.016 3.016 0 0 0 2.122 2.136c1.871.505 9.376.505 9.376.505s7.505 0 9.377-.505a3.015 3.015 0 0 0 2.122-2.136C24 15.93 24 12 24 12s0-3.93-.502-5.814zM9.545 15.568V8.432L15.818 12l-6.273 3.568z',
		'twitch'    => 'M11.571 4.714h1.715v5.143H11.57zm4.715 0H18v5.143h-1.714zM6 0L1.714 4.286v15.428h5.143V24l4.286-4.286h3.428L22.286 12V0zm14.571 11.143l-3.428 3.428h-3.429l-3 3v-3H6.857V1.714h13.714Z',
		'discord'   => 'M20.317 4.3698a19.7913 19.7913 0 00-4.8851-1.5152.0741.0741 0 00-.0785.0371c-.211.3753-.4447.8648-.6083 1.2495-1.8447-.2762-3.68-.2762-5.4868 0-.1636-.3933-.4058-.8742-.6177-1.2495a.077.077 0 00-.0785-.037 19.7363 19.7363 0 00-4.8852 1.515.0699.0699 0 00-.0321.0277C.5334 9.0458-.319 13.5799.0992 18.0578a.0824.0824 0 00.0312.0561c2.0528 1.5076 4.0413 2.4228 5.9929 3.0294a.0777.0777 0 00.0842-.0276c.4616-.6304.8731-1.2952 1.226-1.9942a.076.076 0 00-.0416-.1057c-.6528-.2476-1.2743-.5495-1.8722-.8923a.077.077 0 01-.0076-.1277c.1258-.0943.2517-.1923.3718-.2914a.0743.0743 0 01.0776-.0105c3.9278 1.7933 8.18 1.7933 12.0614 0a.0739.0739 0 01.0785.0095c.1202.099.246.1981.3728.2924a.077.077 0 01-.0066.1276 12.2986 12.2986 0 01-1.873.8914.0766.0766 0 00-.0407.1067c.3604.698.7719 1.3628 1.225 1.9932a.076.076 0 00.0842.0286c1.961-.6067 3.9495-1.5219 6.0023-3.0294a.077.077 0 00.0313-.0552c.5004-5.177-.8382-9.6739-3.5485-13.6604a.061.061 0 00-.0312-.0286zM8.02 15.3312c-1.1825 0-2.1569-1.0857-2.1569-2.419 0-1.3332.9555-2.4189 2.157-2.4189 1.2108 0 2.1757 1.0952 2.1568 2.419 0 1.3332-.9555 2.4189-2.1569 2.4189zm7.9748 0c-1.1825 0-2.1569-1.0857-2.1569-2.419 0-1.3332.9554-2.4189 2.1569-2.4189 1.2108 0 2.1757 1.0952 2.1568 2.419 0 1.3332-.946 2.4189-2.1568 2.4189Z',
		'steam'     => 'M11.979 0C5.678 0 .511 4.86.022 11.037l6.432 2.658c.545-.371 1.203-.59 1.912-.59.063 0 .125.004.188.006l2.861-4.142V8.91c0-2.495 2.028-4.524 4.524-4.524 2.494 0 4.524 2.031 4.524 4.527s-2.03 4.525-4.524 4.525h-.105l-4.076 2.911c0 .052.004.105.004.159 0 1.875-1.515 3.396-3.39 3.396-1.635 0-3.016-1.173-3.331-2.727L.436 15.27C1.862 20.307 6.486 24 11.979 24c6.627 0 11.999-5.373 11.999-12S18.605 0 11.979 0zM7.54 18.21l-1.473-.61c.262.543.714.999 1.314 1.25 1.297.539 2.793-.076 3.332-1.375.263-.63.264-1.319.005-1.949s-.75-1.121-1.377-1.383c-.624-.26-1.29-.249-1.878-.03l1.523.63c.956.4 1.409 1.5 1.009 2.455-.397.957-1.497 1.41-2.454 1.012H7.54zm11.415-9.303c0-1.662-1.353-3.015-3.015-3.015-1.665 0-3.015 1.353-3.015 3.015 0 1.665 1.35 3.015 3.015 3.015 1.663 0 3.015-1.35 3.015-3.015zm-5.273-.005c0-1.252 1.013-2.266 2.265-2.266 1.249 0 2.266 1.014 2.266 2.266 0 1.251-1.017 2.265-2.266 2.265-1.253 0-2.265-1.014-2.265-2.265z',
		'linkedin'  => 'M20.447 20.452h-3.554v-5.569c0-1.328-.027-3.037-1.852-3.037-1.853 0-2.136 1.445-2.136 2.939v5.667H9.351V9h3.414v1.561h.046c.477-.9 1.637-1.85 3.37-1.85 3.601 0 4.267 2.37 4.267 5.455v6.286zM5.337 7.433c-1.144 0-2.063-.926-2.063-2.065 0-1.138.92-2.063 2.063-2.063 1.14 0 2.064.925 2.064 2.063 0 1.139-.925 2.065-2.064 2.065zm1.782 13.019H3.555V9h3.564v11.452zM22.225 0H1.771C.792 0 0 .774 0 1.729v20.542C0 23.227.792 24 1.771 24h20.451C23.2 24 24 23.227 24 22.271V1.729C24 .774 23.2 0 22.222 0h.003z',
		'tiktok'    => 'M12.525.02c1.31-.02 2.61-.01 3.91-.02.08 1.53.63 3.09 1.75 4.17 1.12 1.11 2.7 1.62 4.24 1.79v4.03c-1.44-.05-2.89-.35-4.2-.97-.57-.26-1.1-.59-1.62-.93-.01 2.92.01 5.84-.02 8.75-.08 1.4-.54 2.79-1.35 3.94-1.31 1.92-3.58 3.17-5.91 3.21-1.43.08-2.86-.31-4.08-1.03-2.02-1.19-3.44-3.37-3.65-5.71-.02-.5-.03-1-.01-1.49.18-1.9 1.12-3.72 2.58-4.96 1.66-1.44 3.98-2.13 6.15-1.72.02 1.48-.04 2.96-.04 4.44-.99-.32-2.15-.23-3.02.37-.63.41-1.11 1.04-1.36 1.75-.21.51-.15 1.07-.14 1.61.24 1.64 1.82 3.02 3.5 2.87 1.12-.01 2.19-.66 2.77-1.61.19-.33.4-.67.41-1.06.1-1.79.06-3.57.07-5.36.01-4.03-.01-8.05.02-12.07z',
		'bluesky'   => 'M12 10.8c-1.087-2.114-4.046-6.053-6.798-7.995C2.566.944 1.561 1.266.902 1.565.139 1.908 0 3.08 0 3.768c0 .69.378 5.65.624 6.479.815 2.736 3.713 3.66 6.383 3.364.136-.02.275-.039.415-.056-.138.022-.276.04-.415.056-3.912.58-7.387 2.005-2.83 7.078 5.013 5.19 6.87-1.113 7.823-4.308.953 3.195 2.05 9.271 7.733 4.308 4.267-4.308 1.172-6.498-2.74-7.078a8.741 8.741 0 0 1-.415-.056c.14.017.279.036.415.056 2.67.297 5.568-.628 6.383-3.364.246-.828.624-5.79.624-6.478 0-.69-.139-1.861-.902-2.206-.659-.298-1.664-.62-4.3 1.24C16.046 4.748 13.087 8.687 12 10.8Z',
		'reddit'    => 'M12 0C5.373 0 0 5.373 0 12c0 3.314 1.343 6.314 3.515 8.485l-2.286 2.286C.775 23.225 1.097 24 1.738 24H12c6.627 0 12-5.373 12-12S18.627 0 12 0Zm4.388 3.199c1.104 0 1.999.895 1.999 1.999 0 1.105-.895 2-1.999 2-.946 0-1.739-.657-1.947-1.539v.002c-1.147.162-2.032 1.15-2.032 2.341v.007c1.776.067 3.4.567 4.686 1.363.473-.363 1.064-.58 1.707-.58 1.547 0 2.802 1.254 2.802 2.802 0 1.117-.655 2.081-1.601 2.531-.088 3.256-3.637 5.876-7.997 5.876-4.361 0-7.905-2.617-7.998-5.87-.954-.447-1.614-1.415-1.614-2.538 0-1.548 1.255-2.802 2.803-2.802.645 0 1.239.218 1.712.585 1.275-.79 2.881-1.291 4.64-1.365v-.01c0-1.663 1.263-3.034 2.88-3.207.188-.911.993-1.595 1.959-1.595Zm-8.085 8.376c-.784 0-1.459.78-1.506 1.797-.047 1.016.64 1.429 1.426 1.429.786 0 1.371-.369 1.418-1.385.047-1.017-.553-1.841-1.338-1.841Zm7.406 0c-.786 0-1.385.824-1.338 1.841.047 1.017.634 1.385 1.418 1.385.785 0 1.473-.413 1.426-1.429-.046-1.017-.721-1.797-1.506-1.797Zm-3.703 4.013c-.974 0-1.907.048-2.77.135-.147.015-.241.168-.183.305.483 1.154 1.622 1.964 2.953 1.964 1.33 0 2.47-.81 2.953-1.964.057-.137-.037-.29-.184-.305-.863-.087-1.795-.135-2.769-.135Z',
		'github'    => 'M12 .297c-6.63 0-12 5.373-12 12 0 5.303 3.438 9.8 8.205 11.385.6.113.82-.258.82-.577 0-.285-.01-1.04-.015-2.04-3.338.724-4.042-1.61-4.042-1.61C4.422 18.07 3.633 17.7 3.633 17.7c-1.087-.744.084-.729.084-.729 1.205.084 1.838 1.236 1.838 1.236 1.07 1.835 2.809 1.305 3.495.998.108-.776.417-1.305.76-1.605-2.665-.3-5.466-1.332-5.466-5.93 0-1.31.465-2.38 1.235-3.22-.135-.303-.54-1.523.105-3.176 0 0 1.005-.322 3.3 1.23.96-.267 1.98-.399 3-.405 1.02.006 2.04.138 3 .405 2.28-1.552 3.285-1.23 3.285-1.23.645 1.653.24 2.873.12 3.176.765.84 1.23 1.91 1.23 3.22 0 4.61-2.805 5.625-5.475 5.92.42.36.81 1.096.81 2.22 0 1.606-.015 2.896-.015 3.286 0 .315.21.69.825.57C20.565 22.092 24 17.592 24 12.297c0-6.627-5.373-12-12-12',
		'mastodon'  => 'M23.268 5.313c-.35-2.578-2.617-4.61-5.304-5.004C17.51.242 15.792 0 11.813 0h-.03c-3.98 0-4.835.242-5.288.309C3.882.692 1.496 2.518.917 5.127.64 6.412.61 7.837.661 9.143c.074 1.874.088 3.745.26 5.611.118 1.24.325 2.47.62 3.68.55 2.237 2.777 4.098 4.96 4.857 2.336.792 4.849.923 7.256.38.265-.061.527-.132.786-.213.585-.184 1.27-.39 1.774-.753a.057.057 0 0 0 .023-.043v-1.809a.052.052 0 0 0-.02-.041.053.053 0 0 0-.046-.01 20.282 20.282 0 0 1-4.709.545c-2.73 0-3.463-1.284-3.674-1.818a5.593 5.593 0 0 1-.319-1.433.053.053 0 0 1 .066-.054c1.517.363 3.072.546 4.632.546.376 0 .75 0 1.125-.01 1.57-.044 3.224-.124 4.768-.422.038-.008.077-.015.11-.024 2.435-.464 4.753-1.92 4.989-5.604.008-.145.03-1.52.03-1.67.002-.512.167-3.63-.024-5.545zm-3.748 9.195h-2.561V8.29c0-1.309-.55-1.976-1.67-1.976-1.23 0-1.846.79-1.846 2.35v3.403h-2.546V8.663c0-1.56-.617-2.35-1.848-2.35-1.112 0-1.668.668-1.67 1.977v6.218H4.822V8.102c0-1.31.337-2.35 1.011-3.12.696-.77 1.608-1.164 2.74-1.164 1.311 0 2.302.5 2.962 1.498l.638 1.06.638-1.06c.66-.999 1.65-1.498 2.96-1.498 1.13 0 2.043.395 2.74 1.164.675.77 1.012 1.81 1.012 3.12z',
	);

	$path = $paths[ $slug ] ?? 'M3.9 12c0-1.71 1.39-3.1 3.1-3.1h4V7H7c-2.76 0-5 2.24-5 5s2.24 5 5 5h4v-1.9H7c-1.71 0-3.1-1.39-3.1-3.1zM8 13h8v-2H8v2zm9-6h-4v1.9h4c1.71 0 3.1 1.39 3.1 3.1s-1.39 3.1-3.1 3.1h-4V17h4c2.76 0 5-2.24 5-5s-2.24-5-5-5z';

	$markup = $wrap( $path );

	/**
	 * Filters the inline SVG markup for a social profile icon.
	 *
	 * @param string $markup SVG HTML.
	 * @param string $slug   Field slug.
	 */
	return (string) apply_filters( 'clanbite_players_social_profile_svg_icon', $markup, $slug );
}

/**
 * Returns the players display country.
 *
 * @param int    $player_id The Player/User unique identifier.
 * @param string $return_type The country format to return, either code or name.
 * @param bool   $suppress_filters Disallows filtering of the value.
 *
 * @return string
 */
function clanbite_players_get_display_country( int $player_id = 0, string $return_type = 'name', bool $suppress_filters = false ): string {
	if ( ! $player_id ) {
		$player_id = get_current_user_id();
	}

	$country = get_user_meta( $player_id, 'cp_player_country', true );

	if ( 'name' === $return_type ) {
		$countries = clanbite_players_get_countries();

		foreach ( $countries as $country_code => $country_name ) {
			if ( $country_code === $country ) {
				$country = $country_name;
				break;
			}
		}
	}

	if ( $suppress_filters ) {
		return $country;
	}

	/**
	 * Allows filtering of the players display country, this can be bypassed by setting $suppress_filters param
	 * to true.
	 *
	 * @param string $country     Country name or code, per `$return_type`.
	 * @param int    $player_id   Player (user) ID.
	 * @param string $return_type What was requested: `name` or `code`.
	 */
	return apply_filters( 'clanbite_players_get_display_country', $country, $player_id, $return_type );
}

/**
 * Returns the players display city.
 *
 * @param int  $player_id The Player/User unique identifier.
 * @param bool $suppress_filters Disallows filtering of the value.
 *
 * @return string
 */
function clanbite_players_get_display_city( int $player_id = 0, bool $suppress_filters = false ): string {
	if ( ! $player_id ) {
		$player_id = get_current_user_id();
	}

	$city = get_user_meta( $player_id, 'cp_player_city', true );

	if ( $suppress_filters ) {
		return $city;
	}

	/**
	 * Allows filtering of the players display city, this can be bypassed by setting $suppress_filters param
	 * to true.
	 *
	 * @param string $city      Profile city.
	 * @param int    $player_id Player (user) ID.
	 */
	return apply_filters( 'clanbite_players_get_display_city', $city, $player_id );
}

/**
 * Returns the players display birthday.
 *
 * @param int  $player_id The Player/User unique identifier.
 * @param bool $suppress_filters Disallows filtering of the value.
 *
 * @return string
 */
function clanbite_players_get_display_birthday( int $player_id = 0, bool $suppress_filters = false ): string {
	if ( ! $player_id ) {
		$player_id = get_current_user_id();
	}

	$birthday = get_user_meta( $player_id, 'cp_player_birthday', true );

	if ( $suppress_filters ) {
		return $birthday;
	}

	/**
	 * Allows filtering of the players display birthday, this can be bypassed by setting $suppress_filters param
	 * to true.
	 *
	 * @param string $birthday  Birthday (`Y-m-d`, empty when unset).
	 * @param int    $player_id Player (user) ID.
	 */
	return apply_filters( 'clanbite_players_get_display_birthday', $birthday, $player_id );
}

/**
 * Returns the players display age.
 *
 * @param int  $player_id The Player/User unique identifier.
 * @param bool $suppress_filters Disallows filtering of the value.
 *
 * @return string
 */
function clanbite_players_get_display_age( int $player_id = 0, bool $suppress_filters = false ): string {
	if ( ! $player_id ) {
		$player_id = get_current_user_id();
	}

	$birthday = clanbite_players_get_display_birthday( $player_id, true );

	// Create DateTime objects.
	$birth = new DateTime( $birthday );
	$today = new DateTime();

	// Calculate the difference.
	$age = $today->diff( $birth )->y;

	if ( $suppress_filters ) {
		return (string) $age;
	}

	/**
	 * Allows filtering of the players display age, this can be bypassed by setting $suppress_filters param
	 * to true.
	 *
	 * @param string $age       Computed age.
	 * @param int    $player_id Player (user) ID.
	 * @param string $birthday  Birthday the age was computed from.
	 */
	return (string) apply_filters( 'clanbite_players_get_display_age', $age, $player_id, $birthday );
}

/**
 * Returns the players account first name.
 *
 * @param int  $player_id The Player/User unique identifier.
 * @param bool $suppress_filters Disallows filtering of the value.
 *
 * @return string
 */
function clanbite_players_get_account_firstname( int $player_id = 0, bool $suppress_filters = false ): string {
	if ( ! $player_id ) {
		$player_id = get_current_user_id();
	}

	$first_name = get_user_meta( $player_id, 'cp_player_first_name', true );

	if ( $suppress_filters ) {
		return $first_name;
	}

	/**
	 * Allows filtering of the players account first name, this can be bypassed by setting $suppress_filters param
	 * to true.
	 *
	 * @param string $first_name Account first name.
	 * @param int    $player_id  Player (user) ID.
	 */
	return apply_filters( 'clanbite_players_get_account_firstname', $first_name, $player_id );
}

/**
 * Returns the players account last name.
 *
 * @param int  $player_id The Player/User unique identifier.
 * @param bool $suppress_filters Disallows filtering of the value.
 *
 * @return string
 */
function clanbite_players_get_account_lastname( int $player_id = 0, bool $suppress_filters = false ): string {
	if ( ! $player_id ) {
		$player_id = get_current_user_id();
	}

	$last_name = get_user_meta( $player_id, 'cp_player_last_name', true );

	if ( $suppress_filters ) {
		return $last_name;
	}

	/**
	 * Allows filtering of the players account last name, this can be bypassed by setting $suppress_filters param
	 * to true.
	 *
	 * @param string $last_name Account last name.
	 * @param int    $player_id Player (user) ID.
	 */
	return apply_filters( 'clanbite_players_get_account_lastname', $last_name, $player_id );
}

/**
 * Returns the players account full name.
 *
 * @param int  $player_id The Player/User unique identifier.
 * @param bool $suppress_filters Disallows filtering of the value.
 *
 * @return string
 */
function clanbite_players_get_account_fullname( int $player_id = 0, bool $suppress_filters = false ): string {
	if ( ! $player_id ) {
		$player_id = get_current_user_id();
	}

	$first_name = clanbite_players_get_account_firstname( $player_id, $suppress_filters );
	$last_name  = clanbite_players_get_account_lastname( $player_id, $suppress_filters );
	$full_name  = $first_name . ' ' . $last_name;

	if ( $suppress_filters ) {
		return $full_name;
	}

	/**
	 * Allows filtering of the players account full name, this can be bypassed by setting $suppress_filters param
	 * to true.
	 *
	 * @param string $full_name  Account full name.
	 * @param int    $player_id  Player (user) ID.
	 * @param string $first_name Account first name.
	 * @param string $last_name  Account last name.
	 */
	return apply_filters( 'clanbite_players_get_account_fullname', $full_name, $player_id, $first_name, $last_name );
}

/**
 * Get user navigation menu items for logged-in users.
 *
 * Returns an array of menu items that appear in the user nav dropdown.
 * Third-party plugins can filter this to add, remove, or modify items.
 *
 * @param int $user_id User ID.
 * @return array<int, array{
 *     id: string,
 *     label: string,
 *     url: string,
 *     icon?: string,
 *     group?: string,
 *     class?: string,
 *     target?: string,
 *     priority?: int
 * }> Array of menu items sorted by group and priority.
 */
function clanbite_get_user_nav_menu_items( int $user_id = 0 ): array {
	if ( ! $user_id ) {
		$user_id = get_current_user_id();
	}

	if ( ! $user_id ) {
		return array();
	}

	$profile_url  = function_exists( 'clanbite_get_player_profile_url' )
		? clanbite_get_player_profile_url( $user_id )
		: get_author_posts_url( $user_id );
	$settings_url = home_url( '/players/settings/' );

	$items = array(
		array(
			'id'       => 'profile',
			'label'    => __( 'My Profile', 'clanbite' ),
			'url'      => $profile_url,
			'icon'     => 'admin-users',
			'group'    => 'profile',
			'priority' => 10,
		),
		array(
			'id'       => 'settings',
			'label'    => __( 'Settings', 'clanbite' ),
			'url'      => $settings_url,
			'icon'     => 'admin-generic',
			'group'    => 'profile',
			'priority' => 20,
		),
	);

	if ( function_exists( 'clanbite_get_notifications_url' ) ) {
		$items[] = array(
			'id'       => 'notifications',
			'label'    => __( 'Notifications', 'clanbite' ),
			'url'      => clanbite_get_notifications_url( $user_id ),
			'icon'     => 'bell',
			'group'    => 'profile',
			'priority' => 30,
		);
	}

	if ( current_user_can( 'edit_posts' ) ) {
		$items[] = array(
			'id'       => 'dashboard',
			'label'    => __( 'Dashboard', 'clanbite' ),
			'url'      => admin_url(),
			'icon'     => 'dashboard',
			'group'    => 'admin',
			'priority' => 10,
		);
	}

	$items[] = array(
		'id'       => 'logout',
		'label'    => __( 'Log Out', 'clanbite' ),
		'url'      => wp_logout_url( home_url() ),
		'icon'     => 'exit',
		'group'    => 'logout',
		'class'    => 'clanbite-user-nav__menu-item--danger',
		'priority' => 100,
	);

	/**
	 * Filter user navigation menu items.
	 *
	 * Allows third-party plugins to add, remove, or modify menu items
	 * in the user navigation dropdown.
	 *
	 * @param array $items   Array of menu items.
	 * @param int   $user_id Current user ID.
	 */
	$items = (array) apply_filters( 'clanbite_user_nav_menu_items', $items, $user_id );

	usort(
		$items,
		static function ( $a, $b ) {
			$group_order = array(
				'profile' => 1,
				'teams'   => 2,
				'groups'  => 3,
				'admin'   => 4,
				'logout'  => 99,
			);

			$group_a = $a['group'] ?? 'other';
			$group_b = $b['group'] ?? 'other';

			$order_a = $group_order[ $group_a ] ?? 50;
			$order_b = $group_order[ $group_b ] ?? 50;

			if ( $order_a !== $order_b ) {
				return $order_a <=> $order_b;
			}

			$priority_a = $a['priority'] ?? 10;
			$priority_b = $b['priority'] ?? 10;

			return $priority_a <=> $priority_b;
		}
	);

	return $items;
}

/**
 * Get guest links for the user navigation block.
 *
 * Returns an array of links shown to non-logged-in users (typically Login and Register).
 *
 * @return array<int, array{
 *     id: string,
 *     label: string,
 *     url: string,
 *     style?: string,
 *     target?: string,
 *     priority?: int
 * }> Array of guest links sorted by priority.
 */
function clanbite_get_user_nav_guest_links(): array {
	$links = array(
		array(
			'id'       => 'login',
			'label'    => __( 'Log In', 'clanbite' ),
			'url'      => wp_login_url( get_permalink() ),
			'style'    => 'secondary',
			'priority' => 10,
		),
	);

	if ( get_option( 'users_can_register' ) ) {
		$links[] = array(
			'id'       => 'register',
			'label'    => __( 'Register', 'clanbite' ),
			'url'      => wp_registration_url(),
			'style'    => 'primary',
			'priority' => 20,
		);
	}

	/**
	 * Filter guest navigation links.
	 *
	 * Allows third-party plugins to add, remove, or modify links
	 * shown to non-logged-in users.
	 *
	 * @param array $links Array of guest links.
	 */
	$links = (array) apply_filters( 'clanbite_user_nav_guest_links', $links );

	usort(
		$links,
		static function ( $a, $b ) {
			$priority_a = $a['priority'] ?? 10;
			$priority_b = $b['priority'] ?? 10;
			return $priority_a <=> $priority_b;
		}
	);

	return $links;
}

/**
 * Add a menu item to the user navigation.
 *
 * Helper function for third-party plugins to easily add items.
 *
 * @param array $item Menu item configuration.
 * @return void
 */
function clanbite_add_user_nav_menu_item( array $item ): void {
	add_filter(
		'clanbite_user_nav_menu_items',
		static function ( $items ) use ( $item ) {
			$items[] = $item;
			return $items;
		}
	);
}

/**
 * Remove a menu item from the user navigation by ID.
 *
 * Helper function for third-party plugins to easily remove items.
 *
 * @param string $item_id The ID of the item to remove.
 * @return void
 */
function clanbite_remove_user_nav_menu_item( string $item_id ): void {
	add_filter(
		'clanbite_user_nav_menu_items',
		static function ( $items ) use ( $item_id ) {
			return array_filter(
				$items,
				static function ( $item ) use ( $item_id ) {
					return ( $item['id'] ?? '' ) !== $item_id;
				}
			);
		}
	);
}

/**
 * Get the configured player avatar shape from settings.
 *
 * @return string Avatar shape: 'circle', 'square', or 'hexagon'. Default 'circle'.
 */
function clanbite_players_get_avatar_shape(): string {
	// Get directly from the database to avoid caching issues.
	$all_settings = get_option( 'clanbite_players_settings', array() );
	$shape        = isset( $all_settings['player_avatar_shape'] ) ? $all_settings['player_avatar_shape'] : 'circle';

	// Sanitize and validate.
	$shape = sanitize_key( $shape );
	if ( ! in_array( $shape, array( 'circle', 'square', 'hexagon' ), true ) ) {
		$shape = 'circle';
	}

	/**
	 * Filter the player avatar shape.
	 *
	 * @param string $shape Avatar shape.
	 */
	return (string) apply_filters( 'clanbite_players_avatar_shape', $shape );
}

/**
 * Whether player friendships exist on this site (an add-on such as Social Kit provides them).
 *
 * @return bool
 */
function clanbite_players_friends_available(): bool {
	/**
	 * Filters whether player friendships are available.
	 *
	 * Core has no friendships of its own: an add-on that does (Social Kit) returns true here and answers the two filters below.
	 *
	 * @param bool $available Default false.
	 */
	return (bool) apply_filters( 'clanbite_players_friends_available', false );
}

/**
 * The accepted friends of a player.
 *
 * @param int $user_id Player user ID.
 * @return int[] Friend user IDs (empty when friendships are not available).
 */
function clanbite_players_get_friend_ids( int $user_id ): array {
	if ( $user_id < 1 ) {
		return array();
	}

	/**
	 * Filters the accepted friends of a player.
	 *
	 * @param int[] $friend_ids Friend user IDs. Default empty.
	 * @param int   $user_id    Player user ID.
	 */
	$ids = (array) apply_filters( 'clanbite_players_friend_ids', array(), $user_id );

	return array_values( array_unique( array_filter( array_map( 'absint', $ids ) ) ) );
}

/**
 * Whether two players are accepted friends.
 *
 * @param int $user_a First player user ID.
 * @param int $user_b Second player user ID.
 * @return bool
 */
function clanbite_players_are_friends( int $user_a, int $user_b ): bool {
	if ( $user_a < 1 || $user_b < 1 || $user_a === $user_b ) {
		return false;
	}

	/**
	 * Filters whether two players are accepted friends.
	 *
	 * @param bool $are_friends Default false.
	 * @param int  $user_a      First player user ID.
	 * @param int  $user_b      Second player user ID.
	 */
	return (bool) apply_filters( 'clanbite_players_are_friends', false, $user_a, $user_b );
}
