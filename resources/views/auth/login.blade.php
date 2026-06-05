@extends('layouts.app')

@section('content')
    <div style="min-height: calc(100vh - 56px); display: flex; align-items: center; justify-content: center; padding: 20px;">
        <div style="width: 100%; max-width: 400px;">

            {{-- Brand header --}}
            <div style="text-align: center; margin-bottom: 28px;">
                <span class="brand-icon" style="width: 48px; height: 48px; margin: 0 auto 14px; display: flex; align-items: center; justify-content: center;" aria-hidden="true">
                    <svg width="20" height="20" viewBox="0 0 16 16" fill="currentColor" xmlns="http://www.w3.org/2000/svg">
                        <path fill-rule="evenodd" d="M0 0h1v15h15v1H0V0Zm14.817 3.113a.5.5 0 0 1 .07.704l-4.5 5.5a.5.5 0 0 1-.74.037L7.06 6.767l-3.656 5.027a.5.5 0 0 1-.808-.588l4-5.5a.5.5 0 0 1 .758-.06l2.609 2.61 4.15-5.073a.5.5 0 0 1 .704-.07Z"/>
                    </svg>
                </span>
                <div style="font-size: 20px; font-weight: 780; color: var(--ink);">{{ config('app.name') }}</div>
                <div class="text-muted text-sm" style="margin-top: 4px;">{{ __('Accesso privato') }}</div>
            </div>

            {{-- Form card --}}
            <div class="panel" style="padding: 24px;">
                <form method="POST" action="{{ route('login.attempt') }}">
                    @csrf
                    <div class="field">
                        <label for="username">{{ __('Username') }}</label>
                        <input id="username" name="username" type="text" value="{{ old('username') }}" required autofocus autocomplete="username">
                    </div>
                    <div class="field" style="margin-bottom: 20px;">
                        <label for="password">{{ __('Password') }}</label>
                        <input id="password" name="password" type="password" required autocomplete="current-password">
                    </div>
                    <button class="btn btn-primary btn-lg" type="submit" style="width: 100%;">{{ __('Accedi') }}</button>
                </form>
            </div>

        </div>
    </div>
@endsection
