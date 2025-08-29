<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make('Illuminate\\Contracts\\Console\\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

// MySQL-compatible: capitalize first character of trimmed name
$sql = "UPDATE foods SET name = CONCAT(UPPER(LEFT(TRIM(name),1)), SUBSTRING(TRIM(name),2)) WHERE name IS NOT NULL AND name <> ''";

try {
    $affected = DB::update($sql);
    echo "OK: Updated {$affected} rows\n";
} catch (\Throwable $e) {
    fwrite(STDERR, 'ERROR: ' . $e->getMessage() . "\n");
    exit(1);
}

