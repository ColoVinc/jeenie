<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Jeenie_Components — gestisce i componenti generati dall'AI
 */
class Jeenie_Components {

    private static $components_dir = null;

    /**
     * Restituisce il percorso della cartella components
     */
    public static function get_dir(): string {
        if ( null === self::$components_dir ) {
            self::$components_dir = JEENIE_PLUGIN_DIR . 'components/';
        }
        return self::$components_dir;
    }

    /**
     * Carica tutti i componenti attivi con sandbox
     */
    public static function load_active(): void {
        // Safe mode: disattiva tutto via URL
        if ( isset( $_GET['jeenie_safe_mode'] ) && $_GET['jeenie_safe_mode'] === '1' ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
            self::deactivate_all();
            return;
        }

        $components = self::get_all();
        foreach ( $components as $comp ) {
            if ( $comp['status'] !== 'active' ) continue;

            $file = self::get_dir() . $comp['slug'] . '/' . $comp['slug'] . '.php';
            if ( ! file_exists( $file ) ) continue;

            // Sandbox: cattura errori fatali
            try {
                ob_start();
                $error_before = error_get_last();
                include_once $file;
                $error_after = error_get_last();
                ob_end_clean();

                // Se c'è un nuovo errore fatale, disattiva
                if ( $error_after && $error_after !== $error_before && in_array( $error_after['type'], [ E_ERROR, E_PARSE, E_COMPILE_ERROR ] ) ) {
                    self::set_status( $comp['slug'], 'error', $error_after['message'] );
                }
            } catch ( \Throwable $e ) {
                ob_end_clean();
                self::set_status( $comp['slug'], 'error', $e->getMessage() );
            }
        }
    }

    /**
     * Salva un nuovo componente (file + metadati DB)
     */
    public static function create( string $slug, string $name, string $editor, string $php_code, string $css_code = '', string $js_code = '' ): array {
        $slug = sanitize_file_name( $slug );
        $dir  = self::get_dir() . $slug . '/';

        // Crea cartelle
        wp_mkdir_p( $dir . 'assets/css/' );
        wp_mkdir_p( $dir . 'assets/js/' );

        // Salva file PHP
        // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents
        file_put_contents( $dir . $slug . '.php', $php_code );

        // Salva CSS se presente
        if ( $css_code ) {
            // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents
            file_put_contents( $dir . 'assets/css/' . $slug . '.css', $css_code );
        }

        // Salva JS se presente
        if ( $js_code ) {
            // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents
            file_put_contents( $dir . 'assets/js/' . $slug . '.js', $js_code );
        }

        // Salva metadati nel DB
        global $wpdb;
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- custom table, write operation
        $wpdb->replace(
            $wpdb->prefix . 'jeenie_components',
            [
                'slug'       => $slug,
                'name'       => sanitize_text_field( $name ),
                'editor'     => sanitize_text_field( $editor ),
                'status'     => 'active',
                'created_at' => current_time( 'mysql' ),
            ],
            [ '%s', '%s', '%s', '%s', '%s' ]
        );

        return [ 'success' => true, 'slug' => $slug, 'message' => "Componente \"$name\" creato e attivato." ];
    }

    /**
     * Lista tutti i componenti
     */
    public static function get_all(): array {
        global $wpdb;
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- custom table
        return $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}jeenie_components ORDER BY created_at DESC", ARRAY_A ) ?: [];
    }

    /**
     * Cambia stato di un componente
     */
    public static function set_status( string $slug, string $status, string $error = '' ): void {
        global $wpdb;
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- custom table
        $wpdb->update(
            $wpdb->prefix . 'jeenie_components',
            [ 'status' => $status, 'error_message' => $error ],
            [ 'slug' => $slug ],
            [ '%s', '%s' ],
            [ '%s' ]
        );
    }

    /**
     * Disattiva tutti i componenti (safe mode)
     */
    public static function deactivate_all(): void {
        global $wpdb;
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- custom table
        $wpdb->query( "UPDATE {$wpdb->prefix}jeenie_components SET status = 'inactive'" );
    }

    /**
     * Elimina un componente (file + DB)
     */
    public static function delete( string $slug ): void {
        $dir = self::get_dir() . sanitize_file_name( $slug ) . '/';

        // Rimuovi file ricorsivamente
        if ( is_dir( $dir ) ) {
            $files = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator( $dir, \RecursiveDirectoryIterator::SKIP_DOTS ),
                \RecursiveIteratorIterator::CHILD_FIRST
            );
            foreach ( $files as $file ) {
                if ( $file->isDir() ) {
                    // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_rmdir -- removing plugin-generated component directories
                    rmdir( $file->getRealPath() );
                } else {
                    wp_delete_file( $file->getRealPath() );
                }
            }
            // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_rmdir -- removing plugin-generated component directory
            rmdir( $dir );
        }

        global $wpdb;
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- custom table
        $wpdb->delete( $wpdb->prefix . 'jeenie_components', [ 'slug' => $slug ], [ '%s' ] );
    }
}
