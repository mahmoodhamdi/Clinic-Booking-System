import { test, expect, request } from '@playwright/test';

test.describe('5) Bilingual API (ar/en)', () => {
  test('نفس endpoint يرجّع رسائل عربي ثم إنجليزي حسب Accept-Language', async () => {
    const ctxAr = await request.newContext({
      baseURL: 'http://localhost:8000',
      extraHTTPHeaders: { 'Accept-Language': 'ar', Accept: 'application/json' },
    });
    const ctxEn = await request.newContext({
      baseURL: 'http://localhost:8000',
      extraHTTPHeaders: { 'Accept-Language': 'en', Accept: 'application/json' },
    });

    const payload = {
      name: 'test',
      phone: '01125463599',
      password: 'weakpass',
      password_confirmation: 'weakpass',
    };

    const resAr = await ctxAr.post('/api/auth/register', { data: payload });
    expect(resAr.status()).toBe(422);
    const bodyAr = await resAr.json();
    expect(bodyAr.message).toContain('فشل التحقق');
    const arErrors = (bodyAr.errors?.password || []).join(' ');
    expect(arErrors).toMatch(/كلمة المرور/);

    const resEn = await ctxEn.post('/api/auth/register', {
      data: { ...payload, phone: '01125463588' },
    });
    expect(resEn.status()).toBe(422);
    const bodyEn = await resEn.json();
    expect(bodyEn.message).toContain('Validation failed');
    const enErrors = (bodyEn.errors?.password || []).join(' ');
    expect(enErrors).toMatch(/password|uppercase|lowercase/i);

    await ctxAr.dispose();
    await ctxEn.dispose();
  });
});
