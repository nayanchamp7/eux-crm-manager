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

use Automattic\WooCommerce\Client;
use Automattic\WooCommerce\HttpClient\HttpClientException;

if ( ! defined( 'EUX_FILE' ) ) {
    define( 'EUX_FILE', __FILE__ );
}

if ( ! defined( 'EUX_PLUGIN_URL' ) ) {
    define( 'EUX_PLUGIN_URL', plugins_url( '', EUX_FILE ) );
}

if ( ! defined( 'EUX_PLUGIN_PATH' ) ) {
    define( 'EUX_PLUGIN_PATH', untrailingslashit(plugin_dir_path(EUX_FILE)) );
}


/**
 * EUX CRM API Settings Init
 */
function eux_crm_api_settings_init() {
    register_setting( 'eux_crm_api_settings', 'eux_crm_general_settings' );
    add_settings_section(
        'eux_crm_general_setting',
        __( '', 'eux-crm-manager' ),
        '',
        'eux-crm-api-settings'
    );

    add_settings_field(
        'eux_crm_api_consumer_key',
        __( 'Consumer Key:', 'eux-crm-manager' ),
        'eux_crm_api_consumer_key_cb',
        'eux-crm-api-settings',
        'eux_crm_general_setting'
    );

    add_settings_field(
        'eux_crm_api_consumer_secret',
        __( 'Consumer Secret:', 'eux-crm-manager' ),
        'eux_crm_api_consumer_secret_cb',
        'eux-crm-api-settings',
        'eux_crm_general_setting'
    );
}
add_action( 'admin_init', 'eux_crm_api_settings_init' );


/**
 * EUX CRM API consumer key callback.
 */
function eux_crm_api_consumer_key_cb() {
    $options = get_option( 'eux_crm_general_settings' );
    $options = ! empty( $options ) ? $options : "";
    $val = ( isset( $options['eux_crm_api_consumer_key'] ) && !empty($options['eux_crm_api_consumer_key']) ) ? $options['eux_crm_api_consumer_key'] : "";
    echo '<input type="text" class="eux-crm-api-consumer-key" name="eux_crm_general_settings[eux_crm_api_consumer_key]" value="' . $val . '" />';
    echo sprintf('<p class="eux-setting-description"><span>&#9432;</span> %s</p>', esc_html__('Woocommerce consumer key here.', 'eux-crm-manager'));
}

/**
 * EUX CRM API consumer secret callback.
 */
function eux_crm_api_consumer_secret_cb() {
    $options = get_option( 'eux_crm_general_settings' );
    $options = ! empty( $options ) ? $options : "";
    $val = ( isset( $options['eux_crm_api_consumer_secret'] ) && !empty($options['eux_crm_api_consumer_secret']) ) ? $options['eux_crm_api_consumer_secret'] : "";
    echo '<input type="text" class="eux-crm-api-consumer-secret" name="eux_crm_general_settings[eux_crm_api_consumer_secret]" value="' . $val . '" />';
    echo sprintf('<p class="eux-setting-description"><span>&#9432;</span> %s</p>', esc_html__('Woocommerce consumer secret here.', 'eux-crm-manager'));
}


add_action("admin_menu", "eux_crm_submenu");
function eux_crm_submenu() {
    add_submenu_page(
        'options-general.php',
        __("EUX CRM API Settings", "eux-crm-manager"),
        __("EUX CRM API", "eux-crm-manager"),
        'administrator',
        'eux-crm-api-settings',
        'eux_crm_api_settings_page' );
}

function eux_crm_api_settings_page() {
    // check user capabilities
    if ( ! current_user_can( 'manage_options' ) ) {
        return;
    }

    // WordPress will add the "settings-updated" $_GET parameter to the url
    if ( isset( $_GET['settings-updated'] ) ) {
        // add settings saved message with the class of "updated"
        add_settings_error( 'eux_messages', 'eux_messages', __( 'Settings Saved', 'eux-crm-manager' ), 'updated' );
    }

    // show error/update messages
    settings_errors( 'eux_messages' );
    ?>
    <div class="wrap">
        <h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
        <form action="options.php" method="post">
            <?php
            settings_fields( 'eux_crm_api_settings' );
            do_settings_sections( 'eux-crm-api-settings' );

            // output save settings button
            submit_button( 'Save Settings' );
            ?>
        </form>
    </div>
    <?php
}


function eux_crm_api_curl() {
    $url = "http://localhost:10033/wp-json/wc/v3/customers/2";

    $options = get_option( 'eux_crm_general_settings' );
    $options = ! empty( $options ) ? $options : "";
    $consumer_key       = ( isset( $options['eux_crm_api_consumer_key'] ) && !empty($options['eux_crm_api_consumer_key']) ) ? $options['eux_crm_api_consumer_key'] : "";
    $consumer_secret    = ( isset( $options['eux_crm_api_consumer_secret'] ) && !empty($options['eux_crm_api_consumer_secret']) ) ? $options['eux_crm_api_consumer_secret'] : "";

    $headers = array(
        'Authorization' => 'Basic ' . base64_encode($consumer_key.':'.$consumer_secret )
    );

    $curl = curl_init();
    curl_setopt($curl, CURLOPT_URL, $url);
    //curl_setopt($curl, CURLOPT_POST, true);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($curl, CURLOPT_TIMEOUT, 30);

    curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
    //curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($data));

    //for debug only!
    curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($curl, CURLOPT_USERPWD, "$consumer_key:$consumer_secret");
    $resp = curl_exec($curl);
    $status_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
    curl_close($curl);


    error_log( print_r(json_decode($resp)), true );
//    die();

    return json_decode($resp);
}


include EUX_PLUGIN_PATH . '/includes/class-eux-crm-api.php';
$eux_crm_api = EUX_CRM_API::instance();
$eux_crm_api->init_rest_api();


add_filter('woocommerce_rest_prepare_customer', 'filter_response', 10, 3);
function filter_response($response, $user_data, $request) {
    // Customize response data here
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

        //$eux_crm_api = EUX_CRM_API::instance();
        //$wc_api = $eux_crm_api->authorize();
        //$result = $wc_api->get('customers/2');

        $options = get_option( 'eux_crm_general_settings' );
        $options = ! empty( $options ) ? $options : "";
        $consumer_key       = ( isset( $options['eux_crm_api_consumer_key'] ) && !empty($options['eux_crm_api_consumer_key']) ) ? $options['eux_crm_api_consumer_key'] : "";
        $consumer_secret    = ( isset( $options['eux_crm_api_consumer_secret'] ) && !empty($options['eux_crm_api_consumer_secret']) ) ? $options['eux_crm_api_consumer_secret'] : "";


        $result = eux_crm_api_curl();

//        $woocommerce = new Client(
//            'http://localhost:10033',
//            $consumer_key,
//            $consumer_secret,
//            [
//                'wp_api' => true,
//                'version' => 'wc/v3',
//        //        'timeout'=> 30,
////                'verify_ssl'=> false,
//            ]
//        );
//
//        $result = $woocommerce->get('customers');


        return [$result];

//        $data = $response->get_data();

//        $newdata = [];
//
//        if ($request['fields'] != null)
//        {
//            foreach ( explode ( ",", $request['fields'] ) as $field )
//            {
//                $newdata[$field] = $data[$field];
//            }
//
//
//        }

        //$ndata = [];
        //$response->set_data( $ndata );

//        if( $data['id'] == 3 ) {
//            return $response;
//        }



        //if( isset($response) && count($response) > 0 ) {
//            foreach($response as $index => $item) {
//                //die(print_r($item));
//                //echo $item->id;
//                //echo "<br>";
//            }
        //}


        //$consumer_key = $request->get_param('oauth_consumer_key');
        //echo $consumer_secret = $request->get_param('user_id');



        //cs_29dd07d7098fb1c94605d8cd47dae2db50a28ef2
//        $table_name = $wpdb->prefix . "woocommerce_api_keys";
//        $query = "SELECT 'consumer_secret' FROM $table_name WHERE 'user_id' = $user_id";
//        $customer = $wpdb->get_results($query, OBJECT);
//        $consumer_secret_key = $request->get_param('oauth_consumer_key');


    }
//
//    die(print_r($response->data));

    //return $response;
}
