<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Classe Chat — widget AI con tool use nel back-office
 */
class SiteGenie_Chat {

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
        add_action( 'wp_ajax_sitegenie_chat', [ $this, 'ajax_chat' ] );
        add_action( 'wp_ajax_sitegenie_get_conversations', [ $this, 'ajax_get_conversations' ] );
        add_action( 'wp_ajax_sitegenie_load_conversation', [ $this, 'ajax_load_conversation' ] );
        add_action( 'wp_ajax_sitegenie_delete_conversation', [ $this, 'ajax_delete_conversation' ] );
        add_action( 'wp_ajax_sitegenie_new_conversation', [ $this, 'ajax_new_conversation' ] );
    }

    public function enqueue_assets() {
        wp_enqueue_style( 'bootstrap', SITEGENIE_PLUGIN_URL . 'assets/vendor/bootstrap.min.css', [], '5.3.3' );
        wp_enqueue_style( 'fontawesome', SITEGENIE_PLUGIN_URL . 'assets/vendor/fontawesome.min.css', [], '6.5.1' );

        wp_enqueue_style( 'sitegenie-chat', SITEGENIE_PLUGIN_URL . 'assets/css/chat.css', [], SITEGENIE_VERSION );
        wp_enqueue_script( 'sitegenie-chat', SITEGENIE_PLUGIN_URL . 'assets/js/chat.js', [ 'jquery' ], SITEGENIE_VERSION, true );

        wp_localize_script( 'sitegenie-chat', 'sitegenie_chat', [
            'ajax_url'   => admin_url( 'admin-ajax.php' ),
            'nonce'      => wp_create_nonce( 'sitegenie_nonce' ),
            'session_id' => wp_get_session_token(),
        ]);
    }

    public function render_chat_widget() {
        require_once SITEGENIE_PLUGIN_DIR . 'templates/chat-widget.php';
    }

    /**
     * AJAX: gestisce il messaggio della chat con tool use
     */
    public function ajax_chat() {
        check_ajax_referer( 'sitegenie_nonce', 'nonce' );
        if ( ! current_user_can( 'edit_posts' ) ) wp_send_json_error( __( 'Permessi insufficienti.', 'sitegenie' ) );

        $message = sanitize_textarea_field( wp_unslash( $_POST['message'] ?? '' ) );
        if ( empty( $message ) ) wp_send_json_error( __( 'Messaggio vuoto.', 'sitegenie' ) );

        $user_id         = get_current_user_id();
        $conversation_id = intval( $_POST['conversation_id'] ?? 0 );

        // Ricostruisci history dal DB (sicuro) oppure usa array vuoto per nuova chat
        $clean_history = [];
        if ( $conversation_id ) {
            $db_messages = SiteGenie_History::get_messages( $conversation_id, $user_id );
            foreach ( $db_messages as $msg ) {
                $clean_history[] = [
                    'role'  => $msg['role'] === 'model' ? 'model' : 'user',
                    'parts' => [ [ 'text' => $msg['content'] ] ],
                ];
            }
        }

        $connector = SiteGenie_Admin::get_connector();
        if ( ! $connector ) wp_send_json_error( __( 'API key non configurata. Vai in SiteGenie → Impostazioni.', 'sitegenie' ) );

        if ( SiteGenie_Admin::is_rate_limited() ) wp_send_json_error( __( 'Hai raggiunto il limite di richieste orarie. Riprova più tardi.', 'sitegenie' ) );

        $response = $connector->generate_with_tools( $clean_history, $message );
        if ( ! $response['success'] ) wp_send_json_error( $response['error'] );

        // Crea conversazione se non esiste
        if ( ! $conversation_id ) {
            $conversation_id = SiteGenie_History::create_conversation( $user_id, $message );
        }

        // Salva messaggi nel DB
        SiteGenie_History::save_message( $conversation_id, 'user', $message );
        SiteGenie_History::save_message( $conversation_id, 'model', $response['text'] );

        // History per Gemini (non più inviata al client)

        wp_send_json_success( [
            'text'            => $response['text'],
            'conversation_id' => $conversation_id,
            'action_taken'    => $response['action_taken'] ?? null,
        ]);
    }

    /**
     * AJAX: lista conversazioni dell'utente
     */
    public function ajax_get_conversations() {
        check_ajax_referer( 'sitegenie_nonce', 'nonce' );
        if ( ! current_user_can( 'edit_posts' ) ) wp_send_json_error( 'Permessi insufficienti.' );

        $conversations = SiteGenie_History::get_conversations( get_current_user_id() );
        wp_send_json_success( $conversations );
    }

    /**
     * AJAX: carica messaggi di una conversazione
     */
    public function ajax_load_conversation() {
        check_ajax_referer( 'sitegenie_nonce', 'nonce' );
        if ( ! current_user_can( 'edit_posts' ) ) wp_send_json_error( 'Permessi insufficienti.' );

        $conversation_id = intval( $_POST['conversation_id'] ?? 0 );
        if ( ! $conversation_id ) wp_send_json_error( 'ID conversazione mancante.' );

        $messages = SiteGenie_History::get_messages( $conversation_id, get_current_user_id() );
        if ( empty( $messages ) ) wp_send_json_error( 'Conversazione non trovata.' );

        // Ricostruisci history per Gemini
        $history = [];
        foreach ( $messages as $msg ) {
            $history[] = [
                'role'  => $msg['role'],
                'parts' => [ [ 'text' => $msg['content'] ] ],
            ];
        }

        wp_send_json_success( [
            'messages' => $messages,
            'history'  => $history,
        ]);
    }

    /**
     * AJAX: elimina conversazione
     */
    public function ajax_delete_conversation() {
        check_ajax_referer( 'sitegenie_nonce', 'nonce' );
        if ( ! current_user_can( 'edit_posts' ) ) wp_send_json_error( 'Permessi insufficienti.' );

        $conversation_id = intval( $_POST['conversation_id'] ?? 0 );
        $deleted = SiteGenie_History::delete_conversation( $conversation_id, get_current_user_id() );

        $deleted ? wp_send_json_success( 'Eliminata.' ) : wp_send_json_error( 'Errore.' );
    }

    /**
     * AJAX: nuova conversazione (reset chat)
     */
    public function ajax_new_conversation() {
        check_ajax_referer( 'sitegenie_nonce', 'nonce' );
        if ( ! current_user_can( 'edit_posts' ) ) wp_send_json_error( 'Permessi insufficienti.' );
        wp_send_json_success( [ 'conversation_id' => 0 ] );
    }
}
