@extends('layouts.guest')

@section('title', 'إعادة تعيين كلمة المرور')

@section('content')
    <div class="auth-header">
        <h1>إعادة تعيين كلمة المرور</h1>
        <p>أدخل كلمة المرور الجديدة</p>
    </div>

    @if(session('error'))
        <div class="alert alert-error">
            {{ session('error') }}
        </div>
    @endif

    <form method="POST" action="{{ route('password.update') }}">
        @csrf

        <input type="hidden" name="phone" value="{{ $phone ?? old('phone') }}">
        <input type="hidden" name="token" value="{{ $token ?? old('token') }}">

        <div class="form-group">
            <label for="password">كلمة المرور الجديدة</label>
            <input type="password" id="password" name="password" placeholder="8 أحرف على الأقل" required autofocus>
            @error('password')
                <div class="error">{{ $message }}</div>
            @enderror
        </div>

        <div class="form-group">
            <label for="password_confirmation">تأكيد كلمة المرور</label>
            <input type="password" id="password_confirmation" name="password_confirmation" placeholder="أعد إدخال كلمة المرور" required>
        </div>

        <button type="submit" class="btn">تغيير كلمة المرور</button>
    </form>

    <div class="auth-footer">
        <p><a href="{{ route('login') }}">العودة لتسجيل الدخول</a></p>
    </div>
@endsection
