<?php
/**
 * Centralized avatar rendering helpers.
 *
 * All avatar markup should go through these functions so third-party developers
 * can customize avatar rendering in one place with consistent filters.
 *
 * ## Context Support
 *
 * All filters and actions support context-specific targeting. When you call
 * clanbite_render_avatar() with a 'context' argument (e.g., 'forum', 'social_feed'),
 * additional context-specific filters/actions are fired:
 *
 * ### Context Examples:
 * - 'profile'      - Player/team profile pages
 * - 'forum'        - Forums plugin (topics, replies)
 * - 'social_feed'  - Social Kit posts and comments
 * - 'user_nav'     - User navigation menu
 * - 'notification' - Notification bell/list
 * - 'event'        - Event cards and lists
 * - 'match'        - Match cards
 *
 * ### Start with `clanbite_avatar_data`
 *
 * To change what an avatar *is* (shape, XP ring on/off, rank badge on/off) filter the resolved data. It runs for both
 * render modes (`$args['mode']` is `static` or `interactive`), so a rule for a context applies everywhere that context
 * renders:
 * - clanbite_avatar_data, clanbite_{type}_avatar_data, clanbite_avatar_data_{context}
 *
 * ```php
 * add_filter( 'clanbite_avatar_data_forum', function ( $data ) {
 *     $data['shape']     = 'square';
 *     $data['show_ring'] = false;
 *     return $data;
 * } );
 * ```
 *
 * The class, image-attribute and HTML filters below also run for Interactivity (feed, forums) avatars; the older
 * clanbite_avatar_interactivity_* filters still fire after them.
 *
 * ### Available Context Filters:
 * - clanbite_avatar_html_{context}           - Modify complete HTML for specific context
 * - clanbite_avatar_classes_{context}        - Modify CSS classes for specific context
 * - clanbite_avatar_img_attributes_{context} - Modify <img> attributes for specific context
 *
 * ### Available Context Actions:
 * - clanbite_avatar_overlays_{context}       - Add custom overlays for specific context
 *
 * ### Example Usage:
 *
 * ```php
 * // Only customize forum avatars
 * add_filter( 'clanbite_avatar_html_forum', 'my_forum_avatars', 10, 2 );
 * function my_forum_avatars( $html, $args ) {
 *     // $args['type'] = 'player'
 *     // $args['id'] = user ID
 *     // $args['context'] = 'forum'
 *     return $html; // Return modified markup
 * }
 *
 * // Only add badges to social feed avatars
 * add_action( 'clanbite_avatar_overlays_social_feed', 'my_social_badges' );
 * function my_social_badges( $args ) {
 *     if ( $args['type'] === 'player' ) {
 *         echo '<span class="my-verified-badge">✓</span>';
 *     }
 * }
 * ```
 *
 * @package clanbite
 */

defined( 'ABSPATH' ) || exit;

/**
 * Resolve the avatar shape for an entity type: an explicit override, else the Players / Teams / Groups setting.
 *
 * @param string $type     Avatar type: `player`, `team` or `group`.
 * @param string $override Shape passed by the caller (`circle`, `square`, `hexagon`) or empty.
 * @return string Shape slug.
 */
function clanbite_avatar_resolve_shape( string $type, string $override = '' ): string {
	if ( '' !== $override ) {
		return $override;
	}

	$getter = 'clanbite_' . $type . 's_get_avatar_shape';

	return function_exists( $getter ) ? (string) $getter() : 'circle';
}

/**
 * Filter the resolved avatar data before any markup is built.
 *
 * This is the one place to change what an avatar *is* (its shape, whether the XP ring or rank badge shows, the badge
 * values), independent of where it is rendered and whether the markup is built on the server or by the Interactivity API.
 * Both `clanbite_render_avatar()` and `clanbite_render_avatar_for_interactivity()` call it, so a context-only rule such as
 * "forum avatars are squares without a ring" works for every surface that renders that context.
 *
 * @param array<string, mixed> $data {
 *     Resolved avatar data.
 *
 *     @type string $shape      `circle`, `square` or `hexagon`.
 *     @type bool   $show_ring  Whether the XP ring is drawn.
 *     @type bool   $show_badge Whether the rank badge is drawn.
 *     @type array  $badge      Badge values (static mode only): see {@see clanbite_get_avatar_badge()}.
 * }
 * @param array<string, mixed> $args Render arguments (`type`, `id`, `size`, `context`, `mode`, …). `mode` is `static`
 *                                   (server-rendered markup) or `interactive` (Interactivity API template).
 * @return array<string, mixed> Filtered data.
 */
function clanbite_avatar_filter_data( array $data, array $args ): array {
	/**
	 * Filters the resolved avatar data (shape, ring and badge visibility) for every avatar.
	 *
	 * @param array<string, mixed> $data Resolved data: `shape`, `show_ring`, `show_badge`, and in static mode `badge`.
	 * @param array<string, mixed> $args Render arguments (`type`, `id`, `size`, `context`, `mode`, …).
	 */
	$data = apply_filters( 'clanbite_avatar_data', $data, $args );
	/**
	 * Filters the resolved avatar data for one entity type (`player`, `team`, `group`).
	 *
	 * @param array<string, mixed> $data Resolved data: `shape`, `show_ring`, `show_badge`, and in static mode `badge`.
	 * @param array<string, mixed> $args Render arguments (`type`, `id`, `size`, `context`, `mode`, …).
	 */
	$data = apply_filters( "clanbite_{$args['type']}_avatar_data", $data, $args );
	if ( ! empty( $args['context'] ) ) {
		/**
		 * Filters the resolved avatar data for one context (for example `forum`, `social_feed`, `user_nav`).
		 *
		 * @param array<string, mixed> $data Resolved data: `shape`, `show_ring`, `show_badge`, and in static mode `badge`.
		 * @param array<string, mixed> $args Render arguments (`type`, `id`, `size`, `context`, `mode`, …).
		 */
		$data = apply_filters( "clanbite_avatar_data_{$args['context']}", $data, $args );
	}

	return $data;
}

/**
 * Render a complete avatar with all wrappers, shape classes, and optional overlays.
 *
 * This is the main function that should be used for ALL avatar rendering across
 * the plugin and extensions. It provides a consistent structure and filter points.
 *
 * @param array $args {
 *     Avatar rendering arguments.
 *
 *     @type string $type           Avatar type: 'player', 'team', 'group'. Required.
 *     @type int    $id             Entity ID (user_id, team_id, or group_id). Required.
 *     @type string $size           Size preset: 'large', 'medium', 'small', or custom size slug.
 *     @type string $context        Context identifier for filtering (e.g., 'profile', 'social_feed', 'forums').
 *     @type string $shape          Override shape: 'circle', 'square', 'hexagon'. If empty, uses setting.
 *     @type bool   $link           Whether to wrap in a profile link. Default false.
 *     @type string $link_target    Link target attribute. Default empty.
 *     @type array  $extra_classes  Additional CSS classes for the avatar container.
 *     @type bool|null $show_rank     Show the rank badge (level icon or number). Default null: automatic, shown when the
 *                                    Ranks settings enable it for this avatar's player. False forces it off.
 *     @type bool|null $show_progress Show the XP ring around the avatar. Default null: automatic, shown when a Ranks
 *                                    ladder has "Show progress on avatar" enabled. False forces it off.
 * }
 * @return string HTML markup.
 */
function clanbite_render_avatar( array $args ): string {
	// Shared frame / ring / badge styles, for callers that are not a block (templates, companions).
	wp_enqueue_style( 'clanbite-avatars' );

	$defaults = array(
		'type'          => 'player',
		'id'            => 0,
		'size'          => 'medium',
		'context'       => '',
		'shape'         => '',
		'link'          => false,
		'link_target'   => '',
		'extra_classes' => array(),
		'show_rank'     => null,
		'show_progress' => null,
	);

	$args = wp_parse_args( $args, $defaults );

	// Validate type.
	if ( ! in_array( $args['type'], array( 'player', 'team', 'group' ), true ) ) {
		return '';
	}

	// Validate ID.
	$id = absint( $args['id'] );
	if ( $id <= 0 ) {
		return '';
	}

	$args['mode'] = 'static';

	// Get avatar URL.
	$avatar_url = clanbite_get_avatar_url( $args['type'], $id, $args['size'], $args['context'] );
	if ( empty( $avatar_url ) ) {
		$avatar_url = clanbite_get_default_avatar_url( $args['type'] );
	}

	// Resolved data: shape, plus the XP ring / rank badge (automatic follows the Ranks settings, false switches a part off).
	$badge = clanbite_get_avatar_badge( $args['type'], $id );
	$data  = clanbite_avatar_filter_data(
		array(
			'shape'      => clanbite_avatar_resolve_shape( $args['type'], (string) $args['shape'] ),
			'show_ring'  => false !== $args['show_progress'] && $badge['show_progress'],
			'show_badge' => false !== $args['show_rank'] && $badge['show_badge'],
			'badge'      => $badge,
		),
		$args
	);

	$shape     = (string) $data['shape'];
	$badge     = (array) $data['badge'];
	$has_ring  = (bool) $data['show_ring'];
	$has_badge = (bool) $data['show_badge'];

	// Build CSS classes.
	$classes = array(
		'clanbite-avatar',
		'clanbite-avatar--' . $args['type'],
		'clanbite-avatar--shape-' . $shape,
		'clanbite-avatar--' . sanitize_html_class( $args['size'] ),
	);
	if ( $has_ring ) {
		$classes[] = 'clanbite-avatar--has-ring';
	}
	if ( $has_badge ) {
		$classes[] = 'clanbite-avatar--has-badge';
	}

	if ( ! empty( $args['extra_classes'] ) ) {
		$classes = array_merge( $classes, (array) $args['extra_classes'] );
	}

	/**
	 * Filter avatar container classes.
	 *
	 * @param array  $classes CSS classes.
	 * @param array  $args    Avatar arguments (includes 'context' key).
	 */
	$classes = apply_filters( 'clanbite_avatar_classes', $classes, $args );
	/**
	 * Filters the avatar CSS classes for one entity type (`player`, `team`, `group`).
	 *
	 * Type-specific variant of `clanbite_avatar_classes`.
	 *
	 * @param string[]      $classes CSS classes.
	 * @param array<string, mixed>   $args Avatar arguments (`type`, `id`, `size`, `context`, `shape`, …); see {@see clanbite_render_avatar()}.
	 */
	$classes = apply_filters( "clanbite_{$args['type']}_avatar_classes", $classes, $args );

	// Context-specific filter (e.g., 'clanbite_avatar_classes_forum', 'clanbite_avatar_classes_social_feed').
	if ( ! empty( $args['context'] ) ) {
		/**
		 * Filters the avatar CSS classes for one context (for example `forum`, `social_feed`).
		 *
		 * Context-specific variant of `clanbite_avatar_classes`.
		 *
		 * @param string[]      $classes CSS classes.
		 * @param array<string, mixed>   $args Avatar arguments (`type`, `id`, `size`, `context`, `shape`, …); see {@see clanbite_render_avatar()}.
		 */
		$classes = apply_filters( "clanbite_avatar_classes_{$args['context']}", $classes, $args );
	}

	// Build the avatar markup.
	ob_start();
	?>
	<div class="<?php echo esc_attr( implode( ' ', array_map( 'sanitize_html_class', $classes ) ) ); ?>" data-clanbite-avatar-type="<?php echo esc_attr( (string) $args['type'] ); ?>" data-clanbite-avatar-id="<?php echo esc_attr( (string) $id ); ?>">
		<?php
		/**
		 * Action: Before avatar inner content.
		 *
		 * @param array $args Avatar arguments.
		 */
		do_action( 'clanbite_before_avatar_inner', $args );
		/**
		 * Fires before the inner markup of a type-specific avatar.
		 *
		 * Type-specific variant of `clanbite_before_avatar_inner`.
		 *
		 * @param array<string, mixed> $args Avatar arguments (`type`, `id`, `size`, `context`, `shape`, …); see {@see clanbite_render_avatar()}.
		 */
		do_action( "clanbite_before_{$args['type']}_avatar_inner", $args );
		?>
		
		<div class="clanbite-avatar__media">
			<?php
			// Frame: border, XP ring track and progress arc, drawn in the avatar's shape (markup built from numbers and constants only).
			echo clanbite_avatar_frame_svg( $shape, $has_ring ? array( 'progress' => $badge['progress'] ) : array( 'ring' => false ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			?>
			<?php if ( $args['link'] ) : ?>
				<a href="<?php echo esc_url( clanbite_get_avatar_profile_url( $args['type'], $id ) ); ?>"
					class="clanbite-avatar__link"
					<?php if ( ! empty( $args['link_target'] ) ) : ?>
						target="<?php echo esc_attr( $args['link_target'] ); ?>"
					<?php endif; ?>>
			<?php endif; ?>
			
			<div class="clanbite-avatar__clip">
				<?php
				/**
				 * Filter avatar image attributes.
				 *
				 * @param array  $img_attrs Image attributes.
				 * @param array  $args      Avatar arguments (includes 'context' key).
				 */
				$img_attrs = apply_filters(
					'clanbite_avatar_img_attributes',
					array(
						'src'      => $avatar_url,
						'class'    => 'clanbite-avatar__img',
						'alt'      => clanbite_get_avatar_alt_text( $args['type'], $id ),
						'loading'  => 'lazy',
						'decoding' => 'async',
					),
					$args
				);

				// Context-specific filter (e.g., 'clanbite_avatar_img_attributes_forum').
				if ( ! empty( $args['context'] ) ) {
					/**
					 * Filters the avatar `<img>` attributes for one context.
					 *
					 * Context-specific variant of `clanbite_avatar_img_attributes`.
					 *
					 * @param array<string, string|bool> $img_attrs Attribute map.
					 * @param array<string, mixed>       $args Avatar arguments (`type`, `id`, `size`, `context`, `shape`, …); see {@see clanbite_render_avatar()}.
					 */
					$img_attrs = apply_filters( "clanbite_avatar_img_attributes_{$args['context']}", $img_attrs, $args );
				}
				?>
				<img <?php echo clanbite_build_html_attributes( $img_attrs ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- clanbite_build_html_attributes() escapes every name and value. ?> />
			</div>
			
			<?php if ( $args['link'] ) : ?>
				</a>
			<?php endif; ?>
			
			<?php
			// Rank badge (level icon or number), in the avatar's shape.
			if ( $has_badge ) {
				echo clanbite_avatar_badge_html( $shape, $badge ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped inside clanbite_avatar_badge_html().
			}

			/**
			 * Action: After avatar overlays.
			 *
			 * Use this to add custom overlays (badges, status indicators, etc.).
			 *
			 * @param array $args Avatar arguments (includes 'context' key).
			 */
			do_action( 'clanbite_avatar_overlays', $args );
			/**
			 * Fires when overlays (badges, status) are rendered for a type-specific avatar.
			 *
			 * Type-specific variant of `clanbite_avatar_overlays`.
			 *
			 * @param array<string, mixed> $args Avatar arguments (`type`, `id`, `size`, `context`, `shape`, …); see {@see clanbite_render_avatar()}.
			 */
			do_action( "clanbite_{$args['type']}_avatar_overlays", $args );

			// Context-specific action (e.g., 'clanbite_avatar_overlays_forum').
			if ( ! empty( $args['context'] ) ) {
				/**
				 * Fires when overlays (badges, status) are rendered for an avatar in one context.
				 *
				 * Context-specific variant of `clanbite_avatar_overlays`.
				 *
				 * @param array<string, mixed> $args Avatar arguments (`type`, `id`, `size`, `context`, `shape`, …); see {@see clanbite_render_avatar()}.
				 */
				do_action( "clanbite_avatar_overlays_{$args['context']}", $args );
			}
			?>
		</div>
		
		<?php
		/**
		 * Action: After avatar inner content.
		 *
		 * @param array $args Avatar arguments.
		 */
		do_action( 'clanbite_after_avatar_inner', $args );
		/**
		 * Fires after the inner markup of a type-specific avatar.
		 *
		 * Type-specific variant of `clanbite_after_avatar_inner`.
		 *
		 * @param array<string, mixed> $args Avatar arguments (`type`, `id`, `size`, `context`, `shape`, …); see {@see clanbite_render_avatar()}.
		 */
		do_action( "clanbite_after_{$args['type']}_avatar_inner", $args );
		?>
	</div>
	<?php
	$output = ob_get_clean();

	/**
	 * Filter the complete avatar HTML output.
	 *
	 * This is the main filter point for customizing avatar rendering.
	 * Third-party developers should use this filter to customize ALL avatars
	 * across the site in one place.
	 *
	 * @param string $output Avatar HTML markup.
	 * @param array  $args   Avatar arguments (includes 'context' key for targeting specific locations).
	 */
	$output = apply_filters( 'clanbite_avatar_html', $output, $args );
	/**
	 * Filters the avatar HTML for one entity type (`player`, `team`, `group`).
	 *
	 * Type-specific variant of `clanbite_avatar_html`.
	 *
	 * @param string        $output Avatar markup.
	 * @param array<string, mixed>  $args Avatar arguments (`type`, `id`, `size`, `context`, `shape`, …); see {@see clanbite_render_avatar()}.
	 */
	$output = apply_filters( "clanbite_{$args['type']}_avatar_html", $output, $args );

	// Context-specific filter (e.g., 'clanbite_avatar_html_forum', 'clanbite_avatar_html_social_feed').
	if ( ! empty( $args['context'] ) ) {
		/**
		 * Filters the avatar HTML for one context.
		 *
		 * Context-specific variant of `clanbite_avatar_html`.
		 *
		 * @param string        $output Avatar markup.
		 * @param array<string, mixed>  $args Avatar arguments (`type`, `id`, `size`, `context`, `shape`, …); see {@see clanbite_render_avatar()}.
		 */
		$output = apply_filters( "clanbite_avatar_html_{$args['context']}", $output, $args );
	}

	return $output;
}

/**
 * Get avatar URL for an entity.
 *
 * @param string $type    Entity type: 'player', 'team', 'group'.
 * @param int    $id      Entity ID.
 * @param string $size    Size preset or slug.
 * @param string $context Context identifier.
 * @return string Avatar URL or empty string.
 */
function clanbite_get_avatar_url( string $type, int $id, string $size = 'medium', string $context = '' ): string {
	$url = '';

	switch ( $type ) {
		case 'player':
			if ( function_exists( 'clanbite_players_get_display_avatar' ) ) {
				$url = clanbite_players_get_display_avatar( $id, false, $size, $context, $size );
			}
			break;
		case 'team':
			if ( function_exists( 'clanbite_teams_get_display_team_avatar' ) ) {
				$url = clanbite_teams_get_display_team_avatar( $id, false, $size, $context, $size );
			}
			break;
		case 'group':
			// Groups to be implemented by group plugin.
			/**
			 * Resolves a group's avatar URL.
			 *
			 * Core ships no group implementation; the plugin that owns `cp_group` should return the URL here.
			 *
			 * @param string       $url     Avatar URL (empty by default).
			 * @param int          $id      Group ID.
			 * @param string|int[] $size    Image size slug or dimensions.
			 * @param string       $context Rendering context.
			 */
			$url = apply_filters( 'clanbite_groups_get_display_avatar', '', $id, $size, $context );
			break;
	}

	/**
	 * Filter avatar URL.
	 *
	 * @param string $url     Avatar URL.
	 * @param string $type    Entity type.
	 * @param int    $id      Entity ID.
	 * @param string $size    Size.
	 * @param string $context Context.
	 */
	return apply_filters( 'clanbite_avatar_url', $url, $type, $id, $size, $context );
}

/**
 * Get profile URL for an entity.
 *
 * @param string $type Entity type: 'player', 'team', 'group'.
 * @param int    $id   Entity ID.
 * @return string Profile URL.
 */
function clanbite_get_avatar_profile_url( string $type, int $id ): string {
	$url = '';

	switch ( $type ) {
		case 'player':
			if ( function_exists( 'clanbite_get_player_profile_url' ) ) {
				$url = clanbite_get_player_profile_url( $id );
			}
			break;
		case 'team':
			$team_post = get_post( $id );
			if ( $team_post instanceof \WP_Post && 'clanbite_team' === $team_post->post_type ) {
				if ( function_exists( 'clanbite_teams_get_team_profile_url_for_slug' ) ) {
					$url = clanbite_teams_get_team_profile_url_for_slug( $team_post->post_name );
				}
				if ( '' === $url ) {
					$url = (string) get_permalink( $team_post );
				}
			}
			break;
		case 'group':
			/**
			 * Resolves a group's profile URL for avatar links.
			 *
			 * Core ships no group implementation; the plugin that owns `cp_group` should return the URL here.
			 *
			 * @param string $url Profile URL (empty by default).
			 * @param int    $id  Group ID.
			 */
			$url = apply_filters( 'clanbite_groups_get_profile_url', '', $id );
			break;
	}

	/**
	 * Filter profile URL.
	 *
	 * @param string $url  Profile URL.
	 * @param string $type Entity type.
	 * @param int    $id   Entity ID.
	 */
	return apply_filters( 'clanbite_avatar_profile_url', $url, $type, $id );
}

/**
 * Get alt text for avatar image.
 *
 * @param string $type Entity type.
 * @param int    $id   Entity ID.
 * @return string Alt text.
 */
function clanbite_get_avatar_alt_text( string $type, int $id ): string {
	$alt = '';

	switch ( $type ) {
		case 'player':
			if ( function_exists( 'clanbite_players_get_display_name' ) ) {
				$name = clanbite_players_get_display_name( $id );
				/* translators: %s: player name */
				$alt = sprintf( __( '%s avatar', 'clanbite' ), $name );
			}
			break;
		case 'team':
			$name = get_the_title( $id );
			if ( '' !== $name ) {
				/* translators: %s: team name */
				$alt = sprintf( __( '%s avatar', 'clanbite' ), $name );
			}
			break;
		case 'group':
			/**
			 * Resolves the alt text for a group's avatar.
			 *
			 * Core ships no group implementation; the plugin that owns `cp_group` should return the text here.
			 *
			 * @param string $alt Alt text (empty by default).
			 * @param int    $id  Group ID.
			 */
			$alt = apply_filters( 'clanbite_groups_get_avatar_alt', '', $id );
			break;
	}

	/**
	 * Filter avatar alt text.
	 *
	 * @param string $alt  Alt text.
	 * @param string $type Entity type.
	 * @param int    $id   Entity ID.
	 */
	return apply_filters( 'clanbite_avatar_alt_text', $alt, $type, $id );
}

/**
 * Get default avatar URL for entity type.
 *
 * @param string $type Entity type.
 * @return string Default avatar URL.
 */
function clanbite_get_default_avatar_url( string $type ): string {
	$url = '';

	// Get default from settings or use plugin defaults.
	switch ( $type ) {
		case 'player':
			$url = includes_url( 'images/blank.gif' );
			break;
		case 'team':
			$url = includes_url( 'images/blank.gif' );
			break;
		case 'group':
			$url = includes_url( 'images/blank.gif' );
			break;
	}

	/**
	 * Filter default avatar URL.
	 *
	 * @param string $url  Default URL.
	 * @param string $type Entity type.
	 */
	return apply_filters( 'clanbite_default_avatar_url', $url, $type );
}

/**
 * Build HTML attributes string from array.
 *
 * @param array $attrs Attributes array.
 * @return string HTML attributes string.
 */
function clanbite_build_html_attributes( array $attrs ): string {
	$parts = array();
	foreach ( $attrs as $name => $value ) {
		$name = sanitize_key( $name );
		if ( '' === $name ) {
			continue;
		}
		if ( is_bool( $value ) ) {
			if ( $value ) {
				$parts[] = esc_attr( $name );
			}
		} else {
			$parts[] = esc_attr( $name ) . '="' . esc_attr( (string) $value ) . '"';
		}
	}
	return implode( ' ', $parts );
}

/**
 * Render avatar structure for Interactivity API contexts.
 *
 * This creates the avatar HTML structure but leaves the src attribute
 * for dynamic binding via data-wp-bind--src. Used by Forums, Social Feed, etc.
 *
 * @param array $args {
 *     Avatar rendering arguments.
 *
 *     @type string $type            Avatar type: 'player', 'team', 'group'. Required.
 *     @type string $context         Context identifier (e.g., 'forum', 'social_feed'). Required.
 *     @type string $size            Size preset or slug. Default 'medium'.
 *     @type string $shape           Override shape. If empty, uses setting.
 *     @type array  $extra_classes   Additional CSS classes for the avatar container.
 *     @type array  $img_attributes  Additional attributes for the <img> tag (e.g., data-wp-bind--src).
 *     @type string $wrapper_tag     Wrapper element tag. Default 'span'.
 *     @type array  $wrapper_attrs   Additional attributes for wrapper element.
 *     @type string $badge_path      Interactivity path of the author's `avatar_badge` object (for example
 *                                   `context.item.author.avatar_badge`). When set, the avatar gets the shape frame, the XP ring
 *                                   and the rank badge, driven by that object's `dash`, `level_text`, `icon_url`, `label` and
 *                                   `hide_*` fields (see `clanbite_ranks_get_avatar_badge_for_rest()`).
 *     @type bool   $show_rank_icons Legacy: include the rank icon stack template. Default false.
 *     @type string $rank_icons_path data-wp-each path for rank icons.
 *     @type string $rank_icons_hide data-wp-bind--hidden for rank icons.
 * }
 * @return string HTML markup with Interactivity API directives.
 */
function clanbite_render_avatar_for_interactivity( array $args ): string {
	// Shared frame / ring / badge styles, for callers that are not a block (templates, companions).
	wp_enqueue_style( 'clanbite-avatars' );

	$defaults = array(
		'type'            => 'player',
		'context'         => '',
		'size'            => 'medium',
		'shape'           => '',
		'extra_classes'   => array(),
		'img_attributes'  => array(),
		'wrapper_tag'     => 'span',
		'wrapper_attrs'   => array(),
		'badge_path'      => '',
		'show_rank_icons' => false,
		'rank_icons_path' => '',
		'rank_icons_hide' => '',
	);

	$args = wp_parse_args( $args, $defaults );

	$args['mode'] = 'interactive';

	// Ring and badge values arrive per item through `badge_path`, so only their visibility is decided here.
	$data  = clanbite_avatar_filter_data(
		array(
			'shape'      => clanbite_avatar_resolve_shape( (string) $args['type'], (string) $args['shape'] ),
			'show_ring'  => '' !== $args['badge_path'],
			'show_badge' => '' !== $args['badge_path'],
		),
		$args
	);
	$shape = (string) $data['shape'];

	// Build CSS classes.
	$classes = array(
		'clanbite-avatar',
		'clanbite-avatar--' . $args['type'],
		'clanbite-avatar--shape-' . $shape,
		'clanbite-avatar--' . sanitize_html_class( (string) $args['size'] ),
	);

	if ( ! empty( $args['extra_classes'] ) ) {
		$classes = array_merge( $classes, (array) $args['extra_classes'] );
	}

	// The same class filters as `clanbite_render_avatar()`: a rule written for a context applies to both render modes.
	/** This filter is documented in inc/avatar-helpers.php (clanbite_render_avatar). */
	$classes = apply_filters( 'clanbite_avatar_classes', $classes, $args );
	/** This filter is documented in inc/avatar-helpers.php (clanbite_render_avatar). */
	$classes = apply_filters( "clanbite_{$args['type']}_avatar_classes", $classes, $args );
	if ( ! empty( $args['context'] ) ) {
		/** This filter is documented in inc/avatar-helpers.php (clanbite_render_avatar). */
		$classes = apply_filters( "clanbite_avatar_classes_{$args['context']}", $classes, $args );
	}

	/**
	 * Filter avatar classes for Interactivity API contexts.
	 *
	 * @param array  $classes CSS classes.
	 * @param array  $args    Avatar arguments.
	 */
	$classes = apply_filters( 'clanbite_avatar_interactivity_classes', $classes, $args );
	if ( ! empty( $args['context'] ) ) {
		/**
		 * Filters the Interactivity-API avatar CSS classes for one context.
		 *
		 * Context-specific variant of `clanbite_avatar_interactivity_classes`.
		 *
		 * @param string[]      $classes CSS classes.
		 * @param array<string, mixed>   $args Avatar arguments (`type`, `id`, `size`, `context`, …); see {@see clanbite_render_avatar_for_interactivity()}.
		 */
		$classes = apply_filters( "clanbite_avatar_interactivity_classes_{$args['context']}", $classes, $args );
	}

	// Build image attributes.
	$img_attrs = array_merge(
		array(
			'class'    => 'clanbite-avatar__img',
			'alt'      => '',
			'loading'  => 'lazy',
			'decoding' => 'async',
		),
		$args['img_attributes']
	);

	/** This filter is documented in inc/avatar-helpers.php (clanbite_render_avatar). */
	$img_attrs = apply_filters( 'clanbite_avatar_img_attributes', $img_attrs, $args );
	if ( ! empty( $args['context'] ) ) {
		/** This filter is documented in inc/avatar-helpers.php (clanbite_render_avatar). */
		$img_attrs = apply_filters( "clanbite_avatar_img_attributes_{$args['context']}", $img_attrs, $args );
	}

	/**
	 * Filter image attributes for Interactivity API contexts.
	 *
	 * @param array  $img_attrs Image attributes.
	 * @param array  $args      Avatar arguments.
	 */
	$img_attrs = apply_filters( 'clanbite_avatar_interactivity_img_attributes', $img_attrs, $args );
	if ( ! empty( $args['context'] ) ) {
		/**
		 * Filters the Interactivity-API avatar `<img>` attributes for one context.
		 *
		 * Context-specific variant of `clanbite_avatar_interactivity_img_attributes`.
		 *
		 * @param array<string, string|bool> $img_attrs Attribute map.
		 * @param array<string, mixed>       $args Avatar arguments (`type`, `id`, `size`, `context`, …); see {@see clanbite_render_avatar_for_interactivity()}.
		 */
		$img_attrs = apply_filters( "clanbite_avatar_interactivity_img_attributes_{$args['context']}", $img_attrs, $args );
	}

	ob_start();
	?>
	<<?php echo esc_attr( $args['wrapper_tag'] ); ?> <?php echo clanbite_build_html_attributes( $args['wrapper_attrs'] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- clanbite_build_html_attributes() escapes every name and value. ?>>
		<span class="<?php echo esc_attr( implode( ' ', array_map( 'sanitize_html_class', $classes ) ) ); ?>">
			<span class="clanbite-avatar__media">
				<?php
				if ( $data['show_ring'] && '' !== $args['badge_path'] ) {
					// Frame with the XP ring bound to the author's avatar_badge data (hidden when the ring is off).
					echo clanbite_avatar_frame_svg( $shape, array( 'bind' => $args['badge_path'] ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				} else {
					echo clanbite_avatar_frame_svg( $shape, array( 'ring' => false ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				}
				?>
				<span class="clanbite-avatar__clip">
					<img <?php echo clanbite_build_html_attributes( $img_attrs ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- clanbite_build_html_attributes() escapes every name and value. ?> />
				</span>
				<?php
				if ( $data['show_badge'] && '' !== $args['badge_path'] ) {
					echo clanbite_avatar_badge_html( $shape, array(), $args['badge_path'] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				}
				?>
			</span>
			<?php if ( $args['show_rank_icons'] && ! empty( $args['rank_icons_path'] ) ) : ?>
				<span class="clanbite-avatar__rank-icons" aria-hidden="true" data-wp-bind--hidden="<?php echo esc_attr( $args['rank_icons_hide'] ); ?>">
					<template data-wp-each--icon="<?php echo esc_attr( $args['rank_icons_path'] ); ?>">
						<img class="clanbite-avatar__rank-icon" data-wp-bind--src="context.icon.url" alt="" decoding="async" data-wp-bind--data-rank-type-key="context.icon.key" loading="lazy" />
					</template>
				</span>
			<?php endif; ?>
			
			<?php
			/**
			 * Action: Add custom content to Interactivity API avatars.
			 *
			 * @param array $args Avatar arguments.
			 */
			do_action( 'clanbite_avatar_interactivity_content', $args );
			if ( ! empty( $args['context'] ) ) {
				/**
				 * Fires to add content inside an Interactivity-API avatar for one context.
				 *
				 * Context-specific variant of `clanbite_avatar_interactivity_content`.
				 *
				 * @param array<string, mixed> $args Avatar arguments (`type`, `id`, `size`, `context`, …); see {@see clanbite_render_avatar_for_interactivity()}.
				 */
				do_action( "clanbite_avatar_interactivity_content_{$args['context']}", $args );
			}
			?>
		</span>
	</<?php echo esc_attr( $args['wrapper_tag'] ); ?>>
	<?php
	$output = ob_get_clean();

	/** This filter is documented in inc/avatar-helpers.php (clanbite_render_avatar). */
	$output = apply_filters( 'clanbite_avatar_html', $output, $args );
	/** This filter is documented in inc/avatar-helpers.php (clanbite_render_avatar). */
	$output = apply_filters( "clanbite_{$args['type']}_avatar_html", $output, $args );
	if ( ! empty( $args['context'] ) ) {
		/** This filter is documented in inc/avatar-helpers.php (clanbite_render_avatar). */
		$output = apply_filters( "clanbite_avatar_html_{$args['context']}", $output, $args );
	}

	/**
	 * Filter the complete Interactivity API avatar HTML.
	 *
	 * @param string $output Avatar HTML markup.
	 * @param array  $args   Avatar arguments.
	 */
	$output = apply_filters( 'clanbite_avatar_interactivity_html', $output, $args );
	if ( ! empty( $args['context'] ) ) {
		/**
		 * Filters the Interactivity-API avatar HTML for one context.
		 *
		 * Context-specific variant of `clanbite_avatar_interactivity_html`.
		 *
		 * @param string        $output Avatar markup.
		 * @param array<string, mixed>  $args Avatar arguments (`type`, `id`, `size`, `context`, …); see {@see clanbite_render_avatar_for_interactivity()}.
		 */
		$output = apply_filters( "clanbite_avatar_interactivity_html_{$args['context']}", $output, $args );
	}

	return $output;
}

/**
 * Data for an avatar's XP ring and rank badge.
 *
 * Players get it from the Ranks companion (`clanbite_ranks_get_avatar_badge()`); teams and groups have none. Plugins can
 * supply or adjust it with the `clanbite_avatar_badge` filter.
 *
 * @param string $type Avatar type: `player`, `team` or `group`.
 * @param int    $id   Entity ID.
 * @return array{show_progress: bool, progress: int, show_badge: bool, level: int, level_text: string, label: string, icon_url: string}
 */
function clanbite_get_avatar_badge( string $type, int $id ): array {
	$data = array(
		'show_progress' => false,
		'progress'      => 0,
		'show_badge'    => false,
		'level'         => 0,
		'level_text'    => '',
		'label'         => '',
		'icon_url'      => '',
	);

	if ( 'player' === $type && function_exists( 'clanbite_ranks_get_avatar_badge' ) ) {
		$data = array_merge( $data, (array) clanbite_ranks_get_avatar_badge( $id ) );
	}

	if ( ! $data['show_progress'] ) {
		/**
		 * Filters a fixed progress value to draw when no real rank progress is available.
		 *
		 * By default no XP ring is drawn unless a Ranks ladder has "Show progress on avatar" enabled. Return an integer from 0
		 * to 100 to force a ring, for example while styling it.
		 *
		 * @param int|null $demo    Progress percentage, or null to render nothing (default).
		 * @param int      $user_id User ID the avatar belongs to.
		 */
		$demo = 'player' === $type ? apply_filters( 'clanbite_avatar_progress_demo_value', null, $id ) : null;
		if ( null !== $demo ) {
			$data['show_progress'] = true;
			$data['progress']      = max( 0, min( 100, (int) $demo ) );
		}
	}

	/**
	 * Filters the XP ring / rank badge data of an avatar.
	 *
	 * @param array<string, mixed> $data Keys `show_progress`, `progress` (0-100), `show_badge`, `level`, `level_text`, `label`, `icon_url`.
	 * @param string               $type Avatar type (`player`, `team`, `group`).
	 * @param int                  $id   Entity ID.
	 */
	return (array) apply_filters( 'clanbite_avatar_badge', $data, $type, $id );
}

/**
 * Points of a point-up regular hexagon with rounded corners, as an SVG `points` list.
 *
 * Corners are arcs sampled at 6 steps so the SVG frame matches the CSS photo clip (`--clanbite-avatar-clip-*`, generated
 * with the same 20% corner radius). Points run clockwise from the top, so a ring arc (`pathLength`) starts at 12 o'clock.
 *
 * @param float $radius Half-size in viewBox units (0 to 50).
 * @return string Space-separated `x,y` pairs.
 */
function clanbite_avatar_hexagon_points( float $radius ): string {
	$corner        = $radius * 0.2;
	$tangent       = $corner / tan( M_PI / 3 );
	$centre_offset = $corner / sin( M_PI / 3 );
	$vertices      = array();
	for ( $i = 0; $i < 6; $i++ ) {
		$angle      = deg2rad( 60 * $i );
		$vertices[] = array( 50 + $radius * sin( $angle ), 50 - $radius * cos( $angle ) );
	}

	$points = array();
	foreach ( $vertices as $i => $v ) {
		$prev = $vertices[ ( $i + 5 ) % 6 ];
		$next = $vertices[ ( $i + 1 ) % 6 ];
		$lp   = hypot( $prev[0] - $v[0], $prev[1] - $v[1] );
		$ln   = hypot( $next[0] - $v[0], $next[1] - $v[1] );
		$up   = array( ( $prev[0] - $v[0] ) / $lp, ( $prev[1] - $v[1] ) / $lp );
		$un   = array( ( $next[0] - $v[0] ) / $ln, ( $next[1] - $v[1] ) / $ln );
		$bx   = $up[0] + $un[0];
		$by   = $up[1] + $un[1];
		$bl   = hypot( $bx, $by );
		$cx   = $v[0] + $centre_offset * $bx / $bl;
		$cy   = $v[1] + $centre_offset * $by / $bl;
		$a0   = atan2( $v[1] + $tangent * $up[1] - $cy, $v[0] + $tangent * $up[0] - $cx );
		$a1   = atan2( $v[1] + $tangent * $un[1] - $cy, $v[0] + $tangent * $un[0] - $cx );
		$da   = fmod( $a1 - $a0 + 3 * M_PI, 2 * M_PI ) - M_PI;
		for ( $k = 0; $k <= 6; $k++ ) {
			$a        = $a0 + $da * $k / 6;
			$points[] = round( $cx + $corner * cos( $a ), 2 ) . ',' . round( $cy + $corner * sin( $a ), 2 );
		}
	}

	return implode( ' ', $points );
}

/**
 * One SVG shape in an avatar's 100 x 100 coordinate space.
 *
 * Geometry is shared with the CSS photo clip in `_avatars.scss`; keep the two in sync. `$radius` is the shape's half-size:
 * 50 touches the edge of the box. Hexagons are regular and point-up, so the progress arc starts at the top and runs clockwise.
 *
 * @param string $shape  `circle`, `square` or `hexagon`.
 * @param float  $radius Half-size in viewBox units (0 to 50).
 * @param string $css_class  CSS class of the element.
 * @param string $attrs  Extra, already-escaped attributes.
 * @return string SVG element markup.
 */
function clanbite_avatar_shape_element( string $shape, float $radius, string $css_class, string $attrs = '' ): string {
	$r = round( $radius, 2 );

	if ( 'hexagon' === $shape ) {
		$list = clanbite_avatar_hexagon_points( $radius );

		return '<polygon class="' . esc_attr( $css_class ) . '" points="' . esc_attr( $list ) . '"' . $attrs . '/>';
	}

	if ( 'square' === $shape ) {
		$inset = round( 50 - $radius, 2 );
		$rx    = round( 6 + $radius * 0.2, 2 );
		$far   = round( 100 - $inset, 2 );
		$d     = sprintf(
			'M50 %1$s H%2$s a%3$s %3$s 0 0 1 %3$s %3$s V%4$s a%3$s %3$s 0 0 1 -%3$s %3$s H%5$s a%3$s %3$s 0 0 1 -%3$s -%3$s V%6$s a%3$s %3$s 0 0 1 %3$s -%3$s Z',
			$inset,
			round( $far - $rx, 2 ),
			$rx,
			round( $far - $rx, 2 ),
			round( $inset + $rx, 2 ),
			round( $inset + $rx, 2 )
		);

		return '<path class="' . esc_attr( $css_class ) . '" d="' . esc_attr( $d ) . '"' . $attrs . '/>';
	}

	// Circle: rotated so the arc starts at the top.
	return '<circle class="' . esc_attr( $css_class ) . '" cx="50" cy="50" r="' . esc_attr( (string) $r ) . '" transform="rotate(-90 50 50)"' . $attrs . '/>';
}

/**
 * The avatar frame: a border in the avatar's shape plus the XP ring (track and progress arc) inside it.
 *
 * Used by the server-rendered and the Interactivity avatars. Options:
 * - `ring` (bool): draw the ring track and progress arc. Default true when `progress` or `bind` is given.
 * - `progress` (int): 0-100 fill of the ring (server rendering).
 * - `bind` (string): Interactivity path of an `avatar_badge` object; the arc follows its `dash` and the ring hides with `hide_ring`.
 *
 * @param string               $shape `circle`, `square` or `hexagon`.
 * @param array<string, mixed> $opts  Options.
 * @return string SVG markup (numbers and constants only; bind paths are escaped).
 */
function clanbite_avatar_frame_svg( string $shape, array $opts = array() ): string {
	$shape    = in_array( $shape, array( 'circle', 'square', 'hexagon' ), true ) ? $shape : 'circle';
	$bind     = isset( $opts['bind'] ) ? (string) $opts['bind'] : '';
	$progress = isset( $opts['progress'] ) ? max( 0, min( 100, (int) $opts['progress'] ) ) : null;
	$ring     = array_key_exists( 'ring', $opts ) ? (bool) $opts['ring'] : ( null !== $progress || '' !== $bind );

	$svg  = '<svg class="clanbite-avatar__frame" viewBox="0 0 100 100" width="100" height="100" aria-hidden="true" focusable="false">';
	$svg .= clanbite_avatar_shape_element( $shape, 50, 'clanbite-avatar__frame-border' );

	if ( $ring ) {
		$group_attrs = '' !== $bind ? ' data-wp-bind--hidden="' . esc_attr( $bind . '.hide_ring' ) . '"' : '';
		$dash        = '' !== $bind
			? ' data-wp-bind--stroke-dasharray="' . esc_attr( $bind . '.dash' ) . '" stroke-dasharray="0 100"'
			: ' stroke-dasharray="' . (int) $progress . ' 100"';

		$svg .= '<g class="clanbite-avatar__ring"' . $group_attrs . '>';
		$svg .= clanbite_avatar_shape_element( $shape, 43, 'clanbite-avatar__ring-track' );
		$svg .= clanbite_avatar_shape_element( $shape, 43, 'clanbite-avatar__ring-progress', ' pathLength="100"' . $dash );
		$svg .= '</g>';
	}

	return $svg . '</svg>';
}

/**
 * The rank badge: a small shape (hexagon for hexagon avatars) at the avatar's lower right, holding the level icon or number.
 *
 * Server rendering takes the data from `$badge`; Interactivity rendering passes `$bind` (a path to an `avatar_badge`
 * object) and the browser fills in the icon, number and label.
 *
 * @param string               $shape `circle`, `square` or `hexagon`.
 * @param array<string, mixed> $badge Data from {@see clanbite_get_avatar_badge()} (ignored when `$bind` is set).
 * @param string               $bind  Interactivity path of the `avatar_badge` object, or empty for server rendering.
 * @return string Badge markup, or an empty string when there is nothing to show.
 */
function clanbite_avatar_badge_html( string $shape, array $badge, string $bind = '' ): string {
	$shape = in_array( $shape, array( 'circle', 'square', 'hexagon' ), true ) ? $shape : 'circle';
	$svg   = '<svg class="clanbite-avatar__badge-shape" viewBox="0 0 100 100" width="24" height="24" aria-hidden="true" focusable="false">'
		. clanbite_avatar_shape_element( $shape, 50, 'clanbite-avatar__badge-outer' )
		. clanbite_avatar_shape_element( $shape, 41, 'clanbite-avatar__badge-inner' )
		. '</svg>';

	if ( '' !== $bind ) {
		$b = esc_attr( $bind );

		return '<span class="clanbite-avatar__badge" role="img" data-wp-bind--hidden="' . $b . '.hide_badge" data-wp-bind--aria-label="' . $b . '.label">'
			. $svg
			. '<img class="clanbite-avatar__badge-icon" alt="" decoding="async" src="' . esc_url( includes_url( 'images/blank.gif' ) ) . '" data-wp-bind--src="' . $b . '.icon_url" data-wp-bind--hidden="' . $b . '.hide_icon" />'
			. '<span class="clanbite-avatar__badge-text" data-wp-text="' . $b . '.level_text" data-wp-bind--hidden="' . $b . '.hide_level"></span>'
			. '</span>';
	}

	$icon  = isset( $badge['icon_url'] ) ? (string) $badge['icon_url'] : '';
	$text  = isset( $badge['level_text'] ) ? (string) $badge['level_text'] : '';
	$label = isset( $badge['label'] ) ? (string) $badge['label'] : '';
	if ( '' === $icon && '' === $text ) {
		return '';
	}

	$aria = '' !== $label
		? ( '' !== $text
			/* translators: 1: rank name, 2: level number. */
			? sprintf( __( '%1$s, level %2$s', 'clanbite' ), $label, $text )
			: $label )
		: __( 'Rank', 'clanbite' );

	$inner = '' !== $icon
		? '<img class="clanbite-avatar__badge-icon" src="' . esc_url( $icon ) . '" alt="" decoding="async" loading="lazy" />'
		: '<span class="clanbite-avatar__badge-text">' . esc_html( $text ) . '</span>';

	return '<span class="clanbite-avatar__badge" role="img" aria-label="' . esc_attr( $aria ) . '">' . $svg . $inner . '</span>';
}
