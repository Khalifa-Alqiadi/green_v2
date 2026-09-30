import AxeBuilder from '@axe-core/playwright';
import { expect, test } from '@playwright/test';

const configuredPaths = (process.env.VELOCITY_E2E_PATHS ?? '/')
    .split(',')
    .map((path) => path.trim())
    .filter(Boolean);

for (const path of configuredPaths) {
    test(`Velocity ${path} has no serious accessibility or local asset failures`, async ({ page, baseURL }) => {
        const runtimeErrors = [];
        const failedAssets = [];
        const origin = new URL(baseURL).origin;

        page.on('pageerror', (error) => runtimeErrors.push(error.message));
        page.on('response', (response) => {
            const url = new URL(response.url());
            if (url.origin === origin && response.status() >= 400 && /\.(?:css|js|mjs|woff2?|png|jpe?g|webp|svg)(?:$|\?)/i.test(url.pathname)) {
                failedAssets.push(`${response.status()} ${url.pathname}`);
            }
        });

        const response = await page.goto(path, { waitUntil: 'networkidle' });

        expect(response?.status()).toBeLessThan(400);
        await expect(page.locator('html')).toHaveAttribute('data-theme', 'velocity');
        await expect(page.locator('main')).toBeVisible();

        const accessibility = await new AxeBuilder({ page })
            .withTags(['wcag2a', 'wcag2aa', 'wcag21aa', 'wcag22aa'])
            .analyze();
        const blockingViolations = accessibility.violations.filter(
            (violation) => ['critical', 'serious'].includes(violation.impact),
        );

        expect(blockingViolations, JSON.stringify(blockingViolations, null, 2)).toEqual([]);
        expect(runtimeErrors).toEqual([]);
        expect(failedAssets).toEqual([]);
    });
}
