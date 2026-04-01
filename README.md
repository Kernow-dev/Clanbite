# Clanspress

Clanspress is a community management plugin for gaming teams, clans, and competitive communities.

## Development Principles
- Built for extensibility first.
- Uses modern WordPress APIs and coding standards.
- Keeps extension lifecycle and data access explicit and testable.

## Static analysis and compatibility checks

From the plugin root (with Composer dev dependencies installed):

| Command | Purpose |
|--------|---------|
| `composer phpstan` | [PHPStan](https://phpstan.org/) at **level 5** (`phpstan.neon.dist`, `phpstan-bootstrap.php` defines `ABSPATH` for stubs). |
| `composer lint:php` | [PHPCS](https://github.com/squizlabs/PHP_CodeSniffer) with **PHPCompatibilityWP** only (`phpcs.xml.dist`). |

Copy `phpstan.neon.dist` to `phpstan.neon` for local overrides (the latter is gitignored).

**Note:** Full WordPress-Core style sniffs are not part of the default `lint:php` command yet, to avoid large formatting-only churn. You can still run PHPCBF against `WordPress-Core` locally if you want to normalize whitespace.

## WordPress admin (Clanspress menu)

The top-level **Clanspress** menu opens a **React** settings shell (`src/admin/index.js`, built to `assets/dist/clanspress-admin.js`).

- **Tabs:** **General** (core options, `clanspress_general_settings`), **Extensions** (enable/disable; runs uninstallers for removed slugs), then one tab per **root** extension that exposes settings via `Skeleton::get_settings_admin()`.
- **Deep links:** the active tab is stored in the query string as `?page=clanspress&tab=<id>` (e.g. `general`, `extensions`, `ext-cp_teams`). Invalid `tab` values fall back to the first tab and the URL is corrected. Save notices stay visible above the tab row when switching tabs.
- **Child extensions** (`parent_slug` set): their settings sections appear **inside the parent extension tab** (grouped with an `<h3>` heading), each group saves its own option row via REST.
- **CPT menus:** Custom post types registered by the plugin should use `'show_in_menu' => 'clanspress'` so list tables appear under this menu (e.g. **Teams** / `cp_team`).

Build after changing the admin UI:

```bash
npm run build:admin
```

Uses `webpack.config.cjs` (CopyPlugin removed so only the admin bundle is emitted into `assets/dist/`).

Block sources live under `src/blocks/` (matches, players, teams). The match post sidebar script lives under `src/cp-match-editor/src/`. Everything compiles into the plugin root `build/` (plus per-extension `blocks-manifest.php` files). From the plugin root:

```bash
npm ci
npm run build:production   # admin + blocks + manifests + match editor
# or: npm run plugin-zip   # production build then clanspress.zip
```

**REST API** (authenticated, `manage_options`):

| Method | Route | Purpose |
|--------|--------|---------|
| `GET` | `/wp-json/clanspress/v1/admin/bootstrap` | Tabs, `optionSchemas`, current `values`, extensions list. |
| `PUT` | `/wp-json/clanspress/v1/admin/settings/{option_key}` | JSON body: field map; uses each `Abstract_Settings::sanitize()`. |
| `PUT` | `/wp-json/clanspress/v1/admin/extensions` | JSON `{ "installed": ["cp_players", ...] }`; uninstalls removed slugs then saves the list. |

To restore a **standalone** PHP submenu for an extension settings class, filter `clanspress_extension_settings_register_submenu` to `true` for that `Abstract_Settings` instance.

## Extension System
Extensions are registered through filter-based discovery and loaded by the extension loader.

### Core Rules
- Every extension has a unique slug and semantic version.
- Extensions may declare dependencies (`requires`) and parent-child relationships (`parent_slug`).
- Extensions with unmet requirements must not be enabled.
- Lifecycle methods are available for installer, updater, runtime boot, and uninstaller flows.

### Extension loader bootstrap

`Main::$extensions` is **`null` until `init()`** runs (after `load_plugin_textdomain()`). Theme or plugin code that runs earlier must not call `clanspress()->extensions` without a null check.

`Skeleton::can_install()` reads installed slugs via `Extension_Loader::read_installed_extensions_from_options()` so dependency checks work while the loader singleton is still constructing (avoiding a circular access on `clanspress()->extensions`).

### Extension Data Stores
Extensions should persist extension-specific data through a PHP data store abstraction.

- Contract: `Kernowdev\Clanspress\Extensions\Extension_Data_Store`
- Default implementation: `Kernowdev\Clanspress\Extensions\Data_Store_WP`
- Base extension helper methods:
  - `get_data()`
  - `set_data( array $data )`
  - `delete_data()`

Swap implementations with the `clanspress_extension_data_store` filter for custom storage backends.

### Extension-Owned Block Registration
Each extension should register its own blocks and keep its block list local to that extension class.

Block editor categories (registered on `block_categories_all`):

| Slug | Label | Intended blocks |
|------|--------|-----------------|
| `clanspress` | Clanspress | Cross-cutting / generic (e.g. player settings, team create form) |
| `clanspress-players` | Clanspress Players | Players extension (e.g. avatar, cover) |
| `clanspress-teams` | Clanspress Teams | Teams extension (e.g. team card) |
| `clanspress-matches` | Clanspress Matches | Matches extension (match list / match card) |

Set each block’s `category` in `block.json` to one of these slugs.

First-party extensions compile blocks to **`build/{matches|players|teams}/…`** and register them with **`Skeleton::register_extension_block_types_from_metadata_collection()`** (WordPress 6.8+ `wp_register_block_types_from_metadata_collection()`, with fallbacks for older releases). Manifests are generated at `build/…/blocks-manifest.php` during `npm run build:blocks`.

For ad-hoc or third-party blocks that ship as separate compiled folders, you can still use **`Skeleton::register_extension_blocks( array $block_directories )`** (`register_block_type_from_metadata()` per directory). Filters apply only to that path:

- `clanspress_extension_{slug}_block_directories`
- `clanspress_extension_block_directories`

### Extension-Owned FSE Template Registration
Extensions can and should register their own FSE templates, so template availability follows extension activation.

- Use base helper: `Skeleton::register_extension_templates( array $templates )`
- Expected template array shape:
  - key: template slug (for example `player-settings`)
  - value:
    - `title` => translated title string
    - `path` => absolute template file path
- Support template customization through:
  - `clanspress_extension_{slug}_templates`
  - `clanspress_extension_templates`

### Player settings (front-end): plugin actions

The **Player settings** block (`clanspress/player-settings`) uses the Interactivity API store `clanspress-player-settings`. Core exposes a generic **`actions.runPluginAction`** handler so extensions can add buttons or links inside player settings panels **without** inline scripts: the click runs `fetch()` against your URL, sends the WordPress REST nonce, and shows the block’s existing success/error toast.

**Localized config** is attached as `window.CLANSPRESSPLAYERSETTINGS` when the Players extension enqueues scripts. Default keys:

| Key | Purpose |
|-----|---------|
| `ajax_url` | Admin AJAX URL (legacy profile save). |
| `nonce` | Nonce for `clanspress_profile_settings_save_action`. |
| `rest_url` | Site REST root (for building route URLs in PHP). |
| `rest_nonce` | Nonce for `wp_rest` (sent as `X-WP-Nonce` on plugin actions). |
| `settings_url_base` | (Player settings page only.) Trailing-slash base URL, e.g. `https://example.com/players/settings/`. |
| `settings_initial_nav` | (Player settings page only.) Resolved parent tab slug (`profile`, `account`, `teams`, …). |
| `settings_initial_panel` | (Player settings page only.) Resolved panel slug (`profile-info`, `account-info`, …). |

Extend or override the object with filter **`clanspress_player_settings_frontend_config`** (same array shape as above).

**Deep links:** Each tab and sub-page has a canonical URL: `/players/settings/{nav}/{panel}/` (e.g. `/players/settings/account/account-info/`). `/players/settings/{nav}/` redirects to that nav’s first panel. Invalid slugs redirect to a valid default. The block updates the address bar when you switch tabs (history `pushState` / `replaceState`). After adding or changing rewrite rules, save **Settings → Permalinks** once (or flush rewrite rules) so WordPress routes new paths.

**Markup contract** (on the clicked element, e.g. `<button type="button">`):

| Attribute | Required | Description |
|-----------|----------|-------------|
| `data-wp-on--click="actions.runPluginAction"` | Yes | Wires the Interactivity action. |
| `data-cp-action-url` | Yes | Full request URL (typically `rest_url( 'your-namespace/v1/...' )` from PHP). |
| `data-cp-action-method` | No | HTTP method; default `POST`. `GET` / `HEAD` omit body. |
| `data-cp-action-body` | No | JSON string for the request body (parsed client-side; empty object if omitted). |
| `data-cp-action-confirm` | No | If set, `window.confirm()` must pass before the request runs. |
| `data-cp-action-remove-closest` | No | CSS selector; on success, `closest(selector)` on the clicked element is removed (e.g. list row). |
| `data-cp-action-success-message` / `data-cp-action-error-message` | No | Toast copy; sensible defaults if omitted. |

Your REST route must validate `X-WP-Nonce`, check capabilities, and return appropriate HTTP status codes (`runPluginAction` treats non-OK responses as errors).

### Social Kit: automatic activity posts

When the **Clanspress Social Kit** extension is enabled, social “activity” posts are created automatically for common events and rendered in the **Social feed** block (`clanspress-social/social-feed`):

- **Friendship accepted:** When two players become friends (`clanspress_social_kit_friendship_accepted`), Social Kit inserts an `activity` post for each user so that both see a “became friends” card in their feed.
- **Team created:** When a team is created (`clanspress_team_created`), the creator gets an `activity` post that can show the team’s cover and avatar.
- **Team joined:** When a player joins a team (`clanspress_team_roster_updated`), that player gets an `activity` post indicating they joined the team.

Per-user toggles live in the **Player → Account → Social posts** settings panel (rendered by Social Kit):

- **Default post visibility** and **Allow replies on new posts** (existing).
- Additional booleans (stored as user meta) such as:
  - `cp_social_kit_activity_friends_enabled` — show “became friends” updates.
  - `cp_social_kit_activity_teams_enabled` — show team create/join updates.

These are respected by Social Kit’s `Activity_Logger` before inserting new activity rows.

#### Activity payload and filters

Social Kit uses the `cp_social_posts` table with `post_type = 'activity'` for these items and fills structured context using existing columns:

- `author_id` — the player whose feed the item belongs to.
- `actor_user_id` — the “other” player for friendship events.
- `team_id` — the team for create/join events.

The low-level insert payload for automatic activity posts is filterable:

```php
add_filter(
	'clanspress_social_kit_activity_payload',
	function ( array $payload, int $user_id, array $args ) {
		// Inspect/modify $payload before it is passed to Data_Access::insert_post().
		// Example: change visibility, override post_type, or inject custom link metadata.
		return $payload;
	},
	10,
	3
);
```

When Social Kit formats posts for the REST API, it also builds an `activity` structure for `activity` posts (user/team objects, labels, etc.). Per-activity filters allow customization of that structure before it is sent to the Social feed block:

```php
add_filter(
	'clanspress_social_kit_activity_friendship',
	function ( array $activity, object $row ) {
		// $activity contains primary/secondary user payloads for "became friends" events.
		// You can swap avatars, change labels, or attach extra metadata.
		return $activity;
	},
	10,
	2
);

add_filter(
	'clanspress_social_kit_activity_team_created',
	function ( array $activity, object $row ) {
		// Customize “created the team” cards (e.g. extra badges or stats).
		return $activity;
	},
	10,
	2
);
```

Finally, the short activity label shown next to the author name in the feed header can be overridden per type:

```php
add_filter(
	'clanspress_social_kit_activity_label_friendship',
	function ( string $label, array $activity, object $row ) {
		// Replace the default “became friends” string.
		return $label;
	},
	10,
	3
);
```

These hooks allow third-party plugins to completely change the text, imagery, or behaviour of automatic activity posts (friendships, teams, or future action types) without modifying core Social Kit code.

## Admin Extension Manager
The `Clanspress > Extensions` screen should remain the source of truth for extension state.

- Shows extension metadata (name, description, version, type, requirements).
- Prevents enabling extensions with unmet dependencies.
- Supports multisite-aware storage of installed extension records.
- Offers validation hooks before persistence (`clanspress_validate_installed_extensions`).

## Teams Modes
The Teams extension now supports mode-based behavior through admin settings (`Clanspress > Teams`):

- `single_team`: single organization/team setup (for traditional sports style sites).
- `multiple_teams`: multi-team clan setup under one community.
- `team_directories`: directory mode where users can create and manage teams.
  - Includes block-based FSE templates `teams-create` (`/teams/create/`) and `teams-manage` (`/teams/{slug}/manage/`, BuddyPress-style actions). Legacy `/teams/manage/{slug}/` still resolves. Extend actions via `clanspress_team_front_action_rewrite_slugs` and `clanspress_team_action_dispatch`.

Mode helpers available on the teams extension class:
- `get_team_mode()`
- `is_single_team_mode()`
- `is_multiple_teams_mode()`
- `is_team_directories_mode()`

### Per-Team Options
Each `cp_team` post supports individual options:
- Join mode: `open_join`, `join_with_permission`, `invite_only`
- Allow player invites
- Allow front-end team editing
- Allow banning players

These options are managed in the block editor sidebar (no metaboxes) and stored as post meta. PHP-side updates from the Teams extension (for example `update_team_options()` and roster persistence) go through the team data store so all structured fields stay on one path.

### Team entity data store

Team posts (`cp_team`) and their structured meta are persisted through a small CRUD layer, separate from the extension-wide option bucket described in [Extension Data Stores](#extension-data-stores).

- **Contract:** `Kernowdev\Clanspress\Extensions\Teams\Team_Data_Store` (`read` / `create` / `update` / `delete` on `Team` entities).
- **Default implementation:** `Kernowdev\Clanspress\Extensions\Teams\Team_Data_Store_CPT` (WordPress post + post meta).
- **Shared meta helpers:** `Kernowdev\Clanspress\Extensions\Data_Stores\WP_Post_Meta_Data_Store` — optional base for CPT-backed stores that need direct meta-table reads/writes in one place.

Swap the implementation with the **`clanspress_team_data_store`** filter. The filter must return a `Team_Data_Store` instance; anything else is ignored and the default CPT store is used.

**Procedural helper:** `clanspress_get_team( int $id )` loads a `Team` via the active store (or `null` if Teams is inactive or the post is not a team).

Third parties can customize the JS UI using JavaScript hooks:
- `clanspress.teams.joinModes`
- `clanspress.teams.optionControls`

JS example (add custom option control):

```js
const { addFilter } = wp.hooks;
const { createElement: el } = wp.element;
const { ToggleControl } = wp.components;

addFilter(
	'clanspress.teams.optionControls',
	'my-plugin/team-options-control',
	( controls, context ) => {
		return [
			...controls,
			el( ToggleControl, {
				key: 'my_custom_toggle',
				label: 'Enable custom team flag',
				checked: !! context.meta.cp_team_custom_flag,
				onChange: ( value ) =>
					context.setMetaValue( 'cp_team_custom_flag', !! value ),
			} ),
		];
	}
);
```

Teams extension helper methods:
- `get_team_options( int $team_id )`
- `update_team_options( int $team_id, array $options )`
- `can_user_join_team( int $team_id, int $user_id )`
- `can_invite_players( int $team_id )`
- `can_edit_team_frontend( int $team_id )`
- `can_ban_players( int $team_id )`

## Hooking And Customization
When adding new features, expose logical hooks around:
- extension registration and validation
- extension install and runtime checks
- settings sanitization and persistence
- admin interface decision points

### Settings Extensibility
All extension settings can be extended or customized by third parties.

For an extension option key (example: `clanspress_teams_settings`) these hooks are available:
- `{option_key}_parent_menu_slug`
- `{option_key}_defaults`
- `{option_key}_sections`
- `{option_key}_section_fields`
- `{option_key}_field`
- `{option_key}_render_field` (return `true` when custom rendering is handled)
- `{option_key}_sanitize_input`
- `{option_key}_sanitize`
- `{option_key}_before_page`
- `{option_key}_after_page`

Example: add a custom Teams mode and extra settings fields from a third-party plugin:

```php
<?php
/**
 * Plugin Name: Clanspress Teams Pro Modes
 */

// 1) Add a custom teams mode option.
add_filter(
	'clanspress_teams_mode_options',
	function ( array $options ): array {
		$options['academy_mode'] = __( 'Academy mode (junior squads)', 'my-plugin' );

		return $options;
	}
);

// 2) Add fields to the Teams "general" section.
add_filter(
	'clanspress_teams_settings_section_fields',
	function ( array $fields, string $section_id ): array {
		if ( 'general' !== $section_id ) {
			return $fields;
		}

		$fields['academy_max_players'] = array(
			'label'       => __( 'Academy max players', 'my-plugin' ),
			'type'        => 'text',
			'description' => __( 'Maximum players per academy team.', 'my-plugin' ),
			'default'     => '25',
			'sanitize'    => 'absint',
		);

		return $fields;
	},
	10,
	2
);

// 3) Enforce extra save rules for teams settings.
add_filter(
	'clanspress_teams_settings_sanitize',
	function ( array $output ): array {
		if ( isset( $output['academy_max_players'] ) ) {
			$output['academy_max_players'] = max( 5, absint( $output['academy_max_players'] ) );
		}

		return $output;
	}
);

// 4) Run mode-specific logic when your mode is active.
add_action(
	'clanspress_teams_mode_academy_mode',
	function ( \Kernowdev\Clanspress\Extensions\Teams $teams_extension ): void {
		// Boot academy-specific features here.
	}
);
```

Example: fully custom render a Teams setting field with `{option_key}_render_field`:

```php
<?php
// Render a custom UI for a specific field and mark it handled.
add_filter(
	'clanspress_teams_settings_render_field',
	function ( bool $handled, string $field_id, array $field, $value ): bool {
		if ( 'academy_max_players' !== $field_id ) {
			return $handled;
		}

		printf(
			'<input type="range" min="5" max="60" step="1" name="clanspress_teams_settings[%1$s]" value="%2$d" />',
			esc_attr( $field_id ),
			absint( $value )
		);

		echo ' <span>' . esc_html( absint( $value ) ) . '</span>';
		echo '<p class="description">' . esc_html__( 'Choose max players for academy teams.', 'my-plugin' ) . '</p>';

		// Returning true prevents default field rendering.
		return true;
	},
	10,
	4
);
```

Example: register extension-owned FSE templates from a third-party extension:

```php
<?php
add_filter(
	'clanspress_extension_cp_teams_templates',
	function ( array $templates ): array {
		$templates['team-archive'] = array(
			'title' => __( 'Team Archive', 'my-plugin' ),
			'path'  => plugin_dir_path( __FILE__ ) . 'templates/team-archive.php',
		);

		return $templates;
	}
);
```

Create-team form steps are filterable so third-party plugins can add their own steps:

```php
add_filter(
	'clanspress_team_create_form_steps',
	function ( array $steps ): array {
		$steps['custom_rules'] = array(
			'label' => __( 'Step 4: Custom Rules', 'my-plugin' ),
		);

		return $steps;
	}
);

add_action(
	'clanspress_team_create_form_step_custom_rules',
	function (): void {
		echo '<p><label for="my-team-rules">Rules</label><textarea id="my-team-rules" name="my_team_rules"></textarea></p>';
	}
);
```

Create-team step labels are currently:
- `Step 1: Team Details`
- `Step 2: Team Avatar`
- `Step 3: Player invites` (with autocomplete + removable invite chips)

Complete pattern: custom step + save custom data on team creation:

```php
<?php
// 1) Add a custom step to the create-team flow.
add_filter(
	'clanspress_team_create_form_steps',
	function ( array $steps ): array {
		$steps['brand_voice'] = array(
			'label' => __( 'Step 4: Brand Voice', 'my-plugin' ),
		);

		return $steps;
	}
);

// 2) Render custom fields for your step.
add_action(
	'clanspress_team_create_form_step_brand_voice',
	function (): void {
		?>
		<p>
			<label for="my-team-tone"><?php esc_html_e( 'Team Tone', 'my-plugin' ); ?></label>
			<select id="my-team-tone" name="my_team_tone">
				<option value="competitive"><?php esc_html_e( 'Competitive', 'my-plugin' ); ?></option>
				<option value="casual"><?php esc_html_e( 'Casual', 'my-plugin' ); ?></option>
			</select>
		</p>
		<p>
			<label for="my-team-tagline"><?php esc_html_e( 'Public Tagline', 'my-plugin' ); ?></label>
			<input type="text" id="my-team-tagline" name="my_team_tagline" />
		</p>
		<?php
	}
);

// 3) Persist custom data after core team creation succeeds.
add_action(
	'clanspress_team_created',
	function ( int $team_id, int $user_id, array $request ): void {
		$tone    = sanitize_key( wp_unslash( $request['my_team_tone'] ?? '' ) );
		$tagline = sanitize_text_field( wp_unslash( $request['my_team_tagline'] ?? '' ) );

		$allowed_tones = array( 'competitive', 'casual' );
		if ( ! in_array( $tone, $allowed_tones, true ) ) {
			$tone = 'competitive';
		}

		update_post_meta( $team_id, 'my_team_tone', $tone );
		update_post_meta( $team_id, 'my_team_tagline', $tagline );
	},
	10,
	3
);
```

### Documented Hooks
- `clanspress_registered_extensions`
  - Filter returning all third-party extension objects keyed by slug.
  - Args: `array $extensions`
- `clanspress_official_registered_extensions`
  - Filter used by first-party extensions to self-register before whitelist validation.
  - Args: `array $extensions`
- `clanspress_extension_data_store`
  - Filter to swap extension data store implementation.
  - Args: `Extension_Data_Store $data_store`, `string $slug`, `Skeleton $extension`
- `clanspress_extension_{slug}_block_directories`
  - Dynamic filter for extension-specific block build directories.
  - Args: `array $block_directories`, `Skeleton $extension`
- `clanspress_extension_block_directories`
  - Global filter for extension block build directories.
  - Args: `array $block_directories`, `Skeleton $extension`
- `clanspress_extension_{slug}_templates`
  - Dynamic filter for extension-specific FSE templates.
  - Args: `array $templates`, `Skeleton $extension`
- `clanspress_extension_templates`
  - Global filter for extension FSE templates.
  - Args: `array $templates`, `Skeleton $extension`
- `clanspress_can_install_{slug}_extension`
  - Dynamic filter used for extension requirement checks.
  - Args: `bool $can_install`, `Skeleton $extension`
- `clanspress_extension_installer_{slug}`
  - Dynamic action fired by base installer lifecycle.
  - Args: `Skeleton $extension`
- `clanspress_extension_updater_{slug}`
  - Dynamic action fired by base updater lifecycle.
  - Args: `Skeleton $extension`
- `clanspress_extension_uninstaller_{slug}`
  - Dynamic action fired by base uninstaller lifecycle.
  - Args: `Skeleton $extension`
- `clanspress_extension_run_{slug}`
  - Dynamic action fired by base runtime boot lifecycle.
  - Args: `Skeleton $extension`
- `clanspress_validate_installed_extensions`
  - Filter to enforce install policy before persisting extension state.
  - Args: `array $new_installed`, `array $requested`, `array $available_extensions`
- `clanspress_teams_mode`
  - Filter resolved teams mode from teams settings.
  - Args: `string $team_mode`, `Teams $extension`
- `clanspress_teams_mode_options`
  - Filter teams mode options used by teams admin settings.
  - Args: `array $options`, `Admin $admin`
- `clanspress_teams_mode_loaded`
  - Action fired after teams mode has been resolved.
  - Args: `string $team_mode`, `Teams $extension`
- `clanspress_teams_mode_{mode}`
  - Dynamic action fired for mode-specific boot logic.
  - Args: `Teams $extension`
- `clanspress_team_join_modes`
  - Filter available per-team join modes.
  - Args: `array $modes`, `Teams $extension`
- `clanspress_team_options`
  - Filter resolved per-team option map.
  - Args: `array $options`, `int $team_id`, `Teams $extension`
- `clanspress_team_data_store`
  - Filter team entity persistence implementation (must return `Team_Data_Store`; non-instance values fall back to the default CPT store).
  - Args: `Team_Data_Store $store`, `Teams $extension`
- `clanspress_team_options_updated`
  - Action fired after team options save.
  - Args: `int $team_id`, `array $options`, `Teams $extension`
- `clanspress_can_user_join_team`
  - Filter whether a user can join a team.
  - Args: `bool $can_join`, `int $team_id`, `int $user_id`, `array $options`, `Teams $extension`
- `clanspress_team_can_invite_players`
  - Filter team invite capability.
  - Args: `bool $allowed`, `int $team_id`, `array $options`, `Teams $extension`
- `clanspress_team_can_edit_frontend`
  - Filter front-end edit capability.
  - Args: `bool $allowed`, `int $team_id`, `array $options`, `Teams $extension`
- `clanspress_team_can_ban_players`
  - Filter ban capability.
  - Args: `bool $allowed`, `int $team_id`, `array $options`, `Teams $extension`

## Social Kit: Automatic Activity Posts

Social Kit automatically creates activity posts in users' feeds when certain events occur. Users can toggle these off in their player settings under "Activity posts".

### Supported Activity Types

| Activity Type | Trigger | Visibility |
|---------------|---------|------------|
| `team_created` | User creates a new team | Creator's feed only |
| `team_joined` | User joins a team | User's feed + team's feed |
| `friendship_accepted` | Two users become friends | Both users' feeds (reversed perspective) |

### Activity Cards

Activity posts display rich cards:
- **Team activities**: Team cover image, avatar, and name with link to team profile
- **Friendship activities**: Target user's avatar and name with link to their profile

### PHP Filters

**Modifying activity post data before insertion:**

```php
// Modify activity payload before insertion
add_filter( 'clanspress_social_kit_activity_payload', function( $payload, $user_id, $args ) {
    // Customize the activity post data
    return $payload;
}, 10, 3 );

// Modify payload for a specific activity type
add_filter( 'clanspress_social_kit_activity_team_created', function( $payload, $user_id, $args ) {
    // Customize team_created activity specifically
    return $payload;
}, 10, 3 );

// Customize the activity label shown in the feed header
add_filter( 'clanspress_social_kit_activity_label_team_created', function( $label, $activity_type ) {
    return __( 'founded a new team', 'my-plugin' );
}, 10, 2 );
```

**Rendering custom activity cards:**

Third-party developers can render custom activity cards for their own activity types:

```php
// Render a custom card for any activity type
add_filter( 'clanspress_social_kit_activity_card_html', function( $html, $activity_type, $row, $team_payload, $target_user, $viewer_id ) {
    if ( 'my_custom_activity' === $activity_type ) {
        return '<div class="my-custom-card">Custom content here</div>';
    }
    return $html;
}, 10, 6 );

// Or use the type-specific filter (cleaner for single types)
add_filter( 'clanspress_social_kit_activity_card_html_my_custom_activity', function( $html, $row, $team_payload, $target_user, $viewer_id ) {
    return '<div class="my-custom-card">Custom content here</div>';
}, 10, 5 );
```

The card HTML is rendered server-side and injected into the feed. Available data:
- `$row` - Database row with `id`, `author_id`, `team_id`, `activity_type`, `content`, etc.
- `$team_payload` - Array with `id`, `name`, `avatar_url`, `cover_url`, `profile_url` (if team activity)
- `$target_user` - Array with `id`, `name`, `avatar_url`, `profile_url` (if friendship activity)
- `$viewer_id` - Current logged-in user ID

### User Preferences

Users can disable specific activity types via user meta:
- `cp_social_kit_activity_friendship_accepted_enabled` (default: `'1'`)
- `cp_social_kit_activity_team_created_enabled` (default: `'1'`)
- `cp_social_kit_activity_team_joined_enabled` (default: `'1'`)

Set to `'0'` to disable that activity type for the user.

## Notifications System

Clanspress includes a core notifications system that supports both simple notifications and interactive notifications with action buttons. The system uses HTTP long polling for real-time updates with filters to swap in WebSocket transport.

### Notification Bell Block

Add the `clanspress/notification-bell` block to display a bell icon with unread count and dropdown. Block attributes:

| Attribute | Type | Default | Description |
|-----------|------|---------|-------------|
| `showDropdown` | boolean | `true` | Show dropdown on click |
| `dropdownCount` | number | `10` | Number of notifications in dropdown |

### Sending Notifications

```php
// Simple notification
clanspress_notify( $user_id, 'mention', 'You were mentioned in a post', [
    'url'      => $post_url,
    'actor_id' => $mentioner_id,
] );

// Interactive notification with actions
clanspress_notify( $user_id, 'team_invite', sprintf( '%s invited you to join %s', $inviter_name, $team_name ), [
    'actor_id'    => $inviter_id,
    'object_type' => 'team',
    'object_id'   => $team_id,
    'url'         => $team_url,
    'actions'     => [
        [
            'key'             => 'accept',
            'label'           => __( 'Accept', 'clanspress' ),
            'style'           => 'primary',
            'handler'         => 'my_team_invite_accept',
            'status'          => 'accepted',
            'success_message' => __( 'You have joined the team!', 'clanspress' ),
        ],
        [
            'key'             => 'decline',
            'label'           => __( 'Decline', 'clanspress' ),
            'style'           => 'secondary',
            'handler'         => 'my_team_invite_decline',
            'status'          => 'declined',
            'success_message' => __( 'Invitation declined.', 'clanspress' ),
        ],
    ],
] );
```

### Action Parameters

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `key` | string | Yes | Unique action identifier (e.g., 'accept', 'decline') |
| `label` | string | Yes | Button label |
| `style` | string | No | 'primary', 'secondary', or 'danger'. Default 'secondary' |
| `handler` | string | Yes | Handler identifier for the action |
| `status` | string | No | Status to set after action ('accepted', 'declined', 'dismissed') |
| `success_message` | string | No | Message to show on success |
| `confirm` | string/false | No | Confirmation message, or false for no confirm |

### Handling Actions

Extensions register their own action handlers. For example, the Teams extension registers handlers for `team_invite_accept` and `team_invite_decline`. Third-party plugins can register handlers using filters:

```php
// Handle actions by handler identifier (recommended)
add_filter( 'clanspress_notification_action_handler', function( $result, $handler, $notification, $action, $user_id ) {
    // Return early if another handler already processed this
    if ( null !== $result ) {
        return $result;
    }

    if ( 'my_custom_handler' === $handler ) {
        // Perform your action logic
        $object_id = $notification->object_id;
        
        // Return result array
        return [
            'success'  => true,
            'message'  => __( 'Action completed!', 'my-plugin' ),
            'redirect' => get_permalink( $object_id ), // Optional redirect
        ];
    }

    // Return null to pass to next handler
    return null;
}, 10, 5 );

// Or handle by notification type (fires before generic handler)
add_filter( 'clanspress_notification_action_group_invite', function( $result, $notification, $action, $user_id ) {
    if ( 'accept' === $action['key'] ) {
        // Add user to group
        return [
            'success' => true,
            'message' => __( 'You have joined the group!', 'my-plugin' ),
        ];
    }
    return $result;
}, 10, 4 );
```

**Handler registration pattern for extensions:**

```php
class My_Extension {
    public function run(): void {
        // Register notification action handlers
        add_filter( 'clanspress_notification_action_handler', [ $this, 'handle_notification_actions' ], 10, 5 );
    }

    public function handle_notification_actions( $result, $handler, $notification, $action, $user_id ) {
        if ( null !== $result ) {
            return $result;
        }

        switch ( $handler ) {
            case 'my_invite_accept':
                return $this->handle_invite_accept( $notification, $user_id );
            case 'my_invite_decline':
                return $this->handle_invite_decline( $notification, $user_id );
            default:
                return null;
        }
    }
}
```

### Helper Functions

| Function | Description |
|----------|-------------|
| `clanspress_notify( $user_id, $type, $title, $args )` | Send a notification |
| `clanspress_get_notifications( $user_id, $page, $per_page, $unread_only )` | Get notifications for a user |
| `clanspress_get_notification( $id )` | Get a single notification |
| `clanspress_get_unread_notification_count( $user_id )` | Get unread count |
| `clanspress_mark_notification_read( $id, $user_id )` | Mark as read |
| `clanspress_mark_all_notifications_read( $user_id )` | Mark all as read |
| `clanspress_delete_notification( $id, $user_id )` | Delete a notification |
| `clanspress_delete_all_notifications( $user_id )` | Delete all for a user |
| `clanspress_delete_notifications_for_object( $type, $id )` | Delete by object |
| `clanspress_execute_notification_action( $id, $action_key, $user_id )` | Execute an action |
| `clanspress_dismiss_notification( $id, $user_id )` | Dismiss a notification |
| `clanspress_get_notifications_url( $user_id )` | Get notifications page URL |
| `clanspress_render_notification( $notification, $compact )` | Render notification HTML |

### REST API Endpoints

| Method | Endpoint | Description |
|--------|----------|-------------|
| `GET` | `/clanspress/v1/notifications` | List notifications |
| `GET` | `/clanspress/v1/notifications/poll` | Long polling for real-time updates |
| `GET` | `/clanspress/v1/notifications/count` | Get unread count |
| `GET` | `/clanspress/v1/notifications/{id}` | Get single notification |
| `DELETE` | `/clanspress/v1/notifications/{id}` | Delete notification |
| `POST` | `/clanspress/v1/notifications/{id}/read` | Mark as read |
| `POST` | `/clanspress/v1/notifications/{id}/action` | Execute action |
| `POST` | `/clanspress/v1/notifications/read-all` | Mark all as read |
| `GET` | `/clanspress/v1/notifications/transport` | Get transport config |

### Real-Time Updates (Long Polling)

The notification bell uses HTTP long polling by default. The poll endpoint (`/notifications/poll`) waits up to 30 seconds for new notifications before returning.

**Polling parameters:**
- `since` - ISO timestamp to get notifications after
- `last_id` - Get notifications with ID greater than this
- `timeout` - Max wait time in seconds (default 30)

**Response includes:**
- `notifications` - Array of new notifications
- `unread_count` - Current unread count
- `timestamp` - Server timestamp for next poll
- `next_poll` - Recommended interval until next poll (ms)

### WebSocket Support

The system is designed for WebSocket upgrade. Use these filters to provide WebSocket transport:

```js
// JavaScript: Enable WebSocket transport
wp.hooks.addFilter(
    'clanspress.notifications.useWebSocket',
    'my-plugin/websocket',
    ( useWs, context ) => {
        // Return true if WebSocket is available
        return myWebSocketService.isConnected();
    }
);

// Provide WebSocket configuration
wp.hooks.addFilter(
    'clanspress.notifications.webSocketConfig',
    'my-plugin/websocket-config',
    ( config, context ) => {
        return {
            url: 'wss://example.com/notifications',
            authMessage: { token: myAuthToken },
        };
    }
);
```

```php
// PHP: Override polling transport entirely
add_filter( 'clanspress_notification_poll_transport', function( $response, $user_id, $since, $last_id, $request ) {
    // Return a WP_REST_Response to bypass polling
    // Useful for WebSocket-only setups
    return new WP_REST_Response( [
        'transport' => 'websocket',
        'message'   => 'Use WebSocket connection instead',
    ] );
}, 10, 5 );

// Customize transport configuration
add_filter( 'clanspress_notification_transport_config', function( $config, $user_id ) {
    if ( my_websocket_available() ) {
        $config['type'] = 'websocket';
        $config['websocket_url'] = 'wss://example.com/notifications';
    }
    return $config;
}, 10, 2 );
```

### JavaScript Hooks

| Hook | Description |
|------|-------------|
| `clanspress.notifications.useWebSocket` | Return true to use WebSocket transport |
| `clanspress.notifications.webSocketConfig` | Provide WebSocket URL and auth config |
| `clanspress.notifications.received` | Fired when new notifications arrive |
| `clanspress.notifications.showToast` | Customize toast notification display |

### PHP Filters

| Filter | Description |
|--------|-------------|
| `clanspress_notification_poll_timeout` | Modify poll timeout |
| `clanspress_notification_poll_interval` | Modify poll check interval |
| `clanspress_notification_poll_transport` | Override polling with custom transport |
| `clanspress_notification_next_poll_interval` | Modify next poll interval |
| `clanspress_notification_transport_config` | Customize transport configuration |
| `clanspress_notification_action_{type}` | Handle actions for a notification type |
| `clanspress_notification_action_handler` | Generic action handler |
| `clanspress_notification_types` | Register custom notification types |
| `clanspress_render_notification` | Customize notification HTML |
| `clanspress_format_notification_response` | Customize API response format |

### Built-in Notification Types

| Type | Description |
|------|-------------|
| `team_invite` | Team invitation with Accept/Decline actions |
| `team_join` | User joined a team |
| `team_role` | Team role changed |
| `team_removed` | Removed from team |
| `mention` | Mentioned in content |
| `system` | System notifications |

Third-party plugins can register additional types via the `clanspress_notification_types` filter.

## Maintenance Notes
- Keep this README updated when extension architecture, hooks, or setup requirements change.
- Keep public hooks documented with intent and expected arguments.
