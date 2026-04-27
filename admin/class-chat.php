<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Classe Chat — widget AI con tool use nel back-office
 */
class Jeenie_Chat {

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
        add_action( 'wp_ajax_jeenie_chat', [ $this, 'ajax_chat' ] );
        add_action( 'wp_ajax_jeenie_chat_stream', [ $this, 'ajax_chat_stream' ] );
        add_action( 'wp_ajax_jeenie_get_conversations', [ $this, 'ajax_get_conversations' ] );
        add_action( 'wp_ajax_jeenie_load_conversation', [ $this, 'ajax_load_conversation' ] );
        add_action( 'wp_ajax_jeenie_delete_conversation', [ $this, 'ajax_delete_conversation' ] );
        add_action( 'wp_ajax_jeenie_new_conversation', [ $this, 'ajax_new_conversation' ] );
    }

    public function enqueue_assets() {
        wp_enqueue_style( 'fontawesome', JEENIE_PLUGIN_URL . 'assets/vendor/fontawesome.min.css', [], '6.5.1' );

        wp_enqueue_style( 'jeenie-chat', JEENIE_PLUGIN_URL . 'assets/css/chat.css', [], JEENIE_VERSION );
        wp_enqueue_script( 'marked', JEENIE_PLUGIN_URL . 'assets/vendor/marked.min.js', [], '16.3.0', true );
        wp_enqueue_script( 'jeenie-chat', JEENIE_PLUGIN_URL . 'assets/js/chat.js', [ 'jquery', 'marked' ], JEENIE_VERSION, true );

        wp_localize_script( 'jeenie-chat', 'jeenie_chat', [
            'ajax_url'   => admin_url( 'admin-ajax.php' ),
            'nonce'      => wp_create_nonce( 'jeenie_nonce' ),
            'session_id' => wp_get_session_token(),
        ]);
    }

    public function render_chat_widget() {
        require_once JEENIE_PLUGIN_DIR . 'templates/chat-widget.php';
    }

    /**
     * AJAX: gestisce il messaggio della chat con tool use
     */
    public function ajax_chat() {
        check_ajax_referer( 'jeenie_nonce', 'nonce' );
        if ( ! current_user_can( 'edit_posts' ) ) wp_send_json_error( __( 'Permessi insufficienti.', 'jeenie' ) );

        $message = sanitize_textarea_field( wp_unslash( $_POST['message'] ?? '' ) );
        if ( empty( $message ) ) wp_send_json_error( __( 'Messaggio vuoto.', 'jeenie' ) );

        $user_id         = get_current_user_id();
        $conversation_id = intval( $_POST['conversation_id'] ?? 0 );

        // Ricostruisci history dal DB (sicuro) oppure usa array vuoto per nuova chat
        $clean_history = [];
        if ( $conversation_id ) {
            $db_messages = Jeenie_History::get_messages( $conversation_id, $user_id );
            foreach ( $db_messages as $msg ) {
                $clean_history[] = [
                    'role'  => $msg['role'] === 'model' ? 'model' : 'user',
                    'parts' => [ [ 'text' => $msg['content'] ] ],
                ];
            }
        }

        $connector = Jeenie_Admin::get_connector();
        if ( ! $connector ) wp_send_json_error( __( 'API key non configurata. Vai in Jeenie → Impostazioni.', 'jeenie' ) );

        if ( Jeenie_Admin::is_rate_limited() ) wp_send_json_error( __( 'Hai raggiunto il limite di richieste orarie. Riprova più tardi.', 'jeenie' ) );

        $response = $connector->generate_with_tools( $clean_history, $message );
        if ( ! $response['success'] ) wp_send_json_error( $response['error'] );

        // Crea conversazione se non esiste
        if ( ! $conversation_id ) {
            $conversation_id = Jeenie_History::create_conversation( $user_id, $message );
        }

        // Salva messaggi nel DB
        Jeenie_History::save_message( $conversation_id, 'user', $message );
        Jeenie_History::save_message( $conversation_id, 'model', $response['text'] );

        // History per Gemini (non più inviata al client)

        wp_send_json_success( [
            'text'            => $response['text'],
            'conversation_id' => $conversation_id,
            'action_taken'    => $response['action_taken'] ?? null,
        ]);
    }

    /**
     * AJAX SSE: chat con streaming della risposta finale.
     * Il tool calling avviene normalmente, poi la risposta testuale viene streamata.
     */
    public function ajax_chat_stream() {
        check_ajax_referer( 'jeenie_nonce', 'nonce' );
        if ( ! current_user_can( 'edit_posts' ) ) {
            $this->sse_error( __( 'Permessi insufficienti.', 'jeenie' ) );
            return;
        }

        $message = sanitize_textarea_field( wp_unslash( $_GET['message'] ?? '' ) );
        if ( empty( $message ) ) {
            $this->sse_error( __( 'Messaggio vuoto.', 'jeenie' ) );
            return;
        }

        $user_id         = get_current_user_id();
        $conversation_id = intval( $_GET['conversation_id'] ?? 0 );

        $clean_history = [];
        if ( $conversation_id ) {
            $db_messages = Jeenie_History::get_messages( $conversation_id, $user_id );
            foreach ( $db_messages as $msg ) {
                $clean_history[] = [
                    'role'  => $msg['role'] === 'model' ? 'model' : 'user',
                    'parts' => [ [ 'text' => $msg['content'] ] ],
                ];
            }
        }

        $connector = Jeenie_Admin::get_connector();
        if ( ! $connector ) {
            $this->sse_error( __( 'API key non configurata.', 'jeenie' ) );
            return;
        }

        if ( Jeenie_Admin::is_rate_limited() ) {
            $this->sse_error( __( 'Limite richieste orarie raggiunto.', 'jeenie' ) );
            return;
        }

        // Fase 1: tool calling normale (non streamato)
        $page_context = '';
        if ( ! empty( $_GET['page_context'] ) ) {
            $ctx = json_decode( sanitize_textarea_field( wp_unslash( $_GET['page_context'] ) ), true );
            if ( $ctx && ( ! empty( $ctx['title'] ) || ! empty( $ctx['content'] ) ) ) {
                $page_context = "\n\n[CONTESTO: l'utente sta lavorando su un post";
                if ( ! empty( $ctx['title'] ) )   $page_context .= ' con titolo "' . sanitize_text_field( $ctx['title'] ) . '"';
                if ( ! empty( $ctx['content'] ) )  $page_context .= '. Contenuto attuale: ' . mb_strimwidth( sanitize_textarea_field( $ctx['content'] ), 0, 1500, '...' );
                $page_context .= ']';
            }
        }

        // Knowledge base: cerca frammenti rilevanti
        $kb_context = '';
        if ( get_option( 'jeenie_knowledge_enabled', 1 ) ) {
            $max_chars  = (int) get_option( 'jeenie_knowledge_max_chars', 1500 );
            $kb_results = Jeenie_Knowledge::search( $message, $max_chars );
            if ( $kb_results ) {
                $kb_context = "\n\n[KNOWLEDGE BASE - usa queste informazioni se pertinenti alla domanda:\n" . $kb_results . ']';
            }
        }

        $response = $connector->generate_with_tools( $clean_history, $message . $page_context . $kb_context );
        if ( ! $response['success'] ) {
            $this->sse_error( $response['error'] );
            return;
        }

        // Salva nel DB
        if ( ! $conversation_id ) {
            $conversation_id = Jeenie_History::create_conversation( $user_id, $message );
        }
        Jeenie_History::save_message( $conversation_id, 'user', $message );
        Jeenie_History::save_message( $conversation_id, 'model', $response['text'] );

        // Fase 2: invia header SSE e streama la risposta
        header( 'Content-Type: text/event-stream' );
        header( 'Cache-Control: no-cache' );
        header( 'X-Accel-Buffering: no' );

        // Manda prima i metadati (conversation_id, action_taken)
        $meta = [
            'meta'            => true,
            'conversation_id' => $conversation_id,
            'action_taken'    => $response['action_taken'] ?? null,
        ];
        echo "data: " . wp_json_encode( $meta ) . "\n\n";
        if ( ob_get_level() ) ob_flush();
        flush();

        // Streama il testo della risposta in chunk simulati
        $text   = $response['text'];
        $words  = preg_split( '/([ \n])/', $text, -1, PREG_SPLIT_DELIM_CAPTURE );
        $buffer = '';
        $count  = 0;

        foreach ( $words as $word ) {
            $buffer .= $word;
            $count++;
            if ( $count >= 2 ) {
                echo "data: " . wp_json_encode( [ 'chunk' => $buffer ] ) . "\n\n";
                if ( ob_get_level() ) ob_flush();
                flush();
                $buffer = '';
                $count  = 0;
                usleep( 40000 ); // 40ms tra i chunk
            }
        }
        if ( $buffer !== '' ) {
            echo "data: " . wp_json_encode( [ 'chunk' => $buffer ] ) . "\n\n";
            if ( ob_get_level() ) ob_flush();
            flush();
        }

        echo "data: [DONE]\n\n";
        if ( ob_get_level() ) ob_flush();
        flush();
        exit;
    }

    /**
     * Helper: manda un errore via SSE e chiude
     */
    private function sse_error( string $message ): void {
        header( 'Content-Type: text/event-stream' );
        header( 'Cache-Control: no-cache' );
        echo "data: " . wp_json_encode( [ 'error' => $message ] ) . "\n\n";
        echo "data: [DONE]\n\n";
        flush();
        exit;
    }

    /**
     * AJAX: lista conversazioni dell'utente
     */
    public function ajax_get_conversations() {
        check_ajax_referer( 'jeenie_nonce', 'nonce' );
        if ( ! current_user_can( 'edit_posts' ) ) wp_send_json_error( 'Permessi insufficienti.' );

        $conversations = Jeenie_History::get_conversations( get_current_user_id() );
        wp_send_json_success( $conversations );
    }

    /**
     * AJAX: carica messaggi di una conversazione
     */
    public function ajax_load_conversation() {
        check_ajax_referer( 'jeenie_nonce', 'nonce' );
        if ( ! current_user_can( 'edit_posts' ) ) wp_send_json_error( 'Permessi insufficienti.' );

        $conversation_id = intval( $_POST['conversation_id'] ?? 0 );
        if ( ! $conversation_id ) wp_send_json_error( 'ID conversazione mancante.' );

        $messages = Jeenie_History::get_messages( $conversation_id, get_current_user_id() );
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
        check_ajax_referer( 'jeenie_nonce', 'nonce' );
        if ( ! current_user_can( 'edit_posts' ) ) wp_send_json_error( 'Permessi insufficienti.' );

        $conversation_id = intval( $_POST['conversation_id'] ?? 0 );
        $deleted = Jeenie_History::delete_conversation( $conversation_id, get_current_user_id() );

        $deleted ? wp_send_json_success( 'Eliminata.' ) : wp_send_json_error( 'Errore.' );
    }

    /**
     * AJAX: nuova conversazione (reset chat)
     */
    public function ajax_new_conversation() {
        check_ajax_referer( 'jeenie_nonce', 'nonce' );
        if ( ! current_user_can( 'edit_posts' ) ) wp_send_json_error( 'Permessi insufficienti.' );
        wp_send_json_success( [ 'conversation_id' => 0 ] );
    }
}
