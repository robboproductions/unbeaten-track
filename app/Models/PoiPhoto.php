<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class PoiPhoto extends Model
{
    protected $fillable = [
        'poi_id',
        'path',
        'caption',
        'source',
        'is_primary',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'is_primary' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        static::deleting(function (PoiPhoto $photo): void {
            if ($photo->path && Storage::disk('public')->exists($photo->path)) {
                Storage::disk('public')->delete($photo->path);
            }
        });
    }

    public function poi(): BelongsTo
    {
        return $this->belongsTo(Poi::class, 'poi_id');
    }

    public function publicUrl(): string
    {
        $path = ltrim(str_replace('\\', '/', (string) $this->path), '/');
        $base = request()->getBasePath();

        return rtrim($base, '/').'/storage/'.$path;
    }
}
