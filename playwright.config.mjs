import { defineConfig } from '@playwright/test';

export default defineConfig({
    testDir: './tests/Browser',
    timeout: 30000,
    expect: { timeout: 10000 },
    fullyParallel: false,
    workers: 1,
    reporter: [['list'], ['html', { open: 'never' }]],
    use: {
        launchOptions: process.env.PLAYWRIGHT_CHROMIUM_EXECUTABLE
            ? { executablePath: process.env.PLAYWRIGHT_CHROMIUM_EXECUTABLE }
            : {},
        baseURL: 'http://127.0.0.1:8000',
        screenshot: 'only-on-failure',
        trace: 'retain-on-failure',
    },
    projects: [{ name: 'chromium', use: { browserName: 'chromium' } }],
    webServer: {
        command: 'php workbench/artisan serve --host=127.0.0.1 --port=8000',
        url: 'http://127.0.0.1:8000/demo/profile?section=profile',
        reuseExistingServer: !process.env.CI,
        timeout: 60000,
    },
});
