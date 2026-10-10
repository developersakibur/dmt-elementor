# Dark Mode Toggle for Elementor

A WordPress plugin that adds a light/dark mode toggle to any Elementor site. It reads your Elementor **Global Colors**, lets you set a dark value for each one, and switches the whole site when the visitor uses the toggle.

- **Version:** 1.0.1
- **Requires:** WordPress 5.6+, PHP 7.4+, Elementor (for the Global Colors)
- **License:** GPL-2.0-or-later

## Features

- **Reads Global Colors automatically.** System and Custom colors come straight from the active Elementor Kit. Nothing is hard-coded, so it works on any site.
- **Dark value per color.** Each color has a picker and hex field. An empty dark field keeps the light value.
- **Copy CSS variable.** Every row has a Copy button that gives you `var(--e-global-color-xxxx)`, ready for custom CSS. Colors written this way switch with the mode automatically.
- **Two-state toggle (light / dark)** with Sun and Moon icons, in two styles: Button or Switch.
- **Toggle design settings with live preview:** size, border radius, border width, shadow, and light/dark colors for background, icon, border and hover.
- **Smart default mode.** First visit follows the visitor's device theme. After they use the toggle, their choice is saved in `localStorage` and used from then on. Until they choose, the site follows the device theme live.
- **No flash.** A tiny inline script in `<head>` sets the mode before the page paints.
- **Synced across tabs.** Switching in one tab updates the others.
- **Accessible.** Real `<button>`, `aria-pressed`, `aria-label`, keyboard focus ring, and reduced-motion support.
- **Extra dark CSS** and **JSON export / import** in the Advanced tab.

## Installation

1. Download the plugin folder as a `.zip` (the zip must contain the `dmt-elementor` folder at its top level).
2. In WordPress go to **Plugins > Add New > Upload Plugin**, upload the zip and activate it.
3. Open **Settings > Dark Mode Toggle**.

## Quick start

1. **Colors tab:** enter a dark value for the Global Colors you want to change (background, headings, text, borders, and so on). Save.
2. **Toggle Design tab:** pick a style and colors. Save.
3. Add the shortcode where you want the toggle, for example in an Elementor Shortcode widget in your header:

   ```
   [dark_mode_toggle]
   ```

## Shortcode

```
[dark_mode_toggle]
[dark_mode_toggle style="switch" size="36" class="my-class"]
```

| Attribute | Values | Default |
|-----------|--------|---------|
| `style`   | `button` or `switch` | the style chosen in settings |
| `size`    | 20 to 120 (px, height of the toggle) | the size chosen in settings |
| `class`   | extra CSS class(es) | none |

You can place the shortcode more than once. All toggles stay in sync.

### Use your own button or icon

Add the CSS class `dmt-trigger` to any Elementor widget (button, icon, menu item). Clicking it switches the mode. No shortcode needed.

## Settings

### Colors
Light values are read live from **Elementor > Site Settings > Global Colors**. Use **Refresh from Elementor** after changing colors in Elementor.

### Toggle Design
Style, size, radius, border width, shadow, and light/dark colors for background, icon, border and hover background. The preview shows light and dark side by side.

### General
- **localStorage key:** where the choice is stored in the browser (default `dmt_theme`).
- **Transition (ms):** how long colors fade when switching. `0` is instant. Skipped for visitors who prefer reduced motion.
- **Dark form controls:** adds `color-scheme: dark` so scrollbars and default inputs match.

### Advanced
- **Extra dark mode CSS:** printed only in dark mode. Prefix selectors with `html[data-dmt-theme="dark"]`:

  ```css
  html[data-dmt-theme="dark"] .my-logo { filter: invert(1); }
  ```
- **Export / Import:** JSON of your settings. Colors are matched by Elementor color ID, so an import is useful between copies of the same site (for example staging and live).

## How it works

Elementor exposes every Global Color as a CSS variable (`--e-global-color-<id>`) on the Kit, and widgets read their colors from those variables. The plugin keeps the light values as they are and, when the page is in dark mode, overrides the same variables:

```css
html[data-dmt-theme="dark"] body,
html[data-dmt-theme="dark"] .elementor-kit-<id> {
  --e-global-color-primary: #0DC167;
  /* ...your dark values... */
}
```

The mode is stored as an attribute on the root element: `html[data-dmt-theme="light|dark"]`.

**Mode priority:** saved visitor choice, then device theme (`prefers-color-scheme`), then light.

## For developers

```js
// Event fired on every mode change
document.addEventListener('themechange', function (e) {
  console.log(e.detail.theme); // "light" or "dark"
});

// API
window.DMT.get();        // "light" | "dark"
window.DMT.set('dark');  // also saves the choice
window.DMT.toggle();
```

Handy for custom animations (GSAP, canvas): listen for `themechange`, or read colors with `getComputedStyle(document.documentElement)` so they follow the CSS variables.

## Limitations and tips

- **Only colors linked to a Global Color change.** In Elementor, link the site Background, widget colors and borders to Global Colors instead of typing hex values. Hard-coded colors, gradients, images, logos, maps and third-party plugin UI need the **Extra dark mode CSS** field.
- **Edit Global Colors, do not delete them.** A re-created color gets a new ID, so its dark value and any copied `var()` stop matching.
- **Missing tokens.** If you want dark values for things like badge backgrounds or alert backgrounds, add them as Custom Global Colors in Elementor first. They will then appear in the Colors tab.
- **Caching / optimization plugins.** If you see a light flash before dark mode, exclude the inline script containing `__DMT` from Delay JS / Combine JS / minify (WP Rocket, LiteSpeed, and similar).
- **Elementor editor.** The plugin does not apply dark mode inside the Elementor editor preview, so editing is not affected.
- **Contrast.** The plugin does not check color contrast. Check your light and dark text/background pairs yourself (aim for 4.5:1 for body text).

## File structure

```
dmt-elementor/
├── dmt-elementor.php            # plugin header, loader
├── uninstall.php                # removes the saved settings
├── includes/
│   ├── class-dmt-colors.php     # reads Global Colors from the active Elementor Kit
│   ├── class-dmt-settings.php   # option storage, validation, defaults
│   ├── class-dmt-frontend.php   # head script, dark CSS, shortcode, toggle markup
│   └── class-dmt-admin.php      # settings page (Colors, Toggle Design, General, Advanced)
└── assets/
    ├── css/toggle.css           # toggle and transition styles
    ├── css/admin.css            # settings page styles
    ├── js/toggle.js             # front-end behavior
    └── js/admin.js              # settings page behavior and live preview
```

## Uninstall

Deleting the plugin removes its saved settings (`dmt_settings` option). Your Elementor colors are never modified.

## Changelog

### 1.0.1
- Fix: in the Switch style the active Sun/Moon icon was the same color as the thumb and not visible. The thumb now carries the active icon in a contrasting color.

### 1.0.0
- First release.

## Author

Sakibur Rahman
