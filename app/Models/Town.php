<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Facades\Auth;

class Town extends Model
{
    protected $fillable = [
        'country',
        'name',
        'state',
        'region',
        'status',
        'verification_status',
        'population_approx',
        'latitude',
        'longitude',
        'has_pub',
        'has_cafe',
        'has_shop',
        'has_fuel',
        'has_caravan_park',
        'editorial_hook',
        'about_html',
        'likely_poi_categories',
        'suggested_corridor',
        'spreadsheet_notes',
    ];

    protected function casts(): array
    {
        return [
            'published_at' => 'datetime',
            'verified_at' => 'datetime',
            'population_approx' => 'integer',
            'latitude' => 'decimal:7',
            'longitude' => 'decimal:7',
            'has_pub' => 'boolean',
            'has_cafe' => 'boolean',
            'has_shop' => 'boolean',
            'has_fuel' => 'boolean',
            'has_caravan_park' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (Town $town): void {
            if (blank($town->country)) {
                $town->country = config('australia_geography.country_default', 'AU');
            }

            if (! $town->exists) {
                if ($town->status === 'published') {
                    $town->published_at = now();
                    $town->published_by = Auth::id();
                }
                if (($town->verification_status ?? 'unverified') !== 'unverified') {
                    $town->verified_at = now();
                    $town->verified_by = Auth::id();
                }

                return;
            }

            if ($town->isDirty('status')) {
                if ($town->status === 'published') {
                    $town->published_at = now();
                    $town->published_by = Auth::id();
                } else {
                    $town->published_at = null;
                    $town->published_by = null;
                }
            }

            if ($town->isDirty('verification_status')) {
                $town->verified_at = now();
                $town->verified_by = Auth::id();
            }
        });

        static::deleting(function (Town $town): void {
            foreach ($town->photos as $photo) {
                $photo->delete();
            }
        });
    }

    public function photos(): HasMany
    {
        return $this->hasMany(TownPhoto::class);
    }

    public function primaryPhoto(): HasOne
    {
        return $this->hasOne(TownPhoto::class)->where('is_primary', true);
    }

    public function pois(): HasMany
    {
        return $this->hasMany(Poi::class);
    }

    public function publishedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'published_by');
    }

    public function verifiedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }
}
