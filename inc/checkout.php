<?php
/** Guest checkout and contact details for the digital-products store. */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Disable registration without removing existing customers' ability to log in.
add_filter( 'pre_option_woocommerce_enable_guest_checkout', static function () { return 'yes'; } );
add_filter( 'pre_option_woocommerce_enable_myaccount_registration', static function () { return 'no'; } );
add_filter( 'pre_option_woocommerce_enable_signup_and_login_from_checkout', static function () { return 'no'; } );
add_filter( 'pre_option_woocommerce_enable_delayed_account_creation', static function () { return 'no'; } );

/** Only simplify addresses when the current cart does not need shipping. */
function wordsite_is_digital_cart() {
	return function_exists( 'WC' ) && WC()->cart && ! WC()->cart->is_empty() && ! WC()->cart->needs_shipping();
}

/** Register a native contact field in the WooCommerce Checkout block. */
add_action( 'woocommerce_init', static function () {
	if ( ! function_exists( 'woocommerce_register_additional_checkout_field' ) ) {
		return;
	}
	woocommerce_register_additional_checkout_field( array(
		'id'       => 'saudistore/phone',
		'label'    => 'رقم الجوال',
		'location' => 'contact',
		'type'     => 'text',
		'required' => true,
		'attributes' => array( 'autocomplete' => 'tel' ),
		'validate_callback' => static function ( $value ) {
			if ( $value && ! WC_Validation::is_phone( $value ) ) {
				return new WP_Error( 'invalid_phone', 'يرجى إدخال رقم جوال صحيح.' );
			}
			return true;
		},
	) );
} );

/** Keep the phone in WooCommerce's usual billing-phone field for order tools. */
add_action( 'woocommerce_store_api_checkout_update_order_from_request', static function ( $order, $request ) {
	$fields = $request['additional_fields'];
	if ( is_array( $fields ) && isset( $fields['saudistore/phone'] ) ) {
		$order->set_billing_phone( sanitize_text_field( $fields['saudistore/phone'] ) );
	}
	if ( wordsite_is_digital_cart() && ! $order->get_billing_country() ) {
		$base = WC()->countries->get_base_country();
		if ( isset( WC()->countries->get_allowed_countries()[ $base ] ) ) {
			$order->set_billing_country( $base );
		}
	}
}, 10, 2 );

/** Use the store's base country if a digital checkout has no country yet. */
function wordsite_default_digital_country( $customer ) {
	if ( ! wordsite_is_digital_cart() || ! $customer || $customer->get_billing_country() ) {
		return;
	}
	$base = WC()->countries->get_base_country();
	$allowed = WC()->countries->get_allowed_countries();
	if ( isset( $allowed[ $base ] ) ) {
		$customer->set_billing_country( $base );
	}
}
add_action( 'wp_loaded', static function () {
	if ( function_exists( 'WC' ) && WC()->customer ) {
		wordsite_default_digital_country( WC()->customer );
	}
}, 30 );
add_action( 'woocommerce_store_api_checkout_update_customer_from_request', static function ( $customer ) {
	wordsite_default_digital_country( $customer );
}, 10, 1 );

/** Relax core address requirements for digital carts in block checkout. */
function wordsite_optional_digital_address_fields( $fields ) {
	if ( ! wordsite_is_digital_cart() ) {
		return $fields;
	}
	foreach ( array( 'first_name', 'last_name', 'company', 'address_1', 'address_2', 'city', 'state', 'postcode', 'phone' ) as $key ) {
		if ( isset( $fields[ $key ] ) ) {
			$fields[ $key ]['required'] = false;
			$fields[ $key ]['hidden'] = true;
		}
	}
	return $fields;
}
add_filter( 'woocommerce_get_country_locale_default', 'wordsite_optional_digital_address_fields', 99 );
add_filter( 'woocommerce_get_country_locale', static function ( $locales ) {
	if ( wordsite_is_digital_cart() ) {
		foreach ( $locales as $country => $fields ) {
			$locales[ $country ] = wordsite_optional_digital_address_fields( $fields );
		}
	}
	return $locales;
}, 99 );

/** Classic checkout fallback: keep email and phone, in that order. */
add_filter( 'woocommerce_checkout_fields', static function ( $fields ) {
	if ( ! wordsite_is_digital_cart() || ! isset( $fields['billing'] ) ) {
		return $fields;
	}
	$fields['billing'] = array_intersect_key( $fields['billing'], array_flip( array( 'billing_email', 'billing_phone' ) ) );
	if ( isset( $fields['billing']['billing_email'] ) ) {
		$fields['billing']['billing_email']['priority'] = 10;
	}
	if ( isset( $fields['billing']['billing_phone'] ) ) {
		$fields['billing']['billing_phone']['label'] = 'رقم الجوال';
		$fields['billing']['billing_phone']['required'] = true;
		$fields['billing']['billing_phone']['priority'] = 20;
	}
	return $fields;
}, 99 );
add_action( 'woocommerce_checkout_create_order', static function ( $order ) {
	if ( wordsite_is_digital_cart() && ! $order->get_billing_country() ) {
		$base = WC()->countries->get_base_country();
		$allowed = WC()->countries->get_allowed_countries();
		if ( isset( $allowed[ $base ] ) ) {
			$order->set_billing_country( $base );
		}
	}
}, 10 );

/** Add a scoped class so shipping checkouts keep their billing address visible. */
add_filter( 'body_class', static function ( $classes ) {
	if ( function_exists( 'is_checkout' ) && is_checkout() && wordsite_is_digital_cart() ) {
		$classes[] = 'ws-digital-checkout';
	}
	return $classes;
} );

/** Replace the checkout template's site title with the store logo. */
add_filter( 'render_block_core/site-title', static function ( $html ) {
	if ( ! function_exists( 'is_checkout' ) || ! is_checkout() || is_admin() ) {
		return $html;
	}
	return str_replace( 'class="ws-brand-logo"', 'class="ws-brand-logo ws-checkout-brand"', wordsite_taraqqi_logo_shortcode() );
}, 10, 1 );
