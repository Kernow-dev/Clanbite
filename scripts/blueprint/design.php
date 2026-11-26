<?php
/**
 * Live Preview, part 3: the demo's dark look (Twenty Twenty-Five with its own colours), header, footer and landing page.
 * All of it is ordinary site content, so visitors can change it in the Site Editor.
 *
 * @package clanbite
 */

require_once '/wordpress/wp-load.php';

wp_set_current_user( 1 );

$demo  = (array) get_option( 'clanbite_demo' );
$theme = get_stylesheet();
$teams = (array) $demo['teams'];
$owls  = (int) $teams['night-owls'];
$me    = get_userdata( 1 )->user_nicename;

// Page artwork that no player or team owns.
require_once ABSPATH . 'wp-admin/includes/image.php';
$uploads = wp_upload_dir();
$files   = array();
foreach ( array( 'hero', 'addon-social-kit', 'addon-forums', 'addon-points', 'addon-ranks', 'addon-shop', 'addon-support-desk' ) as $name ) {
	$file = trailingslashit( $uploads['path'] ) . "clanbite-demo-{$name}.webp";
	copy( "/tmp/clanbite-demo-art/{$name}.webp", $file );
	wp_insert_attachment(
		array(
			'post_title'     => $name,
			'post_mime_type' => 'image/webp',
			'post_status'    => 'inherit',
		),
		$file
	);
	$files[ $name ] = trailingslashit( $uploads['url'] ) . "clanbite-demo-{$name}.webp";
}

$team_badge = static fn( int $id ): string => (string) wp_get_attachment_url( (int) get_post_meta( $id, 'clanbite_team_avatar_id', true ) );
$team_cover = static fn( int $id ): string => (string) wp_get_attachment_url( (int) get_post_meta( $id, 'clanbite_team_cover_id', true ) );
$team_url   = static fn( int $id, string $path = '' ): string => trailingslashit( (string) get_permalink( $id ) ) . $path;

// ---- Look ----
$palette = array(
	'base'     => array( 'Background', '#0e1219' ),
	'contrast' => array( 'Text', '#e9eef5' ),
	'accent-1' => array( 'Highlight', '#9ecbe8' ),
	'accent-2' => array( 'Highlight text', '#c9e4f6' ),
	'accent-3' => array( 'Strong highlight', '#5aa7e0' ),
	'accent-4' => array( 'Muted text', '#9aa8ba' ),
	'accent-5' => array( 'Panel', '#171e29' ),
	'accent-6' => array( 'Lines', 'rgba(158,203,232,0.18)' ),
);
$colours = array();
foreach ( $palette as $slug => list( $name, $colour ) ) {
	$colours[] = array(
		'slug'  => $slug,
		'name'  => $name,
		'color' => $colour,
	);
}

$css = <<<'CSS'
:root{--clanbite-surface:#171e29;--clanbite-surface-alt:#171e29;--clanbite-border:rgba(158,203,232,.18);--clanbite-accent:#9ecbe8}
body{background:radial-gradient(1200px 500px at 50% -120px,#1d2b44 0,#0e1219 70%) no-repeat,#0e1219}
.demo-bar{padding:.9rem 0;border-bottom:1px solid rgba(158,203,232,.18)}
.demo-bar .wp-block-site-title a{text-decoration:none;font-weight:800;letter-spacing:.04em;text-transform:uppercase}
.demo-bar .wp-block-navigation a{text-decoration:none}
.demo-bar .wp-block-navigation a:hover{color:#9ecbe8}
.demo-foot{margin-top:4rem;padding:1.5rem 0;border-top:1px solid rgba(158,203,232,.18);font-size:.85rem;color:#9aa8ba;text-align:center}
.cb-wrap{box-sizing:border-box;width:100%;max-width:1140px;margin:0 auto;padding:0 1.25rem;display:flex;flex-direction:column;gap:3.5rem}
.cb-wrap *{box-sizing:border-box}
.cb-wrap h1,.cb-wrap h2,.cb-wrap h3,.cb-wrap p{margin:0}
.cb-wrap h2{font-size:1.5rem;margin-bottom:1rem}
.cb-hero{position:relative;overflow:hidden;border-radius:22px;border:1px solid rgba(158,203,232,.25);background-size:cover;background-position:center bottom;display:grid;grid-template-columns:minmax(0,1.15fr) minmax(0,.85fr);gap:2rem;align-items:center;padding:3.25rem 2.5rem}
.cb-hero::before{content:"";position:absolute;inset:0;background:linear-gradient(90deg,rgba(8,11,17,.92),rgba(8,11,17,.35))}
.cb-hero>*{position:relative}
.cb-hero h1{font-size:clamp(2rem,4.2vw,3.1rem);line-height:1.08;color:#fff;font-weight:800}
.cb-hero p{margin-top:1rem;font-size:1.05rem;color:#d5e0ec;max-width:32rem}
.cb-actions{display:flex;flex-wrap:wrap;gap:.75rem;margin-top:1.5rem}
.cb-btn{display:inline-block;padding:.7rem 1.25rem;border-radius:999px;font-weight:700;text-decoration:none!important;border:2px solid #9ecbe8;color:#9ecbe8!important}
.cb-btn.is-solid{background:#9ecbe8;color:#0b1119!important}
.cb-btn:hover,.cb-btn:focus-visible{filter:brightness(1.12);outline-offset:3px}
.cb-live{display:block;text-decoration:none!important;color:#fff!important;background:rgba(12,17,26,.82);border:1px solid rgba(158,203,232,.3);border-radius:18px;padding:1.25rem}
.cb-live:hover,.cb-live:focus-visible{border-color:#9ecbe8}
.cb-live__tag{display:flex;justify-content:center;align-items:center;gap:.5rem;font-size:.75rem;font-weight:800;letter-spacing:.12em;text-transform:uppercase;color:#ff8a8a}
.cb-live__tag i{width:.55rem;height:.55rem;border-radius:50%;background:#ff5252;animation:cb-pulse 1.4s ease-in-out infinite}
@keyframes cb-pulse{50%{opacity:.25}}
@media (prefers-reduced-motion:reduce){.cb-live__tag i{animation:none}}
.cb-live__row{display:grid;grid-template-columns:1fr auto 1fr;gap:.75rem;align-items:center;margin:1rem 0 .75rem;text-align:center;font-weight:700}
.cb-live__row img{display:block;width:76px;height:76px;margin:0 auto .5rem;border-radius:14px}
.cb-live__score{font-size:2rem;font-weight:800;white-space:nowrap}
.cb-live__foot{display:block;text-align:center;font-size:.85rem;color:#9aa8ba}
.cb-grid{display:grid;gap:1.25rem;grid-template-columns:repeat(auto-fit,minmax(min(100%,17rem),1fr))}
.cb-card{display:flex;flex-direction:column;overflow:hidden;border-radius:16px;background:#171e29;border:1px solid rgba(158,203,232,.18);text-decoration:none!important;color:#e9eef5!important;transition:transform .15s ease,border-color .15s ease}
.cb-card:hover,.cb-card:focus-visible{transform:translateY(-3px);border-color:#9ecbe8}
@media (prefers-reduced-motion:reduce){.cb-card{transition:none}.cb-card:hover{transform:none}}
.cb-card__banner{height:96px;background-size:cover;background-position:center}
.cb-card__body{padding:1rem 1.15rem 1.2rem;display:flex;flex-direction:column;gap:.35rem;font-size:1rem;line-height:1.45}
.cb-card__body img{width:64px;height:64px;border-radius:14px;margin-top:-3rem;border:3px solid #171e29}
.cb-card__body strong{font-size:1.3rem}
.cb-muted{color:#9aa8ba;font-size:.9rem}
.cb-try .cb-card__body{padding-top:1.2rem}
.cb-try b{font-size:.75rem;letter-spacing:.12em;text-transform:uppercase;color:#9ecbe8}
.cb-addons{display:grid;gap:1rem;grid-template-columns:repeat(auto-fit,minmax(min(45%,9.5rem),1fr))}
.cb-addons a{display:block;border-radius:14px;overflow:hidden;border:1px solid rgba(158,203,232,.18);line-height:0}
.cb-addons a:hover,.cb-addons a:focus-visible{border-color:#9ecbe8}
.cb-addons img{width:100%;height:auto}
.cb-cta{text-align:center;padding:2.5rem 1.5rem;border-radius:22px;background:linear-gradient(135deg,#1b2940,#14202f);border:1px solid rgba(158,203,232,.25)}
.cb-cta .cb-actions{justify-content:center}
.clanbite-match-list{list-style:none;margin:0;padding:0;display:grid;gap:.75rem}
.clanbite-match-list__link{display:flex;flex-wrap:wrap;justify-content:space-between;align-items:center;gap:.35rem 1rem;padding:.9rem 1.15rem;border-radius:12px;background:#171e29;border:1px solid rgba(158,203,232,.18);text-decoration:none!important;color:#e9eef5!important}
.clanbite-match-list__link:hover,.clanbite-match-list__link:focus-visible{border-color:#9ecbe8}
.clanbite-match-list__teams{font-weight:700;font-size:1.05rem}
.clanbite-match-list__meta{font-size:.85rem;color:#9aa8ba}
.clanbite-players-directory__list{list-style:none;margin:0;padding:0}
.clanbite-players-directory__item>a:only-child{display:block;padding:1rem 1.15rem;border-radius:12px;background:#171e29;border:1px solid rgba(158,203,232,.18);text-decoration:none;font-weight:700}
.clanbite-players-directory__list:has(>li>a:only-child){display:grid;gap:.75rem;grid-template-columns:repeat(auto-fill,minmax(min(100%,13rem),1fr))}
/* Fixes that Clanbite 1.9.0 includes; they keep the preview right on the release before it. */
.clanbite-event-calendar__dow,.clanbite-event-calendar__grid{grid-template-columns:repeat(7,minmax(0,1fr))!important}
.clanbite-event-calendar__cell{min-width:0}
.wp-block-group:has(> .wp-block-group > .clanbite-team-cover-block.clanbite-cover--placeholder){display:none}
.wp-element-button.clanbite-button{display:inline-flex;align-items:center;justify-content:center;padding:.55rem 1.1rem;line-height:1.2}
.cb-page h1{margin:0 0 1.5rem}
.clanbite-notifications-page br,.clanbite-notifications-page p:empty{display:none}
.clanbite-notifications-page .clanbite-notification{display:flex;align-items:flex-start;gap:.85rem;padding:1rem}
.clanbite-notifications-page .clanbite-notification+.clanbite-notification{border-top:1px solid rgba(158,203,232,.18)}
.clanbite-notifications-page .clanbite-notification__avatar,.clanbite-notifications-page .clanbite-notification__icon{flex:0 0 2.75rem}
.clanbite-notifications-page .clanbite-notification__content{display:flex;flex-direction:column;gap:.35rem;flex:1;min-width:0;font-size:1rem}
.clanbite-notifications-page .clanbite-notification__header{display:flex;flex-wrap:wrap;justify-content:space-between;align-items:baseline;gap:.15rem .75rem}
.clanbite-notifications-page .clanbite-notification__title{font-weight:700}
.clanbite-notifications-page .clanbite-notification__time{font-size:.8125rem;color:#9aa8ba}
.clanbite-notifications-page .clanbite-notification__message{margin:0;font-size:.9375rem}
.clanbite-notifications-page .clanbite-notification__actions{display:flex;flex-wrap:wrap;gap:.5rem;margin-top:.35rem}
@media (max-width:760px){.cb-hero{grid-template-columns:1fr;padding:2rem 1.25rem}}
CSS;

$styles  = array(
	'version'                     => 3,
	'isGlobalStylesUserThemeJSON' => true,
	'settings'                    => array( 'color' => array( 'palette' => array( 'theme' => $colours ) ) ),
	'styles'                      => array(
		'color'    => array(
			'background' => 'var(--wp--preset--color--base)',
			'text'       => 'var(--wp--preset--color--contrast)',
		),
		'elements' => array(
			'link'    => array( 'color' => array( 'text' => 'var(--wp--preset--color--accent-1)' ) ),
			'heading' => array( 'color' => array( 'text' => '#ffffff' ) ),
			'button'  => array(
				'color'  => array(
					'background' => '#9ecbe8',
					'text'       => '#0b1119',
				),
				'border' => array( 'radius' => '999px' ),
			),
		),
		'css'      => $css,
	),
);
$store   = static function ( string $type, string $name, string $title, string $content, string $area = '' ) use ( $theme ): void {
	$id = wp_insert_post(
		wp_slash(
			array(
				'post_type'    => $type,
				'post_status'  => 'publish',
				'post_name'    => $name,
				'post_title'   => $title,
				'post_content' => $content,
			)
		)
	);
	wp_set_object_terms( $id, $theme, 'wp_theme' );
	if ( '' !== $area ) {
		wp_set_object_terms( $id, $area, 'wp_template_part_area' );
	}
};
$store( 'wp_global_styles', 'wp-global-styles-' . $theme, 'Custom Styles', (string) wp_json_encode( $styles ) );

// ---- Header and footer ----
$nav_link = static fn( string $label, string $url ): string => '<!-- wp:navigation-link ' . wp_json_encode(
	array(
		'label' => $label,
		'url'   => $url,
		'kind'  => 'custom',
	)
) . ' /-->';
$header   = '<!-- wp:group {"className":"demo-bar","align":"full","layout":{"type":"constrained","contentSize":"1100px"}} --><div class="wp-block-group alignfull demo-bar">'
	. '<!-- wp:group {"style":{"spacing":{"padding":{"left":"1.25rem","right":"1.25rem"}}},"layout":{"type":"flex","flexWrap":"wrap","justifyContent":"space-between"}} --><div class="wp-block-group" style="padding-right:1.25rem;padding-left:1.25rem">'
	. '<!-- wp:site-title {"level":0} /-->'
	. '<!-- wp:navigation {"layout":{"type":"flex","justifyContent":"right"}} -->'
	. $nav_link( 'Home', home_url( '/' ) )
	. $nav_link( 'Teams', home_url( '/teams/' ) )
	. $nav_link( 'Players', home_url( '/players/' ) )
	. $nav_link( 'Calendar', $team_url( $owls, 'events/' ) )
	. $nav_link( 'My profile', home_url( "/players/{$me}/" ) )
	. $nav_link( 'Notifications', home_url( "/players/{$me}/notifications/" ) )
	. '<!-- /wp:navigation --></div><!-- /wp:group --></div><!-- /wp:group -->';
$footer   = '<!-- wp:group {"className":"demo-foot","align":"full","layout":{"type":"constrained"}} --><div class="wp-block-group alignfull demo-foot">'
	. '<!-- wp:paragraph {"align":"center"} --><p class="has-text-align-center">A temporary Clanbite demo with made-up players and teams. Nothing is saved when you close it. <a href="https://wordpress.org/plugins/clanbite/" target="_blank" rel="noopener">Download Clanbite</a></p><!-- /wp:paragraph -->'
	. '</div><!-- /wp:group -->';
// Before Clanbite 1.9.0 the buttons on the notifications page did nothing (they only worked in the notification bell), so
// on an older release the preview runs them itself.
if ( version_compare( \Kernowdev\Clanbite\Main::VERSION, '1.9.0', '<' ) ) {
	$footer .= '<!-- wp:html --><script>
document.addEventListener( "click", async ( event ) => {
	const button = event.target.closest( ".clanbite-notifications-page button[data-action][data-notification-id]" );
	if ( ! button ) {
		return;
	}
	const page = button.closest( ".clanbite-notifications-page" );
	const actions = button.closest( ".clanbite-notification__actions" );
	actions.querySelectorAll( "button" ).forEach( ( other ) => ( other.disabled = true ) );
	const note = document.createElement( "div" );
	note.className = "clanbite-notification__status";
	note.setAttribute( "role", "status" );
	try {
		const response = await fetch( page.dataset.clanbiteNotificationsRest.replace( /\\/?$/, "/" ) + "notifications/" + button.dataset.notificationId + "/action", {
			method: "POST",
			credentials: "same-origin",
			headers: { "Content-Type": "application/json", "X-WP-Nonce": page.dataset.clanbiteNotificationsNonce },
			body: JSON.stringify( { action: button.dataset.action } ),
		} );
		const data = await response.json();
		note.textContent = data.message || ( response.ok ? "Done." : "That did not work." );
		if ( response.ok ) {
			actions.replaceWith( note );
		} else {
			actions.after( note );
		}
	} catch {
		note.textContent = "That did not work.";
		actions.after( note );
	}
} );
</script><!-- /wp:html -->';
}

$store( 'wp_template_part', 'header', 'Header', $header, 'header' );
$store( 'wp_template_part', 'footer', 'Footer', $footer, 'footer' );
$store(
	'wp_template',
	'front-page',
	'Front Page',
	'<!-- wp:template-part {"slug":"header","tagName":"header"} /--><!-- wp:group {"tagName":"main","style":{"spacing":{"margin":{"top":"2rem"}}},"layout":{"type":"default"}} --><main class="wp-block-group" style="margin-top:2rem"><!-- wp:post-content {"layout":{"type":"default"}} /--></main><!-- /wp:group --><!-- wp:template-part {"slug":"footer","tagName":"footer"} /-->'
);

// ---- Landing page ----
$live  = (int) $demo['live'];
$home  = (int) get_post_meta( $live, 'clanbite_match_home_team_id', true );
$away  = (int) get_post_meta( $live, 'clanbite_match_away_team_id', true );
$score = (int) get_post_meta( $live, 'clanbite_match_home_score', true ) . ' – ' . (int) get_post_meta( $live, 'clanbite_match_away_score', true );
$side  = static fn( int $id ): string => '<span><img src="' . esc_url( $team_badge( $id ) ) . '" alt="">' . esc_html( get_the_title( $id ) ) . '</span>';

$hero = '<section class="cb-hero" style="background-image:url(' . esc_url( $files['hero'] ) . ')">'
	. '<div><h1>Your whole gaming community, on your own site</h1><p>Teams, players, matches and events for clans and esports clubs, built into WordPress. You are signed in as the admin, so try anything.</p>'
	. '<div class="cb-actions"><a class="cb-btn is-solid" href="' . esc_url( get_permalink( $owls ) ) . '">Explore a team</a><a class="cb-btn" href="' . esc_url( home_url( "/players/{$me}/notifications/" ) ) . '">You have a team invite</a></div></div>'
	. '<a class="cb-live" href="' . esc_url( get_permalink( $live ) ) . '"><span class="cb-live__tag"><i aria-hidden="true"></i>Live now</span>'
	. '<span class="cb-live__row">' . $side( $home ) . '<span class="cb-live__score">' . esc_html( $score ) . '</span>' . $side( $away ) . '</span>'
	. '<span class="cb-live__foot">' . esc_html( get_post_meta( $live, 'clanbite_match_venue', true ) ) . ' · open the match</span></a></section>';

$html  = static fn( string $markup ): string => "<!-- wp:html -->\n{$markup}\n<!-- /wp:html -->";
$cards = '';
$count = static fn( int $number, string $one, string $many ): string => $number . ' ' . ( 1 === $number ? $one : $many );
foreach ( $teams as $team_id ) {
	$record = implode(
		' · ',
		array(
			$count( (int) get_post_meta( $team_id, 'clanbite_team_wins', true ), 'win', 'wins' ),
			$count( (int) get_post_meta( $team_id, 'clanbite_team_losses', true ), 'loss', 'losses' ),
			$count( (int) get_post_meta( $team_id, 'clanbite_team_draws', true ), 'draw', 'draws' ),
		)
	);
	$cards .= '<a class="cb-card" href="' . esc_url( get_permalink( $team_id ) ) . '"><span class="cb-card__banner" style="background-image:url(' . esc_url( $team_cover( $team_id ) ) . ')"></span><span class="cb-card__body"><img src="' . esc_url( $team_badge( $team_id ) ) . '" alt=""><strong>' . esc_html( get_the_title( $team_id ) ) . '</strong><span>' . esc_html( get_post_meta( $team_id, 'clanbite_team_motto', true ) ) . '</span><span class="cb-muted">' . esc_html( $record ) . '</span></span></a>';
}

// The team list: without a template of its own the theme would show teams as blog posts.
$store(
	'wp_template',
	'archive-clanbite_team',
	'Teams',
	'<!-- wp:template-part {"slug":"header","tagName":"header"} /--><!-- wp:group {"tagName":"main","style":{"spacing":{"margin":{"top":"2rem"}}},"layout":{"type":"default"}} --><main class="wp-block-group" style="margin-top:2rem">'
	. $html( '<div class="cb-wrap cb-page"><section><h1>Teams</h1><div class="cb-grid">' . $cards . '</div></section></div>' )
	. '</main><!-- /wp:group --><!-- wp:template-part {"slug":"footer","tagName":"footer"} /-->'
);

$try = '';
foreach ( array(
	array( 'As a player', 'Answer your invite', 'The Iron Wolves want you. Accept or decline from your notifications.', home_url( "/players/{$me}/notifications/" ) ),
	array( 'As a captain', 'Run the Night Owls', 'Change the roster, motto, badge and banner from the team itself.', $team_url( $owls, 'manage/' ) ),
	array( 'As the owner', 'Make it yours', 'Switch extensions on and off and choose how your community works.', admin_url( 'admin.php?page=clanbite' ) ),
) as list( $role, $title, $copy, $url ) ) {
	$try .= '<a class="cb-card" href="' . esc_url( $url ) . '"><span class="cb-card__body"><b>' . esc_html( $role ) . '</b><strong>' . esc_html( $title ) . '</strong><span class="cb-muted">' . esc_html( $copy ) . '</span></span></a>';
}

$addons = '';
foreach ( array(
	'social-kit'   => 'Social Kit: activity feeds, friends and groups',
	'forums'       => 'Forums',
	'points'       => 'Points',
	'ranks'        => 'Ranks',
	'shop'         => 'Shop',
	'support-desk' => 'Support Desk',
) as $slug => $label ) {
	$addons .= '<a href="https://clanbite.com/" target="_blank" rel="noopener"><img src="' . esc_url( $files[ "addon-{$slug}" ] ) . '" alt="' . esc_attr( $label . ' (opens clanbite.com)' ) . '" width="420" height="420" loading="lazy"></a>';
}

$content = $html( '<div class="cb-wrap">' . $hero . '<section><h2>Meet the teams</h2><div class="cb-grid">' . $cards . '</div></section><section class="cb-try"><h2>Try it yourself</h2><div class="cb-grid">' . $try . '</div></section></div>' )
	. '<!-- wp:group {"className":"cb-wrap","style":{"spacing":{"margin":{"top":"3.5rem"}}},"layout":{"type":"default"}} --><div class="wp-block-group cb-wrap" style="margin-top:3.5rem">'
	. '<!-- wp:group {"layout":{"type":"default"}} --><div class="wp-block-group"><!-- wp:heading --><h2 class="wp-block-heading">Latest results</h2><!-- /wp:heading -->'
	. '<!-- wp:clanbite/match-list {"statusFilter":"finished","order":"desc","limit":4} /--></div><!-- /wp:group --></div><!-- /wp:group -->'
	. $html(
		'<div class="cb-wrap" style="margin-top:3.5rem"><section><h2>Add more when you need it</h2><p class="cb-muted" style="margin-bottom:1rem">This demo is the free plugin. These extensions are not installed here.</p><div class="cb-addons">' . $addons . '</div></section>'
		. '<section class="cb-cta"><h2>Start your community today</h2><p class="cb-muted">Clanbite is free. Install it from your WordPress admin or download it.</p><div class="cb-actions"><a class="cb-btn is-solid" href="https://wordpress.org/plugins/clanbite/" target="_blank" rel="noopener">Download Clanbite</a></div></section></div>'
	);

$page_id = (int) wp_insert_post(
	wp_slash(
		array(
			'post_type'    => 'page',
			'post_status'  => 'publish',
			'post_title'   => 'Home',
			'post_name'    => 'welcome',
			'post_content' => $content,
		)
	)
);
update_option( 'show_on_front', 'page' );
update_option( 'page_on_front', $page_id );

wp_delete_post( 1, true ); // "Hello world!".
wp_delete_post( 2, true ); // "Sample Page".
delete_option( 'clanbite_demo' );
wp_clean_theme_json_cache();
delete_option( 'rewrite_rules' );
