<?php

namespace App\Services;

use Symfony\Component\HtmlSanitizer\HtmlSanitizer;
use Symfony\Component\HtmlSanitizer\HtmlSanitizerConfig;

final class TownAboutHtmlSanitizer
{
    private static ?HtmlSanitizer $instance = null;

    public function sanitize(?string $html): string
    {
        if ($html === null || $html === '') {
            return '';
        }

        return self::sanitizer()->sanitize($html);
    }

    private static function sanitizer(): HtmlSanitizer
    {
        if (self::$instance instanceof HtmlSanitizer) {
            return self::$instance;
        }

        $config = (new HtmlSanitizerConfig)
            ->withMaxInputLength(500_000)
            ->allowElement('p', ['class'])
            ->allowElement('br', [])
            ->allowElement('strong', [])
            ->allowElement('b', [])
            ->allowElement('em', [])
            ->allowElement('i', [])
            ->allowElement('ul', ['class'])
            ->allowElement('ol', ['class'])
            ->allowElement('li', ['class'])
            ->allowElement('h2', [])
            ->allowElement('h3', [])
            ->allowElement('h4', [])
            ->allowElement('blockquote', ['cite'])
            ->allowElement('a', ['href', 'title', 'rel'])
            ->allowLinkSchemes(['http', 'https']);

        self::$instance = new HtmlSanitizer($config);

        return self::$instance;
    }
}
