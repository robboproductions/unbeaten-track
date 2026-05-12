@extends('layouts.auth')

@section('title', 'Sign in · ' . config('app.name', 'Unbeaten Track'))

@section('content')
    <div class="auth-page">
        <div class="auth-card">
            <div class="auth-card-brand">
                @if (file_exists(public_path('images/logo-dark.png')))
                    <img src="{{ asset('images/logo-dark.png') }}" alt="{{ config('app.name', 'Unbeaten Track') }}" width="160" decoding="async" />
                @elseif (file_exists(public_path('images/logo-light.png')))
                    <img src="{{ asset('images/logo-light.png') }}" alt="{{ config('app.name', 'Unbeaten Track') }}" width="160" decoding="async" />
                @else
                    <span class="auth-card-brand-text">{{ config('app.name', 'Unbeaten Track') }}</span>
                @endif
                <span class="auth-card-sub">Admin sign-in</span>
            </div>

            <form method="post" action="{{ route('login') }}" class="auth-form">
                @csrf

                <div class="auth-field">
                    <label class="auth-label" for="login-email">Email</label>
                    <input
                        id="login-email"
                        type="email"
                        name="email"
                        value="{{ old('email') }}"
                        required
                        autocomplete="email"
                        autofocus
                        class="auth-input @error('email') auth-input--error @enderror"
                    />
                    @error('email')<p class="auth-error">{{ $message }}</p>@enderror
                </div>

                <div class="auth-field">
                    <label class="auth-label" for="login-password">Password</label>
                    <input
                        id="login-password"
                        type="password"
                        name="password"
                        required
                        autocomplete="current-password"
                        class="auth-input @error('password') auth-input--error @enderror"
                    />
                    @error('password')<p class="auth-error">{{ $message }}</p>@enderror
                </div>

                <label class="auth-remember">
                    <input type="checkbox" name="remember" value="1" @checked(old('remember')) />
                    <span>Remember me</span>
                </label>

                <button type="submit" class="btn btn-primary auth-submit">Sign in</button>
            </form>
        </div>
    </div>
@endsection
