<?php
/**
 * Plugin Name: EUX CRM Manager
 * Plugin URI: https://eux.com.au/
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
 * Format a unix timestamp or MySQL datetime into an RFC3339 datetime
 *
 * @since 1.0
 * @param int|string $timestamp unix timestamp or MySQL datetime
 * @param bool $convert_to_utc
 * @param bool $convert_to_gmt Use GMT timezone.
 * @return string RFC3339 datetime
 */
function eux_format_datetime( $timestamp, $convert_to_utc = false, $convert_to_gmt = false ) {
    if ( $convert_to_gmt ) {
        if ( is_numeric( $timestamp ) ) {
            $timestamp = date( 'Y-m-d H:i:s', $timestamp );
        }

        $timestamp = get_gmt_from_date( $timestamp );
    }

    if ( $convert_to_utc ) {
        $timezone = new DateTimeZone( wc_timezone_string() );
    } else {
        $timezone = new DateTimeZone( 'UTC' );
    }

    try {

        if ( is_numeric( $timestamp ) ) {
            $date = new DateTime( "@{$timestamp}" );
        } else {
            $date = new DateTime( $timestamp, $timezone );
        }

        // convert to UTC by adjusting the time based on the offset of the site's timezone
        if ( $convert_to_utc ) {
            $date->modify( -1 * $date->getOffset() . ' seconds' );
        }
    } catch ( Exception $e ) {

        $date = new DateTime( '@0' );
    }

    return $date->format( 'Y-m-d\TH:i:s\Z' );
}

include EUX_PLUGIN_PATH . '/includes/class-eux-crm-api.php';

add_action('plugins_loaded', 'eux_plugins_loaded');
function eux_plugins_loaded() {
    $eux_crm_api = EUX_CRM_API::instance();
    $eux_crm_api->init_rest_api();
}
