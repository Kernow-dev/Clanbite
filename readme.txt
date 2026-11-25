=== Clanbite: Team Management System ===
Contributors: kernowdev
Tags: community, esports, gaming, teams, clan
Requires at least: 6.9
Tested up to: 7.1
Requires PHP: 8.4
Stable tag: 1.7.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Run your clan or esports club on WordPress: profiles, teams, matches, events, and challenges between Clanbite sites.

== Description ==

**Your clan deserves its own home.**

Clanbite turns WordPress into a community site for gaming clans, esports teams and clubs. Players get profiles, teams get their own pages, matches and events go on the calendar, and other Clanbite communities can challenge yours.

It's your website, on your hosting, with your branding. Your community isn't buried in someone else's feed.

[Website](https://clanbite.com) | [Developer docs](https://developer.clanbite.com) | [GitHub](https://github.com/Kernow-dev/Clanbite) | [Report a bug](https://github.com/Kernow-dev/Clanbite/issues)

= Challenge other communities, even on their own websites =

This is the part I most want you to try. Clanbite sites can talk to each other, once both site owners turn it on.

1. A rival visits your team page, clicks **Challenge**, and enters the address of their Clanbite site.
2. Clanbite looks up their team and pulls in its public details.
3. Your team admins get a notification and can accept or decline.
4. Accept, and the match is created on your site **and sent to theirs**, so both communities have it listed.

Matches sent between sites are signed with Ed25519 keys that each site creates for itself, so there are no API keys or shared passwords to swap.

Opponents who don't use Clanbite can still challenge you by filling in their team's details, and each team chooses whether it accepts challenges.

*Connections between sites are off by default: turn on **Connect with other Clanbite sites** under Clanbite → Settings → General. Two-way match listings need that, plus the Teams and Matches extensions, HTTPS and the PHP sodium extension, on both sites.*

= Player profiles with personality =

* A public profile for every player, plus a player directory
* Avatars and cover images that players can drag and zoom into position
* Circle, square or hexagon avatars
* Tagline, bio, location, birthday, website and social links
* Profile settings that players manage from the front of your site

= Teams, however your community works =

* Run **one team**, **several teams** under one clan, or a **team directory** where members create their own
* Admin, editor, member and banned roles
* Invite players when you create a team or at any time afterwards, and they join when they accept (needs the Notifications extension)
* Team pages with avatar, cover, motto, country, roster and win, loss and draw records
* Create and manage teams from the front of your site

= Matches, events and RSVPs =

* Publish matches with teams, times and scores, then show them as cards or lists
* Month, week, day and list calendar views
* In-person and virtual events with RSVPs
* Events for teams, and for groups when a groups add-on is installed
* Personal player events with invitations and privacy settings (friend features need the Social Kit add-on)

= Notifications people actually notice =

* A notification bell with an unread count, plus a full notifications page
* Accept or decline a team challenge straight from the notification
* On-screen popups: you choose where they appear and how long they stay
* Make it sound like your community: reword notifications and popups from the settings screens

= Built for the block editor =

Clanbite comes with more than 40 blocks for players, teams, matches, events and navigation. Build your pages with them, and edit the included templates in the Site Editor on block themes. Classic theme templates are included too.

There's also a **Visibility container** block that shows content to guests, signed-in members or particular roles.

= Switch on only what you need =

Clanbite is modular. Players is the foundation; enable Teams, Matches, Events and Notifications from **Clanbite → Extensions** as your community needs them.

= Official add-ons =

Want to go further? These are separate plugins, available from [clanbite.com](https://clanbite.com/shop/). They are not part of this download, and Clanbite does not install or update them: their installation and updates are handled by the free Clanbite Add-on Manager, which you can also [get from clanbite.com](https://clanbite.com/add-on-manager/). Some add-ons may need a paid licence.

* **Social Kit:** activity feeds, friends, groups, reactions, @mentions, polls and a Discord webhook relay
* **Forums:** discussions, topic following and moderation tools
* **Points:** your own XP or reputation points with earning rules and limits
* **Ranks:** rank ladders, team ranks and progress rings on avatars
* **Shop:** merchandise, downloads, licence keys and subscriptions

= For developers =

I want you to be able to make Clanbite your own. You can build extensions that sit alongside the official ones, hook into documented actions and filters, use the REST API, register your own blocks, profile tabs and notification actions, and style everything to match your theme.

* [Developer documentation](https://developer.clanbite.com)
* [Developer guide (README)](https://github.com/Kernow-dev/Clanbite/blob/main/README.md)
* [Hook reference](https://github.com/Kernow-dev/Clanbite/blob/main/HOOKS.md)
* [Design system](https://github.com/Kernow-dev/Clanbite/blob/main/DESIGN-SYSTEM.md)
* [Source code on GitHub](https://github.com/Kernow-dev/Clanbite)

== External services ==

Clanbite connects to the following outside services.

= Gravatar =

Notifications show an avatar for the person involved. If they have no Clanbite avatar, WordPress may supply a Gravatar image URL, which the viewer's browser then loads. Gravatar is run by Automattic: [terms](https://wordpress.com/tos/) and [privacy policy](https://automattic.com/privacy/).

= Other Clanbite websites (off until you turn it on) =

Clanbite only connects to other websites for team challenges and matches after a site administrator turns on **Connect with other Clanbite sites** under Clanbite → Settings → General. It is off by default. While it is off, your site makes none of the requests below and its endpoints for other sites refuse to answer.

When it is on:

* **Looking up a challenger's team:** when a challenger chooses "Look up team", or sends a challenge with their site's address, your server requests that address to confirm it runs Clanbite and to read the team's public details. The form explains this beside the field. WordPress identifies your site in the request, including your site address and WordPress version, and the other site sees your server's IP address.
* **Accepting a challenge from another Clanbite site:** your server sends that site the match and challenge reference numbers, their team's number on their site, your team's name, page address and logo address, the match time and visibility, your site address, and a signature. It copies the match onto their site.
* **Requests from other sites:** they can ask your site whether it runs Clanbite (including the version), for a published team's public details and for your public signing key. They can send a signed match; your server then requests the sender's public signing key from their site to check it.
* **Images:** a challenger's or opponent's logo may be loaded from their site, so a visitor's browser contacts it.

These sites are run by their own owners, under their own terms and privacy policies. Nothing is sent to the author of Clanbite.

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

**Requirements:** WordPress 6.9 or later and PHP 8.4 or later.

1. In your WordPress dashboard, go to **Plugins → Add New**, search for **Clanbite**, then click **Install Now** and **Activate**. You can also upload the ZIP file.
2. Open **Clanbite** in the admin menu and review the **General** settings.
3. Go to **Clanbite → Extensions** and switch on what you need. Matches also needs Teams.
4. If your player or team pages show "not found", go to **Settings → Permalinks** and click **Save Changes**.
5. Add Clanbite blocks to your pages and navigation. The User Navigation and Notification Bell blocks are a good start.

== Frequently Asked Questions ==

= Is Clanbite only for gaming? =

It's designed with gaming clans and esports in mind, but the player, team, match and event tools can suit sports clubs and other team-based communities too.

= How do challenges between two websites work? =

Both site owners first turn on **Connect with other Clanbite sites** (off by default). Your challenger then enters their Clanbite site address on your team's challenge form. If you accept, the match is created on your site and a copy is sent to theirs. See "Challenge other communities" in the description for the requirements.

= Does my opponent need Clanbite? =

No. They can still send a challenge by entering their team's details. They need Clanbite if they want the accepted match to appear on their own site automatically.

= Does it work with my theme? =

Clanbite supports block themes, including templates you can edit in the Site Editor, and includes templates for classic themes. Its buttons follow your theme's button styles.

= Do I have to use every feature? =

No. Players is required, and you can switch the other bundled extensions on or off under **Clanbite → Extensions**.

= Can I change the wording of notifications? =

Yes. Look for the **Messages** tab within each extension's settings.

= Can I build my own extensions? =

Yes. Start with the [developer documentation](https://developer.clanbite.com) and the [hook reference](https://github.com/Kernow-dev/Clanbite/blob/main/HOOKS.md).

= Where do I get help? =

For help setting up your site, use the [support forum](https://wordpress.org/support/plugin/clanbite/). For bugs and feature ideas, open an issue on [GitHub](https://github.com/Kernow-dev/Clanbite/issues). Ideas are very welcome: tell me what your community needs.

== Changelog ==

= 1.7.0 =
* Important, if you use Clanbite add-ons: install the free Clanbite Add-on Manager from https://clanbite.com/add-on-manager/ **before** updating, or your add-ons will stop receiving updates until you do. Your licence activation is kept.
* Changed: installing, licensing and updating add-ons has been removed from Clanbite, as WordPress.org does not allow a directory plugin to install or update plugins from another server. The Licence tab now comes from the Add-on Manager, and Clanbite no longer contacts the add-on server.
* For developers: the licence and updater code has moved to the Add-on Manager. Its `clanbite_*` functions, hooks and `clanbite/v1/admin/license` routes are only available while the manager is active, and the `Kernowdev\Clanbite\Updates` classes no longer exist. The add-on compatibility classes are now in `Kernowdev\Clanbite\Compat`.
* Changed: connections with other Clanbite sites (two-way team challenges and match listings) are now off until a site administrator turns on "Connect with other Clanbite sites" under Clanbite → Settings → General. If you used them, turn it on after updating.
* Changed: the challenge form only looks up another site when the challenger chooses "Look up team" or sends the challenge, and explains what that does.
* Security: events, including players' "Only me" events, could be read through the WordPress REST API (`/wp/v2/clanbite_event`) without signing in. That route is now limited to users who can edit other people's content.
* Security: match visibility now also applies to WordPress REST and search, match pages, archives, feeds, link previews and sitemaps. Restricted matches could be read through these.
* Security: events in request-to-join and invite-only groups are only for the group, even if marked Public, including their RSVPs and attendee lists; banned players cannot see a group's events (needs the matching Clanbite Social Kit update).
* Security: event list totals and searches no longer count events the viewer cannot see.
* Fixed: turning scheduled events off for a team or group did not stay off.
* Fixed: the tabs on the front-end team management screen did not work, because their script was missing from the plugin package.
* New: team admins can invite players at any time from the Roster tab of the team management screen: search by name or username, send an invitation, and see who has not replied yet. Needs the Notifications extension.
* Changed: an invitation can no longer be accepted once its sender may no longer invite or the team has turned invitations off. On one-team sites it waits until the player has left their other team.
* Fixed: after declining an invitation, a player could not be invited again by the same person.
* Fixed: a player who turned team invites off could still be found and invited.
* Fixed: a team that turned challenges off kept accepting them.
* Security: team admins and editors can no longer change a team's author, status, address, password, parent or publish date, through the REST API, wp-admin or any other route. Please update.
* Security: stored team roster roles can no longer be edited through WordPress' custom field permissions.
* Security: the team manage screen can only change or remove people already on the roster; new members join by accepting an invitation. Teams site admins can still add people, and developers can use the `clanbite_team_manage_can_add_member_directly` filter.
* Security: turning off front-end editing for a team now also applies to team owners whose WordPress role (such as Author) previously kept access through the REST API and wp-admin.
* Changed: Clanbite now requires PHP 8.4 or later.
* Fixed: the Player settings block in the block inserter had no icon like the other blocks.
* New: shared skeleton loaders. `clanbite_render_skeleton()` (and `clanbite.skeletons.render()` in the browser) draws loading placeholders for avatars, lines, text, blocks, list items and cards with one look, themed through `--clanbite-skeleton-*` CSS properties and extended with `clanbite_register_skeleton()`. The avatar skeleton matches the real avatar's size and shape. A `clanbite-skeleton-surface` class gives images the same shimmer while they load. The event calendar's month, week, day and list loading placeholders now use them too, so the whole plugin family shares one loading look.
* Fixed: the “Developers” button in the Clanbite admin header opened the main website instead of the developer documentation (developer.clanbite.com).
* Changed: the whole notification in the bell dropdown and on the notifications page is now clickable, not just its title. Keyboard focus, middle-click and “mark as read” work as before.
* Changed: built with the latest WordPress build tools.

= 1.6.10 =
* Changed: every script and stylesheet the plugin serves is now built with `@wordpress/scripts` and minified for production (smaller downloads, same behaviour); no setting or API changes.

= 1.6.9 =
* Fixed: a notification bell on a page left open for a long time (or after the nonce expired) kept failing with 403 errors until the page was reloaded. It now asks WordPress for a fresh nonce and carries on, and stops asking when the visitor is signed out.

= 1.6.8 =
* Fixed: the 1.6.7 package could not load (a development file its autoloader listed was missing), which caused a critical error on update. This release is built with production dependencies only. No other changes.

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

= 1.7.0 =
Using Clanbite add-ons? Install the free Add-on Manager from clanbite.com first. Two-way challenges are now off until you turn them on in General settings.

= 1.1.0 =
WordPress 7.0 compatible. Includes improved requirements checking and minor bug fixes. Safe to upgrade.

= 1.0.0 =
First public release on WordPress.org. If you tested pre-release builds, visit Permalinks and save once after upgrading.
