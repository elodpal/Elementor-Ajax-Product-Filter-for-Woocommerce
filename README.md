# Elementor Ajax Product Filter for WooCommerce

A lightweight Elementor widget that adds AJAX product filtering (by product categories) to WooCommerce shop loops. Keeps Elementor loop markup and inline styles when rendering via Elementor templates.

## Features
- AJAX filtering by selected product categories
- Preserves Elementor loop markup and inline styles (supports Elementor loop templates)
- Optional reset link to restore initial product grid
- Style options in the widget: box background, border, radius, padding, colors
- Elementor typography controls for filters, title and reset link (use global presets)

## Requirements
- WordPress 5.0+
- WooCommerce
- Elementor

## Installation
1. Place the `product-filter` folder into `wp-content/plugins/`.
2. Activate the plugin from Plugins → Installed Plugins.
3. In Elementor, add the `Product Filter` widget to a sidebar/column and configure:
   - Select product categories to show as filter checkboxes
   - Set the loop container selector (defaults to `.elementor-loop-container`)
   - (Optional) Set an Elementor Loop Template to render product cards
   - Configure styling and typography in the Style panel

## Usage
- The widget sends selected category IDs to an AJAX endpoint which returns either the rendered Elementor loop template or standard WooCommerce `li.product` items.
- The frontend script will inject returned `<style>` tags and replace or append loop items into the target container.
- Use the reset link (toggleable in settings) to clear filters and restore the initial grid state.

## Files
- `product-filter-plugin.php` — plugin bootstrap and AJAX handlers
- `includes/class-product-filter-widget.php` — Elementor widget implementation and controls
- `assets/js/product-filter.js` — frontend AJAX and DOM update logic
- `assets/css/product-filter.css` — minimal CSS (styling controlled by widget settings)

## Development
- To push changes: commit and push to your repository (the plugin is already set up to push to GitHub in this workspace).

## Troubleshooting
- If product cards lose styling after filtering, ensure your container selector points at the correct `.elementor-loop-container` and that any Elementor template ID is provided.
- If the widget styling doesn't appear, check for theme CSS specificity; widget uses scoped styles to avoid conflicts.

## License
Add a license file if you want to publish this plugin publicly.

---
_Generated from local workspace on your request._
