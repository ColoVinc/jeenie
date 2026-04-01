<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Logger — salva ogni chiamata API nel database
 */
class ChatPress_Logger {

    /**
     * Salva un log nel DB
     */
    public static function log(
        string $provider,
        int $prompt_tokens,
        int $completion_tokens,
        string $status = 'success',
        string $error_message = ''
    ): void {
        global $wpdb;

        $wpdb->insert(
            $wpdb->prefix . 'chatpress_logs',
            [
                'created_at'         => current_time( 'mysql' ),
                'provider'           => $provider,
                'prompt_tokens'      => $prompt_tokens,
                'completion_tokens'  => $completion_tokens,
                'status'             => $status,
                'error_message'      => $error_message,
            ],
            [ '%s', '%s', '%d', '%d', '%s', '%s' ]
        );
    }

    /**
     * Recupera i log con paginazione
     */
    public static function get_logs( int $per_page = 30, int $page = 1 ): array {
        global $wpdb;
        $table  = $wpdb->prefix . 'chatpress_logs';
        $offset = ( $page - 1 ) * $per_page;

        return $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM $table ORDER BY created_at DESC LIMIT %d OFFSET %d",
                $per_page,
                $offset
            ),
            ARRAY_A
        );
    }

    /**
     * Conta il totale dei log
     */
    public static function count_logs(): int {
        global $wpdb;
        return (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}chatpress_logs" );
    }

    /**
     * Statistiche aggregate
     */
    public static function get_stats(): array {
        global $wpdb;
        $table = $wpdb->prefix . 'chatpress_logs';

        $stats = $wpdb->get_row(
            "SELECT
                COUNT(*) as total_calls,
                SUM(prompt_tokens + completion_tokens) as total_tokens,
                SUM(CASE WHEN status = 'error' THEN 1 ELSE 0 END) as total_errors
            FROM $table",
            ARRAY_A
        );

        return $stats ?? [ 'total_calls' => 0, 'total_tokens' => 0, 'total_errors' => 0 ];
    }

    /**
     * Svuota i log
     */
    public static function clear_logs(): void {
        global $wpdb;
        $wpdb->query( "TRUNCATE TABLE {$wpdb->prefix}chatpress_logs" );
    }
}
