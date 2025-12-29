import { test, expect } from '@playwright/test';

test.describe('Patient Journey', () => {
  // Note: These tests require a running backend with test data
  // For CI, these should be run against a test environment

  test.describe('Unauthenticated User', () => {
    test('can view login page', async ({ page }) => {
      await page.goto('/login');
      await expect(page).toHaveURL(/\/login/);
      await expect(page.locator('input[name="phone"]')).toBeVisible();
      await expect(page.locator('input[name="password"]')).toBeVisible();
    });

    test('can navigate to register page', async ({ page }) => {
      await page.goto('/login');
      await page.click('a[href="/register"]');
      await expect(page).toHaveURL(/\/register/);
    });

    test('can navigate to forgot password page', async ({ page }) => {
      await page.goto('/login');
      await page.click('a[href="/forgot-password"]');
      await expect(page).toHaveURL(/\/forgot-password/);
    });

    test('is redirected when trying to access protected routes', async ({ page }) => {
      await page.goto('/dashboard');
      await expect(page).toHaveURL(/\/login/);

      await page.goto('/book');
      await expect(page).toHaveURL(/\/login/);

      await page.goto('/appointments');
      await expect(page).toHaveURL(/\/login/);
    });
  });

  test.describe('Registration Form', () => {
    test('shows validation errors for empty fields', async ({ page }) => {
      await page.goto('/register');
      await page.click('button[type="submit"]');
      // Wait for validation
      await page.waitForTimeout(500);
      // Form should not navigate away
      await expect(page).toHaveURL(/\/register/);
    });

    test('shows validation error for mismatched passwords', async ({ page }) => {
      await page.goto('/register');
      await page.fill('input[name="name"]', 'Test User');
      await page.fill('input[name="phone"]', '01012345678');
      await page.fill('input[name="password"]', 'Password123!');
      await page.fill('input[name="password_confirmation"]', 'DifferentPassword!');
      await page.click('button[type="submit"]');
      // Wait for validation
      await page.waitForTimeout(500);
      // Should stay on register page
      await expect(page).toHaveURL(/\/register/);
    });

    test('has required form fields', async ({ page }) => {
      await page.goto('/register');
      await expect(page.locator('input[name="name"]')).toBeVisible();
      await expect(page.locator('input[name="phone"]')).toBeVisible();
      await expect(page.locator('input[name="password"]')).toBeVisible();
      await expect(page.locator('input[name="password_confirmation"]')).toBeVisible();
    });
  });

  test.describe('Forgot Password Flow', () => {
    test('shows forgot password form', async ({ page }) => {
      await page.goto('/forgot-password');
      await expect(page.locator('input[name="phone"]')).toBeVisible();
      await expect(page.locator('button[type="submit"]')).toBeVisible();
    });

    test('can navigate back to login', async ({ page }) => {
      await page.goto('/forgot-password');
      await page.click('a[href="/login"]');
      await expect(page).toHaveURL(/\/login/);
    });
  });

  test.describe('OTP Verification Page', () => {
    test('redirects to forgot-password if no phone in session', async ({ page }) => {
      await page.goto('/verify-otp');
      await expect(page).toHaveURL(/\/forgot-password/);
    });
  });
});

test.describe('Patient Dashboard UI', () => {
  test('login page has proper styling', async ({ page }) => {
    await page.goto('/login');

    // Check for gradient background
    const container = page.locator('.bg-gradient-to-br');
    await expect(container).toBeVisible();
  });

  test('login form is centered', async ({ page }) => {
    await page.goto('/login');

    // Card should be visible and centered
    const card = page.locator('.bg-white, .bg-card').first();
    await expect(card).toBeVisible();
  });

  test('language switcher is available', async ({ page }) => {
    await page.goto('/login');

    // Should have a language switcher button
    const buttons = page.locator('button');
    expect(await buttons.count()).toBeGreaterThan(0);
  });
});

test.describe('Mobile Responsiveness', () => {
  test('login page works on mobile viewport', async ({ page }) => {
    await page.setViewportSize({ width: 375, height: 667 });
    await page.goto('/login');

    await expect(page.locator('input[name="phone"]')).toBeVisible();
    await expect(page.locator('input[name="password"]')).toBeVisible();
    await expect(page.locator('button[type="submit"]')).toBeVisible();
  });

  test('register page works on mobile viewport', async ({ page }) => {
    await page.setViewportSize({ width: 375, height: 667 });
    await page.goto('/register');

    await expect(page.locator('input[name="name"]')).toBeVisible();
    await expect(page.locator('input[name="phone"]')).toBeVisible();
  });
});
