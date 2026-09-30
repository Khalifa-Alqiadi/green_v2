(function ($) {
    'use strict';
    var config = window.SmartendModuleAiConfig;
    if (!config) return;
    var busy = false;
    var modal = $('#smartend-module-ai');
    function error(message) {
        $('#smartend-module-ai-error').prop('hidden', !message).text(message || '');
    }
    function loading(value) {
        busy = value;
        modal.attr('aria-busy', String(value)).find('button, textarea').prop('disabled', value);
        $('#smartend-module-ai-loading').prop('hidden', !value);
    }
    modal.on('hide.bs.modal', function (event) { if (busy) event.preventDefault(); });
    $('#smartend-module-ai-open').on('click', function () {
        if (config.enabled && !busy) { error(''); modal.modal('show'); }
    });
    $('#smartend-module-ai-form').on('submit', function (event) {
        event.preventDefault();
        if (busy || !config.enabled) return;
        var description = String($('#smartend-module-ai-description').val() || '').trim();
        loading(true);
        error('');
        fetch(config.url, {
            method: 'POST', credentials: 'same-origin',
            headers: {'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': config.csrf},
            body: JSON.stringify({description: description})
        }).then(function (response) {
            return response.json().catch(function () { return {}; }).then(function (body) {
                if (!response.ok || !body.redirect) {
                    var messages = [];
                    Object.keys(body.errors || {}).forEach(function (key) {
                        messages = messages.concat(body.errors[key]);
                    });
                    var message = messages.length ? messages.join('\n') : (body.message || config.error);
                    if (body.request_id) message += '\n' + body.request_id;
                    throw new Error(message);
                }
                window.location.assign(body.redirect);
            });
        }).catch(function (failure) {
            error(failure instanceof TypeError ? config.error : failure.message);
            loading(false);
        });
    });
})(jQuery);
