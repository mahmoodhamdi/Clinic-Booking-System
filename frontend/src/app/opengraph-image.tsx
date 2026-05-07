import { ImageResponse } from 'next/og';

export const alt = 'Clinic Booking';
export const size = { width: 1200, height: 630 };
export const contentType = 'image/png';

// Static fallback OG image. Once clinic_settings is wired into the landing
// page (W2-T1), the dynamic OG can pull clinic_name + tagline. For now this
// gives WhatsApp/Facebook/Twitter shares a real preview instead of the
// browser's default.
export default function OpenGraphImage() {
  return new ImageResponse(
    (
      <div
        style={{
          width: '100%',
          height: '100%',
          display: 'flex',
          flexDirection: 'column',
          alignItems: 'center',
          justifyContent: 'center',
          background: 'linear-gradient(135deg, #0d9488 0%, #0f766e 100%)',
          color: 'white',
          fontFamily: 'sans-serif',
        }}
      >
        <div style={{ fontSize: 96, marginBottom: 24 }}>♥</div>
        <div style={{ fontSize: 72, fontWeight: 700, marginBottom: 16 }}>Clinic Booking</div>
        <div style={{ fontSize: 32, opacity: 0.9 }}>Book your medical appointments online</div>
      </div>
    ),
    size
  );
}
