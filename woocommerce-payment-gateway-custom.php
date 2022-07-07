<?php
/*
Plugin Name: WooCommerce Custom Gateway
Plugin URI: https://github.com/FabMediaIO/woocommerce-custom-payment-gateway
Description: Extends WooCommerce with an custom gateway.
Version: 1.0.0
Author: Fabmedia
Author URI: https://fabmedia.io/
Text Domain: wc-gateway-custom
License: GNU General Public License v3.0
License URI: http://www.gnu.org/licenses/gpl-3.0.html
*/

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

// Make sure WooCommerce is active
if ( ! in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) {
	return;
}

// custom Payment Gateway
add_action('plugins_loaded', 'woocommerce_gateway_name_init', 11);
function woocommerce_gateway_name_init() {
	if ( !class_exists( 'WC_Payment_Gateway' ) ) return;
	
	// Gateway class
	class WC_Gateway_Name extends WC_Payment_Gateway {

		public function __construct() {
  
	    $this->id                 = 'custom_gateway';
	    $this->icon               = apply_filters('woocommerce_custom_icon', plugins_url('/assets/icon.png', __FILE__ ));
	    $this->has_fields         = false;
	    $this->method_title       = __( 'Custom', 'wc-gateway-custom' );
	    $this->method_description = __( 'Allows custom payments.', 'wc-gateway-custom' );
	    
	    $this->init_form_fields();
	    $this->init_settings();
	    
	    $this->title        = $this->get_option( 'title' );
	    $this->description  = $this->get_option( 'description' );
	    $this->instructions = $this->get_option( 'instructions', $this->description );
	    
	    // Actions
	    add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );
	    add_action( 'woocommerce_thankyou_' . $this->id, array( $this, 'thankyou_page' ) );
	    
	    // Customer Emails
	    add_action( 'woocommerce_email_before_order_table', array( $this, 'email_instructions' ), 10, 3 );
	  }

	  // Gateway Settings
	  public function init_form_fields() {
  
	    $this->form_fields = apply_filters( 'wc_custom_form_fields', array(
	    
	      'enabled' => array(
	        'title'   => __( 'Enable/Disable', 'wc-gateway-custom' ),
	        'type'    => 'checkbox',
	        'label'   => __( 'Enable Custom Payment', 'wc-gateway-custom' ),
	        'default' => 'yes'
	      ),
	      
	      'title' => array(
	        'title'       => __( 'Title', 'wc-gateway-custom' ),
	        'type'        => 'text',
	        'description' => __( 'This controls the title for the payment method the customer sees during checkout.', 'wc-gateway-custom' ),
	        'default'     => __( 'Custom Payment', 'wc-gateway-custom' ),
	        'desc_tip'    => true,
	      ),
	      
	      'description' => array(
	        'title'       => __( 'Description', 'wc-gateway-custom' ),
	        'type'        => 'textarea',
	        'description' => __( 'Payment method description that the customer will see on your checkout.', 'wc-gateway-custom' ),
	        'default'     => __( 'Please remit payment to Store Name upon pickup or delivery.', 'wc-gateway-custom' ),
	        'desc_tip'    => true,
	      ),
	      
	      'instructions' => array(
	        'title'       => __( 'Instructions', 'wc-gateway-custom' ),
	        'type'        => 'textarea',
	        'description' => __( 'Instructions that will be added to the thank you page and emails.', 'wc-gateway-custom' ),
	        'default'     => '',
	        'desc_tip'    => true,
	      ),
	    ) );
	  }

	  // Thankyou Page Settings
	  public function thankyou_page() {
	    if ( $this->instructions ) {
	      echo wpautop( wptexturize( $this->instructions ) );
	    }
	  }

	  // Emails Settings
	  public function email_instructions( $order, $sent_to_admin, $plain_text = false ) {
	  
	    if ( $this->instructions && ! $sent_to_admin && $this->id === $order->payment_method && $order->has_status( 'on-hold' ) ) {
	      echo wpautop( wptexturize( $this->instructions ) ) . PHP_EOL;
	    }
	  }

	  // Payment Processing
	  public function process_payment( $order_id ) {

	    $order = wc_get_order( $order_id );
	    $order->update_status( 'on-hold', __( 'Awaiting custom payment', 'wc-gateway-custom' ) );
	    $order->reduce_order_stock();
	    WC()->cart->empty_cart();
	    
	    return array(
	      'result'  => 'success',
	      'redirect'  => $this->get_return_url( $order )
	    );
	  }
	}
	
	// Add the Gateway to WooCommerce
	add_filter('woocommerce_payment_gateways', 'woocommerce_add_gateway_name_gateway' );
	function woocommerce_add_gateway_name_gateway($methods) {
		$methods[] = 'WC_Gateway_Name';
		return $methods;
	}

	// Adds plugin page links
	add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), 'wc_custom_gateway_plugin_link' );
	function wc_custom_gateway_plugin_link( $links ) {

		$plugin_links = array(
			'<a href="' . admin_url( 'admin.php?page=wc-settings&tab=checkout&section=custom_gateway' ) . '">' . __( 'Configure', 'wc-gateway-custom' ) . '</a>'
		);

		return array_merge( $plugin_links, $links );
	}
}