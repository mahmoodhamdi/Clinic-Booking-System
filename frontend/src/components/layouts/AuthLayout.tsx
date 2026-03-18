'use client';

import { useTranslations } from 'next-intl';
import { Heart } from 'lucide-react';
import { LanguageSwitcher } from '@/components/shared/LanguageSwitcher';
import { ThemeToggle } from '@/components/shared/ThemeToggle';

interface AuthLayoutProps {
  children: React.ReactNode;
  title: string;
  subtitle?: React.ReactNode;
}

export function AuthLayout({ children, title, subtitle }: AuthLayoutProps) {
  const t = useTranslations('common');

  return (
    <div className="min-h-screen flex flex-col bg-gradient-subtle">
      {/* Decorative background pattern */}
      <div className="fixed inset-0 overflow-hidden pointer-events-none -z-10">
        <div className="absolute -top-24 -end-24 w-96 h-96 rounded-full bg-primary/5 blur-3xl" />
        <div className="absolute top-1/2 -start-24 w-80 h-80 rounded-full bg-info/5 blur-3xl" />
        <div className="absolute -bottom-24 end-1/3 w-72 h-72 rounded-full bg-primary/5 blur-3xl" />
      </div>

      {/* Header */}
      <header className="p-4 flex justify-between items-center animate-fade-in-down">
        <div className="flex items-center gap-2.5">
          <div className="h-9 w-9 rounded-xl bg-gradient-primary flex items-center justify-center shadow-primary">
            <Heart className="h-4.5 w-4.5 text-white" fill="white" />
          </div>
          <span className="text-lg font-bold text-foreground">{t('appName')}</span>
        </div>
        <div className="flex items-center gap-1">
          <ThemeToggle />
          <LanguageSwitcher />
        </div>
      </header>

      {/* Main Content */}
      <main className="flex-1 flex items-center justify-center p-4">
        <div className="w-full max-w-md animate-fade-in-up">
          <div className="bg-card dark:bg-card rounded-2xl shadow-xl border border-border/50 p-8 relative overflow-hidden">
            {/* Subtle top accent */}
            <div className="absolute top-0 inset-x-0 h-1 bg-gradient-primary" />

            {/* Title */}
            <div className="text-center mb-8 pt-2">
              <h2 className="text-2xl font-bold text-foreground">
                {title}
              </h2>
              {subtitle && (
                <p className="mt-2 text-muted-foreground">
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
      <footer className="p-4 text-center text-sm text-muted-foreground">
        &copy; {new Date().getFullYear()} {t('appName')}
      </footer>
    </div>
  );
}
