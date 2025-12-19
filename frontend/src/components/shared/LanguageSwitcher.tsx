'use client';

import { useTransition } from 'react';
import { Languages } from 'lucide-react';
import { Button } from '@/components/ui/button';
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { locales, localeNames, Locale } from '@/i18n/config';

export function LanguageSwitcher() {
  const [isPending, startTransition] = useTransition();

  const switchLocale = (locale: Locale) => {
    startTransition(() => {
      // Set cookie and reload
      document.cookie = `locale=${locale};path=/;max-age=31536000`;
      localStorage.setItem('locale', locale);
      window.location.reload();
    });
  };

  return (
    <DropdownMenu>
      <DropdownMenuTrigger asChild>
        <Button variant="ghost" size="icon" disabled={isPending}>
          <Languages className="h-5 w-5" />
          <span className="sr-only">Switch language</span>
        </Button>
      </DropdownMenuTrigger>
      <DropdownMenuContent align="end">
        {locales.map((locale) => (
          <DropdownMenuItem
            key={locale}
            onClick={() => switchLocale(locale)}
            className="cursor-pointer"
          >
            {localeNames[locale]}
          </DropdownMenuItem>
        ))}
      </DropdownMenuContent>
    </DropdownMenu>
  );
}
