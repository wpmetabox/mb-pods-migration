<?php
namespace MetaBox\Pods\Processors;

use MetaBox\Support\Arr;
use WP_Query;
use MBBParser\Parsers\MetaBox;

class SettingsPages extends Base {
	private $post_id;
	private $settings = [];
	private $fields = [];
	protected $object_type = 'setting';

	protected function get_items() {

		// Process all settings pages at once.
		if ( $_SESSION[ 'processed' ] ) {
			return [];
		}

		$query = new WP_Query( [ 
			'post_type'              => '_pods_pod',
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
			'order'                  => 'ASC',
			'orderby'                => 'ID',
			'no_found_rows'          => true,
			'update_post_meta_cache' => false,
			'update_post_term_cache' => false,
		] );

		return $query->posts;
	}

	protected function migrate_item() {
		$this->create_settings_page();
		$this->migrate_fields();
		$this->migrate_value();
	}

	private function create_settings_page() {
		if ( ! class_exists( 'MBB\SettingsPage\Parser' ) ) {
			return;
		}
		$settings = $this->item;
		$type     = get_post_meta( $settings->ID, 'type' );

		if ( implode( $type ) == 'settings' ) {
			$data    = [ 
				'post_title'  => $settings->post_title,
				'post_type'   => 'mb-settings-page',
				'post_status' => 'publish',
				'post_name'   => $settings->post_name,
			];
			$post_id = $this->get_id_by_slug( $settings->post_name, 'mb-settings-page' );
			if ( $post_id ) {
				$this->post_id = $data[ 'ID' ] = $post_id;
				wp_update_post( $data );
			} else {
				$this->post_id = wp_insert_post( $data );
			}

			$menu_location = get_post_meta( $settings->ID, 'menu_location' );
			$icon_url      = 'dashicons-admin-generic';
			$menu_type     = '';
			switch ( implode( $menu_location ) ) {
				case 'appearances':
					$parent = 'themes.php';
					break;
				case 'submenu':
					$parent = implode( get_post_meta( $settings->ID, 'menu_location_custom' ) );
					break;
				case 'top':
					$parent = '';
					$menu_type = 'top';
				default:
					$parent = 'options-general.php';
					break;
			}
			$parser = [ 
				'menu_title' => $settings->post_title,
				'id'         => $settings->post_name,
				'menu_type'  => $menu_type,
				'parent'     => $parent,
				'icon_url'   => $icon_url,
				'style'      => 'no-boxes',
				'columns'    => 1,
			];
			update_post_meta( $this->post_id, 'settings', $parser );
			update_post_meta( $this->post_id, 'settings_page', $parser );
		}
	}

	private function get_groups() {
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

	private function migrate_fields() {
		$groups = $this->get_groups();
		foreach ( $groups as $group ) {
			$parent = $group->post_parent;
			$type   = get_post_meta( $parent, 'type' );
			if ( implode( $type ) == 'settings' ) {
				$this->post_id  = null;
				$this->settings = [];
				$this->fields   = [];
				$setting        = $this->migrate_settings( $group );
				$fields         = $this->migrate_field( $group );
				$data           = [ 
					'post_name'  => $group->post_name,
					'post_title' => $group->post_title,
					'fields'     => $fields,
					'settings'   => $setting,
				];
				$parser         = new MetaBox( $data );
				$parser->parse();
				$this->create_post( $group );
				update_post_meta( $this->post_id, 'fields', $fields );
				update_post_meta( $this->post_id, 'settings', $setting );
				update_post_meta( $this->post_id, 'meta_box', $parser->get_settings() );

				$this->disable_post( $group );
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

	private function migrate_settings( $group ) {
		$this->migrate_location( $group );
		return $this->settings;
	}

	private function migrate_location( $group ) {
		$post_parent = $group->post_parent;
		$object_type = get_post_meta( $post_parent, 'type' );

		$this->settings[ 'object_type' ]    = implode( $object_type );
		$this->settings[ 'post_types' ]     = $object_type;
		$this->settings[ 'settings_pages' ] = $this->item->post_name;

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
	private function migrate_value() {
		$fields = $this->get_fields();
		$args   = [];
		foreach ( $fields as $field ) {
			$slug        = $field->post_name;
			$option_name = $this->item->post_name . '_' . $slug;
			$value       = get_option( $option_name );

			$args[ $slug ] = $value;
		}
		update_option( $this->item->post_name, $args );
	}
	private function disable_post( $group ) {
		$data = [ 
			'ID'          => $group->ID,
			'post_status' => 'draft',
		];
		wp_update_post( $data );
	}
}