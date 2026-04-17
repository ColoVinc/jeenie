<?php if ( ! defined( 'ABSPATH' ) ) exit; ?>

<div id="sitegenie-chat-widget" class="sitegenie-chat-widget">

    <button id="sitegenie-chat-toggle" class="sitegenie-chat-toggle" title="<?php esc_attr_e( 'SiteGenie AI', 'sitegenie' ); ?>">
        <span class="sitegenie-chat-icon"><i class="fa-solid fa-robot"></i></span>
        <span class="sitegenie-chat-close" style="display:none;"><i class="fa-solid fa-x"></i></span>
    </button>

    <div id="sitegenie-chat-window" class="sitegenie-chat-window" style="display:none;">

        <div class="sitegenie-chat-header">
            <span class="sitegenie-chat-header-title"><i class="fa-solid fa-robot"></i> SiteGenie</span>
            <div class="sitegenie-chat-header-actions">
                <button id="sitegenie-history-btn" class="sitegenie-header-btn" title="<?php esc_attr_e( 'Cronologia', 'sitegenie' ); ?>">
                    <i class="fa-solid fa-clock-rotate-left"></i>
                </button>
                <button id="sitegenie-new-chat-btn" class="sitegenie-header-btn" title="<?php esc_attr_e( 'Nuova chat', 'sitegenie' ); ?>">
                    <i class="fa-solid fa-plus"></i>
                </button>
            </div>
        </div>

        <div id="sitegenie-history-panel" class="sitegenie-history-panel" style="display:none;">
            <div class="sitegenie-history-header">
                <span><?php esc_html_e( 'Cronologia', 'sitegenie' ); ?></span>
                <button id="sitegenie-history-back" class="sitegenie-header-btn"><i class="fa-solid fa-xmark"></i></button>
            </div>
            <div id="sitegenie-history-list" class="sitegenie-history-list"></div>
        </div>

        <div id="sitegenie-chat-main">
            <div id="sitegenie-chat-messages" class="sitegenie-chat-messages">
                <div class="sitegenie-chat-message sitegenie-chat-message--ai">
                    <?php esc_html_e( 'Ciao! Sono il tuo assistente AI. Come posso aiutarti oggi?', 'sitegenie' ); ?>
                </div>
            </div>

            <div class="sitegenie-chat-suggestions">
                <button class="sitegenie-suggestion" data-msg="<?php esc_attr_e( 'Dammi 5 idee per articoli del blog', 'sitegenie' ); ?>"><i class="fa-solid fa-lightbulb"></i> <?php esc_html_e( 'Idee articoli', 'sitegenie' ); ?></button>
                <button class="sitegenie-suggestion" data-msg="<?php esc_attr_e( 'Come posso migliorare la SEO del sito?', 'sitegenie' ); ?>"><i class="fa-solid fa-magnifying-glass"></i> <?php esc_html_e( 'Consigli SEO', 'sitegenie' ); ?></button>
                <button class="sitegenie-suggestion" data-msg="<?php esc_attr_e( 'Scrivi un post breve su un argomento a mia scelta', 'sitegenie' ); ?>"><i class="fa-solid fa-pen"></i> <?php esc_html_e( 'Scrivi un post', 'sitegenie' ); ?></button>
            </div>

            <div class="sitegenie-chat-input-wrap">
                <textarea id="sitegenie-chat-input" class="sitegenie-chat-textarea" placeholder="<?php esc_attr_e( 'Scrivi un messaggio...', 'sitegenie' ); ?>" rows="2"></textarea>
                <button id="sitegenie-chat-send" class="sitegenie-btn-send" title="<?php esc_attr_e( 'Invia', 'sitegenie' ); ?>">
                    <i class="fa-solid fa-paper-plane"></i>
                </button>
            </div>
        </div>

    </div>
</div>
