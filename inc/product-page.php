<?php
/**
 * Single product page: dynamic blocks, purchase fields, buy-now, riyal symbol.
 *
 * @package Wordsite
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/* -------------------------------------------------------------------------
 * Helpers
 * ---------------------------------------------------------------------- */

/**
 * Product being viewed (works inside block templates).
 *
 * @return WC_Product|null
 */
function wordsite_current_product() {
	global $product;
	if ( $product instanceof WC_Product ) {
		return $product;
	}
	$id = get_queried_object_id();
	if ( ! $id ) {
		$id = get_the_ID();
	}
	$p = $id && function_exists( 'wc_get_product' ) ? wc_get_product( $id ) : null;
	return $p instanceof WC_Product ? $p : null;
}

/**
 * Highest discount % of a product (0 when not on sale).
 *
 * @param WC_Product $product Product.
 * @return float
 */
function wordsite_discount_percent( $product ) {
	if ( ! $product || ! $product->is_on_sale() ) {
		return 0;
	}
	$max = 0;
	if ( $product->is_type( 'variable' ) ) {
		$prices = $product->get_variation_prices( true );
		foreach ( $prices['regular_price'] as $vid => $regular ) {
			$sale = isset( $prices['price'][ $vid ] ) ? (float) $prices['price'][ $vid ] : (float) $regular;
			if ( (float) $regular > 0 && $sale < (float) $regular ) {
				$max = max( $max, ( (float) $regular - $sale ) / (float) $regular * 100 );
			}
		}
	} else {
		$regular = (float) $product->get_regular_price();
		$sale    = (float) $product->get_price();
		if ( $regular > 0 && $sale < $regular ) {
			$max = ( $regular - $sale ) / $regular * 100;
		}
	}
	return round( $max, 2 );
}

/**
 * Format a percentage like "68.85" / "25".
 *
 * @param float $pct Percent.
 * @return string
 */
function wordsite_format_pct( $pct ) {
	return rtrim( rtrim( number_format( (float) $pct, 2, '.', '' ), '0' ), '.' );
}

/**
 * Does this product ask for the e-mail of the account to activate?
 *
 * @param int $product_id Product ID.
 * @return bool
 */
function wordsite_needs_account( $product_id ) {
	$flag = get_post_meta( $product_id, '_ws_needs_account', true );
	if ( '' === $flag ) {
		// Never configured: on by default (filter to change).
		return (bool) apply_filters( 'wordsite_account_fields_default', true, $product_id );
	}
	return 'yes' === $flag;
}

/**
 * Also ask for the account password?
 *
 * @param int $product_id Product ID.
 * @return bool
 */
function wordsite_needs_password( $product_id ) {
	if ( ! wordsite_needs_account( $product_id ) ) {
		return false;
	}
	// Passwords are sensitive: only when explicitly enabled on the product.
	return 'yes' === get_post_meta( $product_id, '_ws_needs_password', true );
}

/**
 * Encrypt a secret with the site's auth salt (AES-256-CBC).
 *
 * @param string $plain Plain text.
 * @return string
 */
function wordsite_encrypt( $plain ) {
	if ( '' === $plain || ! function_exists( 'openssl_encrypt' ) ) {
		return '';
	}
	$key = hash( 'sha256', wp_salt( 'auth' ), true );
	$iv  = random_bytes( 16 );
	$enc = openssl_encrypt( $plain, 'aes-256-cbc', $key, OPENSSL_RAW_DATA, $iv );
	return false === $enc ? '' : 'v1:' . base64_encode( $iv . $enc ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode
}

/**
 * Decrypt a secret made by wordsite_encrypt().
 *
 * @param string $data Encrypted.
 * @return string
 */
function wordsite_decrypt( $data ) {
	if ( 0 !== strpos( (string) $data, 'v1:' ) || ! function_exists( 'openssl_decrypt' ) ) {
		return '';
	}
	$raw = base64_decode( substr( $data, 3 ), true ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_decode
	if ( false === $raw || strlen( $raw ) < 17 ) {
		return '';
	}
	$key   = hash( 'sha256', wp_salt( 'auth' ), true );
	$plain = openssl_decrypt( substr( $raw, 16 ), 'aes-256-cbc', $key, OPENSSL_RAW_DATA, substr( $raw, 0, 16 ) );
	return false === $plain ? '' : $plain;
}

/**
 * Terms text the customer must accept ('' = none).
 *
 * @param int $product_id Product ID.
 * @return string
 */
function wordsite_terms_text( $product_id ) {
	if ( ! metadata_exists( 'post', $product_id, '_ws_terms' ) ) {
		// Never configured: sensible default (filter to change, '' to disable).
		return trim( (string) apply_filters( 'wordsite_default_terms', 'وقت الشراء ما يكون فيه اشتراك فعال بالحساب', $product_id ) );
	}
	return trim( (string) get_post_meta( $product_id, '_ws_terms', true ) );
}

/**
 * Product needs fields filled on its own page?
 *
 * @param int $product_id Product ID.
 * @return bool
 */
function wordsite_has_fields( $product_id ) {
	return wordsite_needs_account( $product_id ) || '' !== wordsite_terms_text( $product_id );
}

/**
 * Account field label, e.g. "ايميل لنكد إن".
 *
 * @param int $product_id Product ID.
 * @return string
 */
function wordsite_account_label( $product_id ) {
	$name = trim( (string) get_post_meta( $product_id, '_ws_account_name', true ) );
	if ( '' === $name ) {
		$name = wordsite_guess_service( get_the_title( $product_id ) );
	}
	return $name ? 'ايميل ' . $name : 'ايميل الحساب';
}

/**
 * Guess the service name from the product title (used when "اسم الخدمة" is empty).
 *
 * @param string $title Product title.
 * @return string
 */
function wordsite_guess_service( $title ) {
	$map = array(
		'/linked\s*in|لينكد|لنكد/iu' => 'لنكد إن',
		'/netflix|نتفلكس|نتفليكس/iu'  => 'نتفلكس',
		'/chat\s*gpt|شات\s*جي/iu'   => 'ChatGPT',
		'/canva|كانفا/iu'             => 'كانفا',
		'/spotify|سبوتيفاي/iu'        => 'سبوتيفاي',
		'/shahid|شاهد/iu'             => 'شاهد',
		'/youtube|يوتيوب/iu'          => 'يوتيوب',
		'/adobe|أدوبي|ادوبي/iu'       => 'أدوبي',
	);
	foreach ( $map as $re => $name ) {
		if ( preg_match( $re, (string) $title ) ) {
			return $name;
		}
	}
	return '';
}

/**
 * Inline SVG icons.
 *
 * @param string $name Icon name.
 * @return string
 */
function wordsite_icon( $name ) {
	$icons = array(
		'star'     => '<svg viewBox="0 0 24 24" aria-hidden="true"><path fill="currentColor" d="M12 2.5l2.9 6.1 6.6.8-4.9 4.6 1.3 6.6L12 17.3l-5.9 3.3 1.3-6.6L2.5 9.4l6.6-.8z"/></svg>',
		'share'    => '<svg viewBox="0 0 24 24" aria-hidden="true" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"><circle cx="18" cy="5" r="2.5"/><circle cx="6" cy="12" r="2.5"/><circle cx="18" cy="19" r="2.5"/><path d="M8.2 10.8l7.6-4.4M8.2 13.2l7.6 4.4"/></svg>',
		'fire'     => '<svg viewBox="0 0 24 24" aria-hidden="true"><path fill="currentColor" d="M13.5 1.5s.7 3-1.6 5.8C9.6 10.1 6 12 6 16a6 6 0 0012 0c0-2.4-1.1-4-2-5 .1 1.7-.6 3-1.8 3.4.6-2.4.4-5.6-.7-12.9zM12 21a3 3 0 01-3-3c0-1.7 1.3-2.8 2.4-4 .2 1.4 1 2.2 1.9 2.6.9.4 1.7 1.1 1.7 2.1A2.9 2.9 0 0112 21z"/></svg>',
		'cursor'   => '<svg viewBox="0 0 24 24" aria-hidden="true" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="M9 9l11 4-5 1.5L13.5 20z"/><path d="M5 3v3M2 6h3M3 3l2 2M9.5 2.5L8.8 5M2.5 9.5L5 8.8"/></svg>',
		'cart'     => '<svg viewBox="0 0 24 24" aria-hidden="true" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 4h2l2.4 10.2a1 1 0 001 .8h9.2a1 1 0 001-.8L20 7H6.2"/><circle cx="9" cy="19.5" r="1.3"/><circle cx="17" cy="19.5" r="1.3"/><path d="M11 10h5M13.5 7.5v5"/></svg>',
		'wallet'   => '<svg viewBox="0 0 24 24" aria-hidden="true" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linejoin="round"><path d="M4 7h14a2 2 0 012 2v9a2 2 0 01-2 2H6a2 2 0 01-2-2z"/><path d="M4 7l11-3v3M20 11h-4a2 2 0 000 4h4"/></svg>',
		'chevron'  => '<svg viewBox="0 0 24 24" aria-hidden="true" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M15 5l-7 7 7 7"/></svg>',
		'eye'      => '<svg viewBox="0 0 24 24" aria-hidden="true" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M2 12s3.6-7 10-7 10 7 10 7-3.6 7-10 7S2 12 2 12z"/><circle cx="12" cy="12" r="3"/></svg>',
		'close'    => '<svg viewBox="0 0 24 24" aria-hidden="true" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M6 6l12 12M18 6L6 18"/></svg>',
		'whatsapp' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path fill="currentColor" d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413Z"/></svg>',
	);
	return isset( $icons[ $name ] ) ? $icons[ $name ] : '';
}

/**
 * Five stars filled to a rating (0–5).
 *
 * @param float $rating Rating.
 * @return string
 */
function wordsite_stars( $rating ) {
	$row = str_repeat( wordsite_icon( 'star' ), 5 );
	return sprintf(
		'<span class="ws-stars" role="img" aria-label="%1$s"><span class="ws-stars__base">%2$s</span><span class="ws-stars__fill" style="width:%3$s%%">%2$s</span></span>',
		esc_attr( sprintf( 'التقييم %s من 5', wc_format_decimal( $rating, 1 ) ) ),
		$row,
		esc_attr( min( 100, max( 0, $rating / 5 * 100 ) ) )
	);
}

/* -------------------------------------------------------------------------
 * Saudi Riyal symbol (U+20C1) instead of "ر.س" on the storefront
 * ---------------------------------------------------------------------- */

/**
 * Swap the SAR symbol. Disable with: add_filter( 'wordsite_riyal_symbol', '__return_false' );
 *
 * @param string $symbol   Symbol.
 * @param string $currency Currency code.
 * @return string
 */
function wordsite_currency_symbol( $symbol, $currency ) {
	if ( 'SAR' !== $currency || is_admin() || did_action( 'woocommerce_email_header' ) || ! apply_filters( 'wordsite_riyal_symbol', true ) ) {
		return $symbol;
	}
	return "\u{20C1}";
}
add_filter( 'woocommerce_currency_symbol', 'wordsite_currency_symbol', 20, 2 );

/* -------------------------------------------------------------------------
 * Assets
 * ---------------------------------------------------------------------- */

/**
 * Product page CSS / JS.
 */
function wordsite_product_assets() {
	if ( ! function_exists( 'is_product' ) || ! is_product() ) {
		return;
	}
	$ver = wp_get_theme()->get( 'Version' );
	wp_enqueue_style( 'wordsite-product', get_theme_file_uri( 'assets/css/product.css' ), array( 'wordsite-style' ), $ver );
	wp_enqueue_script( 'wc-add-to-cart' );
	wp_enqueue_script( 'wordsite-product', get_theme_file_uri( 'assets/js/product.js' ), array( 'jquery' ), $ver, true );
}
add_action( 'wp_enqueue_scripts', 'wordsite_product_assets', 20 );

/* -------------------------------------------------------------------------
 * Dynamic blocks used by templates/single-product.html
 * ---------------------------------------------------------------------- */

/**
 * Register blocks.
 */
function wordsite_register_product_blocks() {
	$blocks = array(
		'product-meta'   => 'wordsite_block_product_meta',
		'price-row'      => 'wordsite_block_price_row',
		'sold-count'     => 'wordsite_block_sold_count',
		'whatsapp-box'   => 'wordsite_block_whatsapp_box',
		'excerpt'        => 'wordsite_block_excerpt',
		'rating-summary' => 'wordsite_block_rating_summary',
	);
	foreach ( $blocks as $name => $cb ) {
		register_block_type( 'saudistore/' . $name, array( 'render_callback' => $cb ) );
	}
	register_block_type(
		'saudistore/product-carousel',
		array(
			'render_callback' => 'wordsite_block_carousel',
			'attributes'      => array(
				'type'  => array(
					'type'    => 'string',
					'default' => 'related',
				),
				'title' => array(
					'type'    => 'string',
					'default' => 'منتجات مشابهة',
				),
			),
		)
	);
}
add_action( 'init', 'wordsite_register_product_blocks' );

/**
 * Rating + share.
 *
 * @return string
 */
function wordsite_block_product_meta() {
	$product = wordsite_current_product();
	if ( ! $product ) {
		return '';
	}
	$count  = $product->get_review_count();
	$rating = '<span></span>';
	if ( $count && wc_review_ratings_enabled() ) {
		$rating = sprintf(
			'<a class="ws-pmeta__rating" href="#ws-reviews">%s<span>(%s تقييم)</span></a>',
			wordsite_stars( (float) $product->get_average_rating() ),
			esc_html( number_format_i18n( $count ) )
		);
	}
	return sprintf(
		'<div class="ws-pmeta">%s<div class="ws-pmeta__actions"><button type="button" class="ws-icon-btn" data-ws-share aria-label="مشاركة المنتج" title="مشاركة">%s<span class="ws-icon-btn__tip">تم نسخ الرابط</span></button></div></div>',
		$rating,
		wordsite_icon( 'share' )
	);
}

/**
 * Price + discount badge.
 *
 * @return string
 */
function wordsite_block_price_row() {
	$product = wordsite_current_product();
	if ( ! $product ) {
		return '';
	}
	$pct = wordsite_discount_percent( $product );
	return sprintf(
		'<div class="ws-price-row"><div class="ws-price" data-ws-price>%s</div><span class="ws-discount" data-ws-discount%s>- <bdi>%s</bdi> %%</span></div>',
		$product->get_price_html(),
		$pct > 0 ? '' : ' hidden',
		esc_html( wordsite_format_pct( $pct ) )
	);
}

/**
 * "Sold more than N times".
 *
 * @return string
 */
function wordsite_block_sold_count() {
	$product = wordsite_current_product();
	if ( ! $product ) {
		return '';
	}
	$custom = get_post_meta( $product->get_id(), '_ws_sold_display', true );
	$count  = '' !== $custom ? absint( $custom ) : absint( $product->get_total_sales() );
	if ( ! $count ) {
		return '';
	}
	return sprintf(
		'<div class="ws-sold"><span class="ws-sold__icon">%s</span><strong>%s مرة</strong><span class="ws-sold__label">تم بيعه أكثر من</span></div>',
		wordsite_icon( 'fire' ),
		esc_html( number_format_i18n( $count ) )
	);
}

/**
 * WhatsApp enquiry box.
 *
 * @return string
 */
function wordsite_block_whatsapp_box() {
	$product = wordsite_current_product();
	$url     = function_exists( 'wordsite_whatsapp_url' ) ? wordsite_whatsapp_url() : '';
	if ( ! $product || ! $url ) {
		return '';
	}
	$url = add_query_arg( 'text', rawurlencode( 'مرحباً، عندي استفسار عن: ' . $product->get_name() . ' ' . $product->get_permalink() ), $url );
	return sprintf(
		'<a class="ws-wa-box" href="%s" target="_blank" rel="noopener"><span class="ws-wa-box__text">%s حاب تستفسر عن المنتج؟ تواصل معنا</span><span class="ws-wa-box__icon">%s</span></a>',
		esc_url( $url ),
		wordsite_icon( 'cursor' ),
		wordsite_icon( 'whatsapp' )
	);
}

/**
 * Short description with "read more".
 *
 * @return string
 */
function wordsite_block_excerpt() {
	$product = wordsite_current_product();
	if ( ! $product ) {
		return '';
	}
	$text = $product->get_short_description();
	if ( ! $text ) {
		$text = wp_trim_words( wp_strip_all_tags( $product->get_description() ), 40 );
	}
	if ( ! $text ) {
		return '';
	}
	return sprintf(
		'<div class="ws-excerpt"><div class="ws-excerpt__text">%s</div><a class="ws-readmore" href="#ws-details">اقرأ المزيد</a></div>',
		wp_kses_post( apply_filters( 'woocommerce_short_description', $text ) )
	);
}

/**
 * Overall rating with bars.
 *
 * @return string
 */
function wordsite_block_rating_summary() {
	$product = wordsite_current_product();
	if ( ! $product || ! wc_review_ratings_enabled() ) {
		return '';
	}
	$count  = (int) $product->get_review_count();
	$avg    = (float) $product->get_average_rating();
	$counts = $product->get_rating_counts();
	$total  = array_sum( $counts );
	$bars   = '';
	for ( $i = 5; $i >= 1; $i-- ) {
		$n     = isset( $counts[ $i ] ) ? (int) $counts[ $i ] : 0;
		$pct   = $total ? round( $n / $total * 100 ) : 0;
		$bars .= sprintf(
			'<li><span class="ws-bar__star">%1$s<b>%2$d</b></span><span class="ws-bar"><span style="width:%3$d%%"></span></span><span class="ws-bar__pct">%3$d%%</span></li>',
			wordsite_icon( 'star' ),
			$i,
			$pct
		);
	}
	return sprintf(
		'<section class="ws-reviews-summary" id="ws-reviews"><div class="ws-reviews-summary__score"><h2>التقييم العام</h2><div class="ws-score"><strong>%1$s</strong><div>%2$s<small>%3$s</small></div></div></div><ul class="ws-reviews-summary__bars">%4$s</ul></section>',
		esc_html( $count ? wc_format_decimal( $avg, 1 ) : '0' ),
		wordsite_stars( $avg ),
		esc_html( $count ? sprintf( 'بناءً على %s تقييم', number_format_i18n( $count ) ) : 'لا توجد تقييمات بعد' ),
		$bars
	);
}

/**
 * Product IDs for a carousel.
 *
 * @param WC_Product $product Current product.
 * @param string     $type    related|upsell.
 * @return int[]
 */
function wordsite_carousel_ids( $product, $type ) {
	$id      = $product->get_id();
	$related = wc_get_related_products( $id, 12 );
	if ( 'upsell' === $type ) {
		$ids = $product->get_upsell_ids();
		if ( $ids ) {
			return array_slice( array_map( 'absint', $ids ), 0, 12 );
		}
		$exclude = array_merge( array( $id ), $related );
	} else {
		if ( $related ) {
			return $related;
		}
		$exclude = array( $id );
	}
	$q = new WP_Query(
		array(
			'post_type'           => 'product',
			'post_status'         => 'publish',
			'posts_per_page'      => 12,
			'fields'              => 'ids',
			'no_found_rows'       => true,
			'ignore_sticky_posts' => true,
			'post__not_in'        => $exclude,
			'meta_key'            => 'total_sales', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
			'orderby'             => 'meta_value_num',
			'order'               => 'DESC',
			'tax_query'           => WC()->query->get_tax_query(), // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
		)
	);
	return array_map( 'absint', $q->posts );
}

/**
 * Add-to-cart button for a card.
 *
 * @param WC_Product $p Product.
 * @return string
 */
function wordsite_card_cart_button( $p ) {
	if ( $p->is_type( 'simple' ) && $p->is_purchasable() && $p->is_in_stock() && ! wordsite_has_fields( $p->get_id() ) ) {
		return sprintf(
			'<a href="%s" data-quantity="1" data-product_id="%d" data-product_sku="%s" class="ws-card__btn ws-card__btn--cart add_to_cart_button ajax_add_to_cart" rel="nofollow" aria-label="%s">%s أضف للسلة</a>',
			esc_url( $p->add_to_cart_url() ),
			$p->get_id(),
			esc_attr( $p->get_sku() ),
			esc_attr( 'أضف ' . $p->get_name() . ' للسلة' ),
			wordsite_icon( 'cart' )
		);
	}
	return sprintf(
		'<a href="%s" class="ws-card__btn ws-card__btn--cart">%s %s</a>',
		esc_url( $p->get_permalink() ),
		wordsite_icon( 'cart' ),
		$p->is_in_stock() ? 'اختر الباقة' : 'غير متوفر'
	);
}

/**
 * Product carousel.
 *
 * @param array $attrs Attributes.
 * @return string
 */
function wordsite_block_carousel( $attrs ) {
	$product = wordsite_current_product();
	if ( ! $product ) {
		return '';
	}
	$ids = wordsite_carousel_ids( $product, isset( $attrs['type'] ) ? $attrs['type'] : 'related' );
	if ( ! $ids ) {
		return '';
	}
	$cards = '';
	foreach ( $ids as $pid ) {
		$p = wc_get_product( $pid );
		if ( ! $p || ! $p->is_visible() ) {
			continue;
		}
		$link   = esc_url( $p->get_permalink() );
		$name   = esc_html( $p->get_name() );
		$button = wordsite_card_cart_button( $p );
		$desc   = $p->get_short_description() ? $p->get_short_description() : wp_trim_words( wp_strip_all_tags( $p->get_description() ), 35 );
		$quick  = sprintf(
			'<div class="ws-quick__media">%s</div><div class="ws-quick__info"><h3>%s</h3><div class="ws-quick__price">%s</div><div class="ws-quick__desc">%s</div><div class="ws-quick__actions">%s<a class="ws-card__btn ws-card__btn--quick" href="%s">عرض التفاصيل</a></div></div>',
			$p->get_image( 'woocommerce_single' ),
			$name,
			$p->get_price_html(),
			wp_kses_post( wpautop( $desc ) ),
			$button,
			$link
		);
		$cards .= sprintf(
			'<article class="ws-card"><a class="ws-card__img" href="%1$s" tabindex="-1">%2$s</a><h3 class="ws-card__title"><a href="%1$s">%3$s</a></h3><div class="ws-card__price">%4$s</div><div class="ws-card__actions">%5$s<button type="button" class="ws-card__btn ws-card__btn--quick" data-ws-quick>نظرة سريعة</button></div><template class="ws-quick-tpl">%6$s</template></article>',
			$link,
			$p->get_image( 'woocommerce_thumbnail' ),
			$name,
			$p->get_price_html(),
			$button,
			$quick
		);
	}
	if ( ! $cards ) {
		return '';
	}
	add_action( 'wp_footer', 'wordsite_quick_view_dialog' );
	return sprintf(
		'<section class="ws-carousel" data-ws-carousel><div class="ws-carousel__head"><h2>%1$s</h2><div class="ws-carousel__nav"><button type="button" data-dir="prev" aria-label="السابق">%2$s</button><button type="button" data-dir="next" aria-label="التالي">%2$s</button></div></div><div class="ws-carousel__track">%3$s</div></section>',
		esc_html( $attrs['title'] ),
		wordsite_icon( 'chevron' ),
		$cards
	);
}

/**
 * Quick-view modal shell.
 */
function wordsite_quick_view_dialog() {
	static $done = false;
	if ( $done ) {
		return;
	}
	$done = true;
	printf(
		'<dialog class="ws-quick" id="ws-quick" aria-label="نظرة سريعة"><button type="button" class="ws-quick__close" data-ws-close aria-label="إغلاق">%s</button><div class="ws-quick__body"></div></dialog>',
		wordsite_icon( 'close' ) // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	);
}

/* -------------------------------------------------------------------------
 * Add-to-cart form: options card + buy box
 * ---------------------------------------------------------------------- */

/**
 * Terms + account e-mail field markup.
 *
 * @param WC_Product $product Product.
 * @return string
 */
function wordsite_purchase_fields( $product ) {
	$id    = $product->get_id();
	$terms = wordsite_terms_text( $id );
	$acct  = wordsite_needs_account( $id );
	if ( ! $terms && ! $acct ) {
		return '';
	}
	// Account fields stay hidden until a plan is chosen (variable) and the terms box is ticked.
	$variable = $product->is_type( 'variable' ) || '' !== $terms;
	$out      = '<div class="ws-fields">';
	if ( $terms ) {
		$out .= sprintf(
			'<div class="ws-field ws-field--terms"><span class="ws-field__label">الشــروط<span class="ws-req">*</span></span><label class="ws-check"><input type="checkbox" name="ws_terms" value="1" required><span class="ws-check__box"></span><span>%s</span></label></div>',
			esc_html( $terms )
		);
	}
	if ( $acct ) {
		$out .= sprintf(
			'<div class="ws-field ws-field--account"%1$s><label class="ws-field__label" for="ws_account_email">%2$s<span class="ws-req">*</span><small>User@mail.com</small></label><input type="email" id="ws_account_email" name="ws_account_email" placeholder="User@mail.com" autocomplete="email" dir="ltr" required%3$s></div>',
			$variable ? ' hidden' : '',
			esc_html( wordsite_account_label( $id ) ),
			$variable ? ' disabled' : ''
		);
		if ( wordsite_needs_password( $id ) ) {
			$out .= sprintf(
				'<div class="ws-field ws-field--account"%1$s><label class="ws-field__label" for="ws_account_password">كلمة المرور<span class="ws-req">*</span><small>Password</small></label><div class="ws-pass"><input type="password" id="ws_account_password" name="ws_account_password" placeholder="Password" autocomplete="new-password" dir="ltr" required%2$s><button type="button" class="ws-pass__toggle" data-ws-pass aria-label="إظهار كلمة المرور">%3$s</button></div></div><p class="ws-field__note ws-field--account"%1$s>%4$s</p>',
				$variable ? ' hidden' : '',
				$variable ? ' disabled' : '',
				wordsite_icon( 'eye' ),
				esc_html( 'بياناتك مشفّرة ولا يراها إلا فريق التفعيل، وتُحذف تلقائياً بعد إتمام الطلب.' )
			);
		}
	}
	return $out . '</div>';
}

/**
 * Open the options card (variable products: wraps the variations table).
 */
function wordsite_open_options_card() {
	echo '<div class="ws-opts">';
}
add_action( 'woocommerce_before_variations_form', 'wordsite_open_options_card', 5 );

/**
 * Close the options card after the variations table + our fields.
 */
function wordsite_close_options_card() {
	global $product;
	echo wordsite_purchase_fields( $product ) . '</div>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
}
add_action( 'woocommerce_after_variations_table', 'wordsite_close_options_card', 99 );

/**
 * Open the buy box (before the add-to-cart button, both product types).
 */
function wordsite_open_buybox() {
	global $product;
	if ( ! $product instanceof WC_Product ) {
		return;
	}
	if ( ! $product->is_type( 'variable' ) ) {
		$fields = wordsite_purchase_fields( $product );
		if ( $fields ) {
			echo '<div class="ws-opts">' . $fields . '</div>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		}
	}
	printf( '<div class="ws-buybox"><div class="ws-buybox__price" data-ws-price>%s</div>', $product->get_price_html() ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
}
add_action( 'woocommerce_before_add_to_cart_button', 'wordsite_open_buybox', 1 );

/**
 * "Buy now" button + close the buy box.
 */
function wordsite_close_buybox() {
	global $product;
	if ( ! $product instanceof WC_Product ) {
		return;
	}
	$variable = $product->is_type( 'variable' );
	printf(
		'%s<button type="submit" name="ws_buy_now" value="1" class="ws-buy-now button%s">%s اشترِ الآن</button></div>',
		$variable ? '' : sprintf( '<input type="hidden" name="add-to-cart" value="%d">', absint( $product->get_id() ) ), // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		$variable ? ' disabled' : '',
		wordsite_icon( 'wallet' ) // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	);
}
add_action( 'woocommerce_after_add_to_cart_button', 'wordsite_close_buybox', 99 );

/**
 * Buy now → straight to checkout.
 *
 * @param string $url Redirect URL.
 * @return string
 */
function wordsite_buy_now_redirect( $url ) {
	return empty( $_REQUEST['ws_buy_now'] ) ? $url : wc_get_checkout_url(); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
}
add_filter( 'woocommerce_add_to_cart_redirect', 'wordsite_buy_now_redirect', 99 );

/**
 * No "added to cart" notice when buying now.
 *
 * @param string $message Message.
 * @return string
 */
function wordsite_buy_now_message( $message ) {
	return empty( $_REQUEST['ws_buy_now'] ) ? $message : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
}
add_filter( 'wc_add_to_cart_message_html', 'wordsite_buy_now_message', 99 );

/**
 * Products with required fields must be bought from their page (no AJAX add from lists).
 *
 * @param bool       $supports Supports.
 * @param string     $feature  Feature.
 * @param WC_Product $product  Product.
 * @return bool
 */
function wordsite_no_ajax_for_field_products( $supports, $feature, $product ) {
	if ( 'ajax_add_to_cart' === $feature && $product && wordsite_has_fields( $product->get_id() ) ) {
		return false;
	}
	return $supports;
}
add_filter( 'woocommerce_product_supports', 'wordsite_no_ajax_for_field_products', 10, 3 );

/**
 * Link list buttons to the product page for those products.
 *
 * @param string     $url     URL.
 * @param WC_Product $product Product.
 * @return string
 */
function wordsite_field_products_cart_url( $url, $product ) {
	if ( $product && $product->is_type( 'simple' ) && wordsite_has_fields( $product->get_id() ) ) {
		return $product->get_permalink();
	}
	return $url;
}
add_filter( 'woocommerce_product_add_to_cart_url', 'wordsite_field_products_cart_url', 10, 2 );

/* -------------------------------------------------------------------------
 * Validate / store the purchase fields
 * ---------------------------------------------------------------------- */

/**
 * Validate.
 *
 * @param bool $passed     Passed.
 * @param int  $product_id Product ID.
 * @return bool
 */
function wordsite_validate_fields( $passed, $product_id ) {
	// phpcs:disable WordPress.Security.NonceVerification.Missing
	if ( wordsite_terms_text( $product_id ) && empty( $_POST['ws_terms'] ) ) {
		wc_add_notice( 'يرجى الموافقة على الشروط قبل الشراء.', 'error' );
		$passed = false;
	}
	if ( wordsite_needs_account( $product_id ) ) {
		$email = isset( $_POST['ws_account_email'] ) ? sanitize_email( wp_unslash( $_POST['ws_account_email'] ) ) : '';
		if ( ! is_email( $email ) ) {
			wc_add_notice( 'يرجى إدخال ' . wordsite_account_label( $product_id ) . ' بشكل صحيح.', 'error' );
			$passed = false;
		}
		if ( wordsite_needs_password( $product_id ) ) {
			$pass = isset( $_POST['ws_account_password'] ) ? (string) wp_unslash( $_POST['ws_account_password'] ) : ''; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
			if ( '' === trim( $pass ) ) {
				wc_add_notice( 'يرجى إدخال كلمة مرور الحساب.', 'error' );
				$passed = false;
			} elseif ( ! function_exists( 'openssl_encrypt' ) ) {
				wc_add_notice( 'تعذّر حفظ البيانات بشكل آمن، يرجى التواصل معنا عبر واتساب.', 'error' );
				$passed = false;
			}
		}
	}
	// phpcs:enable
	return $passed;
}
add_filter( 'woocommerce_add_to_cart_validation', 'wordsite_validate_fields', 10, 2 );

/**
 * Save to the cart item.
 *
 * @param array $data       Cart item data.
 * @param int   $product_id Product ID.
 * @return array
 */
function wordsite_cart_item_data( $data, $product_id ) {
	// phpcs:disable WordPress.Security.NonceVerification.Missing
	if ( wordsite_needs_account( $product_id ) && isset( $_POST['ws_account_email'] ) ) {
		$data['ws_account'] = array(
			'label' => wordsite_account_label( $product_id ),
			'email' => sanitize_email( wp_unslash( $_POST['ws_account_email'] ) ),
		);
		if ( wordsite_needs_password( $product_id ) && isset( $_POST['ws_account_password'] ) ) {
			// Stored encrypted only; never shown on the storefront or in customer e-mails.
			$data['ws_account']['password'] = wordsite_encrypt( (string) wp_unslash( $_POST['ws_account_password'] ) ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
			$data['ws_unique']              = wp_generate_uuid4();
		}
	}
	if ( wordsite_terms_text( $product_id ) && ! empty( $_POST['ws_terms'] ) ) {
		$data['ws_terms'] = 1;
	}
	// phpcs:enable
	return $data;
}
add_filter( 'woocommerce_add_cart_item_data', 'wordsite_cart_item_data', 10, 2 );

/**
 * Show the account e-mail in cart / checkout.
 *
 * @param array $item_data Item data.
 * @param array $cart_item Cart item.
 * @return array
 */
function wordsite_cart_item_display( $item_data, $cart_item ) {
	if ( ! empty( $cart_item['ws_account']['email'] ) ) {
		$item_data[] = array(
			'key'   => $cart_item['ws_account']['label'],
			'value' => $cart_item['ws_account']['email'],
		);
	}
	return $item_data;
}
add_filter( 'woocommerce_get_item_data', 'wordsite_cart_item_display', 10, 2 );

/**
 * Copy to the order line item (visible in the admin order and e-mails).
 *
 * @param WC_Order_Item_Product $item   Item.
 * @param string                $key    Cart key.
 * @param array                 $values Cart item.
 */
function wordsite_order_item_meta( $item, $key, $values ) {
	if ( ! empty( $values['ws_account'] ) ) {
		$item->add_meta_data( $values['ws_account']['label'], $values['ws_account']['email'], true );
		if ( ! empty( $values['ws_account']['password'] ) ) {
			$item->add_meta_data( '_ws_account_password', $values['ws_account']['password'], true );
		}
	}
	if ( ! empty( $values['ws_terms'] ) ) {
		$item->add_meta_data( 'وافق على الشروط', 'نعم', true );
	}
}
add_action( 'woocommerce_checkout_create_order_line_item', 'wordsite_order_item_meta', 10, 3 );

/**
 * Show the decrypted password to shop managers on the order screen only.
 *
 * @param int                   $item_id Item ID.
 * @param WC_Order_Item_Product $item    Item.
 */
function wordsite_admin_item_password( $item_id, $item ) {
	if ( ! is_admin() || ! current_user_can( 'edit_shop_orders' ) || ! is_callable( array( $item, 'get_meta' ) ) ) {
		return;
	}
	$enc = $item->get_meta( '_ws_account_password' );
	if ( ! $enc ) {
		return;
	}
	$pass = wordsite_decrypt( $enc );
	printf(
		'<div class="ws-admin-pass" style="margin-top:6px"><strong>كلمة مرور الحساب:</strong> <code style="user-select:all">%s</code><br><small style="color:#787c82">تُحذف تلقائياً عند تغيير حالة الطلب إلى مكتمل أو ملغي أو مسترد أو فاشل.</small></div>',
		esc_html( '' !== $pass ? $pass : '—' )
	);
}
add_action( 'woocommerce_after_order_itemmeta', 'wordsite_admin_item_password', 10, 2 );

/**
 * Delete stored passwords once the order is finished.
 *
 * @param int $order_id Order ID.
 */
function wordsite_purge_order_passwords( $order_id ) {
	$order = wc_get_order( $order_id );
	if ( ! $order ) {
		return;
	}
	$purged = false;
	foreach ( $order->get_items() as $item ) {
		if ( $item->get_meta( '_ws_account_password' ) ) {
			$item->delete_meta_data( '_ws_account_password' );
			$item->save();
			$purged = true;
		}
	}
	if ( $purged ) {
		$order->add_order_note( 'تم حذف كلمة مرور حساب العميل تلقائياً.' );
	}
}
foreach ( array( 'completed', 'cancelled', 'refunded', 'failed' ) as $wordsite_status ) {
	add_action( 'woocommerce_order_status_' . $wordsite_status, 'wordsite_purge_order_passwords' );
}
unset( $wordsite_status );

/**
 * Keep the password out of every generic meta display.
 *
 * @param array $keys Hidden keys.
 * @return array
 */
function wordsite_hide_password_meta( $keys ) {
	$keys[] = '_ws_account_password';
	return $keys;
}
add_filter( 'woocommerce_hidden_order_itemmeta', 'wordsite_hide_password_meta' );

/* -------------------------------------------------------------------------
 * Product edit screen settings (Product data → General)
 * ---------------------------------------------------------------------- */

/**
 * Fields.
 */
function wordsite_product_settings_fields() {
	echo '<div class="options_group">';
	woocommerce_wp_text_input(
		array(
			'id'                => '_ws_sold_display',
			'label'             => 'عدد المبيعات المعروض',
			'type'              => 'number',
			'custom_attributes' => array( 'min' => '0' ),
			'desc_tip'          => true,
			'description'       => 'يظهر في "تم بيعه أكثر من N مرة". اتركه فارغاً لعرض المبيعات الفعلية.',
		)
	);
	woocommerce_wp_textarea_input(
		array(
			'id'          => '_ws_terms',
			'value'       => wordsite_terms_text( get_the_ID() ),
			'label'       => 'نص الشروط',
			'placeholder' => 'وقت الشراء ما يكون فيه اشتراك فعال بالحساب',
			'desc_tip'    => true,
			'description' => 'إذا كتبت نصاً هنا، يجب على العميل تحديد مربع الموافقة قبل الشراء.',
		)
	);
	woocommerce_wp_checkbox(
		array(
			'id'          => '_ws_needs_account',
			'value'       => wordsite_needs_account( get_the_ID() ) ? 'yes' : 'no',
			'label'       => 'اطلب إيميل الحساب',
			'description' => 'يطلب من العميل إيميل الحساب الذي سيُفعّل عليه الاشتراك.',
		)
	);
	woocommerce_wp_checkbox(
		array(
			'id'          => '_ws_needs_password',
			'value'       => wordsite_needs_password( get_the_ID() ) ? 'yes' : 'no',
			'label'       => 'اطلب كلمة المرور أيضاً',
			'description' => 'تُحفظ مشفّرة، تظهر لك فقط في صفحة الطلب، وتُحذف تلقائياً عند اكتمال الطلب.',
		)
	);
	woocommerce_wp_text_input(
		array(
			'id'          => '_ws_account_name',
			'label'       => 'اسم الخدمة',
			'placeholder' => 'لنكد إن',
			'desc_tip'    => true,
			'description' => 'يظهر في عنوان الحقل، مثل: ايميل لنكد إن',
		)
	);
	echo '</div>';
}
add_action( 'woocommerce_product_options_general_product_data', 'wordsite_product_settings_fields' );

/**
 * Save.
 *
 * @param WC_Product $product Product.
 */
function wordsite_save_product_settings( $product ) {
	// phpcs:disable WordPress.Security.NonceVerification.Missing -- WooCommerce verifies the product nonce.
	$sold = isset( $_POST['_ws_sold_display'] ) ? trim( sanitize_text_field( wp_unslash( $_POST['_ws_sold_display'] ) ) ) : '';
	$product->update_meta_data( '_ws_sold_display', '' === $sold ? '' : (string) absint( $sold ) );
	$product->update_meta_data( '_ws_terms', isset( $_POST['_ws_terms'] ) ? sanitize_textarea_field( wp_unslash( $_POST['_ws_terms'] ) ) : '' );
	$product->update_meta_data( '_ws_needs_account', isset( $_POST['_ws_needs_account'] ) ? 'yes' : 'no' );
	$product->update_meta_data( '_ws_needs_password', isset( $_POST['_ws_needs_password'] ) ? 'yes' : 'no' );
	$product->update_meta_data( '_ws_account_name', isset( $_POST['_ws_account_name'] ) ? sanitize_text_field( wp_unslash( $_POST['_ws_account_name'] ) ) : '' );
	// phpcs:enable
}
add_action( 'woocommerce_admin_process_product_object', 'wordsite_save_product_settings' );
