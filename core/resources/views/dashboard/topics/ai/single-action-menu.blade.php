<div class="input-group-btn dropdown smartend-ai-tools">
    <button type="button" class="btn white dropdown-toggle" data-toggle="dropdown" title="{{ __('backend.aiAssistant') }}" aria-label="{{ __('backend.aiAssistant') }}" aria-haspopup="true" aria-expanded="false">
        <i class="material-icons" aria-hidden="true">&#xe39f;</i><span class="caret"></span></button>
    <div class="dropdown-menu pull-right">
        <div class="smartend-ai-menu-heading">
            <i class="material-icons" aria-hidden="true">&#xe39f;</i>{{ __('backend.aiAssistant') }}</div>
        <button type="button" class="dropdown-item {{ $aiActionClass }}" data-language="{{ $aiLanguage }}" @isset($aiFieldId)data-ai-field="{{ $aiFieldId }}"@endisset>
            <i class="material-icons" aria-hidden="true">{{ $aiActionIcon }}</i><span>{{ $aiActionLabel }}</span>
        </button>
    </div>
</div>
