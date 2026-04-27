<?php
/**
 * Plugin Name: Jeenie – AI for WordPress
 * Plugin URI:  https://github.com/ColoVinc/jeenie
 * Description: AI Assistant for WordPress — Agentic chat, content generation, ACF/CPT support with Gemini, OpenAI, Claude and Groq.
 * Version:     0.1.0
 * Author:      Vincenzo Colonna
 * Author URI:  https://github.com/ColoVinc
 * License:     GPL-2.0+
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: jeenie-ai-assistant
 * Domain Path: /languages
 * Requires at least: 5.8
 * Requires PHP: 7.4
 */

// Sicurezza: blocca accesso diretto al file
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Costanti del plugin
define( 'JEENIE_VERSION', '0.4.0' );
define( 'JEENIE_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'JEENIE_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'JEENIE_PLUGIN_FILE', __FILE__ );

// Autoload delle classi
spl_autoload_register( function( $class ) {
    $prefix = 'Jeenie_';
    if ( strpos( $class, $prefix ) !== 0 ) return;

    $map = [
        'Jeenie_Core'          => JEENIE_PLUGIN_DIR . 'includes/class-core.php',
        'Jeenie_API_Connector' => JEENIE_PLUGIN_DIR . 'includes/class-api-connector.php',
        'Jeenie_Gemini'        => JEENIE_PLUGIN_DIR . 'includes/connectors/class-gemini.php',
        'Jeenie_OpenAI'        => JEENIE_PLUGIN_DIR . 'includes/connectors/class-openai.php',
        'Jeenie_Claude'        => JEENIE_PLUGIN_DIR . 'includes/connectors/class-claude.php',
        'Jeenie_Groq'          => JEENIE_PLUGIN_DIR . 'includes/connectors/class-groq.php',
        'Jeenie_Logger'        => JEENIE_PLUGIN_DIR . 'includes/class-logger.php',
        'Jeenie_History'       => JEENIE_PLUGIN_DIR . 'includes/class-history.php',
        'Jeenie_Admin'         => JEENIE_PLUGIN_DIR . 'admin/class-admin.php',
        'Jeenie_Metabox'       => JEENIE_PLUGIN_DIR . 'admin/class-metabox.php',
        'Jeenie_Chat'          => JEENIE_PLUGIN_DIR . 'admin/class-chat.php',
        'Jeenie_Tools'         => JEENIE_PLUGIN_DIR . 'includes/class-tools.php',
        'Jeenie_Knowledge'     => JEENIE_PLUGIN_DIR . 'includes/class-knowledge.php',
        'Jeenie_Components'    => JEENIE_PLUGIN_DIR . 'includes/class-components.php',
    ];

    if ( isset( $map[$class] ) && file_exists( $map[$class] ) ) {
        require_once $map[$class];
    }
});

// Avvio del plugin
function jeenie_init() {
    Jeenie_Core::get_instance();
}
add_action( 'plugins_loaded', 'jeenie_init' );

// Hook attivazione / disattivazione
register_activation_hook( __FILE__, 'jeenie_activate' );
register_deactivation_hook( __FILE__, 'jeenie_deactivate' );

function jeenie_activate() {
    set_transient( 'jeenie_activated', true, 60 );
    jeenie_create_tables();
    add_option( 'jeenie_version', JEENIE_VERSION );

    if ( ! wp_next_scheduled( 'jeenie_daily_cleanup' ) ) {
        wp_schedule_event( time(), 'daily', 'jeenie_daily_cleanup' );
    }
}

// Avviato a plugins_loaded per aggiornamenti DB
function jeenie_check_version() {
    $installed = get_option( 'jeenie_version', '0' );
    if ( version_compare( $installed, JEENIE_VERSION, '<' ) ) {
        jeenie_create_tables();
        jeenie_preload_docs();
        update_option( 'jeenie_version', JEENIE_VERSION );
    }
}
add_action( 'plugins_loaded', 'jeenie_check_version', 5 );

function jeenie_preload_docs() {
    $docs_dir = JEENIE_PLUGIN_DIR . 'docs/';
    if ( ! is_dir( $docs_dir ) ) return;

    foreach ( glob( $docs_dir . '*.md' ) as $file ) {
        $name    = 'doc:' . basename( $file, '.md' );
        $content = file_get_contents( $file ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
        if ( $content ) {
            Jeenie_Knowledge::add_document( $name, $content );
        }
    }
}

function jeenie_create_tables() {
    global $wpdb;
    $charset = $wpdb->get_charset_collate();
    require_once ABSPATH . 'wp-admin/includes/upgrade.php';

    // Tabella log
    dbDelta( "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}jeenie_logs (
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
    dbDelta( "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}jeenie_conversations (
        id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
        user_id BIGINT(20) UNSIGNED NOT NULL,
        title VARCHAR(100) NOT NULL DEFAULT '',
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        KEY user_id (user_id)
    ) $charset;" );

    // Tabella messaggi
    dbDelta( "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}jeenie_messages (
        id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
        conversation_id BIGINT(20) UNSIGNED NOT NULL,
        role VARCHAR(10) NOT NULL,
        content TEXT NOT NULL,
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        KEY conversation_id (conversation_id)
    ) $charset;" );

    // Tabella knowledge base
    dbDelta( "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}jeenie_knowledge (
        id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
        doc_name VARCHAR(255) NOT NULL,
        chunk_index INT NOT NULL DEFAULT 0,
        content TEXT NOT NULL,
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        KEY doc_name (doc_name),
        FULLTEXT KEY content_ft (content)
    ) $charset;" );

    // Tabella componenti
    dbDelta( "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}jeenie_components (
        id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
        slug VARCHAR(100) NOT NULL,
        name VARCHAR(255) NOT NULL,
        editor VARCHAR(50) NOT NULL,
        status VARCHAR(20) NOT NULL DEFAULT 'active',
        error_message TEXT NULL,
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        UNIQUE KEY slug (slug)
    ) $charset;" );
}

function jeenie_deactivate() {
    wp_clear_scheduled_hook( 'jeenie_daily_cleanup' );
}
