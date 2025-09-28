<?php
/**
 * Plugin Name: Return Button Plugin
 * Plugin URI: https://yourwebsite.com/
 * Description: A WooCommerce extension that adds return request functionality to customer orders.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 * Requires at least: 5.0
 * Tested up to: 6.3
 * WC requires at least: 4.0
 * WC tested up to: 8.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('RETURN_BUTTON_PLUGIN_URL', plugin_dir_url(__FILE__));
define('RETURN_BUTTON_PLUGIN_PATH', plugin_dir_path(__FILE__));
define('RETURN_BUTTON_PLUGIN_VERSION', '1.0.0');

class ReturnButtonPlugin {
    
    public function __construct() {
        add_action('plugins_loaded', array($this, 'init'));
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
    }
    
    /**
     * Initialize the plugin
     */
    public function init() {
        // Check if WooCommerce is active
        if (!$this->is_woocommerce_active()) {
            add_action('admin_notices', array($this, 'woocommerce_missing_notice'));
            return;
        }
        
        // Load plugin components
        $this->load_dependencies();
        $this->init_hooks();
    }
    
    /**
     * Check if WooCommerce is active
     */
    private function is_woocommerce_active() {
        return class_exists('WooCommerce');
    }
    
    /**
     * Show admin notice if WooCommerce is not active
     */
    public function woocommerce_missing_notice() {
        echo '<div class="error"><p><strong>Return Button Plugin</strong> requires WooCommerce to be installed and active.</p></div>';
    }
    
    /**
     * Load plugin dependencies
     */
    private function load_dependencies() {
        require_once RETURN_BUTTON_PLUGIN_PATH . 'includes/class-return-request-handler.php';
        require_once RETURN_BUTTON_PLUGIN_PATH . 'includes/class-return-request-admin.php';
        require_once RETURN_BUTTON_PLUGIN_PATH . 'includes/class-return-request-database.php';
        require_once RETURN_BUTTON_PLUGIN_PATH . 'includes/class-return-request-email.php';
    }
    
    /**
     * Initialize hooks
     */
    private function init_hooks() {
        // Enqueue scripts and styles
        add_action('wp_enqueue_scripts', array($this, 'enqueue_frontend_scripts'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
        
        // Initialize classes
        new Return_Request_Handler();
        new Return_Request_Admin();
        new Return_Request_Email();
    }
    
    /**
     * Enqueue frontend scripts and styles
     */
    public function enqueue_frontend_scripts() {
        if (is_account_page()) {
            wp_enqueue_style('return-button-style', RETURN_BUTTON_PLUGIN_URL . 'assets/css/frontend.css', array(), RETURN_BUTTON_PLUGIN_VERSION);
            wp_enqueue_script('return-button-script', RETURN_BUTTON_PLUGIN_URL . 'assets/js/frontend.js', array('jquery'), RETURN_BUTTON_PLUGIN_VERSION, true);
            
            // Localize script for AJAX
            wp_localize_script('return-button-script', 'return_button_ajax', array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('return_request_nonce'),
                'messages' => array(
                    'success' => __('Return request submitted successfully!', 'return-button'),
                    'error' => __('Something went wrong. Please try again.', 'return-button'),
                )
            ));
        }
    }
    
    /**
     * Enqueue admin scripts and styles
     */
    public function enqueue_admin_scripts($hook) {
        if (strpos($hook, 'return-requests') !== false) {
            wp_enqueue_style('return-button-admin-style', RETURN_BUTTON_PLUGIN_URL . 'assets/css/admin.css', array(), RETURN_BUTTON_PLUGIN_VERSION);
            wp_enqueue_script('return-button-admin-script', RETURN_BUTTON_PLUGIN_URL . 'assets/js/admin.js', array('jquery'), RETURN_BUTTON_PLUGIN_VERSION, true);
        }
    }
    
    /**
     * Plugin activation
     */
    public function activate() {
        // Create database table
        Return_Request_Database::create_table();
        
        // Set default options
        add_option('return_button_return_period_days', 10);
        add_option('return_button_grace_period_days', 10);
    }
    
    /**
     * Plugin deactivation
     */
    public function deactivate() {
        // Clean up if needed
    }
}

// Initialize the plugin
new ReturnButtonPlugin();