import { test, expect } from '@playwright/test';

for (const theme of ['light', 'dark']) {
    for (const viewport of [{ width: 1440, height: 1000 }, { width: 390, height: 900 }]) {
        test(`profile cards in ${theme} at ${viewport.width}px`, async ({ page }, testInfo) => {
            const errors = [];
            page.on('pageerror', error => errors.push(error.message));
            await page.setViewportSize(viewport);
            await page.emulateMedia({ colorScheme: theme, reducedMotion: 'reduce' });
            await page.goto('/demo/profile?section=profile');
            await page.evaluate(value => document.documentElement.classList.toggle('dark', value === 'dark'), theme);

            const information = page.locator('.fcup-profile-information');
            const security = page.locator('.fcup-account-security');
            const cards = security.locator('.fcup-security-card');
            await expect(information.getByRole('heading', { name: 'Profile Information', exact: true })).toBeVisible();
            await expect(information.getByText('Update your personal information and profile details.')).toBeVisible();
            await expect(cards).toHaveCount(4);
            await expect(security.locator('.fcup-security-card-dot')).toHaveCount(2);
            await expect(security.locator('.fi-section-content')).toHaveCSS('row-gap', '10px');
            await expect(security.locator('.fi-section-content-ctn')).toHaveCSS('border-top-width', '0px');
            await expect(cards.first()).toHaveAttribute('data-security-enabled', 'false');
            await expect(cards.first()).toContainText('Not configured');
            await expect(cards.first()).toHaveCSS('display', 'grid');
            await expect(cards.first()).toHaveCSS('border-top-width', '1px');
            expect(await page.evaluate(() => document.documentElement.scrollWidth > innerWidth)).toBe(false);

            const profileBox = await information.boundingBox();
            const securityBox = await security.boundingBox();
            if (viewport.width > 1000) {
                expect(Math.abs(profileBox.y - securityBox.y)).toBeLessThan(2);
                expect(securityBox.x).toBeGreaterThan(profileBox.x + profileBox.width);
            } else {
                expect(securityBox.y).toBeGreaterThan(profileBox.y + profileBox.height);
            }
            await page.screenshot({ path: testInfo.outputPath(`profile-${theme}-${viewport.width}.png`), fullPage: true });
            await security.screenshot({ path: testInfo.outputPath('account-security.png') });
            await cards.first().focus();
            await expect(cards.first()).toBeFocused();
            await page.keyboard.press('Enter');
            await expect(page).toHaveURL(/section=security/);
            await expect(page.getByRole('heading', { name: 'Security', exact: true, level: 1 })).toBeVisible();
            expect(errors).toEqual([]);
        });
    }
}

test('configured profile and routed Billing page have the same content width', async ({ page }) => {
    const errors = [];
    page.on('pageerror', error => errors.push(error.message));
    await page.setViewportSize({ width: 1600, height: 1000 });
    await page.goto('/demo/profile?section=profile');
    const profileBox = await page.locator('[data-fph-root]').boundingBox();
    await page.locator('.fi-page-sub-navigation-tabs').getByRole('link', { name: 'Billing', exact: true }).click();
    await expect(page).toHaveURL(/\/profile\/billing$/);
    const billingBox = await page.locator('[data-fph-root]').boundingBox();
    expect(Math.abs(profileBox.width - billingBox.width)).toBeLessThan(2);
    expect(errors).toEqual([]);
});
