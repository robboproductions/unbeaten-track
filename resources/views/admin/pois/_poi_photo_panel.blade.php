@php
    /** @var \App\Models\Poi|null $poi */
    /** @var string|null $mainFormId */
    $mainFormId = $mainFormId ?? null;
    $ordered = $poi && $poi->relationLoaded('photos')
        ? $poi->photos
        : collect($poi?->photos ?? []);
    if (! $ordered instanceof \Illuminate\Support\Collection) {
        $ordered = collect($ordered->all());
    }
    $galleryPhotos = $ordered
        ->sort(function (\App\Models\PoiPhoto $a, \App\Models\PoiPhoto $b) {
            if ((bool) $a->is_primary !== (bool) $b->is_primary) {
                return $b->is_primary <=> $a->is_primary;
            }

            return [$a->sort_order, $a->id] <=> [$b->sort_order, $b->id];
        })
        ->values();
    $heroPhoto = $galleryPhotos->first();
    $gridPhotos = $galleryPhotos->slice(1)->values();
@endphp

<div class="town-photo-panel">
    <div class="town-photo-panel-title">Photos</div>
    <p class="town-photo-panel-hint">JPEG, PNG, WebP or GIF · up to 5&nbsp;MB each · up to 20 files per save.</p>

    <div class="town-photo-upload" x-data="poiPhotoUploader">
        <div class="town-photo-upload-label">Add images</div>

        <input
            x-ref="photoInput"
            id="poi_photos_input"
            type="file"
            name="photos[]"
            accept="image/jpeg,image/png,image/webp,image/gif,.jpg,.jpeg,.png,.webp,.gif"
            multiple
            class="town-photo-file-input-sr"
            x-on:change="syncSummary()"
            @if ($mainFormId) form="{{ $mainFormId }}" @endif
        />

        <div
            class="town-photo-dropzone"
            :class="{ 'town-photo-dropzone--active': dragActive }"
            x-on:dragover.prevent="dragActive = true"
            x-on:dragleave.prevent="dragActive = false"
            x-on:drop.prevent="dragActive = false; mergeFiles($event.dataTransfer.files)"
            x-on:click="$refs.photoInput.click()"
            role="button"
            tabindex="0"
            x-on:keydown.enter.prevent="$refs.photoInput.click()"
            x-on:keydown.space.prevent="$refs.photoInput.click()"
        >
            <span class="town-photo-dropzone-line">Drag and drop or <strong>click here</strong> to upload</span>
            <span class="town-photo-dropzone-meta" x-show="summary" x-text="summary" x-cloak></span>
        </div>

        @error('photos')<div class="town-photo-error">{{ $message }}</div>@enderror
        @error('photos.*')<div class="town-photo-error">{{ $message }}</div>@enderror
    </div>

    <div class="town-photo-gallery">
        @if ($errors->has('caption') || $errors->has('source'))
            <div class="town-photo-error town-photo-error--banner" role="alert">
                @error('caption')<div>{{ $message }}</div>@enderror
                @error('source')<div>{{ $message }}</div>@enderror
            </div>
        @endif

        @if (! $poi)
            <div class="town-photo-empty">Choose files above, then <strong>save the POI</strong>. After that you can set the main image and edit captions.</div>
        @elseif ($galleryPhotos->isEmpty())
            <div class="town-photo-empty">No photos yet — add some above, then save the POI.</div>
        @else
            @include('admin.pois._poi_photo_card', ['poi' => $poi, 'photo' => $heroPhoto, 'layout' => 'hero'])

            @if ($gridPhotos->isNotEmpty())
                <div class="town-photo-gallery-grid" role="list">
                    @foreach ($gridPhotos as $gridPhoto)
                        <div class="town-photo-gallery-cell" role="listitem">
                            @include('admin.pois._poi_photo_card', ['poi' => $poi, 'photo' => $gridPhoto, 'layout' => 'compact'])
                        </div>
                    @endforeach
                </div>
            @endif
        @endif
    </div>
</div>

@once
    <script>
        document.addEventListener('alpine:init', () => {
            const maxFiles = 20;
            const imageMime = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];

            function isImageFile(file) {
                if (imageMime.includes(file.type)) {
                    return true;
                }
                return /\.(jpe?g|png|webp|gif)$/i.test(file.name);
            }

            Alpine.data('poiPhotoUploader', () => ({
                dragActive: false,
                summary: '',

                init() {
                    this.syncSummary();
                },

                syncSummary() {
                    const input = this.$refs.photoInput;
                    const n = input?.files?.length ?? 0;
                    this.summary = n ? n + ' file' + (n === 1 ? '' : 's') + ' ready to save' : '';
                },

                mergeFiles(incomingList) {
                    const input = this.$refs.photoInput;
                    if (!input) {
                        return;
                    }

                    const dt = new DataTransfer();
                    for (let i = 0; i < input.files.length; i++) {
                        dt.items.add(input.files[i]);
                    }

                    for (let i = 0; i < incomingList.length; i++) {
                        const file = incomingList[i];
                        if (!isImageFile(file)) {
                            continue;
                        }
                        if (dt.items.length >= maxFiles) {
                            break;
                        }
                        dt.items.add(file);
                    }

                    input.files = dt.files;
                    this.syncSummary();
                },
            }));
        });
    </script>
@endonce
