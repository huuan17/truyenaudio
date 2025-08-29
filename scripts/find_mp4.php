<?php
// Find first .mp4 under storage/app (recursive) and print relative path from storage/app
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$base = storage_path('app');
$found = null;
$maxScan = 5000; $count = 0;
$rii = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($base, FilesystemIterator::SKIP_DOTS));
foreach ($rii as $file) {
    if ($count++ > $maxScan) break; // safety
    if (!$file->isFile()) continue;
    $ext = strtolower(pathinfo($file->getFilename(), PATHINFO_EXTENSION));
    if (in_array($ext, ['mp4','mov','m4v'])) {
        $full = $file->getPathname();
        // Skip very large files (> 500MB) for quick test
        if (@filesize($full) !== false && filesize($full) > 500 * 1024 * 1024) continue;
        $found = $full;
        break;
    }
}
if ($found) {
    $rel = ltrim(str_replace('\\', '/', substr($found, strlen($base))), '/');
    echo $rel; // e.g., videos/sample.mp4
    exit(0);
}
// fallback: check test/test.mp4
$test = $base . DIRECTORY_SEPARATOR . 'test' . DIRECTORY_SEPARATOR . 'test.mp4';
if (file_exists($test)) {
    echo 'test/test.mp4';
    exit(0);
}
// nothing found
fwrite(STDERR, "No MP4 file found under storage/app. Please add a small file at storage/app/test/test.mp4\n");
exit(2);

