import type { MetadataRoute } from 'next';

function siteUrl(): string {
  return (process.env.NEXT_PUBLIC_APP_URL || 'http://localhost:3000').replace(/\/$/, '');
}

// Single-doctor clinic = small public surface. Only the landing,
// register, and login pages are intended for indexing. Patient/admin
// areas are robots-disallowed (see robots.ts).
export default function sitemap(): MetadataRoute.Sitemap {
  const base = siteUrl();
  const lastModified = new Date();

  return [
    {
      url: `${base}/`,
      lastModified,
      changeFrequency: 'weekly',
      priority: 1.0,
    },
    {
      url: `${base}/register`,
      lastModified,
      changeFrequency: 'monthly',
      priority: 0.5,
    },
    {
      url: `${base}/login`,
      lastModified,
      changeFrequency: 'monthly',
      priority: 0.3,
    },
  ];
}
