import type { MetadataRoute } from 'next';

// Resolve the public site origin for the sitemap reference. NEXT_PUBLIC_APP_URL
// is the canonical front-end URL (set per-environment in .env.local /
// vercel project vars). Falls back to localhost for dev so the route still
// renders without crashing.
function siteUrl(): string {
  return (process.env.NEXT_PUBLIC_APP_URL || 'http://localhost:3000').replace(/\/$/, '');
}

export default function robots(): MetadataRoute.Robots {
  return {
    rules: [
      {
        userAgent: '*',
        allow: ['/'],
        // Authenticated areas should not be indexed — they only render after
        // login anyway, but spelling it out blocks accidental link leaks.
        disallow: [
          '/admin',
          '/admin/',
          '/dashboard',
          '/appointments',
          '/medical-records',
          '/prescriptions',
          '/profile',
          '/notifications',
          '/book',
          '/change-password',
          '/forgot-password',
          '/reset-password',
          '/verify-otp',
        ],
      },
    ],
    sitemap: `${siteUrl()}/sitemap.xml`,
  };
}
