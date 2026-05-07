import type { MetadataRoute } from 'next';

// PWA manifest. Name is intentionally generic; Wave 3 onboarding wizard
// will let the doctor override branding strings stored in clinic_settings.
export default function manifest(): MetadataRoute.Manifest {
  return {
    name: 'Clinic Booking',
    short_name: 'Clinic',
    description: 'Book medical appointments online',
    start_url: '/',
    display: 'standalone',
    background_color: '#ffffff',
    theme_color: '#0d9488',
    orientation: 'portrait',
    icons: [
      {
        src: '/icon',
        sizes: '32x32',
        type: 'image/png',
      },
    ],
  };
}
