<?php
/**
 * Plugin Name: SiteGenie
 * Plugin URI:
 * Description: Assistente AI per WordPress — Chat agentica, generazione contenuti e supporto ACF/CPT con Gemini, OpenAI e Claude.
 * Version:     0.1.0
 * Author:      Vincenzo Colonna
 * Author URI:  https://github.com/ColoVinc/sitegenie
 * License:     GPL-2.0+
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: sitegenie
 * Domain Path: /languages
 * Requires at least: 5.8
 * Requires PHP: 7.4
 */

// Sicurezza: blocca accesso diretto al file
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Costanti del plugin
define( 'SITEGENIE_VERSION', '0.3.0' );
define( 'SITEGENIE_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'SITEGENIE_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'SITEGENIE_PLUGIN_FILE', __FILE__ );

// Autoload delle classi
spl_autoload_register( function( $class ) {
    $prefix = 'SiteGenie_';
    if ( strpos( $class, $prefix ) !== 0 ) return;

    $map = [
        'SiteGenie_Core'          => SITEGENIE_PLUGIN_DIR . 'includes/class-core.php',
        'SiteGenie_API_Connector' => SITEGENIE_PLUGIN_DIR . 'includes/class-api-connector.php',
        'SiteGenie_Gemini'        => SITEGENIE_PLUGIN_DIR . 'includes/connectors/class-gemini.php',
        'SiteGenie_OpenAI'        => SITEGENIE_PLUGIN_DIR . 'includes/connectors/class-openai.php',
        'SiteGenie_Claude'        => SITEGENIE_PLUGIN_DIR . 'includes/connectors/class-claude.php',
        'SiteGenie_Logger'        => SITEGENIE_PLUGIN_DIR . 'includes/class-logger.php',
        'SiteGenie_History'       => SITEGENIE_PLUGIN_DIR . 'includes/class-history.php',
        'SiteGenie_Admin'         => SITEGENIE_PLUGIN_DIR . 'admin/class-admin.php',
        'SiteGenie_Metabox'       => SITEGENIE_PLUGIN_DIR . 'admin/class-metabox.php',
        'SiteGenie_Chat'          => SITEGENIE_PLUGIN_DIR . 'admin/class-chat.php',
        'SiteGenie_Tools'         => SITEGENIE_PLUGIN_DIR . 'includes/class-tools.php',
        'SiteGenie_Knowledge'     => SITEGENIE_PLUGIN_DIR . 'includes/class-knowledge.php',
    ];

    if ( isset( $map[$class] ) && file_exists( $map[$class] ) ) {
        require_once $map[$class];
    }
});

// Avvio del plugin
function sitegenie_init() {
    SiteGenie_Core::get_instance();
}
add_action( 'plugins_loaded', 'sitegenie_init' );

// Hook attivazione / disattivazione
register_activation_hook( __FILE__, 'sitegenie_activate' );
register_deactivation_hook( __FILE__, 'sitegenie_deactivate' );

function sitegenie_activate() {
    set_transient( 'sitegenie_activated', true, 60 );
    sitegenie_create_tables();
    add_option( 'sitegenie_version', SITEGENIE_VERSION );

    if ( ! wp_next_scheduled( 'sitegenie_daily_cleanup' ) ) {
        wp_schedule_event( time(), 'daily', 'sitegenie_daily_cleanup' );
    }
}

// Avviato a plugins_loaded per aggiornamenti DB
function sitegenie_check_version() {
    $installed = get_option( 'sitegenie_version', '0' );
    if ( version_compare( $installed, SITEGENIE_VERSION, '<' ) ) {
        sitegenie_create_tables();
        update_option( 'sitegenie_version', SITEGENIE_VERSION );
    }
}
add_action( 'plugins_loaded', 'sitegenie_check_version', 5 );

function sitegenie_create_tables() {
    global $wpdb;
    $charset = $wpdb->get_charset_collate();
    require_once ABSPATH . 'wp-admin/includes/upgrade.php';

    // Tabella log
    dbDelta( "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}sitegenie_logs (
        id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        provider VARCHAR(50) NOT NULL,
        prompt_tokens INT NOT NULL DEFAULT 0,
        completion_tokens INT NOT NULL DEFAULT 0,
        status VARCHAR(20) NOT NULL DEFAULT 'success',
        error_message TEXT NULL,
        PRIMARY KEY (id)
    ) $charset;" );

    // Tabella conversazioni
    dbDelta( "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}sitegenie_conversations (
        id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
        user_id BIGINT(20) UNSIGNED NOT NULL,
        title VARCHAR(100) NOT NULL DEFAULT '',
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        KEY user_id (user_id)
    ) $charset;" );

    // Tabella messaggi
    dbDelta( "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}sitegenie_messages (
        id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
        conversation_id BIGINT(20) UNSIGNED NOT NULL,
        role VARCHAR(10) NOT NULL,
        content TEXT NOT NULL,
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        KEY conversation_id (conversation_id)
    ) $charset;" );

    // Tabella knowledge base
    dbDelta( "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}sitegenie_knowledge (
        id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
        doc_name VARCHAR(255) NOT NULL,
        chunk_index INT NOT NULL DEFAULT 0,
        content TEXT NOT NULL,
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        KEY doc_name (doc_name),
        FULLTEXT KEY content_ft (content)
    ) $charset;" );
}

function sitegenie_deactivate() {
    wp_clear_scheduled_hook( 'sitegenie_daily_cleanup' );
}
