<?php if ( ! defined( 'ABSPATH' ) ) exit; ?>
<div class="wrap sitegenie-settings">

    <div class="sitegenie-header rounded-3 mb-4 d-flex align-items-center gap-3 p-4">
        <h1 class="text-white m-0 fs-4"><i class="fa-solid fa-robot"></i> <?php esc_html_e( 'SiteGenie — Log Chiamate', 'sitegenie' ); ?></h1>
    </div>

    <div class="row g-3 mb-4">
        <div class="col">
            <div class="card text-center">
                <div class="card-body py-3">
                    <span class="sitegenie-stat-number"><?php echo esc_html( intval( $stats['total_calls'] ) ); ?></span>
                    <span class="sitegenie-stat-label"><?php esc_html_e( 'Chiamate Totali', 'sitegenie' ); ?></span>
                </div>
            </div>
        </div>
        <div class="col">
            <div class="card text-center">
                <div class="card-body py-3">
                    <span class="sitegenie-stat-number"><?php echo esc_html( number_format( intval( $stats['total_tokens'] ) ) ); ?></span>
                    <span class="sitegenie-stat-label"><?php esc_html_e( 'Token Usati', 'sitegenie' ); ?></span>
                </div>
            </div>
        </div>
        <div class="col">
            <div class="card text-center">
                <div class="card-body py-3">
                    <span class="sitegenie-stat-number"><?php echo esc_html( intval( $stats['total_errors'] ) ); ?></span>
                    <span class="sitegenie-stat-label"><?php esc_html_e( 'Errori', 'sitegenie' ); ?></span>
                </div>
            </div>
        </div>
    </div>

    <?php if ( $total_items > 0 ) : ?>
        <div class="d-flex justify-content-between align-items-center mb-3">
            <button type="button" id="sitegenie-clear-logs" class="btn btn-outline-danger btn-sm">
                <i class="fa-solid fa-trash"></i> <?php esc_html_e( 'Svuota Log', 'sitegenie' ); ?>
            </button>
            <span class="text-muted small">
                <?php
                // translators: %d is the total number of log entries
                echo esc_html( sprintf( __( '%d registrazioni totali', 'sitegenie' ), intval( $total_items ) ) ); ?>
            </span>
        </div>
    <?php endif; ?>

    <?php if ( empty( $logs ) ) : ?>
        <div class="card">
            <div class="card-body">
                <p class="mb-0"><?php esc_html_e( 'Nessuna chiamata registrata ancora. Inizia a usare SiteGenie per vedere i log qui.', 'sitegenie' ); ?></p>
            </div>
        </div>
    <?php else : ?>
        <div class="card p-0">
            <div class="card-body p-0">
                <table class="table table-striped table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th><?php esc_html_e( 'Data', 'sitegenie' ); ?></th>
                            <th><?php esc_html_e( 'Provider', 'sitegenie' ); ?></th>
                            <th><?php esc_html_e( 'Prompt Token', 'sitegenie' ); ?></th>
                            <th><?php esc_html_e( 'Completion Token', 'sitegenie' ); ?></th>
                            <th><?php esc_html_e( 'Totale', 'sitegenie' ); ?></th>
                            <th><?php esc_html_e( 'Stato', 'sitegenie' ); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ( $logs as $sitegenie_log ) : ?>
                            <tr>
                                <td><?php echo esc_html( $sitegenie_log['created_at'] ); ?></td>
                                <td><strong><?php echo esc_html( ucfirst( $sitegenie_log['provider'] ) ); ?></strong></td>
                                <td><?php echo esc_html( intval( $sitegenie_log['prompt_tokens'] ) ); ?></td>
                                <td><?php echo esc_html( intval( $sitegenie_log['completion_tokens'] ) ); ?></td>
                                <td><?php echo esc_html( intval( $sitegenie_log['prompt_tokens'] ) + intval( $sitegenie_log['completion_tokens'] ) ); ?></td>
                                <td>
                                    <?php if ( $sitegenie_log['status'] === 'success' ) : ?>
                                        <span class="badge bg-success"><i class="fa-solid fa-check"></i> OK</span>
                                    <?php else : ?>
                                        <span class="badge bg-danger" title="<?php echo esc_attr( $sitegenie_log['error_message'] ); ?>"><i class="fa-solid fa-xmark"></i> <?php esc_html_e( 'Errore', 'sitegenie' ); ?></span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <?php if ( $total_pages > 1 ) : ?>
                <div class="card-footer d-flex justify-content-center align-items-center gap-3">
                    <?php
                    $sitegenie_base_url = admin_url( 'admin.php?page=sitegenie-logs' );
                    if ( $current > 1 ) :
                    ?>
                        <a href="<?php echo esc_url( add_query_arg( 'paged', $current - 1, $sitegenie_base_url ) ); ?>" class="btn btn-outline-secondary btn-sm">
                            <i class="fa-solid fa-chevron-left"></i> <?php esc_html_e( 'Precedente', 'sitegenie' ); ?>
                        </a>
                    <?php endif; ?>

                    <span class="text-muted small">
                        <?php
                        // translators: %1$d is the current page number, %2$d is the total number of pages
                        echo esc_html( sprintf( __( 'Pagina %1$d di %2$d', 'sitegenie' ), $current, $total_pages ) ); ?>
                    </span>

                    <?php if ( $current < $total_pages ) : ?>
                        <a href="<?php echo esc_url( add_query_arg( 'paged', $current + 1, $sitegenie_base_url ) ); ?>" class="btn btn-outline-secondary btn-sm">
                            <?php esc_html_e( 'Successiva', 'sitegenie' ); ?> <i class="fa-solid fa-chevron-right"></i>
                        </a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
    <?php endif; ?>

</div>
