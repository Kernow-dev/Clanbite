=== Clanbite: Team Management System ===
Contributors: kernowdev
Tags: community, teams, esports, gaming
Requires at least: 6.7
Tested up to: 6.9
Requires PHP: 8.2
Stable tag: 1.0.0
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

== External services ==

* **Gravatar (Automattic):** In-site notifications can show an actor portrait using WordPress `get_avatar_url()`, which may resolve to Gravatar when the user has no local Clanbite player avatar. The visitor's browser loads that image URL (standard `<img>` request). No passwords are transmitted to Gravatar. Terms: https://wordpress.com/tos/ — Privacy: https://automattic.com/privacy/

== Source code ==

JavaScript and CSS distributed with this plugin (for example under the `build/` directory) are **compiled** from human-readable sources. The WordPress.org download does not include those development sources, to keep the install package smaller.

The full, reviewable source—including block sources, the admin settings bundle, and build configuration—is maintained in this public repository:

* https://github.com/Kernow-dev/Clanbite

In that repository, non-compiled code lives mainly under `src/blocks/` (Gutenberg blocks and front-end assets), `src/admin/` (unified admin UI), and `src/cp-match-editor/` (match document sidebar). See the `package.json` file there for npm dependencies and scripts used to produce the production assets (`npm ci`, `npm run build`).

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

= 1.0.0 =
* Initial WordPress.org release.
* Unified maintenance step and database schema versioning for new installs.

== Upgrade Notice ==

= 1.0.0 =
First public release on WordPress.org. If you tested pre-release builds, visit Permalinks and save once after upgrading.
