@extends('layouts.admin')

@section('title', 'Users · Admin')

@section('content')
    <div class="admin-page-header">
        <div>
            <div class="admin-page-title">Users</div>
            <div class="admin-page-subtitle">{{ $users->total() }} accounts</div>
        </div>
        <div class="admin-page-actions">
            <a class="btn btn-primary btn-sm" href="{{ route('admin.users.create') }}">+ Add user</a>
        </div>
    </div>

    <div class="admin-content" style="padding-top:16px;">
        <div class="card">
            @if (session('status'))
                <div class="admin-flash admin-flash--success">{{ session('status') }}</div>
            @endif
            @error('delete')
                <div class="admin-flash admin-flash--error">{{ $message }}</div>
            @enderror

            <table style="width:100%;border-collapse:collapse;font-size:13px;">
                <thead>
                    <tr style="background:#fafbf9;">
                        <th style="padding:10px 14px;text-align:left;font-size:10px;font-weight:600;letter-spacing:0.07em;text-transform:uppercase;color:var(--color-mid-grey);border-bottom:1px solid var(--color-border);">Name</th>
                        <th style="padding:10px 14px;text-align:left;font-size:10px;font-weight:600;letter-spacing:0.07em;text-transform:uppercase;color:var(--color-mid-grey);border-bottom:1px solid var(--color-border);">Email</th>
                        <th style="padding:10px 14px;text-align:left;font-size:10px;font-weight:600;letter-spacing:0.07em;text-transform:uppercase;color:var(--color-mid-grey);border-bottom:1px solid var(--color-border);">Role</th>
                        <th style="padding:10px 14px;border-bottom:1px solid var(--color-border);width:140px;"></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($users as $u)
                        <tr style="border-bottom:1px solid var(--color-river-stone);">
                            <td style="padding:11px 14px;">
                                <span style="font-weight:500;color:var(--color-near-black);">{{ $u->name }}</span>
                            </td>
                            <td style="padding:11px 14px;color:var(--color-mid-grey);font-size:12px;">{{ $u->email }}</td>
                            <td style="padding:11px 14px;font-size:12px;">{{ $u->roleLabel() }}</td>
                            <td style="padding:11px 14px;text-align:right;">
                                <a class="btn btn-neutral btn-sm" href="{{ route('admin.users.edit', $u) }}">Edit</a>
                                @unless ($u->is(auth()->user()))
                                    <form method="post" action="{{ route('admin.users.destroy', $u) }}" style="display:inline;" onsubmit="return confirm('Remove this user? They will no longer be able to sign in.');">
                                        @csrf
                                        @method('delete')
                                        <button type="submit" class="btn btn-neutral btn-sm">Remove</button>
                                    </form>
                                @endunless
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" style="padding:24px 14px;color:var(--color-mid-grey);text-align:center;">No users yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>

            @if ($users->hasPages())
                <div style="padding:12px 16px;border-top:1px solid var(--color-border);">
                    {{ $users->links() }}
                </div>
            @endif
        </div>
    </div>
@endsection
