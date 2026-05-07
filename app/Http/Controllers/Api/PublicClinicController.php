<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\PublicClinicInfoResource;
use App\Models\ClinicSetting;
use Illuminate\Http\JsonResponse;

class PublicClinicController extends Controller
{
    // Surfaces clinic branding/contact for the unauthenticated landing page.
    // Operational settings (slot duration, advance booking days, cancellation
    // window) live on the admin endpoint — they're policy, not marketing.
    public function info(): JsonResponse
    {
        $settings = ClinicSetting::getInstance();

        return response()->json([
            'success' => true,
            'data' => new PublicClinicInfoResource($settings),
        ]);
    }
}
