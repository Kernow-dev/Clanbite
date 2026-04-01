<?php
/**
 * Server-side render for the Team Profile Navigation block.
 *
 * @var array    $attributes Block attributes.
 * @var string   $content    Block content.
 * @var WP_Block $block      Block instance.
 */

if ( ! is_singular( 'cp_team' ) ) {
	return;
}

$post = get_queried_object();
if ( ! ( $post instanceof WP_Post ) ) {
	return;
}

$team_id      = (int) $post->ID;
$subpages     = function_exists( 'clanspress_get_team_subpages' ) ? clanspress_get_team_subpages() : array();
$current_slug = sanitize_key( (string) get_query_var( 'cp_team_subpage' ) );
$base_url     = trailingslashit( get_permalink( $team_id ) );

/**
 * Filter the label for the team profile home/overview link.
 *
 * @param string $label   Default label.
 * @param int    $team_id Team post ID.
 */
$home_label = (string) apply_filters( 'clanspress_team_profile_home_label', __( 'Overview', 'clanspress' ), $team_id );

?>
<nav <?php echo wp_kses_data( get_block_wrapper_attributes( array( 'class' => 'clanspress-team-profile-nav' ) ) ); ?>>
	<ul class="clanspress-team-profile-nav__list">
		<li class="clanspress-team-profile-nav__item<?php echo empty( $current_slug ) ? ' is-active' : ''; ?>">
			<a
				class="clanspress-team-profile-nav__link"
				href="<?php echo esc_url( $base_url ); ?>"
				<?php echo empty( $current_slug ) ? ' aria-current="page"' : ''; ?>
			>
				<?php echo esc_html( $home_label ); ?>
			</a>
		</li>
		<?php foreach ( $subpages as $slug => $config ) :
			$label   = $config['label'] ?? ucfirst( $slug );
			$cap     = $config['capability'] ?? 'read';
			$allowed = current_user_can( $cap, $team_id );

			if ( ! $allowed ) {
				continue;
			}

			$is_active = ( $slug === $current_slug );
			$url       = trailingslashit( $base_url . $slug );
			?>
			<li class="clanspress-team-profile-nav__item<?php echo $is_active ? ' is-active' : ''; ?>">
				<a
					class="clanspress-team-profile-nav__link"
					href="<?php echo esc_url( $url ); ?>"
					<?php echo $is_active ? ' aria-current="page"' : ''; ?>
				>
					<?php echo esc_html( $label ); ?>
				</a>
			</li>
		<?php endforeach; ?>
	</ul>
</nav>

