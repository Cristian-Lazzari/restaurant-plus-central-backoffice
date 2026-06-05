@extends('layouts.app')

@section('content')
    <style>
        .login-wrap {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px 16px;
            background: #090333;
        }
        .login-box { width: 100%; max-width: 380px; }
        .login-logo-area {
            text-align: center;
            margin-bottom: 28px;
        }
        .login-logo-area img {
            height: 36px;
            width: auto;
            display: inline-block;
            margin-bottom: 12px;
        }
        .login-subtitle {
            color: rgba(216,221,232,0.65);
            font-size: 13px;
        }
        .login-card {
            background: rgba(255,255,255,0.04);
            border: 1px solid rgba(255,255,255,0.1);
            border-radius: 12px;
            padding: 28px;
            backdrop-filter: blur(8px);
        }
        .login-card label { color: rgba(216,221,232,0.85); }
        .login-card input {
            background: rgba(255,255,255,0.06);
            border-color: rgba(255,255,255,0.12);
            color: #d8dde8;
        }
        .login-card input:focus {
            background: rgba(255,255,255,0.09);
            border-color: #0eb792;
            box-shadow: 0 0 0 3px rgba(14,183,146,0.18);
            color: #fff;
        }
        .login-card input::placeholder { color: rgba(216,221,232,0.35); }
        .login-card .btn-primary {
            background: #0eb792;
            border-color: #0eb792;
            color: #fff;
            font-size: 14px;
            font-weight: 700;
        }
        .login-card .btn-primary:hover { background: #0ca07f; border-color: #0ca07f; }
    </style>

    <div class="login-wrap">
        <div class="login-box">
            <div class="login-logo-area">
                @php
                    $lsrc = file_exists(public_path('images/logo-futureplus.png'))
                        ? 'data:image/png;base64,'.base64_encode(file_get_contents(public_path('images/logo-futureplus.png')))
                        : null;
                @endphp
                @if($lsrc)
                    <img src="{{ $lsrc }}" alt="Future Plus">
                @else
                    <span style="color:#fff;font-weight:800;font-size:22px;letter-spacing:.5px;">FUTURE+</span>
                @endif
                <div class="login-subtitle">{{ __('Accesso backoffice privato') }}</div>
            </div>
            <div class="login-card">
                <form method="POST" action="{{ route('login.attempt') }}">
                    @csrf
                    <div class="field">
                        <label for="username">{{ __('Username') }}</label>
                        <input id="username" name="username" type="text" value="{{ old('username') }}" required autofocus autocomplete="username">
                    </div>
                    <div class="field" style="margin-bottom: 22px;">
                        <label for="password">{{ __('Password') }}</label>
                        <input id="password" name="password" type="password" required autocomplete="current-password">
                    </div>
                    <button class="btn btn-primary btn-lg" type="submit" style="width:100%;">{{ __('Accedi') }}</button>
                </form>
            </div>
        </div>
    </div>
@endsection
