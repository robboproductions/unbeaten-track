@extends('layouts.admin')

@section('title', 'Towns · Admin')

@section('content')
    <div class="admin-page-header">
        <div>
            <div class="admin-page-title">Towns</div>
            <div class="admin-page-subtitle">{{ $towns->total() }} towns</div>
        </div>
        <div class="admin-page-actions">
            <a class="btn btn-neutral btn-sm" href="{{ route('admin.towns.map', request()->query()) }}">Map view</a>
            <a class="btn btn-primary btn-sm" href="{{ route('admin.towns.create') }}">+ Add town</a>
        </div>
    </div>

    <div class="admin-content" style="padding-top:16px;">
        <div class="card">
            <div style="padding:14px 16px;border-bottom:1px solid var(--color-border);display:flex;gap:8px;align-items:center;background:var(--color-white);">
                <form method="get" action="{{ route('admin.towns.index') }}" style="display:flex;gap:8px;align-items:center;flex:1;">
                    <input
                        name="q"
                        value="{{ request('q') }}"
                        placeholder="Search towns..."
                        style="background:var(--color-river-stone);border:1px solid var(--color-border);border-radius:var(--radius-md);padding:7px 12px;font-size:13px;color:var(--color-near-black);font-family:var(--font-family);outline:none;width:240px;"
                    />
                    <select
                        name="state"
                        style="background:var(--color-white);border:1px solid var(--color-border);border-radius:var(--radius-md);padding:7px 12px;font-size:13px;color:var(--color-charcoal);font-family:var(--font-family);outline:none;cursor:pointer;min-width:200px;max-width:260px;"
                    >
                        <option value="">All states</option>
                        @foreach ($stateOptions as $st)
                            <option value="{{ $st }}" @selected(request('state') === $st)>{{ $st }}</option>
                        @endforeach
                    </select>
                    <select
                        name="status"
                        style="background:var(--color-white);border:1px solid var(--color-border);border-radius:var(--radius-md);padding:7px 12px;font-size:13px;color:var(--color-charcoal);font-family:var(--font-family);outline:none;cursor:pointer;"
                    >
                        <option value="">All status</option>
                        <option value="published" @selected(request('status') === 'published')>Published</option>
                        <option value="draft" @selected(request('status') === 'draft')>Draft</option>
                        <option value="pending" @selected(request('status') === 'pending')>Pending</option>
                    </select>
                    <input class="btn btn-neutral btn-sm" type="submit" value="Filter" />
                    <a class="btn btn-neutral btn-sm" href="{{ route('admin.towns.index') }}">Reset</a>
                </form>
            </div>

            <table style="width:100%;border-collapse:collapse;font-size:13px;">
                <thead>
                    <tr style="background:#fafbf9;">
                        <th style="padding:10px 14px;text-align:left;font-size:10px;font-weight:600;letter-spacing:0.07em;text-transform:uppercase;color:var(--color-mid-grey);border-bottom:1px solid var(--color-border);">Town</th>
                        <th style="padding:10px 14px;text-align:left;font-size:10px;font-weight:600;letter-spacing:0.07em;text-transform:uppercase;color:var(--color-mid-grey);border-bottom:1px solid var(--color-border);">State</th>
                        <th style="padding:10px 14px;text-align:left;font-size:10px;font-weight:600;letter-spacing:0.07em;text-transform:uppercase;color:var(--color-mid-grey);border-bottom:1px solid var(--color-border);">Region</th>
                        <th style="padding:10px 14px;text-align:left;font-size:10px;font-weight:600;letter-spacing:0.07em;text-transform:uppercase;color:var(--color-mid-grey);border-bottom:1px solid var(--color-border);">Pop. (approx)</th>
                        <th style="padding:10px 14px;text-align:left;font-size:10px;font-weight:600;letter-spacing:0.07em;text-transform:uppercase;color:var(--color-mid-grey);border-bottom:1px solid var(--color-border);">Corridor</th>
                        <th style="padding:10px 14px;text-align:left;font-size:10px;font-weight:600;letter-spacing:0.07em;text-transform:uppercase;color:var(--color-mid-grey);border-bottom:1px solid var(--color-border);">Status</th>
                        <th style="padding:10px 14px;text-align:center;font-size:10px;font-weight:600;letter-spacing:0.07em;text-transform:uppercase;color:var(--color-mid-grey);border-bottom:1px solid var(--color-border);width:72px;">Photos</th>
                        <th style="padding:10px 14px;border-bottom:1px solid var(--color-border);width:160px;"></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($towns as $town)
                        <tr style="border-bottom:1px solid var(--color-river-stone);">
                            <td style="padding:11px 14px;">
                                <a class="admin-town-table-name" href="{{ route('admin.towns.edit', $town) }}">{{ $town->name }}</a>
                            </td>
                            <td style="padding:11px 14px;color:var(--color-mid-grey);font-size:12px;">
                                {{ $town->state }}
                            </td>
                            <td style="padding:11px 14px;color:var(--color-mid-grey);font-size:12px;">
                                {{ $town->region ?? '—' }}
                            </td>
                            <td style="padding:11px 14px;color:var(--color-mid-grey);font-size:12px;">
                                {{ $town->population_approx !== null ? number_format($town->population_approx) : '—' }}
                            </td>
                            <td style="padding:11px 14px;color:var(--color-mid-grey);font-size:12px;max-width:200px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;" title="{{ $town->suggested_corridor }}">
                                {{ $town->suggested_corridor ?? '—' }}
                            </td>
                            <td style="padding:11px 14px;">
                                @php
                                    $townStatusBadge = match ($town->status) {
                                        'published' => ['bg' => 'var(--badge-in-progress-bg)', 'text' => 'var(--badge-in-progress-text)', 'label' => 'Published'],
                                        'pending' => ['bg' => 'var(--badge-saved-bg)', 'text' => 'var(--badge-saved-text)', 'label' => 'Pending'],
                                        default => ['bg' => 'var(--badge-draft-bg)', 'text' => 'var(--badge-draft-text)', 'label' => 'Draft'],
                                    };
                                @endphp
                                <span style="display:inline-block;font-size:10px;font-weight:600;padding:2px 7px;border-radius:4px;background:{{ $townStatusBadge['bg'] }};color:{{ $townStatusBadge['text'] }};">
                                    {{ $townStatusBadge['label'] }}
                                </span>
                            </td>
                            <td style="padding:11px 14px;text-align:center;color:var(--color-mid-grey);font-size:12px;font-variant-numeric:tabular-nums;">
                                {{ ($town->photos_count ?? 0) > 0 ? $town->photos_count : '-' }}
                            </td>
                            <td style="padding:11px 14px;">
                                <div style="display:flex;gap:6px;justify-content:flex-end;">
                                    <a class="btn btn-neutral btn-sm" href="{{ route('admin.towns.edit', $town) }}">Edit</a>
                                    <form method="post" action="{{ route('admin.towns.destroy', $town) }}">
                                        @csrf
                                        @method('delete')
                                        <input class="btn btn-neutral btn-sm" type="submit" value="Delete" />
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" style="padding:16px;color:var(--color-mid-grey);">
                                No towns yet. <a href="{{ route('admin.towns.create') }}" style="color:var(--color-bush-green);text-decoration:none;font-weight:600;">Add your first town →</a>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>

            <div style="padding:14px 16px;border-top:1px solid var(--color-border);background:var(--color-white);">
                {{ $towns->links() }}
            </div>
        </div>
    </div>
@endsection

