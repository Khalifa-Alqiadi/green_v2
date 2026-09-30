@php
    $velocityPopupSubscribeFormId = 'velocity-popup-subscribe-form-'.($PopupID ?? 'default');
    $velocityPopupSubscribeFeedbackId = $velocityPopupSubscribeFormId.'-feedback';
    $velocityPopupSubscribeConfigId = $velocityPopupSubscribeFormId.'-config';
    $velocityPopupSubscribeConfig = [
        'target' => $velocityPopupSubscribeFormId,
        'feedbackTarget' => $velocityPopupSubscribeFeedbackId,
        'submittingLabel' => __('backend.loading'),
        'errorLabel' => __('frontend.youMessageNotSent'),
        'closeTarget' => 'page-popup-'.($PopupID ?? 'default'),
        'successCloseDelay' => 5000,
    ];
@endphp
<form
    method="POST"
    action="{{ route('subscribeSubmit') }}"
    class="velocity-subscribe-form"
    id="{{ $velocityPopupSubscribeFormId }}"
    aria-describedby="{{ $velocityPopupSubscribeFeedbackId }}"
    data-velocity-native-form
>
    @csrf
    @honeypot
    <label class="velocity-visually-hidden" for="velocity-popup-subscribe-email-{{ $PopupID ?? 'default' }}">
        {{ __('frontend.yourEmail') }}
    </label>
    <input
        class="velocity-field"
        type="email"
        name="subscribe_email"
        id="velocity-popup-subscribe-email-{{ $PopupID ?? 'default' }}"
        placeholder="{{ __('frontend.yourEmail') }}"
        autocomplete="email"
        required
    >
    <button class="velocity-button velocity-button--primary" type="submit">
        {{ __('frontend.subscribe') }}
    </button>
</form>
<div id="{{ $velocityPopupSubscribeFeedbackId }}" class="velocity-form-feedback" aria-live="polite"></div>
<div data-velocity-component="cms-form-enhancer" data-velocity-config="{{ $velocityPopupSubscribeConfigId }}"></div>
<script id="{{ $velocityPopupSubscribeConfigId }}" type="application/json" data-velocity-config>
    @json($velocityPopupSubscribeConfig)
</script>
