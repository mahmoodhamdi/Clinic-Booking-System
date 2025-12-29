<?php

namespace App\Http\Controllers\Api;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\ChangePasswordRequest;
use App\Http\Requests\Auth\ForgotPasswordRequest;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Http\Requests\Auth\ResetPasswordRequest;
use App\Http\Requests\Auth\UpdateProfileRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    /**
     * Register a new patient.
     */
    public function register(RegisterRequest $request): JsonResponse
    {
        $user = User::create([
            'name' => $request->name,
            'phone' => $request->phone,
            'email' => $request->email,
            'password' => $request->password,
            'role' => UserRole::PATIENT,
        ]);

        $token = $user->createToken('auth-token')->plainTextToken;

        // Set HttpOnly cookie for browser clients
        $authCookie = $this->createAuthCookie($token);
        $userCookie = $this->createUserCookie($user);

        return response()->json([
            'success' => true,
            'message' => 'تم التسجيل بنجاح.',
            'data' => [
                'user' => new UserResource($user),
                'token' => $token, // Still returned for mobile apps
            ],
        ], 201)->withCookie($authCookie)->withCookie($userCookie);
    }

    /**
     * Login user and create token.
     */
    public function login(LoginRequest $request): JsonResponse
    {
        $user = User::where('phone', $request->phone)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'بيانات الدخول غير صحيحة.',
            ], 401);
        }

        if (!$user->is_active) {
            return response()->json([
                'success' => false,
                'message' => 'الحساب غير مفعل. يرجى التواصل مع الإدارة.',
            ], 403);
        }

        $token = $user->createToken('auth-token')->plainTextToken;

        // Set HttpOnly cookie for browser clients
        $authCookie = $this->createAuthCookie($token);
        $userCookie = $this->createUserCookie($user);

        return response()->json([
            'success' => true,
            'message' => 'تم تسجيل الدخول بنجاح.',
            'data' => [
                'user' => new UserResource($user),
                'token' => $token, // Still returned for mobile apps
            ],
        ])->withCookie($authCookie)->withCookie($userCookie);
    }

    /**
     * Logout user (revoke token).
     */
    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'success' => true,
            'message' => 'تم تسجيل الخروج بنجاح.',
        ])->withCookie(cookie()->forget('auth_token'))
          ->withCookie(cookie()->forget('user'));
    }

    /**
     * Get authenticated user.
     */
    public function me(Request $request): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => new UserResource($request->user()),
        ]);
    }

    /**
     * Update user profile.
     */
    public function updateProfile(UpdateProfileRequest $request): JsonResponse
    {
        $user = $request->user();
        $user->update($request->validated());

        return response()->json([
            'success' => true,
            'message' => 'تم تحديث الملف الشخصي بنجاح.',
            'data' => new UserResource($user->fresh()),
        ]);
    }

    /**
     * Change password.
     */
    public function changePassword(ChangePasswordRequest $request): JsonResponse
    {
        $request->user()->update([
            'password' => $request->password,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'تم تغيير كلمة المرور بنجاح.',
        ]);
    }

    /**
     * Upload avatar.
     */
    public function uploadAvatar(Request $request): JsonResponse
    {
        $request->validate([
            'avatar' => [
                'required',
                'image',
                'mimes:jpeg,png,jpg',
                'mimetypes:image/jpeg,image/png',
                'max:2048',
                'dimensions:min_width=50,min_height=50,max_width=2000,max_height=2000',
            ],
        ]);

        $user = $request->user();

        // Delete old avatar if exists
        if ($user->avatar) {
            Storage::disk('public')->delete($user->avatar);
        }

        // Generate a secure random filename to prevent path traversal
        $extension = $request->file('avatar')->getClientOriginalExtension();
        $filename = Str::uuid() . '.' . $extension;
        $path = $request->file('avatar')->storeAs('avatars', $filename, 'public');
        $user->update(['avatar' => $path]);

        return response()->json([
            'success' => true,
            'message' => 'تم تحديث الصورة الشخصية بنجاح.',
            'data' => new UserResource($user->fresh()),
        ]);
    }

    /**
     * Delete user account.
     */
    public function deleteAccount(Request $request): JsonResponse
    {
        $user = $request->user();

        // Revoke all tokens
        $user->tokens()->delete();

        // Soft delete the user
        $user->delete();

        return response()->json([
            'success' => true,
            'message' => 'تم حذف الحساب بنجاح.',
        ]);
    }

    /**
     * Request password reset (send OTP).
     */
    public function forgotPassword(ForgotPasswordRequest $request): JsonResponse
    {
        $token = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        DB::table('password_reset_tokens')->updateOrInsert(
            ['phone' => $request->phone],
            [
                'token' => Hash::make($token),
                'created_at' => now(),
            ]
        );

        // TODO: Implement SMS gateway integration to send OTP
        // In production, use a service like Twilio, Vonage, or local SMS provider
        // NotificationService::sendPasswordResetSms($request->phone, $token);

        return response()->json([
            'success' => true,
            'message' => 'تم إرسال رمز التحقق إلى هاتفك.',
        ]);
    }

    /**
     * Maximum OTP verification attempts before lockout.
     */
    private const MAX_OTP_ATTEMPTS = 5;

    /**
     * Lockout duration in minutes.
     */
    private const OTP_LOCKOUT_MINUTES = 30;

    /**
     * Verify OTP token.
     */
    public function verifyOtp(Request $request): JsonResponse
    {
        $request->validate([
            'phone' => ['required', 'string', 'exists:users,phone'],
            'token' => ['required', 'string', 'size:6'],
        ]);

        $record = DB::table('password_reset_tokens')
            ->where('phone', $request->phone)
            ->first();

        if (!$record) {
            return response()->json([
                'success' => false,
                'message' => 'رمز التحقق غير صحيح.',
            ], 422);
        }

        // Check if account is locked out
        if ($record->locked_until && now()->lessThan($record->locked_until)) {
            $remainingMinutes = now()->diffInMinutes($record->locked_until);
            return response()->json([
                'success' => false,
                'message' => "تم تجاوز الحد الأقصى للمحاولات. يرجى المحاولة بعد {$remainingMinutes} دقيقة.",
            ], 429);
        }

        // Check if token is expired (60 minutes)
        $createdAt = \Carbon\Carbon::parse($record->created_at);
        if ($createdAt->addMinutes(60)->isPast()) {
            return response()->json([
                'success' => false,
                'message' => 'رمز التحقق منتهي الصلاحية.',
            ], 422);
        }

        if (!Hash::check($request->token, $record->token)) {
            // Increment failed attempts
            $attempts = ($record->attempts ?? 0) + 1;
            $updateData = ['attempts' => $attempts];

            // Lock out if max attempts reached
            if ($attempts >= self::MAX_OTP_ATTEMPTS) {
                $updateData['locked_until'] = now()->addMinutes(self::OTP_LOCKOUT_MINUTES);
            }

            DB::table('password_reset_tokens')
                ->where('phone', $request->phone)
                ->update($updateData);

            $remainingAttempts = self::MAX_OTP_ATTEMPTS - $attempts;
            if ($remainingAttempts > 0) {
                return response()->json([
                    'success' => false,
                    'message' => "رمز التحقق غير صحيح. المحاولات المتبقية: {$remainingAttempts}",
                ], 422);
            }

            return response()->json([
                'success' => false,
                'message' => 'تم تجاوز الحد الأقصى للمحاولات. يرجى المحاولة بعد 30 دقيقة.',
            ], 429);
        }

        // Reset attempts on successful verification
        DB::table('password_reset_tokens')
            ->where('phone', $request->phone)
            ->update(['attempts' => 0, 'locked_until' => null]);

        return response()->json([
            'success' => true,
            'message' => 'رمز التحقق صحيح.',
        ]);
    }

    /**
     * Reset password with OTP.
     */
    public function resetPassword(ResetPasswordRequest $request): JsonResponse
    {
        $record = DB::table('password_reset_tokens')
            ->where('phone', $request->phone)
            ->first();

        if (!$record) {
            return response()->json([
                'success' => false,
                'message' => 'رمز التحقق غير صحيح.',
            ], 422);
        }

        // Check if account is locked out
        if ($record->locked_until && now()->lessThan($record->locked_until)) {
            $remainingMinutes = now()->diffInMinutes($record->locked_until);
            return response()->json([
                'success' => false,
                'message' => "تم تجاوز الحد الأقصى للمحاولات. يرجى المحاولة بعد {$remainingMinutes} دقيقة.",
            ], 429);
        }

        // Check if token is expired (60 minutes)
        $createdAt = \Carbon\Carbon::parse($record->created_at);
        if ($createdAt->addMinutes(60)->isPast()) {
            return response()->json([
                'success' => false,
                'message' => 'رمز التحقق منتهي الصلاحية.',
            ], 422);
        }

        if (!Hash::check($request->token, $record->token)) {
            // Increment failed attempts
            $attempts = ($record->attempts ?? 0) + 1;
            $updateData = ['attempts' => $attempts];

            // Lock out if max attempts reached
            if ($attempts >= self::MAX_OTP_ATTEMPTS) {
                $updateData['locked_until'] = now()->addMinutes(self::OTP_LOCKOUT_MINUTES);
            }

            DB::table('password_reset_tokens')
                ->where('phone', $request->phone)
                ->update($updateData);

            $remainingAttempts = self::MAX_OTP_ATTEMPTS - $attempts;
            if ($remainingAttempts > 0) {
                return response()->json([
                    'success' => false,
                    'message' => "رمز التحقق غير صحيح. المحاولات المتبقية: {$remainingAttempts}",
                ], 422);
            }

            return response()->json([
                'success' => false,
                'message' => 'تم تجاوز الحد الأقصى للمحاولات. يرجى المحاولة بعد 30 دقيقة.',
            ], 429);
        }

        // Update password
        User::where('phone', $request->phone)->update([
            'password' => Hash::make($request->password),
        ]);

        // Delete the reset token
        DB::table('password_reset_tokens')->where('phone', $request->phone)->delete();

        return response()->json([
            'success' => true,
            'message' => 'تم إعادة تعيين كلمة المرور بنجاح.',
        ]);
    }

    /**
     * Refresh token.
     */
    public function refresh(Request $request): JsonResponse
    {
        $user = $request->user();

        // Revoke current token
        $user->currentAccessToken()->delete();

        // Create new token
        $token = $user->createToken('auth-token')->plainTextToken;

        // Set HttpOnly cookie for browser clients
        $authCookie = $this->createAuthCookie($token);
        $userCookie = $this->createUserCookie($user);

        return response()->json([
            'success' => true,
            'message' => 'تم تحديث التوكن بنجاح.',
            'data' => [
                'token' => $token,
            ],
        ])->withCookie($authCookie)->withCookie($userCookie);
    }

    /**
     * Get cookie expiration in minutes (matches token expiration).
     */
    protected function getCookieExpiration(): int
    {
        return (int) config('sanctum.expiration', 240);
    }

    /**
     * Create HttpOnly auth cookie.
     */
    protected function createAuthCookie(string $token): \Symfony\Component\HttpFoundation\Cookie
    {
        $secure = app()->environment('production');
        $expiration = $this->getCookieExpiration();

        return cookie(
            'auth_token',
            $token,
            $expiration,
            '/',
            null,
            $secure, // secure (HTTPS only in production)
            true, // httpOnly - not accessible via JavaScript
            false,
            'lax' // SameSite - lax for better compatibility
        );
    }

    /**
     * Create user info cookie (readable by JavaScript for UI purposes).
     */
    protected function createUserCookie(User $user): \Symfony\Component\HttpFoundation\Cookie
    {
        $secure = app()->environment('production');
        $expiration = $this->getCookieExpiration();

        // Only include non-sensitive user info
        $userData = [
            'id' => $user->id,
            'name' => $user->name,
            'role' => $user->role->value,
            'avatar' => $user->avatar,
        ];

        return cookie(
            'user',
            json_encode($userData),
            $expiration,
            '/',
            null,
            $secure,
            false, // NOT httpOnly - accessible by JavaScript for UI
            false,
            'lax'
        );
    }
}
