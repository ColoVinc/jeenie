<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Classe Metabox — aggiunge il pannello AI nell'editor post/pagina
 */
class SiteGenie_Metabox {

    private static $instance = null;

    public static function get_instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action( 'add_meta_boxes', [ $this, 'register_metabox' ] );
        add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_assets' ] );
        add_action( 'wp_ajax_sitegenie_generate_content', [ $this, 'ajax_generate_content' ] );
        add_action( 'wp_ajax_sitegenie_generate_seo', [ $this, 'ajax_generate_seo' ] );
    }

    public function register_metabox() {
        $screens = get_post_types( [ 'public' => true ], 'names' );
        foreach ( $screens as $screen ) {
            add_meta_box(
                'sitegenie_metabox',
                '🤖 SiteGenie — Assistente AI',
                [ $this, 'render_metabox' ],
                $screen,
                'side',
                'high'
            );
        }
    }

    public function enqueue_assets( $hook ) {
        if ( ! in_array( $hook, [ 'post.php', 'post-new.php' ] ) ) return;

        wp_enqueue_script(
            'sitegenie-metabox',
            SITEGENIE_PLUGIN_URL . 'assets/js/metabox.js',
            [ 'jquery', 'wp-blocks', 'wp-data', 'wp-block-editor' ],
            SITEGENIE_VERSION,
            true
        );

        wp_localize_script( 'sitegenie-metabox', 'sitegenie', [
            'ajax_url' => admin_url( 'admin-ajax.php' ),
            'nonce'    => wp_create_nonce( 'sitegenie_nonce' ),
        ]);
    }

    public function render_metabox( $post ) {
        require_once SITEGENIE_PLUGIN_DIR . 'templates/metabox.php';
    }

    /**
     * AJAX: genera bozza articolo
     */
    public function ajax_generate_content() {
        check_ajax_referer( 'sitegenie_nonce', 'nonce' );
        if ( ! current_user_can( 'edit_posts' ) ) wp_send_json_error( 'Permessi insufficienti.' );

        $title    = sanitize_text_field( wp_unslash( $_POST['title'] ?? '' ) );
        $keywords = sanitize_text_field( wp_unslash( $_POST['keywords'] ?? '' ) );
        $type     = sanitize_text_field( wp_unslash( $_POST['type'] ?? 'post' ) );

        if ( empty( $title ) ) wp_send_json_error( 'Inserisci un titolo.' );

        $connector = SiteGenie_Admin::get_connector();
        if ( ! $connector ) wp_send_json_error( 'API key non configurata. Vai in SiteGenie → Impostazioni.' );

        if ( SiteGenie_Admin::is_rate_limited() ) wp_send_json_error( 'Hai raggiunto il limite di richieste orarie. Riprova più tardi.' );

        $context = SiteGenie_Admin::get_site_context();
        $prompt  = "$context\n\n";
        $prompt .= "Scrivi una bozza completa per un " . ( $type === 'page' ? 'pagina web' : 'articolo di blog' );
        $prompt .= " con il titolo: \"$title\".";
        if ( $keywords ) $prompt .= " Keywords da includere: $keywords.";
        $prompt .= "\n\nStruttura il contenuto con paragrafi ben organizzati. Non aggiungere tag HTML, solo testo semplice con titoli usando ##.";

        $response = $connector->generate( $prompt, [ 'max_tokens' => 1500 ] );

        if ( $response['success'] ) {
            wp_send_json_success( [ 'text' => $response['text'] ] );
        } else {
            wp_send_json_error( $response['error'] );
        }
    }

    /**
     * AJAX: genera meta SEO
     */
    public function ajax_generate_seo() {
        check_ajax_referer( 'sitegenie_nonce', 'nonce' );
        if ( ! current_user_can( 'edit_posts' ) ) wp_send_json_error( 'Permessi insufficienti.' );

        $title   = sanitize_text_field( wp_unslash( $_POST['title'] ?? '' ) );
        $content = sanitize_textarea_field( wp_unslash( $_POST['content'] ?? '' ) );

        if ( empty( $title ) && empty( $content ) ) wp_send_json_error( 'Nessun contenuto da analizzare.' );

        $connector = SiteGenie_Admin::get_connector();
        if ( ! $connector ) wp_send_json_error( 'API key non configurata.' );

        if ( SiteGenie_Admin::is_rate_limited() ) wp_send_json_error( 'Hai raggiunto il limite di richieste orarie. Riprova più tardi.' );

        $context = SiteGenie_Admin::get_site_context();
        $prompt  = "$context\n\n";
        $prompt .= "Basandoti su questo contenuto:\nTitolo: $title\n";
        if ( $content ) $prompt .= "Testo: " . substr( $content, 0, 500 ) . "...\n";
        $prompt .= "\nGenera in formato JSON (e solo JSON, nessun testo extra):\n";
        $prompt .= '{"meta_title": "titolo SEO max 60 caratteri", "meta_description": "descrizione SEO max 155 caratteri", "excerpt": "riassunto breve max 40 parole"}';

        $response = $connector->generate( $prompt, [ 'max_tokens' => 300, 'temperature' => 0.3 ] );

        if ( ! $response['success'] ) {
            wp_send_json_error( $response['error'] );
        }

        // Pulisce la risposta e la parsa come JSON
        $text = trim( $response['text'] );
        $text = preg_replace( '/^```json\s*/i', '', $text );
        $text = preg_replace( '/\s*```$/', '', $text );
        $data = json_decode( $text, true );

        if ( ! $data ) {
            wp_send_json_error( 'Risposta non valida dall\'AI.' );
        }

        wp_send_json_success( $data );
    }
}
