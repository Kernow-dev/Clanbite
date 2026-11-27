<?php
/**
 * Live Preview: fetch the demo's pictures into /tmp/clanbite-demo-art/.
 *
 * This is PHP rather than the blueprint's own `unzip` step because a failed step stops the whole preview. The pictures are
 * only decoration: each address is tried twice, and if none works the demo is built without them.
 *
 * The build script replaces {{ART_ZIP}} with the zip's file name.
 *
 * @package clanbite
 */

require_once '/wordpress/wp-load.php';

$sources = array(
	'https://ps.w.org/clanbite/assets/blueprints/{{ART_ZIP}}',
	'https://raw.githubusercontent.com/Kernow-dev/Clanbite/main/.wordpress-org/blueprints/{{ART_ZIP}}',
);
$target  = '/tmp/clanbite-demo-art/';

wp_mkdir_p( $target );

$unpack = static function ( string $data ) use ( $target ): bool {
	if ( ! class_exists( 'ZipArchive' ) || ! str_starts_with( $data, "PK\x03\x04" ) ) {
		return false;
	}
	$file = $target . 'art.zip';
	file_put_contents( $file, $data );
	$zip   = new ZipArchive();
	$count = 0;
	if ( true === $zip->open( $file ) ) {
		for ( $index = 0; $index < $zip->numFiles; $index++ ) {
			$name = (string) $zip->getNameIndex( $index );
			// Only flat picture names, so nothing can be written anywhere else.
			if ( 1 !== preg_match( '/^[a-z0-9-]+\.webp$/', $name ) ) {
				continue;
			}
			$picture = $zip->getFromIndex( $index );
			if ( false !== $picture ) {
				file_put_contents( $target . $name, $picture );
				++$count;
			}
		}
		$zip->close();
	}
	unlink( $file );

	return $count > 0;
};

foreach ( $sources as $url ) {
	for ( $attempt = 1; $attempt <= 2; $attempt++ ) {
		try {
			$response = wp_remote_get( $url, array( 'timeout' => 20 ) );
			if ( 200 === wp_remote_retrieve_response_code( $response ) && $unpack( wp_remote_retrieve_body( $response ) ) ) {
				return;
			}
		} catch ( Throwable $error ) {
			error_log( 'Clanbite demo artwork: ' . $error->getMessage() );
		}
	}
}
error_log( 'Clanbite demo artwork could not be fetched; continuing without pictures.' );
