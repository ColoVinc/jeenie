<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Classe Admin — gestisce il pannello di impostazioni nel WP Admin
 */
class Jeenie_Admin {

    private static $instance = null;

    public static function get_instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action( 'admin_menu', [ $this, 'register_menu' ] );
        add_action( 'admin_init', [ $this, 'register_settings' ] );
        add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_assets' ] );
        add_action( 'admin_notices', [ $this, 'activation_notice' ] );
        add_action( 'admin_notices', [ $this, 'missing_key_notice' ] );
        add_action( 'wp_ajax_jeenie_test_api', [ $this, 'ajax_test_api' ] );
        add_action( 'wp_ajax_jeenie_clear_logs', [ $this, 'ajax_clear_logs' ] );
        add_action( 'wp_ajax_jeenie_upload_knowledge', [ $this, 'ajax_upload_knowledge' ] );
        add_action( 'wp_ajax_jeenie_delete_knowledge', [ $this, 'ajax_delete_knowledge' ] );
        add_action( 'wp_ajax_jeenie_index_posts', [ $this, 'ajax_index_posts' ] );
        add_action( 'wp_ajax_jeenie_generate_alt', [ $this, 'ajax_generate_alt' ] );
        add_action( 'wp_ajax_jeenie_toggle_component', [ $this, 'ajax_toggle_component' ] );
        add_action( 'wp_ajax_jeenie_delete_component', [ $this, 'ajax_delete_component' ] );
        add_filter( 'attachment_fields_to_edit', [ $this, 'add_alt_button_to_media' ], 10, 2 );
    }

    /**
     * Notice dopo attivazione plugin
     */
    public function activation_notice() {
        if ( ! get_transient( 'jeenie_activated' ) ) return;
        delete_transient( 'jeenie_activated' );
        $url = admin_url( 'admin.php?page=jeenie' );
        echo '<div class="notice notice-success is-dismissible"><p><strong>🤖 ' . esc_html__( 'Jeenie attivato!', 'jeenie' ) . '</strong> <a href="' . esc_url( $url ) . '">' . esc_html__( 'Configura la tua API key', 'jeenie' ) . '</a> ' . esc_html__( 'per iniziare.', 'jeenie' ) . '</p></div>';
    }

    /**
     * Notice se nessuna API key è configurata
     */
    public function missing_key_notice() {
        if ( get_transient( 'jeenie_activated' ) ) return;
        $screen = get_current_screen();
        if ( $screen && strpos( $screen->id, 'jeenie' ) !== false ) return;

        $provider = get_option( 'jeenie_default_provider', 'gemini' );
        $key      = get_option( 'jeenie_' . $provider . '_api_key', '' );
        if ( ! empty( $key ) ) return;

        $url = admin_url( 'admin.php?page=jeenie' );
        echo '<div class="notice notice-warning is-dismissible"><p><strong>🤖 Jeenie:</strong> ' . esc_html__( 'API key non configurata.', 'jeenie' ) . ' <a href="' . esc_url( $url ) . '">' . esc_html__( 'Vai alle impostazioni', 'jeenie' ) . '</a>.</p></div>';
    }

    /**
     * Registra le voci di menu nel WP Admin
     */
    public function register_menu() {
        add_menu_page(
            'Jeenie',
            'Jeenie',
            'manage_options',
            'jeenie',
            [ $this, 'render_settings_page' ],
            'dashicons-superhero',
            30
        );

        add_submenu_page(
            'jeenie',
            'Impostazioni',
            'Impostazioni',
            'manage_options',
            'jeenie',
            [ $this, 'render_settings_page' ]
        );

        add_submenu_page(
            'jeenie',
            'Log Chiamate',
            'Log Chiamate',
            'manage_options',
            'jeenie-logs',
            [ $this, 'render_logs_page' ]
        );

        add_submenu_page(
            'jeenie',
            'Knowledge Base',
            'Knowledge Base',
            'manage_options',
            'jeenie-knowledge',
            [ $this, 'render_knowledge_page' ]
        );

        add_submenu_page(
            'jeenie',
            'Componenti',
            'Componenti',
            'manage_options',
            'jeenie-components',
            [ $this, 'render_components_page' ]
        );
    }

    /**
     * Registra le impostazioni WordPress
     */
    public function register_settings() {
        // Gruppo impostazioni API
        register_setting( 'jeenie_settings', 'jeenie_gemini_api_key', [
            'sanitize_callback' => 'sanitize_text_field',
        ]);
        register_setting( 'jeenie_settings', 'jeenie_openai_api_key', [
            'sanitize_callback' => 'sanitize_text_field',
        ]);
        register_setting( 'jeenie_settings', 'jeenie_default_provider', [
            'sanitize_callback' => 'sanitize_text_field',
            'default'           => 'gemini',
        ]);
        register_setting( 'jeenie_settings', 'jeenie_gemini_model', [
            'sanitize_callback' => 'sanitize_text_field',
            'default'           => 'gemini-2.0-flash',
        ]);
        register_setting( 'jeenie_settings', 'jeenie_openai_model', [
            'sanitize_callback' => 'sanitize_text_field',
            'default'           => 'gpt-5.4-mini',
        ]);
        register_setting( 'jeenie_settings', 'jeenie_claude_api_key', [
            'sanitize_callback' => 'sanitize_text_field',
        ]);
        register_setting( 'jeenie_settings', 'jeenie_claude_model', [
            'sanitize_callback' => 'sanitize_text_field',
            'default'           => 'claude-sonnet-4-6',
        ]);
        register_setting( 'jeenie_settings', 'jeenie_groq_api_key', [
            'sanitize_callback' => 'sanitize_text_field',
        ]);
        register_setting( 'jeenie_settings', 'jeenie_groq_model', [
            'sanitize_callback' => 'sanitize_text_field',
            'default'           => 'llama-3.3-70b-versatile',
        ]);
        register_setting( 'jeenie_settings', 'jeenie_rate_limit', [
            'sanitize_callback' => 'absint',
            'default'           => 30,
        ]);
        register_setting( 'jeenie_settings', 'jeenie_auto_delete_days', [
            'sanitize_callback' => 'absint',
            'default'           => 0,
        ]);
        register_setting( 'jeenie_settings', 'jeenie_api_timeout', [
            'sanitize_callback' => 'absint',
            'default'           => 30,
        ]);

        // Gruppo knowledge base (settings group separato)
        register_setting( 'jeenie_knowledge_settings', 'jeenie_knowledge_enabled', [
            'sanitize_callback' => 'absint',
            'default'           => 1,
        ]);
        register_setting( 'jeenie_knowledge_settings', 'jeenie_knowledge_max_chars', [
            'sanitize_callback' => 'absint',
            'default'           => 1500,
        ]);

        // Gruppo contesto sito
        register_setting( 'jeenie_settings', 'jeenie_site_name', [
            'sanitize_callback' => 'sanitize_text_field',
        ]);
        register_setting( 'jeenie_settings', 'jeenie_site_sector', [
            'sanitize_callback' => 'sanitize_text_field',
        ]);
        register_setting( 'jeenie_settings', 'jeenie_site_tone', [
            'sanitize_callback' => 'sanitize_text_field',
        ]);
        register_setting( 'jeenie_settings', 'jeenie_site_target', [
            'sanitize_callback' => 'sanitize_text_field',
        ]);
        register_setting( 'jeenie_settings', 'jeenie_site_description', [
            'sanitize_callback' => 'sanitize_textarea_field',
        ]);
    }

    /**
     * Carica CSS e JS solo nelle pagine del plugin
     */
    public function enqueue_assets( $hook ) {
        // Script per il bottone alt text nella modale media (tutte le pagine admin)
        wp_enqueue_script( 'jeenie-media-alt', JEENIE_PLUGIN_URL . 'assets/js/media-alt.js', [ 'jquery' ], JEENIE_VERSION, true );
        wp_localize_script( 'jeenie-media-alt', 'jeenie_alt', [
            'ajax_url' => admin_url( 'admin-ajax.php' ),
            'nonce'    => wp_create_nonce( 'jeenie_nonce' ),
        ]);

        if ( strpos( $hook, 'jeenie' ) === false ) return;

        // Chart.js per la pagina log
        if ( strpos( $hook, 'jeenie-logs' ) !== false ) {
            wp_enqueue_script( 'chartjs', JEENIE_PLUGIN_URL . 'assets/vendor/chart.min.js', [], '4.4.7', true );
        }

        // Bootstrap solo nelle pagine Jeenie (settings, logs, knowledge)
        wp_enqueue_style( 'bootstrap', JEENIE_PLUGIN_URL . 'assets/vendor/bootstrap.min.css', [], '5.3.3' );
        wp_enqueue_script( 'bootstrap', JEENIE_PLUGIN_URL . 'assets/vendor/bootstrap.bundle.min.js', [], '5.3.3', true );

        wp_enqueue_style(
            'jeenie-admin',
            JEENIE_PLUGIN_URL . 'assets/css/admin.css',
            [],
            JEENIE_VERSION
        );

        wp_enqueue_script(
            'jeenie-admin',
            JEENIE_PLUGIN_URL . 'assets/js/admin.js',
            [ 'jquery' ],
            JEENIE_VERSION,
            true
        );

        // Passa dati PHP → JS
        wp_localize_script( 'jeenie-admin', 'jeenie', [
            'ajax_url' => admin_url( 'admin-ajax.php' ),
            'nonce'    => wp_create_nonce( 'jeenie_nonce' ),
        ]);
    }

    /**
     * Renderizza la pagina impostazioni
     */
    public function render_settings_page() {
        require_once JEENIE_PLUGIN_DIR . 'templates/settings-page.php';
    }

    /**
     * Renderizza la pagina log
     */
    public function render_logs_page() {
        $per_page    = 30;
        $current     = isset( $_GET['paged'] ) ? absint( $_GET['paged'] ) : 1; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- pagination, read-only
        $total_items = Jeenie_Logger::count_logs();
        $total_pages = max( 1, ceil( $total_items / $per_page ) );
        $logs        = Jeenie_Logger::get_logs( $per_page, $current );
        $stats       = Jeenie_Logger::get_stats();
        $daily_stats    = Jeenie_Logger::get_daily_stats( 30 );
        $provider_stats = Jeenie_Logger::get_provider_stats();
        require_once JEENIE_PLUGIN_DIR . 'templates/logs-page.php';
    }

    /**
     * AJAX: testa la connessione API
     */
    public function ajax_test_api() {
        check_ajax_referer( 'jeenie_nonce', 'nonce' );

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( __( 'Permessi insufficienti.', 'jeenie' ) );
        }

        $connector = self::get_connector();
        if ( ! $connector ) {
            wp_send_json_error( __( 'API key non configurata.', 'jeenie' ) );
        }

        $response = $connector->generate( 'Rispondi solo con: "Jeenie connesso correttamente!"' );

        if ( $response['success'] ) {
            wp_send_json_success( $response['text'] );
        } else {
            wp_send_json_error( $response['error'] );
        }
    }

    /**
     * AJAX: svuota tutti i log
     */
    public function ajax_clear_logs() {
        check_ajax_referer( 'jeenie_nonce', 'nonce' );

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( 'Permessi insufficienti.' );
        }

        Jeenie_Logger::clear_logs();
        wp_send_json_success( 'Log svuotati.' );
    }

    /**
     * Recupera il contesto del sito da usare nei prompt
     */
    public static function get_site_context(): string {
        $name        = get_option( 'jeenie_site_name', get_bloginfo('name') );
        $sector      = get_option( 'jeenie_site_sector', '' );
        $tone        = get_option( 'jeenie_site_tone', '' );
        $target      = get_option( 'jeenie_site_target', '' );
        $description = get_option( 'jeenie_site_description', '' );

        $context = "Stai lavorando per il sito web chiamato \"$name\".";
        if ( $sector )      $context .= " Settore: $sector.";
        if ( $description ) $context .= " Descrizione: $description.";
        if ( $tone )        $context .= " Tono di comunicazione: $tone.";
        if ( $target )      $context .= " Pubblico target: $target.";
        $context .= " Scrivi sempre in italiano, a meno che non venga specificato diversamente.";

        return $context;
    }

    /**
     * Crea e restituisce il connettore AI attivo
     */
    public static function get_connector(): ?Jeenie_API_Connector {
        $provider = get_option( 'jeenie_default_provider', 'gemini' );

        if ( $provider === 'openai' ) {
            $api_key = get_option( 'jeenie_openai_api_key', '' );
            if ( empty( $api_key ) ) return null;
            $connector = new Jeenie_OpenAI( $api_key );
            $connector->set_model( get_option( 'jeenie_openai_model', 'gpt-5.4-mini' ) );
            return $connector;
        }

        if ( $provider === 'claude' ) {
            $api_key = get_option( 'jeenie_claude_api_key', '' );
            if ( empty( $api_key ) ) return null;
            $connector = new Jeenie_Claude( $api_key );
            $connector->set_model( get_option( 'jeenie_claude_model', 'claude-sonnet-4-6' ) );
            return $connector;
        }

        if ( $provider === 'groq' ) {
            $api_key = get_option( 'jeenie_groq_api_key', '' );
            if ( empty( $api_key ) ) return null;
            $connector = new Jeenie_Groq( $api_key );
            $connector->set_model( get_option( 'jeenie_groq_model', 'llama-3.3-70b-versatile' ) );
            return $connector;
        }

        $api_key = get_option( 'jeenie_gemini_api_key', '' );
        if ( empty( $api_key ) ) return null;
        $connector = new Jeenie_Gemini( $api_key );
        $connector->set_model( get_option( 'jeenie_gemini_model', 'gemini-2.5-flash-lite' ) );
        return $connector;
    }

    /**
     * Controlla e incrementa il rate limit per l'utente corrente.
     * Restituisce true se il limite è superato.
     */
    public static function is_rate_limited(): bool {
        $limit = (int) get_option( 'jeenie_rate_limit', 30 );
        if ( $limit <= 0 ) return false; // 0 = disabilitato

        $user_id = get_current_user_id();
        $key     = 'jeenie_rl_' . $user_id;
        $count   = (int) get_transient( $key );

        if ( $count >= $limit ) return true;

        set_transient( $key, $count + 1, HOUR_IN_SECONDS );
        return false;
    }

    /**
     * Renderizza la pagina Knowledge Base
     */
    public function render_knowledge_page() {
        $documents = Jeenie_Knowledge::get_documents();
        require_once JEENIE_PLUGIN_DIR . 'templates/knowledge-page.php';
    }

    /**
     * AJAX: carica un documento nella knowledge base
     */
    public function ajax_upload_knowledge() {
        check_ajax_referer( 'jeenie_nonce', 'nonce' );
        if ( ! current_user_can( 'manage_options' ) ) wp_send_json_error( 'Permessi insufficienti.' );

        $name    = sanitize_text_field( wp_unslash( $_POST['doc_name'] ?? '' ) );
        $content = sanitize_textarea_field( wp_unslash( $_POST['doc_content'] ?? '' ) );

        if ( empty( $name ) || empty( $content ) ) {
            wp_send_json_error( 'Nome e contenuto sono obbligatori.' );
        }

        $chunks = Jeenie_Knowledge::add_document( $name, $content );
        wp_send_json_success( [ 'chunks' => $chunks, 'message' => "Documento \"$name\" salvato ($chunks frammenti)." ] );
    }

    /**
     * AJAX: elimina un documento dalla knowledge base
     */
    public function ajax_delete_knowledge() {
        check_ajax_referer( 'jeenie_nonce', 'nonce' );
        if ( ! current_user_can( 'manage_options' ) ) wp_send_json_error( 'Permessi insufficienti.' );

        $name = sanitize_text_field( wp_unslash( $_POST['doc_name'] ?? '' ) );
        if ( empty( $name ) ) wp_send_json_error( 'Nome documento mancante.' );

        Jeenie_Knowledge::delete_document( $name );
        wp_send_json_success( 'Documento eliminato.' );
    }

    /**
     * AJAX: indicizza tutti i post pubblicati
     */
    public function ajax_index_posts() {
        check_ajax_referer( 'jeenie_nonce', 'nonce' );
        if ( ! current_user_can( 'manage_options' ) ) wp_send_json_error( 'Permessi insufficienti.' );

        $count = Jeenie_Knowledge::index_all_posts();
        wp_send_json_success( [ 'count' => $count, 'message' => "$count post indicizzati nella knowledge base." ] );
    }

    /**
     * Aggiunge il bottone "Genera Alt Text" nella modale media
     */
    public function add_alt_button_to_media( $form_fields, $post ) {
        if ( ! wp_attachment_is_image( $post->ID ) ) return $form_fields;

        $form_fields['jeenie_alt'] = [
            'label' => '',
            'input' => 'html',
            'html'  => '<button type="button" class="button jeenie-generate-alt" data-id="' . esc_attr( $post->ID ) . '">🤖 ' . esc_html__( 'Genera Alt Text con AI', 'jeenie' ) . '</button>',
        ];

        return $form_fields;
    }

    /**
     * AJAX: genera alt text per un'immagine
     */
    public function ajax_generate_alt() {
        check_ajax_referer( 'jeenie_nonce', 'nonce' );
        if ( ! current_user_can( 'edit_posts' ) ) wp_send_json_error( 'Permessi insufficienti.' );

        $attachment_id = intval( $_POST['attachment_id'] ?? 0 );
        if ( ! $attachment_id || ! wp_attachment_is_image( $attachment_id ) ) {
            wp_send_json_error( 'Immagine non valida.' );
        }

        $connector = self::get_connector();
        if ( ! $connector ) wp_send_json_error( 'API key non configurata.' );

        $image_url = wp_get_attachment_url( $attachment_id );
        if ( ! $image_url ) wp_send_json_error( 'URL immagine non trovato.' );

        // Usa il thumbnail per risparmiare token
        $thumb = wp_get_attachment_image_src( $attachment_id, 'medium' );
        $url   = $thumb ? $thumb[0] : $image_url;

        $context  = self::get_site_context();
        $prompt   = "$context\n\nGenera un alt text breve e descrittivo (massimo 125 caratteri) per questa immagine. ";
        $prompt  .= "Rispondi SOLO con il testo dell'alt, senza virgolette né spiegazioni.\n\nURL immagine: $url";

        $response = $connector->generate( $prompt, [ 'max_tokens' => 100, 'temperature' => 0.3 ] );

        if ( ! $response['success'] ) {
            wp_send_json_error( $response['error'] );
        }

        $alt_text = sanitize_text_field( trim( $response['text'], ' "\'') );
        update_post_meta( $attachment_id, '_wp_attachment_image_alt', $alt_text );

        wp_send_json_success( [ 'alt_text' => $alt_text ] );
    }

    /**
     * Renderizza la pagina Componenti
     */
    public function render_components_page() {
        $components = Jeenie_Components::get_all();
        require_once JEENIE_PLUGIN_DIR . 'templates/components-page.php';
    }

    /**
     * AJAX: toggle stato componente
     */
    public function ajax_toggle_component() {
        check_ajax_referer( 'jeenie_nonce', 'nonce' );
        if ( ! current_user_can( 'manage_options' ) ) wp_send_json_error( 'Permessi insufficienti.' );

        $slug   = sanitize_text_field( wp_unslash( $_POST['slug'] ?? '' ) );
        $status = sanitize_text_field( wp_unslash( $_POST['status'] ?? '' ) );
        if ( ! in_array( $status, [ 'active', 'inactive' ] ) ) wp_send_json_error( 'Stato non valido.' );

        if ( $slug === '__all__' ) {
            Jeenie_Components::deactivate_all();
        } else {
            Jeenie_Components::set_status( $slug, $status );
        }
        wp_send_json_success( 'Stato aggiornato.' );
    }

    /**
     * AJAX: elimina componente
     */
    public function ajax_delete_component() {
        check_ajax_referer( 'jeenie_nonce', 'nonce' );
        if ( ! current_user_can( 'manage_options' ) ) wp_send_json_error( 'Permessi insufficienti.' );

        $slug = sanitize_text_field( wp_unslash( $_POST['slug'] ?? '' ) );
        Jeenie_Components::delete( $slug );
        wp_send_json_success( 'Componente eliminato.' );
    }
}
