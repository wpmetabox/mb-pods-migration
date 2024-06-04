<?php
namespace MetaBox\Pods\Processors;

use MetaBox\Support\Arr;
use WP_Query;

class PostTypes extends Base {

	protected function get_items() {

		if ( $_SESSION[ 'processed' ] ) {
			return [];
		}

		$query = new WP_Query( [
			'post_type'              => '_pods_pod',
			'post_status'            => 'any',
			'posts_per_page'         => -1,
			'no_found_rows'          => true,
			'update_post_meta_cache' => false,
			'update_post_term_cache' => false,
		] );

		return $query->posts;
	}

	protected function migrate_item() {
		$items = $this->get_items();
		foreach ( $items as $key => $item ) {
			$tax  = get_post_meta( $item->ID, 'type' );
			$name = $item->post_title;
			$slug = $item->post_name;
			if ( implode( $tax ) === 'post_type' ) {
				$labels  = [
					'name'                     => esc_html__( $name, 'mb-pods-migration' ),
					'singular_name'            => esc_html__( $name, 'mb-pods-migration' ),
					'add_new'                  => esc_html__( 'Add new', 'mb-pods-migration' ),
					'add_new_item'             => esc_html__( 'Add new' . $name, 'mb-pods-migration' ),
					'edit_item'                => esc_html__( 'Edit ' . $name, 'mb-pods-migration' ),
					'new_item'                 => esc_html__( 'New ' . $name, 'mb-pods-migration' ),
					'view_item'                => esc_html__( 'View ' . $name, 'mb-pods-migration' ),
					'view_items'               => esc_html__( 'View ' . $name, 'mb-pods-migration' ),
					'search_items'             => esc_html__( 'Search ' . $name, 'mb-pods-migration' ),
					'not_found'                => esc_html__( 'No ' . $name . ' found.', 'mb-pods-migration' ),
					'not_found_in_trash'       => esc_html__( 'No ' . $name . ' found in Trash.', 'mb-pods-migration' ),
					'parent_item_colon'        => esc_html__( 'Parent ' . $name . ':', 'mb-pods-migration' ),
					'all_items'                => esc_html__( 'Toàn bộ ' . $name, 'mb-pods-migration' ),
					'archives'                 => esc_html__( $name . ' Archives', 'mb-pods-migration' ),
					'attributes'               => esc_html__( $name . ' Attributes', 'mb-pods-migration' ),
					'insert_into_item'         => esc_html__( 'Insert into ' . $name, 'mb-pods-migration' ),
					'uploaded_to_this_item'    => esc_html__( 'Uploaded to this ' . $name, 'mb-pods-migration' ),
					'featured_image'           => esc_html__( 'Featured image', 'mb-pods-migration' ),
					'set_featured_image'       => esc_html__( 'Set featured image', 'mb-pods-migration' ),
					'remove_featured_image'    => esc_html__( 'Remove featured image', 'mb-pods-migration' ),
					'use_featured_image'       => esc_html__( 'Use as featured image', 'mb-pods-migration' ),
					'menu_name'                => esc_html__( $name, 'mb-pods-migration' ),
					'filter_items_list'        => esc_html__( 'Filter ' . $name . ' list', 'mb-pods-migration' ),
					'filter_by_date'           => esc_html__( '', 'mb-pods-migration' ),
					'items_list_navigation'    => esc_html__( $name . ' list navigation', 'mb-pods-migration' ),
					'items_list'               => esc_html__( $name . ' list', 'mb-pods-migration' ),
					'item_published'           => esc_html__( $name . ' published.', 'mb-pods-migration' ),
					'item_published_privately' => esc_html__( $name . ' published privately.', 'mb-pods-migration' ),
					'item_reverted_to_draft'   => esc_html__( $name . ' reverted to draft.', 'mb-pods-migration' ),
					'item_scheduled'           => esc_html__( $name . ' scheduled.', 'mb-pods-migration' ),
					'item_updated'             => esc_html__( $name . ' updated.', 'mb-pods-migration' ),
				];
				$args    = [
					'slug'                => $slug,
					'label'               => esc_html__( $name, 'mb-pods-migration' ),
					'labels'              => $labels,
					'description'         => '',
					'public'              => true,
					'hierarchical'        => true,
					'exclude_from_search' => false,
					'publicly_queryable'  => true,
					'show_ui'             => true,
					'show_in_nav_menus'   => true,
					'show_in_admin_bar'   => true,
					'show_in_rest'        => true,
					'query_var'           => true,
					'can_export'          => true,
					'delete_with_user'    => true,
					'has_archive'         => true,
					'rest_base'           => '',
					'show_in_menu'        => true,
					'menu_position'       => '',
					'menu_icon'           => 'dashicons-admin-generic',
					'capability_type'     => 'post',
					'supports'            => [ 'title', 'editor', 'thumbnail' ],
					'taxonomies'          => [],
					'rewrite'             => [
						'with_front' => false,
					],
				];
				$content = wp_json_encode( $args, JSON_UNESCAPED_UNICODE );
				$content = str_replace( '"1"', 'true', $content );
				$post_id = $this->get_id_by_slug( $slug, 'mb-post-type' );
				if ( $post_id ) {
					wp_update_post( [
						'ID'           => $post_id,
						'post_content' => $content,
					] );
				} else {
					wp_insert_post( [
						'post_content' => $content,
						'post_type'    => 'mb-post-type',
						'post_title'   => $name,
						'post_status'  => 'publish',
						'post_name'    => $slug,
					] );
				}
			}
		}

		wp_send_json_success( [
			'message' => __( 'Done', 'mb-pods-migration' ),
			'type'    => 'done',
		] );
	}

}
