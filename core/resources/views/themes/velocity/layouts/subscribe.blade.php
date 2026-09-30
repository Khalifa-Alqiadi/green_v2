@if(Helper::GeneralSiteSettings('style_subscribe'))
    @php
        $velocitySubscribeFormId = 'velocity-footer-subscribe-form';
        $velocitySubscribeFeedbackId = $velocitySubscribeFormId.'-feedback';
        $velocitySubscribeFormConfig = [
            'target' => $velocitySubscribeFormId,
            'feedbackTarget' => $velocitySubscribeFeedbackId,
            'submittingLabel' => __('backend.loading'),
            'errorLabel' => __('frontend.youMessageNotSent'),
        ];
    @endphp
    <section class="velocity-footer__newsletter">
        <h2 class="velocity-footer__title">{{ __('frontend.newsletter') }}</h2>
        <p>{{ __('frontend.subscribeToOurNewsletter') }}</p>
        <form
            method="POST"
            action="{{ route('subscribeSubmit') }}"
            class="velocity-subscribe-form"
            id="{{ $velocitySubscribeFormId }}"
            aria-describedby="{{ $velocitySubscribeFeedbackId }}"
            data-velocity-native-form
        >
            @csrf
            @honeypot
            <label class="velocity-visually-hidden" for="velocity-subscribe-email">{{ __('frontend.yourEmail') }}</label>
            <input
                class="velocity-field"
                type="email"
                name="subscribe_email"
                id="velocity-subscribe-email"
                placeholder="{{ __('frontend.yourEmail') }}"
                required
                autocomplete="email"
            >
            <button class="velocity-button velocity-button--primary" type="submit">
                <span>{{ __('frontend.subscribe') }}</span>
                <span class="velocity-arrow" aria-hidden="true">→</span>
            </button>
        </form>
        <div id="{{ $velocitySubscribeFeedbackId }}" class="velocity-form-feedback" aria-live="polite"></div>
        <div data-velocity-component="cms-form-enhancer" data-velocity-config="{{ $velocitySubscribeFormId }}-config"></div>
        <script id="{{ $velocitySubscribeFormId }}-config" type="application/json" data-velocity-config>
            @json($velocitySubscribeFormConfig)
        </script>
    </section>
@endif
