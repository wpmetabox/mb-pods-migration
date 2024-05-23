<?php
namespace MetaBox\Pods\Processors;

class Relationship extends Base {
	protected function get_items() {
		global $wpdb;
		$sql = "SELECT id FROM `{$wpdb->prefix}podsrel`";
		return $wpdb->get_col( $sql );
	}

	protected function migrate_item() {
		$items = $this->get_items();
		foreach ( $items as $id ) {
			$this->migrate_values( $id );
		}

		wp_send_json_success( [ 
			'message' => __( 'Done', 'mb-pods-migration' ),
			'type'    => 'done',
		] );
	}

	private function migrate_values( $id ) {
		list( $item_id, $related_item_id, $slug, $weight ) = $this->get_data( $id );
		global $wpdb;
		$sql    = "INSERT INTO `{$wpdb->prefix}mb_relationships` (`from`, `to`, `type`, `order_from`) VALUES (%d, %d, %s, %d)";
		$from   = $wpdb->get_results( "SELECT `from`, `to` FROM `{$wpdb->prefix}mb_relationships` WHERE `type` = '{$slug}'" );
		$object = (object) [ 
			'from' => $item_id,
			'to'   => $related_item_id,
		];
		if ( self::objectInArray( $object, $from ) ) {
			return;
		} else {
			$wpdb->query( $wpdb->prepare( $sql, (int) $item_id, (int) $related_item_id, $slug, (int) $weight ) );
		}
	}

	private function get_data( $id ) {
		global $wpdb;
		$item_id         = $this->get_col_single_value( 'podsrel', 'item_id', 'id', $id );
		$related_item_id = $this->get_col_single_value( 'podsrel', 'related_item_id', 'id', $id );
		$field_id        = $this->get_col_single_value( 'podsrel', 'field_id', 'id', $id );
		$queried_post    = get_post( $field_id );
		$type            = $queried_post->post_name;
		$weight          = $this->get_col_single_value( 'podsrel', 'weight', 'id', $id );

		return [ $item_id, $related_item_id, $type, $weight ];
	}

	private function get_col_single_value( $table, $col, $conditional_col, $conditional_value ) {
		global $wpdb;
		$sql = "SELECT `{$col}` FROM `{$wpdb->prefix}{$table}` WHERE `{$conditional_col}`=%s";
		return $wpdb->get_var( $wpdb->prepare( $sql, $conditional_value ) );
	}

	private function get_col_values( $table, $col, $conditional_col, $conditional_value ) {
		global $wpdb;
		$sql = "SELECT `{$col}`  FROM `{$wpdb->prefix}{$table}` WHERE `{$conditional_col}`=%s";
		return $wpdb->get_col( $wpdb->prepare( $sql, $conditional_value ) );
	}
	private function objectInArray( $object, $array ) {
		foreach ( $array as $item ) {
			if ( $item->from === $object->from && $item->to === $object->to ) {
				return true;
			}
		}
		return false;
	}
}
