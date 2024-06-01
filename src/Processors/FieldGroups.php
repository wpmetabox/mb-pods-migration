<?php
namespace MetaBox\Pods\Processors;

use WP_Query;
use MetaBox\Support\Data;
use MBBParser\Parsers\MetaBox;

class FieldGroups extends Base {
	private $post_id;
	private $settings = [];
	private $fields = [];

	protected function get_items() {
		// Process all field groups at once.
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
		$this->post_id  = null;
		$this->settings = [];
		$this->fields   = [];
		$setting        = $this->migrate_settings();
		$fields         = $this->migrate_fields();

		$data   = [
			'post_name'  => $this->item->post_name,
			'post_title' => $this->item->post_title,
			'fields'     => $fields,
			'settings'   => $setting,
		];
		$parser = new MetaBox( $data );
		$parser->parse();
		$this->create_post();
		$this->save_id();
		update_post_meta( $this->post_id, 'fields', $fields );
		update_post_meta( $this->post_id, 'settings', $setting );
		update_post_meta( $this->post_id, 'meta_box', $parser->get_settings() );

		$this->disable_post();
		//$this->delete_post();
	}

	private function create_post() {
		$data = [
			'post_title'        => $this->item->post_title,
			'post_type'         => 'meta-box',
			'post_status'       => $this->item->post_status,
			'post_name'         => $this->item->post_name,
			'post_content'      => $this->item->post_content,
			'post_date'         => $this->item->post_date,
			'post_date_gmt'     => $this->item->post_date_gmt,
			'post_modified'     => $this->item->post_modified,
			'post_modified_gmt' => $this->item->post_modified_gmt,
		];

		$post_id = get_post_meta( $this->item->ID, 'meta_box_id', true );
		if ( $post_id ) {
			$this->post_id = $data[ 'ID' ] = $post_id;
			wp_update_post( $data );
		} else {
			$this->post_id = wp_insert_post( $data );
		}

		update_post_meta( $this->item->ID, 'meta_box_id', $this->post_id );
	}

	private function disable_post() {
		$data = [
			'ID'          => $this->item->ID,
			'post_status' => 'draft',
		];
		wp_update_post( $data );
	}

	private function delete_post() {
		wp_delete_post( $this->item->ID );
	}

	private function migrate_settings() {
		$this->migrate_location();
		return $this->settings;
	}

	private function migrate_location() {
		$post_parent = $this->item->post_parent;
		$object_type = get_post_meta( $post_parent, 'type' );

		$this->settings[ 'object_type' ] = implode( $object_type );
		$this->settings[ 'post_types' ]  = $object_type;

	}

	private function migrate_fields() {
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
			$group = get_post_meta( $id, 'group' );
			if ( implode( $group ) == $this->item->ID ) {
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
