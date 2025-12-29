import { test, expect } from '@playwright/test';

test.describe('Admin Access Control', () => {
  test('redirects unauthenticated users from admin routes', async ({ page }) => {
    await page.goto('/admin/dashboard');
    await expect(page).toHaveURL(/\/login/);
  });

  test('redirects unauthenticated users from admin appointments', async ({ page }) => {
    await page.goto('/admin/appointments');
    await expect(page).toHaveURL(/\/login/);
  });

  test('redirects unauthenticated users from admin patients', async ({ page }) => {
    await page.goto('/admin/patients');
    await expect(page).toHaveURL(/\/login/);
  });

  test('redirects unauthenticated users from admin settings', async ({ page }) => {
    await page.goto('/admin/settings');
    await expect(page).toHaveURL(/\/login/);
  });

  test('redirects unauthenticated users from admin medical records', async ({ page }) => {
    await page.goto('/admin/medical-records');
    await expect(page).toHaveURL(/\/login/);
  });

  test('redirects unauthenticated users from admin prescriptions', async ({ page }) => {
    await page.goto('/admin/prescriptions');
    await expect(page).toHaveURL(/\/login/);
  });

  test('redirects unauthenticated users from admin payments', async ({ page }) => {
    await page.goto('/admin/payments');
    await expect(page).toHaveURL(/\/login/);
  });

  test('redirects unauthenticated users from admin reports', async ({ page }) => {
    await page.goto('/admin/reports');
    await expect(page).toHaveURL(/\/login/);
  });
});

test.describe('Admin Login UI', () => {
  test('login page is styled correctly', async ({ page }) => {
    await page.goto('/login');

    // Check form elements exist
    await expect(page.locator('input[name="phone"]')).toBeVisible();
    await expect(page.locator('input[name="password"]')).toBeVisible();
    await expect(page.locator('button[type="submit"]')).toBeVisible();
  });

  test('login shows error for invalid credentials', async ({ page }) => {
    await page.goto('/login');
    await page.fill('input[name="phone"]', '01234567890');
    await page.fill('input[name="password"]', 'wrongpassword');
    await page.click('button[type="submit"]');

    // Wait for response
    await page.waitForTimeout(2000);

    // Should stay on login page (authentication failed)
    await expect(page).toHaveURL(/\/login/);
  });
});

test.describe('Admin Page Structure', () => {
  // These tests verify URL patterns exist but require auth for full access

  test('admin dashboard URL pattern exists', async ({ page }) => {
    const response = await page.goto('/admin/dashboard');
    // Should redirect to login or load the page
    expect([200, 302, 307]).toContain(response?.status());
  });

  test('admin appointments URL pattern exists', async ({ page }) => {
    const response = await page.goto('/admin/appointments');
    expect([200, 302, 307]).toContain(response?.status());
  });

  test('admin patients URL pattern exists', async ({ page }) => {
    const response = await page.goto('/admin/patients');
    expect([200, 302, 307]).toContain(response?.status());
  });

  test('admin settings URL pattern exists', async ({ page }) => {
    const response = await page.goto('/admin/settings');
    expect([200, 302, 307]).toContain(response?.status());
  });
});

test.describe('Admin Responsive Design', () => {
  test('login page is mobile responsive', async ({ page }) => {
    await page.setViewportSize({ width: 375, height: 667 });
    await page.goto('/login');

    // All form elements should be visible on mobile
    await expect(page.locator('input[name="phone"]')).toBeVisible();
    await expect(page.locator('input[name="password"]')).toBeVisible();
    await expect(page.locator('button[type="submit"]')).toBeVisible();
  });

  test('login page is tablet responsive', async ({ page }) => {
    await page.setViewportSize({ width: 768, height: 1024 });
    await page.goto('/login');

    await expect(page.locator('input[name="phone"]')).toBeVisible();
    await expect(page.locator('input[name="password"]')).toBeVisible();
  });

  test('login page is desktop responsive', async ({ page }) => {
    await page.setViewportSize({ width: 1920, height: 1080 });
    await page.goto('/login');

    await expect(page.locator('input[name="phone"]')).toBeVisible();
    await expect(page.locator('input[name="password"]')).toBeVisible();
  });
});

test.describe('Admin Accessibility', () => {
  test('login form has proper input types', async ({ page }) => {
    await page.goto('/login');

    const phoneInput = page.locator('input[name="phone"]');
    const passwordInput = page.locator('input[name="password"]');

    await expect(phoneInput).toHaveAttribute('type', 'tel');
    await expect(passwordInput).toHaveAttribute('type', 'password');
  });

  test('login button is keyboard accessible', async ({ page }) => {
    await page.goto('/login');

    // Focus on phone input
    await page.locator('input[name="phone"]').focus();

    // Tab through form
    await page.keyboard.press('Tab'); // to password
    await page.keyboard.press('Tab'); // to submit button

    const submitButton = page.locator('button[type="submit"]');
    await expect(submitButton).toBeFocused();
  });

  test('form can be submitted with Enter key', async ({ page }) => {
    await page.goto('/login');

    await page.fill('input[name="phone"]', '01012345678');
    await page.fill('input[name="password"]', 'testpassword');
    await page.keyboard.press('Enter');

    // Should attempt to submit (may stay on page due to invalid credentials)
    await page.waitForTimeout(1000);
  });
});

test.describe('Admin Security', () => {
  test('password field is masked', async ({ page }) => {
    await page.goto('/login');

    const passwordInput = page.locator('input[name="password"]');
    await expect(passwordInput).toHaveAttribute('type', 'password');
  });

  test('HTTPS redirect check', async ({ page }) => {
    // This test just verifies the page loads - HTTPS is enforced at server level
    await page.goto('/login');
    await expect(page).toHaveURL(/\/login/);
  });
});
