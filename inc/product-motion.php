<?php
/** Shared, progressive product-card motion for custom cards and WooCommerce loops. */
if ( ! defined( 'ABSPATH' ) ) {
 exit;
}
add_action( 'wp_enqueue_scripts', static function () {
 $ver = wp_get_theme()->get( 'Version' );
 $dependencies = array( 'wordsite-style' );
 foreach ( array( 'wordsite-product', 'wordsite-home-products' ) as $handle ) {
  if ( wp_style_is( $handle, 'enqueued' ) ) {
   $dependencies[] = $handle;
  }
 }
 wp_enqueue_style( 'wordsite-product-motion', get_theme_file_uri( 'assets/css/product-motion.css' ), $dependencies, $ver );
 wp_enqueue_script( 'wordsite-product-motion', get_theme_file_uri( 'assets/js/product-motion.js' ), array(), $ver, true );
 wp_script_add_data( 'wordsite-product-motion', 'strategy', 'defer' );
}, 40 );
