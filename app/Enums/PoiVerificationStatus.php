<?php

namespace App\Enums;

enum PoiVerificationStatus: string
{
    case BeatenTrackVerified = 'beaten_track_verified';
    case CommunityVerified = 'community_verified';
    case AiVerified = 'ai_verified';
    case NotVerified = 'not_verified';

    public function label(): string
    {
        return match ($this) {
            self::BeatenTrackVerified => 'Beaten Track Verified',
            self::CommunityVerified => 'Community Verified',
            self::AiVerified => 'AI Verified',
            self::NotVerified => 'Not Verified',
        };
    }

    /**
     * @return array<string, string> value => label
     */
    public static function options(): array
    {
        $out = [];
        foreach (self::cases() as $case) {
            $out[$case->value] = $case->label();
        }

        return $out;
    }
}
