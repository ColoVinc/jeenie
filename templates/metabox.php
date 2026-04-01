<?php if ( ! defined( 'ABSPATH' ) ) exit; ?>

<div class="chatpress-metabox">

    <div class="chatpress-mb-tabs">
        <button type="button" class="chatpress-tab active" data-tab="content">✍️ Contenuto</button>
        <button type="button" class="chatpress-tab" data-tab="seo">🔍 SEO</button>
    </div>

    <!-- TAB CONTENUTO -->
    <div class="chatpress-tab-content active" id="chatpress-tab-content">
        <div class="chatpress-mb-field">
            <label>Keywords (opzionale)</label>
            <input type="text" id="chatpress-keywords" placeholder="es. scarpe running, sport..." />
        </div>
        <button type="button" id="chatpress-generate-content" class="button button-primary chatpress-btn-full">
            ✨ Genera Bozza Articolo
        </button>
        <div id="chatpress-content-result" class="chatpress-result" style="display:none;">
            <div class="chatpress-result-text"></div>
            <button type="button" class="button chatpress-insert-content">⬆️ Inserisci nell'editor</button>
            <button type="button" class="button chatpress-copy-content">📋 Copia testo</button>
        </div>
    </div>

    <!-- TAB SEO -->
    <div class="chatpress-tab-content" id="chatpress-tab-seo">
        <p class="description">Genera meta title, description ed excerpt basati sul contenuto del post.</p>
        <button type="button" id="chatpress-generate-seo" class="button button-primary chatpress-btn-full">
            🔍 Genera Meta SEO
        </button>
        <div id="chatpress-seo-result" class="chatpress-result" style="display:none;">
            <div class="chatpress-seo-field">
                <label><strong>Meta Title</strong> <span class="chatpress-char-count"></span></label>
                <input type="text" id="chatpress-meta-title" class="widefat" />
            </div>
            <div class="chatpress-seo-field">
                <label><strong>Meta Description</strong> <span class="chatpress-char-count"></span></label>
                <textarea id="chatpress-meta-description" class="widefat" rows="3"></textarea>
            </div>
            <div class="chatpress-seo-field">
                <label><strong>Excerpt</strong></label>
                <textarea id="chatpress-excerpt" class="widefat" rows="2"></textarea>
                <button type="button" class="button chatpress-insert-excerpt">⬆️ Inserisci Excerpt</button>
            </div>
        </div>
    </div>

    <div id="chatpress-loading" class="chatpress-loading" style="display:none;">
        <span class="spinner is-active"></span> L'AI sta elaborando...
    </div>

    <div id="chatpress-error" class="chatpress-error notice notice-error" style="display:none;"></div>

</div>
