@extends('layouts.guest')

@section('title', 'التحقق من الرمز')

@section('content')
    <div class="auth-header">
        <h1>التحقق من الرمز</h1>
        <p>أدخل الرمز المرسل إلى هاتفك</p>
    </div>

    @if(session('error'))
        <div class="alert alert-error">
            {{ session('error') }}
        </div>
    @endif

    <form method="POST" action="{{ route('password.verify') }}">
        @csrf

        <input type="hidden" name="phone" value="{{ $phone ?? old('phone') }}">

        <div class="form-group">
            <label for="token">رمز التحقق</label>
            <input type="text" id="token" name="token" placeholder="أدخل الرمز المكون من 6 أرقام" maxlength="6" required autofocus style="text-align: center; letter-spacing: 10px; font-size: 24px;">
            @error('token')
                <div class="error">{{ $message }}</div>
            @enderror
        </div>

        <button type="submit" class="btn">تحقق</button>
    </form>

    <div class="auth-footer">
        <p><a href="{{ route('password.request') }}">إعادة إرسال الرمز</a></p>
    </div>
@endsection
