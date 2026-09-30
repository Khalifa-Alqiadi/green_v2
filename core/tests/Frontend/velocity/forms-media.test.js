import { mount } from '@vue/test-utils';
import { afterEach, describe, expect, test, vi } from 'vitest';
import CmsFormEnhancer from '../../../resources/themes/velocity/js/components/CmsFormEnhancer.vue';

afterEach(() => {
    document.body.innerHTML = '';
    vi.restoreAllMocks();
});

describe('Velocity form enhancement', () => {
    test('deduplicates submission, preserves the native form, and renders server feedback', async () => {
        document.body.innerHTML = `
            <form id="contact" method="post" action="/contact-submit">
                <input name="contact_phone" value="+20 100">
                <input type="hidden" name="contact_phone_full" data-velocity-phone-source="contact_phone">
                <button type="submit"><span>Send</span><span aria-hidden="true">→</span></button>
            </form>
            <div id="feedback"></div>
        `;
        const fetchMock = vi.fn(() => Promise.resolve({
            ok: true,
            json: () => Promise.resolve({ stat: 'success', msg: 'Sent' }),
        }));
        vi.stubGlobal('fetch', fetchMock);

        const wrapper = mount(CmsFormEnhancer, {
            props: { target: 'contact', feedbackTarget: 'feedback', submittingLabel: 'Sending' },
            attachTo: document.body,
        });
        const form = document.getElementById('contact');
        const submitter = form.querySelector('button');

        const firstSubmit = new SubmitEvent('submit', { bubbles: true, cancelable: true, submitter });
        const repeatedSubmit = new SubmitEvent('submit', { bubbles: true, cancelable: true, submitter });
        form.dispatchEvent(firstSubmit);
        form.dispatchEvent(repeatedSubmit);
        await vi.waitFor(() => expect(fetchMock).toHaveBeenCalledOnce());
        await vi.waitFor(() => expect(document.getElementById('feedback').textContent).toContain('Sent'));

        const body = fetchMock.mock.calls[0][1].body;
        expect(body.get('contact_phone_full')).toBe('+20 100');
        expect(firstSubmit.defaultPrevented).toBe(true);
        expect(repeatedSubmit.defaultPrevented).toBe(true);
        expect(submitter.innerHTML).toContain('<span>Send</span>');
        expect(submitter.innerHTML).toContain('→');
        expect(form.isConnected).toBe(true);
        wrapper.unmount();
    });

    test('closes a popup after a successful Ajax submission', async () => {
        document.body.innerHTML = `
            <dialog id="newsletter-popup" open></dialog>
            <form id="newsletter" method="post" action="/subscribe">
                <input name="subscribe_email" value="reader@example.com">
                <button type="submit">Subscribe</button>
            </form>
            <div id="newsletter-feedback"></div>
        `;
        const popup = document.getElementById('newsletter-popup');
        const close = vi.fn();
        Object.defineProperty(popup, 'close', { value: close });
        vi.stubGlobal('fetch', vi.fn(() => Promise.resolve({
            ok: true,
            json: () => Promise.resolve({ stat: 'success', msg: 'Subscribed' }),
        })));

        const wrapper = mount(CmsFormEnhancer, {
            props: {
                target: 'newsletter',
                feedbackTarget: 'newsletter-feedback',
                closeTarget: 'newsletter-popup',
                successCloseDelay: 0,
            },
            attachTo: document.body,
        });
        const form = document.getElementById('newsletter');
        const submitter = form.querySelector('button');

        form.dispatchEvent(new SubmitEvent('submit', { bubbles: true, cancelable: true, submitter }));

        await vi.waitFor(() => expect(document.getElementById('newsletter-feedback').textContent).toContain('Subscribed'));
        await vi.waitFor(() => expect(close).toHaveBeenCalledOnce());
        wrapper.unmount();
    });
});
