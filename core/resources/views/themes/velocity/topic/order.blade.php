@php
    $velocityOrderFormId = 'velocity-order-form-'.$Topic->id;
    $velocityOrderFeedbackId = $velocityOrderFormId.'-feedback';
    $velocityOrderFormConfig = [
        'target' => $velocityOrderFormId,
        'feedbackTarget' => $velocityOrderFeedbackId,
        'submittingLabel' => __('backend.loading'),
        'errorLabel' => __('backend.error'),
    ];
@endphp
<section class="velocity-topic-section velocity-order" id="order" aria-labelledby="velocity-order-title">
    <div class="velocity-panel velocity-submission-panel velocity-order-composer">
        <div class="velocity-order-composer__heading">
            <span class="velocity-order-composer__icon" aria-hidden="true">
                @include(theme_view('components.icon'), ['name' => 'order'])
            </span>
            <h2 id="velocity-order-title">{{ __('frontend.orderForm') }}</h2>
        </div>
        @if(($errors ?? null)?->any())
            @component(theme_view('components.status'), ['tone' => 'error'])
                <ul>
                    @foreach($errors->all() as $velocityError)<li>{{ $velocityError }}</li>@endforeach
                </ul>
            @endcomponent
        @endif
        <form class="velocity-order-form" id="{{ $velocityOrderFormId }}" method="POST" action="{{ route('orderSubmit') }}" data-velocity-native-form>
            @csrf
            @honeypot
            <div class="velocity-form-grid">
                <label for="{{ $velocityOrderFormId }}-name">
                    <span>{{ __('frontend.name') }}</span>
                    <input
                        class="velocity-field"
                        id="{{ $velocityOrderFormId }}-name"
                        type="text"
                        name="order_name"
                        value="{{ old('order_name', Auth::user()?->name) }}"
                        autocomplete="name"
                        required
                        maxlength="100"
                        @error('order_name') aria-invalid="true" aria-describedby="{{ $velocityOrderFormId }}-name-error" @enderror
                    >
                    @error('order_name')
                        <small class="velocity-field-error" id="{{ $velocityOrderFormId }}-name-error" role="alert">{{ $message }}</small>
                    @enderror
                </label>
                <label for="{{ $velocityOrderFormId }}-email">
                    <span>{{ __('frontend.email') }}</span>
                    <input
                        class="velocity-field"
                        id="{{ $velocityOrderFormId }}-email"
                        type="email"
                        name="order_email"
                        value="{{ old('order_email', Auth::user()?->email) }}"
                        autocomplete="email"
                        inputmode="email"
                        required
                        maxlength="100"
                        @error('order_email') aria-invalid="true" aria-describedby="{{ $velocityOrderFormId }}-email-error" @enderror
                    >
                    @error('order_email')
                        <small class="velocity-field-error" id="{{ $velocityOrderFormId }}-email-error" role="alert">{{ $message }}</small>
                    @enderror
                </label>
                <label class="velocity-form-grid__wide" for="{{ $velocityOrderFormId }}-phone">
                    <span>{{ __('frontend.phone') }}</span>
                    <input
                        class="velocity-field velocity-ltr"
                        id="{{ $velocityOrderFormId }}-phone"
                        type="tel"
                        name="order_phone"
                        value="{{ old('order_phone') }}"
                        autocomplete="tel"
                        inputmode="tel"
                        required
                        minlength="5"
                        maxlength="30"
                        data-velocity-phone-input
                        data-velocity-phone-full-name="order_phone_full"
                        @error('order_phone') aria-invalid="true" aria-describedby="{{ $velocityOrderFormId }}-phone-error" @enderror
                    >
                    <input type="hidden" name="order_phone_full" value="{{ old('order_phone_full') }}" data-velocity-phone-source="order_phone">
                    @error('order_phone')
                        <small class="velocity-field-error" id="{{ $velocityOrderFormId }}-phone-error" role="alert">{{ $message }}</small>
                    @enderror
                </label>
                <label class="velocity-form-grid__wide" for="{{ $velocityOrderFormId }}-message">
                    <span>{{ __('frontend.notes') }}</span>
                    <textarea
                        class="velocity-textarea"
                        id="{{ $velocityOrderFormId }}-message"
                        name="order_message"
                        rows="6"
                        required
                        @error('order_message') aria-invalid="true" aria-describedby="{{ $velocityOrderFormId }}-message-error" @enderror
                    >{{ old('order_message') }}</textarea>
                    @error('order_message')
                        <small class="velocity-field-error" id="{{ $velocityOrderFormId }}-message-error" role="alert">{{ $message }}</small>
                    @enderror
                </label>
            </div>
            @include('common.recaptcha', ['action' => 'order'])
            <input type="hidden" name="topic_id" value="{{ $Topic->id }}">
            <div id="{{ $velocityOrderFeedbackId }}" class="velocity-form-feedback" aria-live="polite"></div>
            <div class="velocity-order-form__footer">
                <button class="velocity-button velocity-button--primary" type="submit">
                    {{ __('frontend.sendOrder') }}
                    <span class="velocity-arrow" aria-hidden="true">→</span>
                </button>
            </div>
        </form>
        <div data-velocity-component="cms-form-enhancer" data-velocity-config="{{ $velocityOrderFormId }}-config"></div>
        <script id="{{ $velocityOrderFormId }}-config" type="application/json" data-velocity-config>
            @json($velocityOrderFormConfig)
        </script>
    </div>
</section>
