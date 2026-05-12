<?php

namespace App\Services;

use App\Models\Town;
use App\Models\TownPhoto;
use Illuminate\Http\UploadedFile;

final class TownPhotoService
{
    /**
     * @param  array<int, UploadedFile|null>  $files
     */
    public function attachUploads(Town $town, array $files): void
    {
        $files = array_values(array_filter($files, fn ($f) => $f instanceof UploadedFile && $f->isValid()));
        if ($files === []) {
            return;
        }

        $hasPrimary = $town->photos()->where('is_primary', true)->exists();
        $sortBase = (int) $town->photos()->max('sort_order');

        foreach ($files as $i => $file) {
            $path = $file->store("towns/{$town->id}", 'public');
            $sortBase++;
            $town->photos()->create([
                'path' => $path,
                'is_primary' => ! $hasPrimary && $i === 0,
                'sort_order' => $sortBase,
            ]);
            if (! $hasPrimary && $i === 0) {
                $hasPrimary = true;
            }
        }
    }

    public function setPrimary(Town $town, TownPhoto $photo): void
    {
        abort_unless($photo->town_id === $town->id, 404);

        $town->photos()->update(['is_primary' => false]);
        $photo->update(['is_primary' => true]);
    }

    public function deletePhoto(Town $town, TownPhoto $photo): void
    {
        abort_unless($photo->town_id === $town->id, 404);

        $wasPrimary = $photo->is_primary;
        $photo->delete();

        if ($wasPrimary) {
            $next = $town->photos()->orderBy('sort_order')->orderBy('id')->first();
            $next?->update(['is_primary' => true]);
        }
    }
}
