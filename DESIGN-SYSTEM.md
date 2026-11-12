# Clanbite Block Class Naming System

This document defines the unified class naming conventions for **all Clanbite blocks and plugins** to ensure consistency, maintainability, and proper integration with WordPress theme.json styling.

## Core Principles

1. **BEM Methodology**: Use Block-Element-Modifier pattern (`.block__element--modifier`)
2. **WordPress Integration**: Leverage WordPress core classes for theme.json support
3. **Consistency**: Same component types use same base classes regardless of plugin or context
4. **Specificity**: Block-specific AND plugin-specific classes for unique styling needs
5. **Backward Compatibility**: Old class names preserved via SCSS `@extend` for existing customizations

## Plugin-Specific Modifiers

All UI components support plugin-specific modifiers to enable per-plugin customization while maintaining consistency:

- `--forums` - Forums plugin components
- `--social-kit` - Social Kit plugin components
- `--points` - Points plugin components
- `--ranks` - Ranks plugin components

**Example:**
```html
<button class="wp-element-button clanbite-button clanbite-button--primary clanbite-button--forums">
    Forum Button
</button>
```

## Component Categories

### 1. Buttons

All buttons should use WordPress core button classes for theme.json support, plus a Clanbite-specific modifier:

**Fits any theme by default:**
- `clanbite-buttons` has a zero-specificity defaults layer (`:where()`): padding, radius, accent background and a readable text colour computed from that accent. It only shows when the theme says nothing about buttons (classic themes, block themes without `styles.elements.button`); any theme rule or theme.json overrides it with no `!important`.
- `clanbite-tokens` (`assets/css/clanbite-tokens.css`) holds the colour tokens: `--clanbite-surface`, `-on-surface`, `-surface-alt`, `-border`, `-muted`, `-accent`, `-on-accent`, `-danger`, `-warning`, `-success`. They are derived from the page (theme presets `background`/`base`, `primary`/`accent`, `danger`, `success`, with `-on-*` text computed for contrast), so panels and popovers adapt to light and dark themes without configuration. A theme overrides any token in `:root`.
- In block SCSS always write `var(--clanbite-x, <fallback>)` with a standalone fallback (for example `var(--clanbite-border, color-mix(in srgb, currentcolor 22%, transparent))`). Never hard-code a light or dark palette (`#fff`, `#1d2327`, `rgba(0,0,0,.x)`); use `currentcolor`/`color-mix()` or a token. Surfaces that sit on a differently coloured background (grid gaps) must be opaque: mix with the surface token, not `transparent`.
- List `clanbite-tokens` (or `clanbite-buttons`, which depends on it) in `block.json` `style`.

**Delivery and theming (single contract):**
- The variants, states and focus ring are one plain stylesheet, `assets/css/clanbite-buttons.css`, registered by core as the style handle `clanbite-buttons`. List it in the `style` array of every `block.json` that renders buttons (`"style": ["file:./style-index.css", "clanbite-buttons"]`), or call `wp_enqueue_style( 'clanbite-buttons' )` from template output. Companion plugins reuse the core handle; they never ship their own button colours.
- Font, padding, radius and the primary colours always come from the theme (`styles.elements.button` and `styles.blocks.core/button` in theme.json). The contract only adds layout, the `--ghost` / `--secondary` outline, `--danger`, `--small` (padding only), `.is-active` / `[aria-pressed]` toggle state, disabled and `:focus-visible`.
- Themes can retune it with custom properties (`--clanbite-button-danger`, `--clanbite-button-wash`, `--clanbite-focus-ring`) or by targeting the variant classes. The danger colour follows the palette slug `danger`, then core `vivid-red`.
- **Block SCSS must never set background, colour, border, padding, radius or font on a button class**: that overrides the theme. Block SCSS may position buttons only (margin, flex, grid, width).
- Utility controls that are not actions (tabs, menu triggers, kebab/overflow, icon dismiss, rich-text toolbar tools, media tiles) are not `wp-element-button`; style them from `currentcolor` and theme tokens so they follow the theme too.
- Use `.is-active` for the selected choice in a segmented control and also set `aria-pressed` for assistive technology.

**Base Structure:**
```html
<div class="wp-block-button clanbite-button [clanbite-button--variant]">
    <a|button class="wp-block-button__link wp-element-button clanbite-button__element">
        Button Text
    </a|button>
</div>
```

**Class Definitions:**
- `wp-block-button` - WordPress core wrapper (required for theme.json)
- `wp-block-button__link` - WordPress core button element
- `wp-element-button` - WordPress core button styling hook
- `clanbite-button` - Clanbite button wrapper
- `clanbite-button--primary` - Primary action button
- `clanbite-button--secondary` - Secondary action button
- `clanbite-button--danger` - Destructive action button
- `clanbite-button--ghost` - Minimal/text button
- `clanbite-button__element` - Clanbite-specific button element class

**Context-Specific Variants:**
- `clanbite-button--challenge` - Team challenge button
- `clanbite-button--manage` - Manage/settings links
- `clanbite-button--settings` - Settings links
- `clanbite-button--submit` - Form submit buttons
- `clanbite-button--cancel` - Form cancel buttons

**Plugin-Specific Variants:**
- `clanbite-button--forums` - Forums plugin buttons
- `clanbite-button--social-kit` - Social Kit plugin buttons
- `clanbite-button--points` - Points plugin buttons
- `clanbite-button--ranks` - Ranks plugin buttons

**Action-Specific Variants (cross-plugin):**
- `clanbite-button--react` - Reaction buttons (Forums emoji reactions, Social Kit reactions)
- `clanbite-button--add-friend` - Social Kit add friend action
- `clanbite-button--follow` - Forums follow topic action
- `clanbite-button--post` - Post/reply submit actions
- `clanbite-button--edit` - Edit actions
- `clanbite-button--delete` - Delete/destructive actions (extends `--danger`)

**Example Usage:**
```html
<!-- Forums: Follow topic button -->
<button class="wp-element-button clanbite-button clanbite-button--primary clanbite-button--follow clanbite-button--forums">
    Follow Topic
</button>

<!-- Social Kit: React button -->
<button class="wp-element-button clanbite-button clanbite-button--ghost clanbite-button--react clanbite-button--social-kit">
    React
</button>
```

### 2. Avatars

Unified avatar structure for players, teams, and user navigation:

**Base Structure:**
```html
<div class="clanbite-avatar clanbite-avatar--[type] clanbite-avatar--[size]">
    <div class="clanbite-avatar__media">
        <a class="clanbite-avatar__link">
            <div class="clanbite-avatar__clip">
                <img class="clanbite-avatar__img" />
            </div>
        </a>
    </div>
</div>
```

**Class Definitions:**
- `clanbite-avatar` - Base avatar container
- `clanbite-avatar--player` - Player avatar variant
- `clanbite-avatar--team` - Team avatar variant
- `clanbite-avatar--user-nav` - User navigation avatar variant
- `clanbite-avatar--large` - Large size (120px+)
- `clanbite-avatar--medium` - Medium size (64-96px)
- `clanbite-avatar--small` - Small size (32-48px)
- `clanbite-avatar__media` - Media wrapper (for overlays)
- `clanbite-avatar__clip` - Circular clip container
- `clanbite-avatar__link` - Profile link wrapper
- `clanbite-avatar__img` - Actual image element
- `clanbite-avatar__img--placeholder` - Empty state styling
- `clanbite-avatar__overlay` - Rank/status overlay container

**Backward Compatibility:**
Original classes remain for existing customizations but map to unified system internally.

### 3. Covers / Banner Images

Unified cover image structure for profiles:

**Base Structure:**
```html
<div class="clanbite-cover clanbite-cover--[type] [position-class]">
    <div class="clanbite-cover__media-clip">
        <img class="clanbite-cover__media" />
    </div>
    <div class="clanbite-cover__content">
        <!-- Inner blocks -->
    </div>
</div>
```

**Class Definitions:**
- `clanbite-cover` - Base cover container
- `clanbite-cover--player` - Player profile cover
- `clanbite-cover--team` - Team profile cover
- `clanbite-cover--group` - Group profile cover
- `clanbite-cover--placeholder` - Empty state
- `clanbite-cover__media-clip` - Media container
- `clanbite-cover__media` - Image element
- `clanbite-cover__media--empty` - Empty placeholder image
- `clanbite-cover__content` - Content overlay container
- `is-position-[alignment]` - WordPress position classes (e.g., `is-position-center-center`)

### 4. Cards

Unified card structure for content items:

**Base Structure:**
```html
<div class="clanbite-card clanbite-card--[type]">
    <div class="clanbite-card__media">
        <!-- Avatar/image -->
    </div>
    <div class="clanbite-card__content">
        <div class="clanbite-card__header">
            <h3 class="clanbite-card__title">Title</h3>
            <div class="clanbite-card__meta">Meta info</div>
        </div>
        <div class="clanbite-card__body">
            Body content
        </div>
        <div class="clanbite-card__footer">
            Footer actions
        </div>
    </div>
</div>
```

**Class Definitions:**
- `clanbite-card` - Base card container
- `clanbite-card--match` - Match card variant
- `clanbite-card--team` - Team card variant
- `clanbite-card--player` - Player card variant
- `clanbite-card--event` - Event card variant
- `clanbite-card--post` - Social feed post card
- `clanbite-card--forums` - Forums plugin cards
- `clanbite-card--social-kit` - Social Kit plugin cards
- `clanbite-card--points` - Points plugin cards
- `clanbite-card--ranks` - Ranks plugin cards
- `clanbite-card--horizontal` - Horizontal layout
- `clanbite-card--vertical` - Vertical layout
- `clanbite-card__media` - Media/avatar container
- `clanbite-card__content` - Text content wrapper
- `clanbite-card__header` - Title and meta section
- `clanbite-card__title` - Card title element
- `clanbite-card__meta` - Metadata/tags section
- `clanbite-card__body` - Main content area
- `clanbite-card__footer` - Actions/CTA section

### 5. Forms

Unified form field structure:

**Base Structure:**
```html
<div class="clanbite-form-field clanbite-form-field--[type]">
    <label for="field-id" class="clanbite-form-field__label">
        Label Text
    </label>
    <input|select|textarea class="clanbite-form-field__input" />
    <p class="clanbite-form-field__description">
        Help text
    </p>
    <p class="clanbite-form-field__error">
        Error message
    </p>
</div>
```

**Class Definitions:**
- `clanbite-form-field` - Base field wrapper
- `clanbite-form-field--text` - Text input
- `clanbite-form-field--email` - Email input
- `clanbite-form-field--select` - Select dropdown
- `clanbite-form-field--textarea` - Textarea field
- `clanbite-form-field--radio` - Radio group
- `clanbite-form-field--checkbox` - Checkbox field
- `clanbite-form-field--hidden` - Conditionally hidden field
- `clanbite-form-field__label` - Field label
- `clanbite-form-field__input` - Input element
- `clanbite-form-field__description` - Help/description text
- `clanbite-form-field__error` - Error message display
- `clanbite-form-field__success` - Success message display

**Form Container:**
```html
<form class="clanbite-form clanbite-form--[type]">
    <div class="clanbite-form__section">
        <!-- Form fields -->
    </div>
    <div class="clanbite-form__actions">
        <!-- Submit/cancel buttons -->
    </div>
    <div class="clanbite-form__message clanbite-form__message--[state]">
        Form-level messages
    </div>
</form>
```

**Form Classes:**
- `clanbite-form` - Base form container
- `clanbite-form--create` - Create/new forms
- `clanbite-form--edit` - Edit forms
- `clanbite-form--inline` - Inline/compact form
- `clanbite-form--forums` - Forums plugin forms
- `clanbite-form--social-kit` - Social Kit plugin forms
- `clanbite-form--points` - Points plugin forms
- `clanbite-form--ranks` - Ranks plugin forms
- `clanbite-form__section` - Logical form section
- `clanbite-form__actions` - Action buttons container
- `clanbite-form__message` - Message container
- `clanbite-form__message--error` - Error state
- `clanbite-form__message--success` - Success state
- `clanbite-form__message--info` - Info state

### 6. Navigation

Unified navigation components:

**Base Structure:**
```html
<nav class="clanbite-nav clanbite-nav--[type]">
    <ul class="clanbite-nav__list">
        <li class="clanbite-nav__item clanbite-nav__item--active">
            <a href="#" class="clanbite-nav__link">
                <span class="clanbite-nav__icon"></span>
                <span class="clanbite-nav__label">Label</span>
            </a>
        </li>
    </ul>
</nav>
```

**Class Definitions:**
- `clanbite-nav` - Base navigation container
- `clanbite-nav--profile` - Profile navigation
- `clanbite-nav--user` - User menu navigation
- `clanbite-nav--tabs` - Tab navigation
- `clanbite-nav--toolbar` - Toolbar navigation
- `clanbite-nav--pagination` - Pagination navigation
- `clanbite-nav--forums` - Forums plugin navigation
- `clanbite-nav--social-kit` - Social Kit plugin navigation
- `clanbite-nav--points` - Points plugin navigation
- `clanbite-nav--ranks` - Ranks plugin navigation
- `clanbite-nav__list` - Navigation list container
- `clanbite-nav__item` - Individual nav item
- `clanbite-nav__item--active` - Active/current item
- `clanbite-nav__link` - Nav link element
- `clanbite-nav__icon` - Icon container
- `clanbite-nav__label` - Link text label

### 7. Modals / Dialogs

Unified modal structure:

**Base Structure:**
```html
<div class="clanbite-modal clanbite-modal--[type]">
    <div class="clanbite-modal__backdrop" data-wp-on--click="actions.close">
        <div class="clanbite-modal__dialog" role="dialog" aria-modal="true">
            <div class="clanbite-modal__header">
                <h2 class="clanbite-modal__title">Title</h2>
                <button class="clanbite-modal__close">×</button>
            </div>
            <div class="clanbite-modal__body">
                Content
            </div>
            <div class="clanbite-modal__footer">
                Actions
            </div>
        </div>
    </div>
</div>
```

**Class Definitions:**
- `clanbite-modal` - Base modal container
- `clanbite-modal--challenge` - Challenge modal variant
- `clanbite-modal--confirm` - Confirmation dialog
- `clanbite-modal--forums` - Forums plugin modals
- `clanbite-modal--social-kit` - Social Kit plugin modals
- `clanbite-modal--points` - Points plugin modals
- `clanbite-modal--ranks` - Ranks plugin modals
- `clanbite-modal__backdrop` - Overlay background
- `clanbite-modal__dialog` - Dialog box container
- `clanbite-modal__dialog--wide` - Wide modal variant
- `clanbite-modal__dialog--narrow` - Narrow modal variant
- `clanbite-modal__header` - Header section
- `clanbite-modal__title` - Modal title
- `clanbite-modal__close` - Close button
- `clanbite-modal__body` - Content area
- `clanbite-modal__footer` - Action buttons area

### 8. Lists

Unified list structure:

**Base Structure:**
```html
<div class="clanbite-list clanbite-list--[type]">
    <div class="clanbite-list__item">
        <div class="clanbite-list__item-media">
            <!-- Avatar/icon -->
        </div>
        <div class="clanbite-list__item-content">
            <div class="clanbite-list__item-title">Title</div>
            <div class="clanbite-list__item-meta">Meta</div>
        </div>
        <div class="clanbite-list__item-actions">
            <!-- Actions -->
        </div>
    </div>
</div>
```

**Class Definitions:**
- `clanbite-list` - Base list container
- `clanbite-list--matches` - Matches list
- `clanbite-list--events` - Events list
- `clanbite-list--players` - Players list
- `clanbite-list--topics` - Forums topics list
- `clanbite-list--replies` - Forums replies list
- `clanbite-list--forums` - Forums plugin lists
- `clanbite-list--social-kit` - Social Kit plugin lists
- `clanbite-list--points` - Points plugin lists
- `clanbite-list--ranks` - Ranks plugin lists
- `clanbite-list__item` - Individual list item
- `clanbite-list__item-media` - Item avatar/icon
- `clanbite-list__item-content` - Item text content
- `clanbite-list__item-title` - Item title
- `clanbite-list__item-meta` - Item metadata
- `clanbite-list__item-actions` - Item action buttons

## Utility Classes

Common utility classes across all blocks:

- `clanbite-hidden` - Visually hidden but available to screen readers
- `clanbite-loading` - Loading state indicator
- `clanbite-disabled` - Disabled state
- `clanbite-active` - Active state
- `clanbite-error` - Error state styling
- `clanbite-success` - Success state styling
- `clanbite-placeholder` - Placeholder/empty state

## Migration Strategy

1. **Add New Classes**: Add unified classes alongside existing block-specific classes
2. **Update Styles**: Update SCSS to style both old and new classes
3. **Deprecation Period**: Mark old class-only usage as legacy (keep functional)
4. **Documentation**: Update block documentation with new class structure
5. **Theme.json Integration**: Ensure all styled elements properly inherit from theme.json

## WordPress Theme.json Integration

### Button Styling

Buttons using `wp-block-button` and `wp-element-button` automatically inherit:
- Colors from `theme.json` `settings.color.palette`
- Typography from `theme.json` `settings.typography`
- Spacing from `theme.json` `settings.spacing`

### Custom Properties

Clanbite blocks should respect WordPress custom properties:
- `var(--wp--preset--color-*)` - Color palette
- `var(--wp--preset--font-size-*)` - Font sizes
- `var(--wp--preset--spacing-*)` - Spacing scale

## Examples

### Before (Inconsistent):
```php
// Team challenge button
<button class="wp-block-button__link wp-element-button clanbite-team-challenge__toggle">

// Team manage link
<a class="wp-block-button__link wp-element-button">

// Player settings link
<a class="wp-block-button__link wp-element-button">
```

### After (Unified):
```php
// Team challenge button
<div class="wp-block-button clanbite-button clanbite-button--challenge">
    <button class="wp-block-button__link wp-element-button clanbite-button__element">

// Team manage link
<div class="wp-block-button clanbite-button clanbite-button--manage">
    <a class="wp-block-button__link wp-element-button clanbite-button__element">

// Player settings link
<div class="wp-block-button clanbite-button clanbite-button--settings">
    <a class="wp-block-button__link wp-element-button clanbite-button__element">
```

## Maintenance

- Review this document when adding new blocks
- Update when WordPress core classes change
- Keep backward compatibility for at least one major version
- Document all breaking changes in changelog

## Avatar frame, progress ring and level badge

Every avatar goes through `clanbite_render_avatar()` / `clanbite_render_avatar_for_interactivity()` (`inc/avatar-helpers.php`) and is styled by the shared handle `clanbite-avatars` (`assets/css/clanbite-avatars.css`, also enqueued by the helpers). Add the handle to a block's `block.json` `style` array when the block outputs avatar markup from JS/templates.

- Shape: `circle`, `square` or `hexagon` (Players/Teams avatar-shape settings). Frame, ring and badge are SVG in a 100×100 box; the photo is clipped with a matching `clip-path`.
- Progress ring: drawn around the photo when **Ranks** (and Points) are active and a rank type has **Show progress on avatar** on; the arc starts at the top and runs clockwise.
- Level badge: small shape-matched badge at the lower right showing the rank level, when a rank type has **Show avatar icon** on.
- Data: `clanbite_get_avatar_badge()` (filter `clanbite_avatar_badge`), fed by `clanbite_ranks_get_avatar_badge()` (filter `clanbite_ranks_avatar_badge`). Without Ranks nothing extra is rendered.
- Theme: `--clanbite-avatar-frame`, `--clanbite-avatar-ring`, `--clanbite-avatar-ring-track`, `--clanbite-avatar-ring-width`, `--clanbite-avatar-badge-bg`, `--clanbite-avatar-badge-color`, `--clanbite-avatar-size`.

## Form layout (`clanbite-forms`)

`assets/css/clanbite-forms.css` (style handle `clanbite-forms`, depends on the tokens and buttons) lays out the team create/manage forms and the group settings form: label above a full-width control, helper text under its field, checkbox rows, media cards, roster table and a danger zone. Colours come from the shared tokens. Give any other form the `clanbite-form-layout` class (and `clanbite-form-layout__field` on field wrappers) to opt in, and enqueue the handle from its template.
