<?php

namespace Tests\Unit;

use App\Support\NarrationVoiceCatalog;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class NarrationVoiceCatalogTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config([
            'poi_narration.voices' => [
                'terry' => ['id' => 'vid-terry', 'label' => 'Terry'],
                'sarah' => ['id' => 'vid-sarah', 'label' => 'Sarah'],
            ],
        ]);
    }

    public function test_narrator_portrait_slug_matches_voice_id(): void
    {
        $this->assertSame('terry', NarrationVoiceCatalog::narratorPortraitSlug('vid-terry', null));
        $this->assertSame('sarah', NarrationVoiceCatalog::narratorPortraitSlug('vid-sarah', null));
    }

    #[DataProvider('legacyLabelProvider')]
    public function test_narrator_portrait_slug_falls_back_to_legacy_labels(?string $voiceId, ?string $stored, string $expected): void
    {
        $this->assertSame($expected, NarrationVoiceCatalog::narratorPortraitSlug($voiceId, $stored));
    }

    /**
     * @return iterable<string, array{?string, ?string, string}>
     */
    public static function legacyLabelProvider(): iterable
    {
        yield 'baxter label' => [null, 'Baxter', 'terry'];
        yield 'zoe label' => [null, 'Zoe', 'sarah'];
    }

    public function test_narrator_intro_url_matches_slug(): void
    {
        $this->assertStringContainsString(
            'audio/narrator-intros/terry_intro.mp3',
            NarrationVoiceCatalog::narratorIntroUrl('vid-terry', null) ?? ''
        );
        $this->assertStringContainsString(
            'audio/narrator-intros/sarah_intro.mp3',
            NarrationVoiceCatalog::narratorIntroUrl('vid-sarah', null) ?? ''
        );
    }

    public function test_narrator_intro_url_unknown_returns_null(): void
    {
        $this->assertNull(NarrationVoiceCatalog::narratorIntroUrl('x', null));
    }

    public function test_narrators_for_showcase_lists_config_voices_in_order(): void
    {
        $list = NarrationVoiceCatalog::narratorsForShowcase();
        $this->assertCount(2, $list);
        $this->assertSame('sarah', $list[0]['slug']);
        $this->assertSame('Sarah', $list[0]['label']);
        $this->assertNotNull($list[0]['portrait_url']);
        $this->assertNotNull($list[0]['intro_url']);
        $this->assertSame('terry', $list[1]['slug']);
        $this->assertSame('Terry', $list[1]['label']);
    }

    public function test_narrator_portrait_slug_unknown_returns_null(): void
    {
        $this->assertNull(NarrationVoiceCatalog::narratorPortraitSlug('unknown-id', null));
    }
}
