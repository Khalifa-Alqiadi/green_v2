@if(session('doneMessage'))
    @component(theme_view('components.status'), ['tone' => 'success'])
        {!! session('doneMessage') !!}
    @endcomponent
@endif
@if(session('errorMessage') || ($errors ?? null)?->any())
    @component(theme_view('components.status'), ['tone' => 'error'])
        @if(session('errorMessage')){!! session('errorMessage') !!}@endif
        @if(($errors ?? null)?->any())
            <ul>
                @foreach($errors->all() as $velocityError)<li>{{ $velocityError }}</li>@endforeach
            </ul>
        @endif
    @endcomponent
@endif

@if(($FormSectionID ?? 0) > 0)
    @php
        $velocityFormSection = Helper::WebmasterSection($FormSectionID);
        $velocityFormCategories = Helper::SectionCategories($FormSectionID);
        $velocityCode = @Helper::currentLanguage()->code;
        $velocityDefaultCode = config('smartend.default_language');
        $velocityFormId = 'velocity-public-form-'.$FormSectionID.'-'.($Topic->id ?? 'standalone');
        $velocityAllowedImages = collect(explode(',', (string) config('filesystems.allowed_image_types')))
            ->map(fn ($extension) => '.'.ltrim(trim($extension), '.'))
            ->filter()
            ->implode(',');
        $velocityAllowedFiles = collect(explode(',', (string) config('filesystems.allowed_file_types')))
            ->map(fn ($extension) => '.'.ltrim(trim($extension), '.'))
            ->filter()
            ->implode(',');
        $velocityAllowedVideos = '.mp4,.ogv,.webm';
    @endphp
    @if($velocityFormSection)
        <div class="velocity-form-block">
            @if(($viewStyle ?? '') !== 'simple')
                <h2>{{ $velocityFormSection->{'title_'.$velocityCode} ?: $velocityFormSection->{'title_'.$velocityDefaultCode} }}</h2>
            @endif
            <form id="{{ $velocityFormId }}" method="POST" action="{{ route('formSubmit') }}" enctype="multipart/form-data" data-velocity-native-form>
                @csrf
                @honeypot
                <div class="velocity-form-grid">
                    @if($velocityFormSection->date_status)
                        <label>
                            <span>{{ __('backend.topicDate') }}</span>
                            <input class="velocity-field" type="date" name="date" value="{{ old('date', date('Y-m-d')) }}" required>
                        </label>
                    @else
                        <input type="hidden" name="date" value="{{ date('Y-m-d') }}">
                    @endif
                    @if($velocityFormSection->expire_date_status)
                        <label>
                            <span>{{ __('backend.expireDate') }}</span>
                            <input class="velocity-field" type="date" name="expire_date" value="{{ old('expire_date') }}" required>
                        </label>
                    @endif
                    @if($velocityFormSection->sections_status != 0)
                        <div class="velocity-form-control">
                            <label for="{{ $velocityFormId }}-section"><span>{{ __('backend.categories') }}</span></label>
                            <select id="{{ $velocityFormId }}-section" class="velocity-select" name="section_id" required data-velocity-enhanced-select data-label="{{ __('backend.categories') }}" data-placeholder="{{ __('backend.select') }}" data-search-label="{{ __('backend.search') }}">
                                <option value="">{{ __('backend.select') }}</option>
                                @foreach($velocityFormCategories as $velocityCategory)
                                    <option value="{{ $velocityCategory->id }}" @selected(old('section_id') == $velocityCategory->id)>
                                        {{ $velocityCategory->{'title_'.$velocityCode} ?: $velocityCategory->{'title_'.$velocityDefaultCode} }}
                                    </option>
                                    @foreach($velocityCategory->fatherSections as $velocityChildCategory)
                                        <option value="{{ $velocityChildCategory->id }}" @selected(old('section_id') == $velocityChildCategory->id)>
                                            — {{ $velocityChildCategory->{'title_'.$velocityCode} ?: $velocityChildCategory->{'title_'.$velocityDefaultCode} }}
                                        </option>
                                    @endforeach
                                @endforeach
                            </select>
                        </div>
                    @else
                        <input type="hidden" name="section_id" value="0">
                    @endif

                    @if($velocityFormSection->title_status)
                        @foreach(Helper::languagesList() as $velocityLanguage)
                            @if($velocityLanguage->box_status)
                                <label class="velocity-form-grid__wide">
                                    <span>{{ __('backend.topicName') }} {!! Helper::languageName($velocityLanguage) !!}</span>
                                    <input class="velocity-field" type="text" name="title_{{ $velocityLanguage->code }}" value="{{ old('title_'.$velocityLanguage->code) }}" maxlength="100" required dir="{{ $velocityLanguage->direction }}">
                                </label>
                            @endif
                        @endforeach
                    @endif
                    @if($velocityFormSection->longtext_status)
                        @foreach(Helper::languagesList() as $velocityLanguage)
                            @if($velocityLanguage->box_status)
                                <label class="velocity-form-grid__wide">
                                    <span>{{ __('backend.bannerDetails') }} {!! Helper::languageName($velocityLanguage) !!}</span>
                                    <textarea class="velocity-textarea" name="details_{{ $velocityLanguage->code }}" rows="7" required dir="{{ $velocityLanguage->direction }}">{{ old('details_'.$velocityLanguage->code) }}</textarea>
                                </label>
                            @endif
                        @endforeach
                    @endif
                    @if($velocityFormSection->photo_status)
                        <label>
                            <span>{{ __('backend.topicPhoto') }}</span>
                            <input class="velocity-field" type="file" name="photo_file" accept="{{ $velocityAllowedImages ?: 'image/*' }}">
                            <small>{{ __('backend.imagesTypes') }}</small>
                        </label>
                    @endif
                    @if($velocityFormSection->attach_file_status)
                        <label>
                            <span>{{ __('backend.topicAttach') }}</span>
                            <input class="velocity-field" type="file" name="attach_file" accept="{{ $velocityAllowedFiles }}">
                            <small>{{ __('backend.attachTypes') }}</small>
                        </label>
                    @endif

                    @foreach($velocityFormSection->customFields as $velocityField)
                        @php
                            $velocityGroups = filled($velocityField->add_permission_groups) ? explode(',', $velocityField->add_permission_groups) : [];
                            $velocityLanguage = $velocityField->lang_code === 'all' ? null : Helper::LangFromCode($velocityField->lang_code);
                            $velocityFieldVisible = ($velocityGroups === [] || in_array('0', $velocityGroups, true))
                                && ($velocityLanguage === null || $velocityLanguage->box_status);
                            $velocityFieldTitle = $velocityField->{'title_'.$velocityCode} ?: $velocityField->{'title_'.$velocityDefaultCode};
                            $velocityFieldDetails = $velocityField->{'details_'.$velocityCode} ?: $velocityField->{'details_'.$velocityDefaultCode};
                            $velocityOptions = preg_split('/\r\n|[\r\n]/', (string) $velocityFieldDetails, -1, PREG_SPLIT_NO_EMPTY);
                            $velocityFieldName = 'customField_'.$velocityField->id;
                            $velocityDirection = $velocityLanguage->direction ?? @Helper::currentLanguage()->direction;
                            $velocityOldValues = collect((array) old($velocityFieldName, $velocityField->default_value))
                                ->flatMap(fn ($value) => is_string($value) ? preg_split('/,\s*/', $value, -1, PREG_SPLIT_NO_EMPTY) : [$value])
                                ->map(fn ($value) => (string) $value);
                        @endphp
                        @if($velocityFieldVisible)
                            @if($velocityField->type == 99)
                                <div class="velocity-rich-content velocity-form-grid__wide">{!! $velocityFieldDetails !!}</div>
                            @elseif($velocityField->type == 17)
                                <fieldset class="velocity-form-options">
                                    <legend>{{ $velocityFieldTitle }}</legend>
                                    @foreach($velocityOptions as $velocityOption)
                                        <label><input type="checkbox" name="{{ $velocityFieldName }}[]" value="{{ $loop->iteration }}" @checked($velocityOldValues->contains((string) $loop->iteration))> {{ $velocityOption }}</label>
                                    @endforeach
                                </fieldset>
                            @elseif($velocityField->type == 13)
                                <fieldset class="velocity-form-options">
                                    <legend>{{ $velocityFieldTitle }}</legend>
                                    @foreach($velocityOptions as $velocityOption)
                                        <label><input type="radio" name="{{ $velocityFieldName }}" value="{{ $loop->iteration }}" @checked($velocityOldValues->contains((string) $loop->iteration)) @required($velocityField->required)> {{ $velocityOption }}</label>
                                    @endforeach
                                </fieldset>
                            @elseif($velocityField->type == 14)
                                <label class="velocity-form-check"><input type="checkbox" name="{{ $velocityFieldName }}" value="1" @checked($velocityOldValues->contains('1')) @required($velocityField->required)> {{ $velocityFieldTitle }}</label>
                            @elseif((int) $velocityField->type === 18)
                                @php
                                    $velocityRecords = $velocityField->parent_module_id > 0
                                        ? Helper::Topics($velocityField->parent_module_id, 0, 0, 2)
                                        : collect();
                                @endphp
                                <div class="velocity-form-control">
                                    <label for="{{ $velocityFieldName }}"><span>{{ $velocityFieldTitle }}</span></label>
                                    <select id="{{ $velocityFieldName }}" class="velocity-select" name="{{ $velocityFieldName }}" @required($velocityField->required) data-velocity-enhanced-select data-label="{{ $velocityFieldTitle }}" data-placeholder="{{ __('backend.select') }}" data-search-label="{{ __('backend.search') }}">
                                        <option value="">{{ __('backend.select') }}</option>
                                        @foreach($velocityRecords as $velocityRecord)
                                            @php
                                                $velocityRecordTitle = $velocityRecord->{'title_'.$velocityCode}
                                                    ?: $velocityRecord->{'title_'.$velocityDefaultCode};
                                                if(blank($velocityRecordTitle)) {
                                                    $velocityRecordTitleFieldIds = collect($velocityRecord->webmasterSection?->customFields)
                                                        ->where('type', 0)
                                                        ->filter(fn ($field) => in_array($field->lang_code, ['all', $velocityCode], true))
                                                        ->pluck('id');
                                                    $velocityRecordTitle = optional(collect($velocityRecord->fields)->first(
                                                        fn ($field) => $velocityRecordTitleFieldIds->contains($field->field_id) && filled($field->field_value)
                                                    ))->field_value;
                                                }
                                            @endphp
                                            <option value="{{ $velocityRecord->id }}" @selected(old($velocityFieldName, $velocityField->default_value) == $velocityRecord->id)>
                                                {{ $velocityRecordTitle }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            @elseif(in_array((int) $velocityField->type, [6, 7], true))
                                <div class="velocity-form-control">
                                    <label for="{{ $velocityFieldName }}"><span>{{ $velocityFieldTitle }}</span></label>
                                    <select id="{{ $velocityFieldName }}" class="velocity-select" name="{{ $velocityFieldName }}{{ $velocityField->type == 7 ? '[]' : '' }}" @if($velocityField->type == 7) multiple @endif @required($velocityField->required) data-velocity-enhanced-select data-label="{{ $velocityFieldTitle }}" data-placeholder="{{ __('backend.select') }}" data-search-label="{{ __('backend.search') }}">
                                        @if($velocityField->type == 6)<option value="">{{ __('backend.select') }}</option>@endif
                                        @foreach($velocityOptions as $velocityOption)
                                            <option value="{{ $loop->iteration }}" @selected($velocityOldValues->contains((string) $loop->iteration))>{{ $velocityOption }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            @elseif(in_array((int) $velocityField->type, [8, 9, 10], true))
                                <label>
                                    <span>{{ $velocityFieldTitle }}</span>
                                    <input
                                        class="velocity-field"
                                        type="file"
                                        name="{{ $velocityFieldName }}"
                                        accept="{{ (int) $velocityField->type === 8 ? ($velocityAllowedImages ?: 'image/*') : ((int) $velocityField->type === 10 ? $velocityAllowedVideos : $velocityAllowedFiles) }}"
                                        @required($velocityField->required)
                                    >
                                    <small>
                                        @if((int) $velocityField->type === 8)
                                            {{ __('backend.imagesTypes') }}
                                        @elseif((int) $velocityField->type === 10)
                                            {{ __('backend.videoTypes') }}
                                        @else
                                            {{ __('backend.allowedFileTypes') }}: {{ $velocityAllowedFiles }}
                                        @endif
                                    </small>
                                </label>
                            @elseif(in_array((int) $velocityField->type, [1, 16], true))
                                <label class="velocity-form-grid__wide">
                                    <span>{{ $velocityFieldTitle }}</span>
                                    <textarea class="velocity-textarea" name="{{ $velocityFieldName }}" rows="5" dir="{{ $velocityDirection }}" @required($velocityField->required)>{{ old($velocityFieldName, $velocityField->default_value) }}</textarea>
                                </label>
                            @else
                                @php
                                    $velocityInputType = match ((int) $velocityField->type) {
                                        2 => 'number',
                                        3 => 'email',
                                        4 => 'date',
                                        5 => 'datetime-local',
                                        11, 12 => 'url',
                                        15 => 'tel',
                                        default => 'text',
                                    };
                                    $velocityInputValue = old($velocityFieldName, $velocityField->default_value);
                                    if(filled($velocityInputValue) && in_array((int) $velocityField->type, [4, 5], true)) {
                                        $velocityInputTimestamp = strtotime((string) $velocityInputValue);
                                        if($velocityInputTimestamp !== false) {
                                            $velocityInputValue = (int) $velocityField->type === 4
                                                ? date('Y-m-d', $velocityInputTimestamp)
                                                : date('Y-m-d\\TH:i', $velocityInputTimestamp);
                                        }
                                    }
                                @endphp
                                <label>
                                    <span>
                                        {{ $velocityFieldTitle }}
                                        @if((int) $velocityField->type === 11)<i class="fa-brands fa-youtube" aria-hidden="true"></i>@endif
                                        @if((int) $velocityField->type === 12)<i class="fa-brands fa-vimeo-v" aria-hidden="true"></i>@endif
                                    </span>
                                    <input
                                        class="velocity-field"
                                        type="{{ $velocityInputType }}"
                                        name="{{ $velocityFieldName }}"
                                        value="{{ $velocityInputValue }}"
                                        dir="{{ in_array((int) $velocityField->type, [11, 12, 15], true) ? 'ltr' : $velocityDirection }}"
                                        @if((int) $velocityField->type === 2) min="0" step="0.01" @endif
                                        @if((int) $velocityField->type === 3) maxlength="100" @endif
                                        @if(in_array((int) $velocityField->type, [11, 12], true)) maxlength="191" @endif
                                        @if((int) $velocityField->type === 15) autocomplete="tel" inputmode="tel" minlength="5" maxlength="30" @endif
                                        @if((int) $velocityField->type === 0) maxlength="190" @endif
                                        @if((int) $velocityField->type === 15) data-velocity-phone-input data-velocity-phone-full-name="{{ $velocityFieldName }}_phone_full" @endif
                                        @required($velocityField->required)
                                    >
                                    @if((int) $velocityField->type === 15)
                                        <input type="hidden" name="{{ $velocityFieldName }}_phone_full" value="{{ old($velocityFieldName.'_phone_full') }}" data-velocity-phone-source="{{ $velocityFieldName }}">
                                    @endif
                                </label>
                            @endif
                        @endif
                    @endforeach
                </div>
                @include('common.recaptcha', ['action' => 'public_form'])
                <input type="hidden" name="TopicID" value="{{ $Topic->id ?? '' }}">
                <input type="hidden" name="WebmasterSectionId" value="{{ encrypt($velocityFormSection->id) }}">
                <button class="velocity-button velocity-button--primary" type="submit">{{ __('backend.submit') }}</button>
            </form>
        </div>
    @endif
@endif
