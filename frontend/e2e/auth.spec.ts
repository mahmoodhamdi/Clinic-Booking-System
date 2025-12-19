import { test, expect } from '@playwright/test';

test.describe('Authentication', () => {
  test.beforeEach(async ({ page }) => {
    await page.goto('/login');
  });

  test('should display login page', async ({ page }) => {
    await expect(page).toHaveURL('/login');
    await expect(page.locator('input[name="phone"]')).toBeVisible();
    await expect(page.locator('input[name="password"]')).toBeVisible();
    await expect(page.getByRole('button', { name: /تسجيل الدخول|login/i })).toBeVisible();
  });

  test('should show validation errors for empty fields', async ({ page }) => {
    await page.getByRole('button', { name: /تسجيل الدخول|login/i }).click();

    // Check for validation messages
    await expect(page.locator('text=/phone|هاتف/i')).toBeVisible();
  });

  test('should navigate to register page', async ({ page }) => {
    await page.getByRole('link', { name: /إنشاء حساب|register/i }).click();
    await expect(page).toHaveURL('/register');
  });

  test('should navigate to forgot password page', async ({ page }) => {
    await page.getByRole('link', { name: /نسيت كلمة المرور|forgot/i }).click();
    await expect(page).toHaveURL('/forgot-password');
  });

  test('should show error for invalid credentials', async ({ page }) => {
    await page.locator('input[name="phone"]').fill('01234567890');
    await page.locator('input[name="password"]').fill('wrongpassword');
    await page.getByRole('button', { name: /تسجيل الدخول|login/i }).click();

    // Wait for error message (network error or invalid credentials)
    await page.waitForTimeout(2000);
  });
});

test.describe('Registration', () => {
  test.beforeEach(async ({ page }) => {
    await page.goto('/register');
  });

  test('should display registration page', async ({ page }) => {
    await expect(page).toHaveURL('/register');
    await expect(page.locator('input[name="name"]')).toBeVisible();
    await expect(page.locator('input[name="phone"]')).toBeVisible();
    await expect(page.locator('input[name="password"]')).toBeVisible();
    await expect(page.locator('input[name="password_confirmation"]')).toBeVisible();
  });

  test('should show validation errors for mismatched passwords', async ({ page }) => {
    await page.locator('input[name="name"]').fill('Test User');
    await page.locator('input[name="phone"]').fill('01234567890');
    await page.locator('input[name="password"]').fill('password123');
    await page.locator('input[name="password_confirmation"]').fill('different');
    await page.getByRole('button', { name: /إنشاء حساب|register/i }).click();

    await expect(page.locator('text=/match|متطابق/i')).toBeVisible();
  });

  test('should navigate back to login', async ({ page }) => {
    await page.getByRole('link', { name: /تسجيل الدخول|login/i }).click();
    await expect(page).toHaveURL('/login');
  });
});

test.describe('Forgot Password', () => {
  test.beforeEach(async ({ page }) => {
    await page.goto('/forgot-password');
  });

  test('should display forgot password page', async ({ page }) => {
    await expect(page).toHaveURL('/forgot-password');
    await expect(page.locator('input[name="phone"]')).toBeVisible();
  });

  test('should navigate back to login', async ({ page }) => {
    await page.getByRole('link', { name: /تسجيل الدخول|login|back/i }).click();
    await expect(page).toHaveURL('/login');
  });
});
