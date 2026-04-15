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
            'default'           => 'gpt-4o-mini',
        ]);
        register_setting( 'sitegenie_settings', 'sitegenie_claude_api_key', [
            'sanitize_callback' => 'sanitize_text_field',
        ]);
        register_setting( 'sitegenie_settings', 'sitegenie_claude_model', [
            'sanitize_callback' => 'sanitize_text_field',
            'default'           => 'claude-sonnet-4-20250514',
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
        if ( strpos( $hook, 'sitegenie' ) === false ) return;

        // Bootstrap JS (CSS già caricato globalmente dal chat widget)
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
            $connector->set_model( get_option( 'sitegenie_openai_model', 'gpt-4o-mini' ) );
            return $connector;
        }

        if ( $provider === 'claude' ) {
            $api_key = get_option( 'sitegenie_claude_api_key', '' );
            if ( empty( $api_key ) ) return null;
            $connector = new SiteGenie_Claude( $api_key );
            $connector->set_model( get_option( 'sitegenie_claude_model', 'claude-sonnet-4-20250514' ) );
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
}
