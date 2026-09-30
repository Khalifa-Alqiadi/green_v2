@php
    $velocityLanguageCode = Helper::currentLanguage()->code;
    $velocityDefaultCode = config('smartend.default_language');
    $velocityPermissionId = Auth::user()?->permissions_id;
    $velocityFields = collect($Fields ?? [])
        ->filter(function ($field) use ($velocityLanguageCode, $velocityPermissionId) {
            if (!in_array($field->lang_code, ['all', $velocityLanguageCode], true)) {
                return false;
            }

            $velocityGroups = array_filter(array_map('trim', explode(',', (string) $field->view_permission_groups)));

            return $velocityGroups === []
                || in_array('0', $velocityGroups, true)
                || ($velocityPermissionId !== null && in_array((string) $velocityPermissionId, $velocityGroups, true));
        });
@endphp

@if($velocityFields->isNotEmpty())
    <dl class="velocity-detail-fields">
        @foreach($velocityFields as $velocityField)
            @php
                $velocityLabel = $velocityField->{'title_'.$velocityLanguageCode}
                    ?: $velocityField->{'title_'.$velocityDefaultCode};
                $velocityValue = optional(collect($Topic->fields)->firstWhere('field_id', $velocityField->id))->field_value;
            @endphp
            @continue((int) $velocityField->type === 99 || blank($velocityValue))
            <div class="velocity-detail-field velocity-detail-field--type-{{ (int) $velocityField->type }}">
                <dt>{!! $velocityLabel !!}</dt>
                <dd class="{{ (int) $velocityField->type === 16 ? 'velocity-rich-content' : '' }}">
                    @if((int) $velocityField->type === 16)
                        {!! str_replace('"#', '"'.Request::url().'#', $velocityValue) !!}
                    @elseif((int) $velocityField->type === 1)
                        {!! Helper::ParseLinks(nl2br(e($velocityValue))) !!}
                    @elseif((int) $velocityField->type === 3)
                        <a href="mailto:{{ $velocityValue }}">{{ $velocityValue }}</a>
                    @elseif((int) $velocityField->type === 15)
                        <a class="velocity-ltr" href="tel:+{{ preg_replace('/\D/', '', $velocityValue) }}">+{{ preg_replace('/\D/', '', $velocityValue) }}</a>
                    @else
                        @include(theme_view('components.topic-field-value'), [
                            'field' => $velocityField,
                            'value' => $velocityValue,
                            'label' => $velocityLabel,
                            'detail' => true,
                        ])
                    @endif
                </dd>
            </div>
        @endforeach
    </dl>
@endif
