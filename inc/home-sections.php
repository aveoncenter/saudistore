<?php
/**
 * Homepage feature icons, moving customer reviews and FAQ accordion.
 *
 * Dynamic blocks used by templates/front-page.html:
 * - saudistore/home-features  (four animated feature icons; replaces the old benefit cards)
 * - saudistore/home-reviews   (continuous review carousel)
 * - saudistore/home-faq       (six-question accordion, below the WhatsApp CTA)
 *
 * Content is filterable so it can be edited without touching markup:
 * `wordsite_home_features`, `wordsite_home_reviews`, `wordsite_home_reviews_are_samples`,
 * `wordsite_home_faq_items`. Assets: assets/css/home-sections.css, assets/js/home-sections.js
 * (front page only, no libraries).
 *
 * @package Wordsite
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/* ---------------------------------------------------------------------------
 * Feature icons
 * ------------------------------------------------------------------------- */

/**
 * Inline SVG icons for the feature section (white strokes on a gradient orb).
 *
 * @param string $name Icon key.
 * @return string
 */
function wordsite_home_feature_icon( $name ) {
	$icons = array(
		'layers'  => '<path d="m12 2.8 8.6 4.6L12 12 3.4 7.4z" fill="currentColor" fill-opacity=".22"/><path d="m12 2.8 8.6 4.6L12 12 3.4 7.4z"/><path d="m3.4 12 8.6 4.6 8.6-4.6"/><path d="m3.4 16.6 8.6 4.6 8.6-4.6"/>',
		'bolt'    => '<path d="M13.2 2.5 4.6 13.4h6.6l-1 8.1 8.6-10.9h-6.6z" fill="currentColor" fill-opacity=".22"/><path d="M13.2 2.5 4.6 13.4h6.6l-1 8.1 8.6-10.9h-6.6z"/>',
		'card'    => '<rect x="2.2" y="4.8" width="15.6" height="11.4" rx="2.2" fill="currentColor" fill-opacity=".18"/><rect x="2.2" y="4.8" width="15.6" height="11.4" rx="2.2"/><path d="M2.2 8.8h15.6M5.4 12.8h3.2"/><path d="m17.9 11.6 3.9 1.5v2.8c0 2.5-1.6 4.2-3.9 5-2.3-.8-3.9-2.5-3.9-5v-2.8z" fill="currentColor" fill-opacity=".9" stroke="none"/><path d="m17.9 11.6 3.9 1.5v2.8c0 2.5-1.6 4.2-3.9 5-2.3-.8-3.9-2.5-3.9-5v-2.8z"/><path class="tq-feature__tick" d="m16.4 16.2 1.1 1.1 2-2.2"/>',
		'headset' => '<path d="M4.2 13.6v-1.8a7.8 7.8 0 0 1 15.6 0v1.8"/><rect x="2.6" y="12.6" width="4.4" height="6.6" rx="1.8" fill="currentColor" fill-opacity=".22"/><rect x="2.6" y="12.6" width="4.4" height="6.6" rx="1.8"/><rect x="17" y="12.6" width="4.4" height="6.6" rx="1.8" fill="currentColor" fill-opacity=".22"/><rect x="17" y="12.6" width="4.4" height="6.6" rx="1.8"/><path d="M19.2 19.2v.4a2.4 2.4 0 0 1-2.4 2.4h-2.6"/><circle cx="12.9" cy="22" r="1.1" fill="currentColor" stroke="none"/>',
	);
	$paths = isset( $icons[ $name ] ) ? $icons[ $name ] : $icons['layers'];
	return '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">' . $paths . '</svg>';
}

/**
 * Feature definitions. Filter `wordsite_home_features` to edit the copy.
 *
 * @return array[]
 */
function wordsite_home_features() {
	return apply_filters(
		'wordsite_home_features',
		array(
			array(
				'icon'  => 'layers',
				'tone'  => 'cyan',
				'title' => 'الاشتراكات الرقمية الأصلية',
				'text'  => 'وصول إلى مجموعة متنوعة من الخدمات والاشتراكات الرقمية.',
			),
			array(
				'icon'  => 'bolt',
				'tone'  => 'violet',
				'title' => 'تفعيل سريع',
				'text'  => 'تنفيذ طلبك بأسرع وقت حسب نوع المنتج.',
			),
			array(
				'icon'  => 'card',
				'tone'  => 'blue',
				'title' => 'دفع آمن ومتعدد',
				'text'  => 'خيارات دفع متنوعة تناسب احتياجاتك.',
			),
			array(
				'icon'  => 'headset',
				'tone'  => 'aurora',
				'title' => 'دعم فني متواصل',
				'text'  => 'نساعدك ونرد على استفساراتك عبر قنوات الدعم.',
			),
		)
	);
}

/**
 * Render the four-icon feature row.
 *
 * @return string
 */
function wordsite_home_features_render() {
	$features = wordsite_home_features();
	if ( ! $features ) {
		return '';
	}
	$items = '';
	foreach ( array_values( $features ) as $i => $feature ) {
		$tone   = sanitize_html_class( isset( $feature['tone'] ) ? $feature['tone'] : 'cyan' );
		$items .= sprintf(
			'<li class="tq-feature tq-feature--%1$s" style="--tq-i:%2$d">'
			. '<span class="tq-feature__icon" aria-hidden="true"><span class="tq-feature__orb">%3$s</span></span>'
			. '<h3 class="tq-feature__title">%4$s</h3>'
			. '<p class="tq-feature__text">%5$s</p>'
			. '</li>',
			esc_attr( $tone ),
			(int) $i,
			wordsite_home_feature_icon( isset( $feature['icon'] ) ? $feature['icon'] : '' ),
			esc_html( $feature['title'] ),
			esc_html( $feature['text'] )
		);
	}
	return '<section class="tq-features" data-tq-features aria-labelledby="tq-features-title">'
		. '<h2 id="tq-features-title" class="tq-sr-only">لماذا ترقّي؟</h2>'
		. '<ul class="tq-features__list" role="list">' . $items . '</ul>'
		. '</section>';
}

/* ---------------------------------------------------------------------------
 * Customer reviews
 * ------------------------------------------------------------------------- */

/**
 * Whether the reviews below are illustrative samples (not genuine testimonials).
 * Return false from `wordsite_home_reviews_are_samples` once real reviews are supplied.
 *
 * @return bool
 */
function wordsite_home_reviews_are_samples() {
	return (bool) apply_filters( 'wordsite_home_reviews_are_samples', true );
}

/**
 * Review items. Each item: `text` (required) and optional `author`, `product`, `rating` (1–5).
 * Optional fields are only displayed when supplied, so genuine reviews can replace the
 * samples through the `wordsite_home_reviews` filter without changing the markup.
 *
 * SAMPLE CONTENT: the ten default texts are illustrative demo copy for the initial visual
 * implementation. They are not customer testimonials and carry no names or ratings.
 *
 * @return array[]
 */
function wordsite_home_reviews() {
	$samples = array(
		'واجهة المتجر واضحة وسهلة، لقيت اللي أبيه بسرعة.',
		'خيارات الاشتراكات متنوعة والأسعار معروضة بشكل واضح.',
		'طريقة الطلب سهلة والخطوات مفهومة.',
		'عجبني إن تفاصيل كل باقة واضحة قبل الشراء.',
		'التصفح بالجوال مريح والتصميم مرتب.',
		'وسائل الدفع واضحة وهذا يسهل اختيار الطريقة المناسبة.',
		'التصنيفات مرتبة وسهل أوصل للقسم اللي أبيه.',
		'تفاصيل المنتجات تساعدك تعرف وش يناسب احتياجك.',
		'التواصل مع الدعم واضح وسهل الوصول له.',
		'التجربة بشكل عام مرتبة والتصفح سهل.',
	);
	$items = array();
	foreach ( $samples as $text ) {
		$items[] = array( 'text' => $text );
	}
	return apply_filters( 'wordsite_home_reviews', $items );
}

/**
 * Markup for one review card.
 *
 * @param array $review Review data.
 * @return string
 */
function wordsite_home_review_card( $review ) {
	$quote = '<svg class="tq-review__quote" viewBox="0 0 32 24" aria-hidden="true" focusable="false"><path d="M13.6 0C6.2 1.6 1 7 1 14.2 1 19.6 4.3 24 9 24c3.6 0 6.2-2.7 6.2-6.1 0-3.3-2.4-5.8-5.6-5.8-.7 0-1.3.1-1.8.3.9-3.6 3.9-6.6 7.7-8z"/><path d="M30.4 0C23 1.6 17.8 7 17.8 14.2c0 5.4 3.3 9.8 8 9.8 3.6 0 6.2-2.7 6.2-6.1 0-3.3-2.4-5.8-5.6-5.8-.7 0-1.3.1-1.8.3.9-3.6 3.9-6.6 7.7-8z"/></svg>';
	$meta  = '';
	if ( ! empty( $review['rating'] ) ) {
		$rating = max( 1, min( 5, (int) $review['rating'] ) );
		$meta  .= sprintf(
			'<span class="tq-review__stars" role="img" aria-label="%1$s">%2$s</span>',
			esc_attr( sprintf( 'التقييم %d من 5', $rating ) ),
			str_repeat( '★', $rating ) . '<span class="tq-review__stars-off">' . str_repeat( '★', 5 - $rating ) . '</span>'
		);
	}
	if ( ! empty( $review['author'] ) ) {
		$meta .= '<span class="tq-review__author">' . esc_html( $review['author'] ) . '</span>';
	}
	if ( ! empty( $review['product'] ) ) {
		$meta .= '<span class="tq-review__product">' . esc_html( $review['product'] ) . '</span>';
	}
	return '<li class="tq-review"><figure>'
		. $quote
		. '<blockquote class="tq-review__text"><p>' . esc_html( $review['text'] ) . '</p></blockquote>'
		. ( $meta ? '<figcaption class="tq-review__meta">' . $meta . '</figcaption>' : '' )
		. '<span class="tq-review__accent" aria-hidden="true"></span>'
		. '</figure></li>';
}

/**
 * Render the moving reviews section.
 *
 * @return string
 */
function wordsite_home_reviews_render() {
	$reviews = array_values(
		array_filter(
			(array) wordsite_home_reviews(),
			static function ( $review ) {
				return is_array( $review ) && ! empty( $review['text'] );
			}
		)
	);
	if ( ! $reviews ) {
		return '';
	}
	$cards = implode( '', array_map( 'wordsite_home_review_card', $reviews ) );
	$star   = '<svg class="tq-reviews__star" viewBox="0 0 24 24" aria-hidden="true" focusable="false"><defs><linearGradient id="tq-star-g" x1="0" y1="0" x2="1" y2="1"><stop offset="0" stop-color="#38bdf8"/><stop offset=".55" stop-color="#8b5cf6"/><stop offset="1" stop-color="#c084fc"/></linearGradient></defs><path fill="url(#tq-star-g)" d="m12 1.8 2.9 6.3 6.9.8-5.1 4.7 1.4 6.8L12 17l-6.1 3.4 1.4-6.8L2.2 8.9l6.9-.8z"/><path fill="#fff" fill-opacity=".55" d="m12 4.6 1.9 4.2-1.9 6.2z"/></svg>';
	$pause  = '<svg class="tq-reviews__icon-pause" viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M8 5v14M16 5v14"/></svg><svg class="tq-reviews__icon-play" viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M8 5.5v13l10-6.5z"/></svg>';

	return '<section class="tq-reviews" data-tq-reviews aria-labelledby="tq-reviews-title">'
		. '<div class="tq-reviews__head">'
		. '<span class="tq-reviews__line" aria-hidden="true"></span>'
		. '<h2 id="tq-reviews-title" class="tq-reviews__title">تجارب العملاء' . $star . '</h2>'
		. '<span class="tq-reviews__line" aria-hidden="true"></span>'
		. '<button type="button" class="tq-reviews__toggle" data-tq-reviews-toggle aria-pressed="false" aria-label="إيقاف حركة التقييمات" hidden>' . $pause . '</button>'
		. '</div>'
		. '<div class="tq-reviews__viewport" data-tq-reviews-viewport>'
		. '<div class="tq-reviews__track" data-tq-reviews-track>'
		. '<ul class="tq-reviews__group" role="list">' . $cards . '</ul>'
		. '<ul class="tq-reviews__group tq-reviews__group--clone" role="list" aria-hidden="true">' . $cards . '</ul>'
		. '</div></div>'
		. '</section>';
}

/* ---------------------------------------------------------------------------
 * FAQ
 * ------------------------------------------------------------------------- */

/**
 * Store page URLs used in FAQ answers.
 *
 * @return array<string,string>
 */
function wordsite_home_faq_links() {
	$refund = get_page_by_path( 'refund_returns' );
	$refund = ( $refund && 'publish' === $refund->post_status ) ? get_permalink( $refund ) : home_url( '/refund_returns/' );
	$privacy = get_privacy_policy_url();
	return apply_filters(
		'wordsite_home_faq_links',
		array(
			'{refund_url}'  => $refund,
			'{privacy_url}' => $privacy ? $privacy : home_url( '/privacy-policy/' ),
			'{support_url}' => home_url( '/?whatsapp' ),
		)
	);
}

/**
 * FAQ items. Filter `wordsite_home_faq_items` to edit questions/answers.
 * Answers accept basic HTML; {refund_url}, {privacy_url} and {support_url} are replaced
 * with the live store URLs. Keep answers aligned with the store's actual policies.
 *
 * @return array[]
 */
function wordsite_home_faq_items() {
	return apply_filters(
		'wordsite_home_faq_items',
		array(
			array(
				'q' => 'ما هي طرق الدفع المتاحة؟',
				'a' => 'نوفر عدة طرق دفع حسب الخيارات المتاحة أثناء إتمام الطلب، لتختار الطريقة الأنسب لك بكل سهولة.',
			),
			array(
				'q' => 'متى يتم تفعيل طلبي؟',
				'a' => 'تختلف مدة تنفيذ الطلب حسب نوع المنتج. بعض المنتجات يتم تسليمها بسرعة، بينما قد تتطلب المنتجات الأخرى وقتاً إضافياً للتفعيل.',
			),
			array(
				'q' => 'كيف أستلم طلبي؟',
				'a' => 'تختلف طريقة الاستلام حسب نوع المنتج. ستظهر لك تفاصيل المنتج والتعليمات اللازمة أثناء إتمام عملية الشراء.',
			),
			array(
				'q' => 'هل جميع المنتجات تحتاج إلى بيانات حساب؟',
				'a' => 'لا، بعض المنتجات لا تحتاج إلى أي بيانات إضافية، بينما تتطلب منتجات أخرى معلومات محددة لإتمام التفعيل. ستظهر الحقول المطلوبة حسب نوع المنتج. للاطلاع على كيفية التعامل مع بياناتك راجع <a href="{privacy_url}">سياسة الخصوصية</a>.',
			),
			array(
				'q' => 'كيف أتواصل مع الدعم الفني؟',
				'a' => 'يمكنك التواصل معنا عبر <a href="{support_url}">واتساب</a> من خلال زر الدعم الموجود في المتجر، وسنساعدك في استفساراتك.',
			),
			array(
				'q' => 'هل يمكنني استرجاع المبلغ؟',
				'a' => 'تخضع عمليات الاسترجاع لسياسة الاسترجاع الخاصة بالمتجر. يمكنك الاطلاع على <a href="{refund_url}">السياسة</a> لمعرفة الشروط والتفاصيل.',
			),
		)
	);
}

/**
 * Render the FAQ accordion (native <details>, enhanced with a smooth height animation).
 *
 * @return string
 */
function wordsite_home_faq_render() {
	$items = array_values(
		array_filter(
			(array) wordsite_home_faq_items(),
			static function ( $item ) {
				return is_array( $item ) && ! empty( $item['q'] ) && ! empty( $item['a'] );
			}
		)
	);
	if ( ! $items ) {
		return '';
	}
	$links  = wordsite_home_faq_links();
	$html   = '';
	$schema = array();
	foreach ( $items as $i => $item ) {
		$answer   = wp_kses_post( strtr( $item['a'], array_map( 'esc_url', $links ) ) );
		$id       = 'tq-faq-' . ( $i + 1 );
		$html    .= sprintf(
			'<details class="tq-faq__item" id="%1$s"><summary class="tq-faq__q"><span class="tq-faq__q-text">%2$s</span><span class="tq-faq__icon" aria-hidden="true"></span></summary><div class="tq-faq__a"><div class="tq-faq__a-inner"><p>%3$s</p></div></div></details>',
			esc_attr( $id ),
			esc_html( $item['q'] ),
			$answer
		);
		$schema[] = array(
			'@type'          => 'Question',
			'name'           => wp_strip_all_tags( $item['q'] ),
			'acceptedAnswer' => array(
				'@type' => 'Answer',
				'text'  => wp_strip_all_tags( $answer ),
			),
		);
	}
	$json = wp_json_encode(
		array(
			'@context'   => 'https://schema.org',
			'@type'      => 'FAQPage',
			'mainEntity' => $schema,
		),
		JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG
	);
	return '<section class="tq-faq" data-tq-faq aria-labelledby="tq-faq-title">'
		. '<h2 id="tq-faq-title" class="tq-faq__title">الأسئلة الشائعة</h2>'
		. '<div class="tq-faq__grid">' . $html . '</div>'
		. '<script type="application/ld+json">' . $json . '</script>'
		. '</section>';
}

/* ---------------------------------------------------------------------------
 * Registration and assets
 * ------------------------------------------------------------------------- */

add_action(
	'init',
	static function () {
		register_block_type( 'saudistore/home-features', array( 'render_callback' => 'wordsite_home_features_render' ) );
		register_block_type( 'saudistore/home-reviews', array( 'render_callback' => 'wordsite_home_reviews_render' ) );
		register_block_type( 'saudistore/home-faq', array( 'render_callback' => 'wordsite_home_faq_render' ) );
	}
);

add_action(
	'wp_enqueue_scripts',
	static function () {
		if ( ! is_front_page() ) {
			return;
		}
		$ver = wp_get_theme()->get( 'Version' );
		wp_enqueue_style( 'wordsite-home-sections', get_theme_file_uri( 'assets/css/home-sections.css' ), array( 'wordsite-style' ), $ver );
		wp_enqueue_script( 'wordsite-home-sections', get_theme_file_uri( 'assets/js/home-sections.js' ), array(), $ver, true );
	}
);
