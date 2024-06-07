<?php
/**
 * Plugin Name: MB Pods Migration
 * Plugin URI:  https://metabox.io/plugins/mb-pods-migration
 * Description: Migrate Pods custom fields to Meta Box.
 * Version:     1.0.0
 * Author:      MetaBox.io
 * Author URI:  https://metabox.io
 * License:     GPL2+
 * Text Domain: mb-pods-migration
 * Domain Path: /languages/
 */

defined( 'ABSPATH' ) || die;
/*	$query = new WP_Query( [ 
			'post_type'              => '_pods_pod',
			'post_status'            => 'any',
			'posts_per_page'         => -1,
			'no_found_rows'          => true,
			'update_post_meta_cache' => false,
			'update_post_term_cache' => false,
		] );

var_dump( $query->posts);die;*/
/*$item = [ 'a' => get_post_meta( 408, 'built_in_taxonomies_danh-muc-san-pham', true ) ];
$a = wp_json_encode( $item, JSON_UNESCAPED_UNICODE );
var_dump( $a ); die;*/
if ( ! function_exists( 'mb_pods_load' ) ) {
	if ( file_exists( __DIR__ . '/vendor' ) ) {
		require __DIR__ . '/vendor/autoload.php';
	}

	add_action( 'init', 'mb_pods_load', 0 );

	function mb_pods_load() {
		if ( ! defined( 'RWMB_VER' ) || ! defined( 'PODS_DIR' ) || ! is_admin() ) {
			return;
		}

		define( 'MBPODS_DIR', __DIR__ );

		new MetaBox\Pods\AdminPage;
		new MetaBox\Pods\Ajax;
	}
}