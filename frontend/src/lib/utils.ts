import { clsx, type ClassValue } from "clsx"
import { twMerge } from "tailwind-merge"
import { ar, enUS } from 'date-fns/locale';

export function cn(...inputs: ClassValue[]) {
  return twMerge(clsx(inputs))
}

/**
 * Returns the date-fns locale object based on the current locale string.
 * Use with useLocale() from next-intl:
 *   const locale = useLocale();
 *   format(date, 'PPP', { locale: getDateLocale(locale) })
 */
export function getDateLocale(locale: string) {
  return locale === 'ar' ? ar : enUS;
}

/**
 * Returns the Intl locale string for toLocaleDateString().
 */
export function getIntlLocale(locale: string) {
  return locale === 'ar' ? 'ar-EG' : 'en-US';
}
