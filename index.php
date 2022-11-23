<?php

/**
 * Plugin Name: Deadwaves - WooCommerce Coupon Generator
 * Plugin URI:
 * Description: Generate Coupon and Sell Coupon as gift cards
 * Version: 1.0.4
 * Text Domain:
 * Author: Rontó Zoltán
 * Author URI: https://simahero.github.io
 */

/* 
  01001001 00100000 01001100 01001111
  01010110 01000101 00100000 01011001
  01001111 01010101 00100000 01001100
  01001111 01010100 01010100 01001001
  00100000 00111100 00110011 00000000
*/

add_filter('woocommerce_get_sections_products', 'dw_coupon_add_section');
function dw_coupon_add_section($sections)
{

    $sections['dw_coupon'] = __('Gift Card', 'dw');
    return $sections;
}

add_filter('woocommerce_get_settings_products', 'dw_coupon_all_settings', 10, 2);
function dw_coupon_all_settings($settings, $current_section)
{

    if ($current_section == 'dw_coupon') {
        $settings_slider = array();
        $settings_slider[] = array('name' => __('DeadWaves Coupon Settings', 'dw'), 'type' => 'title', 'desc' => __('The following options are used to configure Gift cards', 'dw'), 'id' => 'dw_coupon');
        $settings_slider[] = array(
            'name'     => __('Products', 'dw'),
            'desc_tip' => __('ID\'s of products.', 'dw'),
            'id'       => 'dw_coupon_ids',
            'type'     => 'text',
            'desc'     => __('ID of products as Gift Card! Use comma to separate them!', 'dw'),
        );

        $settings_slider[] = array('type' => 'sectionend', 'id' => 'dw_coupon');
        return $settings_slider;
    } else {
        return $settings;
    }
}

add_action('woocommerce_order_status_processing', 'generate_coupon', 1, 1);
function generate_coupon($order_id)
{

    $order = new WC_Order($order_id);

    $gift_card_ids = get_option('dw_coupon_ids');
    $ids = explode(",", $gift_card_ids);

    foreach ($order->get_items() as $item_id => $item) {

        if (in_array($item->get_product_id(), $ids)) {

            for ($i = 0; $i < $item->get_quantity(); $i++) {
                $product_variation = new WC_Product_Variation($item->get_variation_id());
                $regular_price = $product_variation->regular_price;

                $coupon_code = strtoupper(uniqid('Ajandek'));
                $amount = $regular_price;
                $discount_type = 'fixed_cart';

                $coupon = array(
                    'post_title' => $coupon_code,
                    'post_content' => '',
                    'post_status' => 'publish',
                    'post_author' => 1,
                    'post_type' => 'shop_coupon'
                );

                $new_coupon_id = wp_insert_post($coupon);

                update_post_meta($new_coupon_id, 'discount_type', $discount_type);
                update_post_meta($new_coupon_id, 'coupon_amount', $amount);
                update_post_meta($new_coupon_id, 'individual_use', 'yes');
                update_post_meta($new_coupon_id, 'product_ids', '');
                update_post_meta($new_coupon_id, 'exclude_product_ids', '');
                update_post_meta($new_coupon_id, 'usage_limit', '1');
                update_post_meta($new_coupon_id, 'expiry_date', '');
                update_post_meta($new_coupon_id, 'apply_before_tax', 'yes');
                update_post_meta($new_coupon_id, 'free_shipping', 'no');

                $note = __("Kód: " . $coupon_code . "\nÉrték: " . $amount);
                $order->add_order_note($note);
            }
        }
    }
}

add_filter('woocommerce_available_payment_gateways', 'specific_products_shipping_methods', 10, 2);
function specific_products_shipping_methods($available_gateways)
{

    $gift_card_ids = get_option('dw_coupon_ids');
    $ids = explode(",", $gift_card_ids);

    $method_id = 'cod';
    $found = false;

    global $woocommerce;
    $items = $woocommerce->cart->get_cart();

    foreach ($items as $cart_item) {
        if (in_array($cart_item['product_id'], $ids)) {
            $found = true;
            break;
        }
    }
    if ($found)
        unset($available_gateways[$method_id]);

    return $available_gateways;
}
