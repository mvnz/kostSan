import test from 'node:test';
import assert from 'node:assert/strict';
import { spawnSync } from 'node:child_process';
import path from 'node:path';

test('browser fixture refuses production before bootstrapping or writing data', () => {
    const result = spawnSync('php', ['tests/Browser/seed.php'], {
        env: { ...process.env, APP_ENV: 'production', DB_CONNECTION: 'sqlite', DB_DATABASE: '/not-a-test-database' },
        encoding: 'utf8',
    });
    assert.equal(result.status, 1, result.error?.message);
    assert.match(result.stderr, /Refusing unsafe browser fixture environment/);
});

test('browser fixture refuses an arbitrary database even with testing environment', () => {
    const result = spawnSync('php', ['tests/Browser/seed.php'], {
        env: { ...process.env, APP_ENV: 'testing', DB_CONNECTION: 'sqlite', DB_DATABASE: ':memory:', BROWSER_TEST_PASSWORD: 'SyntheticTestOnly123' },
        encoding: 'utf8',
    });
    assert.equal(result.status, 1, result.error?.message);
    assert.match(result.stderr, /Refusing unsafe browser fixture environment/);
});

test('browser config rejects a non-isolated database instead of starting a server', () => {
    const result = spawnSync(process.execPath, ['--input-type=module', '-e', "await import('./playwright.config.mjs')"], {
        env: { ...process.env, APP_ENV: 'testing', DB_CONNECTION: 'sqlite', DB_DATABASE: path.resolve('database/database.sqlite') },
        encoding: 'utf8',
    });
    assert.equal(result.status, 1);
    assert.match(result.stderr, /Browser smoke requires testing env, dedicated SQLite path/);
});
