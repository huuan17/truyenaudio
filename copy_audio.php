<?php

// Copy audio files from storage to public/storage
$sourceDir = 'storage/truyen/mp3-tien-nghich';
$targetDir = 'public/storage/truyen/mp3-tien-nghich';

// Create target directory if not exists
if (!is_dir($targetDir)) {
    mkdir($targetDir, 0755, true);
    echo "Created directory: $targetDir\n";
}

// Copy all MP3 files
$files = glob($sourceDir . '/*.mp3');
foreach ($files as $file) {
    $filename = basename($file);
    $target = $targetDir . '/' . $filename;
    
    if (copy($file, $target)) {
        echo "Copied: $filename\n";
    } else {
        echo "Failed to copy: $filename\n";
    }
}

echo "Audio copy completed!\n";
