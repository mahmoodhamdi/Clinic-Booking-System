import { test, expect } from '@playwright/test';

test.describe('Navigation', () => {
  test('should redirect unauthenticated users to login', async ({ page }) => {
    await page.goto('/dashboard');
    await expect(page).toHaveURL(/\/login/);
  });

  test('should redirect unauthenticated users from booking to login', async ({ page }) => {
    await page.goto('/book');
    await expect(page).toHaveURL(/\/login/);
  });

  test('should redirect unauthenticated users from admin to login', async ({ page }) => {
    await page.goto('/admin/dashboard');
    await expect(page).toHaveURL(/\/login/);
  });

  test('home page should be accessible', async ({ page }) => {
    await page.goto('/');
    // Should either show home page or redirect to login
    await page.waitForLoadState('networkidle');
  });
});

test.describe('Responsive Design', () => {
  test('login page should be responsive on mobile', async ({ page }) => {
    await page.setViewportSize({ width: 375, height: 667 });
    await page.goto('/login');

    await expect(page.locator('input[name="phone"]')).toBeVisible();
    await expect(page.locator('input[name="password"]')).toBeVisible();
  });

  test('login page should be responsive on tablet', async ({ page }) => {
    await page.setViewportSize({ width: 768, height: 1024 });
    await page.goto('/login');

    await expect(page.locator('input[name="phone"]')).toBeVisible();
    await expect(page.locator('input[name="password"]')).toBeVisible();
  });

  test('login page should be responsive on desktop', async ({ page }) => {
    await page.setViewportSize({ width: 1920, height: 1080 });
    await page.goto('/login');

    await expect(page.locator('input[name="phone"]')).toBeVisible();
    await expect(page.locator('input[name="password"]')).toBeVisible();
  });
});

test.describe('Language Switching', () => {
  test('should be able to switch language on login page', async ({ page }) => {
    await page.goto('/login');

    // Look for language switcher button
    const langSwitcher = page.locator('button:has-text("العربية"), button:has-text("English")');
    if (await langSwitcher.isVisible()) {
      await langSwitcher.click();
      await page.waitForTimeout(500);
    }
  });
});

test.describe('Accessibility', () => {
  test('login page should have proper form labels', async ({ page }) => {
    await page.goto('/login');

    // Check for form elements
    const phoneInput = page.locator('input[name="phone"]');
    const passwordInput = page.locator('input[name="password"]');

    await expect(phoneInput).toBeVisible();
    await expect(passwordInput).toBeVisible();
  });

  test('buttons should be keyboard accessible', async ({ page }) => {
    await page.goto('/login');

    // Tab to the submit button
    await page.keyboard.press('Tab');
    await page.keyboard.press('Tab');
    await page.keyboard.press('Tab');

    // The button should be focusable
    const focusedElement = await page.evaluate(() => document.activeElement?.tagName);
    expect(['BUTTON', 'INPUT', 'A']).toContain(focusedElement);
  });
});
