=== Clanbite: Team Management System ===
Contributors: kernowdev
Tags: community, teams, esports, gaming
Requires at least: 6.9
Tested up to: 7.1
Requires PHP: 8.2
Stable tag: 1.6.7
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Community tools for gaming teams and clubs: player profiles, teams, matches, events, notifications, and an extension system.

== Description ==

Clanbite helps you run team and player profiles, schedules, and community features inside WordPress.

* **Players** — Extended player profiles and settings.
* **Teams** — Rosters, roles, and team-facing templates and blocks.
* **Matches** — Match records with editor and front-end blocks (requires Teams).
* **Events & RSVP** — Event posts with RSVP storage for teams and groups.
* **Notifications** — In-site notifications with a block-ready bell.
* **Extensions** — Enable bundled features from **Clanbite → Extensions**; third-party plugins can register their own extensions via hooks.

The plugin follows modern WordPress APIs (blocks, REST where used, block themes). See `README.md` in the plugin package for developer hooks and architecture notes.

Human-readable JavaScript and CSS: minified files under `build/` and `assets/dist/` are produced from the sources described in the Human-readable source code section below. Clone the public repository linked there to review or fork the same sources used to build this package.

== External services ==

* **Gravatar (Automattic):** In-site notifications can show an actor portrait using WordPress `get_avatar_url()`, which may resolve to Gravatar when the user has no local Clanbite player avatar. The visitor's browser loads that image URL (standard `<img>` request). No passwords are transmitted to Gravatar. Terms: https://wordpress.com/tos/ — Privacy: https://automattic.com/privacy/

== Human-readable source code ==

JavaScript and CSS shipped in this plugin (for example `build/**/index.js`, block `view.js` bundles, and `assets/dist/clanbite-admin.js`) are **compiled and minified** for performance. Per the [WordPress plugin guidelines](https://developer.wordpress.org/plugins/wordpress-org/detailed-plugin-guidelines/#4-code-must-be-mostly-human-readable), the original source is publicly available so it can be reviewed, studied, and forked.

**Public repository (canonical source tree):** https://github.com/Kernow-dev/Clanbite

The WordPress.org plugin download omits the `src/` tree and Node tooling to keep installs small; those files are in the repository above at the same paths as this release’s tag/branch.

**Where to read the non-compiled code**

* **Block editor and front-end blocks** — `src/blocks/` (matches, players, teams, events, notifications, core blocks). Each block’s `index.js`, `edit.js`, `view.js`, and related modules compile into matching paths under `build/` (for example `src/blocks/events/event-calendar/` → `build/events/event-calendar/`).
* **Clanbite admin (React settings shell)** — `src/admin/` → `assets/dist/clanbite-admin.js` (see `webpack.config.cjs` in the repository).
* **Match post editor sidebar** — `src/clanbite-match-editor/src/` → `build/clanbite-match-editor/`.

**Third-party JavaScript** used at build time (for example `@wordpress/scripts` and `@wordpress/*` packages) is declared in `package.json` / `package-lock.json` in that repository. Those packages are open-source; versions are pinned in the lockfile for reproducible builds.

**Rebuild production assets** (from a clone of the repository, with Node.js and npm installed):

1. `npm ci`
2. `npm run build:production` — runs the admin webpack build, then the block build (including block manifests and related steps). Equivalent to the assets in a release ZIP.
3. Optional: `npm run build:admin` or `npm run build:blocks` to rebuild only the admin bundle or only blocks (see `package.json` `"scripts"` for other targets such as `plugin-zip`).

For PHP architecture, hooks, and REST, see the bundled `README.md`.

== Installation ==

1. Upload the plugin files to `/wp-content/plugins/clanbite` or install the ZIP from the Plugins screen.
2. Activate **Clanbite** through the **Plugins** menu.
3. Open **Clanbite** in the admin menu, review **General** settings, and enable the extensions you need (Players is a common starting point).
4. Visit **Settings → Permalinks** and click **Save** once if routes for teams or events do not resolve (the plugin flushes rules on upgrade, but a manual save fixes edge cases).

== Frequently Asked Questions ==

= Does this work with block themes? =

Yes. Clanbite registers block types and plugin block templates for full-site editing where applicable.

= Where are the settings? =

Use the **Clanbite** top-level admin menu for core options and extension toggles. On the **Plugins** screen, use the **Settings** link (administrators) or **Website** to open clanbite.com.

== Support ==

* Documentation: see the bundled `README.md` for developers (hooks, extensions, REST).
* Plugin site: https://clanbite.com
* Help and bug reports: https://github.com/Kernow-dev/Clanbite/issues
* WordPress.org: after the plugin is listed on the directory, use the support forum at https://wordpress.org/support/plugin/clanbite/ for site-owner questions.

== Screenshots ==

1. Clanbite admin settings and extension management.
2. Team and player blocks in the block editor.

== Changelog ==

= 1.6.7 =
* Changed: every stored name that still used the old `cp_` prefix (extension slugs, the group post type, meta keys, options and the add-ons' tables) now uses `clanbite_`. A one-time upgrade moves existing data across; nothing needs doing by hand. Add-ons that read the old names must update. The player Events page now picks an event with `?clanbite_event_view=`, and the event-post visibility filter is `clanbite_event_post_viewer_can_see`.
* Improved: blocks now look like the front end in the block editor (server-rendered previews with sample content where a site has none).
* Maintenance: the developer-docs workflow is pinned to a reviewed commit, passes only the secrets it needs, and a manual republish now requires a published release tag.

= 1.6.6 =
* New: the official Clanbite Shop add-on is recognised as a first-party extension (`clanbite_shop`).

= 1.6.5 =
* Fix: block stylesheets (of Clanbite and every add-on) are now versioned by their build time, so a browser or CDN that cached the old CSS loads the new one after an update.

= 1.6.4 =
* New: players can create their own events and invite friends. Each event has a privacy setting: Anyone, Friends or Only me. Invited friends get a notification and can RSVP; the player's Events tab shows their own calendar (events they created or were invited to) and, to visitors, only the events their privacy setting allows.
* Settings: a new **Events** tab holds everything for events: the on/off switch (was under General), "Let players create their own events", and the Events tab toggles for player, team and group profiles (were on the Players, Teams and Groups tabs). Your existing values are carried over.
* Settings: "Enable team ranks" and the rank status-update switch now live only under Ranks (the duplicate switch on the Players tab is gone).
* Fix: open popups (reaction tooltips and pickers, post and share menus) move into the browser's top layer so no post, card or avatar can cover or clip them.

= 1.6.3 =
* Players can choose which part of their avatar and cover image is shown: in Player settings → Profile, "Set avatar" and "Set cover image" open a popup where you choose an image, drag the focal point, zoom, reset the position or remove the image to go back to the default. The avatar is cut to the chosen square (the original is kept so you can reposition it later). Developers can add their own controls to the popup (see the README).
* Components registry: avatars, popups, clipboard, relative time and the extensions' own pieces (reactions, @mentions) are registered once and used by every extension, so third parties change them in one place. See the README (clanbite_register_component(), clanbite_render_component(), window.clanbite.components).
* Avatars carry where they are shown (a class such as is-forum or is-social-feed) and who is looking, for per-surface styling and filters (clanbite_avatar_args).
* Popups share one lifecycle: clanbite.popup.toggled and clanbite.popup.shouldOpen now cover the user menu, notification bell, challenge dialog and the feed and forum menus and pickers.
* Relative time ("5 minutes ago") is built in one place with a filter (clanbite_human_time_ago).
* Social links on player profiles show the correct icons again (Steam, Facebook, X, Instagram, Discord, TikTok, Bluesky, Reddit, Mastodon).
* Fix: the avatar saved from the position popup is shown correctly instead of an older cached picture.
* Fix: the XP ring around a hexagon avatar starts at the top (12 o'clock) instead of at the left of the top corner, and no longer animates on page load.
* Fix: avatars no longer cover popups that open above them (reaction tooltips over the friends card).
* Fix: the player notifications page layout (the "Mark all as read" button and each notification) is no longer broken by stray paragraphs.

= 1.6.2 =
* Notifications now show the same avatar as the rest of the site (shape, XP ring and rank badge), in the bell and on the notifications page.

= 1.6.1 =
* Fixes: the "After signing in, send members to" setting now works with the normal login form (it was ignored because the form always sends the dashboard address). Settings that move members after sign-in leave a destination chosen by another plugin alone, and updating Clanbite no longer reruns the one-time post-type migration.

= 1.6.0 =
* Install add-ons from the Licence tab: it lists every add-on your licence includes (and the free ones, even without a licence) with Install and Activate buttons, so there is nothing to download by hand. Packages are checked and signed like updates.
* New setting under Clanbite → General → Login: send members to the dashboard, the home page or a custom page after they sign in.
* A tidier Licence tab, and updates that no longer show a "not tested with your version of WordPress" warning when the add-on is tested with your WordPress release line.
* Free add-ons can be updated without a licence.
* Add-on updates: Clanbite add-ons that carry a licence can now be updated from Plugins → Updates. Enter your licence key under Clanbite → Settings → Licence, or use the Activate link under the add-on on the Plugins screen. Updates are checked against your licence, verified before they install, and only offered when they work with your version of Clanbite.
* Add-on safety guard: if an add-on declares that it cannot run on the installed Clanbite, Clanbite switches it off with a notice instead of letting it break the site, and switches it back on once it is compatible.

= 1.5.0 =
* New shared popups ("toasts") for every Clanbite extension: one position, length and look. Settings under Notifications → Popups: on/off, position (six corners), how long they stay (a preset or your own number of seconds), pause when hovered, and a countdown bar that fills as a popup nears expiry. Developers use `clanbite.toasts.show()`; everything is filterable (`clanbite.toasts.*` in the browser, `clanbite_toast_*` in PHP).
* The notification bell and the player settings screen now use the shared popups.
* New Messages sub-tab on each extension's settings tab (Teams → Messages, Ranks → Messages, …): customise the wording of notifications, popups and automatic posts. Teams, Events and the player settings popups are included; extensions register their own with `clanbite_register_message()` (see README).
* Admin: dropdown labels are sentence case like the rest of the screen, and the settings code documentation was completed.

= 1.4.0 =
* Redesigned Clanbite → Settings to match the WordPress admin: full-width header with tabs, white cards, native buttons, toggles and modals, a responsive layout, and a save confirmation that stays in view.
* New sub-navigation for every settings tab, for extensions to add their own views: `clanbite_register_admin_sub_tab()` and the `clanbite_admin_rest_sub_tabs` filter (see README).
* The Clanbite menu icon now follows the admin colour scheme (hover, active and custom schemes).
* Player avatars carry `data-clanbite-avatar-type` and `data-clanbite-avatar-id`, so the Ranks extension can update the ring and badge without a page refresh.

= 1.3.0 =
* Tested up to WordPress 7.1.
* New shared avatar system: shaped frame (circle, square, hexagon), XP progress ring and level badge, filterable with `clanbite_avatar_data`.
* New shared form layout (`clanbite-forms`) for team and group management; fixes for the team cover and profile header on dark themes.
* Shared button variant `clanbite-button--plain`.

= 1.1.0 =
* WordPress 7.0 compatibility verified.
* Added PHP 8.2+ and WordPress 6.7+ requirements validation.
* Fixed block category duplication prevention.
* Improved extension version comparison safety.
* Enhanced code documentation and error handling.

= 1.0.0 =
* Initial WordPress.org release.
* Unified maintenance step and database schema versioning for new installs.

== Upgrade Notice ==

= 1.1.0 =
WordPress 7.0 compatible. Includes improved requirements checking and minor bug fixes. Safe to upgrade.

= 1.0.0 =
First public release on WordPress.org. If you tested pre-release builds, visit Permalinks and save once after upgrading.
