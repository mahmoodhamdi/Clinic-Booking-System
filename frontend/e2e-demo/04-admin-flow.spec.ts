import { test, expect } from '@playwright/test';

test.describe('4) رحلة الأدمن (الطبيب)', () => {
  test('دخول الأدمن وجولة في كل الشاشات', async ({ page }) => {
    await page.goto('/login');
    await page.locator('input[name="phone"]').fill('01000000000');
    await page.waitForTimeout(300);
    await page.locator('input[name="password"]').fill('admin123');
    await page.waitForTimeout(300);
    await page.locator('button[type="submit"]').click();

    await page.waitForURL(/\/change-password|\/admin\/dashboard/, { timeout: 15000 });
    await page.waitForTimeout(2000);

    if (page.url().includes('/change-password')) {
      await page.locator('input[name="current_password"]').fill('admin123');
      await page.waitForTimeout(300);
      await page.locator('input[name="password"]').fill('Cl1nic#Owner2026!');
      await page.waitForTimeout(300);
      await page.locator('input[name="password_confirmation"]').fill('Cl1nic#Owner2026!');
      await page.waitForTimeout(300);
      await page.locator('button[type="submit"]').click();
      await page.waitForURL(/\/admin\/dashboard/, { timeout: 15000 });
      await page.waitForTimeout(2500);
    }

    await page.goto('/admin/dashboard');
    await page.waitForLoadState('networkidle');
    await page.waitForTimeout(3000);

    await page.goto('/admin/appointments');
    await page.waitForLoadState('networkidle');
    await page.waitForTimeout(3000);

    await page.goto('/admin/patients');
    await page.waitForLoadState('networkidle');
    await page.waitForTimeout(3000);

    await page.goto('/admin/medical-records');
    await page.waitForLoadState('networkidle');
    await page.waitForTimeout(2500);

    await page.goto('/admin/prescriptions');
    await page.waitForLoadState('networkidle');
    await page.waitForTimeout(2500);

    await page.goto('/admin/payments');
    await page.waitForLoadState('networkidle');
    await page.waitForTimeout(2500);

    await page.goto('/admin/reports');
    await page.waitForLoadState('networkidle');
    await page.waitForTimeout(3000);

    await page.goto('/admin/settings');
    await page.waitForLoadState('networkidle');
    await page.waitForTimeout(2500);

    await page.goto('/admin/vacations');
    await page.waitForLoadState('networkidle');
    await page.waitForTimeout(2000);
  });
});
