<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Logger — salva ogni chiamata API nel database
 */
class Jeenie_Logger {

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

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- custom table, write operation
        $wpdb->insert(
            $wpdb->prefix . 'jeenie_logs',
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
        $offset = ( $page - 1 ) * $per_page;

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- custom table, dynamic data
        return $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}jeenie_logs ORDER BY created_at DESC LIMIT %d OFFSET %d",
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
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- custom table, dynamic data
        return (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}jeenie_logs" );
    }

    /**
     * Statistiche aggregate
     */
    public static function get_stats(): array {
        global $wpdb;

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- custom table, dynamic data
        $stats = $wpdb->get_row(
            "SELECT
                COUNT(*) as total_calls,
                SUM(prompt_tokens + completion_tokens) as total_tokens,
                SUM(CASE WHEN status = 'error' THEN 1 ELSE 0 END) as total_errors
            FROM {$wpdb->prefix}jeenie_logs",
            ARRAY_A
        );

        return $stats ?? [ 'total_calls' => 0, 'total_tokens' => 0, 'total_errors' => 0 ];
    }

    /**
     * Svuota i log
     */
    public static function clear_logs(): void {
        global $wpdb;
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- custom table, truncate operation
        $wpdb->query( "TRUNCATE TABLE {$wpdb->prefix}jeenie_logs" );
    }

    /**
     * Dati aggregati per giorno (ultimi 30 giorni) — per i grafici dashboard
     */
    public static function get_daily_stats( int $days = 30 ): array {
        global $wpdb;
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- custom table
        return $wpdb->get_results( $wpdb->prepare(
            "SELECT DATE(created_at) as day,
                    COUNT(*) as calls,
                    SUM(prompt_tokens) as prompt_tokens,
                    SUM(completion_tokens) as completion_tokens
             FROM {$wpdb->prefix}jeenie_logs
             WHERE created_at >= DATE_SUB(NOW(), INTERVAL %d DAY)
             GROUP BY DATE(created_at)
             ORDER BY day ASC",
            $days
        ), ARRAY_A );
    }

    /**
     * Distribuzione chiamate per provider
     */
    public static function get_provider_stats(): array {
        global $wpdb;
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- custom table
        return $wpdb->get_results(
            "SELECT provider, COUNT(*) as calls, SUM(prompt_tokens + completion_tokens) as tokens
             FROM {$wpdb->prefix}jeenie_logs
             GROUP BY provider
             ORDER BY calls DESC",
            ARRAY_A
        );
    }
}
