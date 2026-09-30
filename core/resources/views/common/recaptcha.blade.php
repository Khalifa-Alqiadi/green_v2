@php
    $recaptchaAction = $action ?? 'submit';
    $recaptchaLang = @Helper::currentLanguage()->code;
    $recaptchaSiteKey = \App\Services\RecaptchaService::siteKey();
@endphp

@if(\App\Services\RecaptchaService::isEnabled())
    @if(\App\Services\RecaptchaService::isV3())
        <input type="hidden" name="g-recaptcha-response" value="" data-recaptcha-token>
        <input type="hidden" name="g-recaptcha-action" value="{{ $recaptchaAction }}">

        @once
            <script src="https://www.google.com/recaptcha/api.js?{{ http_build_query(['render' => $recaptchaSiteKey, 'hl' => $recaptchaLang]) }}" async defer></script>
        @endonce

        <script>
            (function () {
                var script = document.currentScript;
                var form = script ? script.closest('form') : null;
                if (!form) {
                    return;
                }

                var siteKey = @json($recaptchaSiteKey);
                var action = @json($recaptchaAction);
                var tokenInput = form.querySelector('[data-recaptcha-token]');

                function execute(callback) {
                    if (!window.grecaptcha || !window.grecaptcha.execute) {
                        setTimeout(function () {
                            execute(callback);
                        }, 100);
                        return;
                    }

                    window.grecaptcha.ready(function () {
                        window.grecaptcha.execute(siteKey, {action: action}).then(function (token) {
                            tokenInput.value = token;
                            form.dataset.recaptchaReady = '1';
                            callback();
                        });
                    });
                }

                form.addEventListener('submit', function (event) {
                    if (form.dataset.recaptchaReady === '1') {
                        delete form.dataset.recaptchaReady;
                        return;
                    }

                    event.preventDefault();
                    event.stopImmediatePropagation();

                    var submitter = event.submitter || null;
                    execute(function () {
                        if (form.requestSubmit) {
                            if (submitter) {
                                form.requestSubmit(submitter);
                            } else {
                                form.requestSubmit();
                            }
                            return;
                        }

                        var submitEvent = document.createEvent('Event');
                        submitEvent.initEvent('submit', true, true);
                        if (form.dispatchEvent(submitEvent)) {
                            form.submit();
                        }
                    });
                }, true);
            })();
        </script>
    @else
        <div class="form-group mb-3">
            @once
                {!! NoCaptcha::renderJs($recaptchaLang) !!}
            @endonce
            {!! NoCaptcha::display() !!}
        </div>
    @endif
@endif
