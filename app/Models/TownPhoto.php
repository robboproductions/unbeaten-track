<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class TownPhoto extends Model
{
    protected $fillable = [
        'town_id',
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
        static::deleting(function (TownPhoto $photo): void {
            if ($photo->path && Storage::disk('public')->exists($photo->path)) {
                Storage::disk('public')->delete($photo->path);
            }
        });
    }

    public function town(): BelongsTo
    {
        return $this->belongsTo(Town::class);
    }

    public function publicUrl(): string
    {
        $path = ltrim(str_replace('\\', '/', (string) $this->path), '/');
        $base = request()->getBasePath();

        return rtrim($base, '/').'/storage/'.$path;
    }
}
