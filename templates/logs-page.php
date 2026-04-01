<?php if ( ! defined( 'ABSPATH' ) ) exit; ?>
<div class="wrap chatpress-settings">

    <div class="chatpress-header">
        <h1>📋 ChatPress — Log Chiamate</h1>
    </div>

    <div class="chatpress-stats-row">
        <div class="chatpress-stat-card">
            <span class="chatpress-stat-number"><?php echo intval( $stats['total_calls'] ); ?></span>
            <span class="chatpress-stat-label">Chiamate Totali</span>
        </div>
        <div class="chatpress-stat-card">
            <span class="chatpress-stat-number"><?php echo number_format( intval( $stats['total_tokens'] ) ); ?></span>
            <span class="chatpress-stat-label">Token Usati</span>
        </div>
        <div class="chatpress-stat-card">
            <span class="chatpress-stat-number"><?php echo intval( $stats['total_errors'] ); ?></span>
            <span class="chatpress-stat-label">Errori</span>
        </div>
    </div>

    <?php if ( $total_items > 0 ) : ?>
        <div style="margin-bottom: 16px; display: flex; align-items: center; justify-content: space-between;">
            <button type="button" id="chatpress-clear-logs" class="button button-secondary" style="color: #d63638;">
                🗑️ Svuota Log
            </button>
            <span class="chatpress-log-count" style="color: #757575; font-size: 13px;">
                <?php echo intval( $total_items ); ?> registrazioni totali
            </span>
        </div>
    <?php endif; ?>

    <?php if ( empty( $logs ) ) : ?>
        <div class="chatpress-card">
            <p>Nessuna chiamata registrata ancora. Inizia a usare ChatPress per vedere i log qui.</p>
        </div>
    <?php else : ?>
        <div class="chatpress-card">
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th>Data</th>
                        <th>Provider</th>
                        <th>Prompt Token</th>
                        <th>Completion Token</th>
                        <th>Totale</th>
                        <th>Stato</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ( $logs as $log ) : ?>
                        <tr>
                            <td><?php echo esc_html( $log['created_at'] ); ?></td>
                            <td><strong><?php echo esc_html( ucfirst( $log['provider'] ) ); ?></strong></td>
                            <td><?php echo intval( $log['prompt_tokens'] ); ?></td>
                            <td><?php echo intval( $log['completion_tokens'] ); ?></td>
                            <td><?php echo intval( $log['prompt_tokens'] ) + intval( $log['completion_tokens'] ); ?></td>
                            <td>
                                <?php if ( $log['status'] === 'success' ) : ?>
                                    <span class="chatpress-badge chatpress-badge--success">✅ OK</span>
                                <?php else : ?>
                                    <span class="chatpress-badge chatpress-badge--error" title="<?php echo esc_attr( $log['error_message'] ); ?>">❌ Errore</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <?php if ( $total_pages > 1 ) : ?>
                <div class="chatpress-pagination">
                    <?php
                    $base_url = admin_url( 'admin.php?page=chatpress-logs' );
                    if ( $current > 1 ) :
                    ?>
                        <a href="<?php echo esc_url( add_query_arg( 'paged', $current - 1, $base_url ) ); ?>" class="button">← Precedente</a>
                    <?php endif; ?>

                    <span class="chatpress-pagination-info">
                        Pagina <?php echo $current; ?> di <?php echo $total_pages; ?>
                    </span>

                    <?php if ( $current < $total_pages ) : ?>
                        <a href="<?php echo esc_url( add_query_arg( 'paged', $current + 1, $base_url ) ); ?>" class="button">Successiva →</a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
    <?php endif; ?>

</div>
