import { test, expect } from '@playwright/test';
import { readFile } from 'node:fs/promises';

test.beforeEach(async ({ page }) => {
    page.on('pageerror', error => {
        // A JS exception is an operational failure even if the page still renders.
        page.__runtimeErrors ??= [];
        page.__runtimeErrors.push(error.message);
    });
});

test.afterEach(async ({ page }, testInfo) => {
    const errors = page.__runtimeErrors ?? [];
    if (errors.length) await testInfo.attach('javascript-errors', { body: JSON.stringify(errors, null, 2), contentType: 'application/json' });
    expect(errors, 'Browser must not have uncaught JavaScript/CSP errors').toEqual([]);
});

async function login(page, kind) {
    await page.goto('/login');
    await page.locator('[name="email"]').fill(`${kind}@browser.example.test`);
    await page.locator('[name="password"]').fill(process.env.BROWSER_TEST_PASSWORD);
    await page.locator('button[type="submit"]').click();
    await expect(page).not.toHaveURL(/\/login$/);
}

async function checkLayout(page, testInfo, name) {
    // Do not pass on unstyled HTML or a table whose responsive JS never loaded.
    await expect.poll(() => page.locator('link[href*="vendor/css/core.css"]').evaluate(el => !!el.sheet)).toBe(true);
    await page.waitForFunction(() => typeof window.jQuery?.fn?.DataTable === 'function');
    await expect.poll(() => page.evaluate(() => document.documentElement.scrollWidth - window.innerWidth)).toBeLessThanOrEqual(2);
    await testInfo.attach(name, { body: await page.screenshot({ fullPage: true }), contentType: 'image/png' });
}

async function chooseDropdown(page, name, label) {
    const dropdown = page.locator(`select[name="${name}"]`).locator('..');
    await dropdown.locator('button.bs-select-toggle').click();
    await dropdown.locator('button.dropdown-item', { hasText: label }).click();
}

test('admin can navigate core pages and complete a reservation form', async ({ page }, testInfo) => {
    await login(page, 'admin');
    if (page.viewportSize().width < 1200) {
        await page.locator('#layout-navbar .layout-menu-toggle a').click();
        await expect(page.locator('html')).toHaveClass(/layout-menu-expanded/);
        await page.locator('#layout-menu .menu-toggle', { hasText: 'Manajemen Sewa' }).click();
        await page.locator('#layout-menu a[href$="/sewas"]').click();
        await expect(page).toHaveURL(/\/sewas$/);
    }
    for (const route of ['/kamars', '/penghunis', '/reservasis', '/sewas', '/pembayarans', '/invoices', '/keuangans']) {
        const response = await page.goto(route);
        expect(response.status()).toBe(200);
        await checkLayout(page, testInfo, route.slice(1));
    }
    await page.goto('/reservasis/create');
    await checkLayout(page, testInfo, 'reservation-form');
    // Invalid date order must report an error, not silently create a record.
    await chooseDropdown(page, 'kamar_id', 'BROWSER-02 - Browser');
    await chooseDropdown(page, 'penghuni_id', 'Penghuni Sintetis Browser');
    await page.locator('[name="rencana_masuk"]').fill('2027-01-15');
    await page.locator('[name="rencana_keluar"]').fill('2027-01-14');
    await page.getByRole('button', { name: 'Simpan', exact: true }).click();
    await expect(page.locator('.alert-danger')).toBeVisible();
    await page.locator('[name="rencana_keluar"]').fill('2027-02-15');
    await page.getByRole('button', { name: 'Simpan', exact: true }).click();
    await expect(page).toHaveURL(/\/reservasis$/);
    await expect(page.locator('.alert-success')).toBeVisible();
});

test('manual payment amount rejects overflow and recovers without losing form input', async ({ page }, testInfo) => {
    await login(page, 'admin');
    await page.goto('/pembayarans/create');
    await chooseDropdown(page, 'sewa_id', 'BROWSER-01');
    const period = { phone: '2027-08-01', tablet: '2027-09-01', desktop: '2027-10-01' }[testInfo.project.name];
    await page.locator('[name="periode"]').fill(period);
    await page.getByLabel('Jumlah', { exact: true }).fill('10000000000.00');
    await page.getByRole('button', { name: 'Simpan', exact: true }).click();
    await expect(page).toHaveURL(/\/pembayarans\/create$/);
    expect(await page.locator('#payment-amount').evaluate(el => el.validity.rangeOverflow)).toBe(true);
    // Bypass only native form validation to exercise the server's validation and recovery UI.
    await page.locator('form[action$="/pembayarans"]').evaluate(form => { form.noValidate = true; });
    await page.getByRole('button', { name: 'Simpan', exact: true }).click();
    await expect(page.locator('#payment-amount-error')).toContainText('Jumlah maksimal Rp9.999.999.999,99');
    await expect(page.locator('#payment-amount')).toHaveAttribute('aria-invalid', 'true');
    await expect(page.locator('[name="periode"]')).toHaveValue(period);
    await checkLayout(page, testInfo, 'payment-amount-error');
    await page.getByLabel('Jumlah', { exact: true }).fill('650000.25');
    await page.getByRole('button', { name: 'Simpan', exact: true }).click();
    await expect(page).toHaveURL(/\/pembayarans$/);
    await expect(page.locator('.alert-success')).toBeVisible();
});

test('viewer reads details without gaining mutation rights', async ({ page }) => {
    await login(page, 'viewer');
    await page.goto('/reservasis/1');
    await expect(page.getByText('Penghuni Sintetis Browser', { exact: true }).first()).toBeVisible();
    await expect(page.locator('a[href$="/reservasis/1/edit"]')).toHaveCount(0);
    const denied = await page.request.get('/reservasis/1/edit', { headers: { Accept: 'application/json' } });
    expect(denied.status()).toBe(403);
    await page.goto('/pembayarans/1');
    await expect(page.getByRole('button', { name: 'Approve pembayaran', exact: true })).toHaveCount(0);
});

test('reversed income explains protection without offering unlink or edit', async ({ page }, testInfo) => {
    await login(page, 'admin');
    // The dedicated fixture's first finance entry is the reconciled reversal source.
    await page.goto('/keuangans/1');
    await expect(page.getByText('Synthetic reversed manual income', { exact: true })).toBeVisible();
    await expect(page.locator('.alert-info')).toContainText('Pemasukan asal pembalikan tidak dapat');
    await expect(page.getByRole('button', { name: 'Lepas tautan dengan audit' })).toHaveCount(0);
    await expect(page.getByRole('link', { name: 'Edit transaksi' })).toHaveCount(0);
    await checkLayout(page, testInfo, 'reversal-source-protection');
    await page.getByRole('link', { name: 'Pembayaran asal', exact: true }).click();
    await expect(page).toHaveURL(/\/pembayarans\/2$/);
    await expect(page.getByText('Dibalik penuh (riwayat lunas dipertahankan)', { exact: true })).toBeVisible();
});

test('guest cannot mutate without a CSRF token', async ({ page }) => {
    await page.goto('/login');
    const response = await page.request.post('/login', {
        form: { email: 'admin@browser.example.test', password: process.env.BROWSER_TEST_PASSWORD },
    });
    expect(response.status()).toBe(419);
    await page.goto('/reservasis');
    await expect(page).toHaveURL(/\/login$/);
});

test('public computed overflow preserves link and explains operator recovery', async ({ page }, testInfo) => {
    const token = `synthetic-public-total-${testInfo.project.name}`.padEnd(64, '0');
    const url = `/pembayaran/sewa/${token}`;
    await page.goto(url);
    await chooseDropdown(page, 'metode', 'Cash');
    await page.locator('[name="keterangan"]').fill('Synthetic correction request');
    await page.getByRole('button', { name: 'Kirim Pembayaran', exact: true }).click();
    await expect(page.locator('.alert')).toContainText('Total tagihan terlalu besar untuk diproses. Hubungi pengelola');
    await expect(page.locator('[name="keterangan"]')).toHaveValue('Synthetic correction request');
    await expect.poll(() => page.locator('link[href$="bootstrap.min.css"]').evaluate(el => !!el.sheet)).toBe(true);
    await expect.poll(() => page.evaluate(() => document.documentElement.scrollWidth - window.innerWidth)).toBeLessThanOrEqual(2);
    await testInfo.attach('public-total-error', { body: await page.screenshot({ fullPage: true }), contentType: 'image/png' });
    // Simulate the separate operator correcting the source; the public form cannot change its amount.
    await login(page, 'admin');
    await page.goto('/sewas');
    const row = page.locator('tr').filter({ hasText: `BROWSER-PUBLIC-${testInfo.project.name}` });
    const editUrl = await row.locator('a[href$="/edit"]').getAttribute('href');
    await page.goto(editUrl);
    await page.locator('#biaya_tambahan').fill('0');
    await page.getByRole('button', { name: 'Simpan', exact: true }).click();
    await expect(page.locator('.alert-success')).toBeVisible();
    await page.context().clearCookies();
    await page.goto(url);
    await chooseDropdown(page, 'metode', 'Cash');
    await page.getByRole('button', { name: 'Kirim Pembayaran', exact: true }).click();
    await expect(page.getByRole('heading', { name: 'Pembayaran sewa berhasil dikirim', exact: true })).toBeVisible();
});

test('invalid payment filters recover to a usable list and matching CSV', async ({ page }, testInfo) => {
    await login(page, 'admin');
    let downloads = 0;
    page.on('download', () => { downloads++; });
    for (const path of ['/pembayarans?bulan=2026-99', '/pembayarans/export?bulan=2026-99']) {
        await page.goto(path, { referer: new URL(path, 'http://127.0.0.1:8115').href });
        await expect(page).toHaveURL(/\/pembayarans$/);
        await expect(page.locator('.alert-danger')).toContainText('Format bulan harus YYYY-MM.');
        await expect(page.locator('#payment-month')).toHaveValue('');
        expect(downloads, 'Invalid export must not download unfiltered records').toBe(0);
    }
    await checkLayout(page, testInfo, 'payment-filter-recovery');
    await page.getByLabel('Penghuni atau kamar', { exact: true }).fill('BROWSER-01');
    await chooseDropdown(page, 'status', 'Belum lunas');
    await chooseDropdown(page, 'metode', 'Tunai');
    await page.getByRole('button', { name: 'Terapkan', exact: true }).click();
    await expect(page.locator('#tbl-pembayaran tbody tr')).toHaveCount(1);
    await expect(page.locator('#tbl-pembayaran tbody')).toContainText('550.000');
    const pendingDownload = page.waitForEvent('download');
    await page.getByRole('link', { name: 'Export CSV' }).click();
    const download = await pendingDownload;
    expect(download.suggestedFilename()).toMatch(/^pembayaran-.*\.csv$/);
    const csv = await readFile(await download.path(), 'utf8');
    const lines = csv.replace(/^\uFEFF/, '').trim().split(/\r?\n/);
    expect(lines).toHaveLength(2);
    const columns = lines[1].split(',');
    expect(columns[0]).toBe('1');
    expect(columns[6]).toBe('550000.00');
    expect(columns[7]).toBe('belum_lunas');
});
