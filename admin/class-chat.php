<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Classe Chat — widget AI accessibile da tutto il back-office
 */
class ChatPress_Chat {

    private static $instance = null;

    public static function get_instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action( 'admin_footer', [ $this, 'render_chat_widget' ] );
        add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_assets' ] );
        add_action( 'wp_ajax_chatpress_chat', [ $this, 'ajax_chat' ] );
    }

    public function enqueue_assets() {
        wp_enqueue_style(
            'chatpress-chat',
            CHATPRESS_PLUGIN_URL . 'assets/css/chat.css',
            [],
            CHATPRESS_VERSION
        );

        wp_enqueue_script(
            'chatpress-chat',
            CHATPRESS_PLUGIN_URL . 'assets/js/chat.js',
            [ 'jquery' ],
            CHATPRESS_VERSION,
            true
        );

        wp_localize_script( 'chatpress-chat', 'chatpress_chat', [
            'ajax_url' => admin_url( 'admin-ajax.php' ),
            'nonce'    => wp_create_nonce( 'chatpress_nonce' ),
        ]);
    }

    public function render_chat_widget() {
        require_once CHATPRESS_PLUGIN_DIR . 'templates/chat-widget.php';
    }

    /**
     * AJAX: risponde al messaggio in chat
     */
    public function ajax_chat() {
        check_ajax_referer( 'chatpress_nonce', 'nonce' );
        if ( ! current_user_can( 'edit_posts' ) ) wp_send_json_error( 'Permessi insufficienti.' );

        $message = sanitize_textarea_field( $_POST['message'] ?? '' );
        if ( empty( $message ) ) wp_send_json_error( 'Messaggio vuoto.' );

        $connector = ChatPress_Admin::get_connector();
        if ( ! $connector ) wp_send_json_error( 'API key non configurata. Vai in ChatPress → Impostazioni.' );

        $context = ChatPress_Admin::get_site_context();
        $prompt  = "$context\n\nSei un assistente AI integrato nel pannello di amministrazione WordPress. ";
        $prompt .= "Aiuta l'utente con domande sul sito, generazione contenuti, e suggerimenti. ";
        $prompt .= "Rispondi in modo conciso e pratico.\n\nUtente: $message";

        $response = $connector->generate( $prompt, [ 'max_tokens' => 800 ] );

        if ( $response['success'] ) {
            wp_send_json_success( [ 'text' => $response['text'] ] );
        } else {
            wp_send_json_error( $response['error'] );
        }
    }
}