<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Classe Core — inizializza tutto il plugin
 */
class SiteGenie_Core {

    private static $instance = null;

    public static function get_instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        $this->init_hooks();
    }

    private function init_hooks() {
        add_action( 'sitegenie_daily_cleanup', [ $this, 'run_cleanup' ] );
        add_action( 'admin_init', [ $this, 'add_privacy_policy' ] );
        add_action( 'save_post', [ $this, 'reindex_post' ], 20, 2 );

        if ( is_admin() ) {
            SiteGenie_Admin::get_instance();
            SiteGenie_Metabox::get_instance();
            SiteGenie_Chat::get_instance();
        }
    }

    public function run_cleanup() {
        $days = (int) get_option( 'sitegenie_auto_delete_days', 0 );
        if ( $days > 0 ) {
            SiteGenie_History::delete_older_than( $days );
        }
    }

    public function reindex_post( $post_id, $post ) {
        if ( ! get_option( 'sitegenie_knowledge_enabled', 1 ) ) return;
        if ( wp_is_post_revision( $post_id ) || wp_is_post_autosave( $post_id ) ) return;
        if ( ! in_array( $post->post_type, array_merge( [ 'post', 'page' ], array_keys( get_post_types( [ '_builtin' => false, 'public' => true ] ) ) ), true ) ) return;

        SiteGenie_Knowledge::index_post( $post_id );
    }

    public function add_privacy_policy() {
        if ( ! function_exists( 'wp_add_privacy_policy_content' ) ) return;

        $content = __( 'Questo sito utilizza il plugin SiteGenie, che invia i messaggi della chat e il contesto del sito (nome, settore, tono di comunicazione) al provider AI selezionato dall\'amministratore (Google Gemini, OpenAI o Anthropic Claude) per generare risposte. Nessun dato viene inviato finché un utente non utilizza attivamente la chat o le funzionalità di generazione contenuti. Le conversazioni vengono salvate nel database del sito. Per maggiori informazioni, consulta le privacy policy dei rispettivi provider.', 'sitegenie' );

        wp_add_privacy_policy_content( 'SiteGenie', wp_kses_post( wpautop( $content ) ) );
    }
}
