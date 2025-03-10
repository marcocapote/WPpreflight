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



function WPpreflight_adicionar_scripts() {
    // Enfileirar o script JavaScript
    wp_enqueue_script(
        'wppreflight-script', // Nome do script
        plugins_url('js/script.js', __FILE__), // Caminho do arquivo JS
        array('jquery'), // Dependências (jQuery é necessário para o AJAX do WordPress)
        '1.0', // Versão
        true // Carregar no footer
    );

    // Localizar a URL de admin-ajax.php para uso no JavaScript
    wp_localize_script(
        'wppreflight-script',
        'wppreflight_ajax',
        array(
            'ajax_url' => admin_url('admin-ajax.php'), // URL do admin-ajax.php
            'nonce' => wp_create_nonce('wppreflight_nonce') // Criar um nonce para segurança
        )
    );
}
add_action('wp_enqueue_scripts', 'WPpreflight_adicionar_scripts');



add_action( 'wp_ajax_retornar_relatorio', [$functions, 'retornar_relatorio'] );
add_action( 'wp_ajax_nopriv_retornar_relatorio', [$functions, 'retornar_relatorio'] );


