(function ($) {
    'use strict';

    var config = window.SmartendAiConfig;
    if (!config) return;

    var state = {busy: false, preview: null, request: null, image: null, translateSource: null};

    function fieldId(target, language) { return target + '_' + language; }
    function element(id) { return document.getElementById(id); }

    var editor = {
        get: function (id) {
            if (window.CKEDITOR && CKEDITOR.instances[id]) return CKEDITOR.instances[id].getData();
            if (window.tinymce && tinymce.get(id)) return tinymce.get(id).getContent();
            var $field = $('#' + id);
            if ($field.hasClass('summernote') && typeof $field.summernote === 'function') return $field.summernote('code');
            return $field.val() || '';
        },
        set: function (id, value) {
            if (window.CKEDITOR && CKEDITOR.instances[id]) return CKEDITOR.instances[id].setData(value);
            if (window.tinymce && tinymce.get(id)) return tinymce.get(id).setContent(value);
            var $field = $('#' + id);
            if ($field.hasClass('summernote') && typeof $field.summernote === 'function') return $field.summernote('code', value);
            $field.val(value).trigger('change');
        },
        append: function (id, value) {
            var separator = (state.preview && state.preview.format === 'html') || config.richText ? '<hr>' : '\n\n';
            this.set(id, this.get(id) + separator + value);
        }
    };

    function topicValue(target, language) {
        var node = element(fieldId(target, language));
        return node ? editor.get(node.id) : '';
    }

    function hasMeaningfulContent(value) {
        var normalized = String(value || '').replace(/&nbsp;/gi, ' ');
        var container = document.createElement('div');
        container.innerHTML = normalized;
        return String(container.textContent || container.innerText || '').replace(/\u00a0/g, ' ').trim().length > 0;
    }

    function categoryIds() {
        var value = $('#cat_ids').val();
        if (!value || value === '0') return [];
        return Array.isArray(value) ? value : [value];
    }

    function common(language) {
        return {
            topic_id: config.topicId,
            resource_type: config.resourceType,
            resource_id: config.resourceId,
            context_id: config.contextId,
            language: language,
            title: topicValue('title', language),
            details: topicValue('details', language),
            category_ids: categoryIds()
        };
    }

    var seoPrefixes = (config.seo && config.seo.prefixes) || {seo_title: 'seo_title_', seo_description: 'seo_description_', seo_keywords: 'seo_keywords_', seo_url_slug: 'seo_url_slug_', photo_alt: 'seo_photo_alt_'};
    function seoFieldId(field, language) { return (seoPrefixes[field] || (field + '_')) + language; }
    function seoFields(language, missingLanguage) {
        var fields = {};
        Object.keys(seoPrefixes).forEach(function (field) {
            var source = element(seoFieldId(field, language));
            var target = missingLanguage ? element(seoFieldId(field, missingLanguage)) : null;
            if (source && editor.get(source.id).trim() && (!target || !editor.get(target.id).trim())) fields[field] = editor.get(source.id);
        });
        return fields;
    }

    function seoOriginalText(payload) {
        var labels = (config.seo && config.seo.labels) || {};
        return Object.keys(seoPrefixes).filter(function (field) {
            return Object.prototype.hasOwnProperty.call(payload, field);
        }).map(function (field) {
            var label = labels[field] || field.replace(/^seo_/, '').replace(/_/g, ' ');
            return String(label).toUpperCase() + '\n' + String(payload[field] || '—').trim();
        }).join('\n\n');
    }

    function populateTargetLanguages(sourceLanguage) {
        var $select = $('#smartend-ai-target-language').empty();
        config.languages.forEach(function (language) {
            if (language.code !== sourceLanguage) $('<option>').val(language.code).text(language.title + ' (' + language.code + ')').appendTo($select);
        });
    }

    function api(url, payload, method) {
        if (state.busy) return Promise.reject(new Error('busy'));
        state.busy = true;
        $('.smartend-ai-loading, .smartend-ai-image-loading').prop('hidden', false);
        $('.smartend-ai-action, .smartend-ai-translate, .smartend-ai-translate-all, .smartend-ai-seo-translate, .smartend-ai-seo-translate-all, .smartend-ai-seo, .smartend-ai-alt, .smartend-ai-image').prop('disabled', true);
        return fetch(url, {
            method: method || 'POST',
            credentials: 'same-origin',
            headers: {'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': config.csrf},
            body: payload === null ? null : JSON.stringify(payload)
        }).then(function (response) {
            return response.json().catch(function () { return {}; }).then(function (body) {
                if (!response.ok) {
                    var message = body.message || config.messages.error;
                    if (body.errors) message += '\n' + Object.values(body.errors).flat().join('\n');
                    throw new Error(message);
                }
                return body;
            });
        }).finally(function () {
            state.busy = false;
            $('.smartend-ai-loading, .smartend-ai-image-loading').prop('hidden', true);
            $('.smartend-ai-action, .smartend-ai-translate, .smartend-ai-translate-all, .smartend-ai-seo-translate, .smartend-ai-seo-translate-all, .smartend-ai-seo, .smartend-ai-alt, .smartend-ai-image').prop('disabled', false);
        });
    }

    function fail(error) {
        if (error.message === 'busy') return;
        if ($('#smartend-ai-preview').hasClass('is-loading')) {
            $('#smartend-ai-preview').removeClass('is-loading').attr('aria-busy', 'false');
            $('.smartend-ai-loading').prop('hidden', true);
            $('#smartend-ai-result').prop('hidden', false).text(error.message);
        }
        if (window.swal) swal({title: config.messages.error, text: error.message, type: 'error'});
        else window.alert(error.message);
    }

    function sanitizePreviewHtml(html) {
        var template = document.createElement('template');
        template.innerHTML = String(html || '');
        var dangerous = 'script,noscript,style,link,meta,base,iframe,frame,object,embed,form,input,button,textarea,select,option,img,svg,math,canvas,audio,video,source';
        template.content.querySelectorAll(dangerous).forEach(function (node) { node.remove(); });
        var allowedTags = ['SECTION', 'DIV', 'H1', 'H2', 'H3', 'H4', 'H5', 'H6', 'P', 'UL', 'OL', 'LI', 'STRONG', 'EM', 'B', 'I', 'BLOCKQUOTE', 'TABLE', 'THEAD', 'TBODY', 'TR', 'TH', 'TD', 'A', 'BR', 'HR', 'SPAN'];
        var safeClass = /^(row|container(-fluid)?|col(-(sm|md|lg|xl|xxl))?-(1[0-2]|[1-9])|card|card-(body|header|footer|title|text)|alert|alert-(primary|secondary|success|danger|warning|info|light|dark)|table|table-(responsive|striped|bordered|hover)|list-(group|unstyled)|list-group-item|btn|btn-(primary|secondary|outline-primary|outline-secondary|lg|sm)|text-(center|start|end|primary|secondary|dark|white|muted)|display-[1-6]|lead|fw-(bold|semibold)|small|d-(flex|grid|block)|align-items-(center|start|stretch)|justify-content-(between|center|start|end)|gap-[1-5]|h-100|w-100|shadow-sm|rounded(-3)?|bg-(light|primary|secondary|dark)|[mp][trblxy]?-[0-5])$/;
        Array.from(template.content.querySelectorAll('*')).reverse().forEach(function (node) {
            if (allowedTags.indexOf(node.tagName) === -1) {
                node.replaceWith.apply(node, Array.from(node.childNodes));
                return;
            }
            Array.from(node.attributes).forEach(function (attribute) {
                var name = attribute.name.toLowerCase();
                var keep = name === 'class'
                    || (node.tagName === 'A' && ['href', 'title', 'target', 'rel'].indexOf(name) !== -1)
                    || (['TH', 'TD'].indexOf(node.tagName) !== -1 && ['colspan', 'rowspan', 'scope'].indexOf(name) !== -1);
                if (!keep) node.removeAttribute(attribute.name);
            });
            if (node.hasAttribute('class')) {
                var classes = node.className.split(/\s+/).filter(function (name) { return safeClass.test(name); });
                if (classes.length) node.className = Array.from(new Set(classes)).join(' ');
                else node.removeAttribute('class');
            }
            if (node.tagName === 'A') {
                var href = (node.getAttribute('href') || '').trim();
                if (href && !/^(https?:|mailto:|tel:|\/|#)/i.test(href)) node.removeAttribute('href');
                if (node.getAttribute('target') !== '_blank') node.removeAttribute('target');
                if (node.getAttribute('target') === '_blank') node.setAttribute('rel', 'noopener noreferrer');
            }
        });
        return template.innerHTML;
    }

    function setPreviewContent(side, value, rich) {
        var textId = side === 'original' ? '#smartend-ai-original' : '#smartend-ai-result';
        var htmlId = side === 'original' ? 'smartend-ai-original-html' : 'smartend-ai-html-result';
        $(textId).prop('hidden', rich).text(value || '');
        $('#' + htmlId).prop('hidden', !rich).html(rich ? sanitizePreviewHtml(value) : '');
    }

    function preparePreview(original, format) {
        state.preview = null;
        var $modal = $('#smartend-ai-preview').addClass('is-loading').attr('aria-busy', 'true');
        $('.smartend-ai-loading').prop('hidden', false);
        $('.smartend-ai-comparison').prop('hidden', false);
        $('#smartend-ai-seo-result').prop('hidden', true).empty();
        setPreviewContent('original', original, format === 'html');
        setPreviewContent('result', '', false);
        $('#smartend-ai-apply-all').prop('hidden', true).prop('disabled', true);
        $('#smartend-ai-replace, #smartend-ai-insert, #smartend-ai-copy, #smartend-ai-regenerate').prop('hidden', false).prop('disabled', true);
        $modal.modal('show');
    }

    function showPreview(preview) {
        state.preview = preview;
        $('#smartend-ai-preview').removeClass('is-loading').attr('aria-busy', 'false');
        $('.smartend-ai-loading').prop('hidden', true);
        $('#smartend-ai-seo-result').prop('hidden', true).empty();
        $('.smartend-ai-comparison').prop('hidden', false);
        var rich = preview.format === 'html';
        setPreviewContent('original', preview.original, rich);
        setPreviewContent('result', preview.result, rich);
        var canInsert = preview.target === 'details' && preview.kind !== 'bulk';
        $('#smartend-ai-replace').prop('hidden', false).prop('disabled', false);
        $('#smartend-ai-insert').prop('hidden', !canInsert).prop('disabled', !canInsert);
        $('#smartend-ai-copy').prop('hidden', false).prop('disabled', !preview.result);
        $('#smartend-ai-regenerate').prop('hidden', !(state.request && state.request.type === 'content')).prop('disabled', !(state.request && state.request.type === 'content'));
        $('#smartend-ai-apply-all').prop('hidden', true).prop('disabled', true);
        $('#smartend-ai-preview').modal('show');
    }

    function runContent($button) {
        var $tools = $button.closest('.smartend-ai-tools');
        var target = $tools.data('ai-target');
        var language = String($tools.data('ai-language'));
        var targetField = String($tools.data('ai-field') || fieldId(target, language));
        var fieldValue = element(targetField) ? editor.get(targetField) : topicValue(target, language);
        var contentContext = $tools.attr('data-ai-content-context');
        var payload = Object.assign(common(language), {
            operation: $button.data('operation'), target: target,
            value: fieldValue
        });
        if (contentContext) payload.content_context = String(contentContext);
        payload[target] = fieldValue;
        state.request = {type: 'content', payload: payload, field: targetField};
        preparePreview(payload.value, target === 'details' && (config.richText || contentContext) ? 'html' : 'plain');
        api(config.routes.content, payload).then(function (body) {
            showPreview({kind: 'content', target: target, language: language, field: targetField, original: payload.value, result: body.data.result, format: body.data.format});
        }).catch(fail);
    }

    $(document).on('click', '.smartend-ai-action', function () { runContent($(this)); });

    $(document).on('click', '.smartend-ai-translate', function () {
        var $tools = $(this).closest('.smartend-ai-tools');
        state.translateSource = {
            target: $tools.data('ai-target'),
            language: String($tools.data('ai-language')),
            field: String($tools.data('ai-field') || fieldId($tools.data('ai-target'), String($tools.data('ai-language')))),
            fieldPrefix: $tools.attr('data-ai-field-prefix') ? String($tools.attr('data-ai-field-prefix')) : null
        };
        populateTargetLanguages(state.translateSource.language);
        $('#smartend-ai-translate').modal('show');
    });

    $(document).on('click', '.smartend-ai-seo-translate', function () {
        state.translateSource = {scope: 'seo', language: String($(this).data('language'))};
        populateTargetLanguages(state.translateSource.language);
        $('#smartend-ai-translate').modal('show');
    });

    $('#smartend-ai-translate-submit').on('click', function () {
        var source = state.translateSource;
        var targetLanguage = $('#smartend-ai-target-language').val();
        if (!source || !targetLanguage) return;
        if (source.scope === 'seo') {
            var seoPayload = {topic_id: config.topicId, resource_type: config.resourceType, resource_id: config.resourceId, context_id: config.contextId, source_language: source.language, target_language: targetLanguage, scope: 'seo', fields: seoFields(source.language)};
            state.request = null;
            $('#smartend-ai-translate').modal('hide');
            preparePreview('');
            api(config.routes.translate, seoPayload).then(function (body) {
                var outputs = {}; outputs[targetLanguage] = body.data.fields;
                showPreview({kind: 'bulk', target: 'seo', original: '', result: JSON.stringify(outputs, null, 2), format: 'plain', outputs: outputs, fieldPrefixes: seoPrefixes, missingOnly: false});
            }).catch(fail);
            return;
        }
        var sourceValue = element(source.field) ? editor.get(source.field) : topicValue(source.target, source.language);
        var targetField = source.fieldPrefix ? source.fieldPrefix + targetLanguage : fieldId(source.target, targetLanguage);
        var payload = {topic_id: config.topicId, resource_type: config.resourceType, resource_id: config.resourceId, context_id: config.contextId, source_language: source.language, target_language: targetLanguage, scope: 'content', fields: {}};
        payload.fields[source.target] = sourceValue;
        state.request = {type: 'translate', payload: payload, targetField: targetField};
        $('#smartend-ai-translate').modal('hide');
        preparePreview(element(targetField) ? editor.get(targetField) : '');
        api(config.routes.translate, payload).then(function (body) {
            showPreview({kind: 'translate', target: source.target, field: targetField, original: element(targetField) ? editor.get(targetField) : '', result: body.data.fields[source.target], format: source.target === 'details' && config.richText ? 'html' : 'plain'});
        }).catch(fail);
    });

    $(document).on('click', '.smartend-ai-translate-all', function () {
        var $tools = $(this).closest('.smartend-ai-tools');
        var sourceLanguage = String($tools.data('ai-language'));
        var customFieldPrefix = $tools.attr('data-ai-field-prefix') ? String($tools.attr('data-ai-field-prefix')) : null;
        var sourceFields = {};
        if (customFieldPrefix) {
            var customSource = editor.get(customFieldPrefix + sourceLanguage);
            if (hasMeaningfulContent(customSource)) sourceFields.title = customSource;
        }
        else {
            ['title', 'details', 'photo_alt'].forEach(function (field) {
                var sourceValue = topicValue(field, sourceLanguage);
                if (element(fieldId(field, sourceLanguage)) && hasMeaningfulContent(sourceValue)) sourceFields[field] = sourceValue;
            });
        }
        var missing = config.languages.filter(function (language) {
            if (language.code === sourceLanguage) return false;
            return Object.keys(sourceFields).some(function (field) {
                var targetId = customFieldPrefix ? customFieldPrefix + language.code : fieldId(field, language.code);
                return element(targetId) && !hasMeaningfulContent(editor.get(targetId));
            });
        });
        if (!missing.length) return fail(new Error(config.messages.nothingMissing));
        state.request = null;
        var outputs = {}, failures = [];
        preparePreview('');
        var chain = Promise.resolve();
        missing.forEach(function (language) {
            chain = chain.then(function () {
                var fields = {};
                Object.keys(sourceFields).forEach(function (field) {
                    var targetId = customFieldPrefix ? customFieldPrefix + language.code : fieldId(field, language.code);
                    if (element(targetId) && !hasMeaningfulContent(editor.get(targetId))) fields[field] = sourceFields[field];
                });
                return api(config.routes.translate, {topic_id: config.topicId, resource_type: config.resourceType, resource_id: config.resourceId, context_id: config.contextId, source_language: sourceLanguage, target_language: language.code, scope: 'content', fields: fields})
                    .then(function (body) { outputs[language.code] = body.data.fields; })
                    .catch(function (error) { failures.push(language.title + ': ' + error.message); });
            });
        });
        chain.then(function () {
            showPreview({kind: 'bulk', target: 'bulk', original: '', result: JSON.stringify(outputs, null, 2), format: 'plain', outputs: outputs, fieldPrefixes: customFieldPrefix ? {title: customFieldPrefix} : null});
            if (failures.length) fail(new Error(failures.join('\n')));
        });
    });

    $(document).on('click', '.smartend-ai-seo-translate-all', function () {
        var sourceLanguage = String($(this).data('language'));
        var missing = config.languages.filter(function (language) {
            return language.code !== sourceLanguage && Object.keys(seoFields(sourceLanguage, language.code)).length;
        });
        if (!missing.length) return fail(new Error(config.messages.nothingMissing));
        state.request = null;
        var outputs = {}, failures = [], chain = Promise.resolve();
        preparePreview('');
        missing.forEach(function (language) {
            chain = chain.then(function () {
                return api(config.routes.translate, {topic_id: config.topicId, resource_type: config.resourceType, resource_id: config.resourceId, context_id: config.contextId, source_language: sourceLanguage, target_language: language.code, scope: 'seo', fields: seoFields(sourceLanguage, language.code)})
                    .then(function (body) { outputs[language.code] = body.data.fields; })
                    .catch(function (error) { failures.push(language.title + ': ' + error.message); });
            });
        });
        chain.then(function () {
            showPreview({kind: 'bulk', target: 'seo', original: '', result: JSON.stringify(outputs, null, 2), format: 'plain', outputs: outputs, fieldPrefixes: seoPrefixes});
            if (failures.length) fail(new Error(failures.join('\n')));
        });
    });

    $('#smartend-ai-replace').on('click', function () {
        var preview = state.preview;
        if (!preview) return;
        if (preview.kind === 'bulk') {
            Object.keys(preview.outputs).forEach(function (language) {
                Object.keys(preview.outputs[language]).forEach(function (field) {
                    var id = preview.fieldPrefixes ? (preview.fieldPrefixes[field] + language) : fieldId(field, language);
                    if (element(id) && (preview.missingOnly === false || !editor.get(id).trim())) editor.set(id, preview.outputs[language][field]);
                });
            });
        } else if (preview.field) editor.set(preview.field, preview.result);
        $('#smartend-ai-preview').modal('hide');
    });
    $('#smartend-ai-insert').on('click', function () { if (state.preview && state.preview.field) editor.append(state.preview.field, state.preview.result); $('#smartend-ai-preview').modal('hide'); });
    $('#smartend-ai-copy').on('click', function () {
        if (!state.preview) return;
        navigator.clipboard.writeText(state.preview.result || '').then(function () { if (window.swal) swal({title: config.messages.copied, type: 'success', timer: 1200, showConfirmButton: false}); });
    });
    $('#smartend-ai-regenerate').on('click', function () {
        if (state.request && state.request.type === 'content') {
            var tools = $('.smartend-ai-tools[data-ai-field="' + state.request.field + '"]');
            runContent(tools.find('[data-operation="' + state.request.payload.operation + '"]').first());
        } else if (state.request && state.request.type === 'seo') {
            runSeo(state.request.language, state.request.payload);
        }
    });

    function runSeo(language, existingPayload) {
        var payload = existingPayload || common(language);
        if (!existingPayload) Object.keys(seoPrefixes).forEach(function (field) {
            var node = element(seoFieldId(field, language));
            if (node) payload[field] = editor.get(node.id);
        });
        state.request = {type: 'seo', language: language, payload: payload};
        preparePreview(seoOriginalText(payload));
        api(config.routes.seo, payload).then(function (body) { showSeo(language, body.data.suggestions, payload); }).catch(fail);
    }

    $(document).on('click', '.smartend-ai-seo', function () {
        runSeo(String($(this).data('language')));
    });

    function showSeo(language, suggestions, originalPayload) {
        $('#smartend-ai-preview').removeClass('is-loading').attr('aria-busy', 'false');
        $('.smartend-ai-loading').prop('hidden', true);
        $('.smartend-ai-comparison').prop('hidden', false);
        setPreviewContent('original', seoOriginalText(originalPayload || {}), false);
        $('#smartend-ai-result, #smartend-ai-html-result').prop('hidden', true);
        var $container = $('#smartend-ai-seo-result').prop('hidden', false).empty();
        var mapping = (config.seo && config.seo.resultMapping) || {seo_title: 'seo_title_', seo_description: 'seo_description_', keywords: 'seo_keywords_', slug: 'seo_url_slug_', image_alt: 'seo_photo_alt_'};
        var values = {};
        Object.keys(mapping).forEach(function (key) {
            var value = key === 'keywords' ? (suggestions[key] || []).join(', ') : (suggestions[key] || '');
            values[key] = value;
            var $row = $('<div class="smartend-ai-seo-field">');
            $('<strong>').text(key.replace(/_/g, ' ') + ': ').appendTo($row);
            $('<span>').text(value).appendTo($row);
            $('<button type="button" class="btn btn-xs primary pull-right">').text(config.messages.apply).on('click', function () {
                var apply = function () { editor.set(mapping[key] + language, value); };
                if (key === 'slug' && config.published && window.swal) swal({title: config.messages.confirmSlug, type: 'warning', showCancelButton: true}, function (confirmed) { if (confirmed) apply(); });
                else apply();
            }).appendTo($row);
            $container.append($row);
        });
        state.preview = {kind: 'seo', language: language, mapping: mapping, values: values};
        $('#smartend-ai-replace, #smartend-ai-insert, #smartend-ai-copy').prop('hidden', true).prop('disabled', true);
        $('#smartend-ai-regenerate').prop('hidden', false).prop('disabled', false);
        $('#smartend-ai-apply-all').prop('hidden', false).prop('disabled', false);
    }
    $('#smartend-ai-apply-all').on('click', function () {
        var preview = state.preview;
        if (!preview || preview.kind !== 'seo') return;
        var applyAll = function () {
            Object.keys(preview.mapping).forEach(function (key) {
                var target = preview.mapping[key] + preview.language;
                if (element(target)) editor.set(target, preview.values[key]);
            });
            $('#smartend-ai-preview').modal('hide');
        };
        var slugField = preview.mapping.slug ? preview.mapping.slug + preview.language : null;
        var slugChanged = slugField && element(slugField) && editor.get(slugField) !== preview.values.slug;
        if (slugChanged && config.published && window.swal) {
            swal({title: config.messages.confirmSlug, type: 'warning', showCancelButton: true}, function (confirmed) { if (confirmed) applyAll(); });
        } else applyAll();
    });
    $('#smartend-ai-preview').on('show.bs.modal', function () {
        $('body').addClass('smartend-ai-preview-open');
        window.setTimeout(function () {
            $('.modal-backdrop').last().addClass('smartend-ai-preview-backdrop');
        }, 0);
    }).on('hidden.bs.modal', function () {
        $(this).removeClass('is-loading').attr('aria-busy', 'false');
        $('body').removeClass('smartend-ai-preview-open');
        $('.smartend-ai-preview-backdrop').removeClass('smartend-ai-preview-backdrop');
        if ($('.modal.in').length) $('body').addClass('modal-open');
        $('.smartend-ai-loading').prop('hidden', true);
        $('#smartend-ai-apply-all').prop('hidden', true).prop('disabled', true);
        $('#smartend-ai-replace, #smartend-ai-copy, #smartend-ai-regenerate').prop('hidden', false).prop('disabled', true);
    });

    $(document).on('click', '.smartend-ai-alt', function () {
        var $button = $(this);
        var language = String($button.data('language'));
        var targetField = String($button.data('ai-field') || fieldId('photo_alt', language));
        var original = element(targetField) ? editor.get(targetField) : '';
        var payload = Object.assign(common(language), {asset_id: $('#ai_generated_asset_id').val() || null});
        state.request = {type: 'alt', payload: payload};
        preparePreview(original);
        api(config.routes.alt, payload).then(function (body) { showPreview({kind: 'alt', target: 'photo_alt', field: targetField, original: original, result: body.data.result, format: 'plain'}); }).catch(fail);
    });

    $(document).on('click', '.smartend-ai-image', function () { $('#smartend-ai-image').modal('show'); });
    $('#smartend-ai-image-generate').on('click', function () { generateImage('generate'); });
    $('#smartend-ai-image-regenerate').on('click', function () { generateImage('regenerate'); });
    $('#smartend-ai-image-variation').on('click', function () { generateImage('variation'); });

    function generateImage(mode) {
        var language = $('.smartend-ai-image').first().data('language') || (config.languages[0] && config.languages[0].code);
        var payload = Object.assign(common(String(language)), {
            style: $('#smartend-ai-image-style').val(), orientation: $('#smartend-ai-image-orientation').val(), quality: $('#smartend-ai-image-quality').val(),
            custom_instructions: $('#smartend-ai-image-instructions').val(), mode: mode,
            previous_asset_id: state.image ? state.image.asset_id : null,
            prompt_override: mode === 'regenerate' ? $('#smartend-ai-image-prompt').val() : null
        });
        api(config.routes.image, payload).then(function (body) {
            state.image = body.data;
            $('#smartend-ai-image-result').prop('hidden', false);
            $('#smartend-ai-image-preview').attr('src', body.data.preview_url);
            $('#smartend-ai-image-dimensions').text(body.data.width + ' × ' + body.data.height + ' · ' + body.data.mime);
            $('#smartend-ai-image-prompt').val(body.data.optimized_prompt);
            $('#smartend-ai-image-use, #smartend-ai-image-regenerate, #smartend-ai-image-variation').prop('hidden', false);
        }).catch(fail);
    }

    $('#smartend-ai-image-use').on('click', function () {
        if (!state.image) return;
        $('#ai_generated_asset_id').val(state.image.asset_id);
        $('#photo_file').val('');
        $('#smartend-ai-accepted').remove();
        $('<div id="smartend-ai-accepted" class="smartend-ai-accepted"><img><span></span></div>').find('img').attr('src', state.image.preview_url).end().find('span').text(state.image.width + ' × ' + state.image.height).end().insertAfter($('#photo_file').closest('.input-group'));
        $('#smartend-ai-image').modal('hide');
    });

    $('#smartend-ai-image-cancel').on('click', function () {
        if (state.image) api(config.routes.assetDelete + '/' + state.image.asset_id, null, 'DELETE').catch(function () {});
        state.image = null;
        $('#smartend-ai-image').modal('hide');
    });

    $('#photo_file').on('change', function () {
        var assetId = $('#ai_generated_asset_id').val();
        if (assetId) api(config.routes.assetDelete + '/' + assetId, null, 'DELETE').catch(function () {});
        $('#ai_generated_asset_id').val('');
        $('#smartend-ai-accepted').remove();
        state.image = null;
    });
})(window.jQuery);
