import { test, expect } from '@playwright/test';

test.describe('1) الصفحة الرئيسية واللغة', () => {
  test('عرض الـ landing بالعربي وتبديل الـ theme', async ({ page }) => {
    await page.goto('/');
    await expect(page).toHaveURL(/\/$|\/$/);

    await expect(page.getByRole('link', { name: /تسجيل الدخول/ }).first()).toBeVisible();
    await expect(page.getByRole('link', { name: /احجز موعد|احجز الآن/ }).first()).toBeVisible();

    await expect(page.getByText(/كيف يعمل النظام/)).toBeVisible();
    await expect(page.getByText(/خدماتنا/)).toBeVisible();

    await page.waitForTimeout(800);

    const themeToggle = page.getByRole('button', { name: /toggle theme/i });
    await themeToggle.click();
    await page.waitForTimeout(600);
    await themeToggle.click();
    await page.waitForTimeout(600);
  });
});
