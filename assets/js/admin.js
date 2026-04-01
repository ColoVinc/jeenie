jQuery(function ($) {

    // Test connessione API
    $('#chatpress-test-api').on('click', function () {
        const $btn    = $(this);
        const $result = $('#chatpress-test-result');

        $btn.prop('disabled', true).text('⏳ Test in corso...');
        $result.removeClass('success error').text('');

        $.post(chatpress.ajax_url, {
            action: 'chatpress_test_api',
            nonce:  chatpress.nonce,
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
    $('#chatpress-clear-logs').on('click', function () {
        if ( ! confirm( 'Sei sicuro di voler svuotare tutti i log? L\'operazione non è reversibile.' ) ) return;

        const $btn = $(this);
        $btn.prop('disabled', true).text('⏳ Svuotamento...');

        $.post(chatpress.ajax_url, {
            action: 'chatpress_clear_logs',
            nonce:  chatpress.nonce,
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

});
