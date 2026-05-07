import { ImageResponse } from 'next/og';

export const size = { width: 512, height: 512 };
export const contentType = 'image/png';

// 512x512 PWA icon. Required by both Chrome and the Web App Manifest spec
// for a fully installable progressive web app.
export default function Icon512() {
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
          fontSize: 380,
          fontWeight: 700,
          borderRadius: 96,
        }}
      >
        ♥
      </div>
    ),
    size
  );
}
