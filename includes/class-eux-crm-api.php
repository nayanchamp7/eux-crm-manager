<?php

/**
 * EUX CRM API
 *
 * @package    EUX_CRM_API
 * @subpackage EUX_CRM_API
 * @author     EUX Digital Agency <https://eux.com.au/>
 */

defined('ABSPATH') || exit;

use Automattic\WooCommerce\Client;

if ( ! class_exists('EUX_CRM_API') ) {
    class EUX_CRM_API {

        public $wc_api;

        protected static $_instance = null;

        function __construct() {
            do_action( 'eux_crm_api_loaded', $this );
        }

        // instance
        public static function instance() {
            if ( is_null( self::$_instance ) ) {
                self::$_instance = new self();
            }

            return self::$_instance;
        }

        // authorize
        function authorize() {
            //$this->wc_api
            $woocommerce = new Client(
                'http://localhost:10033',
                'ck_2f7573d408d421a348a2da3e9169745c14048a38',
                'cs_29dd07d7098fb1c94605d8cd47dae2db50a28ef2',
                [
                    'wp_api' => true,
                    'version' => 'wc/v3',
                ]
            );

            $result = $woocommerce->get('customers');

            die(print_r($result));

            return $this;


        }

        // init rest api
        function init_rest_api() {
            add_action('rest_api_init',  [$this, 'eux_register_api']);
        }

        // create eux api endpoint
        function eux_register_api() {

            // register rest route to get customer data by phone and email
            register_rest_route(
                'eux/v1',
                '/customers/',
                array(
                    'methods' => 'GET',
                    'callback' => [$this, 'eux_get_customer_by_phone_n_email'],
                    'permission_callback' => '__return_true',
                )
            );

            // register rest route to post a new customer
            register_rest_route(
                'eux/v1',
                '/customers/',
                array(
                    'methods' => 'POST',
                    'callback' => [$this, 'eux_post_to_create_new_customer'],
                    'permission_callback' => '__return_true',
                )
            );

            // register rest route to post a new customer
            register_rest_route(
                'eux/v1',
                '/customers/search/',
                array(
                    'methods' => 'GET',
                    'callback' => [$this, 'eux_search_customer_by_phone'],
                    'permission_callback' => '__return_true',
                )
            );
        }

        // get customer data by phone number or email
        function eux_get_customer_by_phone_n_email($request) {

            $result  = [];
            $phone = $request->get_param('phone');
            $email = $request->get_param('email');

            if( isset($phone) && ! empty($phone) ) {
                $user_id = '';
                global $wpdb;
                $search_phone = (string)"%" . $phone . "%";
                //$search_phone = $phone;

                $query = "SELECT * FROM $wpdb->usermeta WHERE $wpdb->usermeta.meta_key = 'billing_phone' AND $wpdb->usermeta.meta_value LIKE '" . $search_phone . "'  ORDER BY $wpdb->usermeta.user_id DESC";

                $customer = $wpdb->get_results($query, OBJECT);

            }

            if( isset($email) && ! empty($email) ) {
                $user_id = '';
                global $wpdb;
                $search_email = (string)"%" . $email . "%";
                $search_email = $email;

                $query = "SELECT * FROM $wpdb->usermeta WHERE $wpdb->usermeta.meta_key = 'billing_email' AND $wpdb->usermeta.meta_value = '" . $search_email . "'  ORDER BY $wpdb->usermeta.user_id DESC";

                $customer = $wpdb->get_results($query, OBJECT);

            }

            if( isset($phone) || isset($email) ) {
                if (isset($customer) && count($customer) > 0) {
                    $user_id = $customer[0]->user_id;

                    $customer = new WC_Customer($user_id);
                    $result = $this->eux_create_customer_data($customer);
                }
            }

            return new WP_REST_Response([
                $result
            ]);


        }

        // search customer by phone number
        function eux_search_customer_by_phone($request) {
            $result  = [];
            $phone = $request->get_param('phoneNumber');

            if( isset($phone) && ! empty($phone) ) {
                $user_id = '';
                global $wpdb;
                $search_phone = (string)"%" . $phone . "%";

                $query = "SELECT * FROM $wpdb->usermeta WHERE $wpdb->usermeta.meta_key = 'billing_phone' AND $wpdb->usermeta.meta_value LIKE '" . $search_phone . "'  ORDER BY $wpdb->usermeta.user_id DESC";

                $customer = $wpdb->get_results($query, OBJECT);

                if( isset($phone) || isset($email) ) {
                    if (isset($customer) && count($customer) > 0) {
                        $user_id = $customer[0]->user_id;

                        $customer = new WC_Customer($user_id);
                        $result = $this->eux_create_customer_data($customer);
                    }
                }
            }

            return new WP_REST_Response([
                $result
            ]);
        }

        // post to create a new customer
        function eux_post_to_create_new_customer($request) {
            // Fetching values from API
            $parameters = $request->get_params();
            $data = [];

            if( isset($parameters['email']) && isset($parameters['firstname']) ) {

                if( ! email_exists($parameters['email']) ) {
                    $user_id = wc_create_new_customer( $parameters['email'], $parameters['firstname'], rand() );

                    $lastname   = isset($parameters['lastname']) ? $parameters['lastname'] : '';
                    $phone      = isset($parameters['phone']) ? $parameters['phone'] : '';

                    update_user_meta( $user_id, "billing_first_name", $parameters['firstname'] );
                    update_user_meta( $user_id, "billing_last_name", $lastname );
                    update_user_meta( $user_id, "billing_phone", $phone );
                    update_user_meta( $user_id, "billing_email", $parameters['email'] );


                    $data['id'] = $user_id;
                    $data['email'] = $parameters['email'];
                    $data['phone'] = $phone;
                    $data['first_name'] = $parameters['firstname'];
                    $data['last_name'] = $lastname;
                }
            }

            return new WP_REST_Response([
                $data
            ]);

        }

        // create customer data
        function eux_create_customer_data($customer) {

            if( ! $customer ) {
                return [];
            }

            $last_order = $customer->get_last_order();

            $customer_data = array(
                'id' => $customer->get_id(),
                'email' => $customer->get_email(),
                'first_name' => $customer->get_first_name(),
                'last_name' => $customer->get_last_name(),
                'username' => $customer->get_username(),
                'role' => $customer->get_role(),
                'phone' => $customer->get_billing_phone(),
                'last_order_id' => is_object($last_order) ? $last_order->get_id() : null,
                'last_order_date' => is_object($last_order) ? eux_format_datetime($last_order->get_date_created() ? $last_order->get_date_created()->getTimestamp() : 0) : null, // API gives UTC times.
                'orders_count' => $customer->get_order_count(),
                'total_spent' => wc_format_decimal($customer->get_total_spent(), 2),
                'avatar_url' => $customer->get_avatar_url(),
                'billing_address' => array(
                    'first_name' => $customer->get_billing_first_name(),
                    'last_name' => $customer->get_billing_last_name(),
                    'company' => $customer->get_billing_company(),
                    'address_1' => $customer->get_billing_address_1(),
                    'address_2' => $customer->get_billing_address_2(),
                    'city' => $customer->get_billing_city(),
                    'state' => $customer->get_billing_state(),
                    'postcode' => $customer->get_billing_postcode(),
                    'country' => $customer->get_billing_country(),
                    'email' => $customer->get_billing_email(),
                    'phone' => $customer->get_billing_phone(),
                ),
                'shipping_address' => array(
                    'first_name' => $customer->get_shipping_first_name(),
                    'last_name' => $customer->get_shipping_last_name(),
                    'company' => $customer->get_shipping_company(),
                    'address_1' => $customer->get_shipping_address_1(),
                    'address_2' => $customer->get_shipping_address_2(),
                    'city' => $customer->get_shipping_city(),
                    'state' => $customer->get_shipping_state(),
                    'postcode' => $customer->get_shipping_postcode(),
                    'country' => $customer->get_shipping_country(),
                ),
            );

            return $customer_data;
        }
    }
}