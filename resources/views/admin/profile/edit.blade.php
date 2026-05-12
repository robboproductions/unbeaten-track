@extends('layouts.admin')

@section('title', 'Your profile · Admin')

@section('content')
    <div class="admin-page-header">
        <div>
            <div class="admin-page-title">Your profile</div>
            <div class="admin-page-subtitle">Update your name, email, and password</div>
        </div>
        <div class="admin-page-actions">
            <a class="btn btn-neutral btn-sm" href="{{ route('admin.dashboard') }}">← Dashboard</a>
        </div>
    </div>

    <div class="admin-content" style="padding-top:16px;">
        <div class="card" style="max-width:640px;">
            @if (session('status'))
                <div class="admin-flash admin-flash--success">{{ session('status') }}</div>
            @endif

            <form method="post" action="{{ route('admin.profile.update') }}" class="town-form-main" style="padding:18px 20px 22px;">
                @csrf
                @method('patch')

                <div class="town-form-field-row">
                    <div class="town-form-field">
                        <label class="town-form-label" for="profile-first-name">First name</label>
                        <input
                            id="profile-first-name"
                            type="text"
                            name="first_name"
                            value="{{ old('first_name', $user->first_name) }}"
                            required
                            maxlength="120"
                            class="town-form-control"
                            autocomplete="given-name"
                        />
                        @error('first_name')<p class="town-form-error">{{ $message }}</p>@enderror
                    </div>
                    <div class="town-form-field">
                        <label class="town-form-label" for="profile-last-name">Last name</label>
                        <input
                            id="profile-last-name"
                            type="text"
                            name="last_name"
                            value="{{ old('last_name', $user->last_name) }}"
                            required
                            maxlength="120"
                            class="town-form-control"
                            autocomplete="family-name"
                        />
                        @error('last_name')<p class="town-form-error">{{ $message }}</p>@enderror
                    </div>
                </div>

                <div class="town-form-field" style="margin-top:14px;">
                    <label class="town-form-label" for="profile-email">Email</label>
                    <input
                        id="profile-email"
                        type="email"
                        name="email"
                        value="{{ old('email', $user->email) }}"
                        required
                        class="town-form-control"
                        autocomplete="email"
                    />
                    @error('email')<p class="town-form-error">{{ $message }}</p>@enderror
                </div>

                <div class="town-form-fieldset" style="margin-top:22px;padding-top:16px;border-top:1px solid var(--color-border);">
                    <div class="town-form-label town-form-label--spacer">Change password</div>
                    <p class="town-form-hint" style="margin:0 0 12px;font-size:12px;color:var(--color-mid-grey);">Leave blank to keep your current password.</p>

                    <div class="town-form-field">
                        <label class="town-form-label" for="profile-current-password">Current password</label>
                        <input
                            id="profile-current-password"
                            type="password"
                            name="current_password"
                            class="town-form-control"
                            autocomplete="current-password"
                        />
                        @error('current_password')<p class="town-form-error">{{ $message }}</p>@enderror
                    </div>

                    <div class="town-form-field" style="margin-top:12px;">
                        <label class="town-form-label" for="profile-password">New password</label>
                        <input
                            id="profile-password"
                            type="password"
                            name="password"
                            class="town-form-control"
                            autocomplete="new-password"
                        />
                        @error('password')<p class="town-form-error">{{ $message }}</p>@enderror
                    </div>

                    <div class="town-form-field" style="margin-top:12px;">
                        <label class="town-form-label" for="profile-password-confirmation">Confirm new password</label>
                        <input
                            id="profile-password-confirmation"
                            type="password"
                            name="password_confirmation"
                            class="town-form-control"
                            autocomplete="new-password"
                        />
                    </div>
                </div>

                <div class="town-form-footer-actions" style="margin-top:22px;padding-top:0;border-top:none;">
                    <button type="submit" class="btn btn-primary btn-sm">Save profile</button>
                </div>
            </form>
        </div>
    </div>
@endsection
