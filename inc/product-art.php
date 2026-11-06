<?php
/**
 * Branded product card artwork.
 *
 * Demo products ship with generated gradient placeholders (their attachment
 * files are named demo-*). This swaps those placeholders for the artwork in
 * assets/images/products/ so the homepage cards, quick view, product page,
 * search results and cart all show the real promotional card. Products with a
 * genuine uploaded image are left alone.
 *
 * @package Wordsite
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! defined( 'WORDSITE_PRODUCT_ART_VERSION' ) ) {
	define( 'WORDSITE_PRODUCT_ART_VERSION', '1' );
}

/**
 * Brand keywords => artwork file name (without extension), matched against the
 * product slug and name. First match wins, so specific brands come first.
 *
 * @return array
 */
function wordsite_product_art_keywords() {
	return array(
		'manus-pro'        => array( 'مانوس', 'manus' ),
		'gemini-pro'       => array( 'جيميناي', 'gemini' ),
		'chatgpt-plus'     => array( 'chatgpt', 'شات جي بي تي' ),
		'canva-premium'    => array( 'كانفا', 'canva' ),
		'netflix-premium'  => array( 'نتفلكس', 'netflix' ),
		'spotify-premium'  => array( 'سبوتيفاي', 'spotify' ),
		'shahid-vip'       => array( 'شاهد', 'shahid' ),
		'linkedin-premium' => array( 'لينكد', 'linkedin' ),
	);
}

/**
 * Path to the artwork shipped for a product, or an empty string.
 *
 * @param WC_Product $product Product object.
 * @return string
 */
function wordsite_product_art_path( $product ) {
	$haystack = $product->get_slug() . ' ' . $product->get_name();
	foreach ( wordsite_product_art_keywords() as $art => $keywords ) {
		foreach ( $keywords as $keyword ) {
			if ( false === stripos( $haystack, $keyword ) ) {
				continue;
			}
			$path = get_theme_file_path( 'assets/images/products/' . $art . '.webp' );
			return is_readable( $path ) ? $path : '';
		}
	}
	return '';
}

/**
 * Whether the product still shows a generated demo placeholder (or nothing).
 *
 * @param WC_Product $product Product object.
 * @return bool
 */
function wordsite_product_has_placeholder_image( $product ) {
	$id = (int) $product->get_image_id();
	if ( ! $id ) {
		return true;
	}
	$file = get_attached_file( $id );
	if ( ! $file || ! file_exists( $file ) ) {
		return true;
	}
	return 0 === strpos( basename( $file ), 'demo-' );
}

/**
 * Copy a theme image into the media library.
 *
 * @param string $source Absolute path to the theme file.
 * @param string $title  Attachment title.
 * @return int Attachment ID, or 0 on failure.
 */
function wordsite_import_theme_image( $source, $title ) {
	if ( ! is_readable( $source ) ) {
		return 0;
	}
	require_once ABSPATH . 'wp-admin/includes/image.php';
	$uploads = wp_upload_dir();
	if ( ! empty( $uploads['error'] ) ) {
		return 0;
	}
	$filename = wp_unique_filename( $uploads['path'], basename( $source ) );
	$target   = trailingslashit( $uploads['path'] ) . $filename;
	if ( ! copy( $source, $target ) ) {
		return 0;
	}
	$type = wp_check_filetype( $filename, null );
	$id   = wp_insert_attachment(
		array(
			'post_mime_type' => $type['type'],
			'post_title'     => $title,
			'post_status'    => 'inherit',
		),
		$target
	);
	if ( is_wp_error( $id ) || ! $id ) {
		return 0;
	}
	wp_update_attachment_metadata( $id, wp_generate_attachment_metadata( $id, $target ) );
	return (int) $id;
}

/**
 * Attach the branded artwork to every product that still shows a placeholder.
 *
 * @param bool $force Run even when this version already ran.
 * @return int Number of products updated.
 */
function wordsite_apply_product_art( $force = false ) {
	if ( ! function_exists( 'wc_get_products' ) ) {
		return 0;
	}
	if ( ! $force && WORDSITE_PRODUCT_ART_VERSION === get_option( 'wordsite_product_art_version' ) ) {
		return 0;
	}
	$updated  = 0;
	$products = wc_get_products(
		array(
			'limit'  => 200,
			'status' => array( 'publish', 'draft', 'private' ),
		)
	);
	foreach ( $products as $product ) {
		if ( ! $product instanceof WC_Product || $product->get_parent_id() || ! wordsite_product_has_placeholder_image( $product ) ) {
			continue;
		}
		$source = wordsite_product_art_path( $product );
		if ( ! $source ) {
			continue;
		}
		$attachment_id = wordsite_import_theme_image( $source, $product->get_name() );
		if ( ! $attachment_id ) {
			continue;
		}
		$product->set_image_id( $attachment_id );
		$product->save();
		$updated++;
	}
	update_option( 'wordsite_product_art_version', WORDSITE_PRODUCT_ART_VERSION );
	return $updated;
}

/**
 * Run once for a store manager, so a theme update fixes the existing catalog
 * without anyone re-running the demo import.
 */
add_action( 'init', static function () {
	if ( is_user_logged_in() && current_user_can( 'manage_options' ) ) {
		wordsite_apply_product_art();
	}
}, 20 );
