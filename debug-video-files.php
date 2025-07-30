<?php

echo "=== Debug Video Files ===\n\n";

// Check latest videos in database
$dbFile = 'database/database.sqlite';
if (!file_exists($dbFile)) {
    echo "Database file not found: $dbFile\n";
    exit;
}

try {
    $pdo = new PDO("sqlite:$dbFile");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Get latest 5 videos
    $stmt = $pdo->query("SELECT id, title, file_name, file_path, created_at FROM generated_videos ORDER BY created_at DESC LIMIT 5");
    $videos = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($videos)) {
        echo "No videos found in database.\n";
        exit;
    }
    
    echo "Latest videos:\n";
    foreach ($videos as $video) {
        echo "\n--- Video ID: {$video['id']} ---\n";
        echo "Title: {$video['title']}\n";
        echo "File name: {$video['file_name']}\n";
        echo "File path: {$video['file_path']}\n";
        echo "Created: {$video['created_at']}\n";
        echo "File exists: " . (file_exists($video['file_path']) ? 'YES' : 'NO') . "\n";
        
        if (!file_exists($video['file_path'])) {
            // Check possible locations
            $possiblePaths = [
                "storage/app/videos/{$video['file_name']}",
                "storage/app/tiktok_videos/{$video['file_name']}",
                "storage/app/youtube_videos/{$video['file_name']}",
                "storage/app/temp/{$video['file_name']}",
            ];
            
            echo "Checking possible locations:\n";
            foreach ($possiblePaths as $path) {
                echo "  - $path: " . (file_exists($path) ? 'EXISTS' : 'NOT FOUND') . "\n";
            }
        }
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}

echo "\n=== Debug Complete ===\n";
?>
