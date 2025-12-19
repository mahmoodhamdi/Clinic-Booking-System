'use client';

import { useTranslations } from 'next-intl';
import { LanguageSwitcher } from '@/components/shared/LanguageSwitcher';

interface AuthLayoutProps {
  children: React.ReactNode;
  title: string;
  subtitle?: string;
}

export function AuthLayout({ children, title, subtitle }: AuthLayoutProps) {
  const t = useTranslations('common');

  return (
    <div className="min-h-screen flex flex-col bg-gradient-to-br from-blue-50 to-indigo-100 dark:from-gray-900 dark:to-gray-800">
      {/* Header */}
      <header className="p-4 flex justify-between items-center">
        <h1 className="text-xl font-bold text-primary">{t('appName')}</h1>
        <LanguageSwitcher />
      </header>

      {/* Main Content */}
      <main className="flex-1 flex items-center justify-center p-4">
        <div className="w-full max-w-md">
          <div className="bg-white dark:bg-gray-800 rounded-2xl shadow-xl p-8">
            {/* Title */}
            <div className="text-center mb-8">
              <h2 className="text-2xl font-bold text-gray-900 dark:text-white">
                {title}
              </h2>
              {subtitle && (
                <p className="mt-2 text-gray-600 dark:text-gray-400">
                  {subtitle}
                </p>
              )}
            </div>

            {/* Form Content */}
            {children}
          </div>
        </div>
      </main>

      {/* Footer */}
      <footer className="p-4 text-center text-sm text-gray-500">
        © {new Date().getFullYear()} {t('appName')}
      </footer>
    </div>
  );
}
