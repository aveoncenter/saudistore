<?php
/** Mobile navigation uses WooCommerce's configured pages and the existing core drawer. */
if ( ! defined( 'ABSPATH' ) ) { exit; }
function wordsite_mobile_navigation() {
 if ( ! function_exists( 'wc_get_page_permalink' ) ) { return; }
 $count = wordsite_cart_total_data()['count'];
 $cards = wordsite_store_menu_cards();
 $items = array(
  array( 'home', home_url( '/' ), 'الرئيسية', '<path d="M3 10.5 12 3l9 7.5V21h-6v-6H9v6H3z"/>', is_front_page() ),
  array( 'categories', '', 'التصنيفات', '<rect x="3" y="3" width="7" height="7" rx="1.5"/><rect x="14" y="3" width="7" height="7" rx="1.5"/><rect x="3" y="14" width="7" height="7" rx="1.5"/><rect x="14" y="14" width="7" height="7" rx="1.5"/>', is_product_category() || is_shop() ),
  array( 'cart', wc_get_cart_url(), 'السلة', '<path d="M3 4h2l2 11h11l3-8H6"/><circle cx="9" cy="20" r="1"/><circle cx="18" cy="20" r="1"/>', is_cart() ),
  array( 'account', wc_get_page_permalink( 'myaccount' ), is_user_logged_in() ? 'حسابي' : 'تسجيل الدخول', '<circle cx="12" cy="8" r="4"/><path d="M4 21v-1a8 8 0 0 1 16 0v1"/>', is_account_page() ),
 );
 echo '<nav class="ws-mobile-nav" aria-label="التنقل الرئيسي" dir="rtl">';
 foreach ( $items as $item ) {
  list( $key, $url, $label, $icon, $active ) = $item;
  $tag = 'a';
  $attr = 'categories' === $key ? 'href="' . esc_url( wc_get_page_permalink( 'shop' ) ) . '" data-ws-mobile-categories' . ( $cards ? ' aria-haspopup="dialog" aria-controls="ws-mobile-category-dialog" aria-expanded="false"' : '' ) : 'href="' . esc_url( $url ) . '"';
  echo '<' . $tag . ' class="ws-mobile-nav__item ws-mobile-nav__item--' . esc_attr( $key ) . ( $active ? ' is-active' : '' ) . '" ' . $attr . ( $active && 'categories' !== $key ? ' aria-current="page"' : '' ) . '>';
  echo '<span class="ws-mobile-nav__icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' . $icon . '</svg>';
  if ( 'cart' === $key ) { echo '<span class="ws-mobile-nav__badge" data-ws-mobile-cart-count aria-label="' . esc_attr( sprintf( '%d منتجات في السلة', $count ) ) . '">' . esc_html( $count ) . '</span>'; }
  echo '</span><span class="ws-mobile-nav__label">' . esc_html( $label ) . '</span></' . $tag . '>';
 }
 echo '</nav>';
 /* Reuse the exact same category rows, archive URLs and thumbnails as the header. */
 if ( $cards ) {
  echo '<dialog id="ws-mobile-category-dialog" class="ws-mobile-category-dialog" aria-label="القائمة الرئيسية">';
  echo '<div class="ws-mobile-category-dialog__sheet" dir="rtl">';
  echo '<button type="button" class="ws-mobile-category-dialog__close" data-ws-category-close aria-label="إغلاق القائمة"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><path d="M5 5l14 14M19 5 5 19"/></svg></button>';
  echo $cards; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped by the shared renderer.
  echo '</div></dialog>';
 }
}
/* Render before WordPress prints footer scripts. */
add_action( 'wp_footer', 'wordsite_mobile_navigation', 15 );
add_action( 'wp_enqueue_scripts', static function () {
 $ver = wp_get_theme()->get( 'Version' );
 wp_enqueue_style( 'wordsite-mobile', get_theme_file_uri( 'assets/css/mobile.css' ), array( 'wordsite-style', 'wordsite-menu' ), $ver );
 wp_enqueue_script( 'wordsite-mobile', get_theme_file_uri( 'assets/js/mobile.js' ), array( 'wordsite-header', 'wordsite-cart-total' ), $ver, true );
}, 110 );
