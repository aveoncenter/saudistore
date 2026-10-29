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
		$image = get_theme_file_uri( $file );
		$cards .= sprintf(
			'<li class="ws-category-slide" dir="rtl"><a class="ws-category-card" href="%1$s"><img class="ws-category-card__art" src="%2$s" alt="" width="305" height="225" loading="lazy" decoding="async"><div class="ws-category-card__copy"><span class="ws-category-card__eyebrow">%3$s</span><h3>%4$s</h3><span class="ws-category-card__services" dir="%5$s">%6$s</span><span class="ws-category-card__link">تصفح القسم <span aria-hidden="true">←</span></span></div></a></li>',
			esc_url( $url ), esc_url( $image ),
			'gift-cards' === $key ? 'Gift Cards' : 'اشتراكات',
			esc_html( $definition['label'] ), 'apps-subscriptions' === $key ? 'rtl' : 'ltr', esc_html( $definition['services'] )
		);
	}
	if ( ! $cards ) {
		return '';
	}
	return '<section class="ws-home-categories" data-category-carousel aria-label="أقسام المتجر" aria-roledescription="عارض أقسام"><div class="ws-category-heading"><span aria-hidden="true"></span><h2>تسوق حسب الفئة</h2><span aria-hidden="true"></span></div><div class="ws-category-viewport" dir="ltr"><ul class="ws-category-track">' . $cards . '</ul></div><div class="ws-category-controls" hidden><button type="button" data-category-prev aria-label="تحريك البطاقات إلى اليمين">→</button><span class="ws-category-position" dir="ltr" aria-hidden="true"></span><button type="button" data-category-next aria-label="تحريك البطاقات إلى اليسار">←</button><button type="button" class="ws-category-pause" data-category-pause aria-pressed="false">إيقاف الحركة</button></div></section>';
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
