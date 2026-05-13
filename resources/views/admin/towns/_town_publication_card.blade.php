@php
    /** @var \App\Models\Town|null $town */
    /** @var string $mainFormId */
    $tz = config('app.timezone', 'UTC');
    $verificationStatuses = config('town_verification.statuses', []);
@endphp

<div class="town-sidebar-card">
    <div class="town-sidebar-card-title">Publication</div>

    <div class="town-sidebar-card-field">
        <label class="town-form-label" for="town_publication_status">Status</label>
        <select
            id="town_publication_status"
            name="status"
            form="{{ $mainFormId }}"
            class="town-form-control town-form-control--select"
        >
            <option value="draft" @selected(old('status', $town?->status ?? 'draft') === 'draft')>Draft</option>
            <option value="pending" @selected(old('status', $town?->status ?? 'draft') === 'pending')>Pending</option>
            <option value="published" @selected(old('status', $town?->status ?? 'draft') === 'published')>Published</option>
        </select>
        @error('status')<p class="town-form-error">{{ $message }}</p>@enderror
    </div>

    <div class="town-sidebar-meta-block">
        <div class="town-sidebar-meta-label">Published</div>
        <div class="town-sidebar-meta-value">
            @if ($town && $town->published_at)
                {{ $town->published_at->timezone($tz)->format('j M Y, g:i a') }}
                @if ($town->relationLoaded('publishedByUser') && $town->publishedByUser)
                    <span class="town-sidebar-meta-by">· {{ $town->publishedByUser->name }}</span>
                @elseif ($town->published_by)
                    <span class="town-sidebar-meta-by">· User #{{ $town->published_by }}</span>
                @endif
            @else
                —
            @endif
        </div>
    </div>

    <div class="town-sidebar-card-field">
        <label class="town-form-label" for="town_verification_status">Verification</label>
        <select
            id="town_verification_status"
            name="verification_status"
            form="{{ $mainFormId }}"
            class="town-form-control town-form-control--select"
        >
            @foreach ($verificationStatuses as $value => $label)
                <option value="{{ $value }}" @selected(old('verification_status', $town?->verification_status ?? 'unverified') === $value)>{{ $label }}</option>
            @endforeach
        </select>
        @error('verification_status')<p class="town-form-error">{{ $message }}</p>@enderror
    </div>

    <div class="town-sidebar-meta-block">
        <div class="town-sidebar-meta-label">Last verified</div>
        <div class="town-sidebar-meta-value">
            @if ($town && $town->verified_at)
                {{ $town->verified_at->timezone($tz)->format('j M Y, g:i a') }}
                @if ($town->relationLoaded('verifiedByUser') && $town->verifiedByUser)
                    <span class="town-sidebar-meta-by">· {{ $town->verifiedByUser->name }}</span>
                @elseif ($town->verified_by)
                    <span class="town-sidebar-meta-by">· User #{{ $town->verified_by }}</span>
                @endif
            @else
                —
            @endif
        </div>
    </div>
</div>
