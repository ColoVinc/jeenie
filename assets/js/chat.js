jQuery(function ($) {

    const $widget   = $('#chatpress-chat-widget');
    const $toggle   = $('#chatpress-chat-toggle');
    const $window   = $('#chatpress-chat-window');
    const $messages = $('#chatpress-chat-messages');
    const $input    = $('#chatpress-chat-input');
    const $send     = $('#chatpress-chat-send');

    const STORAGE_KEY_HISTORY  = 'chatpress_history';
    const STORAGE_KEY_MESSAGES = 'chatpress_messages';
    const STORAGE_KEY_SESSION  = 'chatpress_session';

    // Storico conversazione
    let conversationHistory = [];

    // Se la sessione WP è cambiata (logout/login), pulisci tutto
    var savedSession = sessionStorage.getItem(STORAGE_KEY_SESSION);
    if (savedSession && savedSession !== chatpress_chat.session_id) {
        sessionStorage.removeItem(STORAGE_KEY_HISTORY);
        sessionStorage.removeItem(STORAGE_KEY_MESSAGES);
    }
    sessionStorage.setItem(STORAGE_KEY_SESSION, chatpress_chat.session_id);

    // Mappa tool → etichetta leggibile per il badge
    const toolLabels = {
        create_post:    '✅ Post creato',
        update_post:    '✏️ Post aggiornato',
        delete_post:    '🗑️ Post eliminato',
        get_posts:      '📋 Post recuperati',
        get_media:      '🖼️ Media recuperati',
        get_categories: '🗂️ Categorie recuperate',
        get_site_info:  '🌐 Info sito recuperate',
    };

    // ── Ripristina sessione precedente ────────────────────────────
    restoreSession();

    // ── Toggle apertura/chiusura ──────────────────────────────────
    $toggle.on('click', function () {
        const isOpen = $window.is(':visible');
        $window.toggle(!isOpen);
        $('.chatpress-chat-icon').toggle(isOpen);
        $('.chatpress-chat-close').toggle(!isOpen);
        if (!isOpen) { $input.focus(); scrollToBottom(); }
    });

    // ── Suggerimenti rapidi ───────────────────────────────────────
    $(document).on('click', '.chatpress-suggestion', function () {
        $input.val($(this).data('msg'));
        sendMessage();
    });

    // ── Invio ─────────────────────────────────────────────────────
    $send.on('click', sendMessage);
    $input.on('keydown', function (e) {
        if (e.key === 'Enter' && !e.shiftKey) {
            e.preventDefault();
            sendMessage();
        }
    });

    function sendMessage() {
        const msg = $input.val().trim();
        if (!msg) return;

        // Nascondi suggerimenti dopo il primo messaggio
        $('.chatpress-chat-suggestions').hide();

        appendMessage(msg, 'user');
        $input.val('').prop('disabled', true);
        $send.prop('disabled', true);

        const $loading = appendMessage('⏳ Elaborazione in corso...', 'loading');

        $.post(chatpress_chat.ajax_url, {
            action:  'chatpress_chat',
            nonce:   chatpress_chat.nonce,
            message: msg,
            history: JSON.stringify(conversationHistory),
        })
        .done(function (res) {
            $loading.remove();

            if (res.success) {
                const data = res.data;

                // Aggiorna lo storico
                conversationHistory = data.history || [];

                // Mostra il badge azione se un tool è stato eseguito
                if (data.action_taken && data.action_taken.tool) {
                    const label = toolLabels[data.action_taken.tool] || '⚡ Azione eseguita';
                    const result = data.action_taken.result || {};

                    let extraHtml = '';
                    if (data.action_taken.tool === 'create_post' && result.edit_url) {
                        extraHtml = ' — <a href="' + result.edit_url + '" target="_blank">Apri nell\'editor</a>';
                    }

                    appendBadge(label + extraHtml);
                }

                appendMessage(data.text, 'ai');
                saveSession();

            } else {
                appendMessage('❌ ' + res.data, 'ai');
            }
        })
        .fail(function () {
            $loading.remove();
            appendMessage('❌ Errore di connessione.', 'ai');
        })
        .always(function () {
            $input.prop('disabled', false);
            $send.prop('disabled', false);
            $input.focus();
        });
    }

    function appendMessage(text, type) {
        const $msg = $('<div>')
            .addClass('chatpress-chat-message chatpress-chat-message--' + type)
            .text(text);
        $messages.append($msg);
        scrollToBottom();
        return $msg;
    }

    function appendBadge(html) {
        const $badge = $('<div class="chatpress-action-badge">').html(html);
        $messages.append($badge);
        scrollToBottom();
    }

    function scrollToBottom() {
        $messages.scrollTop($messages[0].scrollHeight);
    }

    // ── Persistenza sessionStorage ────────────────────────────────

    function saveSession() {
        try {
            sessionStorage.setItem(STORAGE_KEY_HISTORY, JSON.stringify(conversationHistory));
            // Salva solo i messaggi visibili (user + ai, no loading)
            var msgs = [];
            $messages.find('.chatpress-chat-message--user, .chatpress-chat-message--ai').each(function () {
                msgs.push({ type: $(this).hasClass('chatpress-chat-message--user') ? 'user' : 'ai', text: $(this).text() });
            });
            $messages.find('.chatpress-action-badge').each(function () {
                msgs.push({ type: 'badge', html: $(this).html() });
            });
            // Mantieni ordine DOM
            var ordered = [];
            $messages.children().each(function () {
                var $el = $(this);
                if ($el.hasClass('chatpress-chat-message--user'))     ordered.push({ type: 'user', text: $el.text() });
                else if ($el.hasClass('chatpress-chat-message--ai'))  ordered.push({ type: 'ai', text: $el.text() });
                else if ($el.hasClass('chatpress-action-badge'))      ordered.push({ type: 'badge', html: $el.html() });
            });
            sessionStorage.setItem(STORAGE_KEY_MESSAGES, JSON.stringify(ordered));
        } catch (e) {}
    }

    function restoreSession() {
        try {
            var history = sessionStorage.getItem(STORAGE_KEY_HISTORY);
            var msgs    = sessionStorage.getItem(STORAGE_KEY_MESSAGES);
            if (!history || !msgs) return;

            conversationHistory = JSON.parse(history);
            var parsed = JSON.parse(msgs);
            if (!parsed.length) return;

            // Rimuovi messaggio di benvenuto e suggerimenti
            $messages.empty();
            $('.chatpress-chat-suggestions').hide();

            parsed.forEach(function (m) {
                if (m.type === 'badge') appendBadge(m.html);
                else appendMessage(m.text, m.type);
            });
        } catch (e) {}
    }

});
