# Glidara feature status

Glidara's product promise is: **a fast, responsive WordPress slider builder for images, products, posts, and promotions—without slowing down your website.**

This document labels features honestly. “Shipped” means available in 2.2.2. “Foundation” means a useful first implementation exists and will be expanded. “Roadmap” is not advertised as available.

## Free 2.2.2

### Shipped

- Unlimited sliders and slides (including JSON migration), draft/published status, media-library images, drag-and-drop ordering, duplicate slides and sliders
- Separate image title, description, caption and alt text fields; Media Library metadata is filled automatically without overwriting existing copy
- Image, text, video URL, HTML and shortcode slide content
- Button text, URL and same/new-window target; alt text and responsive WordPress image output
- Standard, full-width, carousel, thumbnail, logo and testimonial presentation modes
- Desktop, tablet and mobile preview; mobile height; cover, contain and original image fit; device visibility; swipe and touch
- Arrows, dots/thumbnails, hover navigation, keyboard and mouse-wheel controls
- Slide/fade plus visual transition presets, autoplay, timing, pause on hover, loop, stop on last and random start
- Width, height, caption placement/background, spacing, radius, shadow, color, gradient, overlay, alignment and basic custom CSS
- Dedicated Slides, Slider Settings and Publish editor tabs, with responsive carousel counts and clearer grouped controls
- Dot, number and thumbnail pagination; circle, square and minimal arrows; configurable start slide and heading semantics
- Gutenberg block, shortcode, PHP helper, Classic Editor button and widget
- Lazy loading, native dimensions/srcset through WordPress, WebP/AVIF compatibility, reduced-motion support, minified frontend assets and conditional loading
- JSON import/export, one-click settings reset, system information, debug preference and uninstall data retention
- Eight starter templates and a slider health summary

### Foundation

- Carousel item controls currently use adaptive equal-width cards; advanced variable-width/multi-row behavior is Pro roadmap work.
- The health checker currently audits empty slides and alt text; performance scoring and deeper accessibility tests are roadmap work.

## Pro 2.2.2

### Shipped or foundation

- Visual layer editor for headings, text, buttons, images and icons with per-device hiding
- Dynamic posts, custom post types, taxonomy terms and include/exclude IDs
- WooCommerce featured, sale and best-selling sources with product price output
- Start/expiry scheduling and logged-in/logged-out audience visibility
- Slider/slide impressions, CTA clicks, CTR and CSV export
- Version history and restore; layer-aware Pro import/export
- Pro Settings link on the Plugins screen

### Roadmap

- Full timeline, grouping, locking, snapping, undo/redo and expanded layer types
- Self-hosted and social video providers with playback orchestration
- Advanced carousel, targeting, personalization, native builder widgets and third-party field integrations
- Full analytics dashboard, external analytics events, permissions, multisite and white label
- Cloud templates and AI-assisted slider creation

## Release sequence

- **2.2.x:** stability, responsive editing, accessibility, migration and health checks
- **2.3:** deeper Pro layer builder, video and WooCommerce controls
- **2.4:** conversion analytics dashboard and conditional visibility
- **3.0:** AI-assisted creation and cloud template library
