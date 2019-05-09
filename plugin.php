<?php
/*
 * Plugin Name: Lazy Blocks Multilingual
 * Description: WPML Compatibility for Lazy Blocks Plugin
 *
 */

class WPML_Lazy_Blocks {
	public function add_hooks() {
		add_filter( 'wpml_found_strings_in_block', array( $this, 'add_block_data_attribute_strings' ), 10, 2 );
		add_filter( 'wpml_update_strings_in_block', array( $this, 'update_block_data_attribute'), 10, 3 );
	}

	public function add_block_data_attribute_strings( array $strings, WP_Block_Parser_Block $block ) {

		if ( $this->is_lazy_block( $block ) ) {

			foreach ( $block->attrs as $field_name => $text ) {

				if ( $this->must_skip( $field_name, $text ) ) {
					continue;
				}

				$type = $this->get_text_type( $text );

				$strings[] = (object) array(
					'id'    => $this->get_string_hash( $block->blockName, $text ),
					'name'  => $this->get_string_name( $block,  $field_name ),
					'value' => $text,
					'type'  => $type,
				);
			}
		}

		return $strings;
	}

	public function update_block_data_attribute( WP_Block_Parser_Block $block, array $string_translations, $lang ) {

		if ( $this->is_lazy_block( $block ) ) {

			foreach ( $block->attrs as $field_name => $text ) {

				if ( $this->is_system_field( $field_name ) ) {
					continue;
				}

				$string_hash = $this->get_string_hash( $block->blockName, $text );

				if ( isset( $string_translations[ $string_hash ][ $lang ]['status'] )
				     && $string_translations[ $string_hash ][ $lang ]['status'] == ICL_TM_COMPLETE
				     && isset( $string_translations[ $string_hash ][ $lang ]['value'] )
				) {
					$block->attrs[ $field_name ] = $string_translations[ $string_hash ][ $lang ]['value'];
				}
			}
		}

		return $block;
	}

	private function is_lazy_block( $block ) {
		return strpos( $block->blockName, 'lazyblock/' ) === 0;
	}

	private function get_text_type( $text ) {
		$type = 'LINE';
		if ( strpos( $text, "\n" ) !== false ) {
			$type = 'AREA';
		}
		if ( strpos( $text, '<' ) !== false ) {
			$type = 'VISUAL';
		}
		return $type;
	}

	private function get_string_hash( $block_name, $text ) {
		return md5( $block_name . $text );
	}

	private function get_string_name( WP_Block_Parser_Block $block, $field_name ) {
		return $block->blockName . '/' . $field_name;
	}

	private function must_skip( $field_name, $text ) {
		return $this->is_system_field( $field_name ) || ( ! is_string( $text ) && ! is_numeric( $text ) );
	}

	private function is_system_field( $field_name ) {
		$re = '/[A-Z]/m'; // Lazy Blocks system field names contains uppercase letters which is not allowed for user provided fields names
		preg_match_all($re, $field_name, $matches, PREG_SET_ORDER, 0);
		return ! empty( $matches );
	}


}

$WPML_Lazy_Blocks = new WPML_Lazy_Blocks();
$WPML_Lazy_Blocks->add_hooks();