<?php

namespace App\Services\Narration\Contracts;

use App\Services\Narration\NarrationResult;

interface NarrationProvider
{
    public function synthesize(string $script, string $voiceId, string $modelId): NarrationResult;
}
