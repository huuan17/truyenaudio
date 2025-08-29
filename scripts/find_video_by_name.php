<?php
// Usage: php scripts/find_video_by_name.php 211112.mp4
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make('Illuminate\\Contracts\\Console\\Kernel')->bootstrap();

$targetName = $argv[1] ?? null;
if (!$targetName) {
    fwrite(STDERR, "Please provide a filename, e.g., 211112.mp4\n");
    exit(2);
}
$targetName = strtolower($targetName);
$base = storage_path('app');
$found = null;
$rii = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($base, FilesystemIterator::SKIP_DOTS));
foreach ($rii as $file) {
    if (!$file->isFile()) continue;
    if (strtolower($file->getFilename()) === $targetName) {
        $full = $file->getPathname();
        $found = $full;
        break;
    }
}
if ($found) {
    $rel = ltrim(str_replace('\\', '/', substr($found, strlen($base))), '/');
    echo $rel; // e.g., videos/211112.mp4
    exit(0);
}
fwrite(STDERR, "File not found under storage/app: {$targetName}\n");
exit(3);

