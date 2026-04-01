<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Classe Chat — widget AI con tool use nel back-office
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
        add_action( 'admin_footer',           [ $this, 'render_chat_widget' ] );
        add_action( 'admin_enqueue_scripts',  [ $this, 'enqueue_assets' ] );
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
            'ajax_url'   => admin_url( 'admin-ajax.php' ),
            'nonce'      => wp_create_nonce( 'chatpress_nonce' ),
            'session_id' => wp_get_session_token(),
        ]);
    }

    public function render_chat_widget() {
        require_once CHATPRESS_PLUGIN_DIR . 'templates/chat-widget.php';
    }

    /**
     * AJAX: gestisce il messaggio della chat con tool use
     */
    public function ajax_chat() {
        check_ajax_referer( 'chatpress_nonce', 'nonce' );

        if ( ! current_user_can( 'edit_posts' ) ) {
            wp_send_json_error( 'Permessi insufficienti.' );
        }

        $message = sanitize_textarea_field( $_POST['message'] ?? '' );
        if ( empty( $message ) ) {
            wp_send_json_error( 'Messaggio vuoto.' );
        }

        // Recupera storico conversazione dalla sessione (serializzato in POST)
        $raw_history = $_POST['history'] ?? '[]';
        $history     = json_decode( stripslashes( $raw_history ), true );
        if ( ! is_array( $history ) ) $history = [];

        // Sanifica la history: tieni solo i campi necessari
        $clean_history = [];
        foreach ( $history as $turn ) {
            if ( isset( $turn['role'], $turn['parts'] ) ) {
                $clean_history[] = [
                    'role'  => in_array( $turn['role'], [ 'user', 'model' ] ) ? $turn['role'] : 'user',
                    'parts' => $turn['parts'],
                ];
            }
        }

        $connector = ChatPress_Admin::get_connector();
        if ( ! $connector ) {
            wp_send_json_error( 'API key non configurata. Vai in ChatPress → Impostazioni.' );
        }

        $response = $connector->generate_with_tools( $clean_history, $message );

        if ( ! $response['success'] ) {
            wp_send_json_error( $response['error'] );
        }

        // Prepara le nuove voci di history da restituire al frontend
        $new_history = $clean_history;
        $new_history[] = [ 'role' => 'user',  'parts' => [ [ 'text' => $message ] ] ];
        $new_history[] = [ 'role' => 'model', 'parts' => [ [ 'text' => $response['text'] ] ] ];

        wp_send_json_success( [
            'text'         => $response['text'],
            'history'      => $new_history,
            'action_taken' => $response['action_taken'] ?? null,
        ]);
    }
}
