@php
    $velocityContactFormId = $formId ?? 'velocity-contact-form';
    $velocityContactFeedbackId = $velocityContactFormId.'-feedback';
    $velocityContactFormConfig = [
        'target' => $velocityContactFormId,
        'feedbackTarget' => $velocityContactFeedbackId,
        'submittingLabel' => __('backend.loading'),
        'errorLabel' => __('backend.error'),
    ];
@endphp

<div class="velocity-panel velocity-contact-form">
    <div class="velocity-contact-form__heading">
        <p class="velocity-eyebrow">{{ __('frontend.sendMessage') }}</p>
        <h2>{{ $title ?? __('frontend.getInTouch') }}</h2>
    </div>

    @if(($errors ?? null)?->any())
        @component(theme_view('components.status'), ['tone' => 'error'])
            <ul>
                @foreach($errors->all() as $velocityError)
                    <li>{{ $velocityError }}</li>
                @endforeach
            </ul>
        @endcomponent
    @endif

    <form
        id="{{ $velocityContactFormId }}"
        method="POST"
        action="{{ route('contactPageSubmit') }}"
        aria-describedby="{{ $velocityContactFeedbackId }}"
        data-velocity-native-form
    >
        @csrf
        @honeypot
        <div class="velocity-form-grid">
            <label>
                <span>{{ __('frontend.yourName') }}</span>
                <input class="velocity-field" type="text" name="contact_name" value="{{ old('contact_name') }}" autocomplete="name" required maxlength="100">
            </label>
            <label>
                <span>{{ __('frontend.phone') }}</span>
                <input class="velocity-field velocity-ltr" type="tel" name="contact_phone" value="{{ old('contact_phone') }}" autocomplete="tel" required minlength="5" maxlength="30" inputmode="tel" data-velocity-phone-input data-velocity-phone-full-name="contact_phone_full">
                <input type="hidden" name="contact_phone_full" value="{{ old('contact_phone_full') }}" data-velocity-phone-source="contact_phone">
            </label>
            <label>
                <span>{{ __('frontend.yourEmail') }}</span>
                <input class="velocity-field" type="email" name="contact_email" value="{{ old('contact_email') }}" autocomplete="email" required maxlength="100">
            </label>
            <label>
                <span>{{ __('frontend.subject') }}</span>
                <input class="velocity-field" type="text" name="contact_subject" value="{{ old('contact_subject') }}" required maxlength="100">
            </label>
            <label class="velocity-form-grid__wide">
                <span>{{ __('frontend.message') }}</span>
                <textarea class="velocity-textarea" name="contact_message" rows="5" required>{{ old('contact_message') }}</textarea>
            </label>
        </div>
        @include('common.recaptcha', ['action' => 'contact'])
        <div id="{{ $velocityContactFeedbackId }}" class="velocity-form-feedback" aria-live="polite"></div>
        <button class="velocity-button velocity-button--primary velocity-contact-form__submit" type="submit">
            <span>{{ __('frontend.sendMessage') }}</span>
            <i class="fa-solid fa-arrow-right" aria-hidden="true"></i>
        </button>
    </form>

    <div
        data-velocity-component="cms-form-enhancer"
        data-velocity-config="{{ $velocityContactFormId }}-config"
    ></div>
    <script id="{{ $velocityContactFormId }}-config" type="application/json" data-velocity-config>
        @json($velocityContactFormConfig)
    </script>
</div>
