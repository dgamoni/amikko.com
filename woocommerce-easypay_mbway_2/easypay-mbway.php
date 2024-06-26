<?php

/**
 * Plugin Name: WooCommerce Easypay Gateway MBWay fix 2.00
 * Description: Easypay Payment Gateway for WooCommerce - Don't leave for tomorrow what you can receive today
 * Version: 9992.00
 * Author: Easypay
 * Author URI: https://easypay.pt
 * Requires at least: 3.5
 * Tested up to: 3.8.1
 *
 * Text Domain: wceasypay
 * Domain Path: /languages/
 *
 * @package Woocommerce-easypay-gateway-mbway
 * @category Gateway
 * @author Easypay
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Install
require_once 'core/install.php';
register_activation_hook(__FILE__, 'wceasypay_activation_mbway_2');

// Uninstall
require_once 'core/uninstall.php';
register_deactivation_hook(__FILE__, 'wceasypay_deactivation_mbway_2');

//Plugin initialization
add_action('plugins_loaded', 'woocommerce_gateway_easypay_mbway_2_init', 0);
add_action('woocommerce_api_easypay', 'easypay_callback_handler');
add_action('wp_ajax_ep_mbway_check_payment', 'ep_mbway_check_payment');
add_action('wp_ajax_nopriv_ep_mbway_check_payment', 'ep_mbway_check_payment');

function ep_mbway_check_payment()
{
    $ajax_nonce = wp_create_nonce('wp-ep-mbway2-plugin');
    check_ajax_referer('wp-ep-mbway2-plugin', 'wp-ep-nonce');

    $order_key = filter_input(INPUT_GET
        , 'order_key'
        , FILTER_VALIDATE_INT);
    if (is_null($order_key) || false === $order_key) {
        echo json_encode(false);
        wp_die();
    }

    global $wpdb; // this is how you get access to the database
    $notifications_table = $wpdb->prefix . 'easypay_notifications_2';

    $query_string = "SELECT ep_status"
        . " FROM $notifications_table"
        . " WHERE t_key = %s AND ep_status != 'pending'";
    $rset = $wpdb->get_results($wpdb->prepare($query_string, [$order_key]));
    if (empty($rset)) {
        $paid = false;
    } else {
        $paid = $rset[0]->ep_status;
    }

    echo json_encode($paid);
    wp_die();
}

add_action('wp_ajax_ep_mbway_user_cancelled', 'ep_mbway_user_cancelled');
add_action('wp_ajax_nopriv_ep_mbway_user_cancelled', 'ep_mbway_user_cancelled');

function ep_mbway_user_cancelled()
{
    $ajax_nonce = wp_create_nonce('wp-ep-mbway2-plugin');
    check_ajax_referer('wp-ep-mbway2-plugin', 'wp-ep-nonce');

    $order_key = filter_input(INPUT_GET
        , 'order_key'
        , FILTER_VALIDATE_INT);
    if (is_null($order_key) || false === $order_key) {
        echo json_encode(false);
        wp_die();
    }

    global $wpdb; // this is how you get access to the database
    $notifications_table = $wpdb->prefix . 'easypay_notifications_2';

    $query_string = "SELECT ep_payment_id, t_key, ep_method"
        . " FROM $notifications_table"
        . " WHERE t_key = %s AND ep_status IN ('pending','authorized')";
    $notification = $wpdb->get_results($wpdb->prepare($query_string, [$order_key]));

    if (empty($notification)) {
        $is_cancelled = false;
    } else {
        $ep_payment_id = $notification[0]->ep_payment_id;
        $t_key = $notification[0]->t_key;
        $ep_method = $notification[0]->ep_method;
        unset($notification);
        //
        // go ahead and cancel the order
        // no use sending the goods or providing the service if the user
        // has already show the "will" to cancel
        $order = new WC_Order($order_key);
        $order->update_status('cancelled', 'Cancelled by customer');
        //
        // cancel on easypay
        // we need to detect the gateway we are working with
        switch ($ep_method) {
            case 'mbw':
                if (!class_exists('WC_Gateway_Easypay_MBWay')) {

                    include realpath(plugin_dir_path(__FILE__)) . DIRECTORY_SEPARATOR
                        . 'includes' . DIRECTORY_SEPARATOR
                        . 'wc-gateway-easypay-mbway.php';
                }

                $wcep = new WC_Gateway_Easypay_MBWay();
                break;

            case 'cc':
            case 'mb':
            default:
                //
                // this hook is only used with mbw
                $msg = '[' . basename(__FILE__)
                    . '] Bad ep_method for this hook';
                (new WC_Logger())->add('easypay', $msg);
                echo json_encode(false);
                wp_die();
        }

        $api_auth = $wcep->easypay_api_auth();
        $auth = [
            'url' => $wcep->getVoidUrl() . "/$ep_payment_id",
            'account_id' => $api_auth['account_id'],
            'api_key' => $api_auth['api_key'],
            'method' => 'POST',
        ];
        $payload = [
            'transaction_key' => $t_key,
            'descriptive' => 'User cancelled',
        ];

        if (!class_exists('WC_Easypay_Request')) {
            include realpath(plugin_dir_path(__FILE__)) . DIRECTORY_SEPARATOR
                . 'includes' . DIRECTORY_SEPARATOR
                . 'wc-easypay-request.php';
        }

        $request = new WC_Easypay_Request($auth);
        $void_response = $request->get_contents($payload);
        $set = [
            'ep_last_operation_type' => 'void',
            'ep_last_operation_id' => null,
        ];
        $where = [
            'ep_payment_id' => $ep_payment_id,
        ];
        if (empty($void_response)
            || $void_response['status'] != 'ok'
        ) {
            // log and silently discard
            // auth will be voided after X days
            (new WC_Logger())->add('easypay', '[' . basename(__FILE__)
                . '] Error voiding auth in ep: ' . $void_response['message'][0]);
        } else {
            $set['ep_last_operation_id'] = $void_response['id'];
        }
        //
        // keep the void id so we can find the payment
        // from the notification
        $wpdb->update($notifications_table, $set, $where);

        $is_cancelled = true;
    }

    echo json_encode($is_cancelled);
    wp_die();
}

/**
 * WC Gateway Class - Easypay MBWAY API 2.0
 */
function woocommerce_gateway_easypay_mbway_2_init()
{

    if (!class_exists('WC_Payment_Gateway')) {
        add_action('admin_notices', 'wceasypay_woocommerce_notice_mbway_2');
        return;
    }

    /**
     * Localisation
     */
    load_plugin_textdomain('wceasypay', false, dirname(plugin_basename(__FILE__)) . '/languages');

    /**
     * Add the Easypay Gateway to WooCommerce
     *
     * @param array $methods
     * @return  array
     */
    function woocommerce_add_gateway_easypay_mbway_2($methods)
    {
        if (!class_exists('WC_Gateway_Easypay_MBWay')) {

            include realpath(plugin_dir_path(__FILE__)) . DIRECTORY_SEPARATOR
                . 'includes' . DIRECTORY_SEPARATOR
                . 'wc-gateway-easypay-mbway.php';
        }

        $methods[] = 'WC_Gateway_Easypay_MBWay';
        return $methods;
    }

    add_filter('woocommerce_payment_gateways', 'woocommerce_add_gateway_easypay_mbway_2');

} //END of function woocommerce_gateway_easypay_mb_2_init

/**
 * WooCommerce Gateway Fallback Notice
 *
 * Request to user that Easypay Plugin needs the last vresion of WooCommerce
 */
function wceasypay_woocommerce_notice_mbway_2()
{
    echo '<div class="error"><p>' . __('WooCommerce Easypay Gateway depends on the last version of <a href="http://wordpress.org/extend/plugins/woocommerce/">WooCommerce</a> to work!', 'wceasypay') . '</p></div>';
}
