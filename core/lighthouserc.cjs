const configuredUrls = (process.env.VELOCITY_LIGHTHOUSE_URLS ?? '')
    .split(',')
    .map((url) => url.trim())
    .filter(Boolean);
const urls = configuredUrls.length > 0
    ? configuredUrls
    : ['http://127.0.0.1:8014/'];

module.exports = {
    ci: {
        collect: {
            url: urls,
            numberOfRuns: 3,
            ...(configuredUrls.length === 0 ? {
                startServerCommand: 'THEME=velocity php artisan serve --host=127.0.0.1 --port=8014',
                startServerReadyPattern: 'Server running on',
                startServerReadyTimeout: 120_000,
            } : {}),
            settings: {
                preset: 'perf',
                onlyCategories: ['performance', 'accessibility', 'best-practices', 'seo'],
                formFactor: 'mobile',
                screenEmulation: {
                    mobile: true,
                    width: 390,
                    height: 844,
                    deviceScaleFactor: 2,
                    disabled: false,
                },
            },
        },
        assert: {
            budgetsFile: 'lighthouse-budget.json',
            assertions: {
                'categories:performance': ['error', { minScore: 0.9 }],
                'categories:accessibility': ['error', { minScore: 0.95 }],
                'categories:best-practices': ['error', { minScore: 0.95 }],
                'categories:seo': ['error', { minScore: 0.95 }],
                'largest-contentful-paint': ['error', { maxNumericValue: 2500, aggregationMethod: 'median' }],
                'cumulative-layout-shift': ['error', { maxNumericValue: 0.1, aggregationMethod: 'median' }],
                'total-blocking-time': ['error', { maxNumericValue: 200, aggregationMethod: 'median' }],
                'server-response-time': ['warn', { maxNumericValue: 800, aggregationMethod: 'median' }],
            },
        },
        upload: {
            target: 'filesystem',
            outputDir: 'output/lighthouse/velocity',
        },
    },
};
