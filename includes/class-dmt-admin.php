<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Settings page: Settings > Dark Mode Toggle.
 * Tabs: Colors, Toggle Design, General, Advanced.
 */
class DMT_Admin {

	const SLUG = 'dmt-settings';

	public static function init() {
		add_action( 'admin_menu', array( __CLASS__, 'menu' ) );
		add_action( 'admin_init', array( __CLASS__, 'register' ) );
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'assets' ) );
		add_filter( 'plugin_action_links_' . plugin_basename( DMT_FILE ), array( __CLASS__, 'links' ) );
	}

	public static function menu() {
		add_options_page(
			__( 'Dark Mode Toggle', 'dmt-elementor' ),
			__( 'Dark Mode Toggle', 'dmt-elementor' ),
			'manage_options',
			self::SLUG,
			array( __CLASS__, 'render' )
		);
	}

	public static function register() {
		register_setting(
			'dmt_group',
			DMT_Settings::OPTION,
			array(
				'type'              => 'array',
				'sanitize_callback' => array( 'DMT_Settings', 'sanitize' ),
				'default'           => array(),
			)
		);
	}

	public static function links( $links ) {
		array_unshift(
			$links,
			'<a href="' . esc_url( admin_url( 'options-general.php?page=' . self::SLUG ) ) . '">' . esc_html__( 'Settings', 'dmt-elementor' ) . '</a>'
		);
		return $links;
	}

	public static function assets( $hook ) {
		if ( 'settings_page_' . self::SLUG !== $hook ) {
			return;
		}
		wp_enqueue_style( 'dmt-toggle', DMT_URL . 'assets/css/toggle.css', array(), DMT_VERSION );
		wp_enqueue_style( 'dmt-admin', DMT_URL . 'assets/css/admin.css', array( 'dmt-toggle' ), DMT_VERSION );
		wp_enqueue_script( 'dmt-admin', DMT_URL . 'assets/js/admin.js', array(), DMT_VERSION, true );
		wp_localize_script(
			'dmt-admin',
			'DMT_ADMIN',
			array(
				'copy'   => __( 'Copy', 'dmt-elementor' ),
				'copied' => __( 'Copied ✓', 'dmt-elementor' ),
			)
		);
	}

	/* ---------------------------------------------------------------
	 * Page
	 * ------------------------------------------------------------- */

	public static function render() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		$s = DMT_Settings::get();
		?>
		<div class="wrap dmt-wrap">
			<h1><?php esc_html_e( 'Dark Mode Toggle for Elementor', 'dmt-elementor' ); ?></h1>
			<?php settings_errors(); ?>

			<form method="post" action="options.php">
				<?php settings_fields( 'dmt_group' ); ?>

				<h2 class="nav-tab-wrapper dmt-tabs">
					<a href="#colors" class="nav-tab" data-tab="colors"><?php esc_html_e( 'Colors', 'dmt-elementor' ); ?></a>
					<a href="#design" class="nav-tab" data-tab="design"><?php esc_html_e( 'Toggle Design', 'dmt-elementor' ); ?></a>
					<a href="#general" class="nav-tab" data-tab="general"><?php esc_html_e( 'General', 'dmt-elementor' ); ?></a>
					<a href="#advanced" class="nav-tab" data-tab="advanced"><?php esc_html_e( 'Advanced', 'dmt-elementor' ); ?></a>
				</h2>

				<div class="dmt-panel" data-panel="colors"><?php self::tab_colors( $s ); ?></div>
				<div class="dmt-panel" data-panel="design" hidden><?php self::tab_design( $s ); ?></div>
				<div class="dmt-panel" data-panel="general" hidden><?php self::tab_general( $s ); ?></div>
				<div class="dmt-panel" data-panel="advanced" hidden><?php self::tab_advanced( $s ); ?></div>

				<?php submit_button(); ?>
			</form>
		</div>
		<?php
	}

	/* ---------------------------------------------------------------
	 * Tab: Colors
	 * ------------------------------------------------------------- */

	private static function tab_colors( $s ) {
		$colors = DMT_Colors::get();
		$n      = DMT_Settings::OPTION;
		$kit    = DMT_Colors::kit_id();
		?>
		<p class="description dmt-intro">
			<?php esc_html_e( 'Light values are read live from Elementor: Site Settings > Global Colors. Set a dark value for each color you want to change. An empty dark field keeps the light value in dark mode.', 'dmt-elementor' ); ?>
		</p>

		<?php if ( ! $kit ) : ?>
			<div class="notice notice-warning inline"><p><?php esc_html_e( 'No active Elementor Kit was found. Install and activate Elementor, then open this page again.', 'dmt-elementor' ); ?></p></div>
		<?php elseif ( ! $colors ) : ?>
			<div class="notice notice-warning inline"><p><?php esc_html_e( 'The active Elementor Kit has no Global Colors yet. Add some in Site Settings, then click Refresh.', 'dmt-elementor' ); ?></p></div>
		<?php endif; ?>

		<p class="dmt-toolbar">
			<a class="button" href="<?php echo esc_url( admin_url( 'options-general.php?page=' . self::SLUG ) ); ?>"><?php esc_html_e( 'Refresh from Elementor', 'dmt-elementor' ); ?></a>
			<button type="button" class="button" id="dmt-clear-all"><?php esc_html_e( 'Clear all dark values', 'dmt-elementor' ); ?></button>
		</p>

		<?php if ( $colors ) : ?>
		<table class="widefat striped dmt-colors">
			<thead>
				<tr>
					<th><?php esc_html_e( 'Color name', 'dmt-elementor' ); ?></th>
					<th><?php esc_html_e( 'Light', 'dmt-elementor' ); ?></th>
					<th><?php esc_html_e( 'Dark', 'dmt-elementor' ); ?></th>
					<th><?php esc_html_e( 'CSS variable', 'dmt-elementor' ); ?></th>
				</tr>
			</thead>
			<tbody>
			<?php
			foreach ( $colors as $c ) :
				$dark      = isset( $s['dark'][ $c['id'] ] ) ? $s['dark'][ $c['id'] ] : '';
				$fallback  = preg_match( '/^#[0-9a-f]{6}$/i', $c['color'] ) ? $c['color'] : '#000000';
				$pick      = preg_match( '/^#[0-9a-f]{6}$/i', $dark ) ? $dark : $fallback;
				$light_css = DMT_Settings::css_color( $c['color'] );
				$var       = 'var(' . $c['var'] . ')';
				?>
				<tr class="dmt-dark-row">
					<td>
						<strong><?php echo esc_html( $c['title'] ); ?></strong>
						<span class="dmt-badge"><?php echo ( 'system' === $c['type'] ) ? esc_html__( 'System', 'dmt-elementor' ) : esc_html__( 'Custom', 'dmt-elementor' ); ?></span>
					</td>
					<td>
						<?php if ( '' !== $light_css ) : ?>
							<span class="dmt-swatch" style="background:<?php echo esc_attr( $light_css ); ?>"></span>
						<?php endif; ?>
						<code><?php echo esc_html( strtoupper( $c['color'] ) ); ?></code>
					</td>
					<td>
						<input type="color" class="dmt-picker" value="<?php echo esc_attr( $pick ); ?>" aria-label="<?php echo esc_attr( sprintf( /* translators: %s: color name */ __( 'Dark color picker for %s', 'dmt-elementor' ), $c['title'] ) ); ?>">
						<input type="text" class="dmt-hex" name="<?php echo esc_attr( $n ); ?>[dark][<?php echo esc_attr( $c['id'] ); ?>]" value="<?php echo esc_attr( $dark ); ?>" placeholder="<?php esc_attr_e( 'same as light', 'dmt-elementor' ); ?>" maxlength="9" spellcheck="false" autocomplete="off">
						<button type="button" class="button-link dmt-clear"><?php esc_html_e( 'Clear', 'dmt-elementor' ); ?></button>
					</td>
					<td class="dmt-var">
						<code><?php echo esc_html( $var ); ?></code>
						<button type="button" class="button button-small" data-copy="<?php echo esc_attr( $var ); ?>"><?php esc_html_e( 'Copy', 'dmt-elementor' ); ?></button>
					</td>
				</tr>
			<?php endforeach; ?>
			</tbody>
		</table>
		<?php endif; ?>

		<h3><?php esc_html_e( 'Tips', 'dmt-elementor' ); ?></h3>
		<ul class="dmt-tips">
			<li><?php esc_html_e( 'Copy the CSS variable and use it in your custom CSS, for example: background: var(--e-global-color-primary); The color then switches with the mode automatically.', 'dmt-elementor' ); ?></li>
			<li><?php esc_html_e( 'Only colors linked to a Global Color change. In Elementor, link the site Background, widget colors and borders to Global Colors instead of typing hex values.', 'dmt-elementor' ); ?></li>
			<li><?php esc_html_e( 'Edit a Global Color in Elementor instead of deleting and re-creating it. A new color gets a new ID, so its dark value and any copied variable will no longer match.', 'dmt-elementor' ); ?></li>
		</ul>
		<?php
	}

	/* ---------------------------------------------------------------
	 * Tab: Toggle design
	 * ------------------------------------------------------------- */

	private static function number_row( $key, $label, $val, $min, $max, $help = '' ) {
		$n = DMT_Settings::OPTION;
		?>
		<tr>
			<th scope="row"><label for="dmt-d-<?php echo esc_attr( $key ); ?>"><?php echo esc_html( $label ); ?></label></th>
			<td>
				<input type="number" class="small-text" id="dmt-d-<?php echo esc_attr( $key ); ?>" name="<?php echo esc_attr( $n ); ?>[design][<?php echo esc_attr( $key ); ?>]" value="<?php echo esc_attr( $val ); ?>" min="<?php echo (int) $min; ?>" max="<?php echo (int) $max; ?>" step="1" data-design="<?php echo esc_attr( $key ); ?>">
				<?php if ( '' !== $help ) : ?>
					<p class="description"><?php echo esc_html( $help ); ?></p>
				<?php endif; ?>
			</td>
		</tr>
		<?php
	}

	private static function color_row( $label, $key, $key_dark, $d ) {
		$n = DMT_Settings::OPTION;
		?>
		<tr>
			<th scope="row"><?php echo esc_html( $label ); ?></th>
			<td><input type="color" name="<?php echo esc_attr( $n ); ?>[design][<?php echo esc_attr( $key ); ?>]" value="<?php echo esc_attr( $d[ $key ] ); ?>" data-design="<?php echo esc_attr( $key ); ?>" aria-label="<?php echo esc_attr( $label . ' (light)' ); ?>"></td>
			<td><input type="color" name="<?php echo esc_attr( $n ); ?>[design][<?php echo esc_attr( $key_dark ); ?>]" value="<?php echo esc_attr( $d[ $key_dark ] ); ?>" data-design="<?php echo esc_attr( $key_dark ); ?>" aria-label="<?php echo esc_attr( $label . ' (dark)' ); ?>"></td>
		</tr>
		<?php
	}

	private static function tab_design( $s ) {
		$d = $s['design'];
		$n = DMT_Settings::OPTION;
		?>
		<div class="dmt-design-grid">
			<div>
				<table class="form-table" role="presentation">
					<tr>
						<th scope="row"><label for="dmt-d-style"><?php esc_html_e( 'Style', 'dmt-elementor' ); ?></label></th>
						<td>
							<select id="dmt-d-style" name="<?php echo esc_attr( $n ); ?>[design][style]" data-design="style">
								<option value="button" <?php selected( $d['style'], 'button' ); ?>><?php esc_html_e( 'Button (one icon)', 'dmt-elementor' ); ?></option>
								<option value="switch" <?php selected( $d['style'], 'switch' ); ?>><?php esc_html_e( 'Switch (sliding thumb)', 'dmt-elementor' ); ?></option>
							</select>
							<p class="description"><?php esc_html_e( 'Can be overridden per shortcode: [dark_mode_toggle style="switch"]', 'dmt-elementor' ); ?></p>
						</td>
					</tr>
					<?php
					self::number_row( 'size', __( 'Size (px)', 'dmt-elementor' ), $d['size'], 24, 96, __( 'Height of the toggle. The switch style is 1.9 times wider.', 'dmt-elementor' ) );
					self::number_row( 'radius', __( 'Border radius (px)', 'dmt-elementor' ), $d['radius'], 0, 999, __( '999 = fully round, 0 = square corners.', 'dmt-elementor' ) );
					self::number_row( 'border_width', __( 'Border width (px)', 'dmt-elementor' ), $d['border_width'], 0, 6 );
					?>
					<tr>
						<th scope="row"><?php esc_html_e( 'Shadow', 'dmt-elementor' ); ?></th>
						<td><label><input type="checkbox" name="<?php echo esc_attr( $n ); ?>[design][shadow]" value="1" <?php checked( $d['shadow'], 1 ); ?> data-design="shadow"> <?php esc_html_e( 'Add a soft shadow', 'dmt-elementor' ); ?></label></td>
					</tr>
				</table>

				<table class="form-table dmt-color-pairs" role="presentation">
					<thead>
						<tr><th></th><th><?php esc_html_e( 'Light mode', 'dmt-elementor' ); ?></th><th><?php esc_html_e( 'Dark mode', 'dmt-elementor' ); ?></th></tr>
					</thead>
					<?php
					self::color_row( __( 'Background', 'dmt-elementor' ), 'bg', 'bg_d', $d );
					self::color_row( __( 'Icon', 'dmt-elementor' ), 'icon', 'icon_d', $d );
					self::color_row( __( 'Border', 'dmt-elementor' ), 'border', 'border_d', $d );
					self::color_row( __( 'Hover background', 'dmt-elementor' ), 'bg_h', 'bg_h_d', $d );
					?>
				</table>
			</div>

			<div class="dmt-preview">
				<h3><?php esc_html_e( 'Live preview', 'dmt-elementor' ); ?></h3>
				<div class="dmt-preview-panel dmt-pv-light" data-style="<?php echo esc_attr( $d['style'] ); ?>">
					<span class="dmt-pv-label"><?php esc_html_e( 'Light mode', 'dmt-elementor' ); ?></span>
					<?php
					echo DMT_Frontend::render( 'button' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
					echo DMT_Frontend::render( 'switch' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
					?>
				</div>
				<div class="dmt-preview-panel dmt-pv-dark dmt-force-dark" data-style="<?php echo esc_attr( $d['style'] ); ?>">
					<span class="dmt-pv-label"><?php esc_html_e( 'Dark mode', 'dmt-elementor' ); ?></span>
					<?php
					echo DMT_Frontend::render( 'button' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
					echo DMT_Frontend::render( 'switch' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
					?>
				</div>
				<p class="description"><?php esc_html_e( 'Hover the toggles to check the hover color. Save to apply the design on your site.', 'dmt-elementor' ); ?></p>
			</div>
		</div>
		<?php
	}

	/* ---------------------------------------------------------------
	 * Tab: General
	 * ------------------------------------------------------------- */

	private static function tab_general( $s ) {
		$g = $s['general'];
		$n = DMT_Settings::OPTION;
		?>
		<table class="form-table" role="presentation">
			<tr>
				<th scope="row"><?php esc_html_e( 'Shortcode', 'dmt-elementor' ); ?></th>
				<td>
					<input type="text" class="regular-text code" readonly value="[dark_mode_toggle]" onfocus="this.select()">
					<button type="button" class="button" data-copy="[dark_mode_toggle]"><?php esc_html_e( 'Copy', 'dmt-elementor' ); ?></button>
					<p class="description">
						<?php esc_html_e( 'Paste it into an Elementor Shortcode widget (header, footer, menu area) or any content. Options:', 'dmt-elementor' ); ?>
						<code>[dark_mode_toggle style="switch" size="36" class="my-class"]</code>
					</p>
					<p class="description">
						<?php esc_html_e( 'Prefer your own button or icon? Add the CSS class dmt-trigger to any Elementor widget and clicking it switches the mode.', 'dmt-elementor' ); ?>
					</p>
				</td>
			</tr>
			<tr>
				<th scope="row"><label for="dmt-g-key"><?php esc_html_e( 'localStorage key', 'dmt-elementor' ); ?></label></th>
				<td>
					<input type="text" class="regular-text code" id="dmt-g-key" name="<?php echo esc_attr( $n ); ?>[general][storage_key]" value="<?php echo esc_attr( $g['storage_key'] ); ?>" maxlength="60">
					<p class="description"><?php esc_html_e( 'Where the visitor\'s choice is saved in their browser. Letters, numbers, - and _ only.', 'dmt-elementor' ); ?></p>
				</td>
			</tr>
			<tr>
				<th scope="row"><label for="dmt-g-ms"><?php esc_html_e( 'Transition (ms)', 'dmt-elementor' ); ?></label></th>
				<td>
					<input type="number" class="small-text" id="dmt-g-ms" name="<?php echo esc_attr( $n ); ?>[general][transition]" value="<?php echo esc_attr( $g['transition'] ); ?>" min="0" max="2000" step="50">
					<p class="description"><?php esc_html_e( 'How long colors fade when switching. 0 = instant. Skipped automatically for visitors who prefer reduced motion.', 'dmt-elementor' ); ?></p>
				</td>
			</tr>
			<tr>
				<th scope="row"><?php esc_html_e( 'Dark form controls', 'dmt-elementor' ); ?></th>
				<td>
					<label><input type="checkbox" name="<?php echo esc_attr( $n ); ?>[general][color_scheme]" value="1" <?php checked( $g['color_scheme'], 1 ); ?>> <?php esc_html_e( 'Tell the browser the page is dark (scrollbars, default inputs, selects)', 'dmt-elementor' ); ?></label>
				</td>
			</tr>
		</table>

		<h3><?php esc_html_e( 'How the mode is chosen', 'dmt-elementor' ); ?></h3>
		<ol class="dmt-tips">
			<li><?php esc_html_e( 'First visit: the visitor\'s device theme (light or dark).', 'dmt-elementor' ); ?></li>
			<li><?php esc_html_e( 'After the visitor uses the toggle: that choice, remembered in the browser and synced across open tabs.', 'dmt-elementor' ); ?></li>
			<li><?php esc_html_e( 'Until they choose: the site follows the device theme live.', 'dmt-elementor' ); ?></li>
		</ol>

		<h3><?php esc_html_e( 'For developers', 'dmt-elementor' ); ?></h3>
		<ul class="dmt-tips">
			<li><code>document.addEventListener('themechange', e =&gt; console.log(e.detail.theme))</code></li>
			<li><code>window.DMT.get()</code>, <code>window.DMT.set('dark')</code>, <code>window.DMT.toggle()</code></li>
			<li><?php esc_html_e( 'The mode is the attribute html[data-dmt-theme="light|dark"].', 'dmt-elementor' ); ?></li>
			<li><?php esc_html_e( 'Caching or optimization plugins: if you see a light flash before dark mode, exclude the inline script containing "__DMT" from Delay JS / Combine JS.', 'dmt-elementor' ); ?></li>
		</ul>
		<?php
	}

	/* ---------------------------------------------------------------
	 * Tab: Advanced
	 * ------------------------------------------------------------- */

	private static function tab_advanced( $s ) {
		$n      = DMT_Settings::OPTION;
		$export = wp_json_encode( $s, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES );
		?>
		<table class="form-table" role="presentation">
			<tr>
				<th scope="row"><label for="dmt-a-css"><?php esc_html_e( 'Extra dark mode CSS', 'dmt-elementor' ); ?></label></th>
				<td>
					<textarea id="dmt-a-css" class="large-text code" rows="8" name="<?php echo esc_attr( $n ); ?>[advanced][dark_css]" spellcheck="false"><?php echo esc_textarea( $s['advanced']['dark_css'] ); ?></textarea>
					<p class="description">
						<?php esc_html_e( 'Printed only for dark mode. Use this for things Global Colors cannot reach: hard-coded colors, images, logos, maps. Prefix your selectors with', 'dmt-elementor' ); ?>
						<code>html[data-dmt-theme="dark"]</code>
					</p>
					<p class="description"><code>html[data-dmt-theme="dark"] .my-logo { filter: invert(1); }</code></p>
				</td>
			</tr>
			<tr>
				<th scope="row"><label for="dmt-a-export"><?php esc_html_e( 'Export', 'dmt-elementor' ); ?></label></th>
				<td>
					<textarea id="dmt-a-export" class="large-text code" rows="6" readonly><?php echo esc_textarea( $export ); ?></textarea>
					<p><button type="button" class="button" data-copy-from="#dmt-a-export"><?php esc_html_e( 'Copy export', 'dmt-elementor' ); ?></button></p>
					<p class="description"><?php esc_html_e( 'Reflects the last saved settings. Use it to move your dark colors and toggle design to another site.', 'dmt-elementor' ); ?></p>
				</td>
			</tr>
			<tr>
				<th scope="row"><label for="dmt-a-import"><?php esc_html_e( 'Import', 'dmt-elementor' ); ?></label></th>
				<td>
					<textarea id="dmt-a-import" class="large-text code" rows="4" name="<?php echo esc_attr( $n ); ?>[import_json]" spellcheck="false" placeholder="<?php esc_attr_e( 'Paste an export here, then click Save Changes. It replaces all current settings.', 'dmt-elementor' ); ?>"></textarea>
					<p class="description"><?php esc_html_e( 'Color IDs are matched by Elementor color ID, so an import only helps on a site that uses the same Global Color IDs (for example a staging copy).', 'dmt-elementor' ); ?></p>
				</td>
			</tr>
		</table>
		<?php
	}
}
