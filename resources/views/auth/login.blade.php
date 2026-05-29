@extends('layouts.app')

@section('content')
    <div class="panel" style="max-width: 420px; margin: 60px auto;">
        <h1>Private access</h1>
        <form method="POST" action="{{ route('login.attempt') }}">
            @csrf
            <div class="field">
                <label for="username">Username</label>
                <input id="username" name="username" type="text" value="{{ old('username') }}" required autofocus>
            </div>
            <div class="field">
                <label for="password">Password</label>
                <input id="password" name="password" type="password" required>
            </div>
            <button class="btn primary" type="submit">Login</button>
        </form>
    </div>
@endsection
