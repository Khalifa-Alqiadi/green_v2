<form method="GET" action="{{ url()->current() }}" id="advanced-search-form">
    @if(filled(request('search_word')))
        <input type="hidden" name="search_word" value="{{ request('search_word') }}">
    @endif
    <div class="velocity-filter-grid">
        @php
            $searchLanguage = Helper::currentLanguage();
            $searchDefaultLanguage = config('smartend.default_language');
        @endphp
        @foreach($WebmasterSection->customFields as $customField)
            @continue(!$customField->in_search)
            @php
                $fieldTitle = $customField->{'title_'.$searchLanguage->code}
                    ?: $customField->{'title_'.$searchDefaultLanguage};
                $fieldDirection = $searchLanguage->direction;
                $fieldVisible = $customField->lang_code === 'all';

                if($customField->lang_code !== 'all') {
                    $fieldLanguage = Helper::LangFromCode($customField->lang_code);
                    $fieldVisible = (bool) ($fieldLanguage?->box_status);
                    $fieldDirection = $fieldLanguage?->direction ?: $fieldDirection;
                }

                $fieldName = 'customField_'.$customField->id;
                $fieldValue = request($fieldName);
            @endphp
            @continue(!$fieldVisible || in_array($customField->type, [8, 9, 10, 11, 12], true))

            <div>
                <label class="velocity-visually-hidden" for="{{ $fieldName }}">{{ $fieldTitle }}</label>
                @if($customField->type == 14)
                    <select class="velocity-select" name="{{ $fieldName }}" id="{{ $fieldName }}">
                        <option value="">{{ $fieldTitle }}</option>
                        <option value="1" @selected((string) $fieldValue === '1')>{{ __('backend.yes') }}</option>
                        <option value="0" @selected((string) $fieldValue === '0')>{{ __('backend.no') }}</option>
                    </select>
                @elseif(in_array($customField->type, [6, 7, 13, 17], true))
                    @php
                        $fieldOptions = $customField->{'details_'.$searchLanguage->code}
                            ?: $customField->{'details_'.$searchDefaultLanguage};
                    @endphp
                    <select class="velocity-select" name="{{ $fieldName }}" id="{{ $fieldName }}">
                        <option value="">{{ $fieldTitle }}</option>
                        @foreach(preg_split('/\r\n|[\r\n]/', (string) $fieldOptions) as $optionIndex => $option)
                            <option value="{{ $optionIndex + 1 }}" @selected((string) $fieldValue === (string) ($optionIndex + 1))>
                                {{ $option }}
                            </option>
                        @endforeach
                    </select>
                @elseif(in_array($customField->type, [4, 5], true))
                    <input
                        class="velocity-field"
                        type="text"
                        name="{{ $fieldName }}"
                        id="{{ $fieldName }}"
                        value="{{ $fieldValue ? Helper::formatDate($fieldValue) : '' }}"
                        maxlength="100"
                        placeholder="{{ $fieldTitle }}"
                        dir="{{ $fieldDirection }}"
                        autocomplete="off"
                    >
                @else
                    <input
                        class="velocity-field"
                        type="{{ $customField->type == 3 ? 'email' : ($customField->type == 2 ? 'number' : 'text') }}"
                        name="{{ $fieldName }}"
                        id="{{ $fieldName }}"
                        value="{{ $fieldValue }}"
                        maxlength="100"
                        placeholder="{{ $fieldTitle }}"
                        dir="{{ $fieldDirection }}"
                        autocomplete="off"
                    >
                @endif
            </div>
        @endforeach

        <button class="velocity-button velocity-button--primary" id="search-btn" type="submit">
            @include(theme_view('components.icon'), ['name' => 'search'])
            {{ __('backend.search') }}
        </button>
    </div>
</form>
