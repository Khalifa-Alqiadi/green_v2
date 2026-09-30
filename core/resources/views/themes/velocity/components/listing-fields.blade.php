@php
    $velocityLanguageCode = Helper::currentLanguage()->code;
    $velocityDefaultCode = config('smartend.default_language');
    $velocityPermissionId = Auth::user()?->permissions_id;
    $velocityListingFields = collect($fields ?? [])
        ->filter(function ($field) use ($velocityLanguageCode, $velocityPermissionId) {
            if (!$field->in_listing || !in_array($field->lang_code, ['all', $velocityLanguageCode], true)) {
                return false;
            }

            $groups = array_filter(array_map('trim', explode(',', (string) $field->view_permission_groups)));

            return $groups === []
                || in_array('0', $groups, true)
                || ($velocityPermissionId !== null && in_array((string) $velocityPermissionId, $groups, true));
        });
@endphp

@if($velocityListingFields->isNotEmpty())
    <dl class="velocity-topic-fields">
        @foreach($velocityListingFields as $velocityField)
            @php
                $velocityLabel = $velocityField->{'title_'.$velocityLanguageCode}
                    ?: $velocityField->{'title_'.$velocityDefaultCode};
                $velocityValue = optional(collect($topic->fields)->firstWhere('field_id', $velocityField->id))->field_value;
            @endphp
            @continue((int) $velocityField->type === 99 || blank($velocityValue))
            <div>
                <dt>{{ $velocityLabel }}</dt>
                <dd>
                    @include(theme_view('components.topic-field-value'), [
                        'field' => $velocityField,
                        'value' => $velocityValue,
                        'label' => $velocityLabel,
                    ])
                </dd>
            </div>
        @endforeach
    </dl>
@endif
