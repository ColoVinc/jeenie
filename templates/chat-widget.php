<?php if ( ! defined( 'ABSPATH' ) ) exit; ?>

<div id="sitegenie-chat-widget" class="sitegenie-chat-widget position-fixed">

    <button id="sitegenie-chat-toggle" class="sitegenie-chat-toggle rounded-circle border-0 d-flex align-items-center justify-content-center" title="<?php esc_attr_e( 'SiteGenie AI', 'sitegenie' ); ?>">
        <span class="sitegenie-chat-icon"><i class="fa-solid fa-robot text-white"></i></span>
        <span class="sitegenie-chat-close" style="display:none;"><i class="fa-solid fa-x text-white"></i></span>
    </button>

    <div id="sitegenie-chat-window" class="sitegenie-chat-window position-absolute bg-white rounded-3 overflow-hidden" style="display:none;">

        <div class="sitegenie-chat-header text-white d-flex justify-content-between align-items-center px-3 py-2">
            <span class="fw-semibold small"><i class="fa-solid fa-robot"></i> SiteGenie</span>
            <div class="d-flex gap-2">
                <button id="sitegenie-history-btn" class="btn btn-sm btn-outline-light border-0 p-1" title="<?php esc_attr_e( 'Cronologia', 'sitegenie' ); ?>">
                    <i class="fa-solid fa-clock-rotate-left"></i>
                </button>
                <button id="sitegenie-new-chat-btn" class="btn btn-sm btn-outline-light border-0 p-1" title="<?php esc_attr_e( 'Nuova chat', 'sitegenie' ); ?>">
                    <i class="fa-solid fa-plus"></i>
                </button>
            </div>
        </div>

        <div id="sitegenie-history-panel" class="sitegenie-history-panel" style="display:none;">
            <div class="d-flex justify-content-between align-items-center px-3 py-2 border-bottom">
                <span class="fw-semibold small"><?php esc_html_e( 'Cronologia', 'sitegenie' ); ?></span>
                <button id="sitegenie-history-back" class="btn btn-sm p-0 border-0"><i class="fa-solid fa-xmark"></i></button>
            </div>
            <div id="sitegenie-history-list" class="sitegenie-history-list"></div>
        </div>

        <div id="sitegenie-chat-main">
            <div id="sitegenie-chat-messages" class="sitegenie-chat-messages d-flex flex-column gap-2 p-3">
                <div class="sitegenie-chat-message sitegenie-chat-message--ai">
                    <?php esc_html_e( 'Ciao! Sono il tuo assistente AI. Come posso aiutarti oggi?', 'sitegenie' ); ?>
                </div>
            </div>

            <div class="sitegenie-chat-suggestions d-flex flex-wrap gap-1 border-top p-2">
                <button class="sitegenie-suggestion border rounded-pill small" data-msg="<?php esc_attr_e( 'Dammi 5 idee per articoli del blog', 'sitegenie' ); ?>"><i class="fa-solid fa-lightbulb"></i> <?php esc_html_e( 'Idee articoli', 'sitegenie' ); ?></button>
                <button class="sitegenie-suggestion border rounded-pill small" data-msg="<?php esc_attr_e( 'Come posso migliorare la SEO del sito?', 'sitegenie' ); ?>"><i class="fa-solid fa-magnifying-glass"></i> <?php esc_html_e( 'Consigli SEO', 'sitegenie' ); ?></button>
                <button class="sitegenie-suggestion border rounded-pill small" data-msg="<?php esc_attr_e( 'Scrivi un post breve su un argomento a mia scelta', 'sitegenie' ); ?>"><i class="fa-solid fa-pen"></i> <?php esc_html_e( 'Scrivi un post', 'sitegenie' ); ?></button>
            </div>

            <div class="sitegenie-chat-input-wrap d-flex gap-2 border-top p-2 align-items-end">
                <textarea id="sitegenie-chat-input" class="form-control form-control-sm" placeholder="<?php esc_attr_e( 'Scrivi un messaggio...', 'sitegenie' ); ?>" rows="2"></textarea>
                <button id="sitegenie-chat-send" class="btn btn-sm sitegenie-btn-send flex-shrink-0" title="<?php esc_attr_e( 'Invia', 'sitegenie' ); ?>">
                    <i class="fa-solid fa-paper-plane"></i>
                </button>
            </div>
        </div>

    </div>
</div>
