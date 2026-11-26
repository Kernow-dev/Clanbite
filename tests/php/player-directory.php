<?php
/**
 * The player directory's cards. Run `php tests/php/player-directory.php` against a development database.
 *
 * @package clanbite
 */

require __DIR__ . '/harness.php';

$user_id = wp_insert_user(
	array(
		'user_login'   => 'zz_dir_' . strtolower( wp_generate_password( 6, false ) ),
		'user_pass'    => wp_generate_password(),
		'user_email'   => 'zzdir' . wp_generate_password( 6, false ) . '@example.test',
		'display_name' => '000 ZZ <b>Card</b> [gallery]',
	)
);
$t->cleanup(
	static function () use ( $user_id ): void {
		require_once ABSPATH . 'wp-admin/includes/user.php';
		wp_delete_user( $user_id );
	}
);
update_user_meta( $user_id, 'clanbite_player_tagline', 'ZZ tagline' );

$players = clanbite()->extensions->get( 'clanbite_players' );
$html    = $players->render_players_directory_shortcode();
$card    = '';
foreach ( explode( 'class="clanbite-players-directory__item"', $html ) as $piece ) {
	if ( str_contains( $piece, 'ZZ tagline' ) ) {
		$card = $piece;
	}
}

$t->ok( 'each player gets a card in the list', str_contains( $html, '<ul class="clanbite-players-directory__list">' ) && '' !== $card );
$t->ok( 'the card has the cover, avatar, name and tagline', str_contains( $card, 'clanbite-cover' ) && str_contains( $card, 'clanbite-avatar' ) && str_contains( $card, 'ZZ tagline' ) && str_contains( $card, 'ZZ ' ), $card );
$t->ok( 'the name links to the profile', str_contains( $card, 'href="' . esc_url( clanbite_get_player_profile_url( $user_id ) ) . '"' ), $card );
$t->ok( 'one link per card, so it is read out once', 1 === preg_match_all( '/<a\s/', $card ), preg_match_all( '/<a\s/', $card ) );
$t->ok( 'a name is never treated as markup', ! str_contains( $card, '<b>Card</b>' ) );
$t->ok( 'nothing is left for the shortcode block to wrap in paragraphs', ! str_contains( $html, "\n" ) && ! str_contains( wpautop( $html ), '<br' ) );

$names = static fn(): string => '';
add_filter( 'clanbite_players_directory_card_markup', $names );
$plain = $players->render_players_directory_shortcode();
$t->ok( 'with an empty card template, names are listed with links', str_contains( $plain, 'href="' . esc_url( clanbite_get_player_profile_url( $user_id ) ) . '"' ) && ! str_contains( $plain, 'clanbite-cover' ) );
remove_filter( 'clanbite_players_directory_card_markup', $names );

$mine = static fn( $markup, $id ) => '<!-- wp:paragraph --><p>ZZ custom ' . (int) $id . '</p><!-- /wp:paragraph -->';
add_filter( 'clanbite_players_directory_card_markup', $mine, 10, 2 );
$t->ok( 'a theme can supply its own card', str_contains( $players->render_players_directory_shortcode(), "ZZ custom {$user_id}" ) );

exit( $t->finish() );
