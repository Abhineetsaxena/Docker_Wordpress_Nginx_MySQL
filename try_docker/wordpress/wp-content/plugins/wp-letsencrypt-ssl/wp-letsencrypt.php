<?php

/**
 *
 * One Click SSL & Force HTTPS
 *
 * Plugin Name:       WP Encryption - One Click SSL & Force HTTPS
 * Plugin URI:        https://wpencryption.com
 * Description:       Secure your WordPress site with free SSL certificate and force HTTPS throughout the site. Just activating this plugin won't help! - Please run the SSL install form of WP Encryption found on left panel.
 * Version:           5.0.3
 * Author:            Go Web Smarty
 * Author URI:        https://gowebsmarty.com
 * License:           GNU General Public License v3.0
 * License URI:       http://www.gnu.org/licenses/gpl-3.0.html
 * Text Domain:       wp-letsencrypt-ssl
 * Domain Path:       /languages
 *
 * @author      Go Web Smarty
 * @category    Plugin
 * @package     WP Encryption
 * @license     http://www.gnu.org/licenses/gpl-3.0.html GNU General Public License v3.0
 * 
 * @copyright   Copyright (C) 2019-2020, Go Web Smarty (vj@gowebsmarty.com)
 *
 * 
 */
/**
 * Die on direct access
 */
if ( !defined( 'ABSPATH' ) ) {
    die( 'Access Denied' );
}
/**
 * Definitions
 */
define( 'WPLE_VERSION', '5.0.3' );
define( 'WPLE_BASE', plugin_basename( __FILE__ ) );
define( 'WPLE_DIR', plugin_dir_path( __FILE__ ) );
define( 'WPLE_URL', plugin_dir_url( __FILE__ ) );
define( 'WPLE_NAME', 'WP Encryption' );
define( 'WPLE_SLUG', 'wp_encryption' );
///define('WPLE_TX', 'wp-letsencrypt-ssl');
$wple_updir = wp_upload_dir();
define( 'WPLE_UPLOADS', $wple_updir['basedir'] . '/' );
define( 'WPLE_DEBUGGER', WPLE_UPLOADS . 'wp_encryption/' );
/**
 * Freemius
 */

if ( function_exists( 'wple_fs' ) ) {
    wple_fs()->set_basename( false, __FILE__ );
} else {
    
    if ( !function_exists( 'wple_fs' ) ) {
        // Create a helper function for easy SDK access.
        function wple_fs()
        {
            global  $wple_fs ;
            
            if ( !isset( $wple_fs ) ) {
                // Include Freemius SDK.
                require_once dirname( __FILE__ ) . '/freemius/start.php';
                $wple_fs = fs_dynamic_init( array(
                    'id'              => '5090',
                    'slug'            => 'wp-letsencrypt-ssl',
                    'premium_slug'    => 'wp-letsencrypt-ssl-pro',
                    'type'            => 'plugin',
                    'public_key'      => 'pk_f6a07c106bf4ef064d9ac4b989e02',
                    'is_premium'      => false,
                    'premium_suffix'  => 'PRO',
                    'has_addons'      => false,
                    'has_paid_plans'  => true,
                    'has_affiliation' => 'selected',
                    'menu'            => array(
                    'slug'    => 'wp_encryption',
                    'contact' => false,
                ),
                    'is_live'         => true,
                ) );
            }
            
            return $wple_fs;
        }
        
        // Init Freemius.
        wple_fs();
        // Signal that SDK was initiated.
        do_action( 'wple_fs_loaded' );
    }

}

/**
 * Autoloader
 * 
 * @since 4.3.0
 */
require_once plugin_dir_path( __FILE__ ) . 'autoloader.php';
\WPEncryption\Autoloader::run();
/**
 * Class to handle all aspects of plugin page
 */
require_once WPLE_DIR . 'admin/le_admin.php';
new WPLE_Admin();
/**
 * Admin Pages
 * @since 5.0.0
 */
require_once WPLE_DIR . 'admin/le_admin_pages.php';
new WPLE_SubAdmin();
/**
 * Force SSL on frontend
 */
require_once WPLE_DIR . 'classes/le-forcessl.php';
new WPLE_ForceSSL();
/**
 * Plugin Activator hook
 */
register_activation_hook( __FILE__, 'wple_activate' );
if ( !function_exists( 'wple_activate' ) ) {
    function wple_activate( $networkwide )
    {
        require_once WPLE_DIR . 'classes/le-activator.php';
        WPLE_Activator::activate( $networkwide );
    }

}
/**
 * Plugin Deactivator hook
 */
register_deactivation_hook( __FILE__, 'wple_deactivate' );
if ( !function_exists( 'wple_deactivate' ) ) {
    function wple_deactivate()
    {
        require_once WPLE_DIR . 'classes/le-deactivator.php';
        WPLE_Deactivator::deactivate();
    }

}

if ( function_exists( 'wple_fs' ) && !function_exists( 'wple_fs_custom_connect_message' ) ) {
    function wple_fs_custom_connect_message( $message )
    {
        $current_user = wp_get_current_user();
        return sprintf( esc_html__( 'Howdy %1$s' ) . ',<br>' . __( 'Due to security nature of this plugin, We <b>HIGHLY</b> recommend to Opt-in to get BEST support!. Never miss an important update - opt in to our security & feature updates notifications, and non-sensitive diagnostic tracking with <a href="https://freemius.com/wordpress/usage-tracking/5090/wp-letsencrypt-ssl/" target="_blank">Freemius</a>. If you skip this, that\'s okay! <b>WP Encryption</b> will still work just fine.', 'wp-letsencrypt-ssl' ), ucfirst( $current_user->user_nicename ) );
    }
    
    wple_fs()->add_filter( 'connect_message', 'wple_fs_custom_connect_message' );
}
