<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Classe Admin — gestisce il pannello di impostazioni nel WP Admin
 */
class SiteGenie_Admin {

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
        add_action( 'wp_ajax_sitegenie_test_api', [ $this, 'ajax_test_api' ] );
        add_action( 'wp_ajax_sitegenie_clear_logs', [ $this, 'ajax_clear_logs' ] );
        add_action( 'wp_ajax_sitegenie_upload_knowledge', [ $this, 'ajax_upload_knowledge' ] );
        add_action( 'wp_ajax_sitegenie_delete_knowledge', [ $this, 'ajax_delete_knowledge' ] );
        add_action( 'wp_ajax_sitegenie_index_posts', [ $this, 'ajax_index_posts' ] );
        add_action( 'wp_ajax_sitegenie_generate_alt', [ $this, 'ajax_generate_alt' ] );
        add_action( 'wp_ajax_sitegenie_toggle_component', [ $this, 'ajax_toggle_component' ] );
        add_action( 'wp_ajax_sitegenie_delete_component', [ $this, 'ajax_delete_component' ] );
        add_filter( 'attachment_fields_to_edit', [ $this, 'add_alt_button_to_media' ], 10, 2 );
    }

    /**
     * Notice dopo attivazione plugin
     */
    public function activation_notice() {
        if ( ! get_transient( 'sitegenie_activated' ) ) return;
        delete_transient( 'sitegenie_activated' );
        $url = admin_url( 'admin.php?page=sitegenie' );
        echo '<div class="notice notice-success is-dismissible"><p><strong>🤖 ' . esc_html__( 'SiteGenie attivato!', 'sitegenie' ) . '</strong> <a href="' . esc_url( $url ) . '">' . esc_html__( 'Configura la tua API key', 'sitegenie' ) . '</a> ' . esc_html__( 'per iniziare.', 'sitegenie' ) . '</p></div>';
    }

    /**
     * Notice se nessuna API key è configurata
     */
    public function missing_key_notice() {
        if ( get_transient( 'sitegenie_activated' ) ) return;
        $screen = get_current_screen();
        if ( $screen && strpos( $screen->id, 'sitegenie' ) !== false ) return;

        $provider = get_option( 'sitegenie_default_provider', 'gemini' );
        $key      = get_option( 'sitegenie_' . $provider . '_api_key', '' );
        if ( ! empty( $key ) ) return;

        $url = admin_url( 'admin.php?page=sitegenie' );
        echo '<div class="notice notice-warning is-dismissible"><p><strong>🤖 SiteGenie:</strong> ' . esc_html__( 'API key non configurata.', 'sitegenie' ) . ' <a href="' . esc_url( $url ) . '">' . esc_html__( 'Vai alle impostazioni', 'sitegenie' ) . '</a>.</p></div>';
    }

    /**
     * Registra le voci di menu nel WP Admin
     */
    public function register_menu() {
        add_menu_page(
            'SiteGenie',
            'SiteGenie',
            'manage_options',
            'sitegenie',
            [ $this, 'render_settings_page' ],
            'dashicons-superhero',
            30
        );

        add_submenu_page(
            'sitegenie',
            'Impostazioni',
            'Impostazioni',
            'manage_options',
            'sitegenie',
            [ $this, 'render_settings_page' ]
        );

        add_submenu_page(
            'sitegenie',
            'Log Chiamate',
            'Log Chiamate',
            'manage_options',
            'sitegenie-logs',
            [ $this, 'render_logs_page' ]
        );

        add_submenu_page(
            'sitegenie',
            'Knowledge Base',
            'Knowledge Base',
            'manage_options',
            'sitegenie-knowledge',
            [ $this, 'render_knowledge_page' ]
        );

        add_submenu_page(
            'sitegenie',
            'Componenti',
            'Componenti',
            'manage_options',
            'sitegenie-components',
            [ $this, 'render_components_page' ]
        );
    }

    /**
     * Registra le impostazioni WordPress
     */
    public function register_settings() {
        // Gruppo impostazioni API
        register_setting( 'sitegenie_settings', 'sitegenie_gemini_api_key', [
            'sanitize_callback' => 'sanitize_text_field',
        ]);
        register_setting( 'sitegenie_settings', 'sitegenie_openai_api_key', [
            'sanitize_callback' => 'sanitize_text_field',
        ]);
        register_setting( 'sitegenie_settings', 'sitegenie_default_provider', [
            'sanitize_callback' => 'sanitize_text_field',
            'default'           => 'gemini',
        ]);
        register_setting( 'sitegenie_settings', 'sitegenie_gemini_model', [
            'sanitize_callback' => 'sanitize_text_field',
            'default'           => 'gemini-2.0-flash',
        ]);
        register_setting( 'sitegenie_settings', 'sitegenie_openai_model', [
            'sanitize_callback' => 'sanitize_text_field',
            'default'           => 'gpt-5.4-mini',
        ]);
        register_setting( 'sitegenie_settings', 'sitegenie_claude_api_key', [
            'sanitize_callback' => 'sanitize_text_field',
        ]);
        register_setting( 'sitegenie_settings', 'sitegenie_claude_model', [
            'sanitize_callback' => 'sanitize_text_field',
            'default'           => 'claude-sonnet-4-6',
        ]);
        register_setting( 'sitegenie_settings', 'sitegenie_groq_api_key', [
            'sanitize_callback' => 'sanitize_text_field',
        ]);
        register_setting( 'sitegenie_settings', 'sitegenie_groq_model', [
            'sanitize_callback' => 'sanitize_text_field',
            'default'           => 'llama-3.3-70b-versatile',
        ]);
        register_setting( 'sitegenie_settings', 'sitegenie_rate_limit', [
            'sanitize_callback' => 'absint',
            'default'           => 30,
        ]);
        register_setting( 'sitegenie_settings', 'sitegenie_auto_delete_days', [
            'sanitize_callback' => 'absint',
            'default'           => 0,
        ]);
        register_setting( 'sitegenie_settings', 'sitegenie_api_timeout', [
            'sanitize_callback' => 'absint',
            'default'           => 30,
        ]);

        // Gruppo knowledge base (settings group separato)
        register_setting( 'sitegenie_knowledge_settings', 'sitegenie_knowledge_enabled', [
            'sanitize_callback' => 'absint',
            'default'           => 1,
        ]);
        register_setting( 'sitegenie_knowledge_settings', 'sitegenie_knowledge_max_chars', [
            'sanitize_callback' => 'absint',
            'default'           => 1500,
        ]);

        // Gruppo contesto sito
        register_setting( 'sitegenie_settings', 'sitegenie_site_name', [
            'sanitize_callback' => 'sanitize_text_field',
        ]);
        register_setting( 'sitegenie_settings', 'sitegenie_site_sector', [
            'sanitize_callback' => 'sanitize_text_field',
        ]);
        register_setting( 'sitegenie_settings', 'sitegenie_site_tone', [
            'sanitize_callback' => 'sanitize_text_field',
        ]);
        register_setting( 'sitegenie_settings', 'sitegenie_site_target', [
            'sanitize_callback' => 'sanitize_text_field',
        ]);
        register_setting( 'sitegenie_settings', 'sitegenie_site_description', [
            'sanitize_callback' => 'sanitize_textarea_field',
        ]);
    }

    /**
     * Carica CSS e JS solo nelle pagine del plugin
     */
    public function enqueue_assets( $hook ) {
        // Script per il bottone alt text nella modale media (tutte le pagine admin)
        wp_enqueue_script( 'sitegenie-media-alt', SITEGENIE_PLUGIN_URL . 'assets/js/media-alt.js', [ 'jquery' ], SITEGENIE_VERSION, true );
        wp_localize_script( 'sitegenie-media-alt', 'sitegenie_alt', [
            'ajax_url' => admin_url( 'admin-ajax.php' ),
            'nonce'    => wp_create_nonce( 'sitegenie_nonce' ),
        ]);

        if ( strpos( $hook, 'sitegenie' ) === false ) return;

        // Chart.js per la pagina log
        if ( strpos( $hook, 'sitegenie-logs' ) !== false ) {
            wp_enqueue_script( 'chartjs', SITEGENIE_PLUGIN_URL . 'assets/vendor/chart.min.js', [], '4.4.7', true );
        }

        // Bootstrap solo nelle pagine SiteGenie (settings, logs, knowledge)
        wp_enqueue_style( 'bootstrap', SITEGENIE_PLUGIN_URL . 'assets/vendor/bootstrap.min.css', [], '5.3.3' );
        wp_enqueue_script( 'bootstrap', SITEGENIE_PLUGIN_URL . 'assets/vendor/bootstrap.bundle.min.js', [], '5.3.3', true );

        wp_enqueue_style(
            'sitegenie-admin',
            SITEGENIE_PLUGIN_URL . 'assets/css/admin.css',
            [],
            SITEGENIE_VERSION
        );

        wp_enqueue_script(
            'sitegenie-admin',
            SITEGENIE_PLUGIN_URL . 'assets/js/admin.js',
            [ 'jquery' ],
            SITEGENIE_VERSION,
            true
        );

        // Passa dati PHP → JS
        wp_localize_script( 'sitegenie-admin', 'sitegenie', [
            'ajax_url' => admin_url( 'admin-ajax.php' ),
            'nonce'    => wp_create_nonce( 'sitegenie_nonce' ),
        ]);
    }

    /**
     * Renderizza la pagina impostazioni
     */
    public function render_settings_page() {
        require_once SITEGENIE_PLUGIN_DIR . 'templates/settings-page.php';
    }

    /**
     * Renderizza la pagina log
     */
    public function render_logs_page() {
        $per_page    = 30;
        $current     = isset( $_GET['paged'] ) ? absint( $_GET['paged'] ) : 1; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- pagination, read-only
        $total_items = SiteGenie_Logger::count_logs();
        $total_pages = max( 1, ceil( $total_items / $per_page ) );
        $logs        = SiteGenie_Logger::get_logs( $per_page, $current );
        $stats       = SiteGenie_Logger::get_stats();
        $daily_stats    = SiteGenie_Logger::get_daily_stats( 30 );
        $provider_stats = SiteGenie_Logger::get_provider_stats();
        require_once SITEGENIE_PLUGIN_DIR . 'templates/logs-page.php';
    }

    /**
     * AJAX: testa la connessione API
     */
    public function ajax_test_api() {
        check_ajax_referer( 'sitegenie_nonce', 'nonce' );

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( __( 'Permessi insufficienti.', 'sitegenie' ) );
        }

        $connector = self::get_connector();
        if ( ! $connector ) {
            wp_send_json_error( __( 'API key non configurata.', 'sitegenie' ) );
        }

        $response = $connector->generate( 'Rispondi solo con: "SiteGenie connesso correttamente!"' );

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
        check_ajax_referer( 'sitegenie_nonce', 'nonce' );

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( 'Permessi insufficienti.' );
        }

        SiteGenie_Logger::clear_logs();
        wp_send_json_success( 'Log svuotati.' );
    }

    /**
     * Recupera il contesto del sito da usare nei prompt
     */
    public static function get_site_context(): string {
        $name        = get_option( 'sitegenie_site_name', get_bloginfo('name') );
        $sector      = get_option( 'sitegenie_site_sector', '' );
        $tone        = get_option( 'sitegenie_site_tone', '' );
        $target      = get_option( 'sitegenie_site_target', '' );
        $description = get_option( 'sitegenie_site_description', '' );

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
    public static function get_connector(): ?SiteGenie_API_Connector {
        $provider = get_option( 'sitegenie_default_provider', 'gemini' );

        if ( $provider === 'openai' ) {
            $api_key = get_option( 'sitegenie_openai_api_key', '' );
            if ( empty( $api_key ) ) return null;
            $connector = new SiteGenie_OpenAI( $api_key );
            $connector->set_model( get_option( 'sitegenie_openai_model', 'gpt-5.4-mini' ) );
            return $connector;
        }

        if ( $provider === 'claude' ) {
            $api_key = get_option( 'sitegenie_claude_api_key', '' );
            if ( empty( $api_key ) ) return null;
            $connector = new SiteGenie_Claude( $api_key );
            $connector->set_model( get_option( 'sitegenie_claude_model', 'claude-sonnet-4-6' ) );
            return $connector;
        }

        if ( $provider === 'groq' ) {
            $api_key = get_option( 'sitegenie_groq_api_key', '' );
            if ( empty( $api_key ) ) return null;
            $connector = new SiteGenie_Groq( $api_key );
            $connector->set_model( get_option( 'sitegenie_groq_model', 'llama-3.3-70b-versatile' ) );
            return $connector;
        }

        $api_key = get_option( 'sitegenie_gemini_api_key', '' );
        if ( empty( $api_key ) ) return null;
        $connector = new SiteGenie_Gemini( $api_key );
        $connector->set_model( get_option( 'sitegenie_gemini_model', 'gemini-2.5-flash-lite' ) );
        return $connector;
    }

    /**
     * Controlla e incrementa il rate limit per l'utente corrente.
     * Restituisce true se il limite è superato.
     */
    public static function is_rate_limited(): bool {
        $limit = (int) get_option( 'sitegenie_rate_limit', 30 );
        if ( $limit <= 0 ) return false; // 0 = disabilitato

        $user_id = get_current_user_id();
        $key     = 'sitegenie_rl_' . $user_id;
        $count   = (int) get_transient( $key );

        if ( $count >= $limit ) return true;

        set_transient( $key, $count + 1, HOUR_IN_SECONDS );
        return false;
    }

    /**
     * Renderizza la pagina Knowledge Base
     */
    public function render_knowledge_page() {
        $documents = SiteGenie_Knowledge::get_documents();
        require_once SITEGENIE_PLUGIN_DIR . 'templates/knowledge-page.php';
    }

    /**
     * AJAX: carica un documento nella knowledge base
     */
    public function ajax_upload_knowledge() {
        check_ajax_referer( 'sitegenie_nonce', 'nonce' );
        if ( ! current_user_can( 'manage_options' ) ) wp_send_json_error( 'Permessi insufficienti.' );

        $name    = sanitize_text_field( wp_unslash( $_POST['doc_name'] ?? '' ) );
        $content = sanitize_textarea_field( wp_unslash( $_POST['doc_content'] ?? '' ) );

        if ( empty( $name ) || empty( $content ) ) {
            wp_send_json_error( 'Nome e contenuto sono obbligatori.' );
        }

        $chunks = SiteGenie_Knowledge::add_document( $name, $content );
        wp_send_json_success( [ 'chunks' => $chunks, 'message' => "Documento \"$name\" salvato ($chunks frammenti)." ] );
    }

    /**
     * AJAX: elimina un documento dalla knowledge base
     */
    public function ajax_delete_knowledge() {
        check_ajax_referer( 'sitegenie_nonce', 'nonce' );
        if ( ! current_user_can( 'manage_options' ) ) wp_send_json_error( 'Permessi insufficienti.' );

        $name = sanitize_text_field( wp_unslash( $_POST['doc_name'] ?? '' ) );
        if ( empty( $name ) ) wp_send_json_error( 'Nome documento mancante.' );

        SiteGenie_Knowledge::delete_document( $name );
        wp_send_json_success( 'Documento eliminato.' );
    }

    /**
     * AJAX: indicizza tutti i post pubblicati
     */
    public function ajax_index_posts() {
        check_ajax_referer( 'sitegenie_nonce', 'nonce' );
        if ( ! current_user_can( 'manage_options' ) ) wp_send_json_error( 'Permessi insufficienti.' );

        $count = SiteGenie_Knowledge::index_all_posts();
        wp_send_json_success( [ 'count' => $count, 'message' => "$count post indicizzati nella knowledge base." ] );
    }

    /**
     * Aggiunge il bottone "Genera Alt Text" nella modale media
     */
    public function add_alt_button_to_media( $form_fields, $post ) {
        if ( ! wp_attachment_is_image( $post->ID ) ) return $form_fields;

        $form_fields['sitegenie_alt'] = [
            'label' => '',
            'input' => 'html',
            'html'  => '<button type="button" class="button sitegenie-generate-alt" data-id="' . esc_attr( $post->ID ) . '">🤖 ' . esc_html__( 'Genera Alt Text con AI', 'sitegenie' ) . '</button>',
        ];

        return $form_fields;
    }

    /**
     * AJAX: genera alt text per un'immagine
     */
    public function ajax_generate_alt() {
        check_ajax_referer( 'sitegenie_nonce', 'nonce' );
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
        $components = SiteGenie_Components::get_all();
        require_once SITEGENIE_PLUGIN_DIR . 'templates/components-page.php';
    }

    /**
     * AJAX: toggle stato componente
     */
    public function ajax_toggle_component() {
        check_ajax_referer( 'sitegenie_nonce', 'nonce' );
        if ( ! current_user_can( 'manage_options' ) ) wp_send_json_error( 'Permessi insufficienti.' );

        $slug   = sanitize_text_field( wp_unslash( $_POST['slug'] ?? '' ) );
        $status = sanitize_text_field( wp_unslash( $_POST['status'] ?? '' ) );
        if ( ! in_array( $status, [ 'active', 'inactive' ] ) ) wp_send_json_error( 'Stato non valido.' );

        if ( $slug === '__all__' ) {
            SiteGenie_Components::deactivate_all();
        } else {
            SiteGenie_Components::set_status( $slug, $status );
        }
        wp_send_json_success( 'Stato aggiornato.' );
    }

    /**
     * AJAX: elimina componente
     */
    public function ajax_delete_component() {
        check_ajax_referer( 'sitegenie_nonce', 'nonce' );
        if ( ! current_user_can( 'manage_options' ) ) wp_send_json_error( 'Permessi insufficienti.' );

        $slug = sanitize_text_field( wp_unslash( $_POST['slug'] ?? '' ) );
        SiteGenie_Components::delete( $slug );
        wp_send_json_success( 'Componente eliminato.' );
    }
}
