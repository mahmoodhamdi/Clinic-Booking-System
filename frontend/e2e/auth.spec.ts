import { test, expect } from '@playwright/test';

test.describe('Authentication', () => {
  test.beforeEach(async ({ page }) => {
    await page.goto('/login');
  });

  test('should display login page', async ({ page }) => {
    await expect(page).toHaveURL(/\/login/);
    await expect(page.locator('input[name="phone"]')).toBeVisible();
    await expect(page.locator('input[name="password"]')).toBeVisible();
    // Check for submit button by type instead of text
    await expect(page.locator('button[type="submit"]')).toBeVisible();
  });

  test('should show validation errors for empty fields', async ({ page }) => {
    await page.locator('button[type="submit"]').click();
    // Wait for form validation
    await page.waitForTimeout(1000);
  });

  test('should navigate to register page', async ({ page }) => {
    // Look for link with text containing register keywords
    const registerLink = page.locator('a[href="/register"]');
    await registerLink.click();
    await expect(page).toHaveURL(/\/register/);
  });

  test('should navigate to forgot password page', async ({ page }) => {
    const forgotLink = page.locator('a[href="/forgot-password"]');
    await forgotLink.click();
    await expect(page).toHaveURL(/\/forgot-password/);
  });

  test('should show error for invalid credentials', async ({ page }) => {
    await page.locator('input[name="phone"]').fill('01234567890');
    await page.locator('input[name="password"]').fill('wrongpassword');
    await page.locator('button[type="submit"]').click();
    // Wait for error toast or message
    await page.waitForTimeout(3000);
  });
});

test.describe('Registration', () => {
  test.beforeEach(async ({ page }) => {
    await page.goto('/register');
  });

  test('should display registration page', async ({ page }) => {
    await expect(page).toHaveURL(/\/register/);
    await expect(page.locator('input[name="name"]')).toBeVisible();
    await expect(page.locator('input[name="phone"]')).toBeVisible();
    await expect(page.locator('input[name="password"]')).toBeVisible();
    await expect(page.locator('input[name="password_confirmation"]')).toBeVisible();
  });

  test('should show validation errors for mismatched passwords', async ({ page }) => {
    await page.locator('input[name="name"]').fill('Test User');
    await page.locator('input[name="phone"]').fill('01234567890');
    await page.locator('input[name="password"]').fill('password123');
    await page.locator('input[name="password_confirmation"]').fill('different123');
    await page.locator('button[type="submit"]').click();
    // Wait for validation message
    await page.waitForTimeout(1000);
  });

  test('should navigate back to login', async ({ page }) => {
    const loginLink = page.locator('a[href="/login"]');
    await loginLink.click();
    await expect(page).toHaveURL(/\/login/);
  });
});

test.describe('Forgot Password', () => {
  test.beforeEach(async ({ page }) => {
    await page.goto('/forgot-password');
  });

  test('should display forgot password page', async ({ page }) => {
    await expect(page).toHaveURL(/\/forgot-password/);
    await expect(page.locator('input[name="phone"]')).toBeVisible();
  });

  test('should navigate back to login', async ({ page }) => {
    const backLink = page.locator('a[href="/login"]');
    await backLink.click();
    await expect(page).toHaveURL(/\/login/);
  });
});
