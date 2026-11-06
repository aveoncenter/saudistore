<?php
/** Homepage category carousel, with real WooCommerce category archive links. */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function wordsite_home_categories() {
	return apply_filters( 'wordsite_home_categories', array(
		'work-productivity' => array( 'name' => 'العمل والتطوير المهني', 'label' => 'العمل والتطوير المهني', 'services' => 'LinkedIn · Zoom · Notion' ),
		'streaming-subscriptions' => array( 'name' => 'الترفيه والمشاهدة', 'label' => 'الترفيه والمشاهدة', 'services' => 'Netflix · Spotify · Shahid · Prime Video' ),
		'apps-subscriptions' => array( 'name' => 'تطبيقات واشتراكات متنوعة', 'label' => 'تطبيقات واشتراكات متنوعة', 'services' => 'تطبيقاتك المفضلة في مكان واحد' ),
		'editing-design' => array( 'name' => 'التصميم والإبداع', 'label' => 'التصميم والإبداع', 'services' => 'Adobe · CapCut · Canva · Figma' ),
		'ai-subscriptions' => array( 'name' => 'الذكاء الاصطناعي', 'label' => 'الذكاء الاصطناعي', 'services' => 'ChatGPT · Gemini · Claude' ),
		'learning-skills' => array( 'name' => 'التعلم وتطوير المهارات', 'label' => 'التعلم وتطوير المهارات', 'services' => 'Duolingo · Udemy · Coursera' ),
		'gift-cards' => array( 'name' => 'بطاقات الهدايا الرقمية', 'label' => 'بطاقات الهدايا الرقمية', 'services' => 'Steam · PlayStation · Apple · Google Play' ),
	) );
}

/**
 * Resolve a category card artwork file, or null when the theme ships none.
 *
 * Tries every supported extension so a missing .webp never leaves a broken
 * <img> on the homepage, and returns the real pixel size for width/height.
 *
 * @param string $key Category key.
 * @return array|null Array with url, width and height.
 */
function wordsite_home_category_art( $key ) {
	$key = sanitize_file_name( $key );
	foreach ( array( 'webp', 'png', 'jpg', 'svg' ) as $extension ) {
		$relative = 'assets/images/home-categories/' . $key . '.' . $extension;
		$path     = get_theme_file_path( $relative );
		if ( ! is_readable( $path ) ) {
			continue;
		}
		$width  = 1130;
		$height = 1392;
		if ( 'svg' !== $extension ) {
			$size = wp_getimagesize( $path );
			if ( $size ) {
				$width  = (int) $size[0];
				$height = (int) $size[1];
			}
		}
		return array(
			'url'    => add_query_arg( 'ver', wp_get_theme()->get( 'Version' ), get_theme_file_uri( $relative ) ),
			'width'  => $width,
			'height' => $height,
		);
	}
	return null;
}

function wordsite_home_category_carousel() {
	if ( ! taxonomy_exists( 'product_cat' ) ) {
		return '';
	}
	$cards = '';
	foreach ( wordsite_home_categories() as $key => $definition ) {
		$term = wordsite_store_menu_term( $key, $definition );
		if ( ! $term ) {
			continue;
		}
		$url = get_term_link( $term, 'product_cat' );
		if ( is_wp_error( $url ) ) {
			continue;
		}
		$art = wordsite_home_category_art( $key );
		if ( $art ) {
			$card = sprintf(
				'<a class="ws-category-card" href="%1$s"><img class="ws-category-card__art" src="%2$s" alt="%3$s" width="%4$d" height="%5$d" loading="lazy" decoding="async"><h3 class="ws-category-card__label">%3$s</h3></a>',
				esc_url( $url ), esc_url( $art['url'] ), esc_attr( $definition['label'] ), $art['width'], $art['height']
			);
		} else {
			/* No artwork in the theme: show a readable text card instead of a broken image. */
			$card = sprintf(
				'<a class="ws-category-card ws-category-card--text" href="%1$s"><h3 class="ws-category-card__title">%2$s</h3><span class="ws-category-card__services">%3$s</span></a>',
				esc_url( $url ), esc_html( $definition['label'] ), esc_html( $definition['services'] )
			);
		}
		$cards .= '<li class="ws-category-slide" dir="rtl">' . $card . '</li>';
	}
	if ( ! $cards ) {
		return '';
	}
	return '<section class="ws-home-categories" data-category-carousel aria-label="أقسام المتجر" aria-roledescription="عارض أقسام"><div class="ws-category-heading"><span aria-hidden="true"></span><h2>تصفح أقسامنا بكل سهولة</h2><span aria-hidden="true"></span></div><div class="ws-category-viewport" dir="ltr" tabindex="0" aria-label="أقسام المتجر: اسحب أو استخدم مفاتيح الأسهم للتنقل"><ul class="ws-category-track">' . $cards . '</ul></div></section>';
}
add_action( 'init', static function () {
	register_block_type( 'saudistore/home-categories', array( 'render_callback' => 'wordsite_home_category_carousel' ) );
} );
add_action( 'wp_enqueue_scripts', static function () {
	if ( ! is_front_page() ) {
		return;
	}
	$ver = wp_get_theme()->get( 'Version' );
	wp_enqueue_style( 'wordsite-home-categories', get_theme_file_uri( 'assets/css/home-categories.css' ), array( 'wordsite-style' ), $ver );
	wp_enqueue_script( 'wordsite-home-categories', get_theme_file_uri( 'assets/js/home-categories.js' ), array(), $ver, true );
} );
