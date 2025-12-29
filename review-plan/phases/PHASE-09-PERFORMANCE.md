# Phase 9: Performance Optimization

## Overview
هذه المرحلة تركز على تحسين أداء الـ Frontend.

**الأولوية:** متوسطة
**الحالة:** لم يبدأ
**التقدم:** 0%
**يعتمد على:** Phase 6

---

## Pre-requisites Checklist
- [ ] Phase 6 completed
- [ ] Frontend running: `cd frontend && npm run dev`

---

## Milestone 9.1: Next.js Image Component Implementation

### المشكلة
الـ Frontend يستخدم `<img>` بدلاً من `<Image>` من Next.js.

### الملفات المتأثرة
جميع الملفات التي تستخدم صور

### المهام

#### Task 9.1.1: Create Optimized Avatar Component
الملف: `frontend/src/components/shared/OptimizedAvatar.tsx`

```tsx
import Image from "next/image";
import { cn } from "@/lib/utils";

interface OptimizedAvatarProps {
  src: string | null;
  alt: string;
  size?: "sm" | "md" | "lg" | "xl";
  className?: string;
}

const sizes = {
  sm: 32,
  md: 40,
  lg: 56,
  xl: 96,
};

export function OptimizedAvatar({
  src,
  alt,
  size = "md",
  className,
}: OptimizedAvatarProps) {
  const dimension = sizes[size];

  if (!src) {
    return (
      <div
        className={cn(
          "rounded-full bg-gray-200 flex items-center justify-center text-gray-500 font-medium",
          className
        )}
        style={{ width: dimension, height: dimension }}
      >
        {alt.charAt(0).toUpperCase()}
      </div>
    );
  }

  return (
    <Image
      src={src}
      alt={alt}
      width={dimension}
      height={dimension}
      className={cn("rounded-full object-cover", className)}
      loading="lazy"
      placeholder="blur"
      blurDataURL="data:image/jpeg;base64,/9j/4AAQSkZJRgABAQAAAQABAAD/2wBDAAMCAgMCAgMDAwMEAwMEBQgFBQQEBQoHBwYIDAoMCwsLCgwMDQoLDAwMCw0MDAwMDA4ODg4NDw8PDw8PDw8PDw7/2wBDAQMEBAUEBQkFBQkOCwsLDg4ODg4ODg4ODg4ODg4ODg4ODg4ODg4ODg4ODg4ODg4ODg4ODg4ODg4ODg4ODg4ODg7/wAARCAABAAEDASIAAhEBAxEB/8QAFQABAQAAAAAAAAAAAAAAAAAAAAn/xAAUEAEAAAAAAAAAAAAAAAAAAAAA/8QAFQEBAQAAAAAAAAAAAAAAAAAAAAX/xAAUEQEAAAAAAAAAAAAAAAAAAAAA/9oADAMBEQCEAwEPwAB//9k="
    />
  );
}
```

#### Task 9.1.2: Update next.config.ts for Images
```typescript
images: {
  remotePatterns: [
    {
      protocol: "http",
      hostname: "localhost",
      port: "8000",
      pathname: "/storage/**",
    },
    {
      protocol: "https",
      hostname: "*.example.com",
      pathname: "/storage/**",
    },
  ],
  formats: ["image/avif", "image/webp"],
  deviceSizes: [640, 750, 828, 1080, 1200],
  imageSizes: [16, 32, 48, 64, 96, 128, 256],
},
```

#### Task 9.1.3: Replace img Tags with Image Component
ابحث في كل الملفات عن `<img` واستبدلها بـ `<Image` أو `<OptimizedAvatar`:

```bash
# Find all img tags
grep -r "<img" frontend/src --include="*.tsx"
```

### Verification
```bash
cd frontend && npm run build
# Check for image optimization in build output
```

---

## Milestone 9.2: Font Optimization

### المشكلة
الـ fonts غير محسنة للتحميل.

### الملفات المتأثرة
1. `frontend/src/app/layout.tsx`

### المهام

#### Task 9.2.1: Use next/font for Cairo
```tsx
import { Cairo } from "next/font/google";

const cairo = Cairo({
  subsets: ["arabic", "latin"],
  display: "swap",
  variable: "--font-cairo",
  preload: true,
  weight: ["400", "500", "600", "700"],
});

export default function RootLayout({
  children,
}: {
  children: React.ReactNode;
}) {
  return (
    <html lang="ar" dir="rtl" className={cairo.variable}>
      <body className={`${cairo.className} antialiased`}>
        {children}
      </body>
    </html>
  );
}
```

#### Task 9.2.2: Update Tailwind Config for Font
```javascript
// tailwind.config.js
module.exports = {
  theme: {
    extend: {
      fontFamily: {
        cairo: ['var(--font-cairo)', 'sans-serif'],
      },
    },
  },
};
```

### Verification
```bash
cd frontend && npm run build
# Check for font optimization in build output
```

---

## Milestone 9.3: Dynamic Imports for Heavy Components

### المشكلة
الـ components الثقيلة تُحمّل مع الـ initial bundle.

### الملفات المتأثرة
صفحات تحتوي على charts أو forms كبيرة

### المهام

#### Task 9.3.1: Create Lazy Loaded Components
```tsx
// frontend/src/components/lazy/index.ts
import dynamic from "next/dynamic";

export const LazyCalendar = dynamic(
  () => import("@/components/ui/calendar").then((mod) => mod.Calendar),
  {
    loading: () => <div className="h-64 bg-gray-100 animate-pulse rounded-lg" />,
    ssr: false,
  }
);

export const LazyVirtualizedList = dynamic(
  () => import("@/components/shared/VirtualizedList").then((mod) => mod.VirtualizedList),
  {
    loading: () => <div className="h-96 bg-gray-100 animate-pulse rounded-lg" />,
  }
);

export const LazyDatePicker = dynamic(
  () => import("react-day-picker").then((mod) => mod.DayPicker),
  {
    loading: () => <div className="h-64 bg-gray-100 animate-pulse rounded-lg" />,
    ssr: false,
  }
);
```

#### Task 9.3.2: Use Lazy Components in Pages
```tsx
// Example in booking page
import { LazyCalendar } from "@/components/lazy";

export default function BookingPage() {
  return (
    <div>
      <Suspense fallback={<CalendarSkeleton />}>
        <LazyCalendar
          selected={selectedDate}
          onSelect={setSelectedDate}
        />
      </Suspense>
    </div>
  );
}
```

### Verification
```bash
cd frontend && npm run build
# Check bundle sizes in build output
```

---

## Milestone 9.4: Bundle Size Optimization

### المشكلة
الـ bundle size يمكن تحسينه.

### المهام

#### Task 9.4.1: Analyze Bundle
```bash
cd frontend
npm install @next/bundle-analyzer
```

```javascript
// next.config.ts
const withBundleAnalyzer = require("@next/bundle-analyzer")({
  enabled: process.env.ANALYZE === "true",
});

module.exports = withBundleAnalyzer(nextConfig);
```

```bash
ANALYZE=true npm run build
```

#### Task 9.4.2: Optimize Imports
```typescript
// Instead of
import { format, parse, isValid } from "date-fns";

// Use
import format from "date-fns/format";
import parse from "date-fns/parse";
import isValid from "date-fns/isValid";
```

#### Task 9.4.3: Tree Shake Unused Code
```javascript
// next.config.ts
experimental: {
  optimizePackageImports: [
    "lucide-react",
    "date-fns",
    "@radix-ui/react-icons",
  ],
},
```

#### Task 9.4.4: Add Bundle Size Limits
```json
// package.json
{
  "bundlesize": [
    {
      "path": ".next/static/chunks/main-*.js",
      "maxSize": "200 kB"
    },
    {
      "path": ".next/static/chunks/pages/**/*.js",
      "maxSize": "100 kB"
    }
  ]
}
```

### Verification
```bash
cd frontend && npm run build
# Check bundle sizes meet targets
```

---

## Milestone 9.5: Caching and Prefetching

### المهام

#### Task 9.5.1: Configure React Query Caching
```typescript
// frontend/src/components/providers/index.tsx
const queryClient = new QueryClient({
  defaultOptions: {
    queries: {
      staleTime: 1000 * 60 * 5, // 5 minutes
      gcTime: 1000 * 60 * 30, // 30 minutes (was cacheTime)
      refetchOnWindowFocus: false,
      retry: 2,
      retryDelay: (attemptIndex) => Math.min(1000 * 2 ** attemptIndex, 30000),
    },
    mutations: {
      retry: 1,
    },
  },
});
```

#### Task 9.5.2: Add Link Prefetching
```tsx
// In navigation components
import Link from "next/link";

<Link href="/appointments" prefetch={true}>
  مواعيدي
</Link>
```

#### Task 9.5.3: Implement Service Worker (Optional)
```typescript
// next.config.ts (if using next-pwa)
const withPWA = require("next-pwa")({
  dest: "public",
  disable: process.env.NODE_ENV === "development",
  runtimeCaching: [
    {
      urlPattern: /^https:\/\/api\.example\.com\/.*/i,
      handler: "NetworkFirst",
      options: {
        cacheName: "api-cache",
        expiration: {
          maxEntries: 200,
          maxAgeSeconds: 60 * 60 * 24, // 24 hours
        },
      },
    },
  ],
});
```

### Verification
```bash
cd frontend && npm run build && npm start
# Check caching in browser dev tools
```

---

## Post-Phase Checklist

### Tests
- [ ] Frontend tests pass: `cd frontend && npm test`
- [ ] Build succeeds: `cd frontend && npm run build`

### Performance
- [ ] Images optimized with Next.js Image
- [ ] Fonts preloaded and subset
- [ ] Bundle size under target
- [ ] Pages load under 3 seconds

### Documentation
- [ ] Update PROGRESS.md
- [ ] Commit changes

---

## Completion Command

```bash
cd frontend && npm test && npm run build && cd .. && git add -A && git commit -m "perf(frontend): implement Phase 9 - Performance Optimization

- Implement Next.js Image component for all images
- Optimize font loading with next/font
- Add dynamic imports for heavy components
- Optimize bundle size with tree shaking
- Configure React Query caching

🤖 Generated with [Claude Code](https://claude.com/claude-code)

Co-Authored-By: Claude <noreply@anthropic.com>"
```
