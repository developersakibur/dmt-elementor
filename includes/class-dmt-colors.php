<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Reads Global Colors (System + Custom) from Elementor's active Kit.
 * Works straight from the database, so it does not depend on Elementor's PHP classes being loaded.
 */
class DMT_Colors {

	public static function kit_id() {
		return (int) get_option( 'elementor_active_kit', 0 );
	}

	/**
	 * @return array[] Each: id, title, color, type (system|custom), var
	 */
	public static function get() {
		static $cache = null;
		if ( null !== $cache ) {
			return $cache;
		}

		$list = array();
		$kit  = self::kit_id();
		$meta = $kit ? get_post_meta( $kit, '_elementor_page_settings', true ) : array();
		if ( ! is_array( $meta ) ) {
			$meta = array();
		}

		$system = ( isset( $meta['system_colors'] ) && is_array( $meta['system_colors'] ) ) ? $meta['system_colors'] : array();
		if ( ! $system ) {
			// Elementor defaults, used only if the kit has never saved its colors.
			$system = array(
				array( '_id' => 'primary', 'title' => 'Primary', 'color' => '#6EC1E4' ),
				array( '_id' => 'secondary', 'title' => 'Secondary', 'color' => '#54595F' ),
				array( '_id' => 'text', 'title' => 'Text', 'color' => '#7A7A7A' ),
				array( '_id' => 'accent', 'title' => 'Accent', 'color' => '#61CE70' ),
			);
		}
		$custom = ( isset( $meta['custom_colors'] ) && is_array( $meta['custom_colors'] ) ) ? $meta['custom_colors'] : array();

		foreach ( array( 'system' => $system, 'custom' => $custom ) as $type => $items ) {
			foreach ( $items as $item ) {
				if ( ! is_array( $item ) || empty( $item['_id'] ) ) {
					continue;
				}
				$id = preg_replace( '/[^A-Za-z0-9_-]/', '', (string) $item['_id'] );
				if ( '' === $id ) {
					continue;
				}
				$color = isset( $item['color'] ) ? trim( (string) $item['color'] ) : '';
				if ( '' === $color ) {
					continue;
				}
				$list[] = array(
					'id'    => $id,
					'title' => isset( $item['title'] ) ? (string) $item['title'] : $id,
					'color' => $color,
					'type'  => $type,
					'var'   => '--e-global-color-' . $id,
				);
			}
		}

		$cache = $list;
		return $cache;
	}
}
