@extends('layouts.admin')

@section('title', 'POIs · Admin')

@section('content')
    <div class="admin-page-header">
        <div>
            <div class="admin-page-title">Points of interest</div>
            <div class="admin-page-subtitle">{{ $pois->total() }} POIs</div>
        </div>
        <div class="admin-page-actions">
            <a class="btn btn-primary btn-sm" href="{{ route('admin.pois.create') }}">+ Add POI</a>
        </div>
    </div>

    <div class="admin-content" style="padding-top:16px;">
        <div class="card">
            <div style="padding:14px 16px;border-bottom:1px solid var(--color-border);display:flex;gap:8px;align-items:center;background:var(--color-white);">
                <form method="get" action="{{ route('admin.pois.index') }}" style="display:flex;gap:8px;align-items:center;flex:1;flex-wrap:wrap;">
                    <input
                        name="q"
                        value="{{ request('q') }}"
                        placeholder="Search POIs..."
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
                        name="category"
                        style="background:var(--color-white);border:1px solid var(--color-border);border-radius:var(--radius-md);padding:7px 12px;font-size:13px;color:var(--color-charcoal);font-family:var(--font-family);outline:none;cursor:pointer;min-width:180px;"
                    >
                        <option value="">All categories</option>
                        @foreach ($categoryOptions as $cat)
                            <option value="{{ $cat }}" @selected(request('category') === $cat)>{{ $cat }}</option>
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
                    <select
                        name="verification_status"
                        style="background:var(--color-white);border:1px solid var(--color-border);border-radius:var(--radius-md);padding:7px 12px;font-size:13px;color:var(--color-charcoal);font-family:var(--font-family);outline:none;cursor:pointer;min-width:200px;"
                    >
                        <option value="">All verification</option>
                        @foreach (\App\Enums\PoiVerificationStatus::cases() as $vs)
                            <option value="{{ $vs->value }}" @selected(request('verification_status') === $vs->value)>{{ $vs->label() }}</option>
                        @endforeach
                    </select>
                    <input class="btn btn-neutral btn-sm" type="submit" value="Filter" />
                    <a class="btn btn-neutral btn-sm" href="{{ route('admin.pois.index') }}">Reset</a>
                </form>
            </div>

            <table style="width:100%;border-collapse:collapse;font-size:13px;">
                <thead>
                    <tr style="background:#fafbf9;">
                        <th style="padding:10px 14px;text-align:left;font-size:10px;font-weight:600;letter-spacing:0.07em;text-transform:uppercase;color:var(--color-mid-grey);border-bottom:1px solid var(--color-border);">Name</th>
                        <th style="padding:10px 14px;text-align:left;font-size:10px;font-weight:600;letter-spacing:0.07em;text-transform:uppercase;color:var(--color-mid-grey);border-bottom:1px solid var(--color-border);">Categories</th>
                        <th style="padding:10px 14px;text-align:left;font-size:10px;font-weight:600;letter-spacing:0.07em;text-transform:uppercase;color:var(--color-mid-grey);border-bottom:1px solid var(--color-border);">Town</th>
                        <th style="padding:10px 14px;text-align:left;font-size:10px;font-weight:600;letter-spacing:0.07em;text-transform:uppercase;color:var(--color-mid-grey);border-bottom:1px solid var(--color-border);">State</th>
                        <th style="padding:10px 14px;text-align:left;font-size:10px;font-weight:600;letter-spacing:0.07em;text-transform:uppercase;color:var(--color-mid-grey);border-bottom:1px solid var(--color-border);">Detour</th>
                        <th style="padding:10px 14px;text-align:left;font-size:10px;font-weight:600;letter-spacing:0.07em;text-transform:uppercase;color:var(--color-mid-grey);border-bottom:1px solid var(--color-border);">Status</th>
                        <th style="padding:10px 14px;text-align:left;font-size:10px;font-weight:600;letter-spacing:0.07em;text-transform:uppercase;color:var(--color-mid-grey);border-bottom:1px solid var(--color-border);">Verification</th>
                        <th style="padding:10px 14px;border-bottom:1px solid var(--color-border);width:160px;"></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($pois as $poi)
                        <tr style="border-bottom:1px solid var(--color-river-stone);">
                            <td style="padding:11px 14px;color:var(--color-near-black);font-weight:600;">
                                {{ $poi->name }}
                            </td>
                            <td style="padding:11px 14px;color:var(--color-mid-grey);font-size:12px;">
                                {{ implode(', ', $poi->categoryList()) ?: '—' }}
                            </td>
                            <td style="padding:11px 14px;color:var(--color-mid-grey);font-size:12px;">
                                {{ $poi->town?->name ?? '—' }}
                            </td>
                            <td style="padding:11px 14px;color:var(--color-mid-grey);font-size:12px;">
                                {{ $poi->state }}
                            </td>
                            <td style="padding:11px 14px;color:var(--color-mid-grey);font-size:12px;">
                                {{ is_null($poi->detour_km) ? 'On route' : ($poi->detour_km . ' km') }}
                            </td>
                            <td style="padding:11px 14px;">
                                @php
                                    $badge = match ($poi->status) {
                                        'published' => ['bg' => 'var(--badge-in-progress-bg)', 'text' => 'var(--badge-in-progress-text)', 'label' => 'Published'],
                                        'pending' => ['bg' => 'var(--badge-saved-bg)', 'text' => 'var(--badge-saved-text)', 'label' => 'Pending'],
                                        default => ['bg' => 'var(--badge-draft-bg)', 'text' => 'var(--badge-draft-text)', 'label' => 'Draft'],
                                    };
                                @endphp
                                <span style="display:inline-block;font-size:10px;font-weight:600;padding:2px 7px;border-radius:4px;background:{{ $badge['bg'] }};color:{{ $badge['text'] }};">
                                    {{ $badge['label'] }}
                                </span>
                            </td>
                            <td style="padding:11px 14px;color:var(--color-mid-grey);font-size:12px;">
                                <div style="font-weight:600;color:var(--color-charcoal);">{{ $poi->verification_status->label() }}</div>
                                @if ($poi->verified_at)
                                    <div style="font-size:11px;margin-top:2px;">{{ $poi->verified_at->format('j M Y') }}</div>
                                @else
                                    <div style="font-size:11px;margin-top:2px;">—</div>
                                @endif
                            </td>
                            <td style="padding:11px 14px;">
                                <div style="display:flex;gap:6px;justify-content:flex-end;">
                                    <a class="btn btn-neutral btn-sm" href="{{ route('admin.pois.edit', $poi) }}">Edit</a>
                                    <form method="post" action="{{ route('admin.pois.destroy', $poi) }}">
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
                                No POIs yet. Create a town first, then <a href="{{ route('admin.pois.create') }}" style="color:var(--color-bush-green);text-decoration:none;font-weight:600;">add your first POI →</a>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>

            <div style="padding:14px 16px;border-top:1px solid var(--color-border);background:var(--color-white);">
                {{ $pois->links() }}
            </div>
        </div>
    </div>
@endsection

