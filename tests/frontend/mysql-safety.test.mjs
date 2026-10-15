import test from 'node:test';
import assert from 'node:assert/strict';
import { spawnSync } from 'node:child_process';

for (const [label, override] of [
    ['production', { APP_ENV: 'production' }],
    ['arbitrary database', { DB_DATABASE: 'kostsan_operational' }],
    ['remote host', { DB_HOST: 'db.example.test' }],
    ['database URL', { DB_URL: 'mysql://not-a-test-target' }],
]) {
    test(`MySQL harness refuses ${label} before connecting`, () => {
        const result = spawnSync('php', ['tests/Concurrency/mysql.php'], {
            env: { ...process.env, APP_ENV: 'testing', DB_CONNECTION: 'mysql', DB_HOST: '127.0.0.1',
                DB_PORT: '3306', DB_DATABASE: 'kostsan_concurrency_test', DB_USERNAME: 'kostsan_ci',
                DB_URL: '', WHATSAPP_ENABLED: 'false', MAIL_MAILER: 'array', SESSION_DRIVER: 'array', ...override },
            encoding: 'utf8',
        });
        assert.equal(result.status, 1, result.error?.message);
        assert.match(result.stderr, /Refusing (unsafe MySQL concurrency environment|database URL or cached configuration)/);
    });
}
