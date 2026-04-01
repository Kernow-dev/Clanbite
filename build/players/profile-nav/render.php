<?php
/**
 * Server-side render for the Player Profile Navigation block.
 *
 * @var array    $attributes Block attributes.
 * @var string   $content    Block content.
 * @var WP_Block $block      Block instance.
 */

$user = get_queried_object();

if ( ! ( $user instanceof WP_User ) ) {
	return;
}

$player_id    = (int) $user->ID;
$subpages     = function_exists( 'clanspress_get_player_subpages' ) ? clanspress_get_player_subpages() : array();
$current_slug = sanitize_key( (string) get_query_var( 'cp_player_subpage' ) );
$base_url     = trailingslashit( home_url( '/players/' . $user->user_nicename ) );

/**
 * Filter the label for the player profile home/overview link.
 *
 * @param string $label   Default label.
 * @param int    $user_id Player user ID.
 */
$home_label = (string) apply_filters( 'clanspress_player_profile_home_label', __( 'Profile', 'clanspress' ), $player_id );

?>
<nav <?php echo wp_kses_data( get_block_wrapper_attributes( array( 'class' => 'clanspress-player-profile-nav' ) ) ); ?>>
	<ul class="clanspress-player-profile-nav__list">
		<li class="clanspress-player-profile-nav__item<?php echo empty( $current_slug ) ? ' is-active' : ''; ?>">
			<a
				class="clanspress-player-profile-nav__link"
				href="<?php echo esc_url( $base_url ); ?>"
				<?php echo empty( $current_slug ) ? ' aria-current="page"' : ''; ?>
			>
				<?php echo esc_html( $home_label ); ?>
			</a>
		</li>
		<?php foreach ( $subpages as $slug => $config ) :
			$label   = $config['label'] ?? ucfirst( $slug );
			$cap     = $config['capability'] ?? 'read';
			$allowed = current_user_can( $cap, $player_id );

			if ( ! $allowed ) {
				continue;
			}

			$is_active = ( $slug === $current_slug );
			$url       = trailingslashit( $base_url . $slug );
			?>
			<li class="clanspress-player-profile-nav__item<?php echo $is_active ? ' is-active' : ''; ?>">
				<a
					class="clanspress-player-profile-nav__link"
					href="<?php echo esc_url( $url ); ?>"
					<?php echo $is_active ? ' aria-current="page"' : ''; ?>
				>
					<?php echo esc_html( $label ); ?>
				</a>
			</li>
		<?php endforeach; ?>
	</ul>
</nav>

