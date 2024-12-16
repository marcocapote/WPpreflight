<?php 
/**
 * Plugin Name: WPpreflight
 * Description: Um plugin para checagem de preflight.
 * Version: 1.0
 * Author: marcocapote
 */

require_once __DIR__ . '/includes/functions.php';


 function WPpreflight_shortcode() {
    // Obtém o valor da página a partir do parâmetro GET (ou usa "alteracao" como padrão)

    ob_start();
        include plugin_dir_path(__FILE__) . 'views/preflight.php';
    return ob_get_clean();
}
add_shortcode('WPpreflight', 'WPpreflight_shortcode');