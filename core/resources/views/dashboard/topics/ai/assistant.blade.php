@if(collect($aiCapabilities ?? [])->contains(true))
    <div id="smartend-ai-preview" class="modal fade" data-backdrop="static" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content smartend-ai-preview-shell">
                <div class="modal-header smartend-ai-preview-header">
                    <div class="smartend-ai-preview-title">
                        <span class="smartend-ai-preview-icon"><i class="material-icons" aria-hidden="true">&#xe39f;</i></span>
                        <div><h5 class="modal-title">{{ __('backend.aiPreview') }}</h5>
                            <small>{{ __('backend.aiPreviewHint') }}</small></div>
                    </div>
                    <button type="button" class="close smartend-ai-preview-close" data-dismiss="modal" aria-label="{{ __('backend.cancel') }}">
                        &times;
                    </button>
                </div>
                <div class="modal-body">
                    <div class="smartend-ai-loading" role="status" aria-live="polite" hidden>
                        <span class="smartend-ai-spinner" aria-hidden="true"></span>
                        <div>
                            <strong>{{ __('backend.aiGenerating') }}</strong><small>{{ __('backend.aiGeneratingHint') }}</small>
                        </div>
                    </div>
                    <div class="smartend-ai-comparison">
                        <div class="row">
                            <div class="col-md-6">
                                <section class="smartend-ai-preview-card smartend-ai-original-card"><h6>
                                        <i class="material-icons" aria-hidden="true">description</i>{{ __('backend.aiOriginal') }}
                                    </h6>
                                    <pre id="smartend-ai-original"></pre>
                                    <div id="smartend-ai-original-html" class="smartend-ai-html-preview" hidden></div>
                                </section>
                            </div>
                            <div class="col-md-6">
                                <section class="smartend-ai-preview-card smartend-ai-result-card"><h6>
                                        <i class="material-icons" aria-hidden="true">&#xe39f;</i>{{ __('backend.aiResult') }}
                                    </h6>
                                    <div class="smartend-ai-result-placeholder" aria-hidden="true">
                                        <span></span><span></span><span></span></div>
                                    <pre id="smartend-ai-result"></pre>
                                    <div id="smartend-ai-html-result" class="smartend-ai-html-preview" hidden></div>
                                    <div id="smartend-ai-seo-result" hidden></div>
                                </section>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer smartend-ai-preview-footer">
                    <div class="smartend-ai-secondary-actions">
                        <button type="button" class="btn white" id="smartend-ai-copy" disabled>
                            <i class="material-icons" aria-hidden="true">content_copy</i>{{ __('backend.aiCopy') }}
                        </button>
                        <button type="button" class="btn white" id="smartend-ai-regenerate" disabled>
                            <i class="material-icons" aria-hidden="true">refresh</i>{{ __('backend.aiRegenerate') }}
                        </button>
                    </div>
                    <div class="smartend-ai-primary-actions">
                        <button type="button" class="btn white" data-dismiss="modal">{{ __('backend.cancel') }}</button>
                        <button type="button" class="btn primary" id="smartend-ai-apply-all" hidden disabled>
                            <i class="material-icons" aria-hidden="true">done_all</i>{{ __('backend.aiApplyAll') }}
                        </button>
                        <button type="button" class="btn info" id="smartend-ai-insert" disabled>
                            <i class="material-icons" aria-hidden="true">vertical_align_bottom</i>{{ __('backend.aiInsertBelow') }}
                        </button>
                        <button type="button" class="btn primary" id="smartend-ai-replace" disabled>
                            <i class="material-icons" aria-hidden="true">check</i>{{ __('backend.aiReplace') }}</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div id="smartend-ai-translate" class="modal fade" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header"><h5>{{ __('backend.aiTranslate') }}</h5></div>
                <div class="modal-body">
                    <label>{{ __('backend.aiTargetLanguage') }}</label><select id="smartend-ai-target-language" class="form-control c-select"></select>
                </div>
                <div class="modal-footer">
                    <button class="btn primary" id="smartend-ai-translate-submit" type="button">{{ __('backend.aiTranslate') }}</button>
                    <button class="btn white" data-dismiss="modal" type="button">{{ __('backend.cancel') }}</button>
                </div>
            </div>
        </div>
    </div>

    @if($aiCapabilities['images'] ?? false)
        <div id="smartend-ai-image" class="modal fade" data-backdrop="static" tabindex="-1" role="dialog" aria-hidden="true">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header"><h5>{{ __('backend.aiGenerateImage') }}</h5>
                        <button type="button" class="close" data-dismiss="modal">&times;</button>
                    </div>
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-sm-4">
                                <label>{{ __('backend.aiStyle') }}</label><select id="smartend-ai-image-style" class="form-control c-select">@foreach(config('smartend_ai.image_styles') as $value)
                                        <option value="{{ $value }}">{{ ucfirst($value) }}</option>
                                    @endforeach</select></div>
                            <div class="col-sm-4">
                                <label>{{ __('backend.aiOrientation') }}</label><select id="smartend-ai-image-orientation" class="form-control c-select">@foreach(config('smartend_ai.image_orientations') as $value)
                                        <option value="{{ $value }}">{{ ucfirst($value) }}</option>
                                    @endforeach</select></div>
                            <div class="col-sm-4">
                                <label>{{ __('backend.aiQuality') }}</label><select id="smartend-ai-image-quality" class="form-control c-select">@foreach(config('smartend_ai.image_qualities') as $value)
                                        <option value="{{ $value }}">{{ ucfirst($value) }}</option>
                                    @endforeach</select></div>
                        </div>
                        <label class="m-t">{{ __('backend.aiCustomInstructions') }}</label><textarea id="smartend-ai-image-instructions" maxlength="2000" class="form-control" rows="2"></textarea>
                        <div class="smartend-ai-image-loading text-center p-a-2" hidden>
                            <img src="{{ asset('assets/dashboard/images/loading.gif') }}" height="35" alt="">
                            <p>{{ __('backend.aiGenerating') }}</p></div>
                        <div id="smartend-ai-image-result" class="m-t" hidden>
                            <img id="smartend-ai-image-preview" class="img-responsive m-b" alt="">
                            <p id="smartend-ai-image-dimensions"></p>
                            <label>{{ __('backend.aiOptimizedPrompt') }}</label><textarea id="smartend-ai-image-prompt" maxlength="2000" class="form-control" rows="5"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn primary" id="smartend-ai-image-generate">{{ __('backend.aiGenerate') }}</button>
                        <button type="button" class="btn success" id="smartend-ai-image-use" hidden>{{ __('backend.aiUseImage') }}</button>
                        <button type="button" class="btn warning" id="smartend-ai-image-regenerate" hidden>{{ __('backend.aiRegenerate') }}</button>
                        <button type="button" class="btn info" id="smartend-ai-image-variation" hidden>{{ __('backend.aiVariation') }}</button>
                        <button type="button" class="btn white" id="smartend-ai-image-cancel">{{ __('backend.cancel') }}</button>
                    </div>
                </div>
            </div>
        </div>
    @endif

    @push('after-styles')
        <link rel="stylesheet" href="{{ asset('assets/dashboard/css/smartend-ai.css') }}?v={{ filemtime(public_path('assets/dashboard/css/smartend-ai.css')) }}">
    @endpush
    @push('after-scripts')
        @php
            $smartendAiConfig = array_replace_recursive([
                'csrf' => csrf_token(),
                'topicId' => isset($Topic) ? $Topic->id : null,
                'resourceType' => 'topic',
                'resourceId' => isset($Topic) ? $Topic->id : null,
                'contextId' => null,
                'published' => isset($Topic) ? (bool) $Topic->status : false,
                'webmasterId' => $WebmasterSection->id ?? null,
                'richText' => (bool) ($WebmasterSection->editor_status ?? false),
                'languages' => Helper::languagesList()->where('box_status', 1)->map(fn($language) => [
                    'code' => $language->code,
                    'title' => $language->title,
                    'direction' => $language->direction,
                ])->values(),
                'routes' => [
                    'content' => isset($WebmasterSection) ? route('topicsAiContent', $WebmasterSection->id) : null,
                    'seo' => isset($WebmasterSection) ? route('topicsAiSeo', $WebmasterSection->id) : null,
                    'translate' => isset($WebmasterSection) ? route('topicsAiTranslate', $WebmasterSection->id) : null,
                    'image' => isset($WebmasterSection) ? route('topicsAiImage', $WebmasterSection->id) : null,
                    'alt' => isset($WebmasterSection) ? route('topicsAiAlt', $WebmasterSection->id) : null,
                    'assetDelete' => url(config('smartend.backend_path').'/ai/assets'),
                ],
                'messages' => [
                    'error' => __('backend.aiErrorGeneric'),
                    'copied' => __('backend.aiCopied'),
                    'confirmSlug' => __('backend.aiConfirmPublishedSlug'),
                    'nothingMissing' => __('backend.aiNothingMissing'),
                    'apply' => __('backend.aiApply'),
                ],
            ], $aiConfig ?? []);
        @endphp
        <script>
            window.SmartendAiConfig = @json($smartendAiConfig);
        </script>
        <script src="{{ asset('assets/dashboard/js/smartend-ai.js') }}?v={{ filemtime(public_path('assets/dashboard/js/smartend-ai.js')) }}"></script>
    @endpush
@endif
