import assert from 'node:assert/strict';
import { existsSync, readFileSync, readdirSync } from 'node:fs';
import { brotliCompressSync, gzipSync } from 'node:zlib';
import { join, resolve } from 'node:path';
import test from 'node:test';

const projectRoot = process.cwd();
const buildRoot = resolve(projectRoot, 'public/build/velocity');
const manifestPath = join(buildRoot, 'manifest.json');
const entry = 'resources/themes/velocity/js/app.js';

function manifest() {
    assert.ok(existsSync(manifestPath), 'Velocity manifest is missing. Run npm run build:velocity first.');

    return JSON.parse(readFileSync(manifestPath, 'utf8'));
}

function emittedFiles() {
    const assetsPath = join(buildRoot, 'assets');

    return readdirSync(assetsPath).map((file) => join(assetsPath, file));
}

test('Velocity build emits an isolated manifest with the configured entry', () => {
    const buildManifest = manifest();
    const entryChunk = buildManifest[entry];

    assert.ok(entryChunk, `Manifest does not contain ${entry}.`);
    assert.equal(entryChunk.isEntry, true);
    assert.ok(existsSync(join(buildRoot, entryChunk.file)));

    for (const stylesheet of entryChunk.css ?? []) {
        assert.ok(existsSync(join(buildRoot, stylesheet)));
    }
});

test('Velocity output contains no Bootstrap, jQuery, or source maps', () => {
    const outputFiles = emittedFiles();
    const bundleText = outputFiles
        .filter((file) => /\.(?:css|js)$/.test(file))
        .map((file) => readFileSync(file, 'utf8'))
        .join('\n');

    assert.doesNotMatch(bundleText, /\b(?:bootstrap|jquery)\b/i);
    assert.equal(outputFiles.some((file) => file.endsWith('.map')), false);
});

test('Velocity entry stays within its compressed size budgets', () => {
    const entryChunk = manifest()[entry];
    const javascriptSize = gzipSync(readFileSync(join(buildRoot, entryChunk.file))).byteLength;
    const cssSize = (entryChunk.css ?? []).reduce(
        (total, file) => total + gzipSync(readFileSync(join(buildRoot, file))).byteLength,
        0,
    );

    assert.ok(javascriptSize <= 35 * 1024, `Entry JavaScript is ${javascriptSize} bytes gzip.`);
    assert.ok(cssSize <= 45 * 1024, `Entry CSS is ${cssSize} bytes gzip.`);

    const javascriptBrotliSize = brotliCompressSync(readFileSync(join(buildRoot, entryChunk.file))).byteLength;
    const cssBrotliSize = (entryChunk.css ?? []).reduce(
        (total, file) => total + brotliCompressSync(readFileSync(join(buildRoot, file))).byteLength,
        0,
    );

    assert.ok(javascriptBrotliSize <= 35 * 1024, `Entry JavaScript is ${javascriptBrotliSize} bytes Brotli.`);
    assert.ok(cssBrotliSize <= 45 * 1024, `Entry CSS is ${cssBrotliSize} bytes Brotli.`);
});

test('Velocity manifest references complete hashed chunks within the first-load JavaScript budget', () => {
    const buildManifest = manifest();
    const entryChunk = buildManifest[entry];
    const reachable = new Set();
    const pending = [entry];

    while (pending.length > 0) {
        const key = pending.pop();
        if (reachable.has(key)) {
            continue;
        }

        reachable.add(key);
        const chunk = buildManifest[key];
        assert.ok(chunk, `Manifest chunk ${key} is missing.`);
        assert.match(chunk.file, /^assets\/.+-[A-Za-z0-9_-]{8,}\.(?:css|js)$/, `${chunk.file} is not content hashed.`);
        assert.ok(existsSync(join(buildRoot, chunk.file)), `${chunk.file} is missing.`);

        for (const stylesheet of chunk.css ?? []) {
            assert.match(stylesheet, /^assets\/.+-[A-Za-z0-9_-]{8,}\.css$/, `${stylesheet} is not content hashed.`);
            assert.ok(existsSync(join(buildRoot, stylesheet)), `${stylesheet} is missing.`);
        }

        pending.push(...(chunk.imports ?? []), ...(chunk.dynamicImports ?? []));
    }

    const entrySize = gzipSync(readFileSync(join(buildRoot, entryChunk.file))).byteLength;
    const vueKey = 'node_modules/vue/dist/vue.runtime.esm-bundler.js';
    const vueSize = gzipSync(readFileSync(join(buildRoot, buildManifest[vueKey].file))).byteLength;
    const pageIslandSizes = (entryChunk.dynamicImports ?? [])
        .filter((key) => key !== vueKey)
        .map((key) => gzipSync(readFileSync(join(buildRoot, buildManifest[key].file))).byteLength);
    const largestNormalPage = entrySize + vueSize + Math.max(0, ...pageIslandSizes);

    assert.ok(
        largestNormalPage <= 100 * 1024,
        `Largest normal-page JavaScript path is ${largestNormalPage} bytes gzip.`,
    );
});

test('Tailwind scans only Velocity and selected shared Blade sources', () => {
    const source = readFileSync(
        resolve(projectRoot, 'resources/themes/velocity/css/app.css'),
        'utf8',
    );

    assert.match(source, /views\/themes\/velocity/);
    assert.match(source, /views\/common\/recaptcha/);
    assert.doesNotMatch(source, /themes\/(?:classic|modern)/);
});

test('Apache cache policy distinguishes hashed assets, the manifest, and fallback CSS', () => {
    const apacheConfig = readFileSync(resolve(projectRoot, 'public/.htaccess'), 'utf8');

    assert.match(apacheConfig, /velocity_hashed_asset/);
    assert.match(apacheConfig, /max-age=31536000, immutable/);
    assert.match(apacheConfig, /velocity\/manifest\\\.json/);
    assert.match(apacheConfig, /no-cache, no-store, must-revalidate/);
    assert.match(apacheConfig, /velocity_fallback/);
});
