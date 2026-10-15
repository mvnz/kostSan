import { defineConfig } from '@playwright/test';
import path from 'node:path';

const database = path.resolve('storage/framework/testing/browser.sqlite');
if (process.env.APP_ENV !== 'testing' || process.env.DB_CONNECTION !== 'sqlite'
    || process.env.DB_DATABASE !== database || !process.env.BROWSER_TEST_PASSWORD) {
    throw new Error('Browser smoke requires testing env, dedicated SQLite path and BROWSER_TEST_PASSWORD.');
}

export default defineConfig({
    testDir: './tests/Browser',
    testMatch: '*.spec.mjs',
    timeout: 90_000,
    expect: { timeout: 15_000 },
    workers: 1,
    retries: 0,
    reporter: [['list'], ['html', { open: 'never' }]],
    use: {
        baseURL: 'http://127.0.0.1:8115',
        trace: 'retain-on-failure',
        screenshot: 'only-on-failure',
    },
    projects: [
        { name: 'phone', use: { viewport: { width: 390, height: 844 } } },
        { name: 'tablet', use: { viewport: { width: 768, height: 1024 } } },
        { name: 'desktop', use: { viewport: { width: 1440, height: 1000 } } },
    ],
    webServer: {
        command: 'php artisan serve --host=127.0.0.1 --port=8115',
        url: 'http://127.0.0.1:8115/up',
        reuseExistingServer: false,
        // Exercise normal CSRF/session behaviour, not Laravel's testing bypass.
        env: {
            APP_ENV: 'local', APP_DEBUG: 'false', APP_URL: 'http://127.0.0.1:8115',
            DB_CONNECTION: 'sqlite', DB_DATABASE: database, DB_URL: '',
            SESSION_DRIVER: 'file', CACHE_STORE: 'array', MAIL_MAILER: 'array',
            WHATSAPP_ENABLED: 'false', WHATSAPP_TOKEN: '',
        },
    },
});
