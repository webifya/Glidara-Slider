<?php

defined( 'ABSPATH' ) || exit;

final class Glidara_Slider_Plugin {
	private static $instance;

	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct() {
		add_action( 'init', array( $this, 'register_slider_type' ) );
		add_action( 'add_meta_boxes', array( $this, 'add_meta_boxes' ) );
		add_action( 'save_post_glidara_slider', array( $this, 'save_slider' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'admin_assets' ) );
		add_action( 'admin_menu', array( $this, 'admin_menu' ) );
		add_action( 'admin_post_glidara_slider_reset_settings', array( $this, 'reset_settings' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'register_frontend_assets' ) );
		add_shortcode( 'glidara_slider', array( $this, 'shortcode' ) );
		add_filter( 'manage_glidara_slider_posts_columns', array( $this, 'columns' ) );
		add_action( 'manage_glidara_slider_posts_custom_column', array( $this, 'column_content' ), 10, 2 );
	}

	public function admin_menu() {
		add_submenu_page( 'options-general.php', __( 'Glidara Slider Pro', 'glidara-slider' ), __( 'Glidara Slider — Go Pro', 'glidara-slider' ), 'manage_options', 'glidara-slider-go-pro', array( $this, 'go_pro_page' ) );
	}

	public function go_pro_page() {
		if ( ! current_user_can( 'manage_options' ) ) return;
		$is_pro = class_exists( 'Glidara_Slider_Pro' );
		$purchase_url = esc_url( apply_filters( 'glidara_slider_pro_url', 'https://webninjallc.com/plugins/glidara' ) );
		?>
		<div class="wrap glidara-pro-page">
			<div class="glidara-brand"><span class="glidara-brand__mark">G</span><div><h1><?php esc_html_e( 'Glidara Slider Pro', 'glidara-slider' ); ?></h1><p><?php esc_html_e( 'Build dynamic, measurable sliders without turning your site into a heavy design suite.', 'glidara-slider' ); ?></p></div></div>
			<div class="glidara-pro-hero"><div><span class="glidara-eyebrow"><?php esc_html_e( 'Professional toolkit', 'glidara-slider' ); ?></span><h2><?php esc_html_e( 'More dynamic content. Better insight. Safer iteration.', 'glidara-slider' ); ?></h2><p><?php esc_html_e( 'Connect posts, products and taxonomies, measure CTA performance, export your work and restore earlier versions.', 'glidara-slider' ); ?></p><div class="glidara-price"><strong>$19.99</strong><span><?php esc_html_e( 'per year', 'glidara-slider' ); ?></span></div><?php if ( $is_pro ) : ?><span class="glidara-status is-active"><?php esc_html_e( 'Pro is active', 'glidara-slider' ); ?></span><?php elseif ( $purchase_url ) : ?><a class="button button-primary button-hero" href="<?php echo esc_url( $purchase_url ); ?>" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'Get Glidara Pro', 'glidara-slider' ); ?></a><?php else : ?><span class="glidara-status"><?php esc_html_e( 'Purchase link will be available at launch', 'glidara-slider' ); ?></span><?php endif; ?></div><div class="glidara-pro-preview"><span></span><span></span><span></span><div><strong>12.4K</strong><small><?php esc_html_e( 'Impressions', 'glidara-slider' ); ?></small></div><div><strong>8.7%</strong><small><?php esc_html_e( 'CTR', 'glidara-slider' ); ?></small></div></div></div>
			<div class="glidara-feature-grid"><?php foreach ( array( 'Dynamic Posts' => 'Build sliders from posts, custom post types and taxonomies.', 'WooCommerce' => 'Show products, prices and product-category content.', 'Built-in Analytics' => 'Track impressions, CTA clicks and per-slide performance.', 'Version History' => 'Keep twenty snapshots and restore earlier slider designs.', 'Import & Export' => 'Move complete sliders safely with versioned JSON.', 'Priority Roadmap' => 'Advanced layers, motion timeline and premium templates.' ) as $title => $description ) : ?><article><span class="dashicons dashicons-yes-alt"></span><h3><?php echo esc_html( $title ); ?></h3><p><?php echo esc_html( $description ); ?></p></article><?php endforeach; ?></div>
		</div>
		<?php
	}

	public function register_slider_type() {
		register_post_type(
			'glidara_slider',
			array(
				'labels' => array(
					'name'          => __( 'Glidara Slider', 'glidara-slider' ),
					'singular_name' => __( 'Slider', 'glidara-slider' ),
					'menu_name'     => __( 'Glidara Slider', 'glidara-slider' ),
					'all_items'     => __( 'Glidara Slider', 'glidara-slider' ),
					'add_new_item'  => __( 'Add New Slider', 'glidara-slider' ),
					'edit_item'     => __( 'Edit Slider', 'glidara-slider' ),
				),
				'public'       => false,
				'show_ui'      => true,
				'show_in_menu' => 'options-general.php',
				'menu_icon'    => 'dashicons-images-alt2',
				'supports'     => array( 'title', 'revisions' ),
				'show_in_rest' => true,
			)
		);
	}

	public function add_meta_boxes() {
		add_meta_box( 'glidara-slider-builder', __( 'Slides', 'glidara-slider' ), array( $this, 'builder_box' ), 'glidara_slider', 'normal', 'high' );
	}

	public function builder_box( $post ) {
		wp_nonce_field( 'glidara_slider_save', 'glidara_slider_nonce' );
		$slides = get_post_meta( $post->ID, '_glidara_slider_slides', true );
		$slides = is_array( $slides ) ? $slides : array();
		?>
		<div id="glidara-slider-builder" data-next-index="<?php echo esc_attr( count( $slides ) ); ?>">
			<div class="glidara-builder-hero"><div class="glidara-brand"><span class="glidara-brand__mark">G</span><div><strong><?php esc_html_e( 'Glidara', 'glidara-slider' ); ?></strong><span><?php esc_html_e( 'Visual Slider Builder', 'glidara-slider' ); ?></span></div></div><div class="glidara-save-hint"><span class="dashicons dashicons-saved"></span><?php esc_html_e( 'Your design is saved with the WordPress Update button.', 'glidara-slider' ); ?></div></div>
			<nav class="glidara-editor-tabs" aria-label="<?php esc_attr_e( 'Slider editor sections', 'glidara-slider' ); ?>"><button type="button" class="glidara-editor-tab is-active" data-tab="slides" aria-selected="true"><span class="dashicons dashicons-images-alt2"></span><?php esc_html_e( 'Slides', 'glidara-slider' ); ?></button><button type="button" class="glidara-editor-tab" data-tab="settings" aria-selected="false"><span class="dashicons dashicons-admin-generic"></span><?php esc_html_e( 'Slider Settings', 'glidara-slider' ); ?></button><button type="button" class="glidara-editor-tab" data-tab="publish" aria-selected="false"><span class="dashicons dashicons-share"></span><?php esc_html_e( 'Publish', 'glidara-slider' ); ?></button></nav>
			<section class="glidara-editor-panel is-active" data-panel="slides">
			<div class="glidara-slider-toolbar"><p><?php esc_html_e( 'Drag slides to reorder them. Changes appear in the preview immediately.', 'glidara-slider' ); ?></p><div class="glidara-editor-health" aria-live="polite"><span class="dashicons dashicons-heart"></span><strong>100</strong><small><?php esc_html_e( 'Slider health', 'glidara-slider' ); ?></small></div><div><strong><?php esc_html_e( 'Preview:', 'glidara-slider' ); ?></strong> <button type="button" class="button glidara-slider-device is-active" data-device="desktop"><?php esc_html_e( 'Desktop', 'glidara-slider' ); ?></button> <button type="button" class="button glidara-slider-device" data-device="tablet"><?php esc_html_e( 'Tablet', 'glidara-slider' ); ?></button> <button type="button" class="button glidara-slider-device" data-device="mobile"><?php esc_html_e( 'Mobile', 'glidara-slider' ); ?></button></div></div>
			<div class="glidara-slider-slides">
				<?php foreach ( $slides as $index => $slide ) : ?>
					<?php $this->slide_editor( $index, $slide ); ?>
				<?php endforeach; ?>
			</div>
			<button type="button" class="button button-primary glidara-slider-add"><?php esc_html_e( 'Add Slide', 'glidara-slider' ); ?></button>
			<select class="glidara-slider-template"><option value=""><?php esc_html_e( 'Insert starter template…', 'glidara-slider' ); ?></option><?php foreach ( array( 'hero' => 'Hero Slider', 'gallery' => 'Image Gallery', 'business' => 'Business Slider', 'portfolio' => 'Portfolio Slider', 'logo' => 'Logo Carousel', 'testimonial' => 'Testimonial', 'promo' => 'Promotion', 'editorial' => 'Editorial', 'saas' => 'SaaS Launch', 'restaurant' => 'Restaurant', 'realestate' => 'Real Estate', 'event' => 'Event Promotion' ) as $value => $label ) : ?><option value="<?php echo esc_attr( $value ); ?>"><?php echo esc_html( $label ); ?></option><?php endforeach; ?></select>
			<div class="glidara-slider-preview-wrap" data-device="desktop"><div class="glidara-slider-preview" aria-live="polite"></div></div>
			</section>
			<section class="glidara-editor-panel" data-panel="settings"><?php $this->settings_box( $post ); ?></section>
			<section class="glidara-editor-panel" data-panel="publish"><div class="glidara-publish-grid"><article><span class="dashicons dashicons-shortcode"></span><h3><?php esc_html_e( 'Shortcode', 'glidara-slider' ); ?></h3><code>[glidara_slider id="<?php echo (int) $post->ID; ?>"]</code><button type="button" class="button glidara-copy-code" data-copy='[glidara_slider id="<?php echo (int) $post->ID; ?>"]'><?php esc_html_e( 'Copy shortcode', 'glidara-slider' ); ?></button></article><article><span class="dashicons dashicons-block-default"></span><h3><?php esc_html_e( 'Gutenberg', 'glidara-slider' ); ?></h3><p><?php esc_html_e( 'Add the Glidara Slider block and select this slider.', 'glidara-slider' ); ?></p></article><article><span class="dashicons dashicons-editor-code"></span><h3><?php esc_html_e( 'Theme template', 'glidara-slider' ); ?></h3><code>glidara_slider( <?php echo (int) $post->ID; ?> );</code></article></div></section>
		</div>
		<script type="text/html" id="tmpl-glidara-slider-slide"><?php $this->slide_editor( '{{data.index}}', array() ); ?></script>
		<script type="text/html" id="tmpl-glidara-slider-layer"><?php $this->layer_editor( '{{data.slide}}', '{{data.layer}}', array() ); ?></script>
		<?php
	}

	private function slide_editor( $index, $slide ) {
		$slide = wp_parse_args( $slide, array( 'uid' => wp_generate_uuid4(), 'type' => 'image', 'title' => '', 'content' => '', 'caption' => '', 'image' => '', 'image_id' => 0, 'image_alt' => '', 'video' => '', 'button_text' => '', 'button_url' => '', 'button_target' => '_self', 'background' => '#141525', 'overlay_opacity' => 55, 'align' => 'left', 'hide_mobile' => 0, 'layers' => array() ) );
		$name  = 'glidara_slider_slides[' . $index . ']';
		?>
		<div class="glidara-slider-slide">
			<input type="hidden" name="<?php echo esc_attr( $name ); ?>[uid]" value="<?php echo esc_attr( $slide['uid'] ); ?>">
			<div class="glidara-slider-handle"><span class="dashicons dashicons-move"></span> <?php esc_html_e( 'Slide', 'glidara-slider' ); ?> <button type="button" class="button-link glidara-slider-duplicate"><?php esc_html_e( 'Duplicate', 'glidara-slider' ); ?></button><button type="button" class="button-link-delete glidara-slider-remove"><?php esc_html_e( 'Remove', 'glidara-slider' ); ?></button></div>
			<div class="glidara-slider-fields">
				<label><?php esc_html_e( 'Type', 'glidara-slider' ); ?>
					<select name="<?php echo esc_attr( $name ); ?>[type]">
						<?php foreach ( array( 'image' => 'Image', 'text' => 'Text', 'video' => 'YouTube/Vimeo', 'html' => 'HTML', 'shortcode' => 'Shortcode' ) as $value => $label ) : ?>
							<option value="<?php echo esc_attr( $value ); ?>" <?php selected( $slide['type'], $value ); ?>><?php echo esc_html( $label ); ?></option>
						<?php endforeach; ?>
					</select>
				</label>
				<label><?php esc_html_e( 'Image title / heading', 'glidara-slider' ); ?><input type="text" name="<?php echo esc_attr( $name ); ?>[title]" value="<?php echo esc_attr( $slide['title'] ); ?>"></label>
				<label><?php esc_html_e( 'Image description / content', 'glidara-slider' ); ?><textarea name="<?php echo esc_attr( $name ); ?>[content]" rows="4"><?php echo esc_textarea( $slide['content'] ); ?></textarea></label>
				<label><?php esc_html_e( 'Image caption', 'glidara-slider' ); ?><input type="text" name="<?php echo esc_attr( $name ); ?>[caption]" value="<?php echo esc_attr( $slide['caption'] ); ?>"></label>
				<label><?php esc_html_e( 'Image', 'glidara-slider' ); ?><span class="glidara-slider-media-row"><input class="glidara-slider-image-url" type="url" name="<?php echo esc_attr( $name ); ?>[image]" value="<?php echo esc_url( $slide['image'] ); ?>"><input class="glidara-slider-image-id" type="hidden" name="<?php echo esc_attr( $name ); ?>[image_id]" value="<?php echo absint( $slide['image_id'] ); ?>"><button type="button" class="button glidara-slider-media"><?php esc_html_e( 'Choose', 'glidara-slider' ); ?></button></span></label>
				<label><?php esc_html_e( 'Image alt text', 'glidara-slider' ); ?><input type="text" name="<?php echo esc_attr( $name ); ?>[image_alt]" value="<?php echo esc_attr( $slide['image_alt'] ); ?>"></label>
				<label><?php esc_html_e( 'Video URL', 'glidara-slider' ); ?><input type="url" name="<?php echo esc_attr( $name ); ?>[video]" value="<?php echo esc_url( $slide['video'] ); ?>"></label>
				<?php do_action( 'glidara_slider_slide_fields', $index, $slide, $name ); ?>
				<label><?php esc_html_e( 'Button text', 'glidara-slider' ); ?><input type="text" name="<?php echo esc_attr( $name ); ?>[button_text]" value="<?php echo esc_attr( $slide['button_text'] ); ?>"></label>
				<label><?php esc_html_e( 'Button URL', 'glidara-slider' ); ?><input type="url" name="<?php echo esc_attr( $name ); ?>[button_url]" value="<?php echo esc_url( $slide['button_url'] ); ?>"></label>
				<label><?php esc_html_e( 'Open button link', 'glidara-slider' ); ?><select name="<?php echo esc_attr( $name ); ?>[button_target]"><option value="_self" <?php selected( $slide['button_target'], '_self' ); ?>><?php esc_html_e( 'Same window', 'glidara-slider' ); ?></option><option value="_blank" <?php selected( $slide['button_target'], '_blank' ); ?>><?php esc_html_e( 'New window', 'glidara-slider' ); ?></option></select></label>
				<fieldset class="glidara-slider-design-fields"><legend><?php esc_html_e( 'Slide design', 'glidara-slider' ); ?></legend><label><?php esc_html_e( 'Background', 'glidara-slider' ); ?><input type="color" name="<?php echo esc_attr( $name ); ?>[background]" value="<?php echo esc_attr( $slide['background'] ); ?>"></label><label><?php esc_html_e( 'Overlay opacity', 'glidara-slider' ); ?><input type="range" min="0" max="100" name="<?php echo esc_attr( $name ); ?>[overlay_opacity]" value="<?php echo absint( $slide['overlay_opacity'] ); ?>"></label><label><?php esc_html_e( 'Alignment', 'glidara-slider' ); ?><select name="<?php echo esc_attr( $name ); ?>[align]"><?php foreach ( array( 'left', 'center', 'right' ) as $align ) : ?><option value="<?php echo esc_attr( $align ); ?>" <?php selected( $slide['align'], $align ); ?>><?php echo esc_html( ucfirst( $align ) ); ?></option><?php endforeach; ?></select></label><label><input type="checkbox" name="<?php echo esc_attr( $name ); ?>[hide_mobile]" value="1" <?php checked( $slide['hide_mobile'] ); ?>><?php esc_html_e( 'Hide content on mobile', 'glidara-slider' ); ?></label></fieldset>
				<?php if ( apply_filters( 'glidara_slider_layers_enabled', false ) ) : ?><fieldset class="glidara-slider-layer-builder"><legend><?php esc_html_e( 'Pro Layers', 'glidara-slider' ); ?></legend><div class="glidara-slider-layers" data-next-layer="<?php echo absint( count( $slide['layers'] ) ); ?>"><?php foreach ( (array) $slide['layers'] as $layer_index => $layer ) $this->layer_editor( $index, $layer_index, $layer ); ?></div><button type="button" class="button glidara-slider-add-layer"><?php esc_html_e( 'Add Layer', 'glidara-slider' ); ?></button></fieldset><?php endif; ?>
			</div>
		</div>
		<?php
	}

	private function layer_editor( $slide_index, $layer_index, $layer ) {
		$layer = wp_parse_args( $layer, array( 'uid' => wp_generate_uuid4(), 'type' => 'text', 'content' => '', 'url' => '', 'image' => '', 'x' => 10, 'y' => 10, 'width' => 50, 'size' => 24, 'z' => 3, 'color' => '#ffffff', 'animation' => 'none', 'delay' => 0, 'locked' => 0, 'hide_tablet' => 0, 'hide_mobile' => 0 ) );
		$name = 'glidara_slider_slides[' . $slide_index . '][layers][' . $layer_index . ']';
		?>
		<div class="glidara-slider-layer">
			<div class="glidara-slider-layer-handle"><span class="dashicons dashicons-move"></span> <?php esc_html_e( 'Layer', 'glidara-slider' ); ?> <button type="button" class="button-link-delete glidara-slider-remove-layer"><?php esc_html_e( 'Remove', 'glidara-slider' ); ?></button></div>
			<input type="hidden" name="<?php echo esc_attr( $name ); ?>[uid]" value="<?php echo esc_attr( $layer['uid'] ); ?>">
			<label><?php esc_html_e( 'Type', 'glidara-slider' ); ?><select name="<?php echo esc_attr( $name ); ?>[type]"><?php foreach ( array( 'heading', 'text', 'button', 'image', 'icon', 'svg', 'shape', 'video', 'html' ) as $type ) : ?><option value="<?php echo esc_attr( $type ); ?>" <?php selected( $layer['type'], $type ); ?>><?php echo esc_html( ucfirst( $type ) ); ?></option><?php endforeach; ?></select></label>
			<label><?php esc_html_e( 'Content', 'glidara-slider' ); ?><input type="text" name="<?php echo esc_attr( $name ); ?>[content]" value="<?php echo esc_attr( $layer['content'] ); ?>"></label>
			<label><?php esc_html_e( 'Link URL', 'glidara-slider' ); ?><input type="url" name="<?php echo esc_attr( $name ); ?>[url]" value="<?php echo esc_url( $layer['url'] ); ?>"></label>
			<label><?php esc_html_e( 'Image URL', 'glidara-slider' ); ?><input type="url" name="<?php echo esc_attr( $name ); ?>[image]" value="<?php echo esc_url( $layer['image'] ); ?>"></label>
			<label>X %<input type="number" min="0" max="100" name="<?php echo esc_attr( $name ); ?>[x]" value="<?php echo absint( $layer['x'] ); ?>"></label>
			<label>Y %<input type="number" min="0" max="100" name="<?php echo esc_attr( $name ); ?>[y]" value="<?php echo absint( $layer['y'] ); ?>"></label>
			<label><?php esc_html_e( 'Width %', 'glidara-slider' ); ?><input type="number" min="5" max="100" name="<?php echo esc_attr( $name ); ?>[width]" value="<?php echo absint( $layer['width'] ); ?>"></label>
			<label><?php esc_html_e( 'Size px', 'glidara-slider' ); ?><input type="number" min="8" max="200" name="<?php echo esc_attr( $name ); ?>[size]" value="<?php echo absint( $layer['size'] ); ?>"></label>
			<label><?php esc_html_e( 'Z-index', 'glidara-slider' ); ?><input type="number" min="1" max="100" name="<?php echo esc_attr( $name ); ?>[z]" value="<?php echo absint( $layer['z'] ); ?>"></label>
			<label><?php esc_html_e( 'Color', 'glidara-slider' ); ?><input type="color" name="<?php echo esc_attr( $name ); ?>[color]" value="<?php echo esc_attr( $layer['color'] ); ?>"></label>
			<label><?php esc_html_e( 'Entrance', 'glidara-slider' ); ?><select name="<?php echo esc_attr( $name ); ?>[animation]"><?php foreach ( array( 'none', 'fade', 'slide-up', 'slide-left', 'zoom' ) as $animation ) : ?><option value="<?php echo esc_attr( $animation ); ?>" <?php selected( $layer['animation'], $animation ); ?>><?php echo esc_html( ucfirst( str_replace( '-', ' ', $animation ) ) ); ?></option><?php endforeach; ?></select></label>
			<label><?php esc_html_e( 'Delay ms', 'glidara-slider' ); ?><input type="number" min="0" max="10000" step="50" name="<?php echo esc_attr( $name ); ?>[delay]" value="<?php echo absint( $layer['delay'] ); ?>"></label>
			<label><input type="checkbox" name="<?php echo esc_attr( $name ); ?>[locked]" value="1" <?php checked( $layer['locked'] ); ?>><?php esc_html_e( 'Lock on canvas', 'glidara-slider' ); ?></label>
			<label><input type="checkbox" name="<?php echo esc_attr( $name ); ?>[hide_tablet]" value="1" <?php checked( $layer['hide_tablet'] ); ?>><?php esc_html_e( 'Hide tablet', 'glidara-slider' ); ?></label>
			<label><input type="checkbox" name="<?php echo esc_attr( $name ); ?>[hide_mobile]" value="1" <?php checked( $layer['hide_mobile'] ); ?>><?php esc_html_e( 'Hide mobile', 'glidara-slider' ); ?></label>
		</div>
		<?php
	}

	public function settings_box( $post ) {
		$settings = wp_parse_args( (array) get_post_meta( $post->ID, '_glidara_slider_settings', true ), self::defaults() );
		?>
		<div class="glidara-settings-intro"><div><span class="dashicons dashicons-admin-generic"></span></div><h2><?php esc_html_e( 'Slider Settings', 'glidara-slider' ); ?></h2><p><?php esc_html_e( 'Control layout, motion, navigation, responsive behavior and styling without leaving the editor.', 'glidara-slider' ); ?></p><a class="button glidara-reset-settings" href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin-post.php?action=glidara_slider_reset_settings&id=' . absint( $post->ID ) ), 'glidara_reset_settings_' . absint( $post->ID ) ) ); ?>" onclick="return confirm('<?php echo esc_js( __( 'Reset all slider settings to their defaults?', 'glidara-slider' ) ); ?>')"><?php esc_html_e( 'Reset settings', 'glidara-slider' ); ?></a></div>
		<div class="glidara-settings-grid">
			<section class="glidara-settings-card"><h3><?php esc_html_e( 'Layout & responsive', 'glidara-slider' ); ?></h3>
				<label><?php esc_html_e( 'Slider type', 'glidara-slider' ); ?><select name="glidara_slider_settings[layout_type]"><?php foreach ( array( 'standard' => 'Standard image slider', 'carousel' => 'Image carousel', 'thumbnails' => 'Thumbnail slider', 'logo' => 'Logo carousel', 'testimonial' => 'Testimonial slider' ) as $value => $label ) : ?><option value="<?php echo esc_attr( $value ); ?>" <?php selected( $settings['layout_type'], $value ); ?>><?php echo esc_html( $label ); ?></option><?php endforeach; ?></select></label>
				<label><?php esc_html_e( 'Width mode', 'glidara-slider' ); ?><select name="glidara_slider_settings[layout]"><option value="full" <?php selected( $settings['layout'], 'full' ); ?>><?php esc_html_e( 'Full width', 'glidara-slider' ); ?></option><option value="boxed" <?php selected( $settings['layout'], 'boxed' ); ?>><?php esc_html_e( 'Boxed', 'glidara-slider' ); ?></option></select></label>
				<div class="glidara-field-row"><label><?php esc_html_e( 'Desktop items', 'glidara-slider' ); ?><input type="number" min="1" max="6" name="glidara_slider_settings[slides_per_view]" value="<?php echo absint( $settings['slides_per_view'] ); ?>"></label><label><?php esc_html_e( 'Tablet items', 'glidara-slider' ); ?><input type="number" min="1" max="4" name="glidara_slider_settings[tablet_slides]" value="<?php echo absint( $settings['tablet_slides'] ); ?>"></label><label><?php esc_html_e( 'Mobile items', 'glidara-slider' ); ?><input type="number" min="1" max="2" name="glidara_slider_settings[mobile_slides]" value="<?php echo absint( $settings['mobile_slides'] ); ?>"></label></div>
				<div class="glidara-field-row"><label><?php esc_html_e( 'Height (px)', 'glidara-slider' ); ?><input type="number" min="100" name="glidara_slider_settings[height]" value="<?php echo esc_attr( $settings['height'] ); ?>" placeholder="Auto"></label><label><?php esc_html_e( 'Mobile height', 'glidara-slider' ); ?><input type="number" min="180" name="glidara_slider_settings[mobile_height]" value="<?php echo esc_attr( $settings['mobile_height'] ); ?>"></label><label><?php esc_html_e( 'Image fit', 'glidara-slider' ); ?><select name="glidara_slider_settings[image_fit]"><?php foreach ( array( 'cover', 'contain', 'original' ) as $fit ) : ?><option value="<?php echo esc_attr( $fit ); ?>" <?php selected( $settings['image_fit'], $fit ); ?>><?php echo esc_html( ucfirst( $fit ) ); ?></option><?php endforeach; ?></select></label></div>
				<div class="glidara-check-row"><?php foreach ( array( 'auto_height' => 'Auto height', 'hide_desktop' => 'Hide desktop', 'hide_tablet' => 'Hide tablet', 'hide_mobile' => 'Hide mobile' ) as $key => $label ) : ?><label><input type="checkbox" name="glidara_slider_settings[<?php echo esc_attr( $key ); ?>]" value="1" <?php checked( $settings[ $key ] ); ?>><?php echo esc_html( $label ); ?></label><?php endforeach; ?></div>
			</section>
			<section class="glidara-settings-card"><h3><?php esc_html_e( 'Motion & playback', 'glidara-slider' ); ?></h3>
				<div class="glidara-field-row"><label><?php esc_html_e( 'Animation', 'glidara-slider' ); ?><select name="glidara_slider_settings[animation]"><?php foreach ( array( 'slide', 'fade', 'zoom', 'flip', 'cube', 'coverflow' ) as $animation ) : ?><option value="<?php echo esc_attr( $animation ); ?>" <?php selected( $settings['animation'], $animation ); ?>><?php echo esc_html( ucfirst( $animation ) ); ?></option><?php endforeach; ?></select></label><label><?php esc_html_e( 'Direction', 'glidara-slider' ); ?><select name="glidara_slider_settings[direction]"><option value="horizontal" <?php selected( $settings['direction'], 'horizontal' ); ?>>Horizontal</option><option value="vertical" <?php selected( $settings['direction'], 'vertical' ); ?>>Vertical</option></select></label><label><?php esc_html_e( 'Start slide', 'glidara-slider' ); ?><input type="number" min="1" name="glidara_slider_settings[start_slide]" value="<?php echo absint( $settings['start_slide'] ); ?>"></label></div>
				<div class="glidara-field-row"><label><?php esc_html_e( 'Autoplay speed (ms)', 'glidara-slider' ); ?><input type="number" min="1000" step="100" name="glidara_slider_settings[duration]" value="<?php echo absint( $settings['duration'] ); ?>"></label><label><?php esc_html_e( 'Transition speed (ms)', 'glidara-slider' ); ?><input type="number" min="0" step="50" name="glidara_slider_settings[speed]" value="<?php echo absint( $settings['speed'] ); ?>"></label></div>
				<div class="glidara-check-row"><?php foreach ( array( 'autoplay' => 'Autoplay', 'pause_hover' => 'Pause on hover', 'loop' => 'Infinite loop', 'stop_last' => 'Stop on last slide', 'random_start' => 'Random start' ) as $key => $label ) : ?><label><input type="checkbox" name="glidara_slider_settings[<?php echo esc_attr( $key ); ?>]" value="1" <?php checked( $settings[ $key ] ); ?>><?php echo esc_html( $label ); ?></label><?php endforeach; ?></div>
			</section>
			<section class="glidara-settings-card"><h3><?php esc_html_e( 'Navigation', 'glidara-slider' ); ?></h3>
				<div class="glidara-field-row"><label><?php esc_html_e( 'Pagination', 'glidara-slider' ); ?><select name="glidara_slider_settings[nav_style]"><?php foreach ( array( 'dots' => 'Dots', 'numbers' => 'Numbers', 'thumbnails' => 'Thumbnails' ) as $value => $label ) : ?><option value="<?php echo esc_attr( $value ); ?>" <?php selected( $settings['nav_style'], $value ); ?>><?php echo esc_html( $label ); ?></option><?php endforeach; ?></select></label><label><?php esc_html_e( 'Arrow style', 'glidara-slider' ); ?><select name="glidara_slider_settings[arrow_style]"><?php foreach ( array( 'circle', 'square', 'minimal' ) as $value ) : ?><option value="<?php echo esc_attr( $value ); ?>" <?php selected( $settings['arrow_style'], $value ); ?>><?php echo esc_html( ucfirst( $value ) ); ?></option><?php endforeach; ?></select></label><label><?php esc_html_e( 'Arrow position', 'glidara-slider' ); ?><select name="glidara_slider_settings[arrow_position]"><option value="inside" <?php selected( $settings['arrow_position'], 'inside' ); ?>>Inside</option><option value="edge" <?php selected( $settings['arrow_position'], 'edge' ); ?>>At edges</option></select></label></div>
				<div class="glidara-check-row"><?php foreach ( array( 'arrows' => 'Arrows', 'dots' => 'Pagination', 'nav_hover' => 'Show on hover', 'keyboard' => 'Keyboard', 'wheel' => 'Mouse wheel' ) as $key => $label ) : ?><label><input type="checkbox" name="glidara_slider_settings[<?php echo esc_attr( $key ); ?>]" value="1" <?php checked( $settings[ $key ] ); ?>><?php echo esc_html( $label ); ?></label><?php endforeach; ?></div>
			</section>
			<section class="glidara-settings-card"><h3><?php esc_html_e( 'Style & accessibility', 'glidara-slider' ); ?></h3>
				<div class="glidara-field-row"><label><?php esc_html_e( 'Accent', 'glidara-slider' ); ?><input type="color" name="glidara_slider_settings[accent]" value="<?php echo esc_attr( $settings['accent'] ); ?>"></label><label><?php esc_html_e( 'Button', 'glidara-slider' ); ?><input type="color" name="glidara_slider_settings[button_color]" value="<?php echo esc_attr( $settings['button_color'] ); ?>"></label><label><?php esc_html_e( 'Text', 'glidara-slider' ); ?><input type="color" name="glidara_slider_settings[text_color]" value="<?php echo esc_attr( $settings['text_color'] ); ?>"></label><label><?php esc_html_e( 'Caption background', 'glidara-slider' ); ?><input type="color" name="glidara_slider_settings[caption_bg]" value="<?php echo esc_attr( $settings['caption_bg'] ); ?>"></label></div>
				<div class="glidara-field-row"><label><?php esc_html_e( 'Caption placement', 'glidara-slider' ); ?><select name="glidara_slider_settings[caption_position]"><?php foreach ( array( 'top', 'center', 'bottom' ) as $position ) : ?><option value="<?php echo esc_attr( $position ); ?>" <?php selected( $settings['caption_position'], $position ); ?>><?php echo esc_html( ucfirst( $position ) ); ?></option><?php endforeach; ?></select></label><label><?php esc_html_e( 'Heading tag', 'glidara-slider' ); ?><select name="glidara_slider_settings[heading_tag]"><?php foreach ( array( 'h2', 'h3', 'p' ) as $tag ) : ?><option value="<?php echo esc_attr( $tag ); ?>" <?php selected( $settings['heading_tag'], $tag ); ?>><?php echo esc_html( strtoupper( $tag ) ); ?></option><?php endforeach; ?></select></label><label><?php esc_html_e( 'Text size', 'glidara-slider' ); ?><input type="number" min="12" max="32" name="glidara_slider_settings[text_size]" value="<?php echo absint( $settings['text_size'] ); ?>"></label><label><?php esc_html_e( 'Maximum width', 'glidara-slider' ); ?><input type="number" min="320" name="glidara_slider_settings[max_width]" value="<?php echo absint( $settings['max_width'] ); ?>"></label><label><?php esc_html_e( 'Radius', 'glidara-slider' ); ?><input type="number" min="0" max="100" name="glidara_slider_settings[radius]" value="<?php echo absint( $settings['radius'] ); ?>"></label></div>
				<div class="glidara-field-row"><label><?php esc_html_e( 'Font family', 'glidara-slider' ); ?><input type="text" name="glidara_slider_settings[font_family]" value="<?php echo esc_attr( $settings['font_family'] ); ?>" placeholder="inherit"></label><label><?php esc_html_e( 'Button radius', 'glidara-slider' ); ?><input type="number" min="0" max="100" name="glidara_slider_settings[button_radius]" value="<?php echo absint( $settings['button_radius'] ); ?>"></label><label><?php esc_html_e( 'Slide gap', 'glidara-slider' ); ?><input type="number" min="0" max="100" name="glidara_slider_settings[spacing]" value="<?php echo absint( $settings['spacing'] ); ?>"></label></div>
				<div class="glidara-check-row"><label><input type="checkbox" name="glidara_slider_settings[shadow]" value="1" <?php checked( $settings['shadow'] ); ?>><?php esc_html_e( 'Slider shadow', 'glidara-slider' ); ?></label></div>
				<label class="glidara-custom-css"><?php esc_html_e( 'Basic custom CSS', 'glidara-slider' ); ?><textarea name="glidara_slider_settings[custom_css]" rows="6" placeholder=".glidara-slider__button { ... }"><?php echo esc_textarea( $settings['custom_css'] ); ?></textarea></label>
			</section>
			<?php do_action( 'glidara_slider_settings_panel', $post, $settings ); ?>
		</div>
		<?php
	}

	private static function defaults() {
		return array( 'autoplay' => 1, 'pause_hover' => 1, 'loop' => 1, 'arrows' => 1, 'dots' => 1, 'keyboard' => 1, 'wheel' => 0, 'auto_height' => 1, 'shadow' => 1, 'nav_hover' => 0, 'stop_last' => 0, 'random_start' => 0, 'hide_desktop' => 0, 'hide_tablet' => 0, 'hide_mobile' => 0, 'animation' => 'slide', 'direction' => 'horizontal', 'duration' => 5200, 'speed' => 650, 'start_slide' => 1, 'height' => '', 'mobile_height' => 360, 'layout' => 'full', 'layout_type' => 'standard', 'slides_per_view' => 1, 'tablet_slides' => 2, 'mobile_slides' => 1, 'image_fit' => 'cover', 'nav_style' => 'dots', 'arrow_style' => 'circle', 'arrow_position' => 'inside', 'caption_position' => 'center', 'max_width' => 1200, 'radius' => 20, 'spacing' => 0, 'accent' => '#7c5cff', 'button_color' => '#7c5cff', 'text_color' => '#ffffff', 'caption_bg' => '#10111a', 'text_size' => 18, 'heading_tag' => 'h2', 'font_family' => 'inherit', 'button_radius' => 12, 'custom_css' => '' );
	}

	public function save_slider( $post_id ) {
		if ( ! isset( $_POST['glidara_slider_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['glidara_slider_nonce'] ) ), 'glidara_slider_save' ) || ! current_user_can( 'edit_post', $post_id ) || wp_is_post_autosave( $post_id ) ) {
			return;
		}
		$slides = array();
		$existing_slides = (array) get_post_meta( $post_id, '_glidara_slider_slides', true );
		foreach ( (array) ( $_POST['glidara_slider_slides'] ?? array() ) as $slide_index => $slide ) {
			$slides[] = array(
				'uid'         => sanitize_key( $slide['uid'] ?? wp_generate_uuid4() ),
				'type'        => sanitize_key( $slide['type'] ?? 'image' ),
				'title'       => sanitize_text_field( wp_unslash( $slide['title'] ?? '' ) ),
				'content'     => wp_kses_post( wp_unslash( $slide['content'] ?? '' ) ),
				'caption'     => sanitize_text_field( wp_unslash( $slide['caption'] ?? '' ) ),
				'image'       => esc_url_raw( wp_unslash( $slide['image'] ?? '' ) ),
				'image_id'    => absint( $slide['image_id'] ?? 0 ),
				'image_alt'   => sanitize_text_field( wp_unslash( $slide['image_alt'] ?? '' ) ),
				'video'       => esc_url_raw( wp_unslash( $slide['video'] ?? '' ) ),
				'button_text' => sanitize_text_field( wp_unslash( $slide['button_text'] ?? '' ) ),
				'button_url'  => esc_url_raw( wp_unslash( $slide['button_url'] ?? '' ) ),
				'button_target' => '_blank' === ( $slide['button_target'] ?? '' ) ? '_blank' : '_self',
				'background'  => sanitize_hex_color( $slide['background'] ?? '#141525' ) ?: '#141525',
				'overlay_opacity' => min( 100, absint( $slide['overlay_opacity'] ?? 55 ) ),
				'align'       => in_array( $slide['align'] ?? '', array( 'left', 'center', 'right' ), true ) ? $slide['align'] : 'left',
				'hide_mobile' => empty( $slide['hide_mobile'] ) ? 0 : 1,
				'layers'      => apply_filters( 'glidara_slider_layers_enabled', false ) ? $this->sanitize_layers( $slide['layers'] ?? array() ) : (array) ( $existing_slides[ $slide_index ]['layers'] ?? array() ),
			);
			$slides[ count( $slides ) - 1 ] = apply_filters( 'glidara_slider_sanitized_slide', $slides[ count( $slides ) - 1 ], $slide, $post_id );
		}
		$raw = (array) ( $_POST['glidara_slider_settings'] ?? array() );
		$settings = self::defaults();
		foreach ( array( 'autoplay', 'pause_hover', 'loop', 'arrows', 'dots', 'keyboard', 'wheel', 'auto_height', 'shadow', 'nav_hover', 'stop_last', 'random_start', 'hide_desktop', 'hide_tablet', 'hide_mobile' ) as $key ) {
			$settings[ $key ] = empty( $raw[ $key ] ) ? 0 : 1;
		}
		$settings['animation'] = in_array( $raw['animation'] ?? '', array( 'slide', 'fade', 'zoom', 'flip', 'cube', 'coverflow' ), true ) ? $raw['animation'] : 'slide';
		$settings['duration'] = max( 1000, absint( $raw['duration'] ?? 5200 ) );
		$settings['speed'] = absint( $raw['speed'] ?? 650 );
		$settings['start_slide'] = max( 1, absint( $raw['start_slide'] ?? 1 ) );
		$settings['height'] = empty( $raw['height'] ) ? '' : max( 100, absint( $raw['height'] ) );
		$settings['layout'] = 'boxed' === ( $raw['layout'] ?? '' ) ? 'boxed' : 'full';
		$settings['layout_type'] = in_array( $raw['layout_type'] ?? '', array( 'standard', 'carousel', 'thumbnails', 'logo', 'testimonial' ), true ) ? $raw['layout_type'] : 'standard';
		$settings['slides_per_view'] = min( 6, max( 1, absint( $raw['slides_per_view'] ?? 1 ) ) );
		$settings['tablet_slides'] = min( 4, max( 1, absint( $raw['tablet_slides'] ?? 2 ) ) );
		$settings['mobile_slides'] = min( 2, max( 1, absint( $raw['mobile_slides'] ?? 1 ) ) );
		$settings['mobile_height'] = max( 180, absint( $raw['mobile_height'] ?? 360 ) );
		$settings['image_fit'] = in_array( $raw['image_fit'] ?? '', array( 'cover', 'contain', 'original' ), true ) ? $raw['image_fit'] : 'cover';
		$settings['direction'] = 'vertical' === ( $raw['direction'] ?? '' ) ? 'vertical' : 'horizontal';
		$settings['nav_style'] = in_array( $raw['nav_style'] ?? '', array( 'dots', 'numbers', 'thumbnails' ), true ) ? $raw['nav_style'] : 'dots';
		$settings['arrow_style'] = in_array( $raw['arrow_style'] ?? '', array( 'circle', 'square', 'minimal' ), true ) ? $raw['arrow_style'] : 'circle';
		$settings['arrow_position'] = 'edge' === ( $raw['arrow_position'] ?? '' ) ? 'edge' : 'inside';
		$settings['caption_position'] = in_array( $raw['caption_position'] ?? '', array( 'top', 'center', 'bottom' ), true ) ? $raw['caption_position'] : 'center';
		$settings['max_width'] = max( 320, absint( $raw['max_width'] ?? 1200 ) );
		$settings['radius'] = min( 100, absint( $raw['radius'] ?? 20 ) );
		$settings['accent'] = sanitize_hex_color( $raw['accent'] ?? '' ) ?: '#7c5cff';
		$settings['button_color'] = sanitize_hex_color( $raw['button_color'] ?? '' ) ?: '#7c5cff';
		$settings['text_color'] = sanitize_hex_color( $raw['text_color'] ?? '' ) ?: '#ffffff';
		$settings['caption_bg'] = sanitize_hex_color( $raw['caption_bg'] ?? '' ) ?: '#10111a';
		$settings['text_size'] = min( 32, max( 12, absint( $raw['text_size'] ?? 18 ) ) );
		$settings['heading_tag'] = in_array( $raw['heading_tag'] ?? '', array( 'h2', 'h3', 'p' ), true ) ? $raw['heading_tag'] : 'h2';
		$settings['font_family'] = preg_replace( '/[^a-zA-Z0-9 ,\'"_-]/', '', sanitize_text_field( wp_unslash( $raw['font_family'] ?? 'inherit' ) ) ) ?: 'inherit';
		$settings['button_radius'] = min( 100, absint( $raw['button_radius'] ?? 12 ) );
		$settings['spacing'] = min( 100, absint( $raw['spacing'] ?? 0 ) );
		$settings['custom_css'] = wp_strip_all_tags( wp_unslash( $raw['custom_css'] ?? '' ) );
		$settings = apply_filters( 'glidara_slider_sanitized_settings', $settings, $raw, $post_id );
		update_post_meta( $post_id, '_glidara_slider_slides', $slides );
		update_post_meta( $post_id, '_glidara_slider_settings', $settings );
	}

	public function reset_settings() {
		$post_id = absint( $_GET['id'] ?? 0 );
		if ( ! $post_id || 'glidara_slider' !== get_post_type( $post_id ) || ! current_user_can( 'edit_post', $post_id ) || ! check_admin_referer( 'glidara_reset_settings_' . $post_id ) ) wp_die( esc_html__( 'Invalid settings reset request.', 'glidara-slider' ) );
		update_post_meta( $post_id, '_glidara_slider_settings', self::defaults() );
		wp_safe_redirect( add_query_arg( 'glidara_reset', '1', get_edit_post_link( $post_id, 'raw' ) ) );
		exit;
	}

	private function sanitize_layers( $layers ) {
		$clean = array();
		foreach ( array_slice( (array) $layers, 0, 50 ) as $layer ) {
			$type = in_array( $layer['type'] ?? '', array( 'heading', 'text', 'button', 'image', 'icon', 'svg', 'shape', 'video', 'html' ), true ) ? $layer['type'] : 'text';
			$clean[] = array(
				'uid' => sanitize_key( $layer['uid'] ?? wp_generate_uuid4() ),
				'type' => $type,
				'content' => 'svg' === $type ? wp_kses( wp_unslash( $layer['content'] ?? '' ), array( 'svg' => array( 'viewbox' => true, 'xmlns' => true, 'aria-hidden' => true ), 'path' => array( 'd' => true, 'fill' => true, 'stroke' => true, 'stroke-width' => true ), 'circle' => array( 'cx' => true, 'cy' => true, 'r' => true, 'fill' => true ) ) ) : ( 'html' === $type ? wp_kses_post( wp_unslash( $layer['content'] ?? '' ) ) : sanitize_text_field( wp_unslash( $layer['content'] ?? '' ) ) ),
				'url' => esc_url_raw( wp_unslash( $layer['url'] ?? '' ) ),
				'image' => esc_url_raw( wp_unslash( $layer['image'] ?? '' ) ),
				'x' => min( 100, absint( $layer['x'] ?? 10 ) ),
				'y' => min( 100, absint( $layer['y'] ?? 10 ) ),
				'width' => min( 100, max( 5, absint( $layer['width'] ?? 50 ) ) ),
				'size' => min( 200, max( 8, absint( $layer['size'] ?? 24 ) ) ),
				'z' => min( 100, max( 1, absint( $layer['z'] ?? 3 ) ) ),
				'color' => sanitize_hex_color( $layer['color'] ?? '' ) ?: '#ffffff',
				'animation' => in_array( $layer['animation'] ?? '', array( 'none', 'fade', 'slide-up', 'slide-left', 'zoom' ), true ) ? $layer['animation'] : 'none',
				'delay' => min( 10000, absint( $layer['delay'] ?? 0 ) ),
				'locked' => empty( $layer['locked'] ) ? 0 : 1,
				'hide_tablet' => empty( $layer['hide_tablet'] ) ? 0 : 1,
				'hide_mobile' => empty( $layer['hide_mobile'] ) ? 0 : 1,
			);
		}
		return $clean;
	}

	public function admin_assets( $hook ) {
		if ( in_array( $hook, array( 'settings_page_glidara-slider-go-pro', 'settings_page_glidara-slider-tools' ), true ) ) {
			wp_enqueue_style( 'glidara-slider-admin', GLIDARA_SLIDER_URL . 'assets/css/admin.css', array(), GLIDARA_SLIDER_VERSION );
			wp_enqueue_style( 'glidara-slider-admin-extras', GLIDARA_SLIDER_URL . 'assets/css/admin-extras.css', array( 'glidara-slider-admin' ), GLIDARA_SLIDER_VERSION );
			return;
		}
		if ( in_array( $hook, array( 'post.php', 'post-new.php' ), true ) && 'glidara_slider' === get_current_screen()->post_type ) {
			wp_enqueue_script( 'jquery-ui-sortable' );
			wp_enqueue_media();
			wp_enqueue_script( 'glidara-slider-admin', GLIDARA_SLIDER_URL . 'assets/js/admin.js', array( 'jquery', 'jquery-ui-sortable', 'wp-util' ), GLIDARA_SLIDER_VERSION, true );
			wp_enqueue_style( 'glidara-slider-admin', GLIDARA_SLIDER_URL . 'assets/css/admin.css', array(), GLIDARA_SLIDER_VERSION );
			if ( apply_filters( 'glidara_slider_layers_enabled', false ) ) wp_enqueue_style( 'glidara-slider-admin-layers', GLIDARA_SLIDER_URL . 'assets/css/admin-layers.css', array( 'glidara-slider-admin' ), GLIDARA_SLIDER_VERSION );
		}
	}

	public function register_frontend_assets() {
		wp_register_script( 'glidara-slider', GLIDARA_SLIDER_URL . 'assets/js/slider.min.js', array(), GLIDARA_SLIDER_VERSION, true );
		wp_register_style( 'glidara-slider', GLIDARA_SLIDER_URL . 'assets/css/slider.css', array(), GLIDARA_SLIDER_VERSION );
		wp_register_style( 'glidara-slider-layers', GLIDARA_SLIDER_URL . 'assets/css/layers.css', array( 'glidara-slider' ), GLIDARA_SLIDER_VERSION );
		wp_register_style( 'glidara-slider-layouts', GLIDARA_SLIDER_URL . 'assets/css/layouts.css', array( 'glidara-slider' ), GLIDARA_SLIDER_VERSION );
	}

	public function shortcode( $atts ) {
		$id = absint( shortcode_atts( array( 'id' => 0 ), $atts, 'glidara_slider' )['id'] );
		if ( ! $id ) return '';
		$slides = (array) get_post_meta( $id, '_glidara_slider_slides', true );
		$slides = (array) apply_filters( 'glidara_slider_render_slides', $slides, $id );
		if ( ! $slides ) return '';
		$settings = wp_parse_args( (array) get_post_meta( $id, '_glidara_slider_settings', true ), self::defaults() );
		wp_enqueue_script( 'glidara-slider' );
		wp_enqueue_style( 'glidara-slider' );
		wp_enqueue_style( 'glidara-slider-layers' );
		wp_enqueue_style( 'glidara-slider-layouts' );
		do_action( 'glidara_slider_frontend_assets', $id );
		if ( $settings['custom_css'] ) wp_add_inline_style( 'glidara-slider', $settings['custom_css'] );
		$fit = 'original' === $settings['image_fit'] ? 'none' : $settings['image_fit'];
		$style = sprintf( '--glidara-slider-speed:%dms;--glidara-slider-radius:%dpx;--glidara-slider-accent:%s;--glidara-slider-button:%s;--glidara-slider-text:%s;--glidara-slider-caption-bg:%s;--glidara-slider-text-size:%dpx;--glidara-slider-button-radius:%dpx;--glidara-slider-mobile-height:%dpx;--glidara-slider-fit:%s;--glidara-slider-gap:%dpx;font-family:%s;%s%s', absint( $settings['speed'] ), absint( $settings['radius'] ), sanitize_hex_color( $settings['accent'] ) ?: '#7c5cff', sanitize_hex_color( $settings['button_color'] ) ?: '#7c5cff', sanitize_hex_color( $settings['text_color'] ) ?: '#ffffff', sanitize_hex_color( $settings['caption_bg'] ) ?: '#10111a', absint( $settings['text_size'] ), absint( $settings['button_radius'] ), absint( $settings['mobile_height'] ), esc_attr( $fit ), absint( $settings['spacing'] ), esc_attr( $settings['font_family'] ), empty( $settings['height'] ) ? '' : 'height:' . absint( $settings['height'] ) . 'px;', 'boxed' === $settings['layout'] ? 'max-width:' . absint( $settings['max_width'] ) . 'px;margin-inline:auto;' : '' );
		ob_start();
		?>
		<div class="glidara-slider glidara-slider--<?php echo esc_attr( $settings['animation'] ); ?> glidara-slider--<?php echo esc_attr( $settings['layout_type'] ); ?> glidara-slider--<?php echo esc_attr( $settings['direction'] ); ?> glidara-slider--nav-<?php echo esc_attr( $settings['nav_style'] ); ?> glidara-slider--arrows-<?php echo esc_attr( $settings['arrow_style'] ); ?> glidara-slider--arrows-<?php echo esc_attr( $settings['arrow_position'] ); ?> glidara-slider--caption-<?php echo esc_attr( $settings['caption_position'] ); ?><?php echo ! empty( $settings['shadow'] ) ? ' glidara-slider--shadow' : ''; ?><?php echo ! empty( $settings['nav_hover'] ) ? ' glidara-slider--nav-hover' : ''; ?><?php echo ! empty( $settings['hide_desktop'] ) ? ' glidara-slider-hide-desktop' : ''; ?><?php echo ! empty( $settings['hide_tablet'] ) ? ' glidara-slider-hide-tablet-root' : ''; ?><?php echo ! empty( $settings['hide_mobile'] ) ? ' glidara-slider-hide-mobile-root' : ''; ?>" style="<?php echo esc_attr( $style ); ?>" data-slider-id="<?php echo absint( $id ); ?>" data-settings="<?php echo esc_attr( wp_json_encode( $settings ) ); ?>" tabindex="0" role="region" aria-roledescription="carousel" aria-label="<?php echo esc_attr( get_the_title( $id ) ); ?>">
			<div class="glidara-slider__track">
				<?php foreach ( $slides as $index => $slide ) : ?>
					<article class="glidara-slider__slide<?php echo ! empty( $slide['hide_mobile'] ) ? ' glidara-slider__slide--hide-content-mobile' : ''; ?>" style="<?php echo esc_attr( 'background-color:' . ( sanitize_hex_color( $slide['background'] ?? '' ) ?: '#141525' ) . ';--glidara-slider-overlay-opacity:' . min( 100, absint( $slide['overlay_opacity'] ?? 55 ) ) / 100 . ';text-align:' . ( in_array( $slide['align'] ?? '', array( 'left', 'center', 'right' ), true ) ? $slide['align'] : 'left' ) ); ?>" aria-hidden="<?php echo 0 === $index ? 'false' : 'true'; ?>">
						<span class="glidara-slider__overlay" aria-hidden="true"></span>
						<?php echo $this->render_slide( $slide, $settings ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
						<?php if ( apply_filters( 'glidara_slider_layers_enabled', false ) ) echo $this->render_layers( $slide['layers'] ?? array() ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
					</article>
				<?php endforeach; ?>
			</div>
			<?php if ( ! empty( $settings['arrows'] ) ) : ?><button class="glidara-slider__arrow glidara-slider__prev" aria-label="<?php esc_attr_e( 'Previous slide', 'glidara-slider' ); ?>">‹</button><button class="glidara-slider__arrow glidara-slider__next" aria-label="<?php esc_attr_e( 'Next slide', 'glidara-slider' ); ?>">›</button><?php endif; ?>
			<?php if ( ! empty( $settings['dots'] ) ) : ?><div class="glidara-slider__dots" role="tablist"><?php foreach ( $slides as $index => $dot_slide ) : ?><button role="tab" <?php if ( 'thumbnails' === $settings['nav_style'] && ! empty( $dot_slide['image'] ) ) : ?>class="glidara-slider__thumb" style="background-image:url('<?php echo esc_attr( esc_url( $dot_slide['image'] ) ); ?>')"<?php endif; ?> aria-label="<?php echo esc_attr( sprintf( __( 'Go to slide %d', 'glidara-slider' ), $index + 1 ) ); ?>"><?php if ( 'numbers' === $settings['nav_style'] ) echo esc_html( $index + 1 ); ?></button><?php endforeach; ?></div><?php endif; ?>
			<div class="glidara-slider__progress" aria-hidden="true"><span></span></div>
			<div class="glidara-slider__counter" aria-hidden="true"><span>01</span><i></i><b><?php echo esc_html( str_pad( (string) count( $slides ), 2, '0', STR_PAD_LEFT ) ); ?></b></div>
		</div>
		<?php
		return ob_get_clean();
	}

	private function render_slide( $slide, $settings ) {
		$type = $slide['type'] ?? 'image';
		ob_start();
		if ( 'image' === $type && ! empty( $slide['image'] ) ) echo ! empty( $slide['image_id'] ) ? wp_get_attachment_image( absint( $slide['image_id'] ), 'full', false, array( 'class' => 'glidara-slider__image', 'loading' => 'lazy', 'alt' => $slide['image_alt'] ?? '' ) ) : '<img class="glidara-slider__image" loading="lazy" src="' . esc_url( $slide['image'] ) . '" alt="' . esc_attr( $slide['image_alt'] ?? '' ) . '">';
		if ( 'video' === $type && ! empty( $slide['pro_video'] ) ) echo '<video class="glidara-slider__video' . ( ! empty( $slide['background_video'] ) ? ' glidara-slider__video--background' : '' ) . '" src="' . esc_url( $slide['pro_video'] ) . '" poster="' . esc_url( $slide['video_poster'] ?? '' ) . '" preload="metadata" playsinline ' . ( empty( $slide['background_video'] ) ? 'controls ' : '' ) . ( empty( $slide['video_autoplay'] ) ? '' : 'autoplay ' ) . ( empty( $slide['video_muted'] ) ? '' : 'muted ' ) . ( empty( $slide['video_loop'] ) ? '' : 'loop ' ) . '></video>';
		elseif ( 'video' === $type && ! empty( $slide['video'] ) ) echo wp_kses_post( wp_oembed_get( $slide['video'] ) );
		echo '<div class="glidara-slider__content">';
		if ( ! empty( $slide['title'] ) ) { $tag = in_array( $settings['heading_tag'] ?? '', array( 'h2', 'h3', 'p' ), true ) ? $settings['heading_tag'] : 'h2'; echo '<' . esc_attr( $tag ) . ' class="glidara-slider__heading">' . esc_html( $slide['title'] ) . '</' . esc_attr( $tag ) . '>'; }
		if ( 'shortcode' === $type ) echo do_shortcode( $slide['content'] ?? '' );
		elseif ( ! empty( $slide['content'] ) ) echo wp_kses_post( wpautop( $slide['content'] ) );
		if ( ! empty( $slide['caption'] ) ) echo '<p class="glidara-slider__caption">' . esc_html( $slide['caption'] ) . '</p>';
		if ( ! empty( $slide['button_text'] ) && ! empty( $slide['button_url'] ) ) echo '<a class="glidara-slider__button" data-glidara-slider-cta="legacy" href="' . esc_url( $slide['button_url'] ) . '" target="' . esc_attr( '_blank' === ( $slide['button_target'] ?? '' ) ? '_blank' : '_self' ) . '"' . ( '_blank' === ( $slide['button_target'] ?? '' ) ? ' rel="noopener noreferrer"' : '' ) . '>' . esc_html( $slide['button_text'] ) . '</a>';
		echo '</div>';
		return ob_get_clean();
	}

	private function render_layers( $layers ) {
		$html = '';
		foreach ( (array) $layers as $layer ) {
			$type = $layer['type'] ?? 'text';
			$classes = 'glidara-slider__layer glidara-slider__layer--' . sanitize_html_class( $type ) . ' glidara-slider__layer-animation--' . sanitize_html_class( $layer['animation'] ?? 'none' );
			if ( ! empty( $layer['hide_tablet'] ) ) $classes .= ' glidara-slider-hide-tablet';
			if ( ! empty( $layer['hide_mobile'] ) ) $classes .= ' glidara-slider-hide-mobile';
			$style = sprintf( 'left:%d%%;top:%d%%;width:%d%%;font-size:%dpx;color:%s;z-index:%d;animation-delay:%dms', absint( $layer['x'] ?? 10 ), absint( $layer['y'] ?? 10 ), absint( $layer['width'] ?? 50 ), absint( $layer['size'] ?? 24 ), sanitize_hex_color( $layer['color'] ?? '' ) ?: '#ffffff', absint( $layer['z'] ?? 3 ), absint( $layer['delay'] ?? 0 ) );
			$content = esc_html( $layer['content'] ?? '' );
			if ( 'image' === $type ) $content = '<img src="' . esc_url( $layer['image'] ?? '' ) . '" alt="' . esc_attr( $layer['content'] ?? '' ) . '">';
			elseif ( 'video' === $type ) $content = wp_video_shortcode( array( 'src' => esc_url( $layer['url'] ?? '' ), 'preload' => 'metadata' ) );
			elseif ( 'shape' === $type ) $content = '<span class="glidara-slider__shape" aria-hidden="true"></span>';
			elseif ( 'svg' === $type ) $content = wp_kses( $layer['content'] ?? '', array( 'svg' => array( 'viewbox' => true, 'xmlns' => true, 'aria-hidden' => true ), 'path' => array( 'd' => true, 'fill' => true, 'stroke' => true, 'stroke-width' => true ), 'circle' => array( 'cx' => true, 'cy' => true, 'r' => true, 'fill' => true ) ) );
			elseif ( 'html' === $type ) $content = wp_kses_post( $layer['content'] ?? '' );
			elseif ( 'button' === $type ) $content = '<a class="glidara-slider__button" data-glidara-slider-cta="' . esc_attr( $layer['uid'] ?? '' ) . '" href="' . esc_url( $layer['url'] ?? '' ) . '">' . $content . '</a>';
			elseif ( 'heading' === $type ) $content = '<h2>' . $content . '</h2>';
			elseif ( 'icon' === $type ) $content = '<span aria-hidden="true">' . $content . '</span>';
			$html .= '<div class="' . esc_attr( $classes ) . '" style="' . esc_attr( $style ) . '">' . $content . '</div>';
		}
		return $html;
	}

	public function columns( $columns ) {
		$columns['shortcode'] = __( 'Shortcode', 'glidara-slider' );
		return $columns;
	}

	public function column_content( $column, $post_id ) {
		if ( 'shortcode' === $column ) echo '<code>[glidara_slider id="' . (int) $post_id . '"]</code>';
	}
}
