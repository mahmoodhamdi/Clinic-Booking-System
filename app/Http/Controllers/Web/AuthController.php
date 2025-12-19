<?php

namespace App\Http\Controllers\Web;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class AuthController extends Controller
{
    /**
     * Show login form.
     */
    public function showLoginForm(): View
    {
        return view('auth.login');
    }

    /**
     * Handle login request.
     */
    public function login(LoginRequest $request): RedirectResponse
    {
        $user = User::where('phone', $request->phone)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return back()->withInput()->with('error', 'بيانات الدخول غير صحيحة.');
        }

        if (!$user->is_active) {
            return back()->withInput()->with('error', 'الحساب غير مفعل. يرجى التواصل مع الإدارة.');
        }

        Auth::login($user);

        if ($user->isAdmin()) {
            return redirect()->intended('/admin/dashboard');
        }

        return redirect()->intended('/patient/dashboard');
    }

    /**
     * Show registration form.
     */
    public function showRegistrationForm(): View
    {
        return view('auth.register');
    }

    /**
     * Handle registration request.
     */
    public function register(RegisterRequest $request): RedirectResponse
    {
        $user = User::create([
            'name' => $request->name,
            'phone' => $request->phone,
            'email' => $request->email,
            'password' => $request->password,
            'role' => UserRole::PATIENT,
        ]);

        Auth::login($user);

        return redirect('/patient/dashboard')->with('success', 'تم التسجيل بنجاح!');
    }

    /**
     * Logout user.
     */
    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }

    /**
     * Show forgot password form.
     */
    public function showForgotPasswordForm(): View
    {
        return view('auth.forgot-password');
    }

    /**
     * Handle forgot password request.
     */
    public function sendResetOtp(Request $request): RedirectResponse
    {
        $request->validate([
            'phone' => ['required', 'string', 'exists:users,phone'],
        ], [
            'phone.exists' => 'رقم الهاتف غير مسجل.',
        ]);

        $token = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        DB::table('password_reset_tokens')->updateOrInsert(
            ['phone' => $request->phone],
            [
                'token' => Hash::make($token),
                'created_at' => now(),
            ]
        );

        // TODO: Send OTP via SMS (currently logging for development)
        \Log::info("Password reset OTP for {$request->phone}: {$token}");

        return redirect()->route('password.verify.form', ['phone' => $request->phone])
            ->with('success', 'تم إرسال رمز التحقق إلى هاتفك.');
    }

    /**
     * Show OTP verification form.
     */
    public function showVerifyOtpForm(Request $request): View
    {
        return view('auth.verify-otp', ['phone' => $request->phone]);
    }

    /**
     * Verify OTP.
     */
    public function verifyOtp(Request $request): RedirectResponse
    {
        $request->validate([
            'phone' => ['required', 'string'],
            'token' => ['required', 'string', 'size:6'],
        ]);

        $record = DB::table('password_reset_tokens')
            ->where('phone', $request->phone)
            ->first();

        if (!$record || !Hash::check($request->token, $record->token)) {
            return back()->with('error', 'رمز التحقق غير صحيح.');
        }

        if (now()->diffInMinutes($record->created_at) > 60) {
            return back()->with('error', 'رمز التحقق منتهي الصلاحية.');
        }

        return redirect()->route('password.reset.form', [
            'phone' => $request->phone,
            'token' => $request->token,
        ]);
    }

    /**
     * Show reset password form.
     */
    public function showResetPasswordForm(Request $request): View
    {
        return view('auth.reset-password', [
            'phone' => $request->phone,
            'token' => $request->token,
        ]);
    }

    /**
     * Reset password.
     */
    public function resetPassword(Request $request): RedirectResponse
    {
        $request->validate([
            'phone' => ['required', 'string'],
            'token' => ['required', 'string'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ], [
            'password.min' => 'كلمة المرور يجب أن تكون 8 أحرف على الأقل.',
            'password.confirmed' => 'كلمة المرور غير متطابقة.',
        ]);

        $record = DB::table('password_reset_tokens')
            ->where('phone', $request->phone)
            ->first();

        if (!$record || !Hash::check($request->token, $record->token)) {
            return back()->with('error', 'رمز التحقق غير صحيح.');
        }

        User::where('phone', $request->phone)->update([
            'password' => Hash::make($request->password),
        ]);

        DB::table('password_reset_tokens')->where('phone', $request->phone)->delete();

        return redirect()->route('login')->with('success', 'تم تغيير كلمة المرور بنجاح.');
    }
}
