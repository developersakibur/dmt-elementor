<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Stores and sanitizes all plugin settings in a single option.
 *
 * Groups: dark (color id => hex), design (toggle look), general, advanced.
 */
class DMT_Settings {

	const OPTION = 'dmt_settings';

	public static function defaults() {
		return array(
			'dark'     => array(),
			'design'   => array(
				'style'        => 'button',
				'size'         => 40,
				'radius'       => 999,
				'border_width' => 1,
				'shadow'       => 0,
				'bg'           => '#F7FAFC',
				'icon'         => '#0F1629',
				'border'       => '#E2E6ED',
				'bg_h'         => '#EEF2F7',
				'bg_d'         => '#1D2739',
				'icon_d'       => '#FFFFFF',
				'border_d'     => '#2A3650',
				'bg_h_d'       => '#263248',
			),
			'general'  => array(
				'storage_key'  => 'dmt_theme',
				'transition'   => 300,
				'color_scheme' => 1,
			),
			'advanced' => array(
				'dark_css' => '',
			),
		);
	}

	/** Saved settings merged over defaults, always validated. */
	public static function get() {
		$saved = get_option( self::OPTION, array() );
		if ( ! is_array( $saved ) ) {
			$saved = array();
		}
		$def = self::defaults();

		$dark = ( isset( $saved['dark'] ) && is_array( $saved['dark'] ) ) ? $saved['dark'] : array();

		return array(
			'dark'     => self::clean_dark( $dark ),
			'design'   => self::clean_design( array_merge( $def['design'], isset( $saved['design'] ) && is_array( $saved['design'] ) ? $saved['design'] : array() ) ),
			'general'  => self::clean_general( array_merge( $def['general'], isset( $saved['general'] ) && is_array( $saved['general'] ) ? $saved['general'] : array() ) ),
			'advanced' => self::clean_advanced( array_merge( $def['advanced'], isset( $saved['advanced'] ) && is_array( $saved['advanced'] ) ? $saved['advanced'] : array() ) ),
		);
	}

	/* ---------------------------------------------------------------
	 * Validators
	 * ------------------------------------------------------------- */

	/** #RGB, #RRGGBB or #RRGGBBAA (the # is optional). Empty string if invalid. */
	public static function hex( $v ) {
		$v = trim( (string) $v );
		if ( '' === $v ) {
			return '';
		}
		if ( '#' !== $v[0] ) {
			$v = '#' . $v;
		}
		return preg_match( '/^#(?:[0-9a-f]{3}|[0-9a-f]{6}|[0-9a-f]{8})$/i', $v ) ? strtoupper( $v ) : '';
	}

	public static function hex6( $v, $fallback ) {
		$v = trim( (string) $v );
		return preg_match( '/^#[0-9a-f]{6}$/i', $v ) ? strtoupper( $v ) : $fallback;
	}

	/** Safe-to-print CSS color (hex / rgb / hsl) for swatches. Empty string otherwise. */
	public static function css_color( $v ) {
		$v = trim( (string) $v );
		if ( preg_match( '/^#(?:[0-9a-f]{3,4}|[0-9a-f]{6}|[0-9a-f]{8})$/i', $v ) ) {
			return $v;
		}
		if ( preg_match( '/^(?:rgb|hsl)a?\(\s*[0-9.,%\s\/deg-]+\)$/i', $v ) ) {
			return $v;
		}
		return '';
	}

	private static function num( $arr, $key, $default, $min, $max ) {
		$n = ( isset( $arr[ $key ] ) && is_numeric( $arr[ $key ] ) ) ? (int) $arr[ $key ] : $default;
		return max( $min, min( $max, $n ) );
	}

	private static function clean_dark( $dark ) {
		$out = array();
		foreach ( $dark as $id => $v ) {
			$id = preg_replace( '/[^A-Za-z0-9_-]/', '', (string) $id );
			$v  = self::hex( $v );
			if ( '' !== $id && '' !== $v ) {
				$out[ $id ] = $v;
			}
		}
		return $out;
	}

	private static function clean_design( $d ) {
		$def = self::defaults();
		$def = $def['design'];
		$out = array(
			'style'        => ( isset( $d['style'] ) && 'switch' === $d['style'] ) ? 'switch' : 'button',
			'size'         => self::num( $d, 'size', $def['size'], 24, 96 ),
			'radius'       => self::num( $d, 'radius', $def['radius'], 0, 999 ),
			'border_width' => self::num( $d, 'border_width', $def['border_width'], 0, 6 ),
			'shadow'       => empty( $d['shadow'] ) ? 0 : 1,
		);
		foreach ( array( 'bg', 'icon', 'border', 'bg_h', 'bg_d', 'icon_d', 'border_d', 'bg_h_d' ) as $k ) {
			$out[ $k ] = self::hex6( isset( $d[ $k ] ) ? $d[ $k ] : '', $def[ $k ] );
		}
		return $out;
	}

	private static function clean_general( $g ) {
		$key = isset( $g['storage_key'] ) ? preg_replace( '/[^A-Za-z0-9_-]/', '', (string) $g['storage_key'] ) : '';
		return array(
			'storage_key'  => ( '' === $key ) ? 'dmt_theme' : substr( $key, 0, 60 ),
			'transition'   => self::num( $g, 'transition', 300, 0, 2000 ),
			'color_scheme' => empty( $g['color_scheme'] ) ? 0 : 1,
		);
	}

	private static function clean_advanced( $a ) {
		return array(
			'dark_css' => isset( $a['dark_css'] ) ? trim( wp_strip_all_tags( (string) $a['dark_css'] ) ) : '',
		);
	}

	/* ---------------------------------------------------------------
	 * register_setting() sanitize callback
	 * ------------------------------------------------------------- */

	public static function sanitize( $input ) {
		$old = self::get();
		if ( ! is_array( $input ) ) {
			return $old;
		}

		// Import: a valid JSON export replaces everything.
		if ( ! empty( $input['import_json'] ) ) {
			$data = json_decode( (string) $input['import_json'], true );
			if ( is_array( $data ) && ( isset( $data['dark'] ) || isset( $data['design'] ) ) ) {
				$input = $data;
				add_settings_error( self::OPTION, 'dmt_import_ok', __( 'Settings imported.', 'dmt-elementor' ), 'success' );
			} else {
				add_settings_error( self::OPTION, 'dmt_import_bad', __( 'Import failed: that is not a valid export, so your other changes were saved instead.', 'dmt-elementor' ), 'error' );
			}
		}

		$out = array();

		// If the colors table had no rows (no kit found), keep what was saved before.
		$out['dark'] = ( isset( $input['dark'] ) && is_array( $input['dark'] ) ) ? self::clean_dark( $input['dark'] ) : $old['dark'];

		$out['design']   = self::clean_design( ( isset( $input['design'] ) && is_array( $input['design'] ) ) ? $input['design'] : array() );
		$out['general']  = self::clean_general( ( isset( $input['general'] ) && is_array( $input['general'] ) ) ? $input['general'] : array() );
		$out['advanced'] = self::clean_advanced( ( isset( $input['advanced'] ) && is_array( $input['advanced'] ) ) ? $input['advanced'] : array() );

		return $out;
	}
}
