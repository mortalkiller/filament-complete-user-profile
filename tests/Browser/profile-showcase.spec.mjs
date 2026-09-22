import { test, expect } from '@playwright/test';

for (const theme of ['light', 'dark']) {
    for (const width of [390, 1440]) {
        test(`profile showcase in ${theme} at ${width}px`, async ({ page }, testInfo) => {
            const errors = [];

            page.on('pageerror', error => errors.push(error.message));

            await page.setViewportSize({ width, height: 1000 });
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
                path: testInfo.outputPath(`profile-${theme}-${width}.png`),
            });

            expect(errors).toEqual([]);
        });
    }
}
