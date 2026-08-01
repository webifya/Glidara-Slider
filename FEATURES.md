# Glidara feature status

Glidara's product promise is: **a fast, responsive WordPress slider builder for images, products, posts, and promotions—without slowing down your website.**

This document labels features honestly. “Shipped” means available in 2.7.0. “Foundation” means a useful first implementation exists and will be expanded. “Roadmap” is not advertised as available.

## Free 2.7.0

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
- Eight starter templates and Health Checker 2.0 with scoring for alt text, oversized source images, incomplete CTAs, empty slides, long sliders and autoplay controls

### Foundation

- Carousel item controls currently use adaptive equal-width cards; advanced variable-width/multi-row behavior is Pro roadmap work.
- Health Checker 2.0 is advisory and local; automated contrast measurement and browser-based overflow detection remain roadmap work.

## Pro 2.7.0

### Shipped or foundation

- Drag-and-resize visual layer canvas with 5% snapping, locking, z-index and undo/redo
- Heading, text, button, image, icon, SVG, shape, video and safe HTML layers with per-device hiding
- Fade, slide-up, slide-left and zoom entrances with per-layer delays
- Self-hosted MP4, poster image, autoplay, mute, loop and background-video controls
- Centered, partial-preview, ticker, free-scroll and equal-height carousel controls
- Dynamic posts, custom post types, taxonomy terms and include/exclude IDs
- WooCommerce featured, sale and best-selling sources with product price output
- Start/expiry scheduling and logged-in/logged-out audience visibility
- Slider/slide impressions, CTA clicks, CTR and CSV export
- Version history and restore; layer-aware Pro import/export
- Pro Settings link on the Plugins screen

### Roadmap

- Full multi-track timeline, grouping, copy/paste and device-specific layer coordinates
- Social video start/end times and playback orchestration
- Variable-width/multi-row carousel, targeting, personalization, native builder widgets and third-party field integrations
- Full analytics dashboard, external analytics events, permissions, multisite and white label
- Cloud templates and AI-assisted slider creation

## Release sequence

- **2.5:** Health Checker 2.0 plus Pro visual canvas, enhanced video and carousel controls
- **2.7:** deeper WooCommerce presentation, scheduling and builder integrations
- **2.9:** conversion analytics dashboard and conditional visibility
- **3.0:** AI-assisted creation and cloud template library
