import { afterEach, describe, expect, test } from 'vitest';
import { enhanceCharts } from '../../../resources/themes/velocity/js/modules/charts.js';

afterEach(() => {
    document.body.innerHTML = '';
});

describe('Velocity table charts', () => {
    test('creates a visual enhancement from escaped server data once', async () => {
        document.body.innerHTML = `
            <div data-velocity-chart data-velocity-config="chart-config"></div>
            <script id="chart-config" type="application/json">
                {"labels":["Open","Closed"],"values":[3,1],"colors":["#2563eb","#0f766e"]}
            </script>
            <table><tr><th>Open</th><td>3</td></tr></table>
        `;

        await expect(enhanceCharts()).resolves.toBe(1);

        const chart = document.querySelector('[data-velocity-chart]');
        expect(chart.dataset.velocityChartReady).toBe('true');
        expect(chart.querySelectorAll('.velocity-chart__item')).toHaveLength(2);
        expect(chart.textContent).toContain('Open: 3');
        expect(document.querySelector('table').textContent).toContain('Open');

        await expect(enhanceCharts()).resolves.toBe(0);
    });
});
