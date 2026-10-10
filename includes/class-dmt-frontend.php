<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Front end: early theme script, dark CSS, toggle assets and shortcode.
 */
class DMT_Frontend {

	public static function init() {
		add_action( 'wp_head', array( __CLASS__, 'head_script' ), 1 );
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'enqueue' ) );
		add_shortcode( 'dark_mode_toggle', array( __CLASS__, 'shortcode' ) );
	}

	/** Do not touch the Elementor editor preview or the Customizer. */
	private static function is_builder_preview() {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		return isset( $_GET['elementor-preview'] ) || is_customize_preview();
	}

	/**
	 * Runs before the page is painted, so there is no light-to-dark flash.
	 * Order: saved choice (localStorage) > device theme > light.
	 */
	public static function head_script() {
		if ( self::is_builder_preview() ) {
			return;
		}
		$s    = DMT_Settings::get();
		$json = wp_json_encode(
			array(
				'key' => $s['general']['storage_key'],
				'ms'  => (int) $s['general']['transition'],
			),
			JSON_HEX_TAG | JSON_HEX_AMP
		);
		?>
<script data-no-optimize="1" data-no-defer="1" data-no-minify="1" data-cfasync="false" data-wpmeteor-nooptimize="true" nowprocket>
window.__DMT=<?php echo $json; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>;
(function(){var d=document.documentElement,t=null;try{var s=localStorage.getItem(window.__DMT.key);if(s==='dark'||s==='light'){t=s;}}catch(e){}
if(!t){t=(window.matchMedia&&window.matchMedia('(prefers-color-scheme: dark)').matches)?'dark':'light';}
d.setAttribute('data-dmt-theme',t);})();
</script>
		<?php
	}

	public static function enqueue() {
		wp_enqueue_style( 'dmt-toggle', DMT_URL . 'assets/css/toggle.css', array(), DMT_VERSION );
		wp_add_inline_style( 'dmt-toggle', self::dynamic_css( ! self::is_builder_preview() ) );

		if ( self::is_builder_preview() ) {
			return;
		}
		wp_enqueue_script(
			'dmt-toggle',
			DMT_URL . 'assets/js/toggle.js',
			array(),
			DMT_VERSION,
			array(
				'in_footer' => true,
				'strategy'  => 'defer',
			)
		);
	}

	/**
	 * Toggle look (CSS variables) + Elementor dark overrides + custom dark CSS.
	 * Everything here comes from validated settings.
	 */
	public static function dynamic_css( $with_dark = true ) {
		$s = DMT_Settings::get();
		$d = $s['design'];

		$css  = ':root{';
		$css .= '--dmt-size:' . (int) $d['size'] . 'px;';
		$css .= '--dmt-radius:' . (int) $d['radius'] . 'px;';
		$css .= '--dmt-bw:' . (int) $d['border_width'] . 'px;';
		$css .= '--dmt-ms:' . (int) $s['general']['transition'] . 'ms;';
		$css .= '--dmt-shadow:' . ( $d['shadow'] ? '0 2px 10px rgba(0,0,0,.15)' : 'none' ) . ';';
		foreach ( array( 'bg', 'icon', 'border', 'bg_h', 'bg_d', 'icon_d', 'border_d', 'bg_h_d' ) as $k ) {
			$css .= '--dmt-' . str_replace( '_', '-', $k ) . ':' . $d[ $k ] . ';';
		}
		$css .= '}';

		if ( $with_dark ) {
			$vars = '';
			foreach ( $s['dark'] as $id => $hex ) {
				$vars .= '--e-global-color-' . $id . ':' . $hex . ';';
			}
			if ( '' !== $vars ) {
				// Beats Elementor's own ".elementor-kit-N{...}" rule on specificity.
				$sel = 'html[data-dmt-theme="dark"] body';
				$kit = DMT_Colors::kit_id();
				if ( $kit ) {
					$sel .= ',html[data-dmt-theme="dark"] .elementor-kit-' . $kit;
				}
				$css .= $sel . '{' . $vars . '}';
			}
			if ( ! empty( $s['general']['color_scheme'] ) ) {
				$css .= 'html[data-dmt-theme="dark"]{color-scheme:dark}';
			}
			if ( '' !== $s['advanced']['dark_css'] ) {
				$css .= "\n" . $s['advanced']['dark_css'];
			}
		}

		return $css;
	}

	/* ---------------------------------------------------------------
	 * Toggle markup
	 * ------------------------------------------------------------- */

	private static function icon( $name ) {
		$open = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">';
		if ( 'sun' === $name ) {
			return $open . '<circle cx="12" cy="12" r="5"/><line x1="12" y1="1" x2="12" y2="3"/><line x1="12" y1="21" x2="12" y2="23"/><line x1="4.22" y1="4.22" x2="5.64" y2="5.64"/><line x1="18.36" y1="18.36" x2="19.78" y2="19.78"/><line x1="1" y1="12" x2="3" y2="12"/><line x1="21" y1="12" x2="23" y2="12"/><line x1="4.22" y1="19.78" x2="5.64" y2="18.36"/><line x1="18.36" y1="5.64" x2="19.78" y2="4.22"/></svg>';
		}
		return $open . '<path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"/></svg>';
	}

	/**
	 * [dark_mode_toggle] or [dark_mode_toggle style="switch" size="36" class="my-class"]
	 */
	public static function shortcode( $atts ) {
		$s = DMT_Settings::get();
		$a = shortcode_atts(
			array(
				'style' => $s['design']['style'],
				'size'  => '',
				'class' => '',
			),
			$atts,
			'dark_mode_toggle'
		);
		return self::render( $a['style'], $a['size'], $a['class'] );
	}

	public static function render( $style = 'button', $size = '', $class = '' ) {
		$style   = ( 'switch' === $style ) ? 'switch' : 'button';
		$classes = 'dmt-toggle dmt-style-' . $style;
		foreach ( preg_split( '/\s+/', trim( (string) $class ) ) as $c ) {
			$c = sanitize_html_class( $c );
			if ( '' !== $c ) {
				$classes .= ' ' . $c;
			}
		}

		$inline = '';
		$size   = (int) $size;
		if ( $size >= 20 && $size <= 120 ) {
			$inline = ' style="--dmt-size:' . $size . 'px"';
		}

		$to_dark  = __( 'Switch to dark mode', 'dmt-elementor' );
		$to_light = __( 'Switch to light mode', 'dmt-elementor' );

		$html  = '<button type="button" class="' . esc_attr( $classes ) . '"' . $inline . ' data-dmt-toggle aria-pressed="false"';
		$html .= ' aria-label="' . esc_attr( $to_dark ) . '" data-label-light="' . esc_attr( $to_dark ) . '" data-label-dark="' . esc_attr( $to_light ) . '">';

		if ( 'switch' === $style ) {
			$html .= '<span class="dmt-track">';
			$html .= '<span class="dmt-sw dmt-sw-sun">' . self::icon( 'sun' ) . '</span>';
			$html .= '<span class="dmt-sw dmt-sw-moon">' . self::icon( 'moon' ) . '</span>';
			// The thumb carries the icon of the active mode, drawn in the track color so it stays visible.
			$html .= '<span class="dmt-thumb">';
			$html .= '<span class="dmt-th dmt-th-sun">' . self::icon( 'sun' ) . '</span>';
			$html .= '<span class="dmt-th dmt-th-moon">' . self::icon( 'moon' ) . '</span>';
			$html .= '</span>';
			$html .= '</span>';
		} else {
			$html .= '<span class="dmt-icon dmt-icon-sun">' . self::icon( 'sun' ) . '</span>';
			$html .= '<span class="dmt-icon dmt-icon-moon">' . self::icon( 'moon' ) . '</span>';
		}

		$html .= '</button>';
		return $html;
	}
}
