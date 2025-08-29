<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make('Illuminate\\Contracts\\Console\\Kernel')->bootstrap();

$rel = $argv[1] ?? '';
if ($rel === '') { fwrite(STDERR, "Usage: php scripts/check_storage_path.php <rel_path>\n"); exit(2); }
$abs = storage_path('app/' . ltrim(str_replace('\\','/', $rel), '/'));
echo json_encode([
  'rel' => $rel,
  'abs' => $abs,
  'exists' => file_exists($abs),
  'size' => file_exists($abs) ? filesize($abs) : null,
], JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE) . "\n";
