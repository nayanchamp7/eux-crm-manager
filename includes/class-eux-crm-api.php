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
            //(?P<phone>[a-zA-Z]+)
            //(?P<phone>[a-zA-Z0-9-]+)

            // register endpoint to get icon list
            register_rest_route(
                'wc/v3',
                '/posts/',
                array(
                    'methods' => 'GET',
                    'callback' => [$this, 'eux_get_customer_by_phone'],
                    'permission_callback' => '__return_true',
                )
            );
        }

        // get customer data by phone number
        function eux_get_customer_by_phone($request) {
            $result  = [];
            $phone = $request->get_param('phone');



            die( print_r($request) );



            return new WP_REST_Response([
                $phone
            ]);
        }
    }
}