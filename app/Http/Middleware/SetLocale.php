<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Symfony\Component\HttpFoundation\Response;

class SetLocale
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $locale = $this->detectLocale($request);

        App::setLocale($locale);

        $response = $next($request);

        // Add locale info to response headers
        if ($response instanceof Response) {
            $response->headers->set('Content-Language', $locale);
            $response->headers->set('X-Locale', $locale);
            $response->headers->set('X-Direction', $this->getDirection($locale));
        }

        return $response;
    }

    /**
     * Detect locale from request.
     */
    protected function detectLocale(Request $request): string
    {
        $supportedLocales = array_keys(config('localization.supported', ['ar', 'en']));

        // 1. Check query parameter
        $queryParam = config('localization.query_param', 'lang');
        if ($request->has($queryParam)) {
            $locale = $request->get($queryParam);
            if (in_array($locale, $supportedLocales)) {
                return $locale;
            }
        }

        // 2. Check Accept-Language header
        $headerName = config('localization.api_header', 'Accept-Language');
        $acceptLanguage = $request->header($headerName);

        if ($acceptLanguage) {
            // Parse Accept-Language header (e.g., "ar-SA,ar;q=0.9,en;q=0.8")
            $locale = $this->parseAcceptLanguage($acceptLanguage, $supportedLocales);
            if ($locale) {
                return $locale;
            }
        }

        // 3. Check user preference (if authenticated)
        if ($request->user() && $request->user()->preferred_locale) {
            $locale = $request->user()->preferred_locale;
            if (in_array($locale, $supportedLocales)) {
                return $locale;
            }
        }

        // 4. Return default locale
        return config('localization.default', 'ar');
    }

    /**
     * Parse Accept-Language header and return best matching locale.
     */
    protected function parseAcceptLanguage(string $header, array $supportedLocales): ?string
    {
        $languages = [];

        foreach (explode(',', $header) as $part) {
            $part = trim($part);
            $quality = 1.0;

            if (str_contains($part, ';')) {
                [$locale, $q] = explode(';', $part, 2);
                $locale = trim($locale);
                if (preg_match('/q=([0-9.]+)/', $q, $matches)) {
                    $quality = (float) $matches[1];
                }
            } else {
                $locale = $part;
            }

            // Extract base language (e.g., "ar" from "ar-SA")
            $baseLocale = explode('-', $locale)[0];

            $languages[$baseLocale] = $quality;
        }

        // Sort by quality
        arsort($languages);

        // Find first matching locale
        foreach (array_keys($languages) as $locale) {
            if (in_array($locale, $supportedLocales)) {
                return $locale;
            }
        }

        return null;
    }

    /**
     * Get text direction for locale.
     */
    protected function getDirection(string $locale): string
    {
        $rtlLocales = config('localization.rtl_locales', ['ar', 'he', 'fa', 'ur']);

        return in_array($locale, $rtlLocales) ? 'rtl' : 'ltr';
    }
}
