<?php

echo "=== Verifying FontAwesome Setup ===\n";

// Check CSS files
$cssFiles = [
    'public/assets/css/fontawesome-6.4.0-all.min.css',
    'public/assets/css/fontawesome-6.0.0-all.min.css',
    'public/assets/css/fontawesome-5.15.4-all.min.css',
];

echo "\n1. CSS Files:\n";
foreach ($cssFiles as $file) {
    if (file_exists($file)) {
        $size = round(filesize($file) / 1024, 2);
        echo "✅ {$file} ({$size} KB)\n";
        
        // Check if it contains icon definitions
        $content = file_get_contents($file);
        $iconCount = substr_count($content, '.fa-');
        echo "   → Contains ~{$iconCount} icon definitions\n";
        
        // Check for specific icons used in audio player
        $audioIcons = ['fa-step-backward', 'fa-step-forward', 'fa-play', 'fa-pause', 'fa-headphones'];
        $foundIcons = 0;
        foreach ($audioIcons as $icon) {
            if (strpos($content, $icon) !== false) {
                $foundIcons++;
            }
        }
        echo "   → Audio player icons: {$foundIcons}/" . count($audioIcons) . " found\n";
        
    } else {
        echo "❌ Missing: {$file}\n";
    }
}

// Check font files
$fontFiles = [
    'public/assets/fonts/fa-solid-900.woff2',
    'public/assets/fonts/fa-solid-900.woff',
    'public/assets/fonts/fa-solid-900.ttf',
    'public/assets/fonts/fa-regular-400.woff2',
    'public/assets/fonts/fa-regular-400.woff',
    'public/assets/fonts/fa-brands-400.woff2',
    'public/assets/fonts/fa-brands-400.woff',
];

echo "\n2. Font Files:\n";
$totalFontSize = 0;
foreach ($fontFiles as $file) {
    if (file_exists($file)) {
        $size = round(filesize($file) / 1024, 2);
        $totalFontSize += filesize($file);
        echo "✅ {$file} ({$size} KB)\n";
    } else {
        echo "❌ Missing: {$file}\n";
    }
}

echo "\nTotal font size: " . round($totalFontSize / 1024 / 1024, 2) . " MB\n";

// Check layout files
echo "\n3. Layout Files:\n";
$layouts = [
    'resources/views/layouts/frontend.blade.php',
    'resources/views/layouts/app.blade.php',
];

foreach ($layouts as $layout) {
    if (file_exists($layout)) {
        $content = file_get_contents($layout);
        
        if (strpos($content, 'fontawesome-6.4.0-all.min.css') !== false) {
            echo "✅ {$layout} → Using FontAwesome 6.4.0 (complete)\n";
        } elseif (strpos($content, 'fontawesome') !== false) {
            echo "⚠️ {$layout} → Using different FontAwesome version\n";
            
            // Extract which version
            preg_match('/fontawesome[^"\']*\.css/', $content, $matches);
            if ($matches) {
                echo "   → Current: {$matches[0]}\n";
            }
        } else {
            echo "❌ {$layout} → No FontAwesome found\n";
        }
    }
}

// Check specific icon usage in story template
echo "\n4. Audio Player Icons in Story Template:\n";
$storyTemplate = 'resources/views/frontend/story.blade.php';
if (file_exists($storyTemplate)) {
    $content = file_get_contents($storyTemplate);
    
    $audioPlayerIcons = [
        'fa-step-backward' => 'Previous Chapter',
        'fa-step-forward' => 'Next Chapter', 
        'fa-headphones' => 'Audio Section Header',
        'fa-play-circle' => 'Playlist Items',
        'fa-volume-up' => 'Audio Badge',
        'fa-list' => 'Playlist Toggle',
    ];
    
    foreach ($audioPlayerIcons as $icon => $description) {
        $count = substr_count($content, $icon);
        if ($count > 0) {
            echo "✅ {$icon} → {$description} ({$count} usage" . ($count > 1 ? 's' : '') . ")\n";
        } else {
            echo "❌ {$icon} → {$description} (not found)\n";
        }
    }
} else {
    echo "❌ Story template not found\n";
}

// Font path verification
echo "\n5. Font Path Verification:\n";
$cssFile = 'public/assets/css/fontawesome-6.4.0-all.min.css';
if (file_exists($cssFile)) {
    $content = file_get_contents($cssFile);
    
    // Check font URLs
    preg_match_all('/url\(["\']?([^"\']+\.woff2?)["\']?\)/', $content, $matches);
    if ($matches[1]) {
        echo "Font URLs found in CSS:\n";
        foreach (array_unique($matches[1]) as $fontUrl) {
            echo "  - {$fontUrl}\n";
            
            // Check if corresponding file exists
            $fontPath = 'public/assets/fonts/' . basename($fontUrl);
            if (file_exists($fontPath)) {
                echo "    ✅ File exists: {$fontPath}\n";
            } else {
                echo "    ❌ File missing: {$fontPath}\n";
            }
        }
    } else {
        echo "❌ No font URLs found in CSS\n";
    }
} else {
    echo "❌ Main CSS file not found\n";
}

echo "\n=== Summary ===\n";
echo "FontAwesome 6.4.0 setup should now be complete.\n";
echo "Icons in audio player should be visible.\n";
echo "\nTest URLs:\n";
echo "- Icons test: http://localhost:8000/test-icons\n";
echo "- Story with audio: http://localhost:8000/story/tien-nghich\n";
echo "- Admin dashboard: http://localhost:8000/admin\n";

echo "\nIf icons are still missing:\n";
echo "1. Check browser console for font loading errors\n";
echo "2. Verify font MIME types in server config\n";
echo "3. Clear browser cache\n";
echo "4. Check network tab for 404 errors on font files\n";

?>
