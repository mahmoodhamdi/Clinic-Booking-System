@extends('layouts.guest')

@section('title', 'نسيت كلمة المرور')

@section('content')
    <div class="auth-header">
        <h1>نسيت كلمة المرور؟</h1>
        <p>أدخل رقم هاتفك لإرسال رمز التحقق</p>
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

    <form method="POST" action="{{ route('password.phone') }}">
        @csrf

        <div class="form-group">
            <label for="phone">رقم الهاتف</label>
            <input type="tel" id="phone" name="phone" value="{{ old('phone') }}" placeholder="01xxxxxxxxx" required autofocus>
            @error('phone')
                <div class="error">{{ $message }}</div>
            @enderror
        </div>

        <button type="submit" class="btn">إرسال رمز التحقق</button>
    </form>

    <div class="auth-footer">
        <p><a href="{{ route('login') }}">العودة لتسجيل الدخول</a></p>
    </div>
@endsection
