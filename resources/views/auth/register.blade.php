@extends('layouts.guest')

@section('title', 'إنشاء حساب')

@section('content')
    <div class="auth-header">
        <h1>إنشاء حساب جديد</h1>
        <p>سجل الآن لحجز موعدك بسهولة</p>
    </div>

    @if(session('error'))
        <div class="alert alert-error">
            {{ session('error') }}
        </div>
    @endif

    <form method="POST" action="{{ route('register') }}">
        @csrf

        <div class="form-group">
            <label for="name">الاسم الكامل</label>
            <input type="text" id="name" name="name" value="{{ old('name') }}" placeholder="أدخل اسمك الكامل" required autofocus>
            @error('name')
                <div class="error">{{ $message }}</div>
            @enderror
        </div>

        <div class="form-group">
            <label for="phone">رقم الهاتف</label>
            <input type="tel" id="phone" name="phone" value="{{ old('phone') }}" placeholder="01xxxxxxxxx" required>
            @error('phone')
                <div class="error">{{ $message }}</div>
            @enderror
        </div>

        <div class="form-group">
            <label for="email">البريد الإلكتروني (اختياري)</label>
            <input type="email" id="email" name="email" value="{{ old('email') }}" placeholder="example@email.com">
            @error('email')
                <div class="error">{{ $message }}</div>
            @enderror
        </div>

        <div class="form-group">
            <label for="password">كلمة المرور</label>
            <input type="password" id="password" name="password" placeholder="8 أحرف على الأقل" required>
            @error('password')
                <div class="error">{{ $message }}</div>
            @enderror
        </div>

        <div class="form-group">
            <label for="password_confirmation">تأكيد كلمة المرور</label>
            <input type="password" id="password_confirmation" name="password_confirmation" placeholder="أعد إدخال كلمة المرور" required>
        </div>

        <button type="submit" class="btn">إنشاء حساب</button>
    </form>

    <div class="auth-footer">
        <p>لديك حساب بالفعل؟ <a href="{{ route('login') }}">تسجيل الدخول</a></p>
    </div>
@endsection
