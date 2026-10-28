<?php
$dir = dirname(__DIR__) . '/vms';
$files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir));

$emojiMap = [];
foreach ($files as $f) {
    if (!$f->isFile()) continue;
    $ext = $f->getExtension();
    if (!in_array($ext, ['php', 'html', 'js', 'css'])) continue;

    $path = $f->getPathname();
    $lines = file($path);
    foreach ($lines as $lineNum => $line) {
        if (preg_match_all('/&#12[0-9]{4};/u', $line, $matches)) {
            foreach ($matches[0] as $match) {
                $emojiMap[$match][] = basename($path) . ':' . ($lineNum + 1);
            }
        }
        if (preg_match_all('/[\x{1F300}-\x{1F9FF}]/u', $line, $matches)) {
            foreach ($matches[0] as $match) {
                $emojiMap[$match][] = basename($path) . ':' . ($lineNum + 1);
            }
        }
    }
}

foreach ($emojiMap as $emoji => $occurrences) {
    echo "$emoji (" . count($occurrences) . " times): " . implode(', ', array_slice($occurrences, 0, 5)) . "\n";
}
