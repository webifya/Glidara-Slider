<?php

defined( 'ABSPATH' ) || exit;

final class Glidara_Slider_Tools {
	private static $instance;

	public static function instance() {
		if ( null === self::$instance ) self::$instance = new self();
		return self::$instance;
	}

	private function __construct() {
		add_filter( 'plugin_action_links_' . plugin_basename( GLIDARA_SLIDER_FILE ), array( $this, 'plugin_links' ) );
		add_filter( 'post_row_actions', array( $this, 'row_actions' ), 10, 2 );
		add_action( 'admin_post_glidara_slider_duplicate', array( $this, 'duplicate_slider' ) );
		add_action( 'admin_post_glidara_slider_export', array( $this, 'export_slider' ) );
		add_action( 'admin_post_glidara_slider_import', array( $this, 'import_slider' ) );
		add_action( 'admin_menu', array( $this, 'admin_menu' ), 20 );
		add_action( 'admin_init', array( $this, 'settings' ) );
		add_action( 'media_buttons', array( $this, 'classic_button' ) );
		add_action( 'admin_footer', array( $this, 'classic_button_script' ) );
		add_action( 'init', array( $this, 'register_block' ), 20 );
		add_action( 'widgets_init', array( $this, 'register_widget' ) );
	}

	public function plugin_links( $links ) {
		$create = '<a href="' . esc_url( admin_url( 'post-new.php?post_type=glidara_slider' ) ) . '"><strong>' . esc_html__( 'Create New Slider', 'glidara-slider' ) . '</strong></a>';
		$pro = '<a style="color:#6d5dfc;font-weight:700" href="' . esc_url( admin_url( 'edit.php?post_type=glidara_slider&page=glidara-slider-go-pro' ) ) . '">' . esc_html__( 'Go Pro', 'glidara-slider' ) . '</a>';
		array_unshift( $links, $create, $pro );
		return $links;
	}

	public function row_actions( $actions, $post ) {
		if ( 'glidara_slider' !== $post->post_type || ! current_user_can( 'edit_post', $post->ID ) ) return $actions;
		$actions['glidara_duplicate'] = '<a href="' . esc_url( wp_nonce_url( admin_url( 'admin-post.php?action=glidara_slider_duplicate&id=' . $post->ID ), 'glidara_duplicate_' . $post->ID ) ) . '">' . esc_html__( 'Duplicate', 'glidara-slider' ) . '</a>';
		$actions['glidara_export'] = '<a href="' . esc_url( wp_nonce_url( admin_url( 'admin-post.php?action=glidara_slider_export&id=' . $post->ID ), 'glidara_export_' . $post->ID ) ) . '">' . esc_html__( 'Export JSON', 'glidara-slider' ) . '</a>';
		return $actions;
	}

	public function duplicate_slider() {
		$id = absint( $_GET['id'] ?? 0 );
		if ( ! $id || ! current_user_can( 'edit_post', $id ) || ! check_admin_referer( 'glidara_duplicate_' . $id ) ) wp_die( esc_html__( 'Invalid duplicate request.', 'glidara-slider' ) );
		$new_id = wp_insert_post( array( 'post_type' => 'glidara_slider', 'post_status' => 'draft', 'post_title' => sprintf( __( '%s — Copy', 'glidara-slider' ), get_the_title( $id ) ) ) );
		if ( is_wp_error( $new_id ) ) wp_die( esc_html( $new_id->get_error_message() ) );
		foreach ( array( '_glidara_slider_slides', '_glidara_slider_settings' ) as $key ) update_post_meta( $new_id, $key, get_post_meta( $id, $key, true ) );
		wp_safe_redirect( get_edit_post_link( $new_id, 'raw' ) );
		exit;
	}

	public function export_slider() {
		$id = absint( $_GET['id'] ?? 0 );
		if ( ! $id || ! current_user_can( 'edit_post', $id ) || ! check_admin_referer( 'glidara_export_' . $id ) ) wp_die( esc_html__( 'Invalid export request.', 'glidara-slider' ) );
		$data = array( 'schema' => 2, 'title' => get_the_title( $id ), 'slides' => get_post_meta( $id, '_glidara_slider_slides', true ), 'settings' => get_post_meta( $id, '_glidara_slider_settings', true ) );
		nocache_headers();
		header( 'Content-Type: application/json; charset=utf-8' );
		header( 'Content-Disposition: attachment; filename="glidara-slider-' . $id . '.json"' );
		echo wp_json_encode( $data, JSON_PRETTY_PRINT );
		exit;
	}

	public function import_slider() {
		if ( ! current_user_can( 'edit_posts' ) || ! check_admin_referer( 'glidara_slider_import' ) ) wp_die( esc_html__( 'Invalid import request.', 'glidara-slider' ) );
		if ( empty( $_FILES['slider_json']['tmp_name'] ) || UPLOAD_ERR_OK !== (int) $_FILES['slider_json']['error'] || (int) $_FILES['slider_json']['size'] > 1048576 ) wp_die( esc_html__( 'Choose a valid Glidara JSON file smaller than 1 MB.', 'glidara-slider' ) );
		$data = json_decode( file_get_contents( $_FILES['slider_json']['tmp_name'] ), true ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized,WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
		if ( ! is_array( $data ) || ! in_array( absint( $data['schema'] ?? 0 ), array( 1, 2 ), true ) || ! is_array( $data['slides'] ?? null ) ) wp_die( esc_html__( 'Unsupported or invalid Glidara export.', 'glidara-slider' ) );
		$id = wp_insert_post( array( 'post_type' => 'glidara_slider', 'post_status' => 'draft', 'post_title' => sanitize_text_field( $data['title'] ?? __( 'Imported Slider', 'glidara-slider' ) ) ) );
		if ( is_wp_error( $id ) ) wp_die( esc_html( $id->get_error_message() ) );
		update_post_meta( $id, '_glidara_slider_slides', $this->sanitize_slides( $data['slides'] ) );
		update_post_meta( $id, '_glidara_slider_settings', map_deep( (array) ( $data['settings'] ?? array() ), 'sanitize_text_field' ) );
		wp_safe_redirect( get_edit_post_link( $id, 'raw' ) );
		exit;
	}

	private function sanitize_slides( $slides ) {
		$clean = array();
		foreach ( array_slice( (array) $slides, 0, 250 ) as $slide ) {
			$layers = array();
			foreach ( array_slice( (array) ( $slide['layers'] ?? array() ), 0, 50 ) as $layer ) {
				$layers[] = array(
					'uid' => sanitize_key( $layer['uid'] ?? wp_generate_uuid4() ), 'type' => in_array( $layer['type'] ?? '', array( 'heading', 'text', 'button', 'image', 'icon' ), true ) ? $layer['type'] : 'text',
					'content' => sanitize_text_field( $layer['content'] ?? '' ), 'url' => esc_url_raw( $layer['url'] ?? '' ), 'image' => esc_url_raw( $layer['image'] ?? '' ),
					'x' => min( 100, absint( $layer['x'] ?? 10 ) ), 'y' => min( 100, absint( $layer['y'] ?? 10 ) ), 'width' => min( 100, max( 5, absint( $layer['width'] ?? 50 ) ) ), 'size' => min( 200, max( 8, absint( $layer['size'] ?? 24 ) ) ),
					'color' => sanitize_hex_color( $layer['color'] ?? '' ) ?: '#ffffff', 'hide_tablet' => empty( $layer['hide_tablet'] ) ? 0 : 1, 'hide_mobile' => empty( $layer['hide_mobile'] ) ? 0 : 1,
				);
			}
			$clean[] = array(
				'uid' => sanitize_key( $slide['uid'] ?? wp_generate_uuid4() ), 'type' => in_array( $slide['type'] ?? '', array( 'image', 'text', 'video', 'html', 'shortcode' ), true ) ? $slide['type'] : 'image',
				'title' => sanitize_text_field( $slide['title'] ?? '' ), 'content' => wp_kses_post( $slide['content'] ?? '' ), 'image' => esc_url_raw( $slide['image'] ?? '' ), 'image_id' => absint( $slide['image_id'] ?? 0 ), 'image_alt' => sanitize_text_field( $slide['image_alt'] ?? '' ),
				'video' => esc_url_raw( $slide['video'] ?? '' ), 'button_text' => sanitize_text_field( $slide['button_text'] ?? '' ), 'button_url' => esc_url_raw( $slide['button_url'] ?? '' ), 'button_target' => '_blank' === ( $slide['button_target'] ?? '' ) ? '_blank' : '_self',
				'background' => sanitize_hex_color( $slide['background'] ?? '' ) ?: '#141525', 'overlay_opacity' => min( 100, absint( $slide['overlay_opacity'] ?? 55 ) ), 'align' => in_array( $slide['align'] ?? '', array( 'left', 'center', 'right' ), true ) ? $slide['align'] : 'left', 'hide_mobile' => empty( $slide['hide_mobile'] ) ? 0 : 1, 'layers' => $layers,
			);
		}
		return $clean;
	}

	public function admin_menu() {
		add_submenu_page( 'edit.php?post_type=glidara_slider', __( 'Tools & Health', 'glidara-slider' ), __( 'Tools & Health', 'glidara-slider' ), 'manage_options', 'glidara-slider-tools', array( $this, 'tools_page' ) );
	}

	public function settings() {
		register_setting( 'glidara_slider_tools', 'glidara_slider_debug', array( 'sanitize_callback' => 'absint', 'default' => 0 ) );
		register_setting( 'glidara_slider_tools', 'glidara_slider_retain_data', array( 'sanitize_callback' => 'absint', 'default' => 1 ) );
	}

	public function tools_page() {
		if ( ! current_user_can( 'manage_options' ) ) return;
		$sliders = get_posts( array( 'post_type' => 'glidara_slider', 'post_status' => array( 'publish', 'draft' ), 'numberposts' => -1 ) );
		$without_slides = 0; $without_alt = 0;
		foreach ( $sliders as $slider ) foreach ( (array) get_post_meta( $slider->ID, '_glidara_slider_slides', true ) as $slide ) { if ( empty( $slide['image'] ) && empty( $slide['content'] ) ) $without_slides++; if ( ! empty( $slide['image'] ) && empty( $slide['image_alt'] ) ) $without_alt++; }
		?>
		<div class="wrap glidara-pro-page"><div class="glidara-brand"><span class="glidara-brand__mark">G</span><div><h1><?php esc_html_e( 'Glidara Tools & Health', 'glidara-slider' ); ?></h1><p><?php esc_html_e( 'Migration, diagnostics and maintenance in one place.', 'glidara-slider' ); ?></p></div></div>
		<div class="glidara-feature-grid"><article><span class="dashicons dashicons-images-alt2"></span><h3><?php echo absint( count( $sliders ) ); ?> <?php esc_html_e( 'sliders', 'glidara-slider' ); ?></h3><p><?php esc_html_e( 'Published and draft sliders.', 'glidara-slider' ); ?></p></article><article><span class="dashicons dashicons-warning"></span><h3><?php echo absint( $without_alt ); ?> <?php esc_html_e( 'missing alt text', 'glidara-slider' ); ?></h3><p><?php esc_html_e( 'Improve accessibility and image context.', 'glidara-slider' ); ?></p></article><article><span class="dashicons dashicons-heart"></span><h3><?php echo $without_slides ? esc_html__( 'Needs attention', 'glidara-slider' ) : esc_html__( 'Healthy', 'glidara-slider' ); ?></h3><p><?php echo esc_html( sprintf( __( '%d empty slide records found.', 'glidara-slider' ), $without_slides ) ); ?></p></article></div>
		<h2><?php esc_html_e( 'Import slider', 'glidara-slider' ); ?></h2><form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" enctype="multipart/form-data"><input type="hidden" name="action" value="glidara_slider_import"><?php wp_nonce_field( 'glidara_slider_import' ); ?><input type="file" name="slider_json" accept="application/json" required> <?php submit_button( __( 'Import JSON', 'glidara-slider' ), 'secondary', 'submit', false ); ?></form>
		<h2><?php esc_html_e( 'Maintenance', 'glidara-slider' ); ?></h2><form method="post" action="options.php"><?php settings_fields( 'glidara_slider_tools' ); ?><p><label><input type="checkbox" name="glidara_slider_debug" value="1" <?php checked( get_option( 'glidara_slider_debug', 0 ) ); ?>> <?php esc_html_e( 'Enable debug mode', 'glidara-slider' ); ?></label></p><p><label><input type="checkbox" name="glidara_slider_retain_data" value="1" <?php checked( get_option( 'glidara_slider_retain_data', 1 ) ); ?>> <?php esc_html_e( 'Retain slider data when the plugin is deleted', 'glidara-slider' ); ?></label></p><?php submit_button(); ?></form>
		<h2><?php esc_html_e( 'System information', 'glidara-slider' ); ?></h2><textarea class="large-text code" rows="7" readonly><?php echo esc_textarea( 'Glidara: ' . GLIDARA_SLIDER_VERSION . "\nWordPress: " . get_bloginfo( 'version' ) . "\nPHP: " . PHP_VERSION . "\nTheme: " . wp_get_theme()->get( 'Name' ) . "\nMemory limit: " . WP_MEMORY_LIMIT . "\nSliders: " . count( $sliders ) ); ?></textarea></div>
		<?php
	}

	public function classic_button() {
		if ( ! current_user_can( 'edit_posts' ) ) return;
		echo '<button type="button" class="button" id="glidara-insert-shortcode"><span class="dashicons dashicons-images-alt2" style="vertical-align:text-top"></span> ' . esc_html__( 'Add Glidara Slider', 'glidara-slider' ) . '</button>';
	}

	public function classic_button_script() {
		if ( ! current_user_can( 'edit_posts' ) ) return;
		?><script>document.getElementById('glidara-insert-shortcode')?.addEventListener('click',function(){const id=window.prompt('<?php echo esc_js( __( 'Enter the slider ID', 'glidara-slider' ) ); ?>');if(id&&/^\d+$/.test(id)){window.send_to_editor('[glidara_slider id="'+id+'"]')}});</script><?php
	}

	public function register_block() {
		wp_register_script( 'glidara-slider-block', GLIDARA_SLIDER_URL . 'blocks/slider/index.js', array( 'wp-blocks', 'wp-element', 'wp-components', 'wp-block-editor' ), GLIDARA_SLIDER_VERSION, true );
		$choices = array( array( 'label' => __( 'Choose a slider', 'glidara-slider' ), 'value' => 0 ) );
		foreach ( get_posts( array( 'post_type' => 'glidara_slider', 'post_status' => 'publish', 'numberposts' => -1 ) ) as $slider ) $choices[] = array( 'label' => $slider->post_title, 'value' => $slider->ID );
		wp_localize_script( 'glidara-slider-block', 'glidaraSliderBlock', array( 'choices' => $choices ) );
		register_block_type( GLIDARA_SLIDER_DIR . 'blocks/slider', array( 'render_callback' => array( $this, 'render_block' ) ) );
	}

	public function render_block( $attributes ) {
		return empty( $attributes['id'] ) ? '' : do_shortcode( '[glidara_slider id="' . absint( $attributes['id'] ) . '"]' );
	}

	public function register_widget() {
		register_widget( 'Glidara_Slider_Widget' );
	}
}

class Glidara_Slider_Widget extends WP_Widget {
	public function __construct() { parent::__construct( 'glidara_slider_widget', __( 'Glidara Slider', 'glidara-slider' ), array( 'description' => __( 'Display a published Glidara slider.', 'glidara-slider' ) ) ); }
	public function widget( $args, $instance ) { echo $args['before_widget']; echo do_shortcode( '[glidara_slider id="' . absint( $instance['id'] ?? 0 ) . '"]' ); echo $args['after_widget']; } // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	public function form( $instance ) { $id = absint( $instance['id'] ?? 0 ); ?><p><label><?php esc_html_e( 'Slider ID', 'glidara-slider' ); ?><input class="widefat" name="<?php echo esc_attr( $this->get_field_name( 'id' ) ); ?>" type="number" value="<?php echo absint( $id ); ?>"></label></p><?php }
	public function update( $new_instance, $old_instance ) { return array( 'id' => absint( $new_instance['id'] ?? 0 ) ); }
}
