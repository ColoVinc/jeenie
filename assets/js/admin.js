jQuery(function ($) {

    // Toggle sezioni provider
    function toggleProvider() {
        var provider = $('#sitegenie-provider-select').val();
        $('.sitegenie-provider-section').hide();
        $('#sitegenie-provider-' + provider).show();
    }
    $('#sitegenie-provider-select').on('change', toggleProvider);
    toggleProvider();

    // Test connessione API
    $('#sitegenie-test-api').on('click', function () {
        const $btn    = $(this);
        const $result = $('#sitegenie-test-result');

        $btn.prop('disabled', true).text('⏳ Test in corso...');
        $result.removeClass('success error').text('');

        $.post(sitegenie.ajax_url, {
            action: 'sitegenie_test_api',
            nonce:  sitegenie.nonce,
        })
        .done(function (res) {
            if (res.success) {
                $result.addClass('success').text('✅ ' + res.data);
            } else {
                $result.addClass('error').text('❌ ' + res.data);
            }
        })
        .fail(function () {
            $result.addClass('error').text('❌ Errore di connessione.');
        })
        .always(function () {
            $btn.prop('disabled', false).text('🔌 Testa Connessione');
        });
    });

    // Svuota log
    $('#sitegenie-clear-logs').on('click', function () {
        if ( ! confirm( 'Sei sicuro di voler svuotare tutti i log? L\'operazione non è reversibile.' ) ) return;

        const $btn = $(this);
        $btn.prop('disabled', true).text('⏳ Svuotamento...');

        $.post(sitegenie.ajax_url, {
            action: 'sitegenie_clear_logs',
            nonce:  sitegenie.nonce,
        })
        .done(function (res) {
            if (res.success) {
                location.reload();
            } else {
                alert('Errore: ' + res.data);
                $btn.prop('disabled', false).text('🗑️ Svuota Log');
            }
        })
        .fail(function () {
            alert('Errore di connessione.');
            $btn.prop('disabled', false).text('🗑️ Svuota Log');
        });
    });


    // ── Componenti ────────────────────────────────────────────

    // Toggle stato componente
    $(document).on('click', '.sitegenie-comp-toggle', function () {
        var $btn = $(this);
        $.post(sitegenie.ajax_url, {
            action: 'sitegenie_toggle_component',
            nonce: sitegenie.nonce,
            slug: $btn.data('slug'),
            status: $btn.data('status'),
        }).done(function (res) {
            if (res.success) location.reload();
            else alert(res.data);
        });
    });

    // Elimina componente
    $(document).on('click', '.sitegenie-comp-delete', function () {
        if (!confirm('Eliminare questo componente? I file verranno rimossi.')) return;
        $.post(sitegenie.ajax_url, {
            action: 'sitegenie_delete_component',
            nonce: sitegenie.nonce,
            slug: $(this).data('slug'),
        }).done(function (res) {
            if (res.success) location.reload();
            else alert(res.data);
        });
    });

    // Disattiva tutti
    $('#sitegenie-deactivate-all').on('click', function () {
        if (!confirm('Disattivare tutti i componenti?')) return;
        $.post(sitegenie.ajax_url, {
            action: 'sitegenie_toggle_component',
            nonce: sitegenie.nonce,
            slug: '__all__',
            status: 'inactive',
        }).done(function () { location.reload(); });
    });

    // Mostra errore componente
    $(document).on('click', '.sitegenie-comp-error', function () {
        var msg = $(this).data('error') || 'Errore sconosciuto';
        alert('Errore componente:\n\n' + msg);
    });

    // Mostra dettaglio errore nei log
    $(document).on('click', '.sitegenie-log-error', function () {
        var msg = $(this).data('error') || 'Errore sconosciuto';
        var $modal = $('<div style="position:fixed;inset:0;background:rgba(0,0,0,0.5);z-index:999999;display:flex;align-items:center;justify-content:center;">'
            + '<div style="background:#fff;border-radius:8px;padding:24px;max-width:500px;width:90%;box-shadow:0 8px 32px rgba(0,0,0,0.2);">'
            + '<h3 style="margin:0 0 12px;font-size:15px;color:#d63638;"><i class="fa-solid fa-circle-exclamation"></i> Dettaglio Errore</h3>'
            + '<p style="margin:0 0 16px;font-size:13px;color:#333;word-break:break-word;">' + $('<span>').text(msg).html() + '</p>'
            + '<button style="background:#0f3460;color:#fff;border:0;padding:6px 16px;border-radius:4px;cursor:pointer;font-size:13px;">Chiudi</button>'
            + '</div></div>');
        $modal.on('click', 'button', function () { $modal.remove(); });
        $modal.on('click', function (e) { if (e.target === this) $modal.remove(); });
        $('body').append($modal);
    });

    // ── Knowledge Base ───────────────────────────────────────────

    // Carica file .txt nel textarea
    $('#sitegenie-kb-file').on('change', function () {
        var file = this.files[0];
        if (!file) return;
        var reader = new FileReader();
        reader.onload = function (e) {
            $('#sitegenie-kb-content').val(e.target.result);
            if (!$('#sitegenie-kb-name').val()) {
                $('#sitegenie-kb-name').val(file.name.replace(/\.txt$/i, ''));
            }
        };
        reader.readAsText(file);
    });

    // Upload documento
    $('#sitegenie-kb-upload').on('click', function () {
        var name    = $('#sitegenie-kb-name').val().trim();
        var content = $('#sitegenie-kb-content').val().trim();
        if (!name || !content) { $('#sitegenie-kb-result').show().text('⚠️ Nome e contenuto obbligatori.'); return; }

        var $btn = $(this);
        $btn.prop('disabled', true).text('⏳ Salvataggio...');

        $.post(sitegenie.ajax_url, {
            action: 'sitegenie_upload_knowledge',
            nonce: sitegenie.nonce,
            doc_name: name,
            doc_content: content,
        }).done(function (res) {
            if (res.success) {
                $('#sitegenie-kb-result').show().css('color', '#00a32a').text('✅ ' + res.data.message);
                setTimeout(function () { location.reload(); }, 1000);
            } else {
                $('#sitegenie-kb-result').show().css('color', '#d63638').text('❌ ' + res.data);
            }
        }).fail(function () {
            $('#sitegenie-kb-result').show().css('color', '#d63638').text('❌ Errore di connessione.');
        }).always(function () {
            $btn.prop('disabled', false).html('<i class="fa-solid fa-plus"></i> Salva Documento');
        });
    });

    // Elimina documento
    $(document).on('click', '.sitegenie-kb-delete', function () {
        var name = $(this).data('name');
        if (!confirm('Eliminare il documento "' + name + '"?')) return;

        $.post(sitegenie.ajax_url, {
            action: 'sitegenie_delete_knowledge',
            nonce: sitegenie.nonce,
            doc_name: name,
        }).done(function (res) {
            if (res.success) location.reload();
            else alert('Errore: ' + res.data);
        });
    });

    // Indicizza tutti i post (RAG)
    $('#sitegenie-index-posts').on('click', function () {
        var $btn = $(this);
        $btn.prop('disabled', true).html('<i class="fa-solid fa-spinner fa-spin"></i> Indicizzazione...');

        $.post(sitegenie.ajax_url, {
            action: 'sitegenie_index_posts',
            nonce: sitegenie.nonce,
        }).done(function (res) {
            if (res.success) {
                $('#sitegenie-index-result').show().css('color', '#00a32a').text('✅ ' + res.data.message);
                setTimeout(function () { location.reload(); }, 1500);
            } else {
                $('#sitegenie-index-result').show().css('color', '#d63638').text('❌ ' + res.data);
            }
        }).fail(function () {
            $('#sitegenie-index-result').show().css('color', '#d63638').text('❌ Errore di connessione.');
        }).always(function () {
            $btn.prop('disabled', false).html('<i class="fa-solid fa-arrows-rotate"></i> Indicizza tutti i post');
        });
    });
});
