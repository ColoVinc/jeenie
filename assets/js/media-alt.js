jQuery(function ($) {

    $(document).on('click', '.sitegenie-generate-alt', function () {
        var $btn = $(this);
        var id = $btn.data('id');
        var $altField = $btn.closest('.compat-field-sitegenie_alt')
            .siblings('.compat-field-alt')
            .find('input[name="attachments[' + id + '][alt]"]');

        // Fallback: cerca nel form più vicino
        if (!$altField.length) {
            $altField = $('input[name="attachments[' + id + '][alt]"]');
        }

        $btn.prop('disabled', true).text('⏳ Generazione...');

        $.post(sitegenie_alt.ajax_url, {
            action: 'sitegenie_generate_alt',
            nonce: sitegenie_alt.nonce,
            attachment_id: id,
        }).done(function (res) {
            if (res.success) {
                if ($altField.length) {
                    $altField.val(res.data.alt_text).trigger('change');
                }
                $btn.text('✅ ' + res.data.alt_text);
                $btn.after('<p style="color:#00a32a;font-size:12px;margin-top:4px;">Alt text salvato. Ricarica la pagina per vederlo nel campo.</p>');
            } else {
                $btn.text('❌ ' + res.data);
            }
        }).fail(function () {
            $btn.text('❌ Errore di connessione');
        }).always(function () {
            setTimeout(function () {
                $btn.prop('disabled', false).html('🤖 Genera Alt Text con AI');
            }, 3000);
        });
    });

});
