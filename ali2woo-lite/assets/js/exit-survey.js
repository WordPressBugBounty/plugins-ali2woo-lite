jQuery(function($) {
    const deactSelector = '#'+a2wl_data.deactivateLinkId+
        ', #deactivate-ali2' + 'woo-lite,' + '#deactivate-ali2' + 'woo';
    $(deactSelector).on('click', function(e) {
        e.preventDefault();

        let deactivateUrl = $(this).attr('href');

        let modal = $('<div id="a2wl-exit-survey" class="a2wl-modal">' +
            '<button type="button" id="a2wl-exit-survey-close" class="a2wl-exit-survey__close">&times;</button>' +
            '<div class="a2wl-exit-survey__header">' +
            '<img src="' + a2wl_data.logo + '" alt="AliNext (Lite version) logo" class="a2wl-exit-survey__logo">' +
            '<h2>' + a2wl_data.lang.title + '</h2>' +
            '<p class="a2wl-exit-survey__subtitle">' + a2wl_data.lang.subtitle + '</p>' +
            '</div>' +
            '<form id="a2wl-exit-survey-form">' +
            '<label><input type="radio" name="reason" value="complex"> ' + a2wl_data.lang.reason_complex + '</label>' +
            '<label><input type="radio" name="reason" value="conflict"> ' + a2wl_data.lang.reason_conflict + '</label>' +
            '<label><input type="radio" name="reason" value="other" checked> ' + a2wl_data.lang.reason_other + '</label>' +
            '<input type="text" name="other" id="a2wl-exit-survey-other" placeholder="' + a2wl_data.lang.other_placeholder + '">' +
            '<div class="a2wl-exit-survey__contact">' +
            '<h3>' + a2wl_data.lang.contact_title + '</h3>' +
            '<p>' + a2wl_data.lang.contact_text + '</p>' +
            '<input type="email" name="contact_email" id="a2wl-exit-survey-email" placeholder="' + a2wl_data.lang.email_placeholder + '">' +
            '</div>' +
            '<button type="submit" class="a2wl-btn a2wl-btn--primary">' + a2wl_data.lang.submit_btn + '</button>' +
            '<button type="button" id="a2wl-exit-survey-skip" class="a2wl-btn a2wl-btn--secondary">' + a2wl_data.lang.skip_btn + '</button>' +
            '</form>' +
            '</div>');

        let overlay = $('<div id="a2wl-exit-survey-overlay"></div>');
        $('body').append(overlay).append(modal);

        $('input[name="reason"]').on('change', function() {
            if ($(this).val() === 'other') {
                $('#a2wl-exit-survey-other').show();
            } else {
                $('#a2wl-exit-survey-other').hide().val('');
            }
        });

        $('#a2wl-exit-survey-skip').on('click', function() {
            $('#a2wl-exit-survey, #a2wl-exit-survey-overlay').remove();
            window.location.href = deactivateUrl;
        });

        $('#a2wl-exit-survey-close').on('click', function() {
            $('#a2wl-exit-survey, #a2wl-exit-survey-overlay').remove();
        });

        $('#a2wl-exit-survey-form').on('submit', function(ev) {
            ev.preventDefault();

            let params = {
                action: 'a2wl_exit_survey_submit',
                ali2woo_nonce: a2wl_data.nonce,
                reason: $('input[name="reason"]:checked').val(),
                other: $('#a2wl-exit-survey-other').val(),
                contact_email: $('#a2wl-exit-survey-email').val()
            };

            $.post(a2wl_data.ajaxurl, params)
                .always(function() {
                    $('#a2wl-exit-survey, #a2wl-exit-survey-overlay').remove();
                    window.location.href = deactivateUrl;
                });
        });
    });
});
