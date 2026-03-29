<?php
/**
 * Plugin Name: ChatPress
 * Plugin URI:  https://portfoliovincenzo.netlify.app/
 * Description: Integrazione AI per WordPress - Generazione contenuti e assistente back-office
 * Version:     0.1.0
 * Author:      Vincenzo Colonna
 * License:     GPL-2.0+
 * Text Domain: chatpress
 */ 

// Sicurezza: blocca accesso diretto al file
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Costanti del plugin
define( 'CHATPRESS_VERSION', '0.1.0' );
define( 'CHATPRESS_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'CHATPRESS_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'CHATPRESS_PLUGIN_FILE', __FILE__ );

// Autoload delle classi
spl_autoload_register( function ( $class ) {
    $prefix = 'ChatPress_';
    if ( strpos( $class, $prefix ) !== 0 ) return;

    $map = [
        'ChatPress_Core'            => CHATPRESS_PLUGIN_DIR . 'includes/class-core.php',
        'ChatPress_API_Connector'   => CHATPRESS_PLUGIN_DIR . 'includes/class-api-connector.php',
        'ChatPress_Gemini'          => CHATPRESS_PLUGIN_DIR . 'includes/connectors/class-gemini.php',
        'ChatPress_Logger'          => CHATPRESS_PLUGIN_DIR . 'includes/class-logger.php',
        'ChatPress_Admin'           => CHATPRESS_PLUGIN_DIR . 'includes/class-admin.php',
        'ChatPress_Metabox'         => CHATPRESS_PLUGIN_DIR . 'includes/class-metabox.php',
        'ChatPress_Chat'            => CHATPRESS_PLUGIN_DIR . 'includes/class-chat.php',
    ];

    if (isset( $map[$class] ) && file_exists( $map[$class] ) ) {
        require_once $map[$class];
    }
});

// Avvio del plugin
function chatpress_init() {
    ChatPress_Core::get_instance();
}
add_action( 'plugins_loaded', 'chatpress_init' );

// Hook attivazione / disattivazione
register_activation_hook( __FILE__, 'chatpress_activate' );
register_deactivation_hook( __FILE__, 'chatpress_deactivate' );

function chatpress_activate() {
    // Crea tabella log nel DB
    global $wpdb;
    $table = $wpdb->prefix . 'chatpress_logs';
    $charset = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE IF NOT EXISTS $table (
        id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        provider VARCHAR(50) NOT NULL,
        prompt_tokens INT NOT NULL DEFAULT 0,
        completion_tokens INT NOT NULL DEFAULT 0,
        status VARCHAR(20) NOT NULL DEFAULT 'success',
        error_message TEXT NULL,
        PRIMARY KEY (id)
    ) $charset;";

    require_once ABSPATH . 'wp-admin/includes/upgrade.php';
    dbDelta( $sql );

    // Salva versione nel db
    add_option( 'chatpress_version', CHATPRESS_VERSION );
}

function chatpress_deactivate() {
    // Per ora non fa nulla
}