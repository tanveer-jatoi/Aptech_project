<?php
$vmsDir = dirname(__DIR__) . '/vms';

$replacements = [
    // Direct emoji entity replacements
    '&#128465;' => 'Delete',
    '&#128222;&nbsp;' => '',
    '&#128222; ' => '',
    '&#128222;' => '',
    '&#128205;&nbsp;' => '',
    '&#128205; ' => '',
    '&#128205;' => '',
    '&#128337;&nbsp;' => '',
    '&#128337; ' => '',
    '&#128337;' => '',
    '&#128438;&nbsp;' => '',
    '&#128438; ' => '',
    '&#128438;' => '',
    '&#128102;&nbsp;' => '',
    '&#128102; ' => '',
    '&#128102;' => '',
    '&#128106;&nbsp;' => '',
    '&#128106; ' => '',
    '&#128106;' => '',
    '&#128197;&nbsp;' => '',
    '&#128197; ' => '',
    '&#128197;' => '',
    '&#128202;&nbsp;' => '',
    '&#128202; ' => '',
    '&#128202;' => '',
    '&#127973;&nbsp;' => '',
    '&#127973; ' => '',
    '&#127973;' => '',
    '&#128137;&nbsp;' => '',
    '&#128137; ' => '',
    '&#128137;' => '',
    '&#128203;&nbsp;' => '',
    '&#128203; ' => '',
    '&#128203;' => '',
    '&#128274;&nbsp;' => '',
    '&#128274; ' => '',
    '&#128274;' => '',
    // Purple stat card icon in admin/dashboard.php
    'background-color: #f3e8ff; color: #7e22ce;' => 'background-color: #f0fdf4; color: #166534;',
];

$files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($vmsDir));
$changed = 0;

foreach ($files as $f) {
    if (!$f->isFile()) continue;
    $ext = $f->getExtension();
    if (!in_array($ext, ['php', 'html', 'js', 'css'])) continue;

    $path = $f->getPathname();
    $orig = file_get_contents($path);
    $content = $orig;

    foreach ($replacements as $search => $replace) {
        $content = str_replace($search, $replace, $content);
    }

    if ($content !== $orig) {
        file_put_contents($path, $content);
        echo "Updated: " . basename($path) . "\n";
        $changed++;
    }
}

echo "Total files updated: $changed\n";

