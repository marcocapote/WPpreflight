<?php 
/**
 * Plugin Name: WPpreflight
 * Description: Um plugin para checagem de preflight.
 * Version: 1.0
 * Author: marcocapote
 */

require_once __DIR__ . '/includes/functions.php';

$functions = new Functions();
 function WPpreflight_shortcode() {
    // Obtém o valor da página a partir do parâmetro GET (ou usa "alteracao" como padrão)

    ob_start();
        include plugin_dir_path(__FILE__) . 'views/preflight.php';
    return ob_get_clean();
}
add_shortcode('WPpreflight', 'WPpreflight_shortcode');


add_action('rest_api_init', function () {
    register_rest_route('wppreflight/v1', '/process-file', [
        'methods' => 'POST',
        'callback' => 'WPpreflight_process_file',
        'permission_callback' => '__return_true',
    ]);
});


