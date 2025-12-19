<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateSettingsRequest;
use App\Http\Resources\ClinicSettingResource;
use App\Models\ClinicSetting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ClinicSettingController extends Controller
{
    /**
     * Get clinic settings.
     */
    public function show(): JsonResponse
    {
        $settings = ClinicSetting::getInstance();

        return response()->json([
            'success' => true,
            'data' => new ClinicSettingResource($settings),
        ]);
    }

    /**
     * Update clinic settings.
     */
    public function update(UpdateSettingsRequest $request): JsonResponse
    {
        $settings = ClinicSetting::getInstance();
        $settings->update($request->validated());

        return response()->json([
            'success' => true,
            'message' => 'تم تحديث إعدادات العيادة بنجاح.',
            'data' => new ClinicSettingResource($settings->fresh()),
        ]);
    }

    /**
     * Upload clinic logo.
     */
    public function uploadLogo(Request $request): JsonResponse
    {
        $request->validate([
            'logo' => ['required', 'image', 'mimes:jpeg,png,jpg,gif,svg', 'max:2048'],
        ]);

        $settings = ClinicSetting::getInstance();

        // Delete old logo if exists
        if ($settings->logo) {
            Storage::disk('public')->delete($settings->logo);
        }

        $path = $request->file('logo')->store('logos', 'public');
        $settings->update(['logo' => $path]);

        return response()->json([
            'success' => true,
            'message' => 'تم تحديث شعار العيادة بنجاح.',
            'data' => new ClinicSettingResource($settings->fresh()),
        ]);
    }

    /**
     * Delete clinic logo.
     */
    public function deleteLogo(): JsonResponse
    {
        $settings = ClinicSetting::getInstance();

        if ($settings->logo) {
            Storage::disk('public')->delete($settings->logo);
            $settings->update(['logo' => null]);
        }

        return response()->json([
            'success' => true,
            'message' => 'تم حذف شعار العيادة بنجاح.',
            'data' => new ClinicSettingResource($settings->fresh()),
        ]);
    }
}
