<?php
namespace MetaBox\Pods\Processors;

use MetaBox\Support\Data as Helper;
use MBBParser\Parsers\MetaBox;
use WP_Query;

class Terms extends Base {
	protected $object_type = 'term';

	private $post_id;
	private $settings = [];
	private $fields = [];

	protected function get_items() {

		if ( $_SESSION[ 'processed' ] ) {
			return [];
		}

		$query = new WP_Query( [ 
			'post_type'              => '_pods_group',
			'post_status'            => 'publish',
			'posts_per_page'         => -1,
			'no_found_rows'          => true,
			'update_post_meta_cache' => false,
			'update_post_term_cache' => false,
		] );

		return $query->posts;
	}

	protected function get_fields() {
		// Process all field groups at once.
		if ( $_SESSION[ 'processed' ] ) {
			return [];
		}

		$query = new WP_Query( [ 
			'post_type'              => '_pods_field',
			'post_status'            => 'publish',
			'posts_per_page'         => -1,
			'no_found_rows'          => true,
			'update_post_meta_cache' => false,
			'update_post_term_cache' => false,
		] );

		return $query->posts;
	}

	protected function migrate_item() {
		$items = $this->get_items();
		foreach ( $items as $item ) {
			$parent = $item->post_parent;
			$type   = get_post_meta( $parent, 'type' );

			if ( implode( $type ) == 'taxonomy' ) {
				$this->post_id  = null;
				$this->settings = [];
				$this->fields   = [];
				$setting        = $this->migrate_settings( $item );
				$fields         = $this->migrate_field( $item );
				$data           = [ 
					'post_name'  => $item->post_name,
					'post_title' => $item->post_title,
					'fields'     => $fields,
					'settings'   => $setting,
				];
				$parser         = new MetaBox( $data );
				$parser->parse();
				$this->create_post( $item );
				$this->save_id();
				update_post_meta( $this->post_id, 'fields', $fields );
				update_post_meta( $this->post_id, 'settings', $setting );
				update_post_meta( $this->post_id, 'meta_box', $parser->get_settings() );

				$this->disable_post( $item );
			}
		}
	}

	private function create_post( $group ) {
		$data = [ 
			'post_title'        => $group->post_title,
			'post_type'         => 'meta-box',
			'post_status'       => $group->post_status,
			'post_name'         => $group->post_name,
			'post_content'      => $group->post_content,
			'post_date'         => $group->post_date,
			'post_date_gmt'     => $group->post_date_gmt,
			'post_modified'     => $group->post_modified,
			'post_modified_gmt' => $group->post_modified_gmt,
		];

		$post_id = get_post_meta( $group->ID, 'meta_box_id', true );
		if ( $post_id ) {
			$this->post_id = $data[ 'ID' ] = $post_id;
			wp_update_post( $data );
		} else {
			$this->post_id = wp_insert_post( $data );
		}

		update_post_meta( $group->ID, 'meta_box_id', $this->post_id );
	}

	private function disable_post( $item ) {
		$data = [ 
			'ID'          => $item->ID,
			'post_status' => 'draft',
		];
		wp_update_post( $data );
	}

	private function migrate_settings( $item ) {
		$this->migrate_location( $item );
		return $this->settings;
	}

	private function migrate_location( $item ) {
		$post_parent = $item->post_parent;
		$object_type = get_post_meta( $post_parent, 'type' );
		$object      = get_post_meta( $post_parent, 'object' );

		if ( implode( $object_type ) == 'taxonomy' ) {
			$this->settings[ 'object_type' ] = 'term';
		}
		$this->settings[ 'taxonomies' ] = $object;
	}

	private function migrate_field( $group ) {
		$fields = $this->get_fields();
		$args   = [];
		foreach ( $fields as $field ) {
			$id           = $field->ID;
			$name         = $field->post_title;
			$slug         = $field->post_name;
			$types        = get_post_meta( $id, 'type' );
			$option       = '';
			$html_content = '';
			$desc         = '';
			switch ( implode( $types ) ) {
				case 'website':
					$type = 'url';
					break;
				case 'phone':
					$type = 'text';
					break;
				case 'paragraph':
					$type = 'textarea';
					break;
				case 'code':
					$type = 'textarea';
					break;
				case 'currency':
					$type = 'text';
					break;
				case 'file':
					$type = 'file_advanced';
					break;
				case 'boolean':
					$type = 'radio';
					$option = [ 
						'1' => __( 'Yes', 'mb-pods-migration' ),
						'0' => __( 'No', 'mb-pods-migration' ),
					];
					break;
				case 'html':
					$type = 'custom_html';
					$html_content = get_post_meta( $id, 'html_content' );
					break;
				case 'heading':
					$desc = get_post_meta( $id, 'heading_tag' );
					break;
				default:
					$type = implode( $types );
					break;
			}
			$group_id = get_post_meta( $id, 'group' );
			if ( implode( $group_id ) == $group->ID ) {
				$args[] = [ 
					'name'       => $name,
					'id'         => $slug,
					'type'       => $type,
					'options'    => $option,
					'std'        => implode( $html_content ),
					'desc'       => implode( $desc ),
					'save_field' => true,
				];
			}
		}
		return $args;
	}

	private function save_id() {
		$object_type = $this->settings[ 'object_type' ];

		if ( empty( $_SESSION[ 'field_groups' ] ) ) {
			$_SESSION[ 'field_groups' ] = [];
		}
		if ( empty( $_SESSION[ 'field_groups' ][ $object_type ] ) ) {
			$_SESSION[ 'field_groups' ][ $object_type ] = [];
		}
		$_SESSION[ 'field_groups' ][ $object_type ][] = $this->item->ID;
	}
}
