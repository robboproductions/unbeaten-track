<?php

$src = dirname(__DIR__) . '/resources/css/app.css';
$outDir = dirname(__DIR__) . '/public/css';
$out = $outDir . '/unbeaten-fallback.css';

if (! is_dir($outDir)) {
    mkdir($outDir, 0777, true);
}

$lines = file($src, FILE_IGNORE_NEW_LINES);
$hdr = "/* Unbeaten Track static fallback when Vite build is missing. Keep in sync with resources/css/app.css (tokens + components). */\n\n";

file_put_contents($out, $hdr.implode("\n", array_slice($lines, 12)));

echo "Wrote {$out}\n";
