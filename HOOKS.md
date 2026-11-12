# clanbite hook reference

> **Generated file: do not edit.** Source of truth is the docblock above each `apply_filters()` / `do_action()` call
> (and the JSDoc above each `wp.hooks` call). Edit that, then run `php scripts/generate-hooks-doc.php`.
> `php scripts/generate-hooks-doc.php --check` fails when this file is stale.

**333 PHP hooks and 8 JavaScript hooks**.

Dynamic hook names use placeholders: `{type}`, `{context}`, `{slug}`… stand for the value that is substituted at run time.

## Contents

- [Admin](#admin) (9)
- [Avatars](#avatars) (42)
- [Events](#events) (19)
- [Extensions](#extensions) (27)
- [General](#general) (20)
- [Matches](#matches) (18)
- [Notifications](#notifications) (21)
- [Players](#players) (82)
- [Profile sub-pages and groups](#profile-sub-pages-and-groups) (12)
- [Teams](#teams) (83)
- [JavaScript hooks](#javascript-hooks) (8)

## Admin

#### `clanbite_admin_icon_packs`

*Filter* · `inc/admin/class-admin-rest.php`

Filters the icon packs offered by the admin icon picker.

Packs from the Points and Ranks companions are merged in afterwards and deduplicated by pack id.

| Parameter | Type | Description |
| --- | --- | --- |
| `$packs` | `array<int, array<string, mixed>>` | Icon pack definitions, each with an `id`. Return a list, not an associative array. |

#### `clanbite_admin_icon_picker_i18n`

*Filter* · `inc/admin/class-admin-rest.php`

Filters the translated UI strings passed to the admin icon picker.

| Parameter | Type | Description |
| --- | --- | --- |
| `$icon_picker_i18n` | `array<string, string>` | Label map (for example `clear`). |

#### `clanbite_admin_infer_icon_pack_scope`

*Filter* · `inc/admin/class-admin-rest.php`

Filter: `clanbite_admin_infer_icon_pack_scope` — default `all` when a pack row has no `scope`.

| Parameter | Type | Description |
| --- | --- | --- |
| `$scope` | `string` | Default scope. |
| `$id` | `string` | Sanitized pack id. |

#### `clanbite_admin_rest_bootstrap`

*Filter* · `inc/admin/class-admin-rest.php`

Filter: `clanbite_admin_rest_bootstrap` — full payload for `GET clanbite/v1/admin/bootstrap`.

| Parameter | Type | Description |
| --- | --- | --- |
| `$data` | `array<string, mixed>` | Bootstrap body (`tabs`, `values`, `extensions`, …). |
| `$loader` | `Loader` | Extension loader instance. |

#### `clanbite_admin_rest_manage_capability`

*Filter* · `inc/admin/class-admin-rest.php`

Filter: `clanbite_admin_rest_manage_capability` — primitive capability required for `clanbite/v1/admin/*` routes.

| Parameter | Type | Description |
| --- | --- | --- |
| `$capability` | `string` | Default `manage_options`. |

#### `clanbite_admin_rest_settings_registry`

*Filter* · `inc/admin/class-admin-settings.php`

Filter: `clanbite_admin_rest_settings_registry`.

| Parameter | Type | Description |
| --- | --- | --- |
| `$registry` | `array<string, Abstract_Settings>` | Option key => handler. |
| `$loader` | `Loader` | Extension loader. |

#### `clanbite_admin_rest_tabs`

*Filter* · `inc/admin/class-admin-rest.php`

Filter: `clanbite_admin_rest_tabs`.

| Parameter | Type | Description |
| --- | --- | --- |
| `$tabs` | `array<int, array<string, mixed>>` | Tab descriptors (`type`: `general`\|`extensions`\|`extension`\|`integration`). |
| `$loader` | `Loader` | Extension loader. |

#### `clanbite_extension_capabilities`

*Filter* · `inc/admin/class-admin-rest.php`

Filter: `clanbite_extension_capabilities` — optional capability hints for an extension row in admin bootstrap JSON.

Use associative keys meaningful to your integration (e.g. `manage`, `configure`).

| Parameter | Type | Description |
| --- | --- | --- |
| `$capabilities` | `array<string, mixed>` | Default empty; merged keys surface on the extension row as `capabilities`. |
| `$slug` | `string` | Extension slug. |
| `$extension` | `Skeleton` | Extension instance. |

#### `clanbite_validate_installed_extensions`

*Filter* · `inc/admin/class-admin-rest.php`

Filters the extension slugs that will be saved after the admin Extensions screen validates the request.

| Parameter | Type | Description |
| --- | --- | --- |
| `$new` | `array<int,    string>` | Validated slugs that will be stored. |
| `$requested` | `array<int,    string>` | Slugs the admin requested. |
| `$available` | `array<string, \Kernowdev\Clanbite\Extensions\Skeleton>` | Registered extensions keyed by slug. |

## Avatars

#### `clanbite_after_avatar_inner`

*Action* · `inc/avatar-helpers.php`

Action: After avatar inner content.

| Parameter | Type | Description |
| --- | --- | --- |
| `$args` | `array` | Avatar arguments. |

#### `clanbite_after_{type}_avatar_inner`

*Action* · `inc/avatar-helpers.php`

Fires after the inner markup of a type-specific avatar.

Type-specific variant of `clanbite_after_avatar_inner`.

| Parameter | Type | Description |
| --- | --- | --- |
| `$args` | `array<string, mixed>` | Avatar arguments (`type`, `id`, `size`, `context`, `shape`, …); see `clanbite_render_avatar()`. |

#### `clanbite_avatar_alt_text`

*Filter* · `inc/avatar-helpers.php`

Filter avatar alt text.

| Parameter | Type | Description |
| --- | --- | --- |
| `$alt` | `string` | Alt text. |
| `$type` | `string` | Entity type. |
| `$id` | `int` | Entity ID. |

#### `clanbite_avatar_badge`

*Filter* · `inc/avatar-helpers.php`

Filters the XP ring / rank badge data of an avatar.

| Parameter | Type | Description |
| --- | --- | --- |
| `$data` | `array<string, mixed>` | Keys `show_progress`, `progress` (0-100), `show_badge`, `level`, `level_text`, `label`, `icon_url`. |
| `$type` | `string` | Avatar type (`player`, `team`, `group`). |
| `$id` | `int` | Entity ID. |

#### `clanbite_avatar_classes`

*Filter* · `inc/avatar-helpers.php`

Filter avatar container classes.

| Parameter | Type | Description |
| --- | --- | --- |
| `$classes` | `array` | CSS classes. |
| `$args` | `array` | Avatar arguments (includes 'context' key). |

#### `clanbite_avatar_classes_{context}`

*Filter* · `inc/avatar-helpers.php`

Filters the avatar CSS classes for one context (for example `forum`, `social_feed`).

Context-specific variant of `clanbite_avatar_classes`.

| Parameter | Type | Description |
| --- | --- | --- |
| `$classes` | `string[]` | CSS classes. |
| `$args` | `array<string, mixed>` | Avatar arguments (`type`, `id`, `size`, `context`, `shape`, …); see `clanbite_render_avatar()`. |

#### `clanbite_avatar_data`

*Filter* · `inc/avatar-helpers.php`

Filters the resolved avatar data (shape, ring and badge visibility) for every avatar.

| Parameter | Type | Description |
| --- | --- | --- |
| `$data` | `array<string, mixed>` | Resolved data: `shape`, `show_ring`, `show_badge`, and in static mode `badge`. |
| `$args` | `array<string, mixed>` | Render arguments (`type`, `id`, `size`, `context`, `mode`, …). |

#### `clanbite_avatar_data_{context}`

*Filter* · `inc/avatar-helpers.php`

Filters the resolved avatar data for one context (for example `forum`, `social_feed`, `user_nav`).

| Parameter | Type | Description |
| --- | --- | --- |
| `$data` | `array<string, mixed>` | Resolved data: `shape`, `show_ring`, `show_badge`, and in static mode `badge`. |
| `$args` | `array<string, mixed>` | Render arguments (`type`, `id`, `size`, `context`, `mode`, …). |

#### `clanbite_avatar_html`

*Filter* · `inc/avatar-helpers.php`

Filter the complete avatar HTML output.

This is the main filter point for customizing avatar rendering.
Third-party developers should use this filter to customize ALL avatars
across the site in one place.

| Parameter | Type | Description |
| --- | --- | --- |
| `$output` | `string` | Avatar HTML markup. |
| `$args` | `array` | Avatar arguments (includes 'context' key for targeting specific locations). |

#### `clanbite_avatar_html_{context}`

*Filter* · `inc/avatar-helpers.php`

Filters the avatar HTML for one context.

Context-specific variant of `clanbite_avatar_html`.

| Parameter | Type | Description |
| --- | --- | --- |
| `$output` | `string` | Avatar markup. |
| `$args` | `array<string, mixed>` | Avatar arguments (`type`, `id`, `size`, `context`, `shape`, …); see `clanbite_render_avatar()`. |

#### `clanbite_avatar_img_attributes`

*Filter* · `inc/avatar-helpers.php`

Filter avatar image attributes.

| Parameter | Type | Description |
| --- | --- | --- |
| `$img_attrs` | `array` | Image attributes. |
| `$args` | `array` | Avatar arguments (includes 'context' key). |

#### `clanbite_avatar_img_attributes_{context}`

*Filter* · `inc/avatar-helpers.php`

Filters the avatar `<img>` attributes for one context.

Context-specific variant of `clanbite_avatar_img_attributes`.

| Parameter | Type | Description |
| --- | --- | --- |
| `$img_attrs` | `array<string, string\|bool>` | Attribute map. |
| `$args` | `array<string, mixed>` | Avatar arguments (`type`, `id`, `size`, `context`, `shape`, …); see `clanbite_render_avatar()`. |

#### `clanbite_avatar_interactivity_classes`

*Filter* · `inc/avatar-helpers.php`

Filter avatar classes for Interactivity API contexts.

| Parameter | Type | Description |
| --- | --- | --- |
| `$classes` | `array` | CSS classes. |
| `$args` | `array` | Avatar arguments. |

#### `clanbite_avatar_interactivity_classes_{context}`

*Filter* · `inc/avatar-helpers.php`

Filters the Interactivity-API avatar CSS classes for one context.

Context-specific variant of `clanbite_avatar_interactivity_classes`.

| Parameter | Type | Description |
| --- | --- | --- |
| `$classes` | `string[]` | CSS classes. |
| `$args` | `array<string, mixed>` | Avatar arguments (`type`, `id`, `size`, `context`, …); see `clanbite_render_avatar_for_interactivity()`. |

#### `clanbite_avatar_interactivity_content`

*Action* · `inc/avatar-helpers.php`

Action: Add custom content to Interactivity API avatars.

| Parameter | Type | Description |
| --- | --- | --- |
| `$args` | `array` | Avatar arguments. |

#### `clanbite_avatar_interactivity_content_{context}`

*Action* · `inc/avatar-helpers.php`

Fires to add content inside an Interactivity-API avatar for one context.

Context-specific variant of `clanbite_avatar_interactivity_content`.

| Parameter | Type | Description |
| --- | --- | --- |
| `$args` | `array<string, mixed>` | Avatar arguments (`type`, `id`, `size`, `context`, …); see `clanbite_render_avatar_for_interactivity()`. |

#### `clanbite_avatar_interactivity_html`

*Filter* · `inc/avatar-helpers.php`

Filter the complete Interactivity API avatar HTML.

| Parameter | Type | Description |
| --- | --- | --- |
| `$output` | `string` | Avatar HTML markup. |
| `$args` | `array` | Avatar arguments. |

#### `clanbite_avatar_interactivity_html_{context}`

*Filter* · `inc/avatar-helpers.php`

Filters the Interactivity-API avatar HTML for one context.

Context-specific variant of `clanbite_avatar_interactivity_html`.

| Parameter | Type | Description |
| --- | --- | --- |
| `$output` | `string` | Avatar markup. |
| `$args` | `array<string, mixed>` | Avatar arguments (`type`, `id`, `size`, `context`, …); see `clanbite_render_avatar_for_interactivity()`. |

#### `clanbite_avatar_interactivity_img_attributes`

*Filter* · `inc/avatar-helpers.php`

Filter image attributes for Interactivity API contexts.

| Parameter | Type | Description |
| --- | --- | --- |
| `$img_attrs` | `array` | Image attributes. |
| `$args` | `array` | Avatar arguments. |

#### `clanbite_avatar_interactivity_img_attributes_{context}`

*Filter* · `inc/avatar-helpers.php`

Filters the Interactivity-API avatar `<img>` attributes for one context.

Context-specific variant of `clanbite_avatar_interactivity_img_attributes`.

| Parameter | Type | Description |
| --- | --- | --- |
| `$img_attrs` | `array<string, string\|bool>` | Attribute map. |
| `$args` | `array<string, mixed>` | Avatar arguments (`type`, `id`, `size`, `context`, …); see `clanbite_render_avatar_for_interactivity()`. |

#### `clanbite_avatar_overlays`

*Action* · `inc/avatar-helpers.php`

Action: After avatar overlays.

Use this to add custom overlays (badges, status indicators, etc.).

| Parameter | Type | Description |
| --- | --- | --- |
| `$args` | `array` | Avatar arguments (includes 'context' key). |

#### `clanbite_avatar_overlays_{context}`

*Action* · `inc/avatar-helpers.php`

Fires when overlays (badges, status) are rendered for an avatar in one context.

Context-specific variant of `clanbite_avatar_overlays`.

| Parameter | Type | Description |
| --- | --- | --- |
| `$args` | `array<string, mixed>` | Avatar arguments (`type`, `id`, `size`, `context`, `shape`, …); see `clanbite_render_avatar()`. |

#### `clanbite_avatar_profile_url`

*Filter* · `inc/avatar-helpers.php`

Filter profile URL.

| Parameter | Type | Description |
| --- | --- | --- |
| `$url` | `string` | Profile URL. |
| `$type` | `string` | Entity type. |
| `$id` | `int` | Entity ID. |

#### `clanbite_avatar_progress_demo_value`

*Filter* · `inc/avatar-helpers.php`

Filters a fixed progress value to draw when no real rank progress is available.

By default no XP ring is drawn unless a Ranks ladder has "Show progress on avatar" enabled. Return an integer from 0
to 100 to force a ring, for example while styling it.

| Parameter | Type | Description |
| --- | --- | --- |
| `$demo` | `int\|null` | Progress percentage, or null to render nothing (default). |
| `$user_id` | `int` | User ID the avatar belongs to. |

#### `clanbite_avatar_url`

*Filter* · `inc/avatar-helpers.php`

Filter avatar URL.

| Parameter | Type | Description |
| --- | --- | --- |
| `$url` | `string` | Avatar URL. |
| `$type` | `string` | Entity type. |
| `$id` | `int` | Entity ID. |
| `$size` | `string` | Size. |
| `$context` | `string` | Context. |

#### `clanbite_before_avatar_inner`

*Action* · `inc/avatar-helpers.php`

Action: Before avatar inner content.

| Parameter | Type | Description |
| --- | --- | --- |
| `$args` | `array` | Avatar arguments. |

#### `clanbite_before_{type}_avatar_inner`

*Action* · `inc/avatar-helpers.php`

Fires before the inner markup of a type-specific avatar.

Type-specific variant of `clanbite_before_avatar_inner`.

| Parameter | Type | Description |
| --- | --- | --- |
| `$args` | `array<string, mixed>` | Avatar arguments (`type`, `id`, `size`, `context`, `shape`, …); see `clanbite_render_avatar()`. |

#### `clanbite_country_display_html`

*Filter* · `inc/functions-country-flags.php`

Filter full inner HTML for country blocks (flag + text layout).

| Parameter | Type | Description |
| --- | --- | --- |
| `$inner` | `string` | Built HTML. |
| `$attributes` | `array<string, mixed>` | Block attributes. |
| `$code` | `string` | Raw storage code. |
| `$label` | `string` | Resolved display label. |
| `$entity` | `string` | `team` or `player`. |
| `$block_name` | `string` | Block name. |
| `$block` | `\WP_Block\|null` | Block instance. |

#### `clanbite_country_flag_asset_slug`

*Filter* · `inc/functions-country-flags.php`

Filters the file slug used to find the bundled SVG flag for a country.

| Parameter | Type | Description |
| --- | --- | --- |
| `$slug` | `string` | Lowercased file slug. |
| `$normalized_code` | `string` | Normalized country code. |
| `$context` | `array<string, mixed>` | Rendering context. |

#### `clanbite_country_flag_file_path`

*Filter* · `inc/functions-country-flags.php`

Filter resolved flag SVG path on disk.

| Parameter | Type | Description |
| --- | --- | --- |
| `$path` | `string` | Absolute path or empty. |
| `$normalized_code` | `string` | Normalized country code. |
| `$context` | `array` | Arbitrary context: `entity`, `block_name`, `block`, etc. |

#### `clanbite_country_flag_html`

*Filter* · `inc/functions-country-flags.php`

Replace default flag markup entirely. Return non-empty HTML to skip the default &lt;img&gt;.

| Parameter | Type | Description |
| --- | --- | --- |
| `$html` | `string` | Empty string by default. |
| `$context` | `array` | Keys: `code`, `alt`, plus passthrough from the caller. |

#### `clanbite_country_flag_img_html`

*Filter* · `inc/functions-country-flags.php`

Filter the default flag &lt;img&gt; HTML after it is built.

| Parameter | Type | Description |
| --- | --- | --- |
| `$img` | `string` | Full img element string. |
| `$context` | `array` | Same as `clanbite_country_flag_html`. |

#### `clanbite_country_flag_slug_aliases`

*Filter* · `inc/functions-country-flags.php`

Map ISO codes to an existing Flagpack filename when alpha-2 SVG is absent.

| Parameter | Type | Description |
| --- | --- | --- |
| `$aliases` | `array<string, string>` | Uppercase code => lowercase slug (no `.svg`). |
| `$normalized_code` | `string` | Requested code. |
| `$context` | `array<string, mixed>` | Optional context. |

#### `clanbite_country_flag_url`

*Filter* · `inc/functions-country-flags.php`

Filter flag asset URL (CDN, different bundle, or empty to hide default file-based URL).

| Parameter | Type | Description |
| --- | --- | --- |
| `$url` | `string` | Default URL from bundled assets. |
| `$normalized_code` | `string` | Normalized country code. |
| `$url_context` | `array` | Includes `slug` plus `entity`, `block_name`, `block`, `attributes`. |

#### `clanbite_default_avatar_url`

*Filter* · `inc/avatar-helpers.php`

Filter default avatar URL.

| Parameter | Type | Description |
| --- | --- | --- |
| `$url` | `string` | Default URL. |
| `$type` | `string` | Entity type. |

#### `clanbite_groups_get_avatar_alt`

*Filter* · `inc/avatar-helpers.php`

Resolves the alt text for a group's avatar.

Core ships no group implementation; the plugin that owns `cp_group` should return the text here.

| Parameter | Type | Description |
| --- | --- | --- |
| `$alt` | `string` | Alt text (empty by default). |
| `$id` | `int` | Group ID. |

#### `clanbite_groups_get_display_avatar`

*Filter* · `inc/avatar-helpers.php`

Resolves a group's avatar URL.

Core ships no group implementation; the plugin that owns `cp_group` should return the URL here.

| Parameter | Type | Description |
| --- | --- | --- |
| `$url` | `string` | Avatar URL (empty by default). |
| `$id` | `int` | Group ID. |
| `$size` | `string\|int[]` | Image size slug or dimensions. |
| `$context` | `string` | Rendering context. |

#### `clanbite_groups_get_profile_url`

*Filter* · `inc/avatar-helpers.php`

Resolves a group's profile URL for avatar links.

Core ships no group implementation; the plugin that owns `cp_group` should return the URL here.

| Parameter | Type | Description |
| --- | --- | --- |
| `$url` | `string` | Profile URL (empty by default). |
| `$id` | `int` | Group ID. |

#### `clanbite_{type}_avatar_classes`

*Filter* · `inc/avatar-helpers.php`

Filters the avatar CSS classes for one entity type (`player`, `team`, `group`).

Type-specific variant of `clanbite_avatar_classes`.

| Parameter | Type | Description |
| --- | --- | --- |
| `$classes` | `string[]` | CSS classes. |
| `$args` | `array<string, mixed>` | Avatar arguments (`type`, `id`, `size`, `context`, `shape`, …); see `clanbite_render_avatar()`. |

#### `clanbite_{type}_avatar_data`

*Filter* · `inc/avatar-helpers.php`

Filters the resolved avatar data for one entity type (`player`, `team`, `group`).

| Parameter | Type | Description |
| --- | --- | --- |
| `$data` | `array<string, mixed>` | Resolved data: `shape`, `show_ring`, `show_badge`, and in static mode `badge`. |
| `$args` | `array<string, mixed>` | Render arguments (`type`, `id`, `size`, `context`, `mode`, …). |

#### `clanbite_{type}_avatar_html`

*Filter* · `inc/avatar-helpers.php`

Filters the avatar HTML for one entity type (`player`, `team`, `group`).

Type-specific variant of `clanbite_avatar_html`.

| Parameter | Type | Description |
| --- | --- | --- |
| `$output` | `string` | Avatar markup. |
| `$args` | `array<string, mixed>` | Avatar arguments (`type`, `id`, `size`, `context`, `shape`, …); see `clanbite_render_avatar()`. |

#### `clanbite_{type}_avatar_overlays`

*Action* · `inc/avatar-helpers.php`

Fires when overlays (badges, status) are rendered for a type-specific avatar.

Type-specific variant of `clanbite_avatar_overlays`.

| Parameter | Type | Description |
| --- | --- | --- |
| `$args` | `array<string, mixed>` | Avatar arguments (`type`, `id`, `size`, `context`, `shape`, …); see `clanbite_render_avatar()`. |

## Events

#### `clanbite_cp_event_user_can_manage`

*Filter* · `inc/events/class-event-permissions.php`

Filter whether a user can manage a `cp_event`.

| Parameter | Type | Description |
| --- | --- | --- |
| `$can` | `bool` | Whether the user can manage. |
| `$post_id` | `int` | Event post ID. |
| `$user_id` | `int` | User ID. |

#### `clanbite_cp_event_viewer_can_see`

*Filter* · `inc/events/class-event-permissions.php`

Filter whether a viewer can see a published `cp_event`.

| Parameter | Type | Description |
| --- | --- | --- |
| `$can` | `bool` | Whether the viewer can see the event. |
| `$post` | `\WP_Post` | Event post. |
| `$viewer_id` | `int` | Viewer user ID. |

#### `clanbite_event_attendees_visibility`

*Filter* · `inc/events/class-events.php`

Filter attendee visibility for an event.

| Parameter | Type | Description |
| --- | --- | --- |
| `$visibility` | `string` | `public` or `hidden`. |
| `$event_type` | `string` | Event type slug. |
| `$event_id` | `int` | Event ID. |

#### `clanbite_event_calendar_create_url`

*Filter* · `src/blocks/events/event-calendar/render.php`

Filter calendar “add event” link (team/group).

| Parameter | Type | Description |
| --- | --- | --- |
| `$create_url` | `string` | URL or empty. |
| `$scope_api` | `string` | `team`, `group`, or `player`. |
| `$team_id` | `int` | Team ID when applicable. |
| `$group_id` | `int` | Group ID when applicable. |

#### `clanbite_event_can_view_attendees`

*Filter* · `inc/events/class-event-rsvp-rest-controller.php`

Filter whether attendee list can be viewed.

Use this to allow team members/admins to see attendee lists even when
the event is set to `hidden`.

| Parameter | Type | Description |
| --- | --- | --- |
| `$can_view_attendees` | `bool` | Whether attendee list can be viewed. |
| `$event_type` | `string` | Event type slug. |
| `$event_id` | `int` | Event ID. |
| `$viewer_id` | `int` | Viewer user ID (0 for anon). |

#### `clanbite_event_member_outreach_completed`

*Action* · `inc/events/class-event-member-outreach.php`

Fires after event member outreach runs for a `cp_event`.

| Parameter | Type | Description |
| --- | --- | --- |
| `$event_id` | `int` | Event post ID. |
| `$mode` | `string` | Outreach mode. |
| `$result` | `array<string, mixed>` | `notified`, `rsvp_set`, `skipped` counts. |

#### `clanbite_event_member_outreach_user_ids`

*Filter* · `inc/events/class-event-member-outreach.php`

Filter user IDs included in event member outreach (notifications / tentative RSVPs).

| Parameter | Type | Description |
| --- | --- | --- |
| `$user_ids` | `list<int>` | User IDs. |
| `$event_id` | `int` | `cp_event` post ID. |
| `$scope` | `string` | `cp_event_scope` meta (`team` or `group`). |
| `$team_id` | `int` | Team post ID when scope is team. |
| `$group_id` | `int` | Group object ID when scope is group. |
| `$mode` | `string` | Outreach mode (`none`, `notify`, `rsvp_tentative`). |

#### `clanbite_event_rsvp_updated`

*Action* · `inc/events/class-event-rsvp-data-access.php`

Fires when a user's RSVP changes for an event.

Third-party extensions can hook this (e.g. activity feeds).

| Parameter | Type | Description |
| --- | --- | --- |
| `$event_type` | `string` | Event type slug. |
| `$event_id` | `int` | Event ID. |
| `$user_id` | `int` | User ID. |
| `$new_status` | `string` | New RSVP status. |
| `$old_status` | `string\|null` | Old RSVP status (null when first set). |

#### `clanbite_event_viewer_can_see`

*Filter* · `inc/events/class-events.php`

Filter whether the viewer can see an event.

Use this to enforce visibility rules for custom event types (e.g. Groups).

| Parameter | Type | Description |
| --- | --- | --- |
| `$can` | `bool` | Whether the viewer can see the event. |
| `$event_type` | `string` | Event type slug. |
| `$event_id` | `int` | Event ID. |
| `$viewer_id` | `int` | Viewer user ID (0 for anon). |

#### `clanbite_events_extension_active`

*Filter* · `inc/extensions/events/functions.php`

Filter whether the Events extension is considered active.

| Parameter | Type | Description |
| --- | --- | --- |
| `$active` | `bool` | True when `cp_events` is in the installed-extensions option. |

#### `clanbite_events_player_calendar_group_ids`

*Filter* · `inc/events/functions.php`

Filter group IDs included in a player’s merged event calendar REST query.

| Parameter | Type | Description |
| --- | --- | --- |
| `$group_ids` | `int[]` | Group post IDs (empty by default). |
| `$user_id` | `int` | Profile owner user ID. |

#### `clanbite_events_player_calendar_team_ids`

*Filter* · `inc/events/functions.php`

Filter team IDs included in a player’s merged event calendar REST query.

| Parameter | Type | Description |
| --- | --- | --- |
| `$team_ids` | `int[]` | Team post IDs. |
| `$user_id` | `int` | Profile owner user ID. |

#### `clanbite_events_rest_default_per_page_paginated`

*Filter* · `inc/events/functions.php`

Filter default page size for paginated event list queries (no calendar window).

| Parameter | Type | Description |
| --- | --- | --- |
| `$default` | `int` | Default per_page before clamping to the paginated max. |

#### `clanbite_events_rest_default_per_page_range`

*Filter* · `inc/events/functions.php`

Filter default page size for ranged event queries (calendar SSR + `fetchEvents`).

| Parameter | Type | Description |
| --- | --- | --- |
| `$default` | `int` | Default per_page before request clamping. |

#### `clanbite_events_rest_max_per_page_paginated`

*Filter* · `inc/events/functions.php`

Filter maximum page size for paginated event list queries.

| Parameter | Type | Description |
| --- | --- | --- |
| `$max` | `int` | Upper bound for `per_page` when no start/end window is set. |

#### `clanbite_events_rest_max_per_page_range`

*Filter* · `inc/events/functions.php`

Filter maximum page size for ranged event queries.

| Parameter | Type | Description |
| --- | --- | --- |
| `$max` | `int` | Upper bound for `per_page` when a start/end window is set. |

#### `clanbite_group_event_member_user_ids`

*Filter* · `inc/events/class-event-member-outreach.php`

Provide WordPress user IDs for members of a group-scoped event’s roster.

Core leaves this empty; a `cp_group` integration should filter and return IDs.

| Parameter | Type | Description |
| --- | --- | --- |
| `$user_ids` | `int[]` | Default empty. |
| `$group_id` | `int` | Group object ID. |

#### `clanbite_group_events_create_url`

*Filter* · `src/blocks/events/event-calendar/render.php`

Filter URL for “add event” from the group event calendar (extension-defined).

| Parameter | Type | Description |
| --- | --- | --- |
| `$url` | `string` | URL or empty. |
| `$group_id` | `int` | Group post ID. |

#### `clanbite_groups_feature_active`

*Filter* · `inc/events/functions.php`

Filter whether group-scoped events may appear in a player’s merged calendar.

Default: true when the `cp_group` post type is registered (group product active).

| Parameter | Type | Description |
| --- | --- | --- |
| `$active` | `bool` | Whether group events are included. |
| `$user_id` | `int` | Profile owner user ID. |

## Extensions

#### `clanbite_can_install_{slug}_extension`

*Filter* · `inc/extensions/class-extension-skeleton.php`

Allows more specific checks to run to determine if an extension can be installed or ran.

| Parameter | Type | Description |
| --- | --- | --- |
| `$can_install` | `bool` | Current install status (core version, then requires satisfied against `$installed_slugs`). |
| `$extension` | `Skeleton` | Extension object. |

#### `clanbite_core_bundled_extension_slugs`

*Filter* · `inc/extensions/class-extension-loader.php`

Filter slugs treated as shipped with the core Clanbite plugin (not separate first-party add-ons).

| Parameter | Type | Description |
| --- | --- | --- |
| `$slugs` | `list<string>` | Default: `cp_players`, `cp_notifications`, `cp_teams`, `cp_matches`, `cp_events`. |

#### `clanbite_extension_block_directories`

*Filter* · `inc/extensions/class-extension-skeleton.php`

Filter block directories for all extensions.

| Parameter | Type | Description |
| --- | --- | --- |
| `$block_directories` | `array<int, string>` | Block directories. |
| `$extension` | `Skeleton` | Extension object. |

#### `clanbite_extension_data_store`

*Filter* · `inc/extensions/class-extension-skeleton.php`

Filter the extension data store implementation.

| Parameter | Type | Description |
| --- | --- | --- |
| `$data_store` | `Extension_Data_Store` | Default WordPress-backed data store instance. |
| `$slug` | `string` | Extension slug. |
| `$extension` | `Skeleton` | Extension object. |

#### `clanbite_extension_installer_{slug}`

*Action* · `inc/extensions/class-extension-skeleton.php`

Fires when an extension installer runs.

| Parameter | Type | Description |
| --- | --- | --- |
| `$extension` | `Skeleton` | Extension object. |

#### `clanbite_extension_run_{slug}`

*Action* · `inc/extensions/class-extension-skeleton.php`

Fires when an extension runtime boot method executes.

| Parameter | Type | Description |
| --- | --- | --- |
| `$extension` | `Skeleton` | Extension object. |

#### `clanbite_extension_settings_register_submenu`

*Filter* · `inc/extensions/class-extension-admin-settings.php`

Filters whether an extension's settings are also registered as a classic submenu under Clanbite.

The unified React admin keeps this false.

| Parameter | Type | Description |
| --- | --- | --- |
| `$register` | `bool` | Whether to register the submenu. |
| `$settings` | `\Kernowdev\Clanbite\Extensions\Abstract_Settings` | Settings instance. |

#### `clanbite_extension_templates`

*Filter* · `inc/extensions/class-extension-skeleton.php`

Filter templates for all extensions.

| Parameter | Type | Description |
| --- | --- | --- |
| `$templates` | `array<string, array<string, string>>` | Template definitions. |
| `$extension` | `Skeleton` | Extension object. |

#### `clanbite_extension_uninstaller_{slug}`

*Action* · `inc/extensions/class-extension-skeleton.php`

Fires when an extension uninstaller runs.

| Parameter | Type | Description |
| --- | --- | --- |
| `$extension` | `Skeleton` | Extension object. |

#### `clanbite_extension_updater_{slug}`

*Action* · `inc/extensions/class-extension-skeleton.php`

Fires when an extension updater runs.

| Parameter | Type | Description |
| --- | --- | --- |
| `$extension` | `Skeleton` | Extension object. |

#### `clanbite_extension_{slug}_block_directories`

*Filter* · `inc/extensions/class-extension-skeleton.php`

Filter block directories for a specific extension.

| Parameter | Type | Description |
| --- | --- | --- |
| `$block_directories` | `array<int, string>` | Block directories. |
| `$extension` | `Skeleton` | Extension object. |

#### `clanbite_extension_{slug}_templates`

*Filter* · `inc/extensions/class-extension-skeleton.php`

Filter templates for a specific extension.

Template array shape:
- key: template slug (without namespace), e.g. "player-settings"
- value: [
    'title'       => 'Template Title',
    'path'        => '/absolute/path/to/template.php',
    'description' => 'Optional. Shown in the Site Editor.',
    'post_types'  => array( 'post' ), // Optional. WP 6.7+ plugin templates only.
  ]

| Parameter | Type | Description |
| --- | --- | --- |
| `$templates` | `array<string, array<string, string>>` | Template definitions. |
| `$extension` | `Skeleton` | Extension object. |

#### `clanbite_install_events_extension_by_default`

*Filter* · `inc/extensions/class-extension-loader.php`

Whether to auto-enable the Events extension the first time the loader runs on a site.

| Parameter | Type | Description |
| --- | --- | --- |
| `$enable` | `bool` | Default true. |

#### `clanbite_install_notifications_extension_by_default`

*Filter* · `inc/extensions/class-extension-loader.php`

Whether to auto-enable the Notifications extension the first time the loader runs on a site.

| Parameter | Type | Description |
| --- | --- | --- |
| `$enable` | `bool` | Default true. |

#### `clanbite_official_registered_extensions`

*Filter* · `inc/extensions/class-extension-loader.php`

Allows our extensions to self register when installed.

| Parameter | Type | Description |
| --- | --- | --- |
| `$extensions` | `array<string, Skeleton>` | Registered first-party extensions keyed by slug. |

#### `clanbite_registered_extensions`

*Filter* · `inc/extensions/class-extension-loader.php`

Allow developers to add their own extensions via this hook.

| Parameter | Type | Description |
| --- | --- | --- |
| `$extensions` | `array<string, Skeleton>` | Registered extension objects keyed by slug. |

#### `clanbite_required_extension_slugs`

*Filter* · `inc/extensions/class-extension-loader.php`

Filter extension slugs treated as required core extensions.

| Parameter | Type | Description |
| --- | --- | --- |
| `$slugs` | `list<string>` | Required slugs (e.g. `cp_players`). |

#### `{option_key}_clanbite_final_merged_option_values`

*Filter* · `inc/extensions/class-extension-admin-settings.php`

Filter the merged, field-sanitized option array before persistence.

Dynamic hook: `{option_key}_clanbite_final_merged_option_values`.

| Parameter | Type | Description |
| --- | --- | --- |
| `$output` | `array<string, mixed>` | Merged settings. |
| `$input` | `array<string, mixed>` | Raw input (same shape as passed to sanitize). |

#### `{option_key}_clanbite_persisted_option_default_map`

*Filter* · `inc/extensions/class-extension-admin-settings.php`

Filter extension settings defaults before registration.

Dynamic hook: `{option_key}_clanbite_persisted_option_default_map`.

| Parameter | Type | Description |
| --- | --- | --- |
| `$default_settings` | `array` | Default settings map. |
| `$settings` | `Abstract_Settings` | Settings class instance. |

#### `{option_key}_clanbite_settings_field_definition_single`

*Filter* · `inc/extensions/class-extension-admin-settings.php`

Filter a single field config before registration.

Dynamic hook: `{option_key}_clanbite_settings_field_definition_single`.

| Parameter | Type | Description |
| --- | --- | --- |
| `$field` | `array` | Field config. |
| `$field_id` | `string` | Field id. |
| `$section_id` | `string` | Section id. |
| `$settings` | `Abstract_Settings` | Settings class instance. |

#### `{option_key}_clanbite_settings_field_markup_delegate`

*Filter* · `inc/extensions/class-extension-admin-settings.php`

Allow full field rendering override.

Return true to signal rendering handled by a custom callback.

Dynamic hook: `{option_key}_clanbite_settings_field_markup_delegate`.

| Parameter | Type | Description |
| --- | --- | --- |
| `$handled` | `bool` | Whether rendering has already been handled. |
| `$id` | `string` | Field id. |
| `$field` | `array` | Field config. |
| `$value` | `mixed` | Current field value. |
| `$settings` | `Abstract_Settings` | Settings class instance. |

#### `{option_key}_clanbite_settings_form_payload_prefilter`

*Filter* · `inc/extensions/class-extension-admin-settings.php`

Filter raw settings payload before field-level sanitization.

Dynamic hook: `{option_key}_clanbite_settings_form_payload_prefilter`.

| Parameter | Type | Description |
| --- | --- | --- |
| `$input` | `array` | Raw input. |
| `$fields` | `array` | Flat field config map. |
| `$settings` | `Abstract_Settings` | Settings class instance. |

#### `{option_key}_clanbite_settings_page_shell_after`

*Action* · `inc/extensions/class-extension-admin-settings.php`

Fires after the classic settings page shell markup.

Dynamic hook: `{option_key}_clanbite_settings_page_shell_after`.

| Parameter | Type | Description |
| --- | --- | --- |
| `$settings` | `Abstract_Settings` | Settings class instance. |

#### `{option_key}_clanbite_settings_page_shell_before`

*Action* · `inc/extensions/class-extension-admin-settings.php`

Fires before the classic settings page shell markup.

Dynamic hook: `{option_key}_clanbite_settings_page_shell_before`.

| Parameter | Type | Description |
| --- | --- | --- |
| `$settings` | `Abstract_Settings` | Settings class instance. |

#### `{option_key}_clanbite_settings_ui_section_field_registry`

*Filter* · `inc/extensions/class-extension-admin-settings.php`

Filter section fields before they are registered.

Dynamic hook: `{option_key}_clanbite_settings_ui_section_field_registry`.

| Parameter | Type | Description |
| --- | --- | --- |
| `$fields` | `array` | Field config map. |
| `$section_id` | `string` | Section id. |
| `$section` | `array` | Section config. |
| `$settings` | `Abstract_Settings` | Settings class instance. |

#### `{option_key}_clanbite_settings_ui_section_registry`

*Filter* · `inc/extensions/class-extension-admin-settings.php`

Filter all sections before they are registered.

Dynamic hook: `{option_key}_clanbite_settings_ui_section_registry`.

| Parameter | Type | Description |
| --- | --- | --- |
| `$sections` | `array` | Sections array. |
| `$settings` | `Abstract_Settings` | Settings class instance. |

#### `{option_key}_clanbite_standalone_submenu_parent_slug`

*Filter* · `inc/extensions/class-extension-admin-settings.php`

Filters the parent menu slug of the classic standalone settings submenu.

Dynamic hook: `{option_key}_clanbite_standalone_submenu_parent_slug`, where `option_key` is this settings
bucket's option name (for example `clanbite_players_settings`). Only used when the submenu is registered.

| Parameter | Type | Description |
| --- | --- | --- |
| `$parent_slug` | `string` | Parent menu slug (default `clanbite`). |
| `$settings` | `\Kernowdev\Clanbite\Extensions\Abstract_Settings` | Settings instance. |

## General

#### `clanbite_abilities_active_extensions_list`

*Filter* · `inc/Abilities_Api.php`

Filter the payload returned by the `clanbite/active-extensions` ability.

| Parameter | Type | Description |
| --- | --- | --- |
| `$list` | `list<array{slug: string, name: string, description: string, version: string}>` | Extensions. |

#### `clanbite_abilities_registered`

*Action* · `inc/Abilities_Api.php`

Fires after Clanbite registers core abilities (WordPress 6.9+).

Companion plugins may call `wp_register_ability()` here to add more abilities
in the same category using slug `clanbite` via `Abilities_Api::CATEGORY`.

_No arguments._

#### `clanbite_block_entity_link_url`

*Filter* · `inc/functions-block-entity-link.php`

Filter permalink used for linked player/team blocks.

| Parameter | Type | Description |
| --- | --- | --- |
| `$url` | `string` | URL. |
| `$block_name` | `string` | Block name. |
| `$entity_id` | `int` | User or team post ID. |
| `$block` | `\WP_Block\|null` | Block instance. |

#### `clanbite_block_fragment_allowed_html`

*Filter* · `inc/clanbite-block-render-escape.php`

Filter: `clanbite_block_fragment_allowed_html` — extend safe tags/attributes for `wp_kses()` on block fragments.

Do not mutate the passed array in place; return an altered copy when adding keys.

| Parameter | Type | Description |
| --- | --- | --- |
| `$allowed` | `array<string, array<string, bool>>` | Base allow-list (post rules plus Clanbite SVG tags). |

#### `clanbite_block_markup_before_do_blocks`

*Filter* · `inc/functions-block-templates.php`

Filter serialized block markup before `do_blocks()` runs for a Clanbite template file.

| Parameter | Type | Description |
| --- | --- | --- |
| `$markup` | `string` | Raw markup read from `$markup_file`. |
| `$markup_file` | `string` | Absolute path to the template source file. |

#### `clanbite_public_team_response`

*Filter* · `inc/class-public-rest.php`

Filter the public team payload exposed to other Clanbite sites.

| Parameter | Type | Description |
| --- | --- | --- |
| `$data` | `array` | Response data. |
| `$post` | `WP_Post` | Team post. |

#### `clanbite_render_block_markup_file`

*Action* · `inc/functions-block-templates.php`

Fires before Clanbite echoes template markup through `do_blocks()`.

| Parameter | Type | Description |
| --- | --- | --- |
| `$markup_file` | `string` | Absolute path to the template source file. |
| `$markup` | `string` | Markup after `clanbite_block_markup_before_do_blocks`. |

#### `clanbite_seo_enabled`

*Filter* · `inc/Front_Seo.php`

Master switch for Clanbite front SEO (titles, robots, OG, JSON-LD).

| Parameter | Type | Description |
| --- | --- | --- |
| `$enabled` | `bool` | Default true. |

#### `clanbite_seo_group_graph`

*Filter* · `inc/Front_Seo.php`

Extra JSON-LD @graph nodes for a Social Kit group (`cp_group`).

| Parameter | Type | Description |
| --- | --- | --- |
| `$extra_graph` | `array<int, array<string, mixed>>` | Nodes. |
| `$post_id` | `int` | Group post ID. |

#### `clanbite_seo_match_graph`

*Filter* · `inc/Front_Seo.php`

Extra JSON-LD @graph nodes for a match (single `cp_match`).

| Parameter | Type | Description |
| --- | --- | --- |
| `$extra_graph` | `array<int, array<string, mixed>>` | Nodes. |
| `$post_id` | `int` | Post ID. |

#### `clanbite_seo_output_json_ld`

*Filter* · `inc/Front_Seo.php`

Whether to print JSON-LD script tags.

| Parameter | Type | Description |
| --- | --- | --- |
| `$enabled` | `bool` | Default true. |

#### `clanbite_seo_output_open_graph`

*Filter* · `inc/Front_Seo.php`

Whether to print Open Graph and Twitter Card meta tags.

| Parameter | Type | Description |
| --- | --- | --- |
| `$enabled` | `bool` | Default true. |

#### `clanbite_seo_output_site_graph`

*Filter* · `inc/Front_Seo.php`

Whether to print WebSite + Organization JSON-LD on the front page.

| Parameter | Type | Description |
| --- | --- | --- |
| `$enabled` | `bool` | Default true when no major SEO plugin is active. |

#### `clanbite_seo_player_graph`

*Filter* · `inc/Front_Seo.php`

Extra JSON-LD @graph nodes for a player profile (merged after core nodes).

| Parameter | Type | Description |
| --- | --- | --- |
| `$extra_graph` | `array<int, array<string, mixed>>` | Nodes. |
| `$user_id` | `int` | User ID. |

#### `clanbite_seo_team_graph`

*Filter* · `inc/Front_Seo.php`

Extra JSON-LD @graph nodes for a team profile.

| Parameter | Type | Description |
| --- | --- | --- |
| `$extra_graph` | `array<int, array<string, mixed>>` | Nodes. |
| `$team_id` | `int` | Team post ID. |

#### `clanbite_visibility_container_editor_script_handle`

*Filter* · `clanbite.php`

Filter the editor script handle used to localize visibility-container role data.

Default is the handle WordPress registered for the block’s `editorScript` (from block.json).

| Parameter | Type | Description |
| --- | --- | --- |
| `$handle` | `string` | Editor script handle. |

#### `clanbite_visibility_container_should_show`

*Filter* · `inc/Blocks/Visibility_Container.php`

Whether to render the visibility container’s inner blocks for this request.

| Parameter | Type | Description |
| --- | --- | --- |
| `$visible` | `bool` | Resolved visibility before this filter. |
| `$attributes` | `array<string, mixed>` | Block attributes. |
| `$block` | `\WP_Block\|null` | Block instance. |

#### `clanbite_wordban_masked_html`

*Filter* · `inc/class-wordban.php`

Filter HTML after the word mask runs on text nodes.

| Parameter | Type | Description |
| --- | --- | --- |
| `$out` | `string` | Masked HTML. |
| `$html` | `string` | Original HTML. |

#### `clanbite_wordban_masked_plain_text`

*Filter* · `inc/class-wordban.php`

Filter plain text after the word mask runs.

| Parameter | Type | Description |
| --- | --- | --- |
| `$out` | `string` | Masked text. |
| `$text` | `string` | Original text. |

#### `clanbite_wordban_merged_tokens`

*Filter* · `inc/class-wordban.php`

Filter merged canonical banned tokens/phrases after defaults and the option list are combined.

Each entry is either a single normalized token (letters only) or a space-separated phrase of tokens.

| Parameter | Type | Description |
| --- | --- | --- |
| `$merged` | `array<int, string>` | Merged list. |

## Matches

#### `clanbite_cross_site_sync_incoming_created`

*Action* · `inc/class-cross-site-match-sync.php`

Fires after this site created a match from a peer sync request.

| Parameter | Type | Description |
| --- | --- | --- |
| `$match_id` | `int` | New local `cp_match` ID. |
| `$body` | `array` | Request body. |
| `$home_team_id` | `int` | Local home team ID. |

#### `clanbite_cross_site_sync_incoming_payload`

*Filter* · `inc/class-cross-site-match-sync.php`

Filter incoming peer-sync body after signature verification (mutate with care).

| Parameter | Type | Description |
| --- | --- | --- |
| `$body` | `array` | Decoded JSON body. |
| `$source_host` | `string` | Normalized host from `source_site` (may be empty before you fix `source_site`). |

#### `clanbite_cross_site_sync_key`

*Filter* · `inc/class-cross-site-match-sync.php`

Filter the legacy HMAC key for cross-site match sync (optional).

When non-empty, requests use the older `timestamp:hex_hmac` header instead of Ed25519 `v1:…`.
Prefer the default automatic Ed25519 handshake; use this only for backward compatibility.

| Parameter | Type | Description |
| --- | --- | --- |
| `$key` | `string` | Legacy shared secret; empty uses automatic per-site keys. |

#### `clanbite_cross_site_sync_outbound_payload`

*Filter* · `inc/class-cross-site-match-sync.php`

Filter outbound peer-sync payload before signing and POST.

| Parameter | Type | Description |
| --- | --- | --- |
| `$body` | `array` | Payload (do not remove required keys without replacing behavior). |
| `$challenge_id` | `int` | Local challenge ID. |
| `$match_id` | `int` | Local match ID. |
| `$challenged_team_id` | `int` | Challenged team ID on this site. |
| `$snapshot` | `array` | Remote snapshot from the challenge form. |

#### `clanbite_cross_site_sync_push_failed`

*Action* · `inc/class-cross-site-match-sync.php`

Fires when outbound peer match sync fails (HTTP transport or WP_Error).

| Parameter | Type | Description |
| --- | --- | --- |
| `$response` | `\WP_Error` | Error object. |
| `$url` | `string` | Remote REST URL. |
| `$body` | `array` | Payload that was sent. |

#### `clanbite_cross_site_sync_push_rejected`

*Action* · `inc/class-cross-site-match-sync.php`

Fires when the peer returns a non-success sync response.

| Parameter | Type | Description |
| --- | --- | --- |
| `$code` | `int` | HTTP status. |
| `$data` | `array` | Decoded body (may be empty). |
| `$url` | `string` | Remote REST URL. |
| `$body` | `array` | Outbound payload. |

#### `clanbite_cross_site_sync_push_succeeded`

*Action* · `inc/class-cross-site-match-sync.php`

Fires after a peer site successfully stored the mirrored match.

| Parameter | Type | Description |
| --- | --- | --- |
| `$challenge_id` | `int` | Local challenge ID. |
| `$local_match_id` | `int` | Local match ID. |
| `$peer_match_id` | `int` | Match ID on the peer site. |
| `$peer_origin` | `string` | Peer site origin URL. |
| `$body` | `array` | Outbound payload. |

#### `clanbite_cross_site_sync_verify_source`

*Filter* · `inc/class-cross-site-match-sync.php`

Whether to accept an incoming sync from this source host after the signature is valid.

| Parameter | Type | Description |
| --- | --- | --- |
| `$allow` | `bool` | Default true. |
| `$source_host` | `string` | Normalized host from `source_site`. |
| `$body` | `array` | Full decoded body. |

#### `clanbite_match_block_wrapper_attributes`

*Filter* · `src/blocks/matches/match-card/render.php`

Filter: `clanbite_match_block_wrapper_attributes` — attribute array passed to `get_block_wrapper_attributes()` for Match blocks.

| Parameter | Type | Description |
| --- | --- | --- |
| `$wrapper_attrs` | `array` | Attribute map (empty default). |
| `$block_name` | `string` | Block name (e.g. `clanbite/match-card`). |
| `$block` | `WP_Block` | Block instance. |

*Also fired in:* `src/blocks/matches/match-list/render.php`

#### `clanbite_match_card_block_inner_markup`

*Filter* · `src/blocks/matches/match-card/render.php`

Filter: `clanbite_match_card_block_inner_markup` — inner HTML for the Match card block (inside the wrapper div).

| Parameter | Type | Description |
| --- | --- | --- |
| `$markup` | `string` | Default inner markup. |
| `$attrs` | `array` | Block attributes. |
| `$block` | `WP_Block` | Block instance. |

#### `clanbite_match_list_block_inner_markup`

*Filter* · `src/blocks/matches/match-list/render.php`

Filter: `clanbite_match_list_block_inner_markup` — inner HTML for the Match list block (inside the wrapper div).

| Parameter | Type | Description |
| --- | --- | --- |
| `$markup` | `string` | Default inner markup. |
| `$attributes` | `array` | Block attributes. |
| `$block` | `WP_Block` | Block instance. |

#### `clanbite_match_rest_prepare`

*Filter* · `inc/extensions/matches/class-matches-rest.php`

Filter: `clanbite_match_rest_prepare` — shaped match row for REST (`collection` or `single`).

| Parameter | Type | Description |
| --- | --- | --- |
| `$item` | `array<string, mixed>` | Row data. |
| `$post` | `WP_Post` | Match post. |
| `$request` | `WP_REST_Request` | Request instance. |
| `$context` | `string` | `collection` or `single`. |

#### `clanbite_match_saved`

*Action* · `inc/extensions/matches/class-extension-matches.php`

Fires after Clanbite validates a match post on save.

Use to trigger side-effects (e.g. optional activity-feed integrations) when a match is
scheduled/updated and its visibility may affect who should see it.

| Parameter | Type | Description |
| --- | --- | --- |
| `$post_id` | `int` | Match post ID. |
| `$post` | `WP_Post` | Match post object. |
| `$extension` | `Matches` | Matches extension instance. |

#### `clanbite_matches_defaults`

*Filter* · `inc/extensions/matches/class-extension-matches-admin.php`

Filter default Matches extension settings before they are merged with stored options.

| Parameter | Type | Description |
| --- | --- | --- |
| `$defaults` | `array<string, mixed>` | Keyed by option field id. |

#### `clanbite_matches_rest_collection_data`

*Filter* · `inc/extensions/matches/class-matches-rest.php`

Filter: `clanbite_matches_rest_collection_data` — JSON body for `GET clanbite/v1/matches` before encoding.

| Parameter | Type | Description |
| --- | --- | --- |
| `$body` | `array<string, mixed>` | Keys: `matches`, `total`, `pages`. |
| `$request` | `WP_REST_Request` | REST request instance. |

#### `clanbite_matches_rest_query_args`

*Filter* · `inc/extensions/matches/class-matches-rest.php`

Filter: `clanbite_matches_rest_query_args` — arguments passed to `WP_Query` for the matches collection route.

| Parameter | Type | Description |
| --- | --- | --- |
| `$args` | `array<string, mixed>` | Query arguments (invalid combinations may trigger SQL errors). |
| `$request` | `WP_REST_Request` | REST request instance. |

#### `clanbite_matches_sections`

*Filter* · `inc/extensions/matches/class-extension-matches-admin.php`

Filter Matches settings sections (tabs/sections in React or classic UI).

| Parameter | Type | Description |
| --- | --- | --- |
| `$sections` | `array<string, array<string, mixed>>` | Section id => config with `title` and `fields`. |

#### `clanbite_viewer_can_see_match`

*Filter* · `inc/extensions/matches/class-extension-matches.php`

Filter whether a viewer may see a match (REST, blocks, templates).

| Parameter | Type | Description |
| --- | --- | --- |
| `$can` | `bool` | Default decision from Clanbite visibility rules. |
| `$post` | `WP_Post` | Match post. |
| `$viewer_id` | `int` | Viewer user ID (0 for guests). |

## Notifications

#### `clanbite_before_insert_notification`

*Filter* · `inc/extensions/notifications/class-notification-data-access.php`

Filter notification data before insertion.

| Parameter | Type | Description |
| --- | --- | --- |
| `$data` | `array` | Notification data. |

#### `clanbite_format_notification_response`

*Filter* · `inc/extensions/notifications/class-notification-rest-controller.php`

Filter formatted notification for API response.

| Parameter | Type | Description |
| --- | --- | --- |
| `$formatted` | `array` | Formatted notification. |
| `$notification` | `object` | Original notification object. |

#### `clanbite_notification_action_`

*Filter* · `inc/extensions/notifications/class-notification-data-access.php`

Execute a notification action.

Handlers should return an array with 'success', 'message', and optionally 'redirect'.

| Parameter | Type | Description |
| --- | --- | --- |
| `$result` | `array\|null` | Initial result (null). |
| `$notification` | `object` | Notification object. |
| `$action` | `array` | Action config. |
| `$user_id` | `int` | User ID. |

#### `clanbite_notification_action_handler`

*Filter* · `inc/extensions/notifications/class-notification-data-access.php`

Generic notification action handler.

| Parameter | Type | Description |
| --- | --- | --- |
| `$result` | `array\|null` | Initial result (null). |
| `$handler` | `string` | Handler identifier. |
| `$notification` | `object` | Notification object. |
| `$action` | `array` | Action config. |
| `$user_id` | `int` | User ID. |

#### `clanbite_notification_deleted`

*Action* · `inc/extensions/notifications/class-notification-data-access.php`

Fires after a notification is deleted.

| Parameter | Type | Description |
| --- | --- | --- |
| `$notification_id` | `int` | Notification ID. |
| `$user_id` | `int` | User ID. |

#### `clanbite_notification_hydrate`

*Filter* · `inc/extensions/notifications/class-notification-data-access.php`

Filter a notification after hydration.

| Parameter | Type | Description |
| --- | --- | --- |
| `$row` | `object` | Notification object. |

#### `clanbite_notification_inserted`

*Action* · `inc/extensions/notifications/class-notification-data-access.php`

Fires after a notification is inserted.

| Parameter | Type | Description |
| --- | --- | --- |
| `$notification_id` | `int` | Notification ID. |
| `$data` | `array` | Notification data. |

#### `clanbite_notification_next_poll_interval`

*Filter* · `inc/extensions/notifications/class-notification-rest-controller.php`

Filter the next poll interval.

| Parameter | Type | Description |
| --- | --- | --- |
| `$next_poll` | `int` | Next poll interval in milliseconds. |
| `$new_notifications` | `array` | New notifications found. |
| `$user_id` | `int` | User ID. |

#### `clanbite_notification_poll_blocking_wait`

*Filter* · `inc/extensions/notifications/class-notification-rest-controller.php`

Filters whether `/notifications/poll` may sleep in a loop until it times out or new items arrive (long-polling).

| Parameter | Type | Description |
| --- | --- | --- |
| `$blocking` | `bool` | Defaults to the Notifications setting `poll_long_polling`. |
| `$user_id` | `int` | Polling user ID. |

#### `clanbite_notification_poll_interval`

*Filter* · `inc/extensions/notifications/class-notification-rest-controller.php`

Filter the polling check interval.

| Parameter | Type | Description |
| --- | --- | --- |
| `$interval` | `int` | Interval in seconds. |
| `$user_id` | `int` | User ID. |

#### `clanbite_notification_poll_tick`

*Action* · `inc/extensions/notifications/class-notification-rest-controller.php`

Action fired during each poll iteration.

Can be used to check for other real-time events.

| Parameter | Type | Description |
| --- | --- | --- |
| `$user_id` | `int` | User ID. |
| `$since` | `string` | Since timestamp. |
| `$last_id` | `int` | Last notification ID. |

#### `clanbite_notification_poll_timeout`

*Filter* · `inc/extensions/notifications/class-notification-rest-controller.php`

Filter the polling timeout.

| Parameter | Type | Description |
| --- | --- | --- |
| `$timeout` | `int` | Timeout in seconds. |
| `$user_id` | `int` | User ID. |

#### `clanbite_notification_poll_transport`

*Filter* · `inc/extensions/notifications/class-notification-rest-controller.php`

Filter to use alternative transport (e.g., WebSockets).

Return a WP_REST_Response to bypass polling entirely.

| Parameter | Type | Description |
| --- | --- | --- |
| `$response` | `WP_REST_Response\|null` | Response or null to continue with polling. |
| `$user_id` | `int` | User ID. |
| `$since` | `string` | Since timestamp. |
| `$last_id` | `int` | Last notification ID. |
| `$request` | `WP_REST_Request` | Original request. |

#### `clanbite_notification_read`

*Action* · `inc/extensions/notifications/class-notification-data-access.php`

Fires after a notification is marked as read.

| Parameter | Type | Description |
| --- | --- | --- |
| `$notification_id` | `int` | Notification ID. |
| `$user_id` | `int` | User ID. |

#### `clanbite_notification_status_changed`

*Action* · `inc/extensions/notifications/class-notification-data-access.php`

Fires after a notification status is updated.

| Parameter | Type | Description |
| --- | --- | --- |
| `$notification_id` | `int` | Notification ID. |
| `$status` | `string` | New status. |

#### `clanbite_notification_transport_config`

*Filter* · `inc/extensions/notifications/class-notification-rest-controller.php`

Filter the notification transport configuration.

Use this to provide WebSocket configuration when available.

| Parameter | Type | Description |
| --- | --- | --- |
| `$config` | `array` | Transport configuration. |
| `$user_id` | `int` | Current user ID. |

#### `clanbite_notification_types`

*Filter* · `inc/extensions/notifications/functions.php`

Filter registered notification types.

Third-party developers can add their own types here.

| Parameter | Type | Description |
| --- | --- | --- |
| `$types` | `array<string, array{label: string, icon: string}>` | Notification types. |

#### `clanbite_notifications_all_read`

*Action* · `inc/extensions/notifications/class-notification-data-access.php`

Fires after all notifications are marked as read.

| Parameter | Type | Description |
| --- | --- | --- |
| `$user_id` | `int` | User ID. |
| `$count` | `int` | Number marked read. |

#### `clanbite_notifications_extension_active`

*Filter* · `inc/extensions/notifications/functions.php`

Filter whether notifications are considered active for theme and third-party checks.

| Parameter | Type | Description |
| --- | --- | --- |
| `$active` | `bool` | True when `cp_notifications` is in the installed-extensions option. |

#### `clanbite_player_notifications_page_markup`

*Filter* · `inc/extensions/notifications/functions.php`

Filter the full notifications subpage markup.

| Parameter | Type | Description |
| --- | --- | --- |
| `$html` | `string` | HTML output. |

#### `clanbite_render_notification`

*Filter* · `inc/extensions/notifications/functions.php`

Filter the rendered notification HTML.

| Parameter | Type | Description |
| --- | --- | --- |
| `$html` | `string` | Rendered HTML. |
| `$notification` | `object` | Notification object. |
| `$compact` | `bool` | Compact mode. |

## Players

#### `clanbite_after_account_info_fields`

*Action* · `inc/extensions/players/class-extension-players.php`

Fires after the fields on the Account Info settings panel.

| Parameter | Type | Description |
| --- | --- | --- |
| `$user_id` | `int` | Profile owner user ID. |
| `$user` | `\WP_User` | Profile owner. |

#### `clanbite_after_profile_info_fields`

*Action* · `inc/extensions/players/class-extension-players.php`

Fires after the fields on the Profile Info settings panel.

| Parameter | Type | Description |
| --- | --- | --- |
| `$user_id` | `int` | Profile owner user ID. |
| `$user` | `\WP_User` | Profile owner. |

#### `clanbite_after_social_networks_fields`

*Action* · `inc/extensions/players/class-extension-players.php`

Fires after the Social Networks fields on the player settings screen.

| Parameter | Type | Description |
| --- | --- | --- |
| `$user_id` | `int` | Profile owner user ID. |

#### `clanbite_before_account_info_fields`

*Action* · `inc/extensions/players/class-extension-players.php`

Fires before the fields on the Account Info settings panel.

| Parameter | Type | Description |
| --- | --- | --- |
| `$user_id` | `int` | Profile owner user ID. |
| `$user` | `\WP_User` | Profile owner. |

#### `clanbite_before_profile_avatar_fields`

*Action* · `inc/extensions/players/class-extension-players.php`

Fires before the avatar and cover upload fields on the Profile Info settings panel.

| Parameter | Type | Description |
| --- | --- | --- |
| `$user_id` | `int` | Profile owner user ID. |

#### `clanbite_before_profile_info_fields`

*Action* · `inc/extensions/players/class-extension-players.php`

Fires before the fields on the Profile Info settings panel.

| Parameter | Type | Description |
| --- | --- | --- |
| `$user_id` | `int` | Profile owner user ID. |
| `$user` | `\WP_User` | Profile owner. |

#### `clanbite_before_social_networks_fields`

*Action* · `inc/extensions/players/class-extension-players.php`

Fires before the Social Networks fields on the player settings screen.

| Parameter | Type | Description |
| --- | --- | --- |
| `$user_id` | `int` | Profile owner user ID. |

#### `clanbite_load_player_settings_template`

*Filter* · `inc/extensions/players/class-extension-players.php`

Filters the template used for the player settings route.

| Parameter | Type | Description |
| --- | --- | --- |
| `$template` | `string` | Resolved template path. |

#### `clanbite_load_player_subpage_template`

*Filter* · `inc/extensions/players/class-extension-players.php`

Filter the resolved player subpage template path.

| Parameter | Type | Description |
| --- | --- | --- |
| `$resolved` | `string` | Resolved path (may be empty). |
| `$sub` | `string` | Subpage slug. |
| `$located` | `string` | Theme or plugin PHP path. |

#### `clanbite_load_players_directory_template`

*Filter* · `inc/extensions/players/class-extension-players.php`

Filters the resolved template path for the players directory.

| Parameter | Type | Description |
| --- | --- | --- |
| `$path` | `string` | Resolved template path. |
| `$located_theme_path` | `string` | Template path found in the active theme, if any. |

#### `clanbite_player_avatar_img_after`

*Action* · `inc/extensions/players/functions.php`

Fires after the player avatar &lt;img&gt; HTML is built.

| Parameter | Type | Description |
| --- | --- | --- |
| `$user_id` | `int` | User ID. |
| `$args` | `array` | Arguments from `clanbite_players_get_player_avatar_img_html()`. |
| `$url` | `string` | Avatar URL. |
| `$html` | `string` | Built &lt;img&gt; markup. |

#### `clanbite_player_avatar_img_before`

*Action* · `inc/extensions/players/functions.php`

Fires before the player avatar &lt;img&gt; is built (URL is resolved).

| Parameter | Type | Description |
| --- | --- | --- |
| `$user_id` | `int` | User ID. |
| `$args` | `array` | Arguments passed to `clanbite_players_get_player_avatar_img_html()`. |
| `$url` | `string` | Avatar URL. |

#### `clanbite_player_avatar_updated`

*Action* · `inc/extensions/players/class-extension-players.php`

Fires after the player successfully uploads a new profile avatar attachment.

| Parameter | Type | Description |
| --- | --- | --- |
| `$user_id` | `int` | Profile owner user ID. |
| `$attachment_id` | `int` | New attachment ID. |

#### `clanbite_player_cover_updated`

*Action* · `inc/extensions/players/class-extension-players.php`

Fires after the player successfully uploads a new profile cover attachment.

| Parameter | Type | Description |
| --- | --- | --- |
| `$user_id` | `int` | Profile owner user ID. |
| `$attachment_id` | `int` | New attachment ID. |

#### `clanbite_player_handle_block_text`

*Filter* · `src/blocks/players/player-handle/render.php`

Filters the @ handle string for the Player @ handle block.

| Parameter | Type | Description |
| --- | --- | --- |
| `$text` | `string` | Full display string (normally includes leading @). |
| `$user_id` | `int` | Resolved player user ID. |
| `$nicename` | `string` | Nicename without @. |
| `$block` | `WP_Block` | Block instance. |

#### `clanbite_player_profile_home_label`

*Filter* · `src/blocks/players/profile-nav/render.php`

Filter the label for the player profile home link.

| Parameter | Type | Description |
| --- | --- | --- |
| `$label` | `string` | Default label. |
| `$user_id` | `int` | Player user ID. |

#### `clanbite_player_profile_nav_show_settings_link`

*Filter* · `src/blocks/players/player-settings-link/render.php`

Filter: `clanbite_player_profile_nav_show_settings_link` — show Settings on this profile.

| Parameter | Type | Description |
| --- | --- | --- |
| `$show` | `bool` | Whether to show the link. |
| `$user_id` | `int` | Player user ID. |

*Also fired in:* `src/blocks/players/profile-nav/render.php`

#### `clanbite_player_profile_settings_nav_label`

*Filter* · `src/blocks/players/profile-nav/render.php`

Filter: `clanbite_player_profile_settings_nav_label` — label for the Settings item.

| Parameter | Type | Description |
| --- | --- | --- |
| `$label` | `string` | Default label. |
| `$user_id` | `int` | Player user ID. |

#### `clanbite_player_profile_settings_url`

*Filter* · `src/blocks/players/player-settings-link/render.php`

Filter: `clanbite_player_profile_settings_url` — URL for the Settings item (player account settings).

| Parameter | Type | Description |
| --- | --- | --- |
| `$url` | `string` | Default URL. |
| `$user_id` | `int` | Player user ID (profile being viewed / settings owner). |

*Also fired in:* `src/blocks/players/profile-nav/render.php`

#### `clanbite_player_profile_url`

*Filter* · `inc/extensions/players/functions.php`

Filter the canonical player profile URL for a user.

| Parameter | Type | Description |
| --- | --- | --- |
| `$url` | `string` | Profile URL. |
| `$user_id` | `int` | User ID. |

#### `clanbite_player_query_member_user_ids`

*Filter* · `inc/extensions/players/functions-player-query.php`

Filter member user IDs after resolving the player query (team roster + exclusions + meta + default ordering).

| Parameter | Type | Description |
| --- | --- | --- |
| `$user_ids` | `list<int>` | User IDs in display order. |
| `$team_id` | `int` | Team post ID. |
| `$query_options` | `array<string, mixed>` | Normalized query options. |
| `$block` | `\WP_Block\|null` | Player template block, if available. |

#### `clanbite_player_settings_before_nav`

*Action* · `src/blocks/players/player-settings/render.php`

Fires before the player settings navigation is rendered.

_No arguments._

#### `clanbite_player_settings_frontend_config`

*Filter* · `inc/extensions/players/class-extension-players.php`

Filters the data localized as `CLANBITEPLAYERSETTINGS` for the player settings front end.

Keys include `ajax_url`, `nonce`, `rest_url`, `rest_nonce` and, on the settings screen, `settings_url_base`, `settings_initial_nav`, `settings_initial_panel`.

| Parameter | Type | Description |
| --- | --- | --- |
| `$config` | `array<string, mixed>` | Localized configuration. |

#### `clanbite_player_settings_panel_{nav_sub_item}`

*Action* · `src/blocks/players/player-settings/render.php`

Renders the content of one player settings sub-panel.

The dynamic portion is the sub-navigation slug (for example `profile-info`, `account-info`).

_No arguments._

#### `clanbite_player_settings_update_account_firstname`

*Filter* · `inc/extensions/players/class-extension-players.php`

Filters the account first name before it is saved from player settings.

| Parameter | Type | Description |
| --- | --- | --- |
| `$value` | `mixed` | Sanitized value; return a `WP_Error` to reject it (its message is shown next to the field). |
| `$user_id` | `int` | Profile owner user ID. |

#### `clanbite_player_settings_update_account_lastname`

*Filter* · `inc/extensions/players/class-extension-players.php`

Filters the account last name before it is saved from player settings.

| Parameter | Type | Description |
| --- | --- | --- |
| `$value` | `mixed` | Sanitized value; return a `WP_Error` to reject it (its message is shown next to the field). |
| `$user_id` | `int` | Profile owner user ID. |

#### `clanbite_player_settings_update_birthday`

*Filter* · `inc/extensions/players/class-extension-players.php`

Filters the profile birthday before it is saved from player settings.

| Parameter | Type | Description |
| --- | --- | --- |
| `$value` | `mixed` | Sanitized value; return a `WP_Error` to reject it (its message is shown next to the field). |
| `$user_id` | `int` | Profile owner user ID. |

#### `clanbite_player_settings_update_city`

*Filter* · `inc/extensions/players/class-extension-players.php`

Filters the profile city before it is saved from player settings.

| Parameter | Type | Description |
| --- | --- | --- |
| `$value` | `mixed` | Sanitized value; return a `WP_Error` to reject it (its message is shown next to the field). |
| `$user_id` | `int` | Profile owner user ID. |

#### `clanbite_player_settings_update_country`

*Filter* · `inc/extensions/players/class-extension-players.php`

Filters the profile country code before it is saved from player settings.

| Parameter | Type | Description |
| --- | --- | --- |
| `$value` | `mixed` | Sanitized value; return a `WP_Error` to reject it (its message is shown next to the field). |
| `$user_id` | `int` | Profile owner user ID. |

#### `clanbite_player_settings_update_description`

*Filter* · `inc/extensions/players/class-extension-players.php`

Filters the profile description before it is saved from player settings.

| Parameter | Type | Description |
| --- | --- | --- |
| `$value` | `mixed` | Sanitized value; return a `WP_Error` to reject it (its message is shown next to the field). |
| `$user_id` | `int` | Profile owner user ID. |

#### `clanbite_player_settings_update_display_cover_position_x`

*Filter* · `inc/extensions/players/class-extension-players.php`

Filters the horizontal cover focal position before it is saved from player settings.

| Parameter | Type | Description |
| --- | --- | --- |
| `$value` | `mixed` | Sanitized value; return a `WP_Error` to reject it (its message is shown next to the field). |
| `$user_id` | `int` | Profile owner user ID. |

#### `clanbite_player_settings_update_display_cover_position_y`

*Filter* · `inc/extensions/players/class-extension-players.php`

Filters the vertical cover focal position before it is saved from player settings.

| Parameter | Type | Description |
| --- | --- | --- |
| `$value` | `mixed` | Sanitized value; return a `WP_Error` to reject it (its message is shown next to the field). |
| `$user_id` | `int` | Profile owner user ID. |

#### `clanbite_player_settings_update_display_name`

*Filter* · `inc/extensions/players/class-extension-players.php`

Filters the display name before it is saved from player settings.

| Parameter | Type | Description |
| --- | --- | --- |
| `$value` | `mixed` | Sanitized value; return a `WP_Error` to reject it (its message is shown next to the field). |
| `$user_id` | `int` | Profile owner user ID. |

#### `clanbite_player_settings_update_social_profile_value`

*Filter* · `inc/extensions/players/class-extension-players.php`

Filters a social profile value before it is saved from player settings.

| Parameter | Type | Description |
| --- | --- | --- |
| `$value` | `string\|WP_Error` | Sanitized string, or WP_Error to reject. |
| `$slug` | `string` | Field slug (e.g. `facebook`). |
| `$user_id` | `int` | User ID. |

#### `clanbite_player_settings_update_tagline`

*Filter* · `inc/extensions/players/class-extension-players.php`

Filters the profile tagline before it is saved from player settings.

| Parameter | Type | Description |
| --- | --- | --- |
| `$value` | `mixed` | Sanitized value; return a `WP_Error` to reject it (its message is shown next to the field). |
| `$user_id` | `int` | Profile owner user ID. |

#### `clanbite_player_settings_update_website`

*Filter* · `inc/extensions/players/class-extension-players.php`

Filters the profile website before it is saved from player settings.

| Parameter | Type | Description |
| --- | --- | --- |
| `$value` | `mixed` | Sanitized value; return a `WP_Error` to reject it (its message is shown next to the field). |
| `$user_id` | `int` | Profile owner user ID. |

#### `clanbite_player_social_links_block_items`

*Filter* · `src/blocks/players/player-social-links/render.php`

Filters the list of social links rendered by the Player Social Links block.

| Parameter | Type | Description |
| --- | --- | --- |
| `$items` | `array<int, array{slug: string, url: string, label: string}>` | Links to render. |
| `$user_id` | `int` | Resolved player user ID. |
| `$block` | `WP_Block` | Block instance. |

#### `clanbite_players_avatar_shape`

*Filter* · `inc/extensions/players/functions.php`

Filter the player avatar shape.

| Parameter | Type | Description |
| --- | --- | --- |
| `$shape` | `string` | Avatar shape. |

#### `clanbite_players_countries_filters`

*Filter* · `inc/extensions/players/functions.php`

Filter the countries before returning

| Parameter | Type | Description |
| --- | --- | --- |
| `$countries` | `array` | countries array for filtering |

#### `clanbite_players_defaults`

*Filter* · `inc/extensions/players/class-extension-players-admin.php`

Filters the default Players settings.

| Parameter | Type | Description |
| --- | --- | --- |
| `$defaults` | `array<string, mixed>` | Defaults keyed by setting. |

#### `clanbite_players_directory_per_page`

*Filter* · `inc/extensions/players/class-extension-players.php`

Filters the page size of the players directory shortcode.

| Parameter | Type | Description |
| --- | --- | --- |
| `$per_page` | `int` | Users per page (default 20). |

#### `clanbite_players_get_account_firstname`

*Filter* · `inc/extensions/players/functions.php`

Allows filtering of the players account first name, this can be bypassed by setting $suppress_filters param to true.

| Parameter | Type | Description |
| --- | --- | --- |
| `$first_name` | `string` | Account first name. |
| `$player_id` | `int` | Player (user) ID. |

#### `clanbite_players_get_account_fullname`

*Filter* · `inc/extensions/players/functions.php`

Allows filtering of the players account full name, this can be bypassed by setting $suppress_filters param to true.

| Parameter | Type | Description |
| --- | --- | --- |
| `$full_name` | `string` | Account full name. |
| `$player_id` | `int` | Player (user) ID. |
| `$first_name` | `string` | Account first name. |
| `$last_name` | `string` | Account last name. |

#### `clanbite_players_get_account_lastname`

*Filter* · `inc/extensions/players/functions.php`

Allows filtering of the players account last name, this can be bypassed by setting $suppress_filters param to true.

| Parameter | Type | Description |
| --- | --- | --- |
| `$last_name` | `string` | Account last name. |
| `$player_id` | `int` | Player (user) ID. |

#### `clanbite_players_get_default_avatar`

*Filter* · `inc/extensions/players/functions.php`

Filters the default player avatar URL.

| Parameter | Type | Description |
| --- | --- | --- |
| `$default_avatar` | `string` | Default avatar URL (setting or bundled image). |
| `$player_id` | `int` | Player (user) ID. |

#### `clanbite_players_get_default_cover`

*Filter* · `inc/extensions/players/functions.php`

Filters the default player cover URL.

| Parameter | Type | Description |
| --- | --- | --- |
| `$default_cover` | `string` | Default cover URL (setting or bundled image). |
| `$player_id` | `int` | Player (user) ID. |

#### `clanbite_players_get_display_age`

*Filter* · `inc/extensions/players/functions.php`

Allows filtering of the players display age, this can be bypassed by setting $suppress_filters param to true.

| Parameter | Type | Description |
| --- | --- | --- |
| `$age` | `string` | Computed age. |
| `$player_id` | `int` | Player (user) ID. |
| `$birthday` | `string` | Birthday the age was computed from. |

#### `clanbite_players_get_display_avatar`

*Filter* · `inc/extensions/players/functions.php`

Filters the resolved player avatar image URL after attachment/default resolution.

Return a URL string; consumers (REST, img[src], notifications) expect a URL, not HTML. Use
`clanbite_players_get_player_avatar_img_html()` for markup.

| Parameter | Type | Description |
| --- | --- | --- |
| `$user_avatar` | `string` | URL. |
| `$player_id` | `int` | User ID. |
| `$size` | `string\|array` | Size used for attachment resolution. |
| `$context` | `string` | Surface key (empty string when not passed by caller). |
| `$avatar_preset` | `string` | `large`, `medium`, `small`, or empty when an explicit size was used. |

#### `clanbite_players_get_display_avatar_id`

*Filter* · `inc/extensions/players/functions.php`

Allows filtering of the players display avatar attachment ID, this can be bypassed by setting $suppress_filters param to true.

| Parameter | Type | Description |
| --- | --- | --- |
| `$avatar_id` | `int` | Avatar attachment ID (0 when unset). |
| `$player_id` | `int` | Player (user) ID. |

#### `clanbite_players_get_display_bio`

*Filter* · `inc/extensions/players/functions.php`

Allows filtering of the players display bio, this can be bypassed by setting $suppress_filters param to true.

| Parameter | Type | Description |
| --- | --- | --- |
| `$bio` | `string` | Profile description. |
| `$player_id` | `int` | Player (user) ID. |

#### `clanbite_players_get_display_birthday`

*Filter* · `inc/extensions/players/functions.php`

Allows filtering of the players display birthday, this can be bypassed by setting $suppress_filters param to true.

| Parameter | Type | Description |
| --- | --- | --- |
| `$birthday` | `string` | Birthday (`Y-m-d`, empty when unset). |
| `$player_id` | `int` | Player (user) ID. |

#### `clanbite_players_get_display_city`

*Filter* · `inc/extensions/players/functions.php`

Allows filtering of the players display city, this can be bypassed by setting $suppress_filters param to true.

| Parameter | Type | Description |
| --- | --- | --- |
| `$city` | `string` | Profile city. |
| `$player_id` | `int` | Player (user) ID. |

#### `clanbite_players_get_display_country`

*Filter* · `inc/extensions/players/functions.php`

Allows filtering of the players display country, this can be bypassed by setting $suppress_filters param to true.

| Parameter | Type | Description |
| --- | --- | --- |
| `$country` | `string` | Country name or code, per `$return_type`. |
| `$player_id` | `int` | Player (user) ID. |
| `$return_type` | `string` | What was requested: `name` or `code`. |

#### `clanbite_players_get_display_cover`

*Filter* · `inc/extensions/players/functions.php`

Allows filtering of the players display cover, this can be bypassed by setting $suppress_filters param to true.

| Parameter | Type | Description |
| --- | --- | --- |
| `$cover` | `string` | Cover image URL. |
| `$player_id` | `int` | Player (user) ID. |

#### `clanbite_players_get_display_cover_id`

*Filter* · `inc/extensions/players/functions.php`

Allows filtering of the players display cover attachment ID, this can be bypassed by setting $suppress_filters param to true.

| Parameter | Type | Description |
| --- | --- | --- |
| `$cover_id` | `int` | Cover attachment ID (0 when unset). |
| `$player_id` | `int` | Player (user) ID. |

#### `clanbite_players_get_display_cover_position_x`

*Filter* · `inc/extensions/players/functions.php`

Allows filtering of the players display cover position on the X axis, this can be bypassed by setting $suppress_filters param to true.

| Parameter | Type | Description |
| --- | --- | --- |
| `$position` | `float` | Horizontal focal position, 0 to 1. |
| `$player_id` | `int` | Player (user) ID. |

#### `clanbite_players_get_display_cover_position_y`

*Filter* · `inc/extensions/players/functions.php`

Allows filtering of the players display cover position on the Y axis, this can be bypassed by setting $suppress_filters param to true.

| Parameter | Type | Description |
| --- | --- | --- |
| `$position` | `float` | Vertical focal position, 0 to 1. |
| `$player_id` | `int` | Player (user) ID. |

#### `clanbite_players_get_display_name`

*Filter* · `inc/extensions/players/functions.php`

Allows filtering of the players display name, this can be bypassed by setting $suppress_filters param to true.

| Parameter | Type | Description |
| --- | --- | --- |
| `$display_name` | `string` | Display name. |
| `$player_id` | `int` | Player (user) ID. |
| `$player` | `\WP_User` | Player user object. |

#### `clanbite_players_get_display_social`

*Filter* · `inc/extensions/players/functions.php`

Filters a player’s social profile field after reading user meta.

| Parameter | Type | Description |
| --- | --- | --- |
| `$value` | `string` | Stored value. |
| `$slug` | `string` | Field slug (e.g. `facebook`, `x`). |
| `$player_id` | `int` | User ID. |

#### `clanbite_players_get_display_tagline`

*Filter* · `inc/extensions/players/functions.php`

Allows filtering of the players display tagline, this can be bypassed by setting $suppress_filters param to true.

| Parameter | Type | Description |
| --- | --- | --- |
| `$tagline` | `string` | Profile tagline. |
| `$player_id` | `int` | Player (user) ID. |

#### `clanbite_players_get_display_website`

*Filter* · `inc/extensions/players/functions.php`

Allows filtering of the players display website, this can be bypassed by setting $suppress_filters param to true.

| Parameter | Type | Description |
| --- | --- | --- |
| `$website` | `string` | Profile website. |
| `$player_id` | `int` | Player (user) ID. |

#### `clanbite_players_image_size_choices_for_settings`

*Filter* · `inc/extensions/players/functions.php`

Filters selectable image size slugs for Clanbite avatar settings dropdowns.

| Parameter | Type | Description |
| --- | --- | --- |
| `$out` | `array<string, string>` | Slug => label. |

#### `clanbite_players_player_avatar_block_parts`

*Filter* · `inc/extensions/players/functions.php`

Filters layout parts for the player avatar block when preset is `large`.

| Parameter | Type | Description |
| --- | --- | --- |
| `$parts` | `array<string, string>` | { @type string $clip_inner Markup inside `.clanbite-player-avatar__clip`. @type string $after_clip Sibling markup after the clip (e.g. rank progress). @type string $avatar_extra_class Extra classes on `.clanbite-player-avatar` (space-separated). @type string $rank_overlay_html Rank icons outside the circular clip (sibling in `.clanbite-player-avatar__media`). } |
| `$user_id` | `int` | User ID. |
| `$args` | `array<string, mixed>` | Avatar args from the block. |
| `$display_inner` | `string` | Markup before splitting (same as default `clip_inner`). |

#### `clanbite_players_player_avatar_display_markup`

*Filter* · `inc/extensions/players/functions.php`

Filters inner player avatar markup after core builds the image or empty-state fragment.

| Parameter | Type | Description |
| --- | --- | --- |
| `$inner` | `string` | HTML. |
| `$user_id` | `int` | User ID. |
| `$args` | `array` | Context from the caller (e.g. `context`, `size`). |

#### `clanbite_players_player_avatar_img_attributes`

*Filter* · `inc/extensions/players/functions.php`

Filters attributes for the player avatar &lt;img&gt; element before the tag is built.

| Parameter | Type | Description |
| --- | --- | --- |
| `$attr` | `array` | Attribute name => value. Omitted or empty values are skipped (except alt may be empty). |
| `$user_id` | `int` | User ID. |
| `$args` | `array` | Arguments from `clanbite_players_get_player_avatar_img_html()`. |
| `$url` | `string` | Avatar URL. |

#### `clanbite_players_player_avatar_img_html`

*Filter* · `inc/extensions/players/functions.php`

Filters the player avatar &lt;img&gt; HTML fragment.

| Parameter | Type | Description |
| --- | --- | --- |
| `$html` | `string` | Markup. |
| `$user_id` | `int` | User ID. |
| `$args` | `array` | Arguments from `clanbite_players_get_player_avatar_img_html()`. |
| `$url` | `string` | Avatar URL. |

#### `clanbite_players_resolve_player_avatar_image_size`

*Filter* · `inc/extensions/players/functions.php`

Filters the resolved image size slug for a player avatar preset.

| Parameter | Type | Description |
| --- | --- | --- |
| `$size` | `string` | Sanitized size slug. |
| `$preset` | `string` | `large`, `medium`, or `small`. |
| `$raw` | `string` | Value from settings before sanitization. |
| `$fallback` | `string` | Default slug for this preset. |

#### `clanbite_players_sections`

*Filter* · `inc/extensions/players/class-extension-players-admin.php`

Filters the Players settings sections and fields shown in the admin.

| Parameter | Type | Description |
| --- | --- | --- |
| `$sections` | `array<string, array<string, mixed>>` | Sections keyed by id. |

#### `clanbite_players_settings_nav_items`

*Filter* · `inc/extensions/players/class-extension-players.php`

Filters the top-level player settings navigation.

Add entries keyed by slug, each with `label` and `description`.

| Parameter | Type | Description |
| --- | --- | --- |
| `$items` | `array<string, array<string, string>>` | Navigation items. |

*Also fired in:* `src/blocks/players/player-settings/render.php`

#### `clanbite_players_settings_nav_{nav_item}_sub_items`

*Filter* · `src/blocks/players/player-settings/render.php`

Filters the sub-navigation of one player settings section while rendering the settings block.

The dynamic portion is the top-level navigation slug.

| Parameter | Type | Description |
| --- | --- | --- |
| `$items` | `array<string, array<string, string>>` | Sub-navigation items keyed by slug (`label`, `description`). |

#### `clanbite_players_settings_nav_{nav}_sub_items`

*Filter* · `inc/extensions/players/class-extension-players.php`

Filters the sub-navigation of one player settings section.

The dynamic portion is the top-level navigation slug (for example `profile`, `account`).

| Parameter | Type | Description |
| --- | --- | --- |
| `$items` | `array<string, array<string, string>>` | Sub-navigation items keyed by slug (`label`, `description`). |

#### `clanbite_players_social_profile_field_definitions`

*Filter* · `inc/extensions/players/functions.php`

Filters social profile fields shown in player settings and registered as user meta.

| Parameter | Type | Description |
| --- | --- | --- |
| `$definitions` | `array<string, array{label: string, placeholder: string}>` | Slug => label and placeholder. |

#### `clanbite_players_social_profile_link_url`

*Filter* · `inc/extensions/players/functions.php`

Filters the final social profile link URL for a player.

| Parameter | Type | Description |
| --- | --- | --- |
| `$url` | `string` | URL or empty. |
| `$slug` | `string` | Field slug. |
| `$player_id` | `int` | User ID. |
| `$raw` | `string` | Raw meta value. |

#### `clanbite_players_social_profile_svg_icon`

*Filter* · `inc/extensions/players/functions.php`

Filters the inline SVG markup for a social profile icon.

| Parameter | Type | Description |
| --- | --- | --- |
| `$markup` | `string` | SVG HTML. |
| `$slug` | `string` | Field slug. |

#### `clanbite_redirect_author_archive_to_players_url`

*Filter* · `inc/extensions/players/class-extension-players.php`

Filter the 301 target when redirecting core author archives to player profile URLs.

| Parameter | Type | Description |
| --- | --- | --- |
| `$target` | `string` | Full URL. |
| `$user` | `\WP_User` | Queried author. |

#### `clanbite_save_player_settings`

*Action* · `inc/extensions/players/class-extension-players.php`

Fires after player settings POST data and uploads are sanitized.

| Parameter | Type | Description |
| --- | --- | --- |
| `$filtered_data` | `array<string, mixed>` | Mutable copy from the filter above (defaults to fully sanitized POST). |
| `$data` | `array<string, mixed>` | Same sanitized POST snapshot passed to the filter (immutable reference for handlers). |
| `$files` | `array<string, array<string, mixed>>` | Whitelisted upload fields only (`profile_avatar`, `profile_cover`). |
| `$user_id` | `int` | Profile owner user ID. |

#### `clanbite_save_player_settings_errors`

*Filter* · `inc/extensions/players/class-extension-players.php`

Filters the field errors reported after a player settings save.

Keys are input names (for example `account_first_name`); a non-empty array fails the request.

| Parameter | Type | Description |
| --- | --- | --- |
| `$errors` | `array<string, string>` | Error messages keyed by input name. |

#### `clanbite_save_player_settings_filtered_data`

*Filter* · `inc/extensions/players/class-extension-players.php`

Filter sanitized player settings POST data before save handlers run.

| Parameter | Type | Description |
| --- | --- | --- |
| `$data` | `array<string, mixed>` | Sanitized field map (see `clanbite_sanitize_player_settings_post_array()`). |
| `$user_id` | `int` | Profile owner user ID. |

#### `clanbite_save_player_settings_save_status`

*Filter* · `inc/extensions/players/class-extension-players.php`

Filters whether a player settings save is considered successful.

Handlers hooked to `clanbite_save_player_settings` return false here to fail the request.

| Parameter | Type | Description |
| --- | --- | --- |
| `$saved` | `bool` | Whether the save succeeded (default true). |

#### `clanbite_should_enqueue_player_settings_frontend_assets`

*Filter* · `inc/extensions/players/class-extension-players.php`

Whether to enqueue the inline script that defines `CLANBITEPLAYERSETTINGS`.

Return true if a custom template or block needs REST/ajax nonces outside the
default routes (player settings URL, author profile, or singular posts that
contain the player-settings block).

| Parameter | Type | Description |
| --- | --- | --- |
| `$enqueue` | `bool` | Default decision from core heuristics. |

#### `clanbite_user_nav_guest_links`

*Filter* · `inc/extensions/players/functions.php`

Filter guest navigation links.

Allows third-party plugins to add, remove, or modify links
shown to non-logged-in users.

| Parameter | Type | Description |
| --- | --- | --- |
| `$links` | `array` | Array of guest links. |

#### `clanbite_user_nav_menu_items`

*Filter* · `inc/extensions/players/functions.php`

Filter user navigation menu items.

Allows third-party plugins to add, remove, or modify menu items
in the user navigation dropdown.

| Parameter | Type | Description |
| --- | --- | --- |
| `$items` | `array` | Array of menu items. |
| `$user_id` | `int` | Current user ID. |

## Profile sub-pages and groups

#### `clanbite_group_profile_nav_context`

*Filter* · `inc/groups/functions.php`

Virtual group profile context when not on a singular `cp_group` post.

| Parameter | Type | Description |
| --- | --- | --- |
| `$context` | `array<string, mixed>\|null` | Context or null. |

#### `clanbite_group_subpages`

*Filter* · `inc/profile-subpages.php`

Filters the registered profile sub-pages (tabs) for one context.

The hook name depends on the context: `clanbite_player_subpages`, `clanbite_team_subpages` or
`clanbite_group_subpages` (extensions can add contexts through `clanbite_profile_subpage_registry_map`).
Prefer `clanbite_register_profile_subpage()` and its player/team/group wrappers for adding a tab.

| Parameter | Type | Description |
| --- | --- | --- |
| `$registry` | `array<string, array<string, mixed>>` | Registered sub-pages keyed by slug. Each entry has `label`, `template_id`, `default_blocks`, `capability` and `position`. |

#### `clanbite_groups_avatar_shape`

*Filter* · `inc/groups/functions.php`

Filter the group avatar shape.

| Parameter | Type | Description |
| --- | --- | --- |
| `$shape` | `string` | Avatar shape. |

#### `clanbite_groups_manage_url`

*Filter* · `inc/groups/functions.php`

Filter the group “settings” / manage URL used in group profile navigation.

| Parameter | Type | Description |
| --- | --- | --- |
| `$url` | `string` | URL or empty string. |
| `$group_id` | `int` | Group ID. |

#### `clanbite_groups_user_can_manage`

*Filter* · `inc/groups/functions.php`

Filter whether the user may open group settings from the profile nav.

| Parameter | Type | Description |
| --- | --- | --- |
| `$can` | `bool` | Whether the link should show. Default false. |
| `$group_id` | `int` | Group ID. |
| `$user_id` | `int` | User ID. |

#### `clanbite_groups_user_is_member`

*Filter* · `inc/groups/functions.php`

Filter whether a user counts as a group member for core features (events, etc.).

| Parameter | Type | Description |
| --- | --- | --- |
| `$is_member` | `bool` | Whether the user is a member. |
| `$group_id` | `int` | Group ID. |
| `$user_id` | `int` | User ID. |

#### `clanbite_player_subpages`

*Filter* · `inc/profile-subpages.php`

Filters the registered profile sub-pages (tabs) for one context.

The hook name depends on the context: `clanbite_player_subpages`, `clanbite_team_subpages` or
`clanbite_group_subpages` (extensions can add contexts through `clanbite_profile_subpage_registry_map`).
Prefer `clanbite_register_profile_subpage()` and its player/team/group wrappers for adding a tab.

| Parameter | Type | Description |
| --- | --- | --- |
| `$registry` | `array<string, array<string, mixed>>` | Registered sub-pages keyed by slug. Each entry has `label`, `template_id`, `default_blocks`, `capability` and `position`. |

#### `clanbite_profile_subpage_nav_visible`

*Filter* · `inc/profile-subpages.php`

Whether a subpage tab is shown in profile navigation for this viewer and subject.

For `player` context, the default is false when the viewer is not the profile owner (owner-only tabs).
Return true from this filter to expose a tab to other visitors (public subpage).

| Parameter | Type | Description |
| --- | --- | --- |
| `$visible` | `bool` | Default after capability and owner checks. |
| `$context` | `string` | Context slug. |
| `$slug` | `string` | Subpage slug. |
| `$object_id` | `int` | Subject id (meaning per context). |
| `$config` | `array` | Subpage config. |

#### `clanbite_profile_subpage_registry_map`

*Filter* · `inc/profile-subpages.php`

Register additional profile subpage contexts (third-party entity profiles).

| Parameter | Type | Description |
| --- | --- | --- |
| `$map` | `array<string, array{globals_key: string, filter: string, template_prefix: string}>` | Context slug => config. |

#### `clanbite_profile_subpages_nav_enabled`

*Filter* · `inc/profile-subpages.php`

Whether profile subpage navigation is enabled for a core context.

| Parameter | Type | Description |
| --- | --- | --- |
| `$enabled` | `bool` | Default true. |
| `$context` | `string` | Context slug (`player`, `team`, `group`). |

#### `clanbite_profile_subpages_nav_enabled_for_unknown_context`

*Filter* · `inc/profile-subpages.php`

Whether profile subpage navigation is enabled for a custom context.

| Parameter | Type | Description |
| --- | --- | --- |
| `$enabled` | `bool` | Default true. |
| `$context` | `string` | Context slug. |

#### `clanbite_team_subpages`

*Filter* · `inc/profile-subpages.php`

Filters the registered profile sub-pages (tabs) for one context.

The hook name depends on the context: `clanbite_player_subpages`, `clanbite_team_subpages` or
`clanbite_group_subpages` (extensions can add contexts through `clanbite_profile_subpage_registry_map`).
Prefer `clanbite_register_profile_subpage()` and its player/team/group wrappers for adding a tab.

| Parameter | Type | Description |
| --- | --- | --- |
| `$registry` | `array<string, array<string, mixed>>` | Registered sub-pages keyed by slug. Each entry has `label`, `template_id`, `default_blocks`, `capability` and `position`. |

## Teams

#### `clanbite_after_team_create_form`

*Action* · `src/blocks/teams/team-create-form/render.php`

Fires after the create-team form markup.

_No arguments._

#### `clanbite_before_team_create_form`

*Action* · `src/blocks/teams/team-create-form/render.php`

Fires before the create-team form markup.

_No arguments._

#### `clanbite_before_team_invite`

*Action* · `inc/extensions/teams/class-extension-teams.php`

Fires before a team invite notification is sent.

| Parameter | Type | Description |
| --- | --- | --- |
| `$team_id` | `int` | Team ID. |
| `$user_id` | `int` | User being invited. |
| `$inviter_id` | `int` | User sending the invite. |

#### `clanbite_can_user_join_team`

*Filter* · `inc/extensions/teams/class-extension-teams.php`

Filter team join permission.

| Parameter | Type | Description |
| --- | --- | --- |
| `$can_join` | `bool` | Whether user can join. |
| `$team_id` | `int` | Team post ID. |
| `$user_id` | `int` | User ID. |
| `$options` | `array` | Team options. |
| `$extension` | `Teams` | Teams extension instance. |

#### `clanbite_create_team_post_status`

*Filter* · `inc/extensions/teams/class-extension-teams.php`

Filters the post status of a newly created team (for example `pending` to require moderation).

| Parameter | Type | Description |
| --- | --- | --- |
| `$post_status` | `string` | Post status (default `publish`). |
| `$user_id` | `int` | Creating user ID. |

#### `clanbite_enable_team_create_invites_step`

*Filter* · `src/blocks/teams/team-create-form/render.php`

Filters whether the Player invites step is part of the create-team wizard.

| Parameter | Type | Description |
| --- | --- | --- |
| `$enabled` | `bool` | Default true. |

#### `clanbite_load_team_action_template`

*Filter* · `inc/extensions/teams/class-extension-teams.php`

Filter template for custom team actions (when not handled above).

| Parameter | Type | Description |
| --- | --- | --- |
| `$template` | `string` | Current template path. |
| `$team_action` | `string` | Action slug. |
| `$team_id` | `int` | Team post ID. |
| `$extension` | `Teams` | Teams extension. |

#### `clanbite_load_team_create_template`

*Filter* · `inc/extensions/teams/class-extension-teams.php`

Filters the template used for the create-team route.

| Parameter | Type | Description |
| --- | --- | --- |
| `$template` | `string` | Resolved template path. |

#### `clanbite_load_team_manage_template`

*Filter* · `inc/extensions/teams/class-extension-teams.php`

Filters the template used for the team management route.

| Parameter | Type | Description |
| --- | --- | --- |
| `$template` | `string` | Resolved template path. |
| `$team_id` | `int` | Team post ID. |
| `$extension` | `\Kernowdev\Clanbite\Extensions\Teams` | Teams extension. |

#### `clanbite_pre_insert_team_slug`

*Filter* · `inc/extensions/teams/class-extension-teams.php`

Filters the slug of a team that is about to be created.

| Parameter | Type | Description |
| --- | --- | --- |
| `$slug` | `string` | Sanitized slug derived from the name. |
| `$team_name` | `string` | Submitted team name. |
| `$user_id` | `int` | Creating user ID. |

#### `clanbite_team_action_dispatch`

*Action* · `inc/extensions/teams/class-extension-teams.php`

Fires when a team front action URL is resolved (BuddyPress-style dispatch).

Use to authorize, redirect, or enqueue for custom actions from
`clanbite_team_front_action_rewrite_slugs`. Return a template path
from a callback on `clanbite_load_team_action_template` if needed.

| Parameter | Type | Description |
| --- | --- | --- |
| `$team_action` | `string` | Action slug (e.g. manage). |
| `$team_id` | `int` | Team post ID. |
| `$extension` | `Teams` | Teams extension. |

#### `clanbite_team_action_url`

*Filter* · `inc/extensions/teams/class-extension-teams.php`

Filter URL for a team front action.

| Parameter | Type | Description |
| --- | --- | --- |
| `$url` | `string` | Full URL. |
| `$team_id` | `int` | Team post ID. |
| `$action` | `string` | Action slug. |
| `$extension` | `Teams` | Teams extension. |

#### `clanbite_team_before_delete`

*Action* · `inc/extensions/teams/class-extension-teams.php`

Fires before a team is permanently deleted from the front-end manage UI.

| Parameter | Type | Description |
| --- | --- | --- |
| `$team_id` | `int` | Team post ID. |
| `$extension` | `Teams` | Teams extension instance. |

#### `clanbite_team_can_ban_players`

*Filter* · `inc/extensions/teams/class-extension-teams.php`

Filters whether players can be banned from a team (defaults to the team's `allow_ban_players` option).

| Parameter | Type | Description |
| --- | --- | --- |
| `$allowed` | `bool` | Whether banning is allowed. |
| `$team_id` | `int` | Team post ID. |
| `$options` | `array<string,                        mixed>` | Team options. |
| `$extension` | `\Kernowdev\Clanbite\Extensions\Teams` | Teams extension. |

#### `clanbite_team_can_edit_frontend`

*Filter* · `inc/extensions/teams/class-extension-teams.php`

Filters whether a team may be edited on the front end (defaults to the team's `allow_frontend_edit` option).

| Parameter | Type | Description |
| --- | --- | --- |
| `$allowed` | `bool` | Whether front-end editing is allowed. |
| `$team_id` | `int` | Team post ID. |
| `$options` | `array<string,                        mixed>` | Team options. |
| `$extension` | `\Kernowdev\Clanbite\Extensions\Teams` | Teams extension. |

#### `clanbite_team_can_invite_players`

*Filter* · `inc/extensions/teams/class-extension-teams.php`

Filters whether players can be invited to a team (defaults to the team's `allow_invites` option).

| Parameter | Type | Description |
| --- | --- | --- |
| `$allowed` | `bool` | Whether inviting is allowed. |
| `$team_id` | `int` | Team post ID. |
| `$options` | `array<string,                        mixed>` | Team options. |
| `$extension` | `\Kernowdev\Clanbite\Extensions\Teams` | Teams extension. |

#### `clanbite_team_challenge_accepted`

*Action* · `inc/extensions/teams/class-team-challenges.php`

Fires after a team challenge is accepted and the match exists.

| Parameter | Type | Description |
| --- | --- | --- |
| `$challenge_id` | `int` | Challenge post ID. |
| `$match_id` | `int` | New `cp_match` ID. |
| `$challenged_team_id` | `int` | Home team ID. |

#### `clanbite_team_challenge_button_visible`

*Filter* · `src/blocks/teams/team-challenge-button/render.php`

Whether to output the team challenge UI.

| Parameter | Type | Description |
| --- | --- | --- |
| `$visible` | `bool` | Default visibility. |
| `$team_id` | `int` | Team post ID. |
| `$block` | `WP_Block` | Block instance. |

#### `clanbite_team_challenge_created`

*Action* · `inc/extensions/teams/class-team-challenges.php`

Fires after a team challenge is stored and notifications are queued.

| Parameter | Type | Description |
| --- | --- | --- |
| `$challenge_id` | `int` | Challenge post ID (`clanbite_team_challenge`). |
| `$challenged_team_id` | `int` | Challenged `cp_team` ID. |

#### `clanbite_team_challenge_declined`

*Action* · `inc/extensions/teams/class-team-challenges.php`

Fires after a pending team challenge is declined by an authorised team representative.

| Parameter | Type | Description |
| --- | --- | --- |
| `$challenge_id` | `int` | Challenge post ID (`clanbite_team_challenge`). |
| `$challenged_team_id` | `int` | Challenged team ID. |
| `$user_id` | `int` | Acting user ID. |

#### `clanbite_team_challenge_notify_user_ids`

*Filter* · `inc/extensions/teams/class-team-challenges.php`

Filter who receives team challenge notifications for a team.

| Parameter | Type | Description |
| --- | --- | --- |
| `$out` | `array` | User IDs. |
| `$team_id` | `int` | Team ID. |

#### `clanbite_team_challenge_rate_limit`

*Filter* · `inc/extensions/teams/class-team-challenges.php`

Filters the per-IP hourly request limit for guest challenge endpoints.

| Parameter | Type | Description |
| --- | --- | --- |
| `$limit` | `int` | Maximum requests per hour per IP (default 20 for `challenge`, 30 for `remote_team`). |
| `$bucket` | `string` | Counter bucket: `challenge` or `remote_team`. |

#### `clanbite_team_challenge_remote_allow_http`

*Filter* · `inc/extensions/teams/class-team-challenges.php`

Filters whether plain-http remote sites may be contacted for challenges.

Defaults to true only on `local` / `development` environments; production requires https.

| Parameter | Type | Description |
| --- | --- | --- |
| `$allow_http` | `bool` | Whether http origins are allowed. |
| `$origin` | `string` | Resolved remote origin (scheme + host + port). |

#### `clanbite_team_challenge_rest_response`

*Filter* · `inc/extensions/teams/class-team-challenges.php`

Filter: `clanbite_team_challenge_rest_response` — JSON payload for challenge REST endpoints before encoding.

| Parameter | Type | Description |
| --- | --- | --- |
| `$payload` | `array<string, mixed>` | Body array. |
| `$route_key` | `string` | `remote_team`, `upload_media`, or `create_challenge`. |
| `$request` | `WP_REST_Request` | Request instance. |

#### `clanbite_team_challenge_upload_max_bytes`

*Filter* · `inc/extensions/teams/class-team-challenges.php`

Filters the maximum size of a guest challenge logo upload.

| Parameter | Type | Description |
| --- | --- | --- |
| `$max_bytes` | `int` | Maximum size in bytes (default 2 MB). |
| `$team_id` | `int` | Challenged team post ID. |
| `$request` | `WP_REST_Request` | Upload request. |

#### `clanbite_team_create_form_step`

*Action* · `src/blocks/teams/team-create-form/render.php`

Render a custom create-team step.

| Parameter | Type | Description |
| --- | --- | --- |
| `$step_key` | `string` | Step key. |
| `$step` | `array` | Step config. |
| `$step_number` | `int` | Step number. |

#### `clanbite_team_create_form_step_{step_key}`

*Action* · `src/blocks/teams/team-create-form/render.php`

Fires inside one step of the create-team wizard.

The dynamic portion is the step key (for example `basic_details`, `invites`, `matches`). Step-specific variant of `clanbite_team_create_form_step`.

| Parameter | Type | Description |
| --- | --- | --- |
| `$step` | `array<string, mixed>` | Step configuration. |
| `$step_number` | `int` | 1-based step number. |

#### `clanbite_team_create_form_steps`

*Filter* · `src/blocks/teams/team-create-form/render.php`

Filter create-team form steps.

Third parties can add custom steps by appending a new keyed array item.
Each step may include:
- label (string) — default in-step heading fallback.
- title (string) — short tab title; falls back to label.
- description (string) — short tab subtitle (optional).

| Parameter | Type | Description |
| --- | --- | --- |
| `$steps` | `array` | Step map. |

#### `clanbite_team_create_invite_tokens`

*Filter* · `inc/extensions/teams/class-extension-teams.php`

Filter pending invite tokens parsed from create-team flow.

| Parameter | Type | Description |
| --- | --- | --- |
| `$invite_tokens` | `array` | Parsed invite values. |
| `$new_team_id` | `int` | Team post ID. |
| `$user_id` | `int` | Creator ID. |

#### `clanbite_team_create_url`

*Filter* · `inc/extensions/teams/class-extension-teams.php`

Filters the URL of the create-team page.

| Parameter | Type | Description |
| --- | --- | --- |
| `$url` | `string` | Create-team URL. |
| `$extension` | `\Kernowdev\Clanbite\Extensions\Teams` | Teams extension. |

#### `clanbite_team_created`

*Action* · `inc/extensions/teams/class-extension-teams.php`

Fires after a team is created via the block-based create team form.

| Parameter | Type | Description |
| --- | --- | --- |
| `$new_team_id` | `int` | Team post ID. |
| `$user_id` | `int` | Creator user ID. |
| `$request` | `array` | Sanitized POST-shaped map (see `clanbite_sanitize_team_create_post_array()`), including custom form fields. |

#### `clanbite_team_data_store`

*Filter* · `inc/extensions/teams/class-extension-teams.php`

Filter team persistence implementation.

| Parameter | Type | Description |
| --- | --- | --- |
| `$store` | `Team_Data_Store` | Default CPT-backed store. |
| `$extension` | `Teams` | Teams extension instance. |

#### `clanbite_team_deleted`

*Action* · `inc/extensions/teams/class-extension-teams.php`

Fires after a team has been permanently deleted from the front-end manage UI.

| Parameter | Type | Description |
| --- | --- | --- |
| `$team_id` | `int` | Team post ID (no longer exists as a post). |
| `$user_id` | `int` | User who performed the deletion. |
| `$extension` | `Teams` | Teams extension instance. |

#### `clanbite_team_front_action_rewrite_slugs`

*Filter* · `inc/extensions/teams/class-extension-teams.php`

Register additional team front actions (BuddyPress-style URLs).

Each key becomes a rewrite segment: teams/{team_slug}/{key}/
Handle routing in `clanbite_team_action_dispatch` or template_include.

| Parameter | Type | Description |
| --- | --- | --- |
| `$actions` | `array` | Slug => label. |
| `$extension` | `Teams` | Teams extension. |

#### `clanbite_team_invite_accepted`

*Action* · `inc/extensions/teams/class-extension-teams.php`

Fires after a user accepts a team invite.

| Parameter | Type | Description |
| --- | --- | --- |
| `$team_id` | `int` | Team ID. |
| `$user_id` | `int` | User who accepted. |
| `$notification` | `object` | The notification object. |

#### `clanbite_team_invite_declined`

*Action* · `inc/extensions/teams/class-extension-teams.php`

Fires after a user declines a team invite.

| Parameter | Type | Description |
| --- | --- | --- |
| `$team_id` | `int` | Team ID. |
| `$user_id` | `int` | User who declined. |
| `$notification` | `object` | The notification object. |

#### `clanbite_team_invite_sent`

*Action* · `inc/extensions/teams/class-extension-teams.php`

Fires after a team invite notification is sent.

| Parameter | Type | Description |
| --- | --- | --- |
| `$notification_id` | `int` | Notification ID. |
| `$team_id` | `int` | Team ID. |
| `$user_id` | `int` | User being invited. |
| `$inviter_id` | `int` | User sending the invite. |

#### `clanbite_team_join_modes`

*Filter* · `inc/extensions/teams/class-extension-teams.php`

Filter available team join modes.

| Parameter | Type | Description |
| --- | --- | --- |
| `$modes` | `array` | Team join modes. |
| `$extension` | `Teams` | Teams extension instance. |

#### `clanbite_team_manage_after_delete_redirect`

*Filter* · `inc/extensions/teams/class-extension-teams.php`

Redirect URL after a successful front-end team deletion.

| Parameter | Type | Description |
| --- | --- | --- |
| `$url` | `string` | Destination URL. |
| `$team_id` | `int` | Deleted team post ID. |
| `$extension` | `Teams` | Teams extension instance. |

#### `clanbite_team_manage_after_section`

*Action* · `inc/extensions/teams/class-extension-teams.php`

Fires after a manage form section’s inner markup.

| Parameter | Type | Description |
| --- | --- | --- |
| `$section_id` | `string` | Section key (e.g. profile, team_options). |
| `$team_id` | `int` | Team post ID. |
| `$extension` | `Teams` | Teams extension instance. |

#### `clanbite_team_manage_before_save`

*Action* · `inc/extensions/teams/class-extension-teams.php`

Fires after the manage form has populated the team entity and before it is saved.

| Parameter | Type | Description |
| --- | --- | --- |
| `$team_id` | `int` | Team post ID. |
| `$team_entity` | `Team` | Mutable team entity. |
| `$extension` | `Teams` | Teams extension instance. |

#### `clanbite_team_manage_form_after`

*Action* · `inc/extensions/teams/class-extension-teams.php`

Fires after the team manage form closes.

| Parameter | Type | Description |
| --- | --- | --- |
| `$team_id` | `int` | Team post ID. |
| `$extension` | `Teams` | Teams extension instance. |

#### `clanbite_team_manage_form_after_sections`

*Action* · `inc/extensions/teams/class-extension-teams.php`

Fires after all default/filtered sections, before navigation and submit.

| Parameter | Type | Description |
| --- | --- | --- |
| `$team_id` | `int` | Team post ID. |
| `$extension` | `Teams` | Teams extension instance. |

#### `clanbite_team_manage_form_before`

*Action* · `inc/extensions/teams/class-extension-teams.php`

Fires before the team manage form markup (after capability checks).

| Parameter | Type | Description |
| --- | --- | --- |
| `$team_id` | `int` | Team post ID. |
| `$extension` | `Teams` | Teams extension instance. |

#### `clanbite_team_manage_form_sections`

*Filter* · `inc/extensions/teams/class-extension-teams.php`

Filter registered sections for the front-end team manage form.

Add, remove, or reorder sections. Third-party sections should use a unique string key.

| Parameter | Type | Description |
| --- | --- | --- |
| `$sections` | `array<string, array{title: string, priority: int, callback: callable}>` | Section key => config. |
| `$team_id` | `int` | Team post ID. |
| `$extension` | `Teams` | Teams extension instance. |

#### `clanbite_team_manage_form_tabs`

*Filter* · `inc/extensions/teams/class-extension-teams.php`

Filter tab definitions for the manage form (order and labels).

Each item: `id` (section id), `title`, `description` (optional).

| Parameter | Type | Description |
| --- | --- | --- |
| `$tabs` | `array<int, array{id: string, title: string, description: string}>` |  |
| `$team_id` | `int` |  |
| `$extension` | `Teams` |  |

#### `clanbite_team_manage_should_render_delete_form`

*Filter* · `inc/extensions/teams/class-extension-teams.php`

Filters whether the delete-team form is shown on the front-end management screen.

| Parameter | Type | Description |
| --- | --- | --- |
| `$show` | `bool` | Whether to show the form (defaults to the current user's delete capability). |
| `$team_id` | `int` | Team post ID. |
| `$extension` | `\Kernowdev\Clanbite\Extensions\Teams` | Teams extension. |

#### `clanbite_team_manage_should_render_section`

*Filter* · `inc/extensions/teams/class-extension-teams.php`

Filters whether a section of the front-end team management screen is rendered.

| Parameter | Type | Description |
| --- | --- | --- |
| `$render` | `bool` | Whether to render the section (default true). |
| `$section_id` | `string` | Section identifier. |
| `$team_id` | `int` | Team post ID. |
| `$extension` | `\Kernowdev\Clanbite\Extensions\Teams` | Teams extension. |

#### `clanbite_team_manage_url`

*Filter* · `inc/extensions/teams/class-extension-teams.php`

Filters the front-end management URL of a team.

| Parameter | Type | Description |
| --- | --- | --- |
| `$url` | `string` | Management URL. |
| `$team_id` | `int` | Team post ID. |
| `$extension` | `\Kernowdev\Clanbite\Extensions\Teams` | Teams extension. |

#### `clanbite_team_media_ajax_saved`

*Action* · `inc/extensions/teams/class-extension-teams.php`

Fires after team avatar/cover media was saved via Ajax from front-end blocks.

| Parameter | Type | Description |
| --- | --- | --- |
| `$team_id` | `int` | Team post ID. |
| `$extension` | `Teams` | Teams extension instance. |

#### `clanbite_team_member_count`

*Filter* · `inc/extensions/teams/class-extension-teams.php`

Filters the number of members counted for a team.

| Parameter | Type | Description |
| --- | --- | --- |
| `$count` | `int` | Member count. |
| `$team_id` | `int` | Team post ID. |
| `$exclude_banned` | `bool` | Whether banned members were excluded. |
| `$extension` | `\Kernowdev\Clanbite\Extensions\Teams` | Teams extension. |

#### `clanbite_team_member_roles_map`

*Filter* · `inc/extensions/teams/class-extension-teams.php`

Filter resolved team roster map.

| Parameter | Type | Description |
| --- | --- | --- |
| `$out` | `array` | User ID => role. |
| `$team_id` | `int` | Team post ID. |
| `$extension` | `Teams` | Teams extension. |

#### `clanbite_team_options`

*Filter* · `inc/extensions/teams/class-extension-teams.php`

Filter resolved team options.

| Parameter | Type | Description |
| --- | --- | --- |
| `$options` | `array` | Team options map. |
| `$team_id` | `int` | Team post ID. |
| `$extension` | `Teams` | Teams extension instance. |

#### `clanbite_team_options_updated`

*Action* · `inc/extensions/teams/class-extension-teams.php`

Fires after team options are updated.

| Parameter | Type | Description |
| --- | --- | --- |
| `$team_id` | `int` | Team post ID. |
| `$sanitized` | `array` | Sanitized options map. |
| `$extension` | `Teams` | Teams extension instance. |

#### `clanbite_team_profile_home_label`

*Filter* · `src/blocks/teams/profile-nav/render.php`

Filter the label for the team profile home link.

| Parameter | Type | Description |
| --- | --- | --- |
| `$label` | `string` | Default label. |
| `$team_id` | `int` | Team post ID. |

#### `clanbite_team_profile_nav_show_settings_link`

*Filter* · `src/blocks/teams/profile-nav/render.php`

Filter: `clanbite_team_profile_nav_show_settings_link` — show the Settings link (team manage URL).

Default: team admins/editors (and site teams admins), matching `clanbite_teams_user_can_manage`.

| Parameter | Type | Description |
| --- | --- | --- |
| `$show` | `bool` | Whether to show the link. |
| `$team_id` | `int` | Team post ID. |

#### `clanbite_team_profile_settings_nav_label`

*Filter* · `src/blocks/teams/profile-nav/render.php`

Filter: `clanbite_team_profile_settings_nav_label` — label for the Settings item.

| Parameter | Type | Description |
| --- | --- | --- |
| `$label` | `string` | Default label. |
| `$team_id` | `int` | Team post ID. |

#### `clanbite_team_profile_url_for_slug`

*Filter* · `inc/extensions/teams/class-extension-teams.php`

Filter the team profile URL built from a slug (no virtual action segment).

| Parameter | Type | Description |
| --- | --- | --- |
| `$url` | `string` | Full URL. |
| `$slug` | `string` | Sanitized `post_name`. |
| `$extension` | `Teams` | Teams extension instance. |

#### `clanbite_team_roster_updated`

*Action* · `inc/extensions/teams/class-extension-teams.php`

Fires after a team's roster has been saved.

| Parameter | Type | Description |
| --- | --- | --- |
| `$team_id` | `int` | Team post ID. |
| `$roster` | `array<int,                           string>` | Saved roster: user ID => role slug. |
| `$extension` | `\Kernowdev\Clanbite\Extensions\Teams` | Teams extension. |

#### `clanbite_team_roster_user_ids`

*Filter* · `inc/extensions/teams/functions.php`

Filter roster user IDs before rendering a team player loop.

| Parameter | Type | Description |
| --- | --- | --- |
| `$ids` | `list<int>` | Ordered user IDs. |
| `$team_id` | `int` | Team post ID. |
| `$exclude_banned` | `bool` | Whether banned members were omitted. |

#### `clanbite_teams_after_create_redirect`

*Filter* · `inc/extensions/teams/class-extension-teams.php`

Filter redirect URL after successful team creation.

Default: the new team’s permalink (`/teams/{slug}/`), not the archive with query args.

| Parameter | Type | Description |
| --- | --- | --- |
| `$redirect` | `string` | Redirect URL. |
| `$team_id` | `int` | New team post ID. |
| `$extension` | `Teams` | Teams extension instance. |

#### `clanbite_teams_auto_roster_synced`

*Action* · `inc/extensions/teams/class-extension-teams.php`

Fires after default team roster memberships are applied for a user.

| Parameter | Type | Description |
| --- | --- | --- |
| `$user_id` | `int` | User ID. |

#### `clanbite_teams_avatar_shape`

*Filter* · `inc/extensions/teams/functions.php`

Filter the team avatar shape.

| Parameter | Type | Description |
| --- | --- | --- |
| `$shape` | `string` | Avatar shape. |

#### `clanbite_teams_default_avatar_url`

*Filter* · `inc/extensions/teams/functions.php`

Filter default team avatar URL when the team has no custom avatar.

| Parameter | Type | Description |
| --- | --- | --- |
| `$url` | `string` | Resolved URL. |
| `$team_id` | `int` | Team post ID (0 if unknown). |

#### `clanbite_teams_default_cover_url`

*Filter* · `inc/extensions/teams/functions.php`

Filter default team cover URL when the team has no custom cover.

| Parameter | Type | Description |
| --- | --- | --- |
| `$url` | `string` | Resolved URL. |
| `$team_id` | `int` | Team post ID (0 if unknown). |

#### `clanbite_teams_defaults`

*Filter* · `inc/extensions/teams/class-extension-teams-admin.php`

Filters the default Teams settings.

| Parameter | Type | Description |
| --- | --- | --- |
| `$defaults` | `array<string, mixed>` | Defaults keyed by setting. |

#### `clanbite_teams_get_display_team_avatar`

*Filter* · `inc/extensions/teams/functions.php`

Filters the resolved team avatar image URL.

| Parameter | Type | Description |
| --- | --- | --- |
| `$url` | `string` | URL. |
| `$team_id` | `int` | Team post ID. |
| `$size` | `string\|array` | Size used for attachment resolution. |
| `$context` | `string` | Surface key. |
| `$avatar_preset` | `string` | `large`, `medium`, `small`, or empty when an explicit size was used. |

#### `clanbite_teams_global_auto_join_team_ids`

*Filter* · `inc/extensions/teams/functions.php`

Filter default auto-join team post IDs.

| Parameter | Type | Description |
| --- | --- | --- |
| `$ids` | `int[]` | Sanitized team post IDs. |

#### `clanbite_teams_mode`

*Filter* · `inc/extensions/teams/class-extension-teams.php`

Filter resolved teams mode.

| Parameter | Type | Description |
| --- | --- | --- |
| `$team_mode` | `string` | Resolved teams mode. |
| `$extension` | `Teams` | Teams extension instance. |

#### `clanbite_teams_mode_loaded`

*Action* · `inc/extensions/teams/class-extension-teams.php`

Fires after teams mode has been resolved.

| Parameter | Type | Description |
| --- | --- | --- |
| `$team_mode` | `string` | Team mode slug. |
| `$extension` | `Teams` | Teams extension instance. |

#### `clanbite_teams_mode_options`

*Filter* · `inc/extensions/teams/class-extension-teams-admin.php`

Filter teams mode options.

| Parameter | Type | Description |
| --- | --- | --- |
| `$options` | `array` | Mode options keyed by mode slug. |
| `$admin` | `Admin` | Teams admin settings instance. |

#### `clanbite_teams_mode_{team_mode}`

*Action* · `inc/extensions/teams/class-extension-teams.php`

Dynamic teams mode hook for mode-specific boot behavior.

| Parameter | Type | Description |
| --- | --- | --- |
| `$extension` | `Teams` | Teams extension instance. |

#### `clanbite_teams_player_team_membership_options`

*Filter* · `inc/extensions/teams/class-extension-teams-admin.php`

Filter global player team membership options (Teams settings UI).

| Parameter | Type | Description |
| --- | --- | --- |
| `$options` | `array` | Key-value map. |
| `$admin` | `Admin` | Teams admin instance. |

#### `clanbite_teams_resolve_team_avatar_image_size`

*Filter* · `inc/extensions/teams/functions.php`

Filters the resolved image size slug for a team avatar preset.

| Parameter | Type | Description |
| --- | --- | --- |
| `$size` | `string` | Sanitized size slug. |
| `$preset` | `string` | `large`, `medium`, or `small`. |
| `$raw` | `string` | Value from settings before sanitization. |
| `$fallback` | `string` | Default slug for this preset. |

#### `clanbite_teams_sections`

*Filter* · `inc/extensions/teams/class-extension-teams-admin.php`

Filters the Teams settings sections and fields shown in the admin.

| Parameter | Type | Description |
| --- | --- | --- |
| `$sections` | `array<string, array<string, mixed>>` | Sections keyed by id. |

#### `clanbite_teams_should_auto_roster_sync_user`

*Filter* · `inc/extensions/teams/class-extension-teams.php`

Whether to apply default team roster membership for this user.

| Parameter | Type | Description |
| --- | --- | --- |
| `$run` | `bool` | Default true. |
| `$user_id` | `int` | User ID. |

#### `clanbite_teams_site_admin_capability`

*Filter* · `inc/extensions/teams/class-extension-teams.php`

Primitive capability required to manage any team as a site administrator.

| Parameter | Type | Description |
| --- | --- | --- |
| `$cap` | `string` | Capability name. Default `manage_options`. |
| `$user_id` | `int` | User ID. |

#### `clanbite_user_accepts_team_invites`

*Filter* · `inc/extensions/teams/class-extension-teams.php`

Filter whether a user accepts team invites.

| Parameter | Type | Description |
| --- | --- | --- |
| `$accepts` | `bool` | Whether invites are accepted. |
| `$user_id` | `int` | User ID. |
| `$extension` | `Teams` | Teams extension. |

#### `clanbite_user_can_appear_in_team_invite_search`

*Filter* · `inc/extensions/teams/class-extension-teams.php`

Filter final invite-search visibility for a user.

| Parameter | Type | Description |
| --- | --- | --- |
| `$can` | `bool` | Whether to include the user. |
| `$user_id` | `int` | User ID. |
| `$extension` | `Teams` | Teams extension. |

#### `clanbite_user_can_delete_team_on_frontend`

*Filter* · `inc/extensions/teams/class-extension-teams.php`

Filter whether the user may delete a team from the front-end manage UI.

| Parameter | Type | Description |
| --- | --- | --- |
| `$can` | `bool` | Whether deletion is allowed. |
| `$team_id` | `int` | Team post ID. |
| `$user_id` | `int` | User ID. |
| `$extension` | `Teams` | Teams extension instance. |

#### `clanbite_user_managed_team_ids`

*Filter* · `inc/extensions/teams/class-extension-teams.php`

Filter resolved managed team IDs for a user.

| Parameter | Type | Description |
| --- | --- | --- |
| `$ids` | `array` | Unique team post IDs. |
| `$user_id` | `int` | User ID. |
| `$extension` | `Teams` | Teams extension instance. |

#### `clanbite_user_team_membership_count`

*Filter* · `inc/extensions/teams/class-extension-teams.php`

Filter how many team memberships count toward global limits.

| Parameter | Type | Description |
| --- | --- | --- |
| `$count` | `int` | Membership count. |
| `$user_id` | `int` | User ID. |
| `$extension` | `Teams` | Teams extension. |

#### `the_content`

*Filter* · `src/blocks/teams/team-description/render.php`

This filter is documented in wp-includes/post-template.php

_No arguments._

## JavaScript hooks

Registered with `wp.hooks.addFilter()` / `addAction()` on the front end or in the editor.

#### `clanbite.notifications.accessibleConfirm`

*Filter (JavaScript, `wp.hooks`)* · `src/blocks/notifications/notification-bell/view.js`

Filters the confirmation dialog shown before a notification action that asks for confirmation.

Return a Promise that resolves to `true` (confirmed) or `false` to replace the built-in accessible dialog;
return `null` to keep it.

| Parameter | Type | Description |
| --- | --- | --- |
| `custom` | `{null}` | Default `null` (use the built-in dialog). |
| `message` | `{string}` | Plain-text confirmation message from the server. |

#### `clanbite.notifications.polled`

*Action (JavaScript, `wp.hooks`)* · `src/blocks/notifications/notification-bell/view.js`

Fires after each successful notification poll (HTTP or WebSocket) so other UI can sync on the same cadence.

The Points companion also fires it with `transport: 'synthetic'` after it awards points for a button click.

| Parameter | Type | Description |
| --- | --- | --- |
| `detail` | `{{ transport: string, response: Object\|null, payload: Object\|null }}` | `transport` is `http`, `websocket` or `synthetic`; `response` is the poll REST JSON for `http`; `payload` is the message for `websocket`. |

#### `clanbite.notifications.received`

*Action (JavaScript, `wp.hooks`)* · `src/blocks/notifications/notification-bell/view.js`

Fires when a poll or WebSocket message delivers new notifications (not on polls with nothing new).

For a signal on every successful poll, use `clanbite.notifications.polled` instead.

| Parameter | Type | Description |
| --- | --- | --- |
| `notifications` | `{Array<Object>}` | New notification objects, newest first (empty when the Points companion mirrors a server-side change). |

#### `clanbite.notifications.showToast`

*Filter (JavaScript, `wp.hooks`)* · `src/blocks/notifications/notification-bell/view.js`

Filter to customize toast notification display.

_No arguments._

#### `clanbite.popup.shouldOpen`

*Filter (JavaScript, `wp.hooks`)* · `src/blocks/players/user-nav/view.js`

Filters whether a Clanbite popup may open. Return `false` to keep it closed.

| Parameter | Type | Description |
| --- | --- | --- |
| `allow` | `{boolean}` | Default `true`. |
| `detail` | `{{ name: string, root: Element }}` | `name` identifies the popup; `root` is its block element. |

*Also fired in:* `src/blocks/teams/team-challenge-button/view.js`

#### `clanbite.popup.toggled`

*Action (JavaScript, `wp.hooks`)* · `src/blocks/players/user-nav/view.js`

Fires whenever a Clanbite popup (user menu, notification bell, challenge dialog) opens or closes, however it was triggered.

| Parameter | Type | Description |
| --- | --- | --- |
| `detail` | `{{ name: string, open: boolean, root: Element }}` | `name` identifies the popup (`user-nav`, `notification-bell`, `team-challenge`); `root` is its block element. |

*Also fired in:* `src/blocks/teams/team-challenge-button/view.js`

#### `clanbite.teams.joinModes`

*Filter (JavaScript, `wp.hooks`)* · `assets/js/admin/team-options-editor.js`

Filters the join-mode options offered in the team editor's Membership panel.

| Parameter | Type | Description |
| --- | --- | --- |
| `joinModes` | `{Array<{label: string, value: string}>}` | Options (`open_join`, `join_with_permission`, `invite_only` by default). |
| `meta` | `{Object}` | Current team post meta. |

#### `clanbite.teams.optionControls`

*Filter (JavaScript, `wp.hooks`)* · `assets/js/admin/team-options-editor.js`

Filters the controls rendered in the team editor's Membership panel.

Append `wp.element` elements to add your own options; persist them with `setMetaValue()`.

| Parameter | Type | Description |
| --- | --- | --- |
| `controls` | `{Array}` | React elements for the panel. |
| `context` | `{{ meta: Object, setMetaValue: Function, patchMeta: Function, components: Object, element: Object }}` | Editor helpers. |
