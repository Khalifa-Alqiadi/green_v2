<div class="tab-pane {{ Session::get('active_tab') == 'aiSettingsTab' ? 'active' : '' }}" id="tab-ai-settings">
    <div class="ai-settings-form">
        <div class="ai-settings-hero">
            <div class="ai-settings-hero-copy">
                <h4>{{ __('backend.aiSettings') }}</h4>
                <p class="text-muted">{{ __('backend.aiSettingsHint') }}</p>
            </div>
            <label class="ai-master-toggle" for="ai_enabled">
                <span>{{ __('backend.aiEnabled') }}</span>
                <span class="md-switch">
                    <input type="checkbox" name="ai_enabled" id="ai_enabled" value="1"
                           @checked(old('ai_enabled', $settings->ai_enabled))>
                    <i class="primary"></i>
                </span>
            </label>
        </div>

        <div id="ai-settings-fields" @style(['display: none' => !old('ai_enabled', $settings->ai_enabled)])>
            <div class="ai-settings-grid p-x-2">
                <section class="ai-settings-card ai-capabilities-card  m-b-2">
                    <div class="ai-capability-grid">
                        @foreach([
                            'ai_content_enabled' => ['aiContent', 'edit'],
                            'ai_seo_enabled' => ['aiSeo', 'search'],
                            'ai_translation_enabled' => ['aiTranslation', 'translate'],
                            'ai_images_enabled' => ['aiImages', 'image'],
                        ] as $field => [$label, $icon])
                            <label class="ai-capability-option dker" for="{{ $field }}">
                                <span class="ai-capability-label"><i class="material-icons">{{ $icon }}</i> {{ __('backend.'.$label) }}</span>
                                <span class="md-switch">
                                    <input type="checkbox" name="{{ $field }}" id="{{ $field }}" value="1"
                                           @checked(old($field, $settings->{$field}))>
                                    <i class="primary"></i>
                                </span>
                            </label>
                        @endforeach
                    </div>
                </section>

                <section class="ai-settings-card ai-capabilities-card  p-a b-a m-b-0">
                    <div class="ai-card-heading">
                        <div>
                            <h5>{{ __('backend.aiTextSettings') }}</h5>
                            <p class="text-muted">{{ __('backend.aiTextProviderHint') }}</p>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-4 form-group">
                            <label for="ai_text_provider">{{ __('backend.aiProvider') }}</label>
                            <select class="form-control c-select" name="ai_text_provider" id="ai_text_provider">
                                @foreach($providers->textProviders() as $provider)
                                    <option value="{{ $provider }}" @selected(old('ai_text_provider', $textProvider) === $provider)>
                                        {{ $providers->label($provider) }}{{ $configured[$provider] ? ' · '.__('backend.aiConfigured') : '' }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4 form-group">
                            <label for="ai_text_model">{{ __('backend.aiModel') }}</label>
                            <input class="form-control" id="ai_text_model" name="ai_text_model" maxlength="191"
                                   value="{{ old('ai_text_model', $settings->ai_text_model) }}"
                                   placeholder="{{ __('backend.aiProviderDefault') }}">
                        </div>
                        <div class="col-md-4 form-group m-b-0">
                            <label for="ai_default_tone">{{ __('backend.aiTone') }}</label>
                            <select class="form-control c-select" id="ai_default_tone" name="ai_default_tone">
                                @foreach(config('smartend_ai.tones') as $tone)
                                    <option value="{{ $tone }}" @selected(old('ai_default_tone', $settings->ai_default_tone) === $tone)>
                                        {{ ucfirst($tone) }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-5 form-group m-b-0" id="ai-custom-tone-field">
                            <label for="ai_custom_tone">{{ __('backend.aiCustomTone') }}</label>
                            <input class="form-control" id="ai_custom_tone" name="ai_custom_tone" maxlength="500"
                                   value="{{ old('ai_custom_tone', $settings->ai_custom_tone) }}">
                        </div>
                    </div>

                <div class="m-b" id="ai-image-settings">
                    <div class="ai-card-heading">
                        <div>
                            <h5>{{ __('backend.aiImageSettings') }}</h5>
                            <p class="text-muted">{{ __('backend.aiImageProviderHint') }}</p>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-4 form-group">
                            <label for="ai_image_provider">{{ __('backend.aiProvider') }}</label>
                            <select class="form-control c-select" name="ai_image_provider" id="ai_image_provider">
                                @foreach($providers->imageProviders() as $provider)
                                    <option value="{{ $provider }}" @selected(old('ai_image_provider', $imageProvider) === $provider)>
                                        {{ $providers->label($provider) }}{{ $configured[$provider] ? ' · '.__('backend.aiConfigured') : '' }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4 form-group">
                            <label for="ai_image_model">{{ __('backend.aiModel') }}</label>
                            <input class="form-control" id="ai_image_model" name="ai_image_model" maxlength="191"
                                   value="{{ old('ai_image_model', $settings->ai_image_model) }}"
                                   placeholder="{{ __('backend.aiProviderDefault') }}">
                        </div>
                        <div class="col-md-4 form-group m-b-0">
                            <label for="ai_image_style">{{ __('backend.aiStyle') }}</label>
                            <select class="form-control  c-select" id="ai_image_style" name="ai_image_style">
                                @foreach(config('smartend_ai.image_styles') as $value)
                                    <option value="{{ $value }}" @selected(old('ai_image_style', $settings->ai_image_style) === $value)>{{ ucfirst($value) }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-4 form-group m-b-0">
                            <label for="ai_image_orientation">{{ __('backend.aiOrientation') }}</label>
                            <select class="form-control  c-select" id="ai_image_orientation" name="ai_image_orientation">
                                @foreach(config('smartend_ai.image_orientations') as $value)
                                    <option value="{{ $value }}" @selected(old('ai_image_orientation', $settings->ai_image_orientation) === $value)>{{ ucfirst($value) }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4 form-group m-b-0">
                            <label for="ai_image_quality">{{ __('backend.aiQuality') }}</label>
                            <select class="form-control  c-select" id="ai_image_quality" name="ai_image_quality">
                                @foreach(config('smartend_ai.image_qualities') as $value)
                                    <option value="{{ $value }}" @selected(old('ai_image_quality', $settings->ai_image_quality) === $value)>{{ ucfirst($value) }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>

                <div>
                    @foreach(array_unique([...$providers->textProviders(), ...$providers->imageProviders()]) as $provider)
                        <div class="ai-provider-credentials" data-provider="{{ $provider }}">
                            <div class="ai-provider-heading">
                                <strong>{{ $providers->label($provider) }}</strong>
                                <span class="label {{ $configured[$provider] ? 'success' : '' }}">
                                    {{ $configured[$provider] ? __('backend.aiConfigured') : __('backend.aiNotConfigured') }}
                                </span>
                            </div>
                            <div class="row">
                                @foreach($providers->credentialFields($provider) as $field)
                                    @php($hasSavedValue = in_array($field, $storedCredentialFields[$provider] ?? [], true))
                                    <div class="col-md-6 form-group">
                                        <label for="credential-{{ $provider }}-{{ $field }}">
                                            {{ ucfirst(str_replace('_', ' ', $field)) }}
                                            @if($hasSavedValue)<span class="ai-saved-badge">{{ __('backend.aiSaved') }}</span>@endif
                                        </label>
                                        @if($field === 'use_default_credential_provider')
                                            <select class="form-control c-select" id="credential-{{ $provider }}-{{ $field }}"
                                                    name="credentials[{{ $provider }}][{{ $field }}]">
                                                <option value="">{{ __('backend.aiLeaveBlankPreserve') }}</option>
                                                <option value="1">{{ __('backend.yes') }}</option>
                                                <option value="0">{{ __('backend.no') }}</option>
                                            </select>
                                        @else
                                            <input class="form-control" id="credential-{{ $provider }}-{{ $field }}"
                                                   type="{{ in_array($field, $providers->secretFields(), true) ? 'password' : 'text' }}"
                                                   name="credentials[{{ $provider }}][{{ $field }}]" value=""
                                                   autocomplete="new-password" placeholder="{{ __('backend.aiLeaveBlankPreserve') }}">
                                        @endif
                                        @if($hasSavedValue)
                                            <label class="md-check text-muted m-t-sm">
                                                <input type="checkbox" name="remove_credentials[{{ $provider }}][{{ $field }}]" value="1">
                                                <i class="danger"></i> {{ __('backend.aiClearSavedValue') }}
                                            </label>
                                        @endif
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endforeach
                </div>

                </section>
                <section class="ai-settings-card ai-credentials-card p-a b-a">
                    <div class="ai-card-heading">
                        <div>
                            <h5>{{ __('backend.aiAdvancedSettings') }}</h5>
                            <p class="text-muted">{{ __('backend.aiAdvancedSettingsHint') }}</p>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-3 form-group">
                            <label for="ai_default_content_length">{{ __('backend.aiContentLength') }}</label>
                            <select class="form-control c-select" id="ai_default_content_length" name="ai_default_content_length">
                                @foreach(config('smartend_ai.content_lengths') as $length)
                                    <option value="{{ $length }}" @selected(old('ai_default_content_length', $settings->ai_default_content_length) === $length)>{{ ucfirst($length) }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3 form-group">
                            <label for="ai_temperature">{{ __('backend.aiTemperature') }}</label>
                            <input type="number" step="0.05" min="0" max="1" class="form-control" id="ai_temperature"
                                   name="ai_temperature" value="{{ old('ai_temperature', $settings->ai_temperature) }}">
                        </div>
                        <div class="col-md-3 form-group">
                            <label for="ai_max_tokens">{{ __('backend.aiMaxTokens') }}</label>
                            <input type="number" min="256" max="16000" class="form-control" id="ai_max_tokens"
                                   name="ai_max_tokens" value="{{ old('ai_max_tokens', $settings->ai_max_tokens ?: 4000) }}">
                        </div>
                        <div class="col-md-3 form-group">
                            <label for="ai_timeout_seconds">{{ __('backend.aiTimeout') }}</label>
                            <input type="number" min="5" max="300" class="form-control" id="ai_timeout_seconds"
                                   name="ai_timeout_seconds" value="{{ old('ai_timeout_seconds', $settings->ai_timeout_seconds ?: 120) }}">
                        </div>
                    </div>
                </section>

            </div>
        </div>

        <div class="ai-settings-actions p-a-2">
            <button class="btn primary" type="submit" id="save-ai-settings-btn">
                <i class="material-icons">save</i> {{ __('backend.update') }}
            </button>
        </div>
    </div>
</div>
