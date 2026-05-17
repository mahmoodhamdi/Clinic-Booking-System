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
            'message' => __('messages.settings.updated'),
            'data' => new ClinicSettingResource($settings->fresh()),
        ]);
    }

    /**
     * Upload clinic logo.
     */
    public function uploadLogo(Request $request): JsonResponse
    {
        $request->validate([
            'logo' => [
                'required',
                'image',
                'mimes:jpeg,png,jpg',
                'mimetypes:image/jpeg,image/png',
                'max:2048',
                'dimensions:min_width=50,min_height=50,max_width=2000,max_height=2000',
            ],
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
            'message' => __('messages.settings.logo_uploaded'),
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
            'message' => __('messages.settings.logo_deleted'),
            'data' => new ClinicSettingResource($settings->fresh()),
        ]);
    }

    public function uploadHeroImage(Request $request): JsonResponse
    {
        $request->validate([
            'hero_image' => [
                'required',
                'image',
                'mimes:jpeg,png,jpg,webp',
                'mimetypes:image/jpeg,image/png,image/webp',
                'max:4096',
                'dimensions:min_width=600,min_height=300,max_width=4000,max_height=3000',
            ],
        ]);

        $settings = ClinicSetting::getInstance();

        if ($settings->hero_image) {
            Storage::disk('public')->delete($settings->hero_image);
        }

        $path = $request->file('hero_image')->store('hero', 'public');
        $settings->update(['hero_image' => $path]);

        return response()->json([
            'success' => true,
            'message' => __('messages.settings.hero_uploaded'),
            'data' => new ClinicSettingResource($settings->fresh()),
        ]);
    }

    public function deleteHeroImage(): JsonResponse
    {
        $settings = ClinicSetting::getInstance();

        if ($settings->hero_image) {
            Storage::disk('public')->delete($settings->hero_image);
            $settings->update(['hero_image' => null]);
        }

        return response()->json([
            'success' => true,
            'message' => __('messages.settings.hero_deleted'),
            'data' => new ClinicSettingResource($settings->fresh()),
        ]);
    }

    // Marks the first-run wizard complete. Required: clinic_name, doctor_name,
    // and phone are non-empty AND not the seeded placeholder strings (to prevent
    // accidentally marking complete on a fresh install). Frontend hits this once
    // the doctor finishes the wizard; after this, /admin/setup redirects to
    // the dashboard and admin pages stop nagging.
    public function completeSetup(): JsonResponse
    {
        $settings = ClinicSetting::getInstance();

        $placeholder = ['العيادة', 'الدكتور', 'Clinic', 'Doctor'];
        $missing = [];
        foreach (['clinic_name' => 'العيادة', 'doctor_name' => 'الدكتور', 'phone' => null] as $field => $defaultPlaceholder) {
            $value = $settings->{$field};
            if (blank($value) || in_array($value, $placeholder, true)) {
                $missing[] = $field;
            }
        }

        if (! empty($missing)) {
            return response()->json([
                'success' => false,
                'message' => __('messages.settings.setup_incomplete'),
                'error_code' => 'SETUP_INCOMPLETE',
                'errors' => ['missing' => $missing],
            ], 422);
        }

        $settings->update(['setup_completed_at' => now()]);

        return response()->json([
            'success' => true,
            'message' => __('messages.settings.setup_completed'),
            'data' => new ClinicSettingResource($settings->fresh()),
        ]);
    }
}
