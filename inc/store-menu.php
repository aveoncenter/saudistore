<?php
/**
 * Product-category cards inside the existing WordPress navigation overlay.
 * Core navigation retains its keyboard handling, focus trap, and close controls.
 *
 * @package Wordsite
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Stable category keys, default names, and presentation.
 *
 * @return array
 */
function wordsite_store_menu_categories() {
	return apply_filters(
		'wordsite_store_menu_categories',
		array(
			'ai-subscriptions' => array(
				'name' => 'اشتراكات الذكاء الاصطناعي',
				'note' => 'ChatGPT · Gemini · Manus',
				'icon' => 'spark',
			),
			'social-premium' => array(
				'name' => 'سناب بلس وتويتر بريميوم الرسمي',
				'note' => 'Snapchat+ · X Premium',
				'icon' => 'social',
			),
			'streaming-subscriptions' => array(
				'name' => 'اشتراكات برامج الأفلام والمسلسلات',
				'note' => 'عالم الأفلام والمسلسلات',
				'icon' => 'film',
			),
			'editing-design' => array(
				'name' => 'تطبيقات المونتاج والتصاميم',
				'note' => 'أدوات للإبداع وصناعة المحتوى',
				'icon' => 'design',
			),
			'linkedin-subscriptions' => array(
				'name' => 'اشتراكات لينكد إن LinkedIn',
				'note' => 'باقات لحياتك المهنية',
				'icon' => 'briefcase',
			),
		)
	);
}

/**
 * Resolve saved IDs first so editing category names or slugs does not break links.
 *
 * @param string $key Stable category key.
 * @param array  $definition Default category settings.
 * @return WP_Term|false
 */
function wordsite_store_menu_term( $key, $definition ) {
	$ids  = get_option( 'wordsite_store_category_ids', array() );
	$term = ! empty( $ids[ $key ] ) ? get_term( (int) $ids[ $key ], 'product_cat' ) : false;
	if ( ! $term || is_wp_error( $term ) ) {
		$term = get_term_by( 'slug', $key, 'product_cat' );
	}
	if ( ! $term ) {
		$term = get_term_by( 'name', $definition['name'], 'product_cat' );
	}
	return $term && ! is_wp_error( $term ) ? $term : false;
}

/**
 * One-time category setup for both new installs and updates of an active theme.
 * Reuse matching terms, never overwrite them, and never reassign live products.
 */
function wordsite_ensure_store_categories() {
	if ( ! taxonomy_exists( 'product_cat' ) || '1' === get_option( 'wordsite_store_categories_version' ) ) {
		return;
	}
	$ids      = get_option( 'wordsite_store_category_ids', array() );
	$complete = true;
	foreach ( wordsite_store_menu_categories() as $key => $definition ) {
		$term = wordsite_store_menu_term( $key, $definition );
		if ( $term ) {
			$ids[ $key ] = (int) $term->term_id;
			continue;
		}
		$created = wp_insert_term( $definition['name'], 'product_cat', array( 'slug' => $key ) );
		if ( is_wp_error( $created ) ) {
			$complete = false;
		} else {
			$ids[ $key ] = (int) $created['term_id'];
		}
	}
	update_option( 'wordsite_store_category_ids', $ids, false );
	if ( $complete ) {
		update_option( 'wordsite_store_categories_version', '1', false );
	}
}
add_action( 'init', 'wordsite_ensure_store_categories', 20 );

/**
 * Small decorative line icons, not brand logos.
 *
 * @param string $icon Icon key.
 * @return string
 */
function wordsite_store_menu_icon( $icon ) {
	$paths = array(
		'spark'     => '<path d="m12 3 2.4 6.6L21 12l-6.6 2.4L12 21l-2.4-6.6L3 12l6.6-2.4L12 3Z"/><path d="M20 2v4m-2-2h4"/>',
		'social'    => '<path d="M21 11.5a8.5 8.5 0 0 1-8.5 8.5H4l-2 2v-9.5A8.5 8.5 0 0 1 10.5 4H13"/><path d="m17 3 1.2 3.8L22 8l-3.8 1.2L17 13l-1.2-3.8L12 8l3.8-1.2L17 3Z"/>',
		'film'      => '<rect x="3" y="7" width="18" height="14" rx="2"/><path d="m3 7 17-4 1 4M7 4l3 3m4-5 3 4m-7 5 5 3-5 3v-6Z"/>',
		'design'    => '<path d="m16 3 5 5-12 12-6 1 1-6L16 3Zm-3 3 5 5M4 15l5 5"/><path d="M3 4h5M5.5 1.5v5"/>',
		'briefcase' => '<rect x="3" y="7" width="18" height="14" rx="2"/><path d="M8 7V4a1 1 0 0 1 1-1h6a1 1 0 0 1 1 1v3M3 12a22 22 0 0 0 18 0M10 12v3h4v-3"/>',
	);
	return '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' . ( $paths[ $icon ] ?? $paths['spark'] ) . '</svg>';
}

/**
 * Build category cards with real WooCommerce archive URLs, including empty categories.
 *
 * @return string
 */
function wordsite_store_menu_cards() {
	if ( ! taxonomy_exists( 'product_cat' ) ) {
		return '';
	}
	$cards = '';
	foreach ( wordsite_store_menu_categories() as $key => $definition ) {
		$term = wordsite_store_menu_term( $key, $definition );
		if ( ! $term ) {
			continue;
		}
		$url = get_term_link( $term, 'product_cat' );
		if ( is_wp_error( $url ) ) {
			continue;
		}
		$current = is_tax( 'product_cat', $term->term_id );
		$cards  .= sprintf(
			'<li><a class="ws-menu-category%1$s" href="%2$s"%3$s><span class="ws-menu-category__icon">%4$s</span><span class="ws-menu-category__copy"><strong>%5$s</strong><span>%6$s</span></span><svg class="ws-menu-category__arrow" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true"><path d="m14 6-6 6 6 6"/></svg></a></li>',
			$current ? ' is-current' : '',
			esc_url( $url ),
			$current ? ' aria-current="page"' : '',
			wordsite_store_menu_icon( $definition['icon'] ),
			esc_html( $term->name ),
			esc_html( $definition['note'] )
		);
	}
	if ( ! $cards ) {
		return '';
	}
	return '<div class="ws-menu-categories"><div class="ws-menu-intro"><span class="ws-menu-eyebrow">تسوق حسب الفئة</span><h2>وش ودك تشترك فيه؟</h2><p>كل اشتراكاتك، في مكان واحد.</p></div><ul class="ws-menu-category-list">' . $cards . '</ul></div><p class="ws-menu-quick-title">روابط سريعة</p>';
}

/**
 * Insert content into our existing core navigation overlay only.
 * Keeping the core block avoids replacing its accessibility and editor features.
 *
 * @param string $content Rendered navigation HTML.
 * @param array  $block Parsed block.
 * @return string
 */
function wordsite_render_store_menu( $content, $block ) {
	$classes = $block['attrs']['className'] ?? '';
	if ( ! preg_match( '/(?:^|\s)ws-nav(?:\s|$)/', $classes ) || false !== strpos( $content, 'ws-menu-categories' ) ) {
		return $content;
	}
	$cards = wordsite_store_menu_cards();
	if ( ! $cards ) {
		return $content;
	}
	$result = preg_replace_callback(
		'/(<div\b[^>]*\bclass="[^"]*\bwp-block-navigation__responsive-container-content\b[^"]*"[^>]*>)/',
		static function ( $match ) use ( $cards ) {
			return $match[1] . $cards;
		},
		$content,
		1
	);
	return null !== $result ? $result : $content;
}
add_filter( 'render_block_core/navigation', 'wordsite_render_store_menu', 10, 2 );