<?php

namespace App\Services\Narration;

final class NarrationResult
{
    /**
     * @param  string  $audio  Raw binary audio
     */
    public function __construct(
        public string $audio,
        public string $mimeType,
        public ?int $durationSeconds,
        public int $bytes,
    ) {}
}
