@php
    /** @var \App\Models\Poi|null $poi */
    /** @var string $mainFormId */
    $tz = config('app.timezone', 'UTC');
@endphp

<div class="town-sidebar-card">
    <div class="town-sidebar-card-title">Publication &amp; verification</div>

    <div class="town-sidebar-card-field">
        <label class="town-form-label" for="poi_publication_status">Status</label>
        <select
            id="poi_publication_status"
            name="status"
            form="{{ $mainFormId }}"
            class="town-form-control town-form-control--select"
        >
            <option value="draft" @selected(old('status', $poi?->status ?? 'draft') === 'draft')>Draft</option>
            <option value="pending" @selected(old('status', $poi?->status ?? 'draft') === 'pending')>Pending</option>
            <option value="published" @selected(old('status', $poi?->status ?? 'draft') === 'published')>Published</option>
        </select>
        @error('status')<p class="town-form-error">{{ $message }}</p>@enderror
    </div>

    <div class="town-sidebar-meta-block">
        <div class="town-sidebar-meta-label">Published</div>
        <div class="town-sidebar-meta-value">
            @if ($poi && $poi->published_at)
                {{ $poi->published_at->timezone($tz)->format('j M Y, g:i a') }}
                @if ($poi->relationLoaded('publishedByUser') && $poi->publishedByUser)
                    <span class="town-sidebar-meta-by">· {{ $poi->publishedByUser->name }}</span>
                @elseif ($poi->published_by)
                    <span class="town-sidebar-meta-by">· User #{{ $poi->published_by }}</span>
                @endif
            @else
                —
            @endif
        </div>
    </div>

    <div class="town-sidebar-card-field">
        <label class="town-form-label" for="poi_verification_status">Verification</label>
        <select
            id="poi_verification_status"
            name="verification_status"
            form="{{ $mainFormId }}"
            class="town-form-control town-form-control--select"
        >
            @foreach (\App\Enums\PoiVerificationStatus::cases() as $vs)
                <option value="{{ $vs->value }}" @selected(old('verification_status', $poi?->verification_status?->value ?? \App\Enums\PoiVerificationStatus::NotVerified->value) === $vs->value)>{{ $vs->label() }}</option>
            @endforeach
        </select>
        @error('verification_status')<p class="town-form-error">{{ $message }}</p>@enderror
    </div>

    <div class="town-sidebar-card-field">
        <label class="town-form-label" for="poi_verified_at">Verification date</label>
        <input
            id="poi_verified_at"
            name="verified_at"
            type="date"
            form="{{ $mainFormId }}"
            class="town-form-control"
            value="{{ old('verified_at', optional($poi?->verified_at)->format('Y-m-d')) }}"
        />
        @error('verified_at')<p class="town-form-error">{{ $message }}</p>@enderror
    </div>
</div>
