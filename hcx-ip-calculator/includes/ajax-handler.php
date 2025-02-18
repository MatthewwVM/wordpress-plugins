<?php
if (!defined('ABSPATH')) exit; // Prevent direct access

add_action('wp_ajax_generate_network_containers', 'generate_network_containers');
add_action('wp_ajax_nopriv_generate_network_containers', 'generate_network_containers');

function generate_network_containers() {
    $num_containers = intval($_POST['num_containers']);
    $output = "";

    for ($i = 1; $i <= $num_containers; $i++) {
        $output .= '
            <div class="network-container">
                <label>Network container ' . $i . '</label>
                <input type="number" class="networks-concurrent" min="1">
            </div>';
    }

    echo $output;
    wp_die();
}
