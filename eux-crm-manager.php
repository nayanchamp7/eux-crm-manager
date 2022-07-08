<?php
/**
 * Plugin Name: EUX CRM Manager
 * Plugin URI: https://profiles.wordpress.org/wpfeelteam/
 * Description: A CRM Manager for 3CX with Woocommerce API Integration.
 * Version: 1.0.0
 * Author: EUX Digital Agency
 * Author URI: https://eux.com.au/
 * Text Domain: eux-crm-manager
 * Domain Path: /i18n/languages/
 *
 * WP Requirement & Test
 * Requires at least: 4.4
 * Tested up to: 6.0
 * Requires PHP: 5.6
 *
 * WC Requirement & Test
 * WC requires at least: 3.2
 * WC tested up to: 6.3
 *
 * @package EUX_CRM_Manager
 */

defined( 'ABSPATH' ) || exit;

// autoload
require __DIR__ . '/vendor/autoload.php';

if ( ! defined( 'EUX_FILE' ) ) {
    define( 'EUX_FILE', __FILE__ );
}

if ( ! defined( 'EUX_PLUGIN_URL' ) ) {
    define( 'EUX_PLUGIN_URL', plugins_url( '', EUX_FILE ) );
}

if ( ! defined( 'EUX_PLUGIN_PATH' ) ) {
    define( 'EUX_PLUGIN_PATH', untrailingslashit(plugin_dir_path(EUX_FILE)) );
}

include EUX_PLUGIN_PATH . '/includes/class-eux-crm-api.php';
$eux_crm_api = EUX_CRM_API::instance();
$eux_crm_api->init_rest_api();


add_filter('woocommerce_rest_prepare_customer', 'filter_response', 10, 3);
function filter_response($response, $user_data, $request) {
    // Customize response data here
    $user_id = $user_data->ID;

    $phone = $request->get_param('phone');

    if( isset($phone) && ! empty($phone) ) {
        $user_id = '';
        global $wpdb;
        $search_phone = (string) "%" . $phone . "%";
        $query = "SELECT * FROM $wpdb->usermeta WHERE $wpdb->usermeta.meta_key = 'billing_phone' AND $wpdb->usermeta.meta_value LIKE '" . $search_phone . "'  ORDER BY $wpdb->usermeta.user_id DESC";

        $customer = $wpdb->get_results($query, OBJECT);

        if( isset($customer) && count($customer) > 0 ) {
            $user_id = $customer[0]->user_id;
        }

        $consumer_key = $request->get_param('oauth_consumer_key');

        //cs_29dd07d7098fb1c94605d8cd47dae2db50a28ef2
        $table_name = $wpdb->prefix . "woocommerce_api_keys";
        $query = "SELECT 'consumer_secret' FROM $table_name WHERE 'user_id' = $user_id";
        $customer = $wpdb->get_results($query, OBJECT);
        $consumer_secret_key = $request->get_param('oauth_consumer_key');


    }
//
//    die(print_r($response->data));

    return $response;
}