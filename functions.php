<?php
/**
 * Wordsite theme functions.
 *
 * @package Wordsite
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Theme setup.
 */
function wordsite_setup() {
	add_theme_support( 'woocommerce' );
	add_theme_support( 'wp-block-styles' );
	add_theme_support( 'editor-styles' );
	add_editor_style( array( 'assets/fonts/riyal.css', 'assets/fonts/cairo.css', 'style.css', 'assets/css/theme-mode.css' ) );
}
add_action( 'after_setup_theme', 'wordsite_setup' );

/**
 * Front-end styles.
 */
function wordsite_enqueue_assets() {
	$ver = wp_get_theme()->get( 'Version' );
	wp_enqueue_style( 'wordsite-riyal', get_theme_file_uri( 'assets/fonts/riyal.css' ), array(), $ver );
	wp_enqueue_style( 'wordsite-fonts', get_theme_file_uri( 'assets/fonts/cairo.css' ), array( 'wordsite-riyal' ), $ver );
	wp_enqueue_style( 'wordsite-style', get_stylesheet_uri(), array( 'wordsite-fonts' ), $ver );
	wp_enqueue_style( 'wordsite-menu', get_theme_file_uri( 'assets/css/menu.css' ), array( 'wordsite-style' ), $ver );
	wp_enqueue_script( 'wordsite-header', get_theme_file_uri( 'assets/js/header.js' ), array(), $ver, true );
}
add_action( 'wp_enqueue_scripts', 'wordsite_enqueue_assets' );

/**
 * WhatsApp number setting (Settings → General).
 */
function wordsite_register_settings() {
	register_setting(
		'general',
		'wordsite_whatsapp',
		array(
			'type'              => 'string',
			'sanitize_callback' => 'wordsite_sanitize_phone',
			'default'           => '',
		)
	);

	add_settings_field(
		'wordsite_whatsapp',
		__( 'WhatsApp number', 'wordsite' ),
		'wordsite_whatsapp_field',
		'general',
		'default',
		array( 'label_for' => 'wordsite_whatsapp' )
	);
}
add_action( 'admin_init', 'wordsite_register_settings' );

/**
 * Keep digits only (international format, no + or spaces).
 *
 * @param string $value Raw value.
 * @return string
 */
function wordsite_sanitize_phone( $value ) {
	return preg_replace( '/\D+/', '', (string) $value );
}

/**
 * Render the WhatsApp settings field.
 */
function wordsite_whatsapp_field() {
	printf(
		'<input type="text" id="wordsite_whatsapp" name="wordsite_whatsapp" value="%s" class="regular-text" placeholder="9665XXXXXXXX" /><p class="description">%s</p>',
		esc_attr( get_option( 'wordsite_whatsapp', '' ) ),
		esc_html__( 'International format, digits only (e.g. 9665XXXXXXXX). Shows the floating WhatsApp button and powers every "Contact us" button.', 'wordsite' )
	);
}

/**
 * Get the WhatsApp chat URL, or empty string when no number is set.
 *
 * @return string
 */
function wordsite_whatsapp_url() {
	$number = get_option( 'wordsite_whatsapp', '' );
	return $number ? 'https://wa.me/' . rawurlencode( $number ) : '';
}

/**
 * Buttons in templates link to /?whatsapp — send visitors to the chat.
 */
function wordsite_whatsapp_redirect() {
	if ( ! isset( $_GET['whatsapp'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		return;
	}
	$url = wordsite_whatsapp_url();
	if ( ! $url ) {
		$url = function_exists( 'wc_get_page_permalink' ) ? wc_get_page_permalink( 'shop' ) : home_url( '/' );
	}
	wp_redirect( esc_url_raw( $url ) ); // phpcs:ignore WordPress.Security.SafeRedirect.wp_redirect_wp_redirect
	exit;
}
add_action( 'template_redirect', 'wordsite_whatsapp_redirect' );

/**
 * Floating WhatsApp button.
 */
function wordsite_whatsapp_button() {
	$url = wordsite_whatsapp_url();
	if ( ! $url ) {
		return;
	}
	printf(
		'<a class="ws-whatsapp" href="%s" target="_blank" rel="noopener" aria-label="%s"><svg viewBox="0 0 24 24" aria-hidden="true"><path fill="currentColor" d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413Z"/></svg></a>',
		esc_url( $url ),
		esc_attr( 'تواصل معنا عبر واتساب' )
	);
}
add_action( 'wp_footer', 'wordsite_whatsapp_button' );


/**
 * Taraqqi brand logo for the header and footer.
 */
function wordsite_taraqqi_logo_shortcode() {
	/* Same artwork as taraqqi-logo.webp with its navy matte stored as transparency, so the
	 * logo needs no blend mode (a blended logo disables the floating header's backdrop blur). */
	$logo_url = add_query_arg( 'ver', '0.2.51', get_theme_file_uri( 'assets/images/taraqqi-logo-transparent.webp' ) );
	$label    = 'Taraqqi | ترقّي';
	return sprintf(
		'<a class="ws-brand-logo" href="%1$s" aria-label="%2$s"><img src="%3$s" alt="%2$s" width="760" height="300" decoding="async" fetchpriority="high" /></a>',
		esc_url( home_url( '/' ) ),
		esc_attr( $label ),
		esc_url( $logo_url )
	);
}
add_shortcode( 'taraqqi_logo', 'wordsite_taraqqi_logo_shortcode' );

/**
 * Taraqqi browser tab / home-screen icon.
 */
function wordsite_taraqqi_site_icon() {
	$icon_url = get_theme_file_uri( 'assets/images/taraqqi-icon.webp' );
	printf( '<link rel="icon" href="%s" type="image/webp" sizes="256x256" />', esc_url( $icon_url ) );
	printf( '<link rel="apple-touch-icon" href="%s" />', esc_url( $icon_url ) );
}
add_action( 'wp_head', 'wordsite_taraqqi_site_icon', 101 );

/** Use the selected brand name in browser document titles. */
add_filter( 'document_title_parts', static function ( $title ) {
	$title['site'] = 'Taraqqi | ترقّي';
	return $title;
} );

require_once get_theme_file_path( 'inc/store-menu.php' );
require_once get_theme_file_path( 'inc/home-categories.php' );
require_once get_theme_file_path( 'inc/product-art.php' );
require_once get_theme_file_path( 'inc/home-hero.php' );
require_once get_theme_file_path( 'inc/home-sections.php' );
require_once get_theme_file_path( 'inc/theme-mode.php' );

/**
 * Product page (needs WooCommerce).
 */
if ( class_exists( 'WooCommerce' ) ) {
	require_once get_theme_file_path( 'inc/product-page.php' );
	require_once get_theme_file_path( 'inc/product-search.php' );
	require_once get_theme_file_path( 'inc/home-products.php' );
	require_once get_theme_file_path( 'inc/product-motion.php' );
	require_once get_theme_file_path( 'inc/header-cart-total.php' );
}
