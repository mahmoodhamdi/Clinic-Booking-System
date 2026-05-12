import type { NextConfig } from "next";
import createNextIntlPlugin from 'next-intl/plugin';

const withNextIntl = createNextIntlPlugin('./src/i18n/request.ts');

// Resolve the backend origin from NEXT_PUBLIC_API_URL so CSP doesn't ship
// "localhost:8000" to production. Falls back to the dev default if unset
// (matches lib/api/client.ts). Strips the trailing "/api" path to get the
// origin for img-src / connect-src directives.
function apiOrigin(): string {
  const raw = process.env.NEXT_PUBLIC_API_URL || 'http://localhost:8000/api';
  try {
    return new URL(raw).origin;
  } catch {
    return 'http://localhost:8000';
  }
}

const isProd = process.env.NODE_ENV === 'production';
const backendOrigin = apiOrigin();
const imgSources = isProd
  ? `'self' data: blob: ${backendOrigin}`
  : `'self' data: blob: ${backendOrigin} http://localhost:* https:`;
const connectSources = isProd
  ? `'self' ${backendOrigin}`
  : `'self' ${backendOrigin} http://localhost:* ws://localhost:*`;

const nextConfig: NextConfig = {
  // Enable standalone output for Docker
  output: 'standalone',

  // Image optimization. remotePatterns derived from NEXT_PUBLIC_API_URL so
  // production builds whitelist the actual backend, not localhost.
  images: {
    remotePatterns: (() => {
      const u = new URL(backendOrigin);
      const patterns: Array<{ protocol: 'http' | 'https'; hostname: string; port?: string; pathname: string }> = [
        {
          protocol: u.protocol === 'https:' ? 'https' : 'http',
          hostname: u.hostname,
          ...(u.port ? { port: u.port } : {}),
          pathname: '/storage/**',
        },
      ];
      if (!isProd) {
        // Keep alternate dev ports working (composer dev port 8000, vite 9000).
        patterns.push(
          { protocol: 'http', hostname: 'localhost', port: '8000', pathname: '/storage/**' },
          { protocol: 'http', hostname: 'localhost', port: '9000', pathname: '/storage/**' },
        );
      }
      return patterns;
    })(),
    formats: ['image/avif', 'image/webp'],
    deviceSizes: [640, 750, 828, 1080, 1200],
    imageSizes: [16, 32, 48, 64, 96, 128, 256],
  },

  // Compiler optimizations
  compiler: {
    removeConsole: process.env.NODE_ENV === 'production',
  },

  // Experimental features
  experimental: {
    optimizePackageImports: ['lucide-react', 'date-fns'],
  },

  // Headers for security
  async headers() {
    return [
      {
        source: '/:path*',
        headers: [
          {
            key: 'X-DNS-Prefetch-Control',
            value: 'on',
          },
          {
            key: 'Strict-Transport-Security',
            value: 'max-age=31536000; includeSubDomains',
          },
          {
            key: 'X-Frame-Options',
            value: 'SAMEORIGIN',
          },
          {
            key: 'X-Content-Type-Options',
            value: 'nosniff',
          },
          {
            key: 'X-XSS-Protection',
            value: '1; mode=block',
          },
          {
            key: 'Referrer-Policy',
            value: 'strict-origin-when-cross-origin',
          },
          {
            key: 'Permissions-Policy',
            value: 'camera=(), microphone=(), geolocation=()',
          },
          {
            // Next.js 16 emits inline <script> tags for hydration and route
            // streaming; under a strict 'script-src self' those get blocked
            // and the app falls back to native form posts. We allow
            // 'unsafe-inline' for scripts (and styles, which the Tailwind
            // styled-jsx runtime needs) to keep the app functional. A future
            // wave can swap this for nonce-based CSP via middleware.
            key: 'Content-Security-Policy',
            value: [
              "default-src 'self'",
              process.env.NODE_ENV === 'production'
                ? "script-src 'self' 'unsafe-inline'"
                : "script-src 'self' 'unsafe-inline' 'unsafe-eval'",
              "style-src 'self' 'unsafe-inline' fonts.googleapis.com",
              "font-src 'self' fonts.gstatic.com",
              `img-src ${imgSources}`,
              `connect-src ${connectSources}`,
              "frame-ancestors 'none'",
              "base-uri 'self'",
              "form-action 'self'",
            ].join('; '),
          },
        ],
      },
    ];
  },
};

export default withNextIntl(nextConfig);
