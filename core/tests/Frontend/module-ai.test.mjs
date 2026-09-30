import {readFileSync} from 'node:fs';
import assert from 'node:assert/strict';
import test from 'node:test';
import {JSDOM} from 'jsdom';

const modal = readFileSync('resources/views/dashboard/modules/ai/create_modal.blade.php', 'utf8');
const jquery = readFileSync('public/assets/dashboard/js/jquery/dist/jquery.js', 'utf8');
const script = readFileSync('public/assets/dashboard/js/smartend-module-ai.js', 'utf8');

for (const direction of ['ltr', 'rtl']) {
    test(direction + ': one submission, busy lock, safe errors and retry preserve requirements', async () => {
        const dom = new JSDOM('<html dir="' + direction + '"><body><button id="smartend-module-ai-open"></button>' + modal + '</body></html>', {
            url: 'https://smartend.test/admin/modules', runScripts: 'outside-only'
        });
        const {window} = dom;
        window.eval(jquery);
        const $ = window.jQuery;
        $.fn.modal = function () { return this; };
        window.SmartendModuleAiConfig = {enabled: true, url: '/admin/modules/ai/create', csrf: 'test-token', error: 'Safe error'};
        const pending = [];
        window.fetch = (url, options) => new Promise(resolve => pending.push({url, options, resolve}));
        window.eval(script);
        const requirements = 'إنشاء دليل للعقارات مع السعر والموقع';
        $('#smartend-module-ai-description').val(requirements);
        $('#smartend-module-ai-form').triggerHandler('submit');
        $('#smartend-module-ai-form').triggerHandler('submit');
        assert.equal(pending.length, 1);
        assert.equal(pending[0].url, '/admin/modules/ai/create');
        assert.deepEqual(JSON.parse(pending[0].options.body), {description: requirements});
        assert.equal(pending[0].options.headers['X-CSRF-TOKEN'], 'test-token');
        assert.equal($('#smartend-module-ai-create').prop('disabled'), true);
        assert.equal($('#smartend-module-ai-description').prop('disabled'), true);
        assert.equal($('#smartend-module-ai-loading').prop('hidden'), false);
        const hide = $.Event('hide.bs.modal');
        $('#smartend-module-ai').triggerHandler(hide);
        assert.equal(hide.isDefaultPrevented(), true);
        pending[0].resolve({ok: false, json: async () => ({message: '<b>Provider unavailable</b>', request_id: 'request-123'})});
        await new Promise(resolve => setTimeout(resolve, 0));
        assert.equal($('#smartend-module-ai-create').prop('disabled'), false);
        assert.equal($('#smartend-module-ai-loading').prop('hidden'), true);
        assert.equal($('#smartend-module-ai-description').val(), requirements);
        assert.equal($('#smartend-module-ai-error b').length, 0);
        assert.match($('#smartend-module-ai-error').text(), /request-123/);
        $('#smartend-module-ai-form').triggerHandler('submit');
        assert.equal(pending.length, 2);
        assert.equal($('#smartend-module-ai-step-review').length, 0);
        dom.window.close();
    });
}
