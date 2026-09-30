export async function enhanceCharts(root = document) {
    const charts = Array.from(root.querySelectorAll('[data-velocity-chart]:not([data-velocity-chart-ready])'));

    charts.forEach((chart) => {
        const configId = chart.dataset.velocityConfig;
        const configElement = configId ? chart.ownerDocument.getElementById(configId) : null;

        if (! configElement) {
            return;
        }

        try {
            const config = JSON.parse(configElement.textContent ?? '{}');
            const labels = Array.isArray(config.labels) ? config.labels : [];
            const values = Array.isArray(config.values)
                ? config.values.map((value) => Math.max(0, Number(value) || 0))
                : [];
            const colors = Array.isArray(config.colors) ? config.colors : [];
            const maximum = Math.max(1, ...values);
            const fragment = chart.ownerDocument.createDocumentFragment();

            labels.forEach((label, index) => {
                const item = chart.ownerDocument.createElement('div');
                const track = chart.ownerDocument.createElement('span');
                const bar = chart.ownerDocument.createElement('span');
                const text = chart.ownerDocument.createElement('span');
                const color = String(colors[index] ?? '');

                item.className = 'velocity-chart__item';
                track.className = 'velocity-chart__track';
                bar.className = 'velocity-chart__bar';
                text.className = 'velocity-chart__label';
                bar.style.inlineSize = `${(values[index] ?? 0) / maximum * 100}%`;
                if (/^(#[0-9a-f]{3,8}|[a-z]+)$/i.test(color)) {
                    bar.style.backgroundColor = color;
                }
                text.textContent = `${label}: ${values[index] ?? 0}`;
                track.append(bar);
                item.append(text, track);
                fragment.append(item);
            });

            chart.replaceChildren(fragment);
            chart.dataset.velocityChartReady = 'true';
        } catch (error) {
            chart.dataset.velocityChartReady = 'failed';
            console.error('[Velocity] Chart enhancement failed.', error);
        }
    });

    return charts.length;
}
