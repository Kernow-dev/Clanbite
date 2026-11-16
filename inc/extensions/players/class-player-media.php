<?php
/**
 * Player avatar and cover positioning: focal point, zoom and reset.
 *
 * @package clanbite
 */

namespace Kernowdev\Clanbite\Extensions\Players;

defined( 'ABSPATH' ) || exit;

/**
 * Lets a player choose which part of their avatar and cover image is shown, and go back to the defaults.
 *
 * The position is a focal point (`x`, `y`, 0 to 1, the same meaning as CSS `object-position` percentages and the Cover block's focal
 * point) and a zoom (1 to 4).
 *
 * - **Cover:** the full image is kept; the position and zoom are stored (`cp_player_cover_position_x/y`, `cp_player_cover_zoom`) and
 *   applied when the cover is drawn (`object-position` plus a scale around the focal point).
 * - **Avatar:** avatars are drawn in many places from a plain image address, so the chosen part is cut out of the original as a
 *   square and saved as the avatar (`cp_player_avatar_id`). The original stays as `cp_player_avatar_source_id` so the position can be
 *   changed later.
 *
 * Both are saved by the normal player settings save (`clanbite_save_player_settings`) through these form fields:
 * `profile_avatar_focal_x`, `profile_avatar_focal_y`, `profile_avatar_zoom`, `profile_avatar_remove`, `profile_cover_position_x`,
 * `profile_cover_position_y`, `profile_cover_zoom` and `profile_cover_remove`.
 *
 * @since 1.6.3
 */
final class Player_Media {

	/**
	 * Largest zoom a player can choose.
	 */
	public const MAX_ZOOM = 4.0;

	/**
	 * Edge length in pixels of the avatar square that is cut from the original.
	 */
	public const AVATAR_SIZE = 512;

	/**
	 * Register hooks.
	 *
	 * @return void
	 */
	public function register(): void {
		add_action( 'init', array( $this, 'register_meta' ) );
		// After the Players extension has stored any new upload (priority 10).
		add_action( 'clanbite_save_player_settings', array( $this, 'save_media_settings' ), 12, 4 );
		add_filter( 'clanbite_player_settings_success_data', array( $this, 'add_success_data' ), 10, 2 );
	}

	/**
	 * Register the user meta this class stores.
	 *
	 * @return void
	 */
	public function register_meta(): void {
		$number = static function ( float $fallback ): array {
			return array(
				'type'              => 'number',
				'single'            => true,
				'sanitize_callback' => static fn( $value ) => (float) $value,
				'show_in_rest'      => false,
				'default'           => $fallback,
			);
		};
		register_meta(
			'user',
			'cp_player_avatar_source_id',
			array(
				'type'              => 'integer',
				'single'            => true,
				'sanitize_callback' => 'absint',
				'show_in_rest'      => false,
				'default'           => 0,
			)
		);
		register_meta( 'user', 'cp_player_avatar_focal_x', $number( 0.5 ) );
		register_meta( 'user', 'cp_player_avatar_focal_y', $number( 0.5 ) );
		register_meta( 'user', 'cp_player_avatar_zoom', $number( 1.0 ) );
		register_meta( 'user', 'cp_player_cover_zoom', $number( 1.0 ) );
	}

	/**
	 * Clamp a focal coordinate to 0..1.
	 *
	 * @param mixed $value Raw value.
	 * @return float
	 */
	public static function clamp_focal( $value ): float {
		return min( 1.0, max( 0.0, (float) $value ) );
	}

	/**
	 * Clamp a zoom to 1..MAX_ZOOM.
	 *
	 * @param mixed $value Raw value.
	 * @return float
	 */
	public static function clamp_zoom( $value ): float {
		return min( self::MAX_ZOOM, max( 1.0, (float) $value ) );
	}

	/**
	 * The square, in source pixels, that is shown for a focal point and zoom.
	 *
	 * Follows how the browser draws it: the image fills a square (`object-fit: cover`) at `object-position` x% y%, then it is scaled by
	 * the zoom around the same point. The editor preview uses the same formula, so what the player sees is what is saved.
	 *
	 * @param int   $width  Source width in pixels.
	 * @param int   $height Source height in pixels.
	 * @param float $x      Focal point, 0 to 1.
	 * @param float $y      Focal point, 0 to 1.
	 * @param float $zoom   Zoom, 1 to MAX_ZOOM.
	 * @return array{left: int, top: int, side: int}
	 */
	public static function crop_square( int $width, int $height, float $x, float $y, float $zoom ): array {
		$x    = self::clamp_focal( $x );
		$y    = self::clamp_focal( $y );
		$zoom = self::clamp_zoom( $zoom );
		$base = (float) min( $width, $height );
		$side = $base / $zoom;
		$left = $x * ( $width - $base ) + $x * $base * ( 1 - 1 / $zoom );
		$top  = $y * ( $height - $base ) + $y * $base * ( 1 - 1 / $zoom );

		return array(
			'left' => (int) max( 0, round( $left ) ),
			'top'  => (int) max( 0, round( $top ) ),
			'side' => (int) max( 1, round( $side ) ),
		);
	}

	/**
	 * Cut the chosen square out of the original avatar and make it the avatar.
	 *
	 * @param int   $user_id   Player.
	 * @param int   $source_id Attachment ID of the original image.
	 * @param float $x         Focal point, 0 to 1.
	 * @param float $y         Focal point, 0 to 1.
	 * @param float $zoom      Zoom, 1 to MAX_ZOOM.
	 * @return int|\WP_Error Attachment ID of the new avatar.
	 */
	public static function bake_avatar( int $user_id, int $source_id, float $x, float $y, float $zoom ) {
		$path = get_attached_file( $source_id );
		if ( ! is_string( $path ) || '' === $path || ! is_readable( $path ) ) {
			return new \WP_Error( 'clanbite_avatar_source', __( 'The original avatar image could not be found.', 'clanbite' ) );
		}
		$editor = wp_get_image_editor( $path );
		if ( is_wp_error( $editor ) ) {
			return $editor;
		}
		$size         = $editor->get_size();
		$crop         = self::crop_square( (int) $size['width'], (int) $size['height'], $x, $y, $zoom );
		$crop['side'] = min( $crop['side'], (int) $size['width'] - $crop['left'], (int) $size['height'] - $crop['top'] );
		$result       = $editor->crop( $crop['left'], $crop['top'], $crop['side'], $crop['side'], self::AVATAR_SIZE, self::AVATAR_SIZE );
		if ( is_wp_error( $result ) ) {
			return $result;
		}

		if ( function_exists( 'clanbite_require_wp_admin_media_includes' ) ) {
			clanbite_require_wp_admin_media_includes();
		}
		$dir      = dirname( $path );
		$ext      = strtolower( (string) pathinfo( $path, PATHINFO_EXTENSION ) );
		$filename = wp_unique_filename( $dir, 'avatar-crop.' . ( 'png' === $ext ? 'png' : 'jpg' ) );
		$saved    = $editor->save( trailingslashit( $dir ) . $filename );
		if ( is_wp_error( $saved ) ) {
			return $saved;
		}

		$attachment_id = wp_insert_attachment(
			array(
				'post_mime_type' => (string) $saved['mime-type'],
				'post_title'     => 'avatar',
				'post_status'    => 'inherit',
				'post_author'    => $user_id,
			),
			(string) $saved['path']
		);
		if ( is_wp_error( $attachment_id ) || ! $attachment_id ) {
			return is_wp_error( $attachment_id ) ? $attachment_id : new \WP_Error( 'clanbite_avatar_save', __( 'The avatar could not be saved.', 'clanbite' ) );
		}
		wp_update_attachment_metadata( (int) $attachment_id, wp_generate_attachment_metadata( (int) $attachment_id, (string) $saved['path'] ) );
		if ( defined( 'CLANBITE_ATTACHMENT_HIDE_FROM_LIBRARY' ) ) {
			update_post_meta( (int) $attachment_id, \CLANBITE_ATTACHMENT_HIDE_FROM_LIBRARY, '1' );
		}

		return (int) $attachment_id;
	}

	/**
	 * Delete an attachment that belongs to a player's avatar or cover.
	 *
	 * @param int $attachment_id Attachment ID (0 is ignored).
	 * @return void
	 */
	private static function delete_attachment( int $attachment_id ): void {
		if ( $attachment_id > 0 ) {
			wp_delete_attachment( $attachment_id, true );
		}
	}

	/**
	 * Remove the player's avatar (original and cut-out) and go back to the default.
	 *
	 * @param int $user_id Player.
	 * @return void
	 */
	public static function remove_avatar( int $user_id ): void {
		$avatar_id = absint( get_user_meta( $user_id, 'cp_player_avatar_id', true ) );
		$source_id = absint( get_user_meta( $user_id, 'cp_player_avatar_source_id', true ) );
		self::delete_attachment( $avatar_id );
		if ( $source_id !== $avatar_id ) {
			self::delete_attachment( $source_id );
		}
		foreach ( array( 'cp_player_avatar_id', 'cp_player_avatar', 'cp_player_avatar_source_id', 'cp_player_avatar_focal_x', 'cp_player_avatar_focal_y', 'cp_player_avatar_zoom' ) as $key ) {
			delete_user_meta( $user_id, $key );
		}

		/**
		 * Fires after a player removed their avatar (the default avatar is shown again).
		 *
		 * @since 1.6.3
		 *
		 * @param int $user_id Player.
		 */
		do_action( 'clanbite_player_avatar_removed', $user_id );
	}

	/**
	 * Remove the player's cover image and its position, and go back to the default.
	 *
	 * @param int $user_id Player.
	 * @return void
	 */
	public static function remove_cover( int $user_id ): void {
		self::delete_attachment( absint( get_user_meta( $user_id, 'cp_player_cover_id', true ) ) );
		foreach ( array( 'cp_player_cover_id', 'cp_player_cover', 'cp_player_cover_position_x', 'cp_player_cover_position_y', 'cp_player_cover_zoom' ) as $key ) {
			delete_user_meta( $user_id, $key );
		}

		/**
		 * Fires after a player removed their cover image (the default cover is shown again).
		 *
		 * @since 1.6.3
		 *
		 * @param int $user_id Player.
		 */
		do_action( 'clanbite_player_cover_removed', $user_id );
	}

	/**
	 * Called by the Players extension right after a new avatar file was stored: it is the original, so keep it and reset the position.
	 *
	 * @param int $user_id       Player.
	 * @param int $attachment_id The newly uploaded original.
	 * @return void
	 */
	public static function register_new_avatar_source( int $user_id, int $attachment_id ): void {
		update_user_meta( $user_id, 'cp_player_avatar_source_id', $attachment_id );
		update_user_meta( $user_id, 'cp_player_avatar_focal_x', 0.5 );
		update_user_meta( $user_id, 'cp_player_avatar_focal_y', 0.5 );
		update_user_meta( $user_id, 'cp_player_avatar_zoom', 1.0 );
		set_transient( 'clanbite_new_avatar_' . $user_id, 1, MINUTE_IN_SECONDS );
	}

	/**
	 * Save handler: remove flags, avatar position, cover zoom.
	 *
	 * @param array<string, mixed> $filtered_data Sanitised request fields.
	 * @param array<string, mixed> $data          Raw request data.
	 * @param array<string, mixed> $files         Uploaded files.
	 * @param int                  $user_id       Player.
	 * @return void
	 */
	public function save_media_settings( $filtered_data, $data, $files, $user_id ): void {
		unset( $data );
		$user_id   = (int) $user_id;
		$filtered  = (array) $filtered_data;
		$files     = (array) $files;
		$new_ava   = (bool) get_transient( 'clanbite_new_avatar_' . $user_id );
		$new_cover = isset( $files['profile_cover'] );
		delete_transient( 'clanbite_new_avatar_' . $user_id );

		// Remove: ignored when a new image was chosen in the same save.
		if ( ! empty( $filtered['profile_avatar_remove'] ) && ! $new_ava ) {
			self::remove_avatar( $user_id );
		}
		if ( ! empty( $filtered['profile_cover_remove'] ) && ! $new_cover ) {
			self::remove_cover( $user_id );
		}

		// Cover zoom (the position fields are stored by the Players extension).
		if ( isset( $filtered['profile_cover_zoom'] ) && absint( get_user_meta( $user_id, 'cp_player_cover_id', true ) ) > 0 ) {
			update_user_meta( $user_id, 'cp_player_cover_zoom', self::clamp_zoom( $filtered['profile_cover_zoom'] ) );
		}
		if ( $new_cover && ! isset( $filtered['profile_cover_zoom'] ) ) {
			update_user_meta( $user_id, 'cp_player_cover_zoom', 1.0 );
			update_user_meta( $user_id, 'cp_player_cover_position_x', 0.5 );
			update_user_meta( $user_id, 'cp_player_cover_position_y', 0.5 );
		}

		// Avatar: cut the chosen square out of the original.
		$source_id = absint( get_user_meta( $user_id, 'cp_player_avatar_source_id', true ) );
		if ( 0 === $source_id ) {
			// An avatar uploaded before positions existed: its image is the original.
			$source_id = absint( get_user_meta( $user_id, 'cp_player_avatar_id', true ) );
		}
		if ( $source_id < 1 ) {
			return;
		}
		$stored     = self::editor_state( $user_id )['avatarFocal'];
		$has_fields = isset( $filtered['profile_avatar_focal_x'], $filtered['profile_avatar_focal_y'], $filtered['profile_avatar_zoom'] );
		$x          = $has_fields ? self::clamp_focal( $filtered['profile_avatar_focal_x'] ) : $stored['x'];
		$y          = $has_fields ? self::clamp_focal( $filtered['profile_avatar_focal_y'] ) : $stored['y'];
		$zoom       = $has_fields ? self::clamp_zoom( $filtered['profile_avatar_zoom'] ) : $stored['zoom'];
		$moved      = abs( $x - $stored['x'] ) > 0.0005 || abs( $y - $stored['y'] ) > 0.0005 || abs( $zoom - $stored['zoom'] ) > 0.0005;
		// A new upload is cut at the position sent with it (the centre when none was sent); an old one only when the position changed.
		if ( ! $new_ava && ! ( $has_fields && $moved ) ) {
			return;
		}

		$baked = self::bake_avatar( $user_id, $source_id, $x, $y, $zoom );
		if ( is_wp_error( $baked ) ) {
			add_filter(
				'clanbite_save_player_settings_errors',
				static function ( $errors ) use ( $baked ) {
					$errors                   = (array) $errors;
					$errors['profile_avatar'] = $baked->get_error_message();

					return $errors;
				}
			);

			return;
		}
		$previous = absint( get_user_meta( $user_id, 'cp_player_avatar_id', true ) );
		update_user_meta( $user_id, 'cp_player_avatar_source_id', $source_id );
		update_user_meta( $user_id, 'cp_player_avatar_id', $baked );
		update_user_meta( $user_id, 'cp_player_avatar', wp_get_attachment_url( $baked ) );
		update_user_meta( $user_id, 'cp_player_avatar_focal_x', $x );
		update_user_meta( $user_id, 'cp_player_avatar_focal_y', $y );
		update_user_meta( $user_id, 'cp_player_avatar_zoom', $zoom );
		// The earlier cut-out is replaced (never the original).
		if ( $previous > 0 && $previous !== $source_id && $previous !== $baked ) {
			self::delete_attachment( $previous );
		}
	}

	/**
	 * State of the media editor after a save, so the page can update its previews.
	 *
	 * @param array<string, mixed> $data    Success payload so far.
	 * @param int                  $user_id Player.
	 * @return array<string, mixed>
	 */
	public function add_success_data( $data, $user_id ): array {
		return array_merge( (array) $data, self::editor_state( (int) $user_id ) );
	}

	/**
	 * Everything the position editor needs to know about a player's images.
	 *
	 * @param int $user_id Player.
	 * @return array<string, mixed>
	 */
	public static function editor_state( int $user_id ): array {
		$avatar_id = absint( get_user_meta( $user_id, 'cp_player_avatar_id', true ) );
		$source_id = absint( get_user_meta( $user_id, 'cp_player_avatar_source_id', true ) );
		if ( 0 === $source_id ) {
			$source_id = $avatar_id;
		}
		$cover_id = absint( get_user_meta( $user_id, 'cp_player_cover_id', true ) );
		$pos      = static function ( string $key, float $fallback ) use ( $user_id ): float {
			$raw = get_user_meta( $user_id, $key, true );

			return '' === (string) $raw ? $fallback : (float) $raw;
		};

		return array(
			'hasAvatar'       => $avatar_id > 0,
			'hasCover'        => $cover_id > 0,
			'avatarSourceUrl' => $source_id > 0 ? (string) wp_get_attachment_url( $source_id ) : '',
			'avatarFocal'     => array(
				'x'    => self::clamp_focal( $pos( 'cp_player_avatar_focal_x', 0.5 ) ),
				'y'    => self::clamp_focal( $pos( 'cp_player_avatar_focal_y', 0.5 ) ),
				'zoom' => self::clamp_zoom( $pos( 'cp_player_avatar_zoom', 1.0 ) ),
			),
			'coverPosition'   => array(
				'x'    => self::clamp_focal( $pos( 'cp_player_cover_position_x', 0.5 ) ),
				'y'    => self::clamp_focal( $pos( 'cp_player_cover_position_y', 0.5 ) ),
				'zoom' => self::clamp_zoom( $pos( 'cp_player_cover_zoom', 1.0 ) ),
			),
		);
	}
}
