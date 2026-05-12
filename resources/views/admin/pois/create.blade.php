@extends('layouts.admin')

@section('title', 'Add POI · Admin')

@section('content')
    <div class="admin-page-header">
        <div>
            <div class="admin-page-title">Add POI</div>
            <div class="admin-page-subtitle">Create a new point of interest</div>
        </div>
        <div class="admin-page-actions">
            <a class="btn btn-neutral btn-sm" href="{{ route('admin.pois.index') }}">← Back to POIs</a>
        </div>
    </div>

    <div class="admin-content" style="padding-top:16px;">
        <div class="card">
            <form method="post" action="{{ route('admin.pois.store') }}" style="padding:16px;">
                @csrf

                <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;">
                    <div style="display:flex;flex-direction:column;gap:5px;grid-column:1 / -1;">
                        <label style="font-size:12px;font-weight:600;color:var(--color-near-black);">Name</label>
                        <input name="name" value="{{ old('name') }}" required style="background:var(--color-river-stone);border:1px solid var(--color-border);border-radius:var(--radius-md);padding:8px 12px;font-size:13px;color:var(--color-near-black);font-family:var(--font-family);outline:none;" />
                        @error('name')<div style="font-size:12px;color:var(--cat-deep-roots-text);">{{ $message }}</div>@enderror
                    </div>

                    <div style="display:flex;flex-direction:column;gap:5px;grid-column:1 / -1;">
                        <label style="font-size:12px;font-weight:600;color:var(--color-near-black);">Categories</label>
                        @php
                            $selCats = old('categories', ['Deep Roots']);
                            $selCats = is_array($selCats) ? $selCats : [$selCats];
                        @endphp
                        <select name="categories[]" multiple required size="5" style="background:var(--color-white);border:1px solid var(--color-border);border-radius:var(--radius-md);padding:8px 12px;font-size:13px;color:var(--color-near-black);font-family:var(--font-family);outline:none;width:100%;max-width:480px;min-height:120px;">
                            @foreach ($categoryOptions as $cat)
                                <option value="{{ $cat }}" @selected(in_array($cat, $selCats, true))>{{ $cat }}</option>
                            @endforeach
                        </select>
                        <span style="font-size:11px;color:var(--color-mid-grey);">Hold Ctrl (Windows) or ⌘ (Mac) to select more than one.</span>
                        @error('categories')<div style="font-size:12px;color:var(--cat-deep-roots-text);">{{ $message }}</div>@enderror
                        @error('categories.*')<div style="font-size:12px;color:var(--cat-deep-roots-text);">{{ $message }}</div>@enderror
                    </div>

                    <div style="display:flex;flex-direction:column;gap:5px;">
                        <label style="font-size:12px;font-weight:600;color:var(--color-near-black);">Town</label>
                        <select name="town_id" required style="background:var(--color-white);border:1px solid var(--color-border);border-radius:var(--radius-md);padding:8px 12px;font-size:13px;color:var(--color-near-black);font-family:var(--font-family);outline:none;cursor:pointer;">
                            <option value="" disabled @selected(old('town_id') === null)>Select a town…</option>
                            @foreach ($towns as $t)
                                <option value="{{ $t->id }}" @selected((string) old('town_id') === (string) $t->id)>{{ $t->name }} ({{ $t->state }})</option>
                            @endforeach
                        </select>
                        @error('town_id')<div style="font-size:12px;color:var(--cat-deep-roots-text);">{{ $message }}</div>@enderror
                    </div>

                    <div style="display:flex;flex-direction:column;gap:5px;">
                        <label style="font-size:12px;font-weight:600;color:var(--color-near-black);">State / territory</label>
                        <select name="state" required style="background:var(--color-white);border:1px solid var(--color-border);border-radius:var(--radius-md);padding:8px 12px;font-size:13px;color:var(--color-near-black);font-family:var(--font-family);outline:none;cursor:pointer;">
                            @foreach ($stateOptions as $st)
                                <option value="{{ $st }}" @selected(old('state', 'New South Wales') === $st)>{{ $st }}</option>
                            @endforeach
                        </select>
                        @error('state')<div style="font-size:12px;color:var(--cat-deep-roots-text);">{{ $message }}</div>@enderror
                    </div>

                    <div style="display:flex;flex-direction:column;gap:5px;">
                        <label style="font-size:12px;font-weight:600;color:var(--color-near-black);">Status</label>
                        <select name="status" style="background:var(--color-white);border:1px solid var(--color-border);border-radius:var(--radius-md);padding:8px 12px;font-size:13px;color:var(--color-near-black);font-family:var(--font-family);outline:none;cursor:pointer;">
                            <option value="draft" @selected(old('status', 'draft') === 'draft')>Draft</option>
                            <option value="pending" @selected(old('status') === 'pending')>Pending</option>
                            <option value="published" @selected(old('status') === 'published')>Published</option>
                        </select>
                        @error('status')<div style="font-size:12px;color:var(--cat-deep-roots-text);">{{ $message }}</div>@enderror
                    </div>

                    <div style="display:flex;flex-direction:column;gap:5px;">
                        <label style="font-size:12px;font-weight:600;color:var(--color-near-black);">Verification</label>
                        <select name="verification_status" style="background:var(--color-white);border:1px solid var(--color-border);border-radius:var(--radius-md);padding:8px 12px;font-size:13px;color:var(--color-near-black);font-family:var(--font-family);outline:none;cursor:pointer;">
                            @foreach (\App\Enums\PoiVerificationStatus::cases() as $vs)
                                <option value="{{ $vs->value }}" @selected(old('verification_status', \App\Enums\PoiVerificationStatus::NotVerified->value) === $vs->value)>{{ $vs->label() }}</option>
                            @endforeach
                        </select>
                        @error('verification_status')<div style="font-size:12px;color:var(--cat-deep-roots-text);">{{ $message }}</div>@enderror
                    </div>

                    <div style="display:flex;flex-direction:column;gap:5px;">
                        <label style="font-size:12px;font-weight:600;color:var(--color-near-black);">Verification date</label>
                        <input name="verified_at" type="date" value="{{ old('verified_at') }}" style="background:var(--color-white);border:1px solid var(--color-border);border-radius:var(--radius-md);padding:8px 12px;font-size:13px;color:var(--color-near-black);font-family:var(--font-family);outline:none;" />
                        @error('verified_at')<div style="font-size:12px;color:var(--cat-deep-roots-text);">{{ $message }}</div>@enderror
                    </div>

                    <div style="display:flex;flex-direction:column;gap:5px;">
                        <label style="font-size:12px;font-weight:600;color:var(--color-near-black);">Detour (km)</label>
                        <input name="detour_km" type="number" step="0.1" min="0" value="{{ old('detour_km') }}" style="background:var(--color-white);border:1px solid var(--color-border);border-radius:var(--radius-md);padding:8px 12px;font-size:13px;color:var(--color-near-black);font-family:var(--font-family);outline:none;" />
                        @error('detour_km')<div style="font-size:12px;color:var(--cat-deep-roots-text);">{{ $message }}</div>@enderror
                    </div>

                    <div style="display:flex;flex-direction:column;gap:5px;grid-column:1 / -1;">
                        <label style="font-size:12px;font-weight:600;color:var(--color-near-black);">Short description</label>
                        <input name="short_description" value="{{ old('short_description') }}" style="background:var(--color-river-stone);border:1px solid var(--color-border);border-radius:var(--radius-md);padding:8px 12px;font-size:13px;color:var(--color-near-black);font-family:var(--font-family);outline:none;" />
                        @error('short_description')<div style="font-size:12px;color:var(--cat-deep-roots-text);">{{ $message }}</div>@enderror
                    </div>
                </div>

                <div style="display:flex;gap:8px;margin-top:14px;">
                    <input class="btn btn-primary btn-sm" type="submit" value="Save POI" />
                    <a class="btn btn-neutral btn-sm" href="{{ route('admin.pois.index') }}">Cancel</a>
                </div>
            </form>
        </div>
    </div>
@endsection

