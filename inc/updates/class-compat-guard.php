<?php
/**
 * Last line of defence: refuse an add-on package that cannot run safely on this site.
 *
 * @package clanbite
 */

namespace Kernowdev\Clanbite\Updates;

defined( 'ABSPATH' ) || exit;

/**
 * The update server only offers compatible releases, but a server can be wrong, stale or compromised, so core checks again with the
 * package it actually downloaded: it reads the headers of the **unpacked** main plugin file (which are covered by the release signature)
 * and compares them with what is running on this site.
 *
 *  - `Requires Clanbite` must be declared and must accept the running Clanbite (Clanbite is backward compatible within a major version).
 *  - PHP and WordPress minimums must be met.
 *  - Add-ons named in `Requires Clanbite Add-ons` must be installed in accepted versions.
 *  - Every other installed add-on must still accept the new version of this one (reverse check).
 *
 * A failure aborts the installation before any file of the existing plugin is replaced.
 */
final class Compat_Guard {

	private const HEADERS = array(
		'version' => 'Version',
		'core'    => 'Requires Clanbite',
		'addons'  => 'Requires Clanbite Add-ons',
		'php'     => 'Requires PHP',
		'wp'      => 'Requires at least',
	);

	/**
	 * Versions of the participating add-ons installed on this site.
	 *
	 * @return array<string, string> Plugin slug => version.
	 */
	public static function installed_addons(): array {
		$out = array();
		foreach ( Updater::discover( get_plugins() ) as $file => $data ) {
			$out[ dirname( (string) $file ) ] = (string) $data['Version'];
		}

		return $out;
	}

	/**
	 * Check an unpacked package.
	 *
	 * @param string $source      Folder the package was unpacked into.
	 * @param string $plugin_file Plugin file being updated (`slug/slug.php`).
	 * @return true|\WP_Error
	 */
	public static function check_unpacked( string $source, string $plugin_file ) {
		$slug = dirname( $plugin_file );
		$main = trailingslashit( $source ) . basename( $plugin_file );
		if ( ! is_readable( $main ) ) {
			return new \WP_Error( 'clanbite_update_incompatible', __( 'The update package has no main plugin file and was not installed.', 'clanbite' ) );
		}
		$new = get_file_data( $main, self::HEADERS );

		$core = trim( (string) $new['core'] );
		if ( '' === $core ) {
			return new \WP_Error( 'clanbite_update_incompatible', __( 'This update does not say which Clanbite versions it works with, so it was not installed.', 'clanbite' ) );
		}
		if ( ! Version_Constraint::is_valid( $core ) ) {
			return new \WP_Error( 'clanbite_update_incompatible', __( 'This update declares an invalid Clanbite requirement and was not installed.', 'clanbite' ) );
		}
		if ( ! Version_Constraint::satisfies( \Kernowdev\Clanbite\Main::VERSION, $core ) ) {
			return new \WP_Error(
				'clanbite_update_incompatible',
				sprintf(
					/* translators: 1: required Clanbite versions, 2: installed Clanbite version */
					__( 'This update is built for Clanbite %1$s, but this site runs Clanbite %2$s. It was not installed so your site keeps working. Update Clanbite first (or renew the add-on for a version that supports your Clanbite).', 'clanbite' ),
					$core,
					\Kernowdev\Clanbite\Main::VERSION
				)
			);
		}
		if ( '' !== trim( (string) $new['php'] ) && version_compare( PHP_VERSION, trim( (string) $new['php'] ), '<' ) ) {
			return new \WP_Error(
				'clanbite_update_incompatible',
				sprintf(
					/* translators: 1: required PHP version, 2: running PHP version */
					__( 'This update needs PHP %1$s or newer; this site runs PHP %2$s. It was not installed.', 'clanbite' ),
					trim( (string) $new['php'] ),
					PHP_VERSION
				)
			);
		}
		if ( '' !== trim( (string) $new['wp'] ) && version_compare( get_bloginfo( 'version' ), trim( (string) $new['wp'] ), '<' ) ) {
			return new \WP_Error(
				'clanbite_update_incompatible',
				sprintf(
					/* translators: 1: required WordPress version, 2: running WordPress version */
					__( 'This update needs WordPress %1$s or newer; this site runs %2$s. It was not installed.', 'clanbite' ),
					trim( (string) $new['wp'] ),
					get_bloginfo( 'version' )
				)
			);
		}

		$installed = self::installed_addons();
		$needs     = Version_Constraint::parse_addons( (string) $new['addons'] );
		if ( null === $needs ) {
			return new \WP_Error( 'clanbite_update_incompatible', __( 'This update declares invalid add-on requirements and was not installed.', 'clanbite' ) );
		}
		foreach ( $needs as $need_slug => $constraint ) {
			if ( ! isset( $installed[ $need_slug ] ) || ! Version_Constraint::satisfies( $installed[ $need_slug ], $constraint ) ) {
				return new \WP_Error(
					'clanbite_update_incompatible',
					sprintf(
						/* translators: 1: add-on slug, 2: required versions */
						__( 'This update needs the add-on "%1$s" %2$s, which this site does not have in a compatible version. It was not installed; update that add-on first.', 'clanbite' ),
						$need_slug,
						$constraint
					)
				);
			}
		}

		// Reverse check: the other add-ons, as installed, must still accept this one.
		foreach ( Updater::discover( get_plugins() ) as $file => $data ) {
			$other_slug = dirname( (string) $file );
			if ( $other_slug === $slug || ! is_readable( WP_PLUGIN_DIR . '/' . $file ) ) {
				continue;
			}
			$other  = get_file_data( WP_PLUGIN_DIR . '/' . $file, self::HEADERS );
			$others = Version_Constraint::parse_addons( (string) $other['addons'] );
			if ( isset( $others[ $slug ] ) && ! Version_Constraint::satisfies( (string) $new['version'], $others[ $slug ] ) ) {
				return new \WP_Error(
					'clanbite_update_incompatible',
					sprintf(
						/* translators: 1: other add-on name, 2: other add-on version, 3: this add-on slug, 4: required versions, 5: new version */
						__( '%1$s %2$s on this site needs %3$s %4$s, so version %5$s was not installed. Update %1$s first.', 'clanbite' ),
						(string) $data['Name'],
						(string) $data['Version'],
						$slug,
						$others[ $slug ],
						(string) $new['version']
					)
				);
			}
		}

		return true;
	}
}
