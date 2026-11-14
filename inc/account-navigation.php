<?php
/** Keep account navigation visible, but point it to the storefront home. */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Ensure the account icon remains a simple link even in a saved block template.
add_filter( 'render_block_data', static function ( $parsed_block ) {
	if ( ! is_admin() && 'woocommerce/customer-account' === $parsed_block['blockName'] ) {
		$parsed_block['attrs']['hasDropdownNavigation'] = false;
	}
	return $parsed_block;
} );

// Change only the account block link; do not redirect the account page itself.
add_filter( 'render_block_woocommerce/customer-account', static function ( $html ) {
	if ( is_admin() || ! class_exists( 'WP_HTML_Tag_Processor' ) ) {
		return $html;
	}
	$tags = new WP_HTML_Tag_Processor( $html );
	if ( $tags->next_tag( array( 'tag_name' => 'a', 'class_name' => 'wc-block-customer-account__link' ) ) ) {
		$tags->set_attribute( 'href', home_url( '/' ) );
		$tags->set_attribute( 'aria-label', 'حسابي' );
	}
	return $tags->get_updated_html();
} );

// Saved navigation blocks can override theme files, so update the account link at render time too.
add_filter( 'render_block_core/navigation-link', static function ( $html, $block ) {
	if ( is_admin() || ! class_exists( 'WP_HTML_Tag_Processor' ) || 'حسابي' !== trim( $block['attrs']['label'] ?? '' ) ) {
		return $html;
	}
	$tags = new WP_HTML_Tag_Processor( $html );
	if ( $tags->next_tag( 'a' ) ) {
		$tags->set_attribute( 'href', home_url( '/' ) );
		$tags->remove_attribute( 'aria-current' );
	}
	return $tags->get_updated_html();
}, 10, 2 );

// Also handle a saved footer account link without changing the separate order-tracking link.
add_filter( 'render_block_core/list-item', static function ( $html ) {
	if ( is_admin() || ! class_exists( 'WP_HTML_Tag_Processor' ) || false === strpos( $html, 'حسابي' ) ) {
		return $html;
	}
	$tags = new WP_HTML_Tag_Processor( $html );
	if ( $tags->next_tag( 'a' ) ) {
		$href = $tags->get_attribute( 'href' );
		$account_url = function_exists( 'wc_get_page_permalink' ) ? wc_get_page_permalink( 'myaccount' ) : '';
		$path = is_string( $href ) ? wp_parse_url( $href, PHP_URL_PATH ) : '';
		$account_path = $account_url ? wp_parse_url( $account_url, PHP_URL_PATH ) : '/my-account/';
		if ( $path && $account_path && untrailingslashit( $path ) === untrailingslashit( $account_path ) ) {
			$tags->set_attribute( 'href', home_url( '/' ) );
		}
	}
	return $tags->get_updated_html();
} );
