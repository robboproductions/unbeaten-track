@extends('layouts.admin')

@section('title', 'Edit user · Admin')

@section('content')
    <div class="admin-page-header">
        <div>
            <div class="admin-page-title">Edit user</div>
            <div class="admin-page-subtitle">{{ $user->name }} · {{ $user->email }}</div>
        </div>
        <div class="admin-page-actions">
            <a class="btn btn-neutral btn-sm" href="{{ route('admin.users.index') }}">← Back to users</a>
        </div>
    </div>

    <div class="admin-content" style="padding-top:16px;">
        <div class="card" style="max-width:640px;">
            <form method="post" action="{{ route('admin.users.update', $user) }}" class="town-form-main" style="padding:18px 20px 22px;">
                @csrf
                @method('put')

                <div class="town-form-field-row">
                    <div class="town-form-field">
                        <label class="town-form-label" for="edit-user-first-name">First name</label>
                        <input id="edit-user-first-name" type="text" name="first_name" value="{{ old('first_name', $user->first_name) }}" required maxlength="120" class="town-form-control" autocomplete="off" />
                        @error('first_name')<p class="town-form-error">{{ $message }}</p>@enderror
                    </div>
                    <div class="town-form-field">
                        <label class="town-form-label" for="edit-user-last-name">Last name</label>
                        <input id="edit-user-last-name" type="text" name="last_name" value="{{ old('last_name', $user->last_name) }}" required maxlength="120" class="town-form-control" autocomplete="off" />
                        @error('last_name')<p class="town-form-error">{{ $message }}</p>@enderror
                    </div>
                </div>

                <div class="town-form-field" style="margin-top:14px;">
                    <label class="town-form-label" for="edit-user-email">Email</label>
                    <input id="edit-user-email" type="email" name="email" value="{{ old('email', $user->email) }}" required class="town-form-control" autocomplete="off" />
                    @error('email')<p class="town-form-error">{{ $message }}</p>@enderror
                </div>

                <div class="town-form-field" style="margin-top:14px;">
                    <label class="town-form-label" for="edit-user-role">Role</label>
                    <select id="edit-user-role" name="role" class="town-form-control town-form-control--select" required>
                        @foreach ($roleOptions as $value => $label)
                            <option value="{{ $value }}" @selected(old('role', $user->role) === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                    @error('role')<p class="town-form-error">{{ $message }}</p>@enderror
                </div>

                <div class="town-form-fieldset" style="margin-top:22px;padding-top:16px;border-top:1px solid var(--color-border);">
                    <div class="town-form-label town-form-label--spacer">Reset password</div>
                    <p class="town-form-hint" style="margin:0 0 12px;font-size:12px;color:var(--color-mid-grey);">Leave blank to keep their current password.</p>
                    <div class="town-form-field">
                        <label class="town-form-label" for="edit-user-password">New password</label>
                        <input id="edit-user-password" type="password" name="password" class="town-form-control" autocomplete="new-password" />
                        @error('password')<p class="town-form-error">{{ $message }}</p>@enderror
                    </div>
                    <div class="town-form-field" style="margin-top:12px;">
                        <label class="town-form-label" for="edit-user-password-confirmation">Confirm new password</label>
                        <input id="edit-user-password-confirmation" type="password" name="password_confirmation" class="town-form-control" autocomplete="new-password" />
                    </div>
                </div>

                <div class="town-form-footer-actions" style="margin-top:22px;">
                    <button type="submit" class="btn btn-primary btn-sm">Save changes</button>
                    <a class="btn btn-neutral btn-sm" href="{{ route('admin.users.index') }}">Cancel</a>
                </div>
            </form>
        </div>
    </div>
@endsection
