<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Classe Admin - gestisce il pannello di impostazioni nel WP Admin
 */
class ChatPress_Admin {

    private static $instance = null;

    public static function get_instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() 
    {
        add_action( 'admin_menu', [ $this, 'register_menu' ] );
        add_action( 'admin_init', [ $this, 'register_settings' ] );
        add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_assets' ] );
        add_action( 'wp_ajax_chatpress_test_api', [ $this, 'ajax_test_api' ] );
    }

    /**
     * Registra le voci di menu nel WP Admin
     */
    public function register_menu() {
        add_menu_page(
            'ChatPress',
            'ChatPress',
            'manage_options',
            'chatpress',
            [ $this, 'render_settings_page' ],
            'dashicons-superhero',
            30
        );

        add_submenu_page(
            'chatpress',
            'Impostazioni',
            'Impostazioni',
            'manage_options',
            'chatpress-settings',
            [ $this, 'render_settings_page' ]
        );

        add_submenu_page(
            'chatpress',
            'Log chiamate',
            'Log chiamate',
            'manage_options',
            'chatpress-logs',
            [ $this, 'render_logs_page' ]
        );
    }

    /**
     * Registra le impostazini Wordpress
     */
    public function register_settings() {
        // Gruppo impostazioni API
        register_setting( 'chatpress_settings', 'chatpress_gemini_api_key', [
            'sanitize_callback' => 'sanitize_text_field',
        ]);
        register_setting( 'chatpress_settings', 'chatpress_default_provider', [
            'sanitize_callback' => 'sanitize_text_field',
            'default' => 'gemini',
        ]);
        register_setting( 'chatpress_settings', 'chatpress_gemini_model', [
            'sanitize_callback' => 'sanitize_text_field',
            'default' => 'gemini-2.0-flash',
        ]);

        // Gruppo contesto sito
        register_setting( 'chatpress_settings', 'chatpress_site_name', [
            'sanitize_callback' => 'sanitize_text_field',
        ]);
        register_setting( 'chatpress_settings', 'chatpress_site_sector', [
            'sanitize_callback' => 'sanitize_text_field',
        ]);
        register_setting( 'chatpress_settings', 'chatpress_site_tone', [
            'sanitize_callback' => 'sanitize_text_field',
        ]);
        register_setting( 'chatpress_settings', 'chatpress_site_target', [
            'sanitize_callback' => 'sanitize_text_field',
        ]);
        register_setting( 'chatpress_settings', 'chatpress_site_description', [
            'sanitize_callback' => 'sanitize_textarea_field',
        ]);        
    }

    /**
     * Carica CSS e JS solo nelle pagine del plugin
     */
    public function enqueue_assets( $hook ) {
        if ( strpos( $hook, 'chatpress' ) === false ) return;
        wp_enqueue_style( 'chatpress-admin', CHATPRESS_PLUGIN_URL . 'assets/css/admin.css', [], CHATPRESS_VERSION );
        wp_enqueue_script( 'chatpress-admin', CHATPRESS_PLUGIN_URL . 'assets/js/admin.js', [ 'jquery' ], CHATPRESS_VERSION, true );

        // Passa dati PHP -> JS
        wp_localize_script( 'chatpress-admin', 'chatpress', [
            'ajaxUrl' => admin_url( 'admin-ajax.php' ),
            'nonce'   => wp_create_nonce( 'chatpress_nonce' ),
        ]);
    }

    /**
     * Renderizza la pagina impostaioni
     */
    public function render_settings_page() {
        require_once CHATPRESS_PLUGIN_DIR . 'templates/settings-page.php';
    }

    /**
     * Renderizza la pagina log
     */
    public function render_logs_page() {
        $logs = ChatPress_Logger::get_logs( 100 );
        $stats = ChatPress_Logger::get_stats();
        require_once CHATPRESS_PLUGIN_DIR . 'templates/logs-page.php';
    }

    /**
     * AJAX: testa la connessione API
     */
    public function ajax_test_api() {
        check_ajax_referer( 'chatpress_nonce', 'nonce' );

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( 'Permessi insufficienti' );
        }

        $api_key = get_option( 'chatpress_gemini_api_key', '' );
        if ( empty( $api_key ) ) {
            wp_send_json_error( 'API key non configurata' );
        }
        $gemini = new ChatPress_Gemini( $api_key );
        $response = $gemini->generate( 'Rispondi solo con: "ChatPress connesso correttamente"' );

        if ( $response['success'] ) {
            wp_send_json_success( $response['text'] );
        } else {
            wp_send_json_error( $response['error'] );
        }
    }

    /**
     * Recupera il contesto del sito da usare nel prompt
     */
    public static function get_site_context(): string {
        $name           = get_option( 'chatpress_site_name', get_bloginfo('name') );
        $sector         = get_option( 'chatpress_site_sector', '' );
        $tone           = get_option( 'chatpress_site_tone', '' );
        $target         = get_option( 'chatpress_site_target', '' );
        $description    = get_option( 'chatpress_site_description', '' );

        $context = "Stai lavorando per il sito web chiamato \"$name\".";
        if ( $sector )      $context .= "Settore: $sector. ";
        if ( $description ) $context .= "Descrizione: $description. ";
        if ( $tone )        $context .= "Tono di comunicazione: $tone. ";
        if ( $target )      $context .= "Pubblco target: $target. ";
        $context .= 'Scrivi sempre in italiano, a meno che non venga specificato diversamente.';
        return $context;
    }

    /**
     * Crea e restituisce il connettore AI attivo
     */
    public static function get_connector(): ?ChatPress_Gemini {
        $api_key = get_option( 'chatpress_gemini_api_key', '' );
        if ( empty( $api_key ) ) return null;
        return new ChatPress_Gemini( $api_key );
    }
}