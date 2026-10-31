<?php
/** Category-backed homepage product sections, using the shared WooCommerce cards. */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function wordsite_home_product_ids( $key = '' ) {
	$visibility = wc_get_product_visibility_term_ids();
	$excluded = array_filter( array( $visibility['exclude-from-catalog'] ?? 0 ) );
	if ( ! $key || 'yes' === get_option( 'woocommerce_hide_out_of_stock_items' ) ) {
		$excluded[] = $visibility['outofstock'] ?? 0;
	}
	$tax = array( array( 'taxonomy' => 'product_visibility', 'field' => 'term_taxonomy_id', 'terms' => array_filter( $excluded ), 'operator' => 'NOT IN' ) );
	if ( $key ) {
		$definitions = wordsite_store_menu_categories();
		$term = wordsite_store_menu_term( $key, $definitions[ $key ] );
		if ( ! $term ) {
			return array();
		}
		$tax[] = array( 'taxonomy' => 'product_cat', 'field' => 'term_id', 'terms' => array( $term->term_id ), 'include_children' => true );
	}
	$query = new WP_Query( array(
		'post_type' => 'product', 'post_status' => 'publish', 'has_password' => false,
		'posts_per_page' => 12, 'fields' => 'ids', 'no_found_rows' => true,
		'ignore_sticky_posts' => true, 'tax_query' => $tax,
		'meta_key' => 'total_sales', 'orderby' => array( 'meta_value_num' => 'DESC', 'ID' => 'DESC' ),
	) );
	return array_map( 'absint', $query->posts );
}

function wordsite_home_products() {
	$sections = array(
		array( 'key' => '', 'title' => 'الأكثر طلباً' ),
		array( 'key' => 'ai-subscriptions', 'title' => 'الذكاء الاصطناعي' ),
		array( 'key' => 'linkedin-subscriptions', 'title' => 'اشتراكات لينكدإن' ),
		array( 'key' => 'editing-design', 'title' => 'التصميم والمونتاج' ),
		array( 'key' => 'streaming-subscriptions', 'title' => 'الأفلام والمسلسلات' ),
	);
	$html = '<div class="ws-home-products ws-product">';
	$definitions = wordsite_store_menu_categories();
	foreach ( $sections as $index => $section ) {
		$url = wc_get_page_permalink( 'shop' );
		if ( $section['key'] ) {
			$term = wordsite_store_menu_term( $section['key'], $definitions[ $section['key'] ] );
			$term_url = $term ? get_term_link( $term, 'product_cat' ) : false;
			if ( $term_url && ! is_wp_error( $term_url ) ) {
				$url = $term_url;
			}
		} else {
			$url = add_query_arg( 'orderby', 'popularity', $url );
		}
		$cards = wordsite_product_list_html( wordsite_home_product_ids( $section['key'] ), '', true, 'home' );
		$html .= sprintf(
			'<section class="ws-home-shelf" data-home-shelf data-autoplay="%1$d" aria-labelledby="ws-home-shelf-%2$d"><div class="ws-home-shelf__head"><h2 id="ws-home-shelf-%2$d">%3$s</h2><div class="ws-home-shelf__tools"><a href="%4$s">عرض الكل%5$s</a><div class="ws-home-shelf__arrows" hidden><button type="button" data-shelf-prev aria-label="السابق في %3$s"><svg viewBox="0 0 24 24" fill="none" aria-hidden="true" focusable="false"><path d="m14 6-6 6 6 6" /></svg></button><button type="button" data-shelf-next aria-label="التالي في %3$s"><svg viewBox="0 0 24 24" fill="none" aria-hidden="true" focusable="false"><path d="m10 6 6 6-6 6" /></svg></button></div></div></div>',
			0 === $index ? 3000 : 0, $index, esc_html( $section['title'] ), esc_url( $url ), 0 === $index ? '' : ' <span aria-hidden="true">←</span>'
		);
		$html .= $cards ? '<div class="ws-home-shelf__viewport" dir="ltr" tabindex="0" aria-label="منتجات القسم، استخدم الأسهم أو اسحب للتنقل"><div class="ws-home-shelf__track">' . $cards . '</div></div>' : '<p class="ws-home-shelf__empty">ستظهر منتجات هذا القسم هنا بعد إضافتها.</p>';
		$html .= '</section>';
	}
	return $html . '</div>';
}
add_action( 'init', static function () {
	register_block_type( 'saudistore/home-products', array( 'render_callback' => 'wordsite_home_products' ) );
} );
add_action( 'wp_enqueue_scripts', static function () {
	if ( ! is_front_page() ) {
		return;
	}
	$ver = wp_get_theme()->get( 'Version' );
	wp_enqueue_style( 'wordsite-home-products', get_theme_file_uri( 'assets/css/home-products.css' ), array( 'wordsite-product' ), $ver );
	wp_enqueue_script( 'wordsite-home-products', get_theme_file_uri( 'assets/js/home-products.js' ), array( 'wordsite-product' ), $ver, true );
}, 30 );
