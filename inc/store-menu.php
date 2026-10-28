<?php
/**
 * Reference-style product-category drawer in the WordPress navigation overlay.
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
			),
			'social-premium' => array(
				'name' => 'سناب بلس وتويتر بريميوم الرسمي',
			),
			'streaming-subscriptions' => array(
				'name' => 'اشتراكات برامج الأفلام والمسلسلات',
			),
			'editing-design' => array(
				'name' => 'تطبيقات المونتاج والتصاميم',
			),
			'linkedin-subscriptions' => array(
				'name' => 'اشتراكات لينكد إن LinkedIn',
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
 * Prefer the category's uploaded thumbnail; otherwise use the supplied reference.
 *
 * @param WP_Term $term Category term.
 * @param string  $key Stable category key.
 * @return string
 */
function wordsite_store_menu_thumbnail( $term, $key ) {
	$thumbnail_id = (int) get_term_meta( $term->term_id, 'thumbnail_id', true );
	if ( $thumbnail_id ) {
		$image = wp_get_attachment_image( $thumbnail_id, 'woocommerce_thumbnail', false, array( 'class' => 'ws-menu-category__image', 'alt' => '', 'loading' => 'eager' ) );
		if ( $image ) {
			return $image;
		}
	}
	$file = 'assets/images/categories/' . sanitize_file_name( $key ) . '.svg';
	if ( ! file_exists( get_theme_file_path( $file ) ) ) {
		return '';
	}
	return sprintf( '<img class="ws-menu-category__image" src="%s" width="75" height="75" alt="" decoding="async">', esc_url( get_theme_file_uri( $file ) ) );
}

/**
 * Build category rows with real WooCommerce archive URLs, including empty categories.
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
		$label   = str_replace( 'LinkedIn', '<bdi class="ws-menu-category__latin">LinkedIn</bdi>', esc_html( $term->name ) );
		if ( 'streaming-subscriptions' === $key ) {
			$label = str_replace( ' والمسلسلات', '<br>والمسلسلات', $label );
		}
		$cards  .= sprintf(
			'<li><a class="ws-menu-category%1$s" href="%2$s"%3$s>%4$s<strong>%5$s</strong></a></li>',
			$current ? ' is-current' : '',
			esc_url( $url ),
			$current ? ' aria-current="page"' : '',
			wordsite_store_menu_thumbnail( $term, $key ),
			$label
		);
	}
	if ( ! $cards ) {
		return '';
	}
	return '<div class="ws-menu-categories"><div class="ws-menu-heading"><h2>القائمة الرئيسية</h2></div><ul class="ws-menu-category-list">' . $cards . '</ul></div>';
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