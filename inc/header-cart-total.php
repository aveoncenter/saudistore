<?php
/**
 * Header cart total, shown immediately to the screen-left of the mini-cart icon.
 *
 * The amount is WooCommerce's real cart total (`WC()->cart->get_total()`), formatted with
 * the store currency settings, so the Saudi Riyal symbol configured for WooCommerce is used
 * (the theme maps SAR to U+20C1; see wordsite_currency_symbol()). An empty cart shows 0.
 * assets/js/cart-total.js refreshes it from the Store API after any cart change, without
 * reloading. The existing mini-cart block, its badge and drawer are left untouched.
 *
 * @package Wordsite
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Cart total + count, or zero when the cart is unavailable.
 *
 * @return array{total: float, count: int}
 */
function wordsite_cart_total_data() {
	$cart = function_exists( 'WC' ) ? WC()->cart : null;
	if ( ! $cart ) {
		return array(
			'total' => 0.0,
			'count' => 0,
		);
	}
	return array(
		'total' => (float) $cart->get_total( 'edit' ),
		'count' => (int) $cart->get_cart_contents_count(),
	);
}

/**
 * Spoken currency name for screen readers (the U+20C1 glyph is not read reliably).
 *
 * @return string
 */
function wordsite_cart_total_currency_name() {
	$code = get_woocommerce_currency();
	return 'SAR' === $code ? 'ريال سعودي' : $code;
}

/**
 * Number for the screen-reader label.
 *
 * @param float $total Total.
 * @return string
 */
function wordsite_cart_total_spoken( $total ) {
	return wp_strip_all_tags(
		wc_price(
			$total,
			array(
				'currency'     => '',
				'price_format' => '%2$s',
			)
		)
	) . ' ' . wordsite_cart_total_currency_name();
}

/**
 * Markup for the total.
 *
 * @return string
 */
function wordsite_cart_total_html() {
	$data = wordsite_cart_total_data();
	return sprintf(
		'<span class="ws-cart-total" data-ws-cart-total data-count="%1$d" data-endpoint="%2$s" data-currency-name="%3$s">'
		. '<span class="ws-cart-total__amount" aria-hidden="true">%4$s</span>'
		. '<span class="tq-sr-only" aria-live="polite" aria-atomic="true">إجمالي السلة: <span data-ws-cart-total-spoken>%5$s</span></span>'
		. '</span>',
		$data['count'],
		esc_url( rest_url( 'wc/store/v1/cart' ) ),
		esc_attr( wordsite_cart_total_currency_name() ),
		wc_price( $data['total'] ),
		esc_html( wordsite_cart_total_spoken( $data['total'] ) )
	);
}

/**
 * Append the total right after the header mini-cart block (same flex row).
 *
 * @param string $content Block HTML.
 * @return string
 */
function wordsite_cart_total_after_mini_cart( $content ) {
	static $done = false;
	if ( $done || is_admin() || wp_is_serving_rest_request() ) {
		return $content;
	}
	$done = true;
	return $content . wordsite_cart_total_html();
}
add_filter( 'render_block_woocommerce/mini-cart', 'wordsite_cart_total_after_mini_cart', 20 );

/**
 * Classic AJAX flows (wc-add-to-cart.js, cart fragments) also receive a fresh total.
 *
 * @param array $fragments Fragments.
 * @return array
 */
function wordsite_cart_total_fragment( $fragments ) {
	/* admin-ajax counts as is_admin(), where the storefront riyal glyph is skipped.
	 * Render this storefront fragment with the same symbol as the page itself. */
	$storefront_symbol = static function ( $symbol, $currency ) {
		if ( 'SAR' === $currency && function_exists( 'wordsite_currency_symbol' ) && apply_filters( 'wordsite_riyal_symbol', true ) ) {
			return "\u{20C1}";
		}
		return $symbol;
	};
	$ajax = wp_doing_ajax();
	if ( $ajax ) {
		add_filter( 'woocommerce_currency_symbol', $storefront_symbol, 30, 2 );
	}
	$fragments['span.ws-cart-total'] = wordsite_cart_total_html();
	if ( $ajax ) {
		remove_filter( 'woocommerce_currency_symbol', $storefront_symbol, 30 );
	}
	return $fragments;
}
add_filter( 'woocommerce_add_to_cart_fragments', 'wordsite_cart_total_fragment' );

add_action(
	'wp_enqueue_scripts',
	static function () {
		$ver = wp_get_theme()->get( 'Version' );
		wp_enqueue_script( 'wordsite-cart-total', get_theme_file_uri( 'assets/js/cart-total.js' ), array(), $ver, true );
	},
	100
);
