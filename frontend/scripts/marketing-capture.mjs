/**
 * Marketing capture script — produces 8 screenshots + a walkthrough video.
 *
 * Uses Playwright with the system Chrome (channel: 'chrome') so it works on
 * Ubuntu 26.04 where Playwright's bundled chromium build isn't yet supported.
 *
 * Run from the frontend dir (so @playwright/test resolves):
 *   ADMIN_PHONE=01000000000 ADMIN_PASS=admin123 \
 *     node scripts/marketing-capture.mjs
 *
 * Assumes both servers are running:
 *   - backend  on http://127.0.0.1:8000
 *   - frontend on http://127.0.0.1:3001
 */
import { chromium } from '@playwright/test';
import path from 'node:path';
import fs from 'node:fs/promises';
import { fileURLToPath } from 'node:url';

const __dirname = path.dirname(fileURLToPath(import.meta.url));
const projectRoot = path.resolve(__dirname, '..', '..');
const shotsDir = path.join(projectRoot, 'marketing', 'screenshots');
const videosDir = path.join(projectRoot, 'marketing', 'videos');

const BASE = process.env.BASE_URL || 'http://127.0.0.1:3001';
const ADMIN_PHONE = process.env.ADMIN_PHONE || '01000000000';
const ADMIN_PASS = process.env.ADMIN_PASS || 'admin123';

await fs.mkdir(shotsDir, { recursive: true });
await fs.mkdir(videosDir, { recursive: true });

const viewports = {
  desktop: { width: 1920, height: 1080 },
  tablet:  { width: 1024, height: 768  },
  mobile:  { width: 390,  height: 844  },
};

async function loginAsAdmin(page) {
  await page.goto(`${BASE}/login`, { waitUntil: 'domcontentloaded' });
  await page.waitForTimeout(1000);
  const phoneInput = page.locator('input[name="phone"], input[type="tel"], input[placeholder*="01"]').first();
  await phoneInput.fill(ADMIN_PHONE);
  const passInput = page.locator('input[type="password"]').first();
  await passInput.fill(ADMIN_PASS);
  const submit = page.locator('button[type="submit"]').first();
  await submit.click();
  await Promise.race([
    page.waitForURL(/\/(admin|patient|dashboard|verify-otp|reset)/, { timeout: 15000 }).catch(() => null),
    page.waitForTimeout(8000),
  ]);
}

async function shot(page, name) {
  const file = path.join(shotsDir, `${name}.png`);
  await page.screenshot({ path: file, fullPage: false });
  const rel = path.relative(projectRoot, file);
  console.log(`  ✓ ${rel}`);
}

async function captureScreenshots(browser) {
  console.log('\n=== SCREENSHOTS ===');

  // ============= DESKTOP (3) =============
  console.log('\n[desktop 1920x1080]');
  {
    const ctx = await browser.newContext({ viewport: viewports.desktop, locale: 'ar-EG' });
    const page = await ctx.newPage();

    await page.goto(`${BASE}/login`, { waitUntil: 'networkidle' }).catch(() => {});
    await page.waitForTimeout(1500);
    await shot(page, '01-desktop-login');

    await loginAsAdmin(page);

    for (const url of [`${BASE}/admin/dashboard`, `${BASE}/dashboard`]) {
      await page.goto(url, { waitUntil: 'networkidle' }).catch(() => {});
      await page.waitForTimeout(2000);
      if (!page.url().includes('/login')) break;
    }
    await shot(page, '02-desktop-admin-dashboard');

    await page.goto(`${BASE}/admin/appointments`, { waitUntil: 'networkidle' }).catch(() => {});
    await page.waitForTimeout(2000);
    await shot(page, '03-desktop-admin-appointments');

    await ctx.close();
  }

  // ============= TABLET (2) =============
  console.log('\n[tablet 1024x768]');
  {
    const ctx = await browser.newContext({ viewport: viewports.tablet, locale: 'ar-EG' });
    const page = await ctx.newPage();
    await loginAsAdmin(page);

    await page.goto(`${BASE}/admin/patients`, { waitUntil: 'networkidle' }).catch(() => {});
    await page.waitForTimeout(2000);
    await shot(page, '04-tablet-admin-patients');

    await page.goto(`${BASE}/admin/medical-records`, { waitUntil: 'networkidle' }).catch(() => {});
    await page.waitForTimeout(2000);
    await shot(page, '05-tablet-admin-medical-records');

    await ctx.close();
  }

  // ============= MOBILE (3) =============
  console.log('\n[mobile 390x844]');
  {
    const ctx = await browser.newContext({
      viewport: viewports.mobile,
      locale: 'ar-EG',
      isMobile: true,
      hasTouch: true,
      userAgent: 'Mozilla/5.0 (iPhone; CPU iPhone OS 17_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.0 Mobile/15E148 Safari/604.1',
    });
    const page = await ctx.newPage();

    await page.goto(`${BASE}/login`, { waitUntil: 'networkidle' }).catch(() => {});
    await page.waitForTimeout(1500);
    await shot(page, '06-mobile-login');

    await page.goto(`${BASE}/register`, { waitUntil: 'networkidle' }).catch(() => {});
    await page.waitForTimeout(1500);
    await shot(page, '07-mobile-register');

    await loginAsAdmin(page);
    for (const url of [`${BASE}/admin/dashboard`, `${BASE}/dashboard`]) {
      await page.goto(url, { waitUntil: 'networkidle' }).catch(() => {});
      await page.waitForTimeout(2000);
      if (!page.url().includes('/login')) break;
    }
    await shot(page, '08-mobile-admin-dashboard');

    await ctx.close();
  }
}

async function recordWalkthrough(browser) {
  console.log('\n=== VIDEO walkthrough ===');
  const ctx = await browser.newContext({
    viewport: viewports.desktop,
    locale: 'ar-EG',
    recordVideo: {
      dir: videosDir,
      size: { width: 1920, height: 1080 },
    },
  });
  const page = await ctx.newPage();

  await page.goto(`${BASE}/login`, { waitUntil: 'networkidle' }).catch(() => {});
  await page.waitForTimeout(2500);

  const phoneInput = page.locator('input[name="phone"], input[type="tel"]').first();
  await phoneInput.click();
  await phoneInput.type(ADMIN_PHONE, { delay: 90 });
  const passInput = page.locator('input[type="password"]').first();
  await passInput.click();
  await passInput.type(ADMIN_PASS, { delay: 90 });
  await page.waitForTimeout(800);
  await page.locator('button[type="submit"]').first().click();
  await page.waitForTimeout(3500);

  for (const url of [`${BASE}/admin/dashboard`, `${BASE}/dashboard`]) {
    await page.goto(url, { waitUntil: 'networkidle' }).catch(() => {});
    await page.waitForTimeout(3500);
    if (!page.url().includes('/login')) break;
  }

  await page.goto(`${BASE}/admin/appointments`, { waitUntil: 'networkidle' }).catch(() => {});
  await page.waitForTimeout(4500);

  await page.goto(`${BASE}/admin/patients`, { waitUntil: 'networkidle' }).catch(() => {});
  await page.waitForTimeout(4500);

  await page.goto(`${BASE}/admin/medical-records`, { waitUntil: 'networkidle' }).catch(() => {});
  await page.waitForTimeout(4500);

  await page.goto(`${BASE}/admin/prescriptions`, { waitUntil: 'networkidle' }).catch(() => {});
  await page.waitForTimeout(4000);

  await page.goto(`${BASE}/admin/settings`, { waitUntil: 'networkidle' }).catch(() => {});
  await page.waitForTimeout(3000);

  await ctx.close();
  console.log('  + walkthrough.webm in marketing/videos/');
}

console.log(`Capturing for ${BASE}`);
const browser = await chromium.launch({
  channel: 'chrome',
  headless: true,
});
try {
  await captureScreenshots(browser);
  await recordWalkthrough(browser);
} finally {
  await browser.close();
}
console.log('\nDone.');
