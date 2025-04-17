<?php
/*
Plugin Name: Log WP Redirects
Plugin URI: https://github.com/BeAPI/log-wp-redirects
Description: Log all WordPress redirections made via wp_redirect() function
Version: 1.0.3
Author: Be API
Author URI: https://beapi.fr
Network: true
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html
Text Domain: log-wp-redirects
Domain Path: /languages
Requires at least: 5.8
Requires PHP: 7.0
*/

defined( 'ABSPATH' ) or exit;

// Define configuration constants
if (!defined('LWR_LOG_IP')) {
    define('LWR_LOG_IP', true); // Set to false to disable IP logging
}

// Au début du fichier, après les définitions de constantes
register_activation_hook(__FILE__, 'lwr_activate');
register_deactivation_hook(__FILE__, 'lwr_deactivate');

/**
 * Plugin activation hook
 */
function lwr_activate() {
    // Schedule the daily cleanup event if not already scheduled
    if (!wp_next_scheduled('lwr_daily_cleanup')) {
        wp_schedule_event(time(), 'daily', 'lwr_daily_cleanup');
    }
}

/**
 * Plugin deactivation hook
 */
function lwr_deactivate() {
    // Clear the scheduled event
    $timestamp = wp_next_scheduled('lwr_daily_cleanup');
    if ($timestamp) {
        wp_unschedule_event($timestamp, 'lwr_daily_cleanup');
    }
}

class Log_WP_Redirects
{
    public $query;
    public $redirect_data;
    public static $instance;


    function __construct() {

        // setup variables
        define( 'LWR_VERSION', '1.0.3' );
        define( 'LWR_DIR', dirname( __FILE__ ) );
        define( 'LWR_URL', plugins_url( '', __FILE__ ) );
        define( 'LWR_BASENAME', plugin_basename( __FILE__ ) );

        add_action( 'init', [ $this, 'init' ] );
        add_action( 'admin_menu', [ $this, 'admin_menu' ] );
        add_action( 'network_admin_menu', [ $this, 'network_admin_menu' ] );
        add_action( 'admin_enqueue_scripts', [ $this, 'admin_scripts' ] );
        add_action( 'lwr_daily_cleanup', [ $this, 'cleanup' ] );
        add_action( 'wp_ajax_lwr_query', [ $this, 'lwr_query' ] );
        add_action( 'wp_ajax_lwr_network_query', [ $this, 'lwr_network_query' ] );
        add_action( 'wp_ajax_lwr_clear', [ $this, 'lwr_clear' ] );
        add_action( 'wp_ajax_lwr_network_clear', [ $this, 'lwr_network_clear' ] );
        
        // Hook into wp_redirect function
        add_filter( 'wp_redirect', [ $this, 'capture_redirect' ], 999, 3 );
        add_filter( 'wp_redirect_status', [ $this, 'capture_status' ], 999, 2 );
    }


    public static function instance() {
        if ( ! isset( self::$instance ) ) {
            self::$instance = new self;
        }
        return self::$instance;
    }


    function init() {
        include( LWR_DIR . '/includes/class-upgrade.php' );
        include( LWR_DIR . '/includes/class-query.php' );

        new LWR_Upgrade();
        $this->query = new LWR_Query();
    }


    function cleanup() {
        global $wpdb;

        $now = current_time( 'timestamp' );
        $expires = apply_filters( 'lwr_expiration_days', 7 );
        $expires = date( 'Y-m-d H:i:s', strtotime( '-' . $expires . ' days', $now ) );
        $blog_id = $this->get_current_blog_id();
        
        $wpdb->query( $wpdb->prepare(
            "DELETE FROM {$wpdb->base_prefix}lwr_log WHERE date_added < %s AND blog_id = %d",
            $expires,
            $blog_id
        ));
    }


    function admin_menu() {
        add_management_page( 'Log WP Redirects', 'Log WP Redirects', 'manage_options', 'log-wp-redirects', [ $this, 'settings_page' ] );
    }
    
    
    function network_admin_menu() {
        if (!function_exists('is_multisite') || !is_multisite()) {
            return;
        }
        
        add_submenu_page(
            'settings.php', // Parent slug
            'Log WP Redirects', // Page title
            'Log WP Redirects', // Menu title
            'manage_network_options', // Capability
            'log-wp-redirects-network', // Menu slug
            [ $this, 'network_settings_page' ] // Callback function
        );
    }


    function settings_page() {
        include( LWR_DIR . '/templates/page-settings.php' );
    }
    
    
    function network_settings_page() {
        include( LWR_DIR . '/templates/page-network-settings.php' );
    }


    function admin_scripts( $hook ) {
        if ( 'tools_page_log-wp-redirects' == $hook || 'settings_page_log-wp-redirects-network' == $hook ) {
            wp_enqueue_script( 'lwr', LWR_URL . '/assets/js/admin.js', [ 'jquery' ] );
            wp_enqueue_style( 'lwr', LWR_URL . '/assets/css/admin.css' );
            wp_enqueue_style( 'media-views' );
        }
    }


    function validate() {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die();
        }

        check_ajax_referer( 'lwr_nonce' );
    }
    
    
    function validate_network() {
        if ( ! current_user_can( 'manage_network_options' ) ) {
            wp_die();
        }

        check_ajax_referer( 'lwr_network_nonce' );
    }


    function lwr_query() {
        $this->validate();

        $output = [
            'rows'  => LWR()->query->get_results( $_POST['data'] ),
            'pager' => LWR()->query->paginate()
        ];

        wp_send_json( $output );
    }
    
    
    function lwr_network_query() {
        $this->validate_network();

        $output = [
            'rows'  => LWR()->query->get_network_results( $_POST['data'] ),
            'pager' => LWR()->query->paginate()
        ];

        wp_send_json( $output );
    }


    function lwr_clear() {
        $this->validate();

        LWR()->query->truncate_table();
        wp_send_json_success();
    }
    
    
    function lwr_network_clear() {
        $this->validate_network();

        $blog_id = isset($_POST['data']['blog_id']) ? (int)$_POST['data']['blog_id'] : 0;
        LWR()->query->truncate_network_table($blog_id);
        wp_send_json_success();
    }


    function capture_redirect( $location, $status, $x_redirect_by = 'WordPress' ) {
        // Store the redirect location for later use
        $this->redirect_data = [
            'location' => $location,
            'status' => $status,
            'x_redirect_by' => $x_redirect_by,
            'referer' => isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : '',
            'request_uri' => isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '',
            'user_agent' => isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : '',
            'user_id' => get_current_user_id(),
            'ip_address' => LWR_LOG_IP ? $this->get_client_ip() : '',
            'cookies' => $this->get_cookie_names(),
            'backtrace' => $this->get_redirect_backtrace()
        ];
        
        return $location;
    }
    
    
    function capture_status( $status, $location ) {
        // Get the complete stack trace
        if (isset($this->redirect_data) && $this->redirect_data['location'] === $location) {
            // Log the redirect
            $this->log_redirect($location, $status);
            // Reset data
            $this->redirect_data = null;
        }
        
        return $status;
    }
    
    
    function get_redirect_backtrace() {
        $backtrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 10);
        $trace = [];
        
        foreach ($backtrace as $item) {
            if (!isset($item['file']) || strpos($item['file'], 'wp-content/plugins/log-wp-redirects') !== false) {
                continue;
            }
            
            $trace[] = [
                'file' => isset($item['file']) ? $item['file'] : '',
                'line' => isset($item['line']) ? $item['line'] : '',
                'function' => isset($item['function']) ? $item['function'] : '',
                'class' => isset($item['class']) ? $item['class'] : ''
            ];
        }
        
        return $trace;
    }
    
    
    /**
     * Get client IP address
     */
    function get_client_ip() {
        $ip = '';
        
        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            $ip = $_SERVER['HTTP_CLIENT_IP'];
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
        } elseif (!empty($_SERVER['REMOTE_ADDR'])) {
            $ip = $_SERVER['REMOTE_ADDR'];
        }
        
        return $ip;
    }
    
    
    /**
     * Get cookie names (not values)
     */
    function get_cookie_names() {
        if (empty($_COOKIE)) {
            return '';
        }
        
        return implode(', ', array_keys($_COOKIE));
    }
    
    
    /**
     * Get current blog ID for multisite
     */
    function get_current_blog_id() {
        if (function_exists('is_multisite') && is_multisite()) {
            return get_current_blog_id();
        }
        
        return 1; // Default blog ID for non-multisite
    }
    
    
    function log_redirect($location, $status) {
        global $wpdb;
        
        if (empty($location)) {
            return;
        }
        
        // Allow filtering redirects based on status code or other criteria
        $should_log = apply_filters('lwr_should_log_redirect', true, $status, $location, $this->redirect_data);
        
        if (!$should_log) {
            return;
        }
        
        $data = [
            'blog_id' => $this->get_current_blog_id(),
            'location' => $location,
            'status' => $status,
            'referer' => $this->redirect_data['referer'],
            'request_uri' => $this->redirect_data['request_uri'],
            'user_agent' => $this->redirect_data['user_agent'],
            'user_id' => $this->redirect_data['user_id'],
            'ip_address' => $this->redirect_data['ip_address'],
            'cookies' => $this->redirect_data['cookies'],
            'backtrace' => json_encode($this->redirect_data['backtrace']),
            'x_redirect_by' => isset($this->redirect_data['x_redirect_by']) ? $this->redirect_data['x_redirect_by'] : '',
            'date_added' => current_time('mysql')
        ];

        /**
         * Filter the data before inserting into database
         * 
         * @param array $data The data to be inserted
         * @param string $location The redirect location
         * @param int $status The redirect status code
         */
        $data = apply_filters('lwr_pre_insert_data', $data, $location, $status);
        
        // Log the redirect
        $wpdb->insert($wpdb->base_prefix . 'lwr_log', $data);
    }
    
    
    /**
     * Returns human readable time difference between given time and now
     *
     * @param string $time MySQL time string
     * @return string Human readable time difference
     */
    function time_since( $time ) {
        return human_time_diff( strtotime( $time ), current_time( 'timestamp' ) );
    }
}


function LWR() {
    return Log_WP_Redirects::instance();
}


LWR(); 