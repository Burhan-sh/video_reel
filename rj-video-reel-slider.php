<?php
/*
Plugin Name: RJ Video Reel Slider
Description: A beautiful video reel slider with popup functionality
Version: 1.0
Author: RJ
*/

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

// Define plugin constants
define('RJVRS_VERSION', '1.0.0');
define('RJVRS_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('RJVRS_PLUGIN_URL', plugin_dir_url(__FILE__));

// Include required files
require_once RJVRS_PLUGIN_DIR . 'includes/class-rj-video-center.php';
require_once RJVRS_PLUGIN_DIR . 'includes/class-rj-video-slider-shortcode.php';

// Initialize the plugin
class RJ_Video_Reel_Slider {
    private static $instance = null;

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action('init', array($this, 'init'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
    }

    public function init() {
        // Initialize Video Center
        RJ_Video_Center::get_instance();
        // Initialize Shortcode
        RJ_Video_Slider_Shortcode::get_instance();
    }

    public function enqueue_scripts() {
        // Enqueue Swiper JS
        wp_enqueue_style('swiper-css', 'https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.css', array(), RJVRS_VERSION);
        wp_enqueue_script('swiper-js', 'https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.js', array('jquery'), RJVRS_VERSION, true);
        
        // Enqueue plugin styles and scripts
        wp_enqueue_style('rjvrs-styles', RJVRS_PLUGIN_URL . 'assets/css/rj-video-slider.css', array(), RJVRS_VERSION);
        wp_enqueue_script('rjvrs-scripts', RJVRS_PLUGIN_URL . 'assets/js/rj-video-slider.js', array('jquery', 'swiper-js'), RJVRS_VERSION, true);
    }
}

// Initialize the plugin
RJ_Video_Reel_Slider::get_instance(); 