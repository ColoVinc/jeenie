<?php if ( ! defined( 'ABSPATH' ) ) exit; ?>

<div class="sitegenie-metabox">

    <div class="sitegenie-mb-tabs">
        <button type="button" class="sitegenie-tab active" data-tab="content">✍️ <?php esc_html_e( 'Contenuto', 'sitegenie' ); ?></button>
        <button type="button" class="sitegenie-tab" data-tab="seo">🔍 <?php esc_html_e( 'SEO', 'sitegenie' ); ?></button>
    </div>

    <!-- TAB CONTENUTO -->
    <div class="sitegenie-tab-content active" id="sitegenie-tab-content">
        <div class="sitegenie-mb-field">
            <label><?php esc_html_e( 'Keywords (opzionale)', 'sitegenie' ); ?></label>
            <input type="text" id="sitegenie-keywords" placeholder="<?php esc_attr_e( 'es. scarpe running, sport...', 'sitegenie' ); ?>" />
        </div>
        <button type="button" id="sitegenie-generate-content" class="button button-primary sitegenie-btn-full">
            ✨ <?php esc_html_e( 'Genera Bozza Articolo', 'sitegenie' ); ?>
        </button>
        <div id="sitegenie-content-result" class="sitegenie-result" style="display:none;">
            <div class="sitegenie-result-text"></div>
            <button type="button" class="button sitegenie-insert-content">⬆️ <?php esc_html_e( 'Inserisci nell\'editor', 'sitegenie' ); ?></button>
            <button type="button" class="button sitegenie-copy-content">📋 <?php esc_html_e( 'Copia testo', 'sitegenie' ); ?></button>
        </div>
    </div>

    <!-- TAB SEO -->
    <div class="sitegenie-tab-content" id="sitegenie-tab-seo">
        <p class="description"><?php esc_html_e( 'Genera meta title, description ed excerpt basati sul contenuto del post.', 'sitegenie' ); ?></p>
        <button type="button" id="sitegenie-generate-seo" class="button button-primary sitegenie-btn-full">
            🔍 <?php esc_html_e( 'Genera Meta SEO', 'sitegenie' ); ?>
        </button>
        <div id="sitegenie-seo-result" class="sitegenie-result" style="display:none;">
            <div class="sitegenie-seo-field">
                <label><strong>Meta Title</strong> <span class="sitegenie-char-count"></span></label>
                <input type="text" id="sitegenie-meta-title" class="widefat" />
            </div>
            <div class="sitegenie-seo-field">
                <label><strong>Meta Description</strong> <span class="sitegenie-char-count"></span></label>
                <textarea id="sitegenie-meta-description" class="widefat" rows="3"></textarea>
            </div>
            <div class="sitegenie-seo-field">
                <label><strong>Excerpt</strong></label>
                <textarea id="sitegenie-excerpt" class="widefat" rows="2"></textarea>
                <button type="button" class="button sitegenie-insert-excerpt">⬆️ <?php esc_html_e( 'Inserisci Excerpt', 'sitegenie' ); ?></button>
            </div>
        </div>
    </div>

    <div id="sitegenie-loading" class="sitegenie-loading" style="display:none;">
        <span class="spinner is-active"></span> <?php esc_html_e( 'L\'AI sta elaborando...', 'sitegenie' ); ?>
    </div>

    <div id="sitegenie-error" class="sitegenie-error notice notice-error" style="display:none;"></div>

</div>
