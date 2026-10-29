<?php
/** Homepage category carousel, with real WooCommerce category archive links. */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function wordsite_home_categories() {
	return apply_filters( 'wordsite_home_categories', array(
		'ai-subscriptions' => array( 'name' => 'اشتراكات الذكاء الاصطناعي', 'label' => 'الذكاء الاصطناعي', 'services' => 'ChatGPT · Gemini · Claude' ),
		'learning-skills' => array( 'name' => 'التعلم وتطوير المهارات', 'label' => 'التعلم وتطوير المهارات', 'services' => 'Duolingo · Udemy · Coursera' ),
		'editing-design' => array( 'name' => 'تطبيقات المونتاج والتصاميم', 'label' => 'التصميم والإبداع', 'services' => 'Adobe · Canva · CapCut' ),
		'work-productivity' => array( 'name' => 'العمل والتطوير المهني', 'label' => 'العمل والتطوير المهني', 'services' => 'LinkedIn · Zoom · Notion' ),
		'gift-cards' => array( 'name' => 'بطاقات الهدايا', 'label' => 'بطاقات الهدايا', 'services' => 'Steam · Apple · Google Play' ),
		'apps-subscriptions' => array( 'name' => 'تطبيقات واشتراكات متنوعة', 'label' => 'تطبيقات واشتراكات متنوعة', 'services' => 'تطبيقاتك المفضلة في مكان واحد' ),
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
		$file = 'assets/images/home-categories/' . sanitize_file_name( $key ) . '.svg';
		$image = add_query_arg( 'ver', wp_get_theme()->get( 'Version' ), get_theme_file_uri( $file ) );
		$cards .= sprintf(
			'<li class="ws-category-slide" dir="rtl"><a class="ws-category-card" href="%1$s"><img class="ws-category-card__art" src="%2$s" alt="" width="306" height="424" loading="lazy" decoding="async"><h3 class="ws-category-card__label">%3$s</h3></a></li>',
			esc_url( $url ), esc_url( $image ), esc_html( $definition['label'] )
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
