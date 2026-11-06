<?php
/**
 * Homepage hero: two-banner automatic crossfade carousel.
 *
 * Rendered by the dynamic block `saudistore/home-hero` (templates/front-page.html).
 * Assets: assets/css/home-hero.css, assets/js/home-hero.js,
 * images in assets/images/home-banners/ (AVIF with WebP fallback).
 *
 * @package Wordsite
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Banner slides. Filter `wordsite_home_hero_slides` to change images or links.
 *
 * @return array[]
 */
function wordsite_home_hero_slides() {
	$alt = 'ترقّي: اشتراكات رقمية أصلية بأفضل الأسعار والعروض في السوق السعودي — اشتراكات أصلية وقانونية 100%، تفعيل سريع واستجابة فورية، وسائل دفع آمنة وموثوقة، دعم فني متواصل';
	return apply_filters(
		'wordsite_home_hero_slides',
		array(
			array( 'file' => 'taraqqi-banner-dark', 'alt' => $alt ),
			array( 'file' => 'taraqqi-banner-light', 'alt' => $alt ),
		)
	);
}

/**
 * Render the hero carousel.
 *
 * @return string
 */
function wordsite_home_hero_render() {
	$slides = wordsite_home_hero_slides();
	if ( ! $slides ) {
		return '';
	}

	$ver   = wp_get_theme()->get( 'Version' );
	$dir   = 'assets/images/home-banners/';
	$link  = function_exists( 'wc_get_page_permalink' ) ? wc_get_page_permalink( 'shop' ) : home_url( '/' );
	$link  = apply_filters( 'wordsite_home_hero_link', $link );
	$total = count( $slides );
	$src   = static function ( $file, $width, $ext ) use ( $dir, $ver ) {
		return esc_url( add_query_arg( 'ver', $ver, get_theme_file_uri( $dir . $file . '-' . $width . '.' . $ext ) ) );
	};

	$items = '';
	$dots  = '';
	foreach ( array_values( $slides ) as $i => $slide ) {
		$file   = sanitize_file_name( $slide['file'] );
		$first  = 0 === $i;
		$label  = sprintf( 'الشريحة %1$d من %2$d', $i + 1, $total );
		$sizes  = '(min-width: 1760px) 1680px, (min-width: 1101px) calc(100vw - 80px), (min-width: 601px) calc(100vw - 48px), calc(100vw - 24px)';
		$items .= sprintf(
			'<div class="ws-hero-slide%1$s" role="group" aria-roledescription="شريحة" aria-label="%2$s"%3$s>'
			. '<a class="ws-hero-slide__link" href="%4$s"%5$s>'
			. '<picture>'
			. '<source type="image/avif" srcset="%6$s 1200w, %7$s 2400w" sizes="%8$s">'
			. '<source type="image/webp" srcset="%9$s 1200w, %10$s 2400w" sizes="%8$s">'
			. '<img class="ws-hero-slide__img" src="%10$s" alt="%11$s" width="2400" height="1000" decoding="async" draggable="false"%12$s>'
			. '</picture></a></div>',
			$first ? ' is-active' : '',
			esc_attr( $label ),
			$first ? '' : ' aria-hidden="true" inert',
			esc_url( $link ),
			$first ? '' : ' tabindex="-1"',
			$src( $file, 1200, 'avif' ),
			$src( $file, 2400, 'avif' ),
			esc_attr( $sizes ),
			$src( $file, 1200, 'webp' ),
			$src( $file, 2400, 'webp' ),
			esc_attr( $slide['alt'] ),
			$first ? ' loading="eager" fetchpriority="high"' : ' loading="eager" fetchpriority="low"'
		);
		$dots .= sprintf(
			'<button type="button" class="ws-hero-dot%1$s" data-hero-dot="%2$d" aria-label="%3$s"%4$s><span aria-hidden="true"></span></button>',
			$first ? ' is-active' : '',
			$i,
			esc_attr( sprintf( 'عرض الشريحة %d', $i + 1 ) ),
			$first ? ' aria-current="true"' : ''
		);
	}

	$arrow = '<svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="m9 6 6 6-6 6" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"/></svg>';
	$nav   = $total > 1
		? '<button type="button" class="ws-hero-arrow ws-hero-arrow--prev" data-hero-prev aria-label="الشريحة السابقة">' . $arrow . '</button>'
		. '<button type="button" class="ws-hero-arrow ws-hero-arrow--next" data-hero-next aria-label="الشريحة التالية">' . $arrow . '</button>'
		: '';
	$dots  = $total > 1 ? '<div class="ws-hero-dots" role="group" aria-label="اختيار الشريحة">' . $dots . '</div>' : '';

	return '<section class="ws-hero-carousel" dir="rtl" data-hero-carousel data-interval="3000" aria-roledescription="عارض شرائح" aria-label="عروض ترقّي">'
		. '<h1 class="ws-visually-hidden">ترقّي — اشتراكات رقمية أصلية بأفضل الأسعار والعروض في السوق السعودي</h1>'
		. '<div class="ws-hero-carousel__frame">'
		. '<div class="ws-hero-carousel__viewport" aria-live="off">' . $items . '</div>'
		. $nav
		. '</div>'
		. $dots
		. '</section>';
}

add_action(
	'init',
	static function () {
		register_block_type( 'saudistore/home-hero', array( 'render_callback' => 'wordsite_home_hero_render' ) );
	}
);

add_action(
	'wp_enqueue_scripts',
	static function () {
		if ( ! is_front_page() ) {
			return;
		}
		$ver = wp_get_theme()->get( 'Version' );
		wp_enqueue_style( 'wordsite-home-hero', get_theme_file_uri( 'assets/css/home-hero.css' ), array( 'wordsite-style' ), $ver );
		wp_enqueue_script( 'wordsite-home-hero', get_theme_file_uri( 'assets/js/home-hero.js' ), array(), $ver, array( 'in_footer' => true, 'strategy' => 'defer' ) );
	}
);
