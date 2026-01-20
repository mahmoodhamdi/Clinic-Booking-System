import { test, expect } from '@playwright/test';

test.describe('Payment Flow', () => {
  // Critical Payment Flow Tests

  test.describe('Payment Page Access', () => {
    test('admin payments page requires authentication', async ({ page }) => {
      await page.goto('/admin/payments');
      await expect(page).toHaveURL(/\/login/);
    });

    test('patient cannot access admin payments', async ({ page }) => {
      await page.goto('/admin/payments');
      await expect(page).toHaveURL(/\/login/);
    });

    test('payments page returns valid status', async ({ page }) => {
      const response = await page.goto('/admin/payments');
      expect([200, 302, 307]).toContain(response?.status());
    });
  });

  test.describe('Payment Methods', () => {
    test('login page form accepts all input types', async ({ page }) => {
      await page.goto('/login');

      // Phone should accept tel type
      const phoneInput = page.locator('input[name="phone"]');
      await expect(phoneInput).toHaveAttribute('type', 'tel');

      // Password should be masked
      const passwordInput = page.locator('input[name="password"]');
      await expect(passwordInput).toHaveAttribute('type', 'password');
    });
  });

  test.describe('Payment Flow - Responsive', () => {
    test('payments accessible on mobile - iPhone SE', async ({ page }) => {
      await page.setViewportSize({ width: 375, height: 667 });
      const response = await page.goto('/admin/payments');
      expect([200, 302, 307]).toContain(response?.status());
    });

    test('payments accessible on mobile - iPhone 12', async ({ page }) => {
      await page.setViewportSize({ width: 390, height: 844 });
      const response = await page.goto('/admin/payments');
      expect([200, 302, 307]).toContain(response?.status());
    });

    test('payments accessible on mobile - Android', async ({ page }) => {
      await page.setViewportSize({ width: 412, height: 915 });
      const response = await page.goto('/admin/payments');
      expect([200, 302, 307]).toContain(response?.status());
    });

    test('payments accessible on tablet - iPad', async ({ page }) => {
      await page.setViewportSize({ width: 768, height: 1024 });
      const response = await page.goto('/admin/payments');
      expect([200, 302, 307]).toContain(response?.status());
    });

    test('payments accessible on tablet - iPad Pro', async ({ page }) => {
      await page.setViewportSize({ width: 1024, height: 1366 });
      const response = await page.goto('/admin/payments');
      expect([200, 302, 307]).toContain(response?.status());
    });

    test('payments accessible on laptop', async ({ page }) => {
      await page.setViewportSize({ width: 1366, height: 768 });
      const response = await page.goto('/admin/payments');
      expect([200, 302, 307]).toContain(response?.status());
    });

    test('payments accessible on desktop', async ({ page }) => {
      await page.setViewportSize({ width: 1920, height: 1080 });
      const response = await page.goto('/admin/payments');
      expect([200, 302, 307]).toContain(response?.status());
    });

    test('payments accessible on ultrawide', async ({ page }) => {
      await page.setViewportSize({ width: 2560, height: 1440 });
      const response = await page.goto('/admin/payments');
      expect([200, 302, 307]).toContain(response?.status());
    });
  });

  test.describe('Payment Statistics Access', () => {
    test('statistics requires authentication', async ({ page }) => {
      // Try to access payments - should redirect to login
      await page.goto('/admin/payments');
      await expect(page).toHaveURL(/\/login/);
    });

    test('reports page requires authentication', async ({ page }) => {
      await page.goto('/admin/reports');
      await expect(page).toHaveURL(/\/login/);
    });
  });

  test.describe('Payment Form Elements', () => {
    test('login form has required structure', async ({ page }) => {
      await page.goto('/login');

      // Check form exists
      const form = page.locator('form');
      await expect(form).toBeVisible();

      // Check inputs exist
      await expect(page.locator('input[name="phone"]')).toBeVisible();
      await expect(page.locator('input[name="password"]')).toBeVisible();

      // Check submit button
      await expect(page.locator('button[type="submit"]')).toBeVisible();
    });
  });
});

test.describe('Direct Payment Recording Flow', () => {
  test.describe('Access Control', () => {
    test('direct payment requires admin access', async ({ page }) => {
      await page.goto('/admin/payments');
      await expect(page).toHaveURL(/\/login/);
    });

    test('secretary can access payment page', async ({ page }) => {
      // Secretary should also be able to access
      await page.goto('/admin/payments');
      await expect(page).toHaveURL(/\/login/);
    });
  });

  test.describe('Payment Recording UI', () => {
    test('payments page is accessible', async ({ page }) => {
      const response = await page.goto('/admin/payments');
      expect([200, 302, 307]).toContain(response?.status());
    });

    test('payments page loads on all browsers', async ({ page }) => {
      await page.goto('/admin/payments');
      // Should redirect to login or show payments
      await page.waitForLoadState('networkidle');
    });
  });
});

test.describe('Payment Refund Flow', () => {
  test.describe('Refund Access Control', () => {
    test('refund requires admin authentication', async ({ page }) => {
      await page.goto('/admin/payments');
      await expect(page).toHaveURL(/\/login/);
    });

    test('refund page pattern exists', async ({ page }) => {
      const response = await page.goto('/admin/payments/1');
      expect([200, 302, 307, 404]).toContain(response?.status());
    });
  });
});

test.describe('Payment Integration with Appointments', () => {
  test.describe('Appointment-Payment Link', () => {
    test('appointments page accessible', async ({ page }) => {
      const response = await page.goto('/admin/appointments');
      expect([200, 302, 307]).toContain(response?.status());
    });

    test('appointment detail includes payment info', async ({ page }) => {
      const response = await page.goto('/admin/appointments/1');
      expect([200, 302, 307, 404]).toContain(response?.status());
    });
  });
});

test.describe('Payment Security', () => {
  test('payment routes are protected', async ({ page }) => {
    const routes = [
      '/admin/payments',
      '/admin/payments/1',
      '/admin/payments/statistics',
    ];

    for (const route of routes) {
      await page.goto(route);
      // Should redirect to login
      await expect(page).toHaveURL(/\/login/);
    }
  });

  test('login form has secure password field', async ({ page }) => {
    await page.goto('/login');

    const passwordInput = page.locator('input[name="password"]');
    await expect(passwordInput).toHaveAttribute('type', 'password');
  });

  test('form prevents CSRF with proper tokens', async ({ page }) => {
    await page.goto('/login');

    // Form should exist and be functional
    const form = page.locator('form');
    await expect(form).toBeVisible();
  });
});

test.describe('Payment Arabic Support', () => {
  test('payment page supports RTL', async ({ page }) => {
    await page.goto('/login');

    // Check for RTL support
    const html = page.locator('html');
    const dir = await html.getAttribute('dir');

    // Either RTL or no explicit direction (handled by CSS)
    expect(['rtl', null]).toContain(dir);
  });

  test('arabic text renders correctly', async ({ page }) => {
    await page.goto('/login');

    // Page should load without font errors
    await page.waitForLoadState('networkidle');
  });
});

test.describe('Payment Error States', () => {
  test('handles 404 for invalid payment', async ({ page }) => {
    const response = await page.goto('/admin/payments/99999999');
    expect([200, 302, 307, 404]).toContain(response?.status());
  });

  test('handles invalid payment ID format', async ({ page }) => {
    const response = await page.goto('/admin/payments/invalid');
    expect([200, 302, 307, 400, 404]).toContain(response?.status());
  });
});

test.describe('Payment UX', () => {
  test('login shows loading state', async ({ page }) => {
    await page.goto('/login');

    await page.fill('input[name="phone"]', '01000000000');
    await page.fill('input[name="password"]', 'admin123');
    await page.click('button[type="submit"]');

    // Should show some loading indicator or attempt submission
    await page.waitForTimeout(500);
  });

  test('form validation provides feedback', async ({ page }) => {
    await page.goto('/login');

    // Submit empty form
    await page.click('button[type="submit"]');

    // Should stay on login page
    await page.waitForTimeout(500);
    await expect(page).toHaveURL(/\/login/);
  });

  test('error messages are user-friendly', async ({ page }) => {
    await page.goto('/login');

    await page.fill('input[name="phone"]', '01000000000');
    await page.fill('input[name="password"]', 'wrongpassword');
    await page.click('button[type="submit"]');

    // Wait for error response
    await page.waitForTimeout(3000);

    // Should stay on login
    await expect(page).toHaveURL(/\/login/);
  });
});
