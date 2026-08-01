<?php

defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

if ( get_option( 'glidara_slider_retain_data', 1 ) ) return;

$sliders = get_posts( array( 'post_type' => 'glidara_slider', 'post_status' => 'any', 'numberposts' => -1, 'fields' => 'ids' ) );
foreach ( $sliders as $slider_id ) wp_delete_post( $slider_id, true );
delete_option( 'glidara_slider_debug' );
delete_option( 'glidara_slider_retain_data' );
