<?php
namespace MetaBox\Pods\Processors;

use MetaBox\Support\Arr;

abstract class Base {
	protected $threshold = 10;
	public $item;
	protected $object_type;
	protected $field_group_ids = null;

	public function migrate() {
		$items = $this->get_items();
		if ( empty( $items ) ) {
			wp_send_json_success( [
				'message' => __( 'Done', 'mb-pods-migration' ),
				'type'    => 'done',
			] );
		}

		$output = [];
		foreach ( $items as $item ) {
			$this->item = $item;
			$output[]   = $this->migrate_item();
		}
		$output = array_filter( $output );

		$_SESSION[ 'processed' ] += count( $items );
		wp_send_json_success( [
			'message' => sprintf( __( 'Processed %d items...', 'mb-pods-migration' ), $_SESSION[ 'processed' ] ) . '<br>' . implode( '<br>', $output ),
			'type'    => 'continue',
		] );
	}

	abstract protected function get_items();
	abstract protected function migrate_item();

	public function get( $key, $single = true ) {
		return get_metadata( $this->object_type, $this->item, $key, $single );
	}

	public function add( $key, $value ) {
		add_metadata( $this->object_type, $this->item, $key, $value, false );
	}

	public function update( $key, $value ) {
		update_metadata( $this->object_type, $this->item, $key, $value );
	}

	public function delete( $key ) {
		delete_metadata( $this->object_type, $this->item, $key );
	}

	protected function get_field_group_ids() {
		if ( null !== $this->field_group_ids ) {
			return $this->field_group_ids;
		}

		$this->field_group_ids = array_unique( Arr::get( $_SESSION, "field_groups.{$this->object_type}", [] ) );

		return $this->field_group_ids;
	}

	public function get_id_by_slug( $slug, $post_type ) {
		global $wpdb;
		if ( ! $slug ) {
			return null;
		}
		$sql = "SELECT ID FROM $wpdb->posts WHERE post_type=%s AND post_name LIKE %s";
		$id  = $wpdb->get_var( $wpdb->prepare( $sql, $post_type, $slug ) );

		return $id;
	}

	public function get_col_values( $post_id, $search ) {
		global $wpdb;
		$sql  = "SELECT meta_key  FROM $wpdb->postmeta WHERE post_id=%d AND meta_key LIKE %s";
		$s    = '%' . $wpdb->esc_like( $search ) . '%';
		$cols = $wpdb->get_col( $wpdb->prepare( $sql, $post_id, $s ) );
		$checks  = [];
		foreach ( $cols as $col ) {
			if ( get_post_meta( $post_id, $col, true ) ) {
				$checks[] = $col;
			}
		}

		$values = [];
		foreach ( $checks as $check ){
			$values[] = str_replace( $search,'', $check );
		}
		return $values;
	}
}
