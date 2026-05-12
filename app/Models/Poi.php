<?php

namespace App\Models;

use App\Enums\PoiVerificationStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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
        'detour_km',
        'short_description',
    ];

    protected function casts(): array
    {
        return [
            'categories' => 'array',
            'verification_status' => PoiVerificationStatus::class,
            'verified_at' => 'date',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (Poi $poi): void {
            if (blank($poi->country)) {
                $poi->country = config('australia_geography.country_default', 'AU');
            }
        });
    }

    public function town(): BelongsTo
    {
        return $this->belongsTo(Town::class);
    }

    /**
     * @return list<string>
     */
    public function categoryList(): array
    {
        $c = $this->categories;

        return is_array($c) ? array_values(array_filter($c, fn ($v) => is_string($v) && $v !== '')) : [];
    }
}
