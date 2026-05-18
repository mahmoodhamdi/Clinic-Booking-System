import { test, expect } from '@playwright/test';

test.describe('3) رحلة المريض', () => {
  test('تسجيل دخول مريض + استعراض اللوحة', async ({ page }) => {
    await page.goto('/login');
    await expect(page).toHaveURL(/\/login/);

    await page.locator('input[name="phone"]').fill('01200000001');
    await page.waitForTimeout(300);
    await page.locator('input[name="password"]').fill('patient123');
    await page.waitForTimeout(300);
    await page.locator('button[type="submit"]').click();

    await page.waitForURL(/\/dashboard/, { timeout: 15000 });
    await expect(page).toHaveURL(/\/dashboard/);
    await page.waitForTimeout(2500);

    await page.goto('/appointments');
    await page.waitForLoadState('networkidle');
    await page.waitForTimeout(2500);

    await page.goto('/medical-records');
    await page.waitForLoadState('networkidle');
    await page.waitForTimeout(2000);

    await page.goto('/prescriptions');
    await page.waitForLoadState('networkidle');
    await page.waitForTimeout(2000);

    await page.goto('/notifications');
    await page.waitForLoadState('networkidle');
    await page.waitForTimeout(2000);

    await page.goto('/profile');
    await page.waitForLoadState('networkidle');
    await page.waitForTimeout(2500);
  });

  test('حجز موعد جديد', async ({ page }) => {
    await page.goto('/login');
    await page.locator('input[name="phone"]').fill('01200000004');
    await page.locator('input[name="password"]').fill('patient123');
    await page.locator('button[type="submit"]').click();
    await page.waitForURL(/\/dashboard/, { timeout: 15000 });
    await page.waitForTimeout(1500);

    await page.goto('/book');
    await page.waitForLoadState('networkidle');
    await page.waitForTimeout(3000);
  });
});
