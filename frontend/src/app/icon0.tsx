import { ImageResponse } from 'next/og';

export const size = { width: 192, height: 192 };
export const contentType = 'image/png';

// 192x192 PWA icon. Required by Chrome's install prompt.
export default function Icon192() {
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
          color: '#ffffff',
          fontSize: 140,
          fontWeight: 700,
          borderRadius: 32,
        }}
      >
        ♥
      </div>
    ),
    size
  );
}
