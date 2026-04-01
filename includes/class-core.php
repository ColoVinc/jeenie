<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Classe Core — inizializza tutto il plugin
 */
class ChatPress_Core {

    private static $instance = null;

    public static function get_instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        $this->load_dependencies();
        $this->init_hooks();
    }

    private function load_dependencies() {
        require_once CHATPRESS_PLUGIN_DIR . 'includes/class-logger.php';
        require_once CHATPRESS_PLUGIN_DIR . 'includes/class-api-connector.php';
        require_once CHATPRESS_PLUGIN_DIR . 'includes/connectors/class-gemini.php';
        require_once CHATPRESS_PLUGIN_DIR . 'includes/class-tools.php';

        if ( is_admin() ) {
            require_once CHATPRESS_PLUGIN_DIR . 'admin/class-admin.php';
            require_once CHATPRESS_PLUGIN_DIR . 'admin/class-metabox.php';
            require_once CHATPRESS_PLUGIN_DIR . 'admin/class-chat.php';
        }
    }

    private function init_hooks() {
        // Carica textdomain per traduzioni
        add_action( 'init', [ $this, 'load_textdomain' ] );

        // Inizializza componenti admin
        if ( is_admin() ) {
            ChatPress_Admin::get_instance();
            ChatPress_Metabox::get_instance();
            ChatPress_Chat::get_instance();
        }
    }

    public function load_textdomain() {
        load_plugin_textdomain(
            'chatpress',
            false,
            dirname( plugin_basename( CHATPRESS_PLUGIN_FILE ) ) . '/languages/'
        );
    }
}
