import type { Metadata } from 'next';
import { Cairo } from 'next/font/google';
import { NextIntlClientProvider } from 'next-intl';
import { getLocale, getMessages } from 'next-intl/server';
import { localeDirection, Locale } from '@/i18n/config';
import { Providers } from '@/components/providers';
import './globals.css';

const cairo = Cairo({
  subsets: ['arabic', 'latin'],
  display: 'swap',
  variable: '--font-cairo',
  weight: ['400', '500', '600', '700'],
});

export const metadata: Metadata = {
  title: {
    default: 'Clinic Booking',
    template: '%s | Clinic Booking',
  },
  description: 'Book your medical appointments online',
  applicationName: 'Clinic Booking',
  formatDetection: {
    telephone: true,
    email: true,
    address: true,
  },
  openGraph: {
    type: 'website',
    siteName: 'Clinic Booking',
    title: 'Clinic Booking',
    description: 'Book your medical appointments online',
  },
  twitter: {
    card: 'summary_large_image',
    title: 'Clinic Booking',
    description: 'Book your medical appointments online',
  },
};

export default async function RootLayout({
  children,
}: Readonly<{
  children: React.ReactNode;
}>) {
  const locale = (await getLocale()) as Locale;
  const messages = await getMessages();
  const direction = localeDirection[locale];

  return (
    <html lang={locale} dir={direction} suppressHydrationWarning>
      <body className={`${cairo.variable} font-sans antialiased`}>
        <NextIntlClientProvider messages={messages}>
          <Providers>{children}</Providers>
        </NextIntlClientProvider>
      </body>
    </html>
  );
}
