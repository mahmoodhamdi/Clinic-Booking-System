import { ImageResponse } from 'next/og';

export const size = { width: 32, height: 32 };
export const contentType = 'image/png';

// Lightweight SVG-as-PNG favicon: a teal rounded square with a white heart
// glyph, matching the in-app brand color and the Heart icon used in
// AuthLayout. Replaced by clinic logo upload in the Wave 3 onboarding wizard.
export default function Icon() {
  return new ImageResponse(
    (
      <div
        style={{
          width: '100%',
          height: '100%',
          display: 'flex',
          alignItems: 'center',
          justifyContent: 'center',
          background: '#0d9488',
          borderRadius: 6,
          color: '#ffffff',
          fontSize: 22,
          fontWeight: 700,
        }}
      >
        ♥
      </div>
    ),
    size
  );
}
