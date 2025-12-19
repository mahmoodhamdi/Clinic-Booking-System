@extends('layouts.guest')

@section('title', 'تسجيل الدخول')

@section('content')
    <div class="auth-header">
        <h1>تسجيل الدخول</h1>
        <p>مرحباً بك في نظام حجز العيادة</p>
    </div>

    @if(session('success'))
        <div class="alert alert-success">
            {{ session('success') }}
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-error">
            {{ session('error') }}
        </div>
    @endif

    <form method="POST" action="{{ route('login') }}">
        @csrf

        <div class="form-group">
            <label for="phone">رقم الهاتف</label>
            <input type="tel" id="phone" name="phone" value="{{ old('phone') }}" placeholder="01xxxxxxxxx" required autofocus>
            @error('phone')
                <div class="error">{{ $message }}</div>
            @enderror
        </div>

        <div class="form-group">
            <label for="password">كلمة المرور</label>
            <input type="password" id="password" name="password" placeholder="********" required>
            @error('password')
                <div class="error">{{ $message }}</div>
            @enderror
        </div>

        <button type="submit" class="btn">تسجيل الدخول</button>
    </form>

    <div class="auth-footer">
        <p>ليس لديك حساب؟ <a href="{{ route('register') }}">إنشاء حساب جديد</a></p>
        <p style="margin-top: 10px;"><a href="{{ route('password.request') }}">نسيت كلمة المرور؟</a></p>
    </div>
@endsection
