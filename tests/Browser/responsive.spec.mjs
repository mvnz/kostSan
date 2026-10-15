import { test, expect } from '@playwright/test';

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

test('guest cannot mutate without a CSRF token', async ({ page }) => {
    await page.goto('/login');
    const response = await page.request.post('/login', {
        form: { email: 'admin@browser.example.test', password: process.env.BROWSER_TEST_PASSWORD },
    });
    expect(response.status()).toBe(419);
    await page.goto('/reservasis');
    await expect(page).toHaveURL(/\/login$/);
});
