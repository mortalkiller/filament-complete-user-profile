import { test, expect } from '@playwright/test';

test('routed account page keeps actions, active navigation and browser history', async ({ page }, testInfo) => {
    const errors = [];
    page.on('pageerror', error => errors.push(error.message));
    await page.goto('/demo/profile?section=profile');
    await page.locator('.fi-page-sub-navigation-tabs').getByRole('link', { name: 'Billing', exact: true }).click();
    await expect(page).toHaveURL(/\/demo\/profile\/billing$/);
    await expect(page.getByRole('heading', { name: 'Billing', exact: true })).toBeVisible();
    await expect(page.locator('[data-fph-sub-navigation]')).toHaveCount(1);
    await expect(page.locator('.fi-page-sub-navigation-tabs .fi-active')).toContainText('Billing');
    await page.getByRole('button', { name: 'Refresh demo', exact: true }).click();
    await expect(page.getByText('Demo refreshes: 1', { exact: true })).toBeVisible();
    await page.screenshot({ path: testInfo.outputPath('billing-desktop.png'), fullPage: true });
    await page.locator('.fi-page-sub-navigation-tabs').getByRole('link', { name: 'Security', exact: true }).click();
    await expect(page).toHaveURL(/section=security/);
    await page.goBack();
    await expect(page.getByRole('heading', { name: 'Billing', exact: true })).toBeVisible();
    await page.goForward();
    await expect(page).toHaveURL(/section=security/);
    await page.goto('/demo/profile/billing');
    await page.reload();
    await expect(page.locator('[data-fph-sub-navigation]')).toHaveCount(1);
    expect(errors).toEqual([]);
});

test('routed account page uses the mobile account menu without overflow', async ({ page }, testInfo) => {
    const errors = [];
    page.on('pageerror', error => errors.push(error.message));
    await page.setViewportSize({ width: 390, height: 900 });
    await page.goto('/demo/profile/billing');
    await expect(page.getByRole('heading', { name: 'Billing', exact: true })).toBeVisible();
    const dropdown = page.locator('.fi-page-sub-navigation-dropdown');
    await expect(dropdown).toBeVisible();
    expect(await page.evaluate(() => document.documentElement.scrollWidth > window.innerWidth)).toBe(false);
    await page.screenshot({ path: testInfo.outputPath('billing-mobile.png'), fullPage: true });
    await dropdown.getByRole('button').first().click();
    await dropdown.getByRole('link', { name: 'Profile', exact: true }).click();
    await expect(page.getByRole('heading', { name: 'Profile', exact: true, level: 1 })).toBeVisible();
    expect(errors).toEqual([]);
});
