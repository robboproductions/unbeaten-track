<?php

namespace App\Models;

use App\Enums\PoiVerificationStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class Poi extends Model
{
    protected $fillable = [
        'country',
        'name',
        'categories',
        'town_id',
        'state',
        'status',
        'verification_status',
        'verified_at',
        'short_description',
        'latitude',
        'longitude',
        'about_html',
        'narration_script',
        'spreadsheet_notes',
    ];

    protected function casts(): array
    {
        return [
            'categories' => 'array',
            'verification_status' => PoiVerificationStatus::class,
            'verified_at' => 'date',
            'latitude' => 'decimal:7',
            'longitude' => 'decimal:7',
            'published_at' => 'datetime',
            'narration_generated_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (Poi $poi): void {
            if (blank($poi->country)) {
                $poi->country = config('australia_geography.country_default', 'AU');
            }

            if (! $poi->exists) {
                if ($poi->status === 'published') {
                    $poi->published_at = now();
                    $poi->published_by = Auth::id();
                }

                return;
            }

            if ($poi->isDirty('status')) {
                if ($poi->status === 'published') {
                    $poi->published_at = now();
                    $poi->published_by = Auth::id();
                } else {
                    $poi->published_at = null;
                    $poi->published_by = null;
                }
            }
        });

        static::deleting(function (Poi $poi): void {
            foreach ($poi->photos as $photo) {
                $photo->delete();
            }

            if (filled($poi->narration_audio_path)) {
                $disk = Storage::disk((string) config('poi_narration.storage.disk', 'public'));
                if ($disk->exists($poi->narration_audio_path)) {
                    $disk->delete($poi->narration_audio_path);
                }
            }
        });
    }

    public function town(): BelongsTo
    {
        return $this->belongsTo(Town::class);
    }

    public function photos(): HasMany
    {
        return $this->hasMany(PoiPhoto::class);
    }

    public function primaryPhoto(): HasOne
    {
        return $this->hasOne(PoiPhoto::class)->where('is_primary', true);
    }

    public function publishedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'published_by');
    }

    public function narrationGeneratedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'narration_generated_by');
    }

    public function getNarrationAudioUrlAttribute(): ?string
    {
        if (! filled($this->narration_audio_path)) {
            return null;
        }

        $relative = ltrim(str_replace('\\', '/', (string) $this->narration_audio_path), '/');

        // Root-relative URL so the browser always loads from the same host as the admin UI.
        // (Storage::disk()->url() uses APP_URL from config; a mismatched APP_URL breaks <audio> and shows 0:00.)
        $url = '/storage/'.$relative;

        if ($this->narration_generated_at !== null) {
            $url .= '?v='.$this->narration_generated_at->getTimestamp();
        }

        return $url;
    }

    public function getHasNarrationAttribute(): bool
    {
        return filled($this->narration_audio_path);
    }

    /**
     * @return list<string>
     */
    public function categoryList(): array
    {
        $c = $this->categories;

        return is_array($c) ? array_values(array_filter($c, fn ($v) => is_string($v) && $v !== '')) : [];
    }

    public function hasValidCoordinates(): bool
    {
        if ($this->latitude === null || $this->longitude === null) {
            return false;
        }

        $lat = (float) $this->latitude;
        $lng = (float) $this->longitude;

        return $lat >= -90.0 && $lat <= 90.0 && $lng >= -180.0 && $lng <= 180.0;
    }
}
