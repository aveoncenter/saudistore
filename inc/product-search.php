<?php
/**
 * Stock-aware catalog suggestions and category-based search recommendations.
 *
 * @package Wordsite
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function wordsite_is_product_search( $query = null ) {
	if ( ! $query ) {
		global $wp_query;
		$query = $wp_query;
	}
	return $query instanceof WP_Query && $query->is_search() && 'product' === $query->get( 'post_type' );
}

/**
 * Public catalog only: no drafts, passwords, hidden/search-excluded products,
 * backorders, or sold-out products. Read current stock on every request.
 */
function wordsite_catalog_conditions( $alias = 'p' ) {
	global $wpdb;
	$visibility = wc_get_product_visibility_term_ids();
	$excluded   = (int) ( $visibility['exclude-from-search'] ?? 0 );
	$lookup     = $wpdb->prefix . 'wc_product_meta_lookup';
	return "$alias.post_type = 'product' AND $alias.post_status = 'publish' AND $alias.post_password = ''
		AND EXISTS (SELECT 1 FROM $lookup ws_stock WHERE ws_stock.product_id = $alias.ID AND ws_stock.stock_status = 'instock')
		AND NOT EXISTS (SELECT 1 FROM {$wpdb->term_relationships} ws_tr
			INNER JOIN {$wpdb->term_taxonomy} ws_tt ON ws_tt.term_taxonomy_id = ws_tr.term_taxonomy_id
			WHERE ws_tr.object_id = $alias.ID AND ws_tt.taxonomy = 'product_visibility' AND ws_tt.term_id = $excluded)";
}

function wordsite_search_url( $term ) {
	return add_query_arg( array( 's' => $term, 'post_type' => 'product' ), home_url( '/' ) );
}

/** Word-prefix suggestions are extracted from actual available product titles. */
function wordsite_search_suggestions( $request ) {
	global $wpdb;
	$term   = trim( sanitize_text_field( $request->get_param( 'term' ) ) );
	$length = preg_match_all( '/./us', $term );
	if ( $length < 2 || $length > 40 ) {
		return rest_ensure_response( array( 'suggestions' => array() ) );
	}
	$like = '%' . $wpdb->esc_like( $term ) . '%';
	$rows = $wpdb->get_results( $wpdb->prepare(
		"SELECT p.ID, p.post_title FROM {$wpdb->posts} p WHERE " . wordsite_catalog_conditions() .
		' AND p.post_title LIKE %s ORDER BY p.post_title ASC LIMIT 500',
		$like
	) );
	$groups  = array();
	$pattern = '/(?:^|[^\p{L}\p{N}])(' . preg_quote( $term, '/' ) . '[\p{L}\p{N}+.&-]*)/iu';
	foreach ( $rows as $row ) {
		if ( ! preg_match_all( $pattern, html_entity_decode( $row->post_title, ENT_QUOTES, 'UTF-8' ), $matches ) ) {
			continue;
		}
		foreach ( $matches[1] as $label ) {
			$key = function_exists( 'mb_strtolower' ) ? mb_strtolower( $label, 'UTF-8' ) : strtolower( $label );
			if ( isset( $groups[ $key ] ) ) {
				continue;
			}
			$product = wc_get_product( $row->ID );
			if ( ! $product || ! $product->is_purchasable() || ! $product->is_in_stock() ) {
				continue;
			}
			$groups[ $key ] = array(
				'label' => $label,
				'url'   => wordsite_search_url( $label ),
				'image' => wp_get_attachment_image_url( $product->get_image_id(), 'woocommerce_thumbnail' ) ?: wc_placeholder_img_src( 'woocommerce_thumbnail' ),
			);
			if ( count( $groups ) >= 8 ) {
				break 2;
			}
		}
	}
	$suggestions = array_values( $groups );
	usort( $suggestions, static function ( $a, $b ) {
		return strnatcasecmp( $a['label'], $b['label'] );
	} );
	$response = rest_ensure_response( array( 'suggestions' => $suggestions ) );
	$response->header( 'Cache-Control', 'no-store' );
	return $response;
}
add_action( 'rest_api_init', static function () {
	register_rest_route( 'saudistore/v1', '/search-suggestions', array(
		'methods'             => 'GET',
		'callback'            => 'wordsite_search_suggestions',
		'permission_callback' => '__return_true',
		'args'                => array( 'term' => array( 'type' => 'string', 'required' => true, 'sanitize_callback' => 'sanitize_text_field' ) ),
	) );
} );

add_action( 'wp_enqueue_scripts', static function () {
	$version = wp_get_theme()->get( 'Version' );
	wp_enqueue_style( 'wordsite-search', get_theme_file_uri( 'assets/css/search.css' ), array( 'wordsite-style' ), $version );
	wp_enqueue_script( 'wordsite-search', get_theme_file_uri( 'assets/js/search.js' ), array( 'wordsite-header' ), $version, true );
	wp_add_inline_script( 'wordsite-search', 'window.wordsiteSearch = ' . wp_json_encode( array( 'endpoint' => rest_url( 'saudistore/v1/search-suggestions' ) ) ) . ';', 'before' );
}, 25 );

/** Search titles, not incidental mentions in descriptions; keep all results pages. */
add_filter( 'posts_search', static function ( $search, $query ) {
	if ( is_admin() || ! wordsite_is_product_search( $query ) ) {
		return $search;
	}
	global $wpdb;
	$term = trim( sanitize_text_field( $query->get( 's' ) ) );
	return $wpdb->prepare( " AND {$wpdb->posts}.post_title LIKE %s ", '%' . $wpdb->esc_like( $term ) . '%' );
}, 20, 2 );
add_filter( 'posts_where', static function ( $where, $query ) {
	if ( is_admin() || ! wordsite_is_product_search( $query ) ) {
		return $where;
	}
	global $wpdb;
	return $where . ' AND ' . wordsite_catalog_conditions( $wpdb->posts );
}, 20, 2 );
add_filter( 'woocommerce_redirect_single_search_result', static function ( $redirect ) {
	return wordsite_is_product_search() ? false : $redirect;
} );

/**
 * Recommend other available products from the matched products' categories.
 * Prefer the five configured categories over a broad parent such as subscriptions.
 */
function wordsite_search_related_ids( $term ) {
	global $wpdb;
	$like = '%' . $wpdb->esc_like( $term ) . '%';
	$cats = array_map( 'absint', $wpdb->get_col( $wpdb->prepare(
		"SELECT DISTINCT tt.term_id FROM {$wpdb->posts} p
		INNER JOIN {$wpdb->term_relationships} tr ON tr.object_id = p.ID
		INNER JOIN {$wpdb->term_taxonomy} tt ON tt.term_taxonomy_id = tr.term_taxonomy_id
		WHERE tt.taxonomy = 'product_cat' AND " . wordsite_catalog_conditions() . ' AND p.post_title LIKE %s',
		$like
	) ) );
	if ( ! $cats ) {
		return array();
	}
	$configured = array_map( 'absint', array_values( get_option( 'wordsite_store_category_ids', array() ) ) );
	$preferred  = array_intersect( $cats, $configured );
	$cats       = $preferred ? $preferred : $cats;
	$cat_list   = implode( ',', array_map( 'absint', $cats ) );
	return array_map( 'absint', $wpdb->get_col( $wpdb->prepare(
		"SELECT p.ID FROM {$wpdb->posts} p WHERE " . wordsite_catalog_conditions() .
		" AND p.post_title NOT LIKE %s AND EXISTS (
			SELECT 1 FROM {$wpdb->term_relationships} tr
			INNER JOIN {$wpdb->term_taxonomy} tt ON tt.term_taxonomy_id = tr.term_taxonomy_id
			WHERE tr.object_id = p.ID AND tt.taxonomy = 'product_cat' AND tt.term_id IN ($cat_list)
		) ORDER BY p.post_title ASC LIMIT 12",
		$like
	) ) );
}

function wordsite_block_search_results() {
	if ( ! wordsite_is_product_search() ) {
		return '';
	}
	global $wp_query;
	$term  = get_search_query( false );
	$ids   = wp_list_pluck( $wp_query->posts, 'ID' );
	$title = sprintf( 'نتائج البحث عن «%s»', $term );
	$html  = '<section class="ws-search-results"><h1>' . esc_html( $title ) . '</h1>';
	$html .= '<p class="ws-search-results__count">' . esc_html( sprintf( '%s منتج متوفر', number_format_i18n( $wp_query->found_posts ) ) ) . '</p>';
	if ( $ids ) {
		$html .= '<div class="ws-search-grid">' . wordsite_product_list_html( $ids, '', true ) . '</div>';
		$pages = paginate_links( array( 'total' => $wp_query->max_num_pages, 'current' => max( 1, (int) get_query_var( 'paged' ) ), 'type' => 'list' ) );
		if ( $pages ) {
			$html .= '<nav class="ws-search-pagination" aria-label="صفحات نتائج البحث">' . $pages . '</nav>';
		}
	} else {
		$html .= '<p class="ws-search-empty">لا توجد منتجات متوفرة بهذا الاسم. جرّب اسم خدمة آخر.</p>';
	}
	$html .= '</section>';
	return $html . wordsite_product_list_html( wordsite_search_related_ids( $term ), 'منتجات مشابهة' );
}
add_action( 'init', static function () {
	register_block_type( 'saudistore/search-results', array( 'render_callback' => 'wordsite_block_search_results' ) );
} );