<?php if ( ! defined( 'ABSPATH' ) ) exit; ?>

<div class="jeenie-metabox">

    <div class="jeenie-mb-tabs">
        <button type="button" class="jeenie-tab active" data-tab="content">✍️ <?php esc_html_e( 'Contenuto', 'jeenie-ai-assistant' ); ?></button>
        <button type="button" class="jeenie-tab" data-tab="seo">🔍 <?php esc_html_e( 'SEO', 'jeenie-ai-assistant' ); ?></button>
    </div>

    <!-- TAB CONTENUTO -->
    <div class="jeenie-tab-content active" id="jeenie-tab-content">
        <div class="jeenie-mb-field">
            <label><?php esc_html_e( 'Keywords (opzionale)', 'jeenie-ai-assistant' ); ?></label>
            <input type="text" id="jeenie-keywords" placeholder="<?php esc_attr_e( 'es. scarpe running, sport...', 'jeenie-ai-assistant' ); ?>" />
        </div>
        <button type="button" id="jeenie-generate-content" class="button button-primary jeenie-btn-full">
            ✨ <?php esc_html_e( 'Genera Bozza Articolo', 'jeenie-ai-assistant' ); ?>
        </button>
        <div id="jeenie-content-result" class="jeenie-result" style="display:none;">
            <div class="jeenie-result-text"></div>
            <button type="button" class="button jeenie-insert-content">⬆️ <?php esc_html_e( 'Inserisci nell\'editor', 'jeenie-ai-assistant' ); ?></button>
            <button type="button" class="button jeenie-copy-content">📋 <?php esc_html_e( 'Copia testo', 'jeenie-ai-assistant' ); ?></button>
        </div>
    </div>

    <!-- TAB SEO -->
    <div class="jeenie-tab-content" id="jeenie-tab-seo">
        <p class="description"><?php esc_html_e( 'Genera meta title, description ed excerpt basati sul contenuto del post.', 'jeenie-ai-assistant' ); ?></p>
        <button type="button" id="jeenie-generate-seo" class="button button-primary jeenie-btn-full">
            🔍 <?php esc_html_e( 'Genera Meta SEO', 'jeenie-ai-assistant' ); ?>
        </button>
        <div id="jeenie-seo-result" class="jeenie-result" style="display:none;">
            <div class="jeenie-seo-field">
                <label><strong>Meta Title</strong> <span class="jeenie-char-count"></span></label>
                <input type="text" id="jeenie-meta-title" class="widefat" />
            </div>
            <div class="jeenie-seo-field">
                <label><strong>Meta Description</strong> <span class="jeenie-char-count"></span></label>
                <textarea id="jeenie-meta-description" class="widefat" rows="3"></textarea>
            </div>
            <div class="jeenie-seo-field">
                <label><strong>Excerpt</strong></label>
                <textarea id="jeenie-excerpt" class="widefat" rows="2"></textarea>
                <button type="button" class="button jeenie-insert-excerpt">⬆️ <?php esc_html_e( 'Inserisci Excerpt', 'jeenie-ai-assistant' ); ?></button>
            </div>
        </div>
    </div>

    <div id="jeenie-loading" class="jeenie-loading" style="display:none;">
        <span class="spinner is-active"></span> <?php esc_html_e( 'L\'AI sta elaborando...', 'jeenie-ai-assistant' ); ?>
    </div>

    <div id="jeenie-error" class="jeenie-error notice notice-error" style="display:none;"></div>

</div>
