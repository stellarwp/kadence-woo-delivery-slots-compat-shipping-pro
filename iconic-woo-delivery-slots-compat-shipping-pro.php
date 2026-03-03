<?php
/**
 * Plugin Name:     WooCommerce Delivery Slots by Kadence [WooCommerce Table Rate Shipping Pro]
 * Plugin URI:      https://iconicwp.com/products/woocommerce-delivery-slots/
 * Description:     Compatibility between WooCommerce Delivery Slots by Kadence and WooCommerce Table Rate Shipping Pro by PluginHive.
 * Author:          Kadence
 * Author URI:      https://www.kadencewp.com/
 * Text Domain:     iconic-woo-delivery-slots-compat-shipping-pro
 * Domain Path:     /languages
 * Version:         0.1.0
 *
 * @package         Iconic_Woo_Delivery_Slots_Compat_Shipping_Pro
 */

/**
 * Is WooCommerce Table Rate Shipping Pro active?
 *
 * @return bool
 */
function iconic_compat_phsp_is_active() {
	return class_exists( 'Ph_WC_Shipping_Pro_Common' );
}

/**
 * Add shipping rate options.
 *
 * @param array            $shipping_method_options
 * @param WC_Shipping_Rate $method
 * @param WC_Shipping_Zone $zone
 *
 * @return array
 */
function iconic_compat_phsp_add_shipping_method_options( $shipping_method_options, $method, $zone ) {
	if ( ! iconic_compat_phsp_is_active() ) {
		return $shipping_method_options;
	}

	$class = str_replace( 'wc_shipping_', '', strtolower( get_class( $method ) ) );

	if ( 'ph_woocommerce_shipping_pro' !== $class ) {
		return $shipping_method_options;
	}

	$rates = ! empty( $method->get_option( 'rate_matrix' ) ) ? $method->get_option( 'rate_matrix' ) : array();

	if ( empty( $rates ) ) {
		return $shipping_method_options;
	}

	$method_title = $method->get_title();

	foreach ( $rates as $index => $rate ) {
		$shipping_name = ! empty( $rate['shipping_name'] ) ? $rate['shipping_name'] : $method_title;
		$method_id     = iconic_compat_phsp_get_method_id( $shipping_name );

		$shipping_method_options[ $method_id ] = esc_html( sprintf( '%s: %s', $zone->get_zone_name(), $shipping_name ) );
	}

	return $shipping_method_options;
}

add_filter( 'iconic_wds_zone_based_shipping_method', 'iconic_compat_phsp_add_shipping_method_options', 10, 3 );

/**
 * Remove default options.
 *
 * @return array
 */
function iconic_compat_phsp_remove_default_shipping_method_options( $shipping_method_options ) {
	if ( ! iconic_compat_phsp_is_active() ) {
		return $shipping_method_options;
	}

	unset( $shipping_method_options['wf_woocommerce_shipping_pro_method'] );

	$shipping_method_options = $shipping_method_options + iconic_compat_phsp_get_zoneless_rates();

	return $shipping_method_options;
}

add_filter( 'iconic_wds_shipping_method_options', 'iconic_compat_phsp_remove_default_shipping_method_options', 10 );

/**
 * Get method ID.
 *
 * @param string $shipping_name
 * @param bool   $ph
 *
 * @return string
 */
function iconic_compat_phsp_get_method_id( $shipping_name, $ph = true ) {
	$method_id = sanitize_title( $shipping_name );
	$method_id = preg_replace( '/[^A-Za-z0-9\-]/', '', $method_id );

	$prefix = $ph ? 'ph_' : 'wf_';

	return $prefix . 'woocommerce_shipping_pro:' . $method_id;
}

/**
 * Get zoneless shipping rates/methods.
 *
 * @return array
 */
function iconic_compat_phsp_get_zoneless_rates() {
	$rates = array();
	
	if ( ! class_exists( 'wf_woocommerce_shipping_pro_method' ) ) {
		return $rates;
	}
	
	$method = new wf_woocommerce_shipping_pro_method();

	if ( ! $method ) {
		return $rates;
	}

	$rate_matrix = ! empty( $method->get_option( 'rate_matrix' ) ) ? $method->get_option( 'rate_matrix' ) : array();

	if ( empty( $rate_matrix ) ) {
		return $rates;
	}

	foreach ( $rate_matrix as $rate ) {
		$shipping_name = ! empty( $rate['shipping_name'] ) ? $rate['shipping_name'] : $method->get_title();
		$method_id     = iconic_compat_phsp_get_method_id( $shipping_name, false );

		$rates[ $method_id ] = $shipping_name;
	}

	return $rates;
}
