<?php

namespace App\Services;

use Illuminate\Support\Facades\App;

class LocalizationService
{
    /**
     * Get current locale.
     */
    public function getCurrentLocale(): string
    {
        return App::getLocale();
    }

    /**
     * Get current text direction.
     */
    public function getDirection(): string
    {
        return $this->isRtl() ? 'rtl' : 'ltr';
    }

    /**
     * Check if current locale is RTL.
     */
    public function isRtl(): bool
    {
        $rtlLocales = config('localization.rtl_locales', ['ar', 'he', 'fa', 'ur']);
        return in_array($this->getCurrentLocale(), $rtlLocales);
    }

    /**
     * Get all supported locales.
     */
    public function getSupportedLocales(): array
    {
        return config('localization.supported', []);
    }

    /**
     * Get locale info.
     */
    public function getLocaleInfo(?string $locale = null): array
    {
        $locale = $locale ?? $this->getCurrentLocale();
        $supported = $this->getSupportedLocales();

        return $supported[$locale] ?? [
            'name' => $locale,
            'native' => $locale,
            'direction' => 'ltr',
            'flag' => '',
        ];
    }

    /**
     * Get localized label based on current locale.
     */
    public function getLabel(string $arLabel, string $enLabel): string
    {
        return $this->getCurrentLocale() === 'ar' ? $arLabel : $enLabel;
    }

    /**
     * Format date according to locale.
     */
    public function formatDate($date, ?string $format = null): string
    {
        if (!$date) {
            return '';
        }

        if (is_string($date)) {
            $date = \Carbon\Carbon::parse($date);
        }

        $format = $format ?? $this->getLocaleInfo()['date_format'] ?? 'Y-m-d';

        return $date->format($format);
    }

    /**
     * Format time according to locale.
     */
    public function formatTime($time, ?string $format = null): string
    {
        if (!$time) {
            return '';
        }

        if (is_string($time)) {
            $time = \Carbon\Carbon::parse($time);
        }

        $format = $format ?? $this->getLocaleInfo()['time_format'] ?? 'H:i';

        return $time->format($format);
    }

    /**
     * Format datetime according to locale.
     */
    public function formatDateTime($datetime, ?string $format = null): string
    {
        if (!$datetime) {
            return '';
        }

        if (is_string($datetime)) {
            $datetime = \Carbon\Carbon::parse($datetime);
        }

        $format = $format ?? $this->getLocaleInfo()['datetime_format'] ?? 'Y-m-d H:i';

        return $datetime->format($format);
    }

    /**
     * Get translation with fallback.
     */
    public function trans(string $key, array $replace = [], ?string $locale = null): string
    {
        $locale = $locale ?? $this->getCurrentLocale();

        $translation = __($key, $replace, $locale);

        // If translation not found, try fallback locale
        if ($translation === $key) {
            $fallback = config('localization.fallback', 'en');
            if ($fallback !== $locale) {
                $translation = __($key, $replace, $fallback);
            }
        }

        return $translation;
    }

    /**
     * Get response metadata with locale info.
     */
    public function getResponseMeta(): array
    {
        return [
            'locale' => $this->getCurrentLocale(),
            'direction' => $this->getDirection(),
            'is_rtl' => $this->isRtl(),
        ];
    }
}
