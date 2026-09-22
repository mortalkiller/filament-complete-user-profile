import { test, expect } from '@playwright/test';

const sections = [
    { id: 'overview', heading: 'Overview' },
    { id: 'profile', heading: 'Profile' },
    { id: 'security', heading: 'Security' },
    { id: 'sessions', heading: 'Sessions' },
    { id: 'api-tokens', heading: 'API Tokens' },
    { id: 'application-settings', heading: 'Application Settings' },
    { id: 'addresses', heading: 'Addresses' },
];

test('full workbench enables built-in features and extension examples', async ({ page }) => {
    const errors = [];

    page.on('pageerror', error => errors.push(error.message));

    await page.setViewportSize({ width: 1440, height: 1000 });

    await page.goto('/demo/profile?section=overview');
    await expect(page.locator('h1').filter({ hasText: 'Overview' })).toBeVisible();
    await expect(page.getByText('Authenticator app', { exact: true })).toBeVisible();
    await expect(page.getByText('Email MFA', { exact: true })).toBeVisible();
    await expect(page.getByText('Active sessions', { exact: true })).toBeVisible();
    await expect(page.getByText('Personal access tokens', { exact: true })).toBeVisible();

    await page.goto('/demo/profile?section=profile');
    await expect(page.locator('h1').filter({ hasText: 'Profile' })).toBeVisible();
    await expect(page.locator('input[type="email"]')).toHaveValue('alex@example.test');
    await expect(page.getByLabel('Job title')).toHaveValue('Product Engineer');
    await expect(page.getByLabel('Phone')).toHaveValue('+351 210 000 000');

    await page.goto('/demo/profile?section=security');
    await expect(page.locator('h1').filter({ hasText: 'Security' })).toBeVisible();
    await expect(page.getByText('Change password', { exact: true })).toBeVisible();
    await expect(page.getByText('authenticatable model must implement', { exact: false })).toHaveCount(0);
    await expect(page.getByText('must support Laravel notifications', { exact: false })).toHaveCount(0);

    await page.goto('/demo/profile?section=sessions');
    await expect(page.locator('h1').filter({ hasText: 'Sessions' })).toBeVisible();
    await expect(page.getByText('Revoke other sessions', { exact: true })).toBeVisible();
    await expect(page.getByText('Browser session management requires SESSION_DRIVER=database.', { exact: true })).toHaveCount(0);
    await expect(page.getByText('The configured Laravel sessions table does not exist.', { exact: true })).toHaveCount(0);

    await page.goto('/demo/profile?section=api-tokens');
    await expect(page.locator('h1').filter({ hasText: 'API Tokens' })).toBeVisible();
    await expect(page.getByText('Create token', { exact: true })).toBeVisible();
    await expect(page.getByText('Laravel Sanctum must be installed to enable API token management.', { exact: true })).toHaveCount(0);
    await expect(page.getByText('The authenticated user model must use Laravel\\Sanctum\\HasApiTokens when API token management is enabled.', { exact: true })).toHaveCount(0);
    await expect(page.getByText('Configure at least one allowed API token ability before enabling API token management.', { exact: true })).toHaveCount(0);

    await page.goto('/demo/profile?section=application-settings');
    await expect(page.locator('h1').filter({ hasText: 'Application Settings' })).toBeVisible();
    await expect(page.getByText('Europe/Lisbon', { exact: true })).toBeVisible();
    await expect(page.getByText('Edit settings', { exact: true })).toBeVisible();

    await page.goto('/demo/profile?section=addresses');
    await expect(page.locator('h1').filter({ hasText: 'Addresses' })).toBeVisible();
    await expect(page.getByText('12 Example Street', { exact: true })).toBeVisible();
    await expect(page.getByText('42 Demo Avenue', { exact: true })).toBeVisible();
    await expect(page.getByText('Add address', { exact: true })).toBeVisible();

    expect(errors).toEqual([]);
});

for (const theme of ['light', 'dark']) {
    for (const section of sections) {
        test(`${section.id} showcase in ${theme}`, async ({ page }, testInfo) => {
            const errors = [];

            page.on('pageerror', error => errors.push(error.message));

            await page.setViewportSize({ width: 1440, height: 1000 });
            await page.emulateMedia({ colorScheme: theme, reducedMotion: 'reduce' });
            await page.goto(`/demo/profile?section=${section.id}`);

            await page.evaluate(currentTheme => {
                document.documentElement.classList.toggle('dark', currentTheme === 'dark');
            }, theme);

            await expect(page.locator('h1').filter({ hasText: section.heading })).toBeVisible();
            await expect(page.locator('.fi-page')).toBeVisible();
            await expect.poll(() => page.evaluate(() => document.documentElement.scrollWidth <= innerWidth)).toBe(true);

            await page.evaluate(() => {
                if (document.activeElement instanceof HTMLElement) {
                    document.activeElement.blur();
                }
            });

            await page.locator('.fi-page').screenshot({
                path: testInfo.outputPath(`${section.id}-${theme}.png`),
            });

            expect(errors).toEqual([]);
        });
    }

    test(`profile mobile showcase in ${theme}`, async ({ page }, testInfo) => {
        const errors = [];

        page.on('pageerror', error => errors.push(error.message));

        await page.setViewportSize({ width: 390, height: 1000 });
        await page.emulateMedia({ colorScheme: theme, reducedMotion: 'reduce' });
        await page.goto('/demo/profile?section=profile');

        await page.evaluate(currentTheme => {
            document.documentElement.classList.toggle('dark', currentTheme === 'dark');
        }, theme);

        await expect(page.locator('h1').filter({ hasText: 'Profile' })).toBeVisible();
        await expect(page.locator('input[type="email"]')).toHaveValue('alex@example.test');
        await expect(page.locator('.fi-page')).toBeVisible();
        await expect.poll(() => page.evaluate(() => document.documentElement.scrollWidth <= innerWidth)).toBe(true);

        await page.evaluate(() => {
            if (document.activeElement instanceof HTMLElement) {
                document.activeElement.blur();
            }
        });

        await page.locator('.fi-page').screenshot({
            path: testInfo.outputPath(`mobile-${theme}.png`),
        });

        expect(errors).toEqual([]);
    });
}
