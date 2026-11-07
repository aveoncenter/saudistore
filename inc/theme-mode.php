<?php
/**
 * Light / dark appearance: no-flash bootstrap, header toggle and assets.
 *
 * - The inline head script picks the saved choice (localStorage `taraqqi-theme`) or defaults
 *   first-time visitors to light mode, and sets <html data-theme> before paint.
 * - The toggle button is appended after the header's category navigation (`.ws-nav`), so it
 *   also appears when the header template part has been customised in the Site Editor.
 * - assets/css/theme-mode.css holds the shared colour tokens and every light-mode override;
 *   assets/js/theme-mode.js handles clicks, persistence and cross-tab sync.
 *
 * @package Wordsite
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Print the no-flash bootstrap as early as possible in <head>.
 */
function wordsite_theme_mode_bootstrap() {
	?>
<meta name="color-scheme" content="dark light" />
<meta name="theme-color" content="#060818" data-tq-theme-color />
<script>(function(d){var r=d.documentElement,t='dark';try{var s=localStorage.getItem('taraqqi-theme');t=(s==='light'||s==='dark')?s:'light';}catch(e){}r.setAttribute('data-theme',t);r.style.colorScheme=t;var m=d.querySelector('meta[data-tq-theme-color]');if(m)m.setAttribute('content',t==='light'?'#f3f6fd':'#060818');})(document);</script>
	<?php
}
add_action( 'wp_head', 'wordsite_theme_mode_bootstrap', 0 );

/**
 * Toggle button markup.
 *
 * @return string
 */
function wordsite_theme_toggle_html() {
	$sun  = '<svg class="ws-theme-toggle__sun" viewBox="0 0 24 24" aria-hidden="true" focusable="false"><circle cx="12" cy="12" r="4.2"/><path d="M12 2.5v2.2M12 19.3v2.2M4.7 4.7l1.6 1.6M17.7 17.7l1.6 1.6M2.5 12h2.2M19.3 12h2.2M4.7 19.3l1.6-1.6M17.7 6.3l1.6-1.6"/></svg>';
	$moon = '<svg class="ws-theme-toggle__moon" viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M20.2 14.6A8.4 8.4 0 0 1 9.4 3.8a8.4 8.4 0 1 0 10.8 10.8z"/><path class="ws-theme-toggle__spark" d="M17.2 3.6v2.6M15.9 4.9h2.6"/></svg>';
	return '<button type="button" class="ws-theme-toggle" data-ws-theme-toggle aria-label="تبديل المظهر" title="تبديل المظهر">'
		. '<span class="ws-theme-toggle__icons" aria-hidden="true">' . $sun . $moon . '</span>'
		. '</button>';
}

/**
 * Append the toggle after the header category navigation.
 *
 * @param string $content Block HTML.
 * @param array  $block   Parsed block.
 * @return string
 */
function wordsite_theme_toggle_after_nav( $content, $block ) {
	static $done = false;
	$class = isset( $block['attrs']['className'] ) ? (string) $block['attrs']['className'] : '';
	if ( $done || is_admin() || false === strpos( ' ' . $class . ' ', ' ws-nav ' ) ) {
		return $content;
	}
	$done = true;
	return $content . wordsite_theme_toggle_html();
}
add_filter( 'render_block_core/navigation', 'wordsite_theme_toggle_after_nav', 20, 2 );

/**
 * Shared tokens + light-mode overrides load after every other theme stylesheet.
 */
function wordsite_theme_mode_assets() {
	$ver = wp_get_theme()->get( 'Version' );
	wp_enqueue_style( 'wordsite-theme-mode', get_theme_file_uri( 'assets/css/theme-mode.css' ), array( 'wordsite-style' ), $ver );
	wp_enqueue_script( 'wordsite-theme-mode', get_theme_file_uri( 'assets/js/theme-mode.js' ), array(), $ver, true );
}
add_action( 'wp_enqueue_scripts', 'wordsite_theme_mode_assets', 100 );
