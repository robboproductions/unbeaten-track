<?php

namespace App\Services;

use App\Models\Poi;
use App\Models\PoiPhoto;
use Illuminate\Http\UploadedFile;

final class PoiPhotoService
{
    /**
     * @param  array<int, UploadedFile|null>  $files
     */
    public function attachUploads(Poi $poi, array $files): void
    {
        $files = array_values(array_filter($files, fn ($f) => $f instanceof UploadedFile && $f->isValid()));
        if ($files === []) {
            return;
        }

        $hasPrimary = $poi->photos()->where('is_primary', true)->exists();
        $sortBase = (int) $poi->photos()->max('sort_order');

        foreach ($files as $i => $file) {
            $path = $file->store("pois/{$poi->id}", 'public');
            $sortBase++;
            $poi->photos()->create([
                'path' => $path,
                'is_primary' => ! $hasPrimary && $i === 0,
                'sort_order' => $sortBase,
            ]);
            if (! $hasPrimary && $i === 0) {
                $hasPrimary = true;
            }
        }
    }

    public function setPrimary(Poi $poi, PoiPhoto $photo): void
    {
        abort_unless($photo->poi_id === $poi->id, 404);

        $poi->photos()->update(['is_primary' => false]);
        $photo->update(['is_primary' => true]);
    }

    public function deletePhoto(Poi $poi, PoiPhoto $photo): void
    {
        abort_unless($photo->poi_id === $poi->id, 404);

        $wasPrimary = $photo->is_primary;
        $photo->delete();

        if ($wasPrimary) {
            $next = $poi->photos()->orderBy('sort_order')->orderBy('id')->first();
            $next?->update(['is_primary' => true]);
        }
    }
}
