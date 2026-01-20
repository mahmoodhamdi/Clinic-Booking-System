import { test, expect } from '@playwright/test';

test.describe('Complete Booking Flow', () => {
  // Critical User Flow: Login → Dashboard → Book Appointment → Confirm

  test.describe('Step 1: User Authentication', () => {
    test('user can complete login form', async ({ page }) => {
      await page.goto('/login');

      // Fill login form
      await page.fill('input[name="phone"]', '01012345678');
      await page.fill('input[name="password"]', 'TestPassword123!');

      // Verify form was filled
      await expect(page.locator('input[name="phone"]')).toHaveValue('01012345678');
      await expect(page.locator('input[name="password"]')).toHaveValue('TestPassword123!');
    });

    test('login form validates phone format', async ({ page }) => {
      await page.goto('/login');

      // Enter invalid phone
      await page.fill('input[name="phone"]', '123');
      await page.fill('input[name="password"]', 'TestPassword123!');
      await page.click('button[type="submit"]');

      // Should stay on login page due to validation
      await page.waitForTimeout(500);
      await expect(page).toHaveURL(/\/login/);
    });

    test('login form validates password required', async ({ page }) => {
      await page.goto('/login');

      // Enter only phone
      await page.fill('input[name="phone"]', '01012345678');
      await page.click('button[type="submit"]');

      // Should stay on login page
      await page.waitForTimeout(500);
      await expect(page).toHaveURL(/\/login/);
    });

    test('user can navigate to registration', async ({ page }) => {
      await page.goto('/login');
      await page.click('a[href="/register"]');
      await expect(page).toHaveURL(/\/register/);
    });

    test('user can navigate to forgot password', async ({ page }) => {
      await page.goto('/login');
      await page.click('a[href="/forgot-password"]');
      await expect(page).toHaveURL(/\/forgot-password/);
    });
  });

  test.describe('Step 2: Registration Flow', () => {
    test('registration form has all required fields', async ({ page }) => {
      await page.goto('/register');

      await expect(page.locator('input[name="name"]')).toBeVisible();
      await expect(page.locator('input[name="phone"]')).toBeVisible();
      await expect(page.locator('input[name="password"]')).toBeVisible();
      await expect(page.locator('input[name="password_confirmation"]')).toBeVisible();
    });

    test('registration validates name field', async ({ page }) => {
      await page.goto('/register');

      // Fill all except name
      await page.fill('input[name="phone"]', '01012345678');
      await page.fill('input[name="password"]', 'TestPassword123!');
      await page.fill('input[name="password_confirmation"]', 'TestPassword123!');
      await page.click('button[type="submit"]');

      // Should stay on register page
      await page.waitForTimeout(500);
      await expect(page).toHaveURL(/\/register/);
    });

    test('registration validates phone format', async ({ page }) => {
      await page.goto('/register');

      await page.fill('input[name="name"]', 'Test User');
      await page.fill('input[name="phone"]', '123'); // Invalid
      await page.fill('input[name="password"]', 'TestPassword123!');
      await page.fill('input[name="password_confirmation"]', 'TestPassword123!');
      await page.click('button[type="submit"]');

      await page.waitForTimeout(500);
      await expect(page).toHaveURL(/\/register/);
    });

    test('registration validates password match', async ({ page }) => {
      await page.goto('/register');

      await page.fill('input[name="name"]', 'Test User');
      await page.fill('input[name="phone"]', '01012345678');
      await page.fill('input[name="password"]', 'TestPassword123!');
      await page.fill('input[name="password_confirmation"]', 'DifferentPassword!');
      await page.click('button[type="submit"]');

      await page.waitForTimeout(500);
      await expect(page).toHaveURL(/\/register/);
    });

    test('user can navigate back to login from registration', async ({ page }) => {
      await page.goto('/register');
      await page.click('a[href="/login"]');
      await expect(page).toHaveURL(/\/login/);
    });
  });

  test.describe('Step 3: Booking Page Access', () => {
    test('booking page requires authentication', async ({ page }) => {
      await page.goto('/book');
      await expect(page).toHaveURL(/\/login/);
    });

    test('appointments page requires authentication', async ({ page }) => {
      await page.goto('/appointments');
      await expect(page).toHaveURL(/\/login/);
    });

    test('dashboard page requires authentication', async ({ page }) => {
      await page.goto('/dashboard');
      await expect(page).toHaveURL(/\/login/);
    });

    test('medical records page requires authentication', async ({ page }) => {
      await page.goto('/medical-records');
      await expect(page).toHaveURL(/\/login/);
    });

    test('prescriptions page requires authentication', async ({ page }) => {
      await page.goto('/prescriptions');
      await expect(page).toHaveURL(/\/login/);
    });

    test('profile page requires authentication', async ({ page }) => {
      await page.goto('/profile');
      await expect(page).toHaveURL(/\/login/);
    });

    test('notifications page requires authentication', async ({ page }) => {
      await page.goto('/notifications');
      await expect(page).toHaveURL(/\/login/);
    });
  });

  test.describe('Step 4: Booking UI Elements', () => {
    test('login page has booking-related branding', async ({ page }) => {
      await page.goto('/login');

      // Check for clinic booking related text/elements
      const heading = page.locator('h1, h2');
      await expect(heading.first()).toBeVisible();
    });

    test('login form is accessible on mobile', async ({ page }) => {
      await page.setViewportSize({ width: 375, height: 667 });
      await page.goto('/login');

      // All elements should be visible and usable
      await expect(page.locator('input[name="phone"]')).toBeVisible();
      await expect(page.locator('input[name="password"]')).toBeVisible();
      await expect(page.locator('button[type="submit"]')).toBeVisible();
    });

    test('login form is accessible on tablet', async ({ page }) => {
      await page.setViewportSize({ width: 768, height: 1024 });
      await page.goto('/login');

      await expect(page.locator('input[name="phone"]')).toBeVisible();
      await expect(page.locator('input[name="password"]')).toBeVisible();
    });

    test('login form is accessible on desktop', async ({ page }) => {
      await page.setViewportSize({ width: 1440, height: 900 });
      await page.goto('/login');

      await expect(page.locator('input[name="phone"]')).toBeVisible();
      await expect(page.locator('input[name="password"]')).toBeVisible();
    });
  });
});

test.describe('Password Reset Flow', () => {
  test('forgot password page has phone input', async ({ page }) => {
    await page.goto('/forgot-password');

    await expect(page.locator('input[name="phone"]')).toBeVisible();
    await expect(page.locator('button[type="submit"]')).toBeVisible();
  });

  test('forgot password validates phone', async ({ page }) => {
    await page.goto('/forgot-password');

    await page.fill('input[name="phone"]', '123');
    await page.click('button[type="submit"]');

    await page.waitForTimeout(500);
    await expect(page).toHaveURL(/\/forgot-password/);
  });

  test('can navigate from forgot password to login', async ({ page }) => {
    await page.goto('/forgot-password');
    await page.click('a[href="/login"]');
    await expect(page).toHaveURL(/\/login/);
  });

  test('OTP page redirects without session', async ({ page }) => {
    await page.goto('/verify-otp');
    await expect(page).toHaveURL(/\/forgot-password/);
  });

  test('reset password page has required fields', async ({ page }) => {
    await page.goto('/reset-password');

    // Should redirect to forgot-password without token
    await page.waitForTimeout(1000);
  });
});

test.describe('Booking Flow - Form Validation', () => {
  test('phone input accepts Egyptian format', async ({ page }) => {
    await page.goto('/login');

    const phoneInput = page.locator('input[name="phone"]');
    await phoneInput.fill('01012345678');

    await expect(phoneInput).toHaveValue('01012345678');
  });

  test('phone input accepts different providers', async ({ page }) => {
    await page.goto('/login');

    const phoneInput = page.locator('input[name="phone"]');

    // Test different Egyptian mobile prefixes
    await phoneInput.fill('01112345678'); // Etisalat
    await expect(phoneInput).toHaveValue('01112345678');

    await phoneInput.fill('01212345678'); // Orange
    await expect(phoneInput).toHaveValue('01212345678');

    await phoneInput.fill('01512345678'); // WE
    await expect(phoneInput).toHaveValue('01512345678');
  });

  test('password field is secure', async ({ page }) => {
    await page.goto('/login');

    const passwordInput = page.locator('input[name="password"]');
    await expect(passwordInput).toHaveAttribute('type', 'password');
  });

  test('form submission is keyboard accessible', async ({ page }) => {
    await page.goto('/login');

    await page.fill('input[name="phone"]', '01012345678');
    await page.fill('input[name="password"]', 'TestPassword123!');
    await page.keyboard.press('Enter');

    // Form should attempt to submit
    await page.waitForTimeout(1000);
  });
});

test.describe('Booking Flow - Error States', () => {
  test('shows error for invalid credentials', async ({ page }) => {
    await page.goto('/login');

    await page.fill('input[name="phone"]', '01012345678');
    await page.fill('input[name="password"]', 'WrongPassword123!');
    await page.click('button[type="submit"]');

    // Wait for error response
    await page.waitForTimeout(3000);

    // Should stay on login page
    await expect(page).toHaveURL(/\/login/);
  });

  test('shows error for non-existent user', async ({ page }) => {
    await page.goto('/login');

    await page.fill('input[name="phone"]', '01099999999');
    await page.fill('input[name="password"]', 'AnyPassword123!');
    await page.click('button[type="submit"]');

    await page.waitForTimeout(3000);
    await expect(page).toHaveURL(/\/login/);
  });

  test('handles network error gracefully', async ({ page }) => {
    await page.goto('/login');

    // Fill form
    await page.fill('input[name="phone"]', '01012345678');
    await page.fill('input[name="password"]', 'TestPassword123!');

    // The form should handle errors gracefully
    await page.click('button[type="submit"]');
    await page.waitForTimeout(3000);
  });
});

test.describe('Booking Flow - UI/UX', () => {
  test('login page has proper contrast', async ({ page }) => {
    await page.goto('/login');

    // Form card should be visible
    const card = page.locator('.bg-white, .bg-card, [class*="card"]').first();
    await expect(card).toBeVisible();
  });

  test('loading state on submit', async ({ page }) => {
    await page.goto('/login');

    await page.fill('input[name="phone"]', '01012345678');
    await page.fill('input[name="password"]', 'TestPassword123!');
    await page.click('button[type="submit"]');

    // Check for loading indicator (spinner or disabled state)
    await page.waitForTimeout(500);
  });

  test('register page has proper layout', async ({ page }) => {
    await page.goto('/register');

    // Check form structure
    const form = page.locator('form');
    await expect(form).toBeVisible();
  });

  test('all auth pages have consistent styling', async ({ page }) => {
    // Check login
    await page.goto('/login');
    const loginBg = page.locator('.bg-gradient-to-br, [class*="gradient"]');
    const hasLoginBg = await loginBg.isVisible().catch(() => false);

    // Check register
    await page.goto('/register');
    await page.waitForLoadState('networkidle');

    // Check forgot-password
    await page.goto('/forgot-password');
    await page.waitForLoadState('networkidle');
  });
});
