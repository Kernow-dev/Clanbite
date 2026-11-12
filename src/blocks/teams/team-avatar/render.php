<?php
/**
 * Render callback: team avatar image.
 *
 * WordPress loads this via `ob_start(); require; return ob_get_clean();` — output must be echoed, not returned.
 *
 * @package clanbite
 */

defined( 'ABSPATH' ) || exit;

// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals -- Block render: core-injected $attributes, $content, and $block in this scope.

$team_id = clanbite_team_single_block_team_id( $block );

if ( $team_id < 1 ) {
	$width = isset( $attributes['width'] ) ? (int) $attributes['width'] : 120;
	$width = min( 512, max( 32, $width ) );
	$style = sprintf( 'width:%dpx;height:%dpx;', $width, $width );

	$wrapper = get_block_wrapper_attributes(
		array(
			'class' => 'clanbite-avatar clanbite-avatar--team clanbite-avatar--placeholder',
			'style' => $style,
		),
		$block
	);
	echo wp_kses( '<div ' . $wrapper . '><span>' . esc_html__( 'Team avatar', 'clanbite' ) . '</span></div>', clanbite_block_fragment_allowed_html() );
	return;
}

// Get avatar preset/size from block attributes.
$avatar_preset = isset( $attributes['avatarPreset'] ) ? sanitize_key( (string) $attributes['avatarPreset'] ) : 'large';
if ( ! in_array( $avatar_preset, array( 'large', 'medium', 'small' ), true ) ) {
	$avatar_preset = 'large';
}

// Check if block should link to profile.
$should_link = ! empty( $attributes['isLink'] );
$link_target = isset( $attributes['linkTarget'] ) && '_blank' === $attributes['linkTarget'] ? '_blank' : '';

$width = isset( $attributes['width'] ) ? (int) $attributes['width'] : 120;
$width = min( 512, max( 32, $width ) );
$style = sprintf( 'width:%dpx;height:%dpx;', $width, $width );

// Build wrapper attributes.
$wrapper_attributes = get_block_wrapper_attributes(
	array(
		'class' => 'clanbite-team-avatar-block clanbite-avatar-block',
		'style' => $style,
	),
	$block
);

// Use the unified avatar rendering system.
if ( function_exists( 'clanbite_render_avatar' ) ) {
	$avatar_html = clanbite_render_avatar(
		array(
			'type'          => 'team',
			'id'            => $team_id,
			'size'          => $avatar_preset,
			'context'       => 'profile',
			'link'          => $should_link,
			'link_target'   => $link_target,
			'show_rank'     => ( 'large' === $avatar_preset ),
			'show_progress' => ( 'large' === $avatar_preset ),
		)
	);

	?>
	<div <?php echo $wrapper_attributes; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
		<?php echo wp_kses( $avatar_html, clanbite_block_fragment_allowed_html() ); ?>
	</div>
	<?php
} else {
	// Fallback if unified system isn't loaded.
	echo '<!-- Team avatar block: unified avatar system not available -->';
}

// phpcs:enable WordPress.NamingConventions.PrefixAllGlobals
