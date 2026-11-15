<?php
/**
 * Verifies release signatures before an add-on is installed.
 *
 * @package clanbite
 */

namespace Kernowdev\Clanbite\Updates;

defined( 'ABSPATH' ) || exit;

/**
 * Add-on releases are signed (Ed25519) in the release workflow with a key that never touches the update server, so even a compromised
 * server cannot make a site install code it did not sign. The signed message is `clanbite-release-v1 \n slug \n version \n sha256`,
 * where the SHA-256 is computed locally from the file that was downloaded.
 *
 * Trusted public keys come from {@see clanbite_release_public_keys()}. With no key configured nothing is verified.
 */
final class Release_Signature {

	/**
	 * The exact bytes that are signed.
	 *
	 * @param string $slug    Plugin slug.
	 * @param string $version Release version.
	 * @param string $sha256  SHA-256 (hex) of the zip, computed locally.
	 * @return string
	 */
	public static function message( string $slug, string $version, string $sha256 ): string {
		return "clanbite-release-v1\n" . $slug . "\n" . $version . "\n" . strtolower( $sha256 );
	}

	/**
	 * Whether a signature verifies against any trusted key.
	 *
	 * @param string   $slug      Plugin slug.
	 * @param string   $version   Release version.
	 * @param string   $sha256    SHA-256 (hex) of the downloaded file.
	 * @param string   $signature Base64 detached signature from the server.
	 * @param string[] $keys      Base64 Ed25519 public keys.
	 * @return bool
	 */
	public static function verify( string $slug, string $version, string $sha256, string $signature, array $keys ): bool {
		$sig = base64_decode( trim( $signature ), true ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_decode -- Decoding a signature, not obfuscation.
		if ( false === $sig || SODIUM_CRYPTO_SIGN_BYTES !== strlen( $sig ) ) {
			return false;
		}
		$message = self::message( $slug, $version, $sha256 );
		foreach ( $keys as $key ) {
			$raw = base64_decode( trim( (string) $key ), true ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_decode -- Decoding a public key, not obfuscation.
			if ( false !== $raw && SODIUM_CRYPTO_SIGN_PUBLICKEYBYTES === strlen( $raw ) && sodium_crypto_sign_verify_detached( $sig, $message, $raw ) ) {
				return true;
			}
		}

		return false;
	}
}
