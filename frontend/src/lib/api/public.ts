import type { ApiResponse, PublicClinicInfo } from '@/types';

// Resolves the API base URL for both server and client renders. The default
// matches the dev fallback in client.ts; production deploys must set
// NEXT_PUBLIC_API_URL.
function apiBase(): string {
  return process.env.NEXT_PUBLIC_API_URL || 'http://localhost:8000/api';
}

export async function getPublicClinicInfo(): Promise<PublicClinicInfo | null> {
  try {
    const res = await fetch(`${apiBase()}/public/clinic-info`, {
      // Refresh every 5 minutes — clinic info changes rarely and the
      // landing page is hot-pathed for SEO. Customers editing settings
      // won't wait more than 5 min to see updates on the public page.
      next: { revalidate: 300 },
    });

    if (!res.ok) return null;
    const json = (await res.json()) as ApiResponse<PublicClinicInfo>;
    return json.data ?? null;
  } catch {
    // If the backend is unreachable during build/SSR, fall back to the
    // static defaults rendered by the landing page itself.
    return null;
  }
}
