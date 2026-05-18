import { defineConfig } from '@playwright/test';

export default defineConfig({
  testDir: './e2e-demo',
  fullyParallel: false,
  workers: 1,
  reporter: [
    ['html', { outputFolder: 'demo-report', open: 'never' }],
    ['list'],
  ],
  outputDir: './demo-artifacts',
  timeout: 90_000,
  expect: { timeout: 15_000 },
  use: {
    baseURL: process.env.PLAYWRIGHT_BASE_URL || 'http://localhost:3001',
    locale: 'ar-EG',
    viewport: { width: 1440, height: 900 },
    video: {
      mode: 'on',
      size: { width: 1440, height: 900 },
    },
    screenshot: 'on',
    trace: 'on',
    actionTimeout: 15_000,
    navigationTimeout: 30_000,
    launchOptions: {
      slowMo: 250,
    },
  },
  projects: [
    {
      name: 'demo-chrome',
      use: {
        channel: 'chrome',
        headless: true,
      },
    },
  ],
});
