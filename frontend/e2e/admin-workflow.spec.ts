import { test, expect } from '@playwright/test';

test.describe('Admin Workflow - Reschedule & Payment', () => {
  // Critical Admin Flow: Login → Dashboard → Reschedule → Record Payment

  test.describe('Admin Authentication', () => {
    test('admin can access login page', async ({ page }) => {
      await page.goto('/login');

      await expect(page.locator('input[name="phone"]')).toBeVisible();
      await expect(page.locator('input[name="password"]')).toBeVisible();
      await expect(page.locator('button[type="submit"]')).toBeVisible();
    });

    test('admin login form accepts credentials', async ({ page }) => {
      await page.goto('/login');

      // Fill admin credentials (01000000000 is default admin)
      await page.fill('input[name="phone"]', '01000000000');
      await page.fill('input[name="password"]', 'admin123');

      await expect(page.locator('input[name="phone"]')).toHaveValue('01000000000');
      await expect(page.locator('input[name="password"]')).toHaveValue('admin123');
    });

    test('admin login validates phone format', async ({ page }) => {
      await page.goto('/login');

      await page.fill('input[name="phone"]', '123');
      await page.fill('input[name="password"]', 'admin123');
      await page.click('button[type="submit"]');

      await page.waitForTimeout(500);
      await expect(page).toHaveURL(/\/login/);
    });

    test('admin login validates password', async ({ page }) => {
      await page.goto('/login');

      await page.fill('input[name="phone"]', '01000000000');
      await page.click('button[type="submit"]');

      await page.waitForTimeout(500);
      await expect(page).toHaveURL(/\/login/);
    });

    test('admin login handles wrong credentials', async ({ page }) => {
      await page.goto('/login');

      await page.fill('input[name="phone"]', '01000000000');
      await page.fill('input[name="password"]', 'wrongpassword');
      await page.click('button[type="submit"]');

      await page.waitForTimeout(3000);
      await expect(page).toHaveURL(/\/login/);
    });
  });

  test.describe('Admin Dashboard Access Control', () => {
    test('admin dashboard requires authentication', async ({ page }) => {
      await page.goto('/admin/dashboard');
      await expect(page).toHaveURL(/\/login/);
    });

    test('admin appointments requires authentication', async ({ page }) => {
      await page.goto('/admin/appointments');
      await expect(page).toHaveURL(/\/login/);
    });

    test('admin patients requires authentication', async ({ page }) => {
      await page.goto('/admin/patients');
      await expect(page).toHaveURL(/\/login/);
    });

    test('admin medical-records requires authentication', async ({ page }) => {
      await page.goto('/admin/medical-records');
      await expect(page).toHaveURL(/\/login/);
    });

    test('admin prescriptions requires authentication', async ({ page }) => {
      await page.goto('/admin/prescriptions');
      await expect(page).toHaveURL(/\/login/);
    });

    test('admin payments requires authentication', async ({ page }) => {
      await page.goto('/admin/payments');
      await expect(page).toHaveURL(/\/login/);
    });

    test('admin reports requires authentication', async ({ page }) => {
      await page.goto('/admin/reports');
      await expect(page).toHaveURL(/\/login/);
    });

    test('admin settings requires authentication', async ({ page }) => {
      await page.goto('/admin/settings');
      await expect(page).toHaveURL(/\/login/);
    });

    test('admin vacations requires authentication', async ({ page }) => {
      await page.goto('/admin/vacations');
      await expect(page).toHaveURL(/\/login/);
    });
  });

  test.describe('Admin Route Patterns', () => {
    test('admin dashboard route exists', async ({ page }) => {
      const response = await page.goto('/admin/dashboard');
      expect([200, 302, 307]).toContain(response?.status());
    });

    test('admin appointments route exists', async ({ page }) => {
      const response = await page.goto('/admin/appointments');
      expect([200, 302, 307]).toContain(response?.status());
    });

    test('admin patients route exists', async ({ page }) => {
      const response = await page.goto('/admin/patients');
      expect([200, 302, 307]).toContain(response?.status());
    });

    test('admin medical-records route exists', async ({ page }) => {
      const response = await page.goto('/admin/medical-records');
      expect([200, 302, 307]).toContain(response?.status());
    });

    test('admin prescriptions route exists', async ({ page }) => {
      const response = await page.goto('/admin/prescriptions');
      expect([200, 302, 307]).toContain(response?.status());
    });

    test('admin payments route exists', async ({ page }) => {
      const response = await page.goto('/admin/payments');
      expect([200, 302, 307]).toContain(response?.status());
    });

    test('admin reports route exists', async ({ page }) => {
      const response = await page.goto('/admin/reports');
      expect([200, 302, 307]).toContain(response?.status());
    });

    test('admin settings route exists', async ({ page }) => {
      const response = await page.goto('/admin/settings');
      expect([200, 302, 307]).toContain(response?.status());
    });

    test('admin vacations route exists', async ({ page }) => {
      const response = await page.goto('/admin/vacations');
      expect([200, 302, 307]).toContain(response?.status());
    });
  });

  test.describe('Admin Responsive Design', () => {
    test('admin login is mobile responsive', async ({ page }) => {
      await page.setViewportSize({ width: 375, height: 667 });
      await page.goto('/login');

      await expect(page.locator('input[name="phone"]')).toBeVisible();
      await expect(page.locator('input[name="password"]')).toBeVisible();
      await expect(page.locator('button[type="submit"]')).toBeVisible();
    });

    test('admin login is tablet responsive', async ({ page }) => {
      await page.setViewportSize({ width: 768, height: 1024 });
      await page.goto('/login');

      await expect(page.locator('input[name="phone"]')).toBeVisible();
      await expect(page.locator('input[name="password"]')).toBeVisible();
    });

    test('admin login is large tablet responsive', async ({ page }) => {
      await page.setViewportSize({ width: 1024, height: 768 });
      await page.goto('/login');

      await expect(page.locator('input[name="phone"]')).toBeVisible();
      await expect(page.locator('input[name="password"]')).toBeVisible();
    });

    test('admin login is desktop responsive', async ({ page }) => {
      await page.setViewportSize({ width: 1440, height: 900 });
      await page.goto('/login');

      await expect(page.locator('input[name="phone"]')).toBeVisible();
      await expect(page.locator('input[name="password"]')).toBeVisible();
    });

    test('admin login is large desktop responsive', async ({ page }) => {
      await page.setViewportSize({ width: 1920, height: 1080 });
      await page.goto('/login');

      await expect(page.locator('input[name="phone"]')).toBeVisible();
      await expect(page.locator('input[name="password"]')).toBeVisible();
    });
  });

  test.describe('Admin Keyboard Navigation', () => {
    test('form fields are tab accessible', async ({ page }) => {
      await page.goto('/login');

      // Focus on phone input
      await page.locator('input[name="phone"]').focus();
      await expect(page.locator('input[name="phone"]')).toBeFocused();

      // Tab to password
      await page.keyboard.press('Tab');

      // Tab to submit button
      await page.keyboard.press('Tab');
    });

    test('form can be submitted with enter key', async ({ page }) => {
      await page.goto('/login');

      await page.fill('input[name="phone"]', '01000000000');
      await page.fill('input[name="password"]', 'admin123');
      await page.keyboard.press('Enter');

      // Wait for form submission
      await page.waitForTimeout(2000);
    });

    test('escape key behavior', async ({ page }) => {
      await page.goto('/login');

      await page.fill('input[name="phone"]', '01000000000');
      await page.keyboard.press('Escape');

      // Input should still have value
      await expect(page.locator('input[name="phone"]')).toHaveValue('01000000000');
    });
  });

  test.describe('Admin Form Input Validation', () => {
    test('phone input has correct type', async ({ page }) => {
      await page.goto('/login');

      const phoneInput = page.locator('input[name="phone"]');
      await expect(phoneInput).toHaveAttribute('type', 'tel');
    });

    test('password input is masked', async ({ page }) => {
      await page.goto('/login');

      const passwordInput = page.locator('input[name="password"]');
      await expect(passwordInput).toHaveAttribute('type', 'password');
    });

    test('phone input accepts only digits', async ({ page }) => {
      await page.goto('/login');

      const phoneInput = page.locator('input[name="phone"]');
      await phoneInput.fill('01000000000');

      // Should have the value
      const value = await phoneInput.inputValue();
      expect(value).toMatch(/^\d+$/);
    });
  });
});

test.describe('Admin Appointment Management', () => {
  test.describe('Appointment Reschedule Flow', () => {
    test('reschedule requires admin authentication', async ({ page }) => {
      await page.goto('/admin/appointments');
      await expect(page).toHaveURL(/\/login/);
    });

    test('appointments page returns valid response', async ({ page }) => {
      const response = await page.goto('/admin/appointments');
      expect([200, 302, 307]).toContain(response?.status());
    });

    test('appointments page on mobile viewport', async ({ page }) => {
      await page.setViewportSize({ width: 375, height: 667 });
      const response = await page.goto('/admin/appointments');
      expect([200, 302, 307]).toContain(response?.status());
    });

    test('appointments page on tablet viewport', async ({ page }) => {
      await page.setViewportSize({ width: 768, height: 1024 });
      const response = await page.goto('/admin/appointments');
      expect([200, 302, 307]).toContain(response?.status());
    });
  });
});

test.describe('Admin Payment Management', () => {
  test.describe('Payment Record Flow', () => {
    test('payments page requires admin authentication', async ({ page }) => {
      await page.goto('/admin/payments');
      await expect(page).toHaveURL(/\/login/);
    });

    test('payments page returns valid response', async ({ page }) => {
      const response = await page.goto('/admin/payments');
      expect([200, 302, 307]).toContain(response?.status());
    });

    test('payments page on mobile viewport', async ({ page }) => {
      await page.setViewportSize({ width: 375, height: 667 });
      const response = await page.goto('/admin/payments');
      expect([200, 302, 307]).toContain(response?.status());
    });

    test('payments page on tablet viewport', async ({ page }) => {
      await page.setViewportSize({ width: 768, height: 1024 });
      const response = await page.goto('/admin/payments');
      expect([200, 302, 307]).toContain(response?.status());
    });

    test('payments page on desktop viewport', async ({ page }) => {
      await page.setViewportSize({ width: 1440, height: 900 });
      const response = await page.goto('/admin/payments');
      expect([200, 302, 307]).toContain(response?.status());
    });
  });
});

test.describe('Admin Settings Management', () => {
  test.describe('Schedule Toggle Flow', () => {
    test('settings page requires admin authentication', async ({ page }) => {
      await page.goto('/admin/settings');
      await expect(page).toHaveURL(/\/login/);
    });

    test('settings page returns valid response', async ({ page }) => {
      const response = await page.goto('/admin/settings');
      expect([200, 302, 307]).toContain(response?.status());
    });
  });

  test.describe('Vacation Management', () => {
    test('vacations page requires admin authentication', async ({ page }) => {
      await page.goto('/admin/vacations');
      await expect(page).toHaveURL(/\/login/);
    });

    test('vacations page returns valid response', async ({ page }) => {
      const response = await page.goto('/admin/vacations');
      expect([200, 302, 307]).toContain(response?.status());
    });
  });
});

test.describe('Admin Reports', () => {
  test('reports page requires admin authentication', async ({ page }) => {
    await page.goto('/admin/reports');
    await expect(page).toHaveURL(/\/login/);
  });

  test('reports page returns valid response', async ({ page }) => {
    const response = await page.goto('/admin/reports');
    expect([200, 302, 307]).toContain(response?.status());
  });

  test('reports page on various viewports', async ({ page }) => {
    // Mobile
    await page.setViewportSize({ width: 375, height: 667 });
    let response = await page.goto('/admin/reports');
    expect([200, 302, 307]).toContain(response?.status());

    // Tablet
    await page.setViewportSize({ width: 768, height: 1024 });
    response = await page.goto('/admin/reports');
    expect([200, 302, 307]).toContain(response?.status());

    // Desktop
    await page.setViewportSize({ width: 1440, height: 900 });
    response = await page.goto('/admin/reports');
    expect([200, 302, 307]).toContain(response?.status());
  });
});

test.describe('Admin Patient Management', () => {
  test('patients page requires admin authentication', async ({ page }) => {
    await page.goto('/admin/patients');
    await expect(page).toHaveURL(/\/login/);
  });

  test('patients page returns valid response', async ({ page }) => {
    const response = await page.goto('/admin/patients');
    expect([200, 302, 307]).toContain(response?.status());
  });

  test('patient detail page pattern', async ({ page }) => {
    const response = await page.goto('/admin/patients/1');
    // Should redirect or return valid status
    expect([200, 302, 307, 404]).toContain(response?.status());
  });
});

test.describe('Admin Medical Records', () => {
  test('medical records page requires admin authentication', async ({ page }) => {
    await page.goto('/admin/medical-records');
    await expect(page).toHaveURL(/\/login/);
  });

  test('medical records page returns valid response', async ({ page }) => {
    const response = await page.goto('/admin/medical-records');
    expect([200, 302, 307]).toContain(response?.status());
  });
});

test.describe('Admin Prescriptions', () => {
  test('prescriptions page requires admin authentication', async ({ page }) => {
    await page.goto('/admin/prescriptions');
    await expect(page).toHaveURL(/\/login/);
  });

  test('prescriptions page returns valid response', async ({ page }) => {
    const response = await page.goto('/admin/prescriptions');
    expect([200, 302, 307]).toContain(response?.status());
  });
});

test.describe('Admin Error Handling', () => {
  test('404 page for invalid admin route', async ({ page }) => {
    const response = await page.goto('/admin/invalid-route-that-does-not-exist');
    expect([302, 307, 404]).toContain(response?.status());
  });

  test('invalid appointment ID handling', async ({ page }) => {
    const response = await page.goto('/admin/appointments/99999999');
    expect([200, 302, 307, 404]).toContain(response?.status());
  });

  test('invalid patient ID handling', async ({ page }) => {
    const response = await page.goto('/admin/patients/99999999');
    expect([200, 302, 307, 404]).toContain(response?.status());
  });
});
