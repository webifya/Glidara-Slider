<?php

defined( 'ABSPATH' ) || exit;

final class My_Slider_Plugin {
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
		add_action( 'save_post_my_slider', array( $this, 'save_slider' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'admin_assets' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'register_frontend_assets' ) );
		add_shortcode( 'my_slider', array( $this, 'shortcode' ) );
		add_filter( 'manage_my_slider_posts_columns', array( $this, 'columns' ) );
		add_action( 'manage_my_slider_posts_custom_column', array( $this, 'column_content' ), 10, 2 );
	}

	public function register_slider_type() {
		register_post_type(
			'my_slider',
			array(
				'labels' => array(
					'name'          => __( 'Sliders', 'my-slider' ),
					'singular_name' => __( 'Slider', 'my-slider' ),
					'add_new_item'  => __( 'Add New Slider', 'my-slider' ),
					'edit_item'     => __( 'Edit Slider', 'my-slider' ),
				),
				'public'       => false,
				'show_ui'      => true,
				'show_in_menu' => true,
				'menu_icon'    => 'dashicons-images-alt2',
				'supports'     => array( 'title', 'revisions' ),
				'show_in_rest' => true,
			)
		);
	}

	public function add_meta_boxes() {
		add_meta_box( 'my-slider-builder', __( 'Slides', 'my-slider' ), array( $this, 'builder_box' ), 'my_slider', 'normal', 'high' );
		add_meta_box( 'my-slider-settings', __( 'Slider Settings', 'my-slider' ), array( $this, 'settings_box' ), 'my_slider', 'side' );
	}

	public function builder_box( $post ) {
		wp_nonce_field( 'my_slider_save', 'my_slider_nonce' );
		$slides = get_post_meta( $post->ID, '_my_slider_slides', true );
		$slides = is_array( $slides ) ? $slides : array();
		?>
		<div id="my-slider-builder" data-next-index="<?php echo esc_attr( count( $slides ) ); ?>">
			<div class="my-slider-toolbar"><p><?php esc_html_e( 'Drag slides to reorder them. Changes appear in the preview immediately.', 'my-slider' ); ?></p><div><strong><?php esc_html_e( 'Preview:', 'my-slider' ); ?></strong> <button type="button" class="button my-slider-device is-active" data-device="desktop"><?php esc_html_e( 'Desktop', 'my-slider' ); ?></button> <button type="button" class="button my-slider-device" data-device="tablet"><?php esc_html_e( 'Tablet', 'my-slider' ); ?></button> <button type="button" class="button my-slider-device" data-device="mobile"><?php esc_html_e( 'Mobile', 'my-slider' ); ?></button></div></div>
			<div class="my-slider-slides">
				<?php foreach ( $slides as $index => $slide ) : ?>
					<?php $this->slide_editor( $index, $slide ); ?>
				<?php endforeach; ?>
			</div>
			<button type="button" class="button button-primary my-slider-add"><?php esc_html_e( 'Add Slide', 'my-slider' ); ?></button>
			<select class="my-slider-template"><option value=""><?php esc_html_e( 'Insert starter template…', 'my-slider' ); ?></option><option value="hero"><?php esc_html_e( 'Hero Slider', 'my-slider' ); ?></option><option value="gallery"><?php esc_html_e( 'Image Gallery', 'my-slider' ); ?></option><option value="business"><?php esc_html_e( 'Business Slider', 'my-slider' ); ?></option><option value="portfolio"><?php esc_html_e( 'Portfolio Slider', 'my-slider' ); ?></option></select>
			<div class="my-slider-preview-wrap" data-device="desktop"><div class="my-slider-preview" aria-live="polite"></div></div>
		</div>
		<script type="text/html" id="tmpl-my-slider-slide"><?php $this->slide_editor( '{{data.index}}', array() ); ?></script>
		<script type="text/html" id="tmpl-my-slider-layer"><?php $this->layer_editor( '{{data.slide}}', '{{data.layer}}', array() ); ?></script>
		<?php
	}

	private function slide_editor( $index, $slide ) {
		$slide = wp_parse_args( $slide, array( 'uid' => wp_generate_uuid4(), 'type' => 'image', 'title' => '', 'content' => '', 'image' => '', 'image_id' => 0, 'image_alt' => '', 'video' => '', 'button_text' => '', 'button_url' => '', 'background' => '#1d2327', 'overlay_opacity' => 20, 'align' => 'left', 'hide_mobile' => 0, 'layers' => array() ) );
		$name  = 'my_slider_slides[' . $index . ']';
		?>
		<div class="my-slider-slide">
			<input type="hidden" name="<?php echo esc_attr( $name ); ?>[uid]" value="<?php echo esc_attr( $slide['uid'] ); ?>">
			<div class="my-slider-handle"><span class="dashicons dashicons-move"></span> <?php esc_html_e( 'Slide', 'my-slider' ); ?> <button type="button" class="button-link-delete my-slider-remove"><?php esc_html_e( 'Remove', 'my-slider' ); ?></button></div>
			<div class="my-slider-fields">
				<label><?php esc_html_e( 'Type', 'my-slider' ); ?>
					<select name="<?php echo esc_attr( $name ); ?>[type]">
						<?php foreach ( array( 'image' => 'Image', 'text' => 'Text', 'video' => 'YouTube/Vimeo', 'html' => 'HTML', 'shortcode' => 'Shortcode' ) as $value => $label ) : ?>
							<option value="<?php echo esc_attr( $value ); ?>" <?php selected( $slide['type'], $value ); ?>><?php echo esc_html( $label ); ?></option>
						<?php endforeach; ?>
					</select>
				</label>
				<label><?php esc_html_e( 'Heading', 'my-slider' ); ?><input type="text" name="<?php echo esc_attr( $name ); ?>[title]" value="<?php echo esc_attr( $slide['title'] ); ?>"></label>
				<label><?php esc_html_e( 'Content / HTML / Shortcode', 'my-slider' ); ?><textarea name="<?php echo esc_attr( $name ); ?>[content]" rows="4"><?php echo esc_textarea( $slide['content'] ); ?></textarea></label>
				<label><?php esc_html_e( 'Image', 'my-slider' ); ?><span class="my-slider-media-row"><input class="my-slider-image-url" type="url" name="<?php echo esc_attr( $name ); ?>[image]" value="<?php echo esc_url( $slide['image'] ); ?>"><input class="my-slider-image-id" type="hidden" name="<?php echo esc_attr( $name ); ?>[image_id]" value="<?php echo absint( $slide['image_id'] ); ?>"><button type="button" class="button my-slider-media"><?php esc_html_e( 'Choose', 'my-slider' ); ?></button></span></label>
				<label><?php esc_html_e( 'Image alt text', 'my-slider' ); ?><input type="text" name="<?php echo esc_attr( $name ); ?>[image_alt]" value="<?php echo esc_attr( $slide['image_alt'] ); ?>"></label>
				<label><?php esc_html_e( 'Video URL', 'my-slider' ); ?><input type="url" name="<?php echo esc_attr( $name ); ?>[video]" value="<?php echo esc_url( $slide['video'] ); ?>"></label>
				<label><?php esc_html_e( 'Button text', 'my-slider' ); ?><input type="text" name="<?php echo esc_attr( $name ); ?>[button_text]" value="<?php echo esc_attr( $slide['button_text'] ); ?>"></label>
				<label><?php esc_html_e( 'Button URL', 'my-slider' ); ?><input type="url" name="<?php echo esc_attr( $name ); ?>[button_url]" value="<?php echo esc_url( $slide['button_url'] ); ?>"></label>
				<fieldset class="my-slider-design-fields"><legend><?php esc_html_e( 'Slide design', 'my-slider' ); ?></legend><label><?php esc_html_e( 'Background', 'my-slider' ); ?><input type="color" name="<?php echo esc_attr( $name ); ?>[background]" value="<?php echo esc_attr( $slide['background'] ); ?>"></label><label><?php esc_html_e( 'Overlay opacity', 'my-slider' ); ?><input type="range" min="0" max="100" name="<?php echo esc_attr( $name ); ?>[overlay_opacity]" value="<?php echo absint( $slide['overlay_opacity'] ); ?>"></label><label><?php esc_html_e( 'Alignment', 'my-slider' ); ?><select name="<?php echo esc_attr( $name ); ?>[align]"><?php foreach ( array( 'left', 'center', 'right' ) as $align ) : ?><option value="<?php echo esc_attr( $align ); ?>" <?php selected( $slide['align'], $align ); ?>><?php echo esc_html( ucfirst( $align ) ); ?></option><?php endforeach; ?></select></label><label><input type="checkbox" name="<?php echo esc_attr( $name ); ?>[hide_mobile]" value="1" <?php checked( $slide['hide_mobile'] ); ?>><?php esc_html_e( 'Hide content on mobile', 'my-slider' ); ?></label></fieldset>
				<fieldset class="my-slider-layer-builder"><legend><?php esc_html_e( 'Layers', 'my-slider' ); ?></legend><div class="my-slider-layers" data-next-layer="<?php echo absint( count( $slide['layers'] ) ); ?>"><?php foreach ( (array) $slide['layers'] as $layer_index => $layer ) $this->layer_editor( $index, $layer_index, $layer ); ?></div><button type="button" class="button my-slider-add-layer"><?php esc_html_e( 'Add Layer', 'my-slider' ); ?></button></fieldset>
			</div>
		</div>
		<?php
	}

	private function layer_editor( $slide_index, $layer_index, $layer ) {
		$layer = wp_parse_args( $layer, array( 'uid' => wp_generate_uuid4(), 'type' => 'text', 'content' => '', 'url' => '', 'image' => '', 'x' => 10, 'y' => 10, 'width' => 50, 'size' => 24, 'color' => '#ffffff', 'hide_tablet' => 0, 'hide_mobile' => 0 ) );
		$name = 'my_slider_slides[' . $slide_index . '][layers][' . $layer_index . ']';
		?>
		<div class="my-slider-layer">
			<div class="my-slider-layer-handle"><span class="dashicons dashicons-move"></span> <?php esc_html_e( 'Layer', 'my-slider' ); ?> <button type="button" class="button-link-delete my-slider-remove-layer"><?php esc_html_e( 'Remove', 'my-slider' ); ?></button></div>
			<input type="hidden" name="<?php echo esc_attr( $name ); ?>[uid]" value="<?php echo esc_attr( $layer['uid'] ); ?>">
			<label><?php esc_html_e( 'Type', 'my-slider' ); ?><select name="<?php echo esc_attr( $name ); ?>[type]"><?php foreach ( array( 'heading', 'text', 'button', 'image', 'icon' ) as $type ) : ?><option value="<?php echo esc_attr( $type ); ?>" <?php selected( $layer['type'], $type ); ?>><?php echo esc_html( ucfirst( $type ) ); ?></option><?php endforeach; ?></select></label>
			<label><?php esc_html_e( 'Content', 'my-slider' ); ?><input type="text" name="<?php echo esc_attr( $name ); ?>[content]" value="<?php echo esc_attr( $layer['content'] ); ?>"></label>
			<label><?php esc_html_e( 'Link URL', 'my-slider' ); ?><input type="url" name="<?php echo esc_attr( $name ); ?>[url]" value="<?php echo esc_url( $layer['url'] ); ?>"></label>
			<label><?php esc_html_e( 'Image URL', 'my-slider' ); ?><input type="url" name="<?php echo esc_attr( $name ); ?>[image]" value="<?php echo esc_url( $layer['image'] ); ?>"></label>
			<label>X %<input type="number" min="0" max="100" name="<?php echo esc_attr( $name ); ?>[x]" value="<?php echo absint( $layer['x'] ); ?>"></label>
			<label>Y %<input type="number" min="0" max="100" name="<?php echo esc_attr( $name ); ?>[y]" value="<?php echo absint( $layer['y'] ); ?>"></label>
			<label><?php esc_html_e( 'Width %', 'my-slider' ); ?><input type="number" min="5" max="100" name="<?php echo esc_attr( $name ); ?>[width]" value="<?php echo absint( $layer['width'] ); ?>"></label>
			<label><?php esc_html_e( 'Size px', 'my-slider' ); ?><input type="number" min="8" max="200" name="<?php echo esc_attr( $name ); ?>[size]" value="<?php echo absint( $layer['size'] ); ?>"></label>
			<label><?php esc_html_e( 'Color', 'my-slider' ); ?><input type="color" name="<?php echo esc_attr( $name ); ?>[color]" value="<?php echo esc_attr( $layer['color'] ); ?>"></label>
			<label><input type="checkbox" name="<?php echo esc_attr( $name ); ?>[hide_tablet]" value="1" <?php checked( $layer['hide_tablet'] ); ?>><?php esc_html_e( 'Hide tablet', 'my-slider' ); ?></label>
			<label><input type="checkbox" name="<?php echo esc_attr( $name ); ?>[hide_mobile]" value="1" <?php checked( $layer['hide_mobile'] ); ?>><?php esc_html_e( 'Hide mobile', 'my-slider' ); ?></label>
		</div>
		<?php
	}

	public function settings_box( $post ) {
		$settings = wp_parse_args( (array) get_post_meta( $post->ID, '_my_slider_settings', true ), self::defaults() );
		foreach ( array( 'autoplay' => 'Autoplay', 'pause_hover' => 'Pause on hover', 'loop' => 'Infinite loop', 'arrows' => 'Arrows', 'dots' => 'Dots', 'keyboard' => 'Keyboard navigation', 'wheel' => 'Mouse wheel', 'auto_height' => 'Auto height' ) as $key => $label ) {
			printf( '<p><label><input type="checkbox" name="my_slider_settings[%1$s]" value="1" %2$s> %3$s</label></p>', esc_attr( $key ), checked( ! empty( $settings[ $key ] ), true, false ), esc_html( $label ) );
		}
		?>
		<p><label><?php esc_html_e( 'Animation', 'my-slider' ); ?><select name="my_slider_settings[animation]">
			<?php foreach ( array( 'slide', 'fade', 'zoom', 'flip', 'cube', 'coverflow' ) as $animation ) : ?>
				<option value="<?php echo esc_attr( $animation ); ?>" <?php selected( $settings['animation'], $animation ); ?>><?php echo esc_html( ucfirst( $animation ) ); ?></option>
			<?php endforeach; ?>
		</select></label></p>
		<p><label><?php esc_html_e( 'Slide duration (ms)', 'my-slider' ); ?><input type="number" min="1000" step="100" name="my_slider_settings[duration]" value="<?php echo esc_attr( $settings['duration'] ); ?>"></label></p>
		<p><label><?php esc_html_e( 'Transition speed (ms)', 'my-slider' ); ?><input type="number" min="0" step="50" name="my_slider_settings[speed]" value="<?php echo esc_attr( $settings['speed'] ); ?>"></label></p>
		<p><label><?php esc_html_e( 'Height (px, blank for responsive)', 'my-slider' ); ?><input type="number" min="100" name="my_slider_settings[height]" value="<?php echo esc_attr( $settings['height'] ); ?>"></label></p>
		<p><label><?php esc_html_e( 'Layout', 'my-slider' ); ?><select name="my_slider_settings[layout]"><option value="full" <?php selected( $settings['layout'], 'full' ); ?>><?php esc_html_e( 'Full width', 'my-slider' ); ?></option><option value="boxed" <?php selected( $settings['layout'], 'boxed' ); ?>><?php esc_html_e( 'Boxed', 'my-slider' ); ?></option></select></label></p>
		<p><label><?php esc_html_e( 'Maximum width (px)', 'my-slider' ); ?><input type="number" min="320" name="my_slider_settings[max_width]" value="<?php echo esc_attr( $settings['max_width'] ); ?>"></label></p>
		<p><label><?php esc_html_e( 'Border radius (px)', 'my-slider' ); ?><input type="number" min="0" max="100" name="my_slider_settings[radius]" value="<?php echo esc_attr( $settings['radius'] ); ?>"></label></p>
		<p><label><input type="checkbox" name="my_slider_settings[shadow]" value="1" <?php checked( $settings['shadow'] ); ?>> <?php esc_html_e( 'Shadow', 'my-slider' ); ?></label></p>
		<hr><strong><?php esc_html_e( 'Global styles', 'my-slider' ); ?></strong>
		<p><label><?php esc_html_e( 'Accent color', 'my-slider' ); ?><input type="color" name="my_slider_settings[accent]" value="<?php echo esc_attr( $settings['accent'] ); ?>"></label></p>
		<p><label><?php esc_html_e( 'Text color', 'my-slider' ); ?><input type="color" name="my_slider_settings[text_color]" value="<?php echo esc_attr( $settings['text_color'] ); ?>"></label></p>
		<p><label><?php esc_html_e( 'Font family', 'my-slider' ); ?><input type="text" name="my_slider_settings[font_family]" value="<?php echo esc_attr( $settings['font_family'] ); ?>" placeholder="inherit"></label></p>
		<p><label><?php esc_html_e( 'Button radius (px)', 'my-slider' ); ?><input type="number" min="0" max="100" name="my_slider_settings[button_radius]" value="<?php echo absint( $settings['button_radius'] ); ?>"></label></p>
		<p><code>[my_slider id="<?php echo (int) $post->ID; ?>"]</code></p>
		<?php
	}

	private static function defaults() {
		return array( 'autoplay' => 1, 'pause_hover' => 1, 'loop' => 1, 'arrows' => 1, 'dots' => 1, 'keyboard' => 1, 'wheel' => 0, 'auto_height' => 1, 'shadow' => 0, 'animation' => 'slide', 'duration' => 5000, 'speed' => 500, 'height' => '', 'layout' => 'full', 'max_width' => 1200, 'radius' => 0, 'accent' => '#2563eb', 'text_color' => '#ffffff', 'font_family' => 'inherit', 'button_radius' => 6 );
	}

	public function save_slider( $post_id ) {
		if ( ! isset( $_POST['my_slider_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['my_slider_nonce'] ) ), 'my_slider_save' ) || ! current_user_can( 'edit_post', $post_id ) || wp_is_post_autosave( $post_id ) ) {
			return;
		}
		$slides = array();
		foreach ( (array) ( $_POST['my_slider_slides'] ?? array() ) as $slide ) {
			$slides[] = array(
				'uid'         => sanitize_key( $slide['uid'] ?? wp_generate_uuid4() ),
				'type'        => sanitize_key( $slide['type'] ?? 'image' ),
				'title'       => sanitize_text_field( wp_unslash( $slide['title'] ?? '' ) ),
				'content'     => wp_kses_post( wp_unslash( $slide['content'] ?? '' ) ),
				'image'       => esc_url_raw( wp_unslash( $slide['image'] ?? '' ) ),
				'image_id'    => absint( $slide['image_id'] ?? 0 ),
				'image_alt'   => sanitize_text_field( wp_unslash( $slide['image_alt'] ?? '' ) ),
				'video'       => esc_url_raw( wp_unslash( $slide['video'] ?? '' ) ),
				'button_text' => sanitize_text_field( wp_unslash( $slide['button_text'] ?? '' ) ),
				'button_url'  => esc_url_raw( wp_unslash( $slide['button_url'] ?? '' ) ),
				'background'  => sanitize_hex_color( $slide['background'] ?? '#1d2327' ) ?: '#1d2327',
				'overlay_opacity' => min( 100, absint( $slide['overlay_opacity'] ?? 20 ) ),
				'align'       => in_array( $slide['align'] ?? '', array( 'left', 'center', 'right' ), true ) ? $slide['align'] : 'left',
				'hide_mobile' => empty( $slide['hide_mobile'] ) ? 0 : 1,
				'layers'      => $this->sanitize_layers( $slide['layers'] ?? array() ),
			);
		}
		$raw = (array) ( $_POST['my_slider_settings'] ?? array() );
		$settings = self::defaults();
		foreach ( array( 'autoplay', 'pause_hover', 'loop', 'arrows', 'dots', 'keyboard', 'wheel', 'auto_height', 'shadow' ) as $key ) {
			$settings[ $key ] = empty( $raw[ $key ] ) ? 0 : 1;
		}
		$settings['animation'] = in_array( $raw['animation'] ?? '', array( 'slide', 'fade', 'zoom', 'flip', 'cube', 'coverflow' ), true ) ? $raw['animation'] : 'slide';
		$settings['duration'] = max( 1000, absint( $raw['duration'] ?? 5000 ) );
		$settings['speed'] = absint( $raw['speed'] ?? 500 );
		$settings['height'] = empty( $raw['height'] ) ? '' : max( 100, absint( $raw['height'] ) );
		$settings['layout'] = 'boxed' === ( $raw['layout'] ?? '' ) ? 'boxed' : 'full';
		$settings['max_width'] = max( 320, absint( $raw['max_width'] ?? 1200 ) );
		$settings['radius'] = min( 100, absint( $raw['radius'] ?? 0 ) );
		$settings['accent'] = sanitize_hex_color( $raw['accent'] ?? '' ) ?: '#2563eb';
		$settings['text_color'] = sanitize_hex_color( $raw['text_color'] ?? '' ) ?: '#ffffff';
		$settings['font_family'] = preg_replace( '/[^a-zA-Z0-9 ,\'"_-]/', '', sanitize_text_field( wp_unslash( $raw['font_family'] ?? 'inherit' ) ) ) ?: 'inherit';
		$settings['button_radius'] = min( 100, absint( $raw['button_radius'] ?? 6 ) );
		update_post_meta( $post_id, '_my_slider_slides', $slides );
		update_post_meta( $post_id, '_my_slider_settings', $settings );
	}

	private function sanitize_layers( $layers ) {
		$clean = array();
		foreach ( array_slice( (array) $layers, 0, 50 ) as $layer ) {
			$clean[] = array(
				'uid' => sanitize_key( $layer['uid'] ?? wp_generate_uuid4() ),
				'type' => in_array( $layer['type'] ?? '', array( 'heading', 'text', 'button', 'image', 'icon' ), true ) ? $layer['type'] : 'text',
				'content' => sanitize_text_field( wp_unslash( $layer['content'] ?? '' ) ),
				'url' => esc_url_raw( wp_unslash( $layer['url'] ?? '' ) ),
				'image' => esc_url_raw( wp_unslash( $layer['image'] ?? '' ) ),
				'x' => min( 100, absint( $layer['x'] ?? 10 ) ),
				'y' => min( 100, absint( $layer['y'] ?? 10 ) ),
				'width' => min( 100, max( 5, absint( $layer['width'] ?? 50 ) ) ),
				'size' => min( 200, max( 8, absint( $layer['size'] ?? 24 ) ) ),
				'color' => sanitize_hex_color( $layer['color'] ?? '' ) ?: '#ffffff',
				'hide_tablet' => empty( $layer['hide_tablet'] ) ? 0 : 1,
				'hide_mobile' => empty( $layer['hide_mobile'] ) ? 0 : 1,
			);
		}
		return $clean;
	}

	public function admin_assets( $hook ) {
		if ( in_array( $hook, array( 'post.php', 'post-new.php' ), true ) && 'my_slider' === get_current_screen()->post_type ) {
			wp_enqueue_script( 'jquery-ui-sortable' );
			wp_enqueue_media();
			wp_enqueue_script( 'my-slider-admin', MY_SLIDER_URL . 'assets/js/admin.js', array( 'jquery', 'jquery-ui-sortable', 'wp-util' ), MY_SLIDER_VERSION, true );
			wp_enqueue_style( 'my-slider-admin', MY_SLIDER_URL . 'assets/css/admin.css', array(), MY_SLIDER_VERSION );
			wp_enqueue_style( 'my-slider-admin-layers', MY_SLIDER_URL . 'assets/css/admin-layers.css', array( 'my-slider-admin' ), MY_SLIDER_VERSION );
		}
	}

	public function register_frontend_assets() {
		wp_register_script( 'my-slider', MY_SLIDER_URL . 'assets/js/slider.js', array(), MY_SLIDER_VERSION, true );
		wp_register_style( 'my-slider', MY_SLIDER_URL . 'assets/css/slider.css', array(), MY_SLIDER_VERSION );
		wp_register_style( 'my-slider-layers', MY_SLIDER_URL . 'assets/css/layers.css', array( 'my-slider' ), MY_SLIDER_VERSION );
	}

	public function shortcode( $atts ) {
		$id = absint( shortcode_atts( array( 'id' => 0 ), $atts, 'my_slider' )['id'] );
		if ( ! $id ) return '';
		$slides = (array) get_post_meta( $id, '_my_slider_slides', true );
		if ( ! $slides ) return '';
		$settings = wp_parse_args( (array) get_post_meta( $id, '_my_slider_settings', true ), self::defaults() );
		wp_enqueue_script( 'my-slider' );
		wp_enqueue_style( 'my-slider' );
		wp_enqueue_style( 'my-slider-layers' );
		$style = sprintf( '--my-slider-speed:%dms;--my-slider-radius:%dpx;--my-slider-accent:%s;--my-slider-text:%s;--my-slider-button-radius:%dpx;font-family:%s;%s%s', absint( $settings['speed'] ), absint( $settings['radius'] ), sanitize_hex_color( $settings['accent'] ) ?: '#2563eb', sanitize_hex_color( $settings['text_color'] ) ?: '#ffffff', absint( $settings['button_radius'] ), esc_attr( $settings['font_family'] ), empty( $settings['height'] ) ? '' : 'height:' . absint( $settings['height'] ) . 'px;', 'boxed' === $settings['layout'] ? 'max-width:' . absint( $settings['max_width'] ) . 'px;margin-inline:auto;' : '' );
		ob_start();
		?>
		<div class="my-slider my-slider--<?php echo esc_attr( $settings['animation'] ); ?><?php echo ! empty( $settings['shadow'] ) ? ' my-slider--shadow' : ''; ?>" style="<?php echo esc_attr( $style ); ?>" data-slider-id="<?php echo absint( $id ); ?>" data-settings="<?php echo esc_attr( wp_json_encode( $settings ) ); ?>" tabindex="0" role="region" aria-roledescription="carousel" aria-label="<?php echo esc_attr( get_the_title( $id ) ); ?>">
			<div class="my-slider__track">
				<?php foreach ( $slides as $index => $slide ) : ?>
					<article class="my-slider__slide<?php echo ! empty( $slide['hide_mobile'] ) ? ' my-slider__slide--hide-content-mobile' : ''; ?>" style="<?php echo esc_attr( 'background-color:' . ( sanitize_hex_color( $slide['background'] ?? '' ) ?: '#1d2327' ) . ';--my-slider-overlay-opacity:' . min( 100, absint( $slide['overlay_opacity'] ?? 20 ) ) / 100 . ';text-align:' . ( in_array( $slide['align'] ?? '', array( 'left', 'center', 'right' ), true ) ? $slide['align'] : 'left' ) ); ?>" aria-hidden="<?php echo 0 === $index ? 'false' : 'true'; ?>">
						<span class="my-slider__overlay" aria-hidden="true"></span>
						<?php echo $this->render_slide( $slide ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
						<?php echo $this->render_layers( $slide['layers'] ?? array() ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
					</article>
				<?php endforeach; ?>
			</div>
			<?php if ( ! empty( $settings['arrows'] ) ) : ?><button class="my-slider__arrow my-slider__prev" aria-label="<?php esc_attr_e( 'Previous slide', 'my-slider' ); ?>">‹</button><button class="my-slider__arrow my-slider__next" aria-label="<?php esc_attr_e( 'Next slide', 'my-slider' ); ?>">›</button><?php endif; ?>
			<?php if ( ! empty( $settings['dots'] ) ) : ?><div class="my-slider__dots" role="tablist"><?php foreach ( $slides as $index => $unused ) : ?><button role="tab" aria-label="<?php echo esc_attr( sprintf( __( 'Go to slide %d', 'my-slider' ), $index + 1 ) ); ?>"></button><?php endforeach; ?></div><?php endif; ?>
		</div>
		<?php
		return ob_get_clean();
	}

	private function render_slide( $slide ) {
		$type = $slide['type'] ?? 'image';
		ob_start();
		if ( 'image' === $type && ! empty( $slide['image'] ) ) echo ! empty( $slide['image_id'] ) ? wp_get_attachment_image( absint( $slide['image_id'] ), 'full', false, array( 'class' => 'my-slider__image', 'loading' => 'lazy', 'alt' => $slide['image_alt'] ?? '' ) ) : '<img class="my-slider__image" loading="lazy" src="' . esc_url( $slide['image'] ) . '" alt="' . esc_attr( $slide['image_alt'] ?? '' ) . '">';
		if ( 'video' === $type && ! empty( $slide['video'] ) ) echo wp_kses_post( wp_oembed_get( $slide['video'] ) );
		if ( ! empty( $slide['title'] ) ) echo '<h2>' . esc_html( $slide['title'] ) . '</h2>';
		if ( 'shortcode' === $type ) echo do_shortcode( $slide['content'] ?? '' );
		elseif ( ! empty( $slide['content'] ) ) echo wp_kses_post( wpautop( $slide['content'] ) );
		if ( ! empty( $slide['button_text'] ) && ! empty( $slide['button_url'] ) ) echo '<a class="my-slider__button" data-my-slider-cta="legacy" href="' . esc_url( $slide['button_url'] ) . '">' . esc_html( $slide['button_text'] ) . '</a>';
		return ob_get_clean();
	}

	private function render_layers( $layers ) {
		$html = '';
		foreach ( (array) $layers as $layer ) {
			$type = $layer['type'] ?? 'text';
			$classes = 'my-slider__layer my-slider__layer--' . sanitize_html_class( $type );
			if ( ! empty( $layer['hide_tablet'] ) ) $classes .= ' my-slider-hide-tablet';
			if ( ! empty( $layer['hide_mobile'] ) ) $classes .= ' my-slider-hide-mobile';
			$style = sprintf( 'left:%d%%;top:%d%%;width:%d%%;font-size:%dpx;color:%s', absint( $layer['x'] ?? 10 ), absint( $layer['y'] ?? 10 ), absint( $layer['width'] ?? 50 ), absint( $layer['size'] ?? 24 ), sanitize_hex_color( $layer['color'] ?? '' ) ?: '#ffffff' );
			$content = esc_html( $layer['content'] ?? '' );
			if ( 'image' === $type ) $content = '<img src="' . esc_url( $layer['image'] ?? '' ) . '" alt="' . esc_attr( $layer['content'] ?? '' ) . '">';
			elseif ( 'button' === $type ) $content = '<a class="my-slider__button" data-my-slider-cta="' . esc_attr( $layer['uid'] ?? '' ) . '" href="' . esc_url( $layer['url'] ?? '' ) . '">' . $content . '</a>';
			elseif ( 'heading' === $type ) $content = '<h2>' . $content . '</h2>';
			elseif ( 'icon' === $type ) $content = '<span aria-hidden="true">' . $content . '</span>';
			$html .= '<div class="' . esc_attr( $classes ) . '" style="' . esc_attr( $style ) . '">' . $content . '</div>';
		}
		return $html;
	}

	public function columns( $columns ) {
		$columns['shortcode'] = __( 'Shortcode', 'my-slider' );
		return $columns;
	}

	public function column_content( $column, $post_id ) {
		if ( 'shortcode' === $column ) echo '<code>[my_slider id="' . (int) $post_id . '"]</code>';
	}
}
