@if(($aiCapabilities['content'] ?? false) || ($aiCapabilities['translation'] ?? false))
    @php($aiInputGroup = ($aiPlacement ?? ($aiTarget === 'title' ? 'input' : 'editor')) === 'input')
    <div class="smartend-ai-tools {{ $aiInputGroup ? 'input-group-btn dropdown' : 'smartend-ai-editor-tools dropdown'.((@$aiRichText)?' dropup':"") }}" data-ai-target="{{ $aiTarget }}" data-ai-language="{{ $aiLanguage }}" data-ai-field="{{ $aiFieldId ?? $aiTarget.'_'.$aiLanguage }}" @if(isset($aiFieldPrefix)) data-ai-field-prefix="{{ $aiFieldPrefix }}" @endif @if(isset($aiContentContext)) data-ai-content-context="{{ $aiContentContext }}" @endif>
        <button type="button" class="btn white dropdown-toggle {{ $aiInputGroup ? '' : 'smartend-ai-editor-button' }}" data-toggle="dropdown"
                title="{{ __('backend.aiAssistant') }}" aria-label="{{ __('backend.aiAssistant') }}" aria-haspopup="true" aria-expanded="false">
            <i class="material-icons" aria-hidden="true">&#xe39f;</i>{!! $aiInputGroup ? '' : ' <small>'.__('backend.aiAssistant').'</small>' !!}<span class="caret"></span>
        </button>
        <div class="dropdown-menu {{ $aiInputGroup ? 'pull-right' : '' }}">
            <div class="smartend-ai-menu-heading">
                <i class="material-icons" aria-hidden="true">&#xe39f;</i>{{ __('backend.aiAssistant') }}</div>
            @if($aiCapabilities['content'] ?? false)
                @foreach(['write' => 'aiWrite', 'rewrite' => 'aiRewrite', 'improve' => 'aiImprove', 'shorten' => 'aiShorten', 'expand' => 'aiExpand', 'fix_grammar' => 'aiFixGrammar', 'change_tone' => 'aiChangeTone'] as $operation => $label)
                    <button type="button" class="dropdown-item smartend-ai-action" data-operation="{{ $operation }}">
                        <i class="material-icons" aria-hidden="true">{{ ['write' => 'edit', 'rewrite' => 'autorenew', 'improve' => 'trending_up', 'shorten' => 'wrap_text', 'expand' => 'format_line_spacing', 'fix_grammar' => 'spellcheck', 'change_tone' => 'settings_voice'][$operation] }}</i><span>{{ __('backend.'.$label) }}</span>
                    </button>
                @endforeach
                @if($aiTarget === 'title')
                    <button type="button" class="dropdown-item smartend-ai-action" data-operation="make_engaging">
                        <i class="material-icons" aria-hidden="true">format_quote</i><span>{{ __('backend.aiMakeEngaging') }}</span>
                    </button>
                    <button type="button" class="dropdown-item smartend-ai-action" data-operation="optimize_title_seo">
                        <i class="material-icons" aria-hidden="true">&#xe8b6;</i><span>{{ __('backend.aiOptimizeTitleSeo') }}</span>
                    </button>
                @elseif($aiTarget === 'details' && ($aiRichText ?? ($WebmasterSection->editor_status ?? false)))
                        <div class="divider"></div>
                    <button type="button" class="dropdown-item smartend-ai-action" data-operation="redesign">
                        <i class="material-icons" aria-hidden="true">&#xe23a;</i><span>{{ __('backend.aiRedesign') }}</span>
                    </button>
                @endif
            @endif
            @if(($aiCapabilities['content'] ?? false) && ($aiCapabilities['translation'] ?? false))
                <div class="divider"></div>
            @endif
            @if($aiCapabilities['translation'] ?? false)
                <button type="button" class="dropdown-item smartend-ai-translate">
                    <i class="material-icons" aria-hidden="true">translate</i><span>{{ __('backend.aiTranslate') }}</span>
                </button>
                @if($aiTarget === 'title')
                    <button type="button" class="dropdown-item smartend-ai-translate-all">
                        <i class="material-icons" aria-hidden="true">language</i><span>{{ __('backend.aiTranslateMissing') }}</span>
                    </button>
                @endif
            @endif
        </div>
    </div>
@endif
