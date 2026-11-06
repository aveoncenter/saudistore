<?php
/** Homepage category carousel, with real WooCommerce category archive links. */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function wordsite_home_categories() {
	return apply_filters( 'wordsite_home_categories', array(
		'entertainment' => array( 'name' => 'الترفيه والمشاهدة', 'label' => 'الترفيه والمشاهدة', 'art_index' => 0 ),
		'apps-subscriptions' => array( 'name' => 'تطبيقات واشتراكات متنوعة', 'label' => 'تطبيقات واشتراكات متنوعة', 'art_index' => 1 ),
		'work-productivity' => array( 'name' => 'العمل والتطوير المهني', 'label' => 'العمل والتطوير المهني', 'art_index' => 2 ),
		'editing-design' => array( 'name' => 'تطبيقات المونتاج والتصاميم', 'label' => 'التصميم والإبداع', 'art_index' => 3 ),
		'ai-subscriptions' => array( 'name' => 'اشتراكات الذكاء الاصطناعي', 'label' => 'الذكاء الاصطناعي', 'art_index' => 4 ),
		'learning-skills' => array( 'name' => 'التعلم وتطوير المهارات', 'label' => 'التعلم وتطوير المهارات', 'art_index' => 5 ),
		'gift-cards' => array( 'name' => 'بطاقات الهدايا', 'label' => 'بطاقات الهدايا', 'art_index' => 6 ),
	) );
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
		$art_index = isset( $definition['art_index'] ) ? absint( $definition['art_index'] ) : 0;
		$cards .= sprintf(
			'<li class="ws-category-slide" dir="rtl"><a class="ws-category-card" href="%1$s"><span class="ws-category-card__art ws-category-card__art--%2$d" aria-hidden="true"></span><h3 class="ws-category-card__label">%3$s</h3></a></li>',
			esc_url( $url ), $art_index, esc_html( $definition['label'] )
		);
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
