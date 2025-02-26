<?php
/**
 * Plugin Name: HCX IP Calculator
 * Plugin URI: https://dinocloud.net/hcx-ip-calculator
 * Description: A dynamic calculator for HCX IP requirements.
 * Version: 2.0
 * Author: Matthew Webb
 * Author URI: https://dinocloud.net
 * License: GPL2
 */

if (!defined('ABSPATH')) {
    exit; // Prevent direct access
}

// Define plugin path constants
define('HCX_IP_CALCULATOR_PATH', plugin_dir_path(__FILE__));
define('HCX_IP_CALCULATOR_URL', plugin_dir_url(__FILE__));

// Enqueue styles & scripts
function hcx_ip_calculator_enqueue_assets() {
    wp_enqueue_style('hcx-ip-calculator-css', HCX_IP_CALCULATOR_URL . 'assets/css/styles.css', array(), '1.0.0');
    wp_enqueue_script('hcx-ip-calculator-js', HCX_IP_CALCULATOR_URL . 'assets/js/script.js', array('jquery'), '1.0.0', true);
    wp_localize_script('hcx-ip-calculator-js', 'hcx_ajax', array('ajax_url' => admin_url('admin-ajax.php')));
}
add_action('wp_enqueue_scripts', 'hcx_ip_calculator_enqueue_assets');

// Shortcode to display the calculator
function hcx_ip_calculator_shortcode() {
    ob_start();
    include HCX_IP_CALCULATOR_PATH . 'templates/form.php';
    return ob_get_clean();
}
add_shortcode('hcx_ip_calculator', 'hcx_ip_calculator_shortcode');

// Include AJAX handler & calculations
require_once HCX_IP_CALCULATOR_PATH . 'includes/ajax-handler.php';
require_once HCX_IP_CALCULATOR_PATH . 'includes/calculations.php';
