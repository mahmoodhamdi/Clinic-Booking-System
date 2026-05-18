import { test, expect } from '@playwright/test';

test.describe('2) صفحة التسجيل والـ validation', () => {
  test('عرض رسائل validation عربي + متطلبات الباسورد', async ({ page }) => {
    await page.goto('/register');
    await expect(page).toHaveURL(/\/register/);

    await expect(page.locator('input[name="name"]')).toBeVisible();
    await expect(page.locator('input[name="phone"]')).toBeVisible();
    await expect(page.locator('input[name="password"]')).toBeVisible();

    await expect(page.getByText(/8 أحرف على الأقل، وتشمل/)).toBeVisible();

    await page.locator('input[name="name"]').fill('محمد اختبار');
    await page.waitForTimeout(300);
    await page.locator('input[name="phone"]').fill('01125463590');
    await page.waitForTimeout(300);
    await page.locator('input[name="password"]').fill('weakpass');
    await page.waitForTimeout(300);
    await page.locator('input[name="password_confirmation"]').fill('weakpass');
    await page.waitForTimeout(300);

    await page.locator('button[type="submit"]').click();

    await expect(
      page.getByText(/يجب أن تحتوي على حرف كبير وحرف صغير|يجب أن تحتوي على رقم|يجب أن تحتوي على رمز خاص/)
    ).toBeVisible({ timeout: 10000 });

    await page.waitForTimeout(1500);
  });

  test('تسجيل ناجح بمستخدم جديد', async ({ page }) => {
    await page.goto('/register');

    const unique = Date.now().toString().slice(-7);
    const phone = `0112${unique.padStart(7, '0')}`.slice(0, 11);

    await page.locator('input[name="name"]').fill('عميل تجريبي');
    await page.waitForTimeout(200);
    await page.locator('input[name="phone"]').fill(phone);
    await page.waitForTimeout(200);
    await page.locator('input[name="password"]').fill('Cl1nic#Demo2026!');
    await page.waitForTimeout(200);
    await page.locator('input[name="password_confirmation"]').fill('Cl1nic#Demo2026!');
    await page.waitForTimeout(300);

    await page.locator('button[type="submit"]').click();

    await page.waitForURL(/\/dashboard/, { timeout: 15000 });
    await expect(page).toHaveURL(/\/dashboard/);
    await page.waitForTimeout(2000);
  });
});
