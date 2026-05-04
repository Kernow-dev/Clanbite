<?php

defined( 'ABSPATH' ) || exit;

// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals -- Block render: core-injected $attributes, $content, and $block in this scope.
/**
 * Renders social profile links for the resolved player (profile, loops, post author fallback).
 *
 * @package clanbite
 *
 * @var array    $attributes Block attributes.
 * @var WP_Block $block      Block instance.
 */

$user_id = function_exists( 'clanbite_player_blocks_resolve_subject_user_id' )
	? (int) clanbite_player_blocks_resolve_subject_user_id( $block )
	: 0;

$icon_size = isset( $attributes['iconSize'] ) ? sanitize_key( (string) $attributes['iconSize'] ) : 'medium';
if ( ! in_array( $icon_size, array( 'small', 'medium', 'large' ), true ) ) {
	$icon_size = 'medium';
}

if ( $user_id < 1 ) {
	$wrapper = get_block_wrapper_attributes(
		array(
			'class' => 'clanbite-player-social-links--placeholder',
		),
		$block
	);
	echo wp_kses( '<div ' . $wrapper . '><p>' . esc_html__( 'Player social links', 'clanbite' ) . '</p></div>', clanbite_block_fragment_allowed_html());
	return;
}

if ( ! function_exists( 'clanbite_players_get_social_profile_field_definitions' )
	|| ! function_exists( 'clanbite_players_get_social_profile_link_url' )
	|| ! function_exists( 'clanbite_players_get_social_profile_svg_icon' ) ) {
	return '';
}

$definitions = clanbite_players_get_social_profile_field_definitions();
$items       = array();

foreach ( $definitions as $slug => $def ) {
	$slug = sanitize_key( (string) $slug );
	if ( '' === $slug || ! is_array( $def ) ) {
		continue;
	}

	$url = clanbite_players_get_social_profile_link_url( $slug, $user_id );
	if ( '' === $url ) {
		continue;
	}

	$label = isset( $def['label'] ) ? (string) $def['label'] : $slug;
	$label = $label !== '' ? $label : $slug;

	$items[] = array(
		'slug'  => $slug,
		'url'   => $url,
		'label' => $label,
	);
}

/**
 * Filters the list of social links rendered by the Player Social Links block.
 *
 * @param array<int, array{slug: string, url: string, label: string}> $items   Links to render.
 * @param int                                                         $user_id Resolved player user ID.
 * @param WP_Block                                                    $block   Block instance.
 */
$items = (array) apply_filters( 'clanbite_player_social_links_block_items', $items, $user_id, $block );

if ( array() === $items ) {
	return '';
}

$wrapper_attributes = get_block_wrapper_attributes(
	array(
		'class' => 'clanbite-player-social-links--size-' . $icon_size,
	),
	$block
);

?>
<?php ob_start(); ?>
<?php clanbite_echo_block_fragment_html( '<div ' . trim( (string) $wrapper_attributes ) . '>' ); ?>
	<ul class="clanbite-player-social-links" role="list">
		<?php foreach ( $items as $row ) : ?>
			<?php
			$slug  = isset( $row['slug'] ) ? sanitize_key( (string) $row['slug'] ) : '';
			$url   = isset( $row['url'] ) ? esc_url( (string) $row['url'] ) : '';
			$label = isset( $row['label'] ) ? (string) $row['label'] : $slug;
			if ( '' === $slug || '' === $url ) {
				continue;
			}
			$icon = clanbite_players_get_social_profile_svg_icon( $slug );
			?>
			<li class="clanbite-player-social-links__item">
				<a
					class="clanbite-player-social-links__link clanbite-player-social-links__link--<?php echo esc_attr( $slug ); ?>"
					href="<?php echo esc_url( $url ); ?>"
					target="_blank"
					rel="noopener noreferrer"
					aria-label="<?php echo esc_attr( $label ); ?>"
				>
					<?php clanbite_echo_block_fragment_html( (string) $icon ); ?>
				</a>
			</li>
		<?php endforeach; ?>
	</ul>
</div>
<?php
echo wp_kses( (string) ob_get_clean(), clanbite_block_fragment_allowed_html());
// phpcs:enable WordPress.NamingConventions.PrefixAllGlobals
