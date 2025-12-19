<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\LocalizationService;
use Illuminate\Http\JsonResponse;

class LocaleController extends Controller
{
    public function __construct(
        private LocalizationService $localizationService
    ) {}

    /**
     * Get all supported locales.
     */
    public function index(): JsonResponse
    {
        $locales = $this->localizationService->getSupportedLocales();

        $data = [];
        foreach ($locales as $code => $info) {
            $data[] = [
                'code' => $code,
                'name' => $info['name'],
                'native' => $info['native'],
                'direction' => $info['direction'],
                'flag' => $info['flag'],
                'is_current' => $code === $this->localizationService->getCurrentLocale(),
            ];
        }

        return response()->json([
            'data' => $data,
            'meta' => $this->localizationService->getResponseMeta(),
        ]);
    }

    /**
     * Get current locale info.
     */
    public function current(): JsonResponse
    {
        $locale = $this->localizationService->getCurrentLocale();
        $info = $this->localizationService->getLocaleInfo();

        return response()->json([
            'data' => [
                'code' => $locale,
                'name' => $info['name'],
                'native' => $info['native'],
                'direction' => $info['direction'],
                'flag' => $info['flag'],
                'is_rtl' => $this->localizationService->isRtl(),
                'date_format' => $info['date_format'] ?? 'Y-m-d',
                'time_format' => $info['time_format'] ?? 'H:i',
                'datetime_format' => $info['datetime_format'] ?? 'Y-m-d H:i',
            ],
        ]);
    }

    /**
     * Get translations for specific keys.
     */
    public function translations(): JsonResponse
    {
        $locale = $this->localizationService->getCurrentLocale();

        // Load translations from JSON file
        $translationsPath = lang_path("{$locale}.json");

        if (file_exists($translationsPath)) {
            $translations = json_decode(file_get_contents($translationsPath), true);
        } else {
            $translations = [];
        }

        return response()->json([
            'data' => $translations,
            'meta' => $this->localizationService->getResponseMeta(),
        ]);
    }
}
