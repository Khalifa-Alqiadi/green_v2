import { mount } from '@vue/test-utils';
import { afterEach, describe, expect, test, vi } from 'vitest';
import TopicExplorer from '../../../resources/themes/velocity/js/components/TopicExplorer.vue';

afterEach(() => {
    document.body.innerHTML = '';
    vi.restoreAllMocks();
});

describe('Velocity topic explorer', () => {
    test('appends server-rendered cards and keeps native pagination available', async () => {
        document.body.innerHTML = `
            <div id="results"><article data-velocity-topic-card>First</article></div>
            <nav id="pagination"><a href="?page=2">Page 2</a></nav>
        `;
        const fetchMock = vi.spyOn(window, 'fetch').mockResolvedValue(new Response(
            '<article data-velocity-topic-card>Second</article>',
            { status: 200, headers: { 'Content-Type': 'text/html' } },
        ));
        const wrapper = mount(TopicExplorer, {
            props: {
                target: 'results',
                paginationTarget: 'pagination',
                endpoint: '/topics-list/1/0/1?lang=en',
                page: 1,
                lastPage: 2,
                resultsLabel: 'results loaded',
            },
        });

        await wrapper.get('button').trigger('click');
        await vi.waitFor(() => expect(document.querySelectorAll('[data-velocity-topic-card]')).toHaveLength(2));

        expect(fetchMock).toHaveBeenCalledWith(
            expect.objectContaining({ pathname: '/topics-list/1/0/1', search: expect.stringContaining('page=2') }),
            expect.objectContaining({ credentials: 'same-origin' }),
        );
        expect(document.querySelector('#pagination a')).not.toBeNull();
        expect(wrapper.text()).toContain('1 results loaded');
        expect(wrapper.find('button').exists()).toBe(false);
    });

    test('keeps a retry control and announces network failures', async () => {
        document.body.innerHTML = '<div id="results"></div>';
        vi.spyOn(window, 'fetch').mockRejectedValue(new Error('offline'));
        const wrapper = mount(TopicExplorer, {
            props: {
                target: 'results',
                endpoint: '/topics-list/1',
                page: 1,
                lastPage: 2,
                errorLabel: 'Try again',
            },
        });

        await wrapper.get('button').trigger('click');
        await vi.waitFor(() => expect(wrapper.get('[role="alert"]').text()).toBe('Try again'));

        expect(wrapper.find('button').exists()).toBe(true);
        expect(wrapper.get('button').attributes('disabled')).toBeUndefined();
    });
});
