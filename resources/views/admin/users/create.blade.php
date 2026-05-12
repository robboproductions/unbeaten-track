@extends('layouts.admin')

@section('title', 'Add user · Admin')

@section('content')
    <div class="admin-page-header">
        <div>
            <div class="admin-page-title">Add user</div>
            <div class="admin-page-subtitle">Create an admin account</div>
        </div>
        <div class="admin-page-actions">
            <a class="btn btn-neutral btn-sm" href="{{ route('admin.users.index') }}">← Back to users</a>
        </div>
    </div>

    <div class="admin-content" style="padding-top:16px;">
        <div class="card" style="max-width:640px;">
            <form method="post" action="{{ route('admin.users.store') }}" class="town-form-main" style="padding:18px 20px 22px;">
                @csrf

                <div class="town-form-field-row">
                    <div class="town-form-field">
                        <label class="town-form-label" for="user-first-name">First name</label>
                        <input id="user-first-name" type="text" name="first_name" value="{{ old('first_name') }}" required maxlength="120" class="town-form-control" autocomplete="off" />
                        @error('first_name')<p class="town-form-error">{{ $message }}</p>@enderror
                    </div>
                    <div class="town-form-field">
                        <label class="town-form-label" for="user-last-name">Last name</label>
                        <input id="user-last-name" type="text" name="last_name" value="{{ old('last_name') }}" required maxlength="120" class="town-form-control" autocomplete="off" />
                        @error('last_name')<p class="town-form-error">{{ $message }}</p>@enderror
                    </div>
                </div>

                <div class="town-form-field" style="margin-top:14px;">
                    <label class="town-form-label" for="user-email">Email</label>
                    <input id="user-email" type="email" name="email" value="{{ old('email') }}" required class="town-form-control" autocomplete="off" />
                    @error('email')<p class="town-form-error">{{ $message }}</p>@enderror
                </div>

                <div class="town-form-field" style="margin-top:14px;">
                    <label class="town-form-label" for="user-role">Role</label>
                    <select id="user-role" name="role" class="town-form-control town-form-control--select" required>
                        @foreach ($roleOptions as $value => $label)
                            <option value="{{ $value }}" @selected(old('role', \App\Models\User::ROLE_ADMIN) === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                    @error('role')<p class="town-form-error">{{ $message }}</p>@enderror
                </div>

                <div class="town-form-field" style="margin-top:14px;">
                    <label class="town-form-label" for="user-password">Password</label>
                    <input id="user-password" type="password" name="password" required class="town-form-control" autocomplete="new-password" />
                    @error('password')<p class="town-form-error">{{ $message }}</p>@enderror
                </div>

                <div class="town-form-field" style="margin-top:14px;">
                    <label class="town-form-label" for="user-password-confirmation">Confirm password</label>
                    <input id="user-password-confirmation" type="password" name="password_confirmation" required class="town-form-control" autocomplete="new-password" />
                </div>

                <div class="town-form-footer-actions" style="margin-top:22px;">
                    <button type="submit" class="btn btn-primary btn-sm">Create user</button>
                    <a class="btn btn-neutral btn-sm" href="{{ route('admin.users.index') }}">Cancel</a>
                </div>
            </form>
        </div>
    </div>
@endsection
