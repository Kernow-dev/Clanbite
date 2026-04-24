<?php
/**
 * Registers block patterns for reusable profile layouts (cover/avatar/name blocks where applicable; navigation shells).
 *
 * Patterns are defined as HTML in `/patterns/*.html` so the same markup is used
 * for registration and can be kept in sync with plugin templates that reference
 * them via the `core/pattern` block.
 *
 * @package clanbite
 */

namespace Kernowdev\Clanbite;

defined( 'ABSPATH' ) || exit;


/**
 * Registers Clanbite block patterns and pattern categories.
 */
final class Block_Patterns {

	/**
	 * Register pattern categories and profile shell patterns.
	 *
	 * @return void
	 */
	public static function register(): void {
		if ( ! function_exists( 'register_block_pattern' ) ) {
			return;
		}

		self::register_categories();

		$base = clanbite()->path . 'patterns/';
		$defs = array(
			'clanbite/team-profile-header-nav' => array(
				'title'       => __( 'Team profile shell & navigation', 'clanbite' ),
				'description' => __( 'Team cover, avatar, name blocks, and horizontal section tabs. Insert into templates or pages, then edit blocks or detach from the pattern wrapper in the site editor.', 'clanbite' ),
				'categories'  => array( 'clanbite-teams' ),
				'file'        => 'team-profile-header-nav.html',
				'keywords'    => array( 'team', 'clanbite', 'cover', 'navigation' ),
			),
			'clanbite/player-profile-header-nav' => array(
				'title'       => __( 'Player profile navigation', 'clanbite' ),
				'description' => __( 'Horizontal profile section tabs. Insert into templates or pages, then edit blocks or detach from the pattern wrapper.', 'clanbite' ),
				'categories'  => array( 'clanbite-players' ),
				'file'        => 'player-profile-header-nav.html',
				'keywords'    => array( 'player', 'clanbite', 'navigation' ),
			),
		);

		foreach ( $defs as $slug => $conf ) {
			$file = $base . $conf['file'];
			if ( ! is_readable( $file ) ) {
				continue;
			}

			$content = file_get_contents( $file );
			if ( false === $content || '' === trim( $content ) ) {
				continue;
			}

			register_block_pattern(
				$slug,
				array(
					'title'       => $conf['title'],
					'description' => $conf['description'],
					'categories'  => $conf['categories'],
					'content'     => $content,
					'keywords'    => $conf['keywords'],
				)
			);
		}
	}

	/**
	 * Register pattern categories for the block inserter.
	 *
	 * @return void
	 */
	private static function register_categories(): void {
		if ( ! function_exists( 'register_block_pattern_category' ) ) {
			return;
		}

		$cats = array(
			'clanbite-teams'   => __( 'Clanbite Teams', 'clanbite' ),
			'clanbite-players' => __( 'Clanbite Players', 'clanbite' ),
		);

		foreach ( $cats as $slug => $label ) {
			register_block_pattern_category(
				$slug,
				array(
					'label' => $label,
				)
			);
		}
	}
}
