@php
    use Illuminate\Support\Str;
    /** @var \App\Models\Poi $poi */
    /** @var \App\Models\PoiPhoto $photo */
    /** @var string $layout hero|compact */
    $photoEditId = session('photo_edit_id');
    $useOld = (int) $photoEditId === (int) $photo->id;
    $captionVal = $useOld ? old('caption', $photo->caption) : $photo->caption;
    $sourceVal = $useOld ? old('source', $photo->source) : $photo->source;
    $captionPlain = trim(strip_tags((string) $captionVal));
    $imgAlt = $captionPlain !== '' ? Str::limit($captionPlain, 160) : 'POI photo';
@endphp

@if ($layout === 'hero')
    <article class="town-photo-card town-photo-card--{{ $layout }}">
        <div class="town-photo-frame town-photo-frame--{{ $layout }}">
            <img
                class="town-photo-frame-img"
                src="{{ $photo->publicUrl() }}"
                alt="{{ $imgAlt }}"
                loading="lazy"
                decoding="async"
            />
        </div>

        @if ($photo->is_primary)
            <div class="town-photo-card-head">
                <span class="town-photo-primary-badge">Main image</span>
            </div>
        @endif

        <form method="post" action="{{ route('admin.pois.photos.update', [$poi, $photo]) }}" class="town-photo-meta-form">
            @csrf
            @method('patch')
            <div class="town-photo-field">
                <label class="town-photo-field-label" for="poi-photo-caption-{{ $photo->id }}">Caption</label>
                <textarea
                    id="poi-photo-caption-{{ $photo->id }}"
                    name="caption"
                    rows="3"
                    class="town-photo-field-input town-photo-field-input--textarea"
                    placeholder="Describe this image…"
                >{{ $captionVal }}</textarea>
            </div>
            <div class="town-photo-field">
                <label class="town-photo-field-label" for="poi-photo-source-{{ $photo->id }}">Source</label>
                <input
                    id="poi-photo-source-{{ $photo->id }}"
                    type="text"
                    name="source"
                    value="{{ $sourceVal }}"
                    class="town-photo-field-input"
                    placeholder="Credit, copyright, or where it came from"
                    maxlength="500"
                />
            </div>
            <div class="town-photo-meta-actions">
                <input class="btn btn-primary btn-sm" type="submit" value="Save caption &amp; source" />
            </div>
        </form>

        <div class="town-photo-card-actions">
            @if (! $photo->is_primary)
                <form method="post" action="{{ route('admin.pois.photos.primary', [$poi, $photo]) }}">
                    @csrf
                    <input class="btn btn-neutral btn-sm" type="submit" value="Set as main" />
                </form>
            @endif
            <form method="post" action="{{ route('admin.pois.photos.destroy', [$poi, $photo]) }}" onsubmit="return confirm('Remove this photo? This cannot be undone.');">
                @csrf
                @method('delete')
                <input class="btn btn-neutral btn-sm" type="submit" value="Remove" />
            </form>
        </div>
    </article>
@else
    <article
        class="town-photo-card town-photo-card--compact town-photo-card--thumb"
        x-data="{ modalOpen: @js($useOld) }"
        @keydown.escape.window="if (modalOpen) { modalOpen = false }"
    >
        <button
            type="button"
            class="town-photo-thumb-btn"
            @click="modalOpen = true"
            aria-haspopup="dialog"
            :aria-expanded="modalOpen ? 'true' : 'false'"
            aria-controls="poi-photo-dialog-{{ $photo->id }}"
        >
            <div class="town-photo-frame town-photo-frame--compact">
                <img
                    class="town-photo-frame-img"
                    src="{{ $photo->publicUrl() }}"
                    alt="{{ $imgAlt }}"
                    loading="lazy"
                    decoding="async"
                />
            </div>
            <span class="town-photo-thumb-hint">Click to edit</span>
        </button>

        <template x-teleport="body">
            <div
                x-show="modalOpen"
                x-transition.opacity.duration.200ms
                x-cloak
                class="town-photo-modal-layer"
                id="poi-photo-dialog-{{ $photo->id }}"
                role="dialog"
                aria-modal="true"
                aria-labelledby="poi-photo-dialog-title-{{ $photo->id }}"
            >
                <div class="town-photo-modal-backdrop" @click="modalOpen = false" aria-hidden="true"></div>
                <div class="town-photo-modal-dialog">
                    <div class="town-photo-modal-head">
                        <h2 class="town-photo-modal-title" id="poi-photo-dialog-title-{{ $photo->id }}">Edit photo</h2>
                        <button type="button" class="town-photo-modal-close" @click="modalOpen = false" aria-label="Close dialog">×</button>
                    </div>

                    <div class="town-photo-modal-preview town-photo-frame town-photo-frame--compact">
                        <img
                            class="town-photo-frame-img"
                            src="{{ $photo->publicUrl() }}"
                            alt=""
                            loading="lazy"
                            decoding="async"
                        />
                    </div>

                    <form method="post" action="{{ route('admin.pois.photos.update', [$poi, $photo]) }}" class="town-photo-meta-form">
                        @csrf
                        @method('patch')
                        <div class="town-photo-field">
                            <label class="town-photo-field-label" for="poi-photo-caption-modal-{{ $photo->id }}">Caption</label>
                            <textarea
                                id="poi-photo-caption-modal-{{ $photo->id }}"
                                name="caption"
                                rows="3"
                                class="town-photo-field-input town-photo-field-input--textarea"
                                placeholder="Describe this image…"
                            >{{ $captionVal }}</textarea>
                        </div>
                        <div class="town-photo-field">
                            <label class="town-photo-field-label" for="poi-photo-source-modal-{{ $photo->id }}">Source</label>
                            <input
                                id="poi-photo-source-modal-{{ $photo->id }}"
                                type="text"
                                name="source"
                                value="{{ $sourceVal }}"
                                class="town-photo-field-input"
                                placeholder="Credit, copyright, or where it came from"
                                maxlength="500"
                            />
                        </div>
                        <div class="town-photo-meta-actions">
                            <input class="btn btn-primary btn-sm" type="submit" value="Save caption &amp; source" />
                        </div>
                    </form>

                    <div class="town-photo-card-actions town-photo-modal-actions">
                        <form method="post" action="{{ route('admin.pois.photos.primary', [$poi, $photo]) }}">
                            @csrf
                            <input class="btn btn-neutral btn-sm" type="submit" value="Set as main" />
                        </form>
                        <form method="post" action="{{ route('admin.pois.photos.destroy', [$poi, $photo]) }}" onsubmit="return confirm('Remove this photo? This cannot be undone.');">
                            @csrf
                            @method('delete')
                            <input class="btn btn-neutral btn-sm" type="submit" value="Remove" />
                        </form>
                    </div>
                </div>
            </div>
        </template>
    </article>
@endif
