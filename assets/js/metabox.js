jQuery(function ($) {

    // Gestione tab
    $('.sitegenie-tab').on('click', function () {
        const tab = $(this).data('tab');
        $('.sitegenie-tab').removeClass('active');
        $('.sitegenie-tab-content').removeClass('active');
        $(this).addClass('active');
        $('#sitegenie-tab-' + tab).addClass('active');
    });

    function showLoading()  { $('#sitegenie-loading').show(); $('#sitegenie-error').hide(); }
    function hideLoading()  { $('#sitegenie-loading').hide(); }
    function showError(msg) { $('#sitegenie-error').text(msg).show(); }

    // GENERA CONTENUTO
    $('#sitegenie-generate-content').on('click', function () {
        const title    = $('#title').val() || $('input[name="post_title"]').val() || '';
        const keywords = $('#sitegenie-keywords').val();

        if (!title) { showError('Inserisci prima il titolo del post.'); return; }

        showLoading();
        $('#sitegenie-content-result').hide();

        $.post(sitegenie.ajax_url, {
            action:   'sitegenie_generate_content',
            nonce:    sitegenie.nonce,
            title:    title,
            keywords: keywords,
            type:     $('#post_type').val() || 'post',
        })
        .done(function (res) {
            hideLoading();
            if (res.success) {
                $('#sitegenie-content-result .sitegenie-result-text').text(res.data.text);
                $('#sitegenie-content-result').show();
            } else {
                showError(res.data);
            }
        })
        .fail(function () { hideLoading(); showError('Errore di connessione.'); });
    });

    // COPIA CONTENUTO GENERATO
    $(document).on('click', '.sitegenie-copy-content', function () {
        var text = $('#sitegenie-content-result .sitegenie-result-text').text();
        if (!text) return;

        // Copia con fallback per HTTP
        if (navigator.clipboard && window.isSecureContext) {
            navigator.clipboard.writeText(text);
        } else {
            var $tmp = $('<textarea>').val(text).css({ position: 'fixed', opacity: 0 }).appendTo('body');
            $tmp[0].select();
            document.execCommand('copy');
            $tmp.remove();
        }

        // Toast notifica in alto a destra
        var $toast = $('<div>')
            .text('✅ Testo copiato!')
            .appendTo('body')
            .attr('style',
                'position:fixed;top:32px;right:20px;background:#1a1a2e;color:#fff;' +
                'padding:10px 20px;border-radius:6px;font-size:13px;z-index:999999;' +
                'opacity:0;transition:opacity 0.3s;pointer-events:none;'
            );
        setTimeout(function () { $toast.css('opacity', 1); }, 10);
        setTimeout(function () { $toast.css('opacity', 0); setTimeout(function () { $toast.remove(); }, 300); }, 2000);
    });

    // INSERISCI CONTENUTO NELL'EDITOR
    $(document).on('click', '.sitegenie-insert-content', function () {
        const text = $('#sitegenie-content-result .sitegenie-result-text').text();
        if (!text) return;

        // Editor classico (TinyMCE) — controlla per primo
        if (typeof tinyMCE !== 'undefined' && tinyMCE.activeEditor && !tinyMCE.activeEditor.isHidden()) {
            tinyMCE.activeEditor.execCommand('mceInsertContent', false, text.replace(/\n/g, '<br>'));
        } else if ($('#content').length) {
            // Modalità testo dell'editor classico
            var $content = $('#content');
            $content.val($content.val() + '\n' + text);
        } else if (typeof wp !== 'undefined' && wp.data && wp.data.dispatch && wp.blocks) {
            // Gutenberg
            var blocks = wp.blocks.rawHandler({ HTML: '<p>' + text.replace(/\n/g, '</p><p>') + '</p>' });
            wp.data.dispatch('core/block-editor').insertBlocks(blocks);
        }
    });

    // GENERA SEO
    $('#sitegenie-generate-seo').on('click', function () {
        const title   = $('#title').val() || '';
        const content = typeof wp !== 'undefined' && wp.data
            ? (wp.data.select('core/block-editor').getBlocks().map(b => b.attributes.content || '').join(' '))
            : (tinyMCE && tinyMCE.activeEditor ? tinyMCE.activeEditor.getContent({ format: 'text' }) : '');

        showLoading();
        $('#sitegenie-seo-result').hide();

        $.post(sitegenie.ajax_url, {
            action:  'sitegenie_generate_seo',
            nonce:   sitegenie.nonce,
            title:   title,
            content: content.substring(0, 1000),
        })
        .done(function (res) {
            hideLoading();
            if (res.success) {
                const d = res.data;
                $('#sitegenie-meta-title').val(d.meta_title || '');
                $('#sitegenie-meta-description').val(d.meta_description || '');
                $('#sitegenie-excerpt').val(d.excerpt || '');
                updateCharCount('#sitegenie-meta-title', 60);
                updateCharCount('#sitegenie-meta-description', 155);
                $('#sitegenie-seo-result').show();
            } else {
                showError(res.data);
            }
        })
        .fail(function () { hideLoading(); showError('Errore di connessione.'); });
    });

    // Contatore caratteri SEO
    function updateCharCount(selector, max) {
        const $el = $(selector);
        const len = $el.val().length;
        const $count = $el.closest('.sitegenie-seo-field').find('.sitegenie-char-count');
        const color = len > max ? '#d63638' : (len > max * 0.85 ? '#dba617' : '#00a32a');
        $count.text(len + '/' + max + ' caratteri').css('color', color);
    }

    $('#sitegenie-meta-title').on('input', function () { updateCharCount('#sitegenie-meta-title', 60); });
    $('#sitegenie-meta-description').on('input', function () { updateCharCount('#sitegenie-meta-description', 155); });

    // Inserisci excerpt
    $(document).on('click', '.sitegenie-insert-excerpt', function () {
        const text = $('#sitegenie-excerpt').val();
        if (text && $('#excerpt').length) {
            $('#excerpt').val(text);
        }
    });

});
