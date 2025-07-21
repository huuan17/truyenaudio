<?php

echo "=== Fixing FontAwesome Font Paths ===\n";

$cssFiles = [
    'public/assets/css/fontawesome-6.0.0.min.css',
    'public/assets/css/fontawesome-free.min.css'
];

foreach ($cssFiles as $cssFile) {
    if (file_exists($cssFile)) {
        echo "Processing: {$cssFile}\n";
        
        $content = file_get_contents($cssFile);
        
        // Replace CDN font URLs with local paths
        $patterns = [
            // CloudFlare CDN
            'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/webfonts/',
            'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/webfonts/',
            // JSDelivr CDN
            'https://cdn.jsdelivr.net/npm/@fortawesome/fontawesome-free/webfonts/',
            // Other common CDN patterns
            '../webfonts/',
            './webfonts/',
        ];
        
        foreach ($patterns as $pattern) {
            $content = str_replace($pattern, '../fonts/', $content);
        }
        
        // Additional pattern for relative paths
        $content = preg_replace('/url\(["\']?\.\.\/webfonts\//', 'url("../fonts/', $content);
        $content = preg_replace('/url\(["\']?webfonts\//', 'url("../fonts/', $content);
        
        file_put_contents($cssFile, $content);
        echo "✅ Updated font paths in {$cssFile}\n";
    } else {
        echo "❌ File not found: {$cssFile}\n";
    }
}

echo "\n=== Creating Custom FontAwesome CSS ===\n";

// Create a custom FontAwesome CSS with correct paths
$customFontAwesome = '
/* FontAwesome Local - Custom */
@font-face {
  font-family: "Font Awesome 6 Free";
  font-style: normal;
  font-weight: 900;
  font-display: block;
  src: url("../fonts/fa-solid-900.woff2") format("woff2");
}

@font-face {
  font-family: "Font Awesome 6 Free";
  font-style: normal;
  font-weight: 400;
  font-display: block;
  src: url("../fonts/fa-regular-400.woff2") format("woff2");
}

@font-face {
  font-family: "Font Awesome 6 Brands";
  font-style: normal;
  font-weight: 400;
  font-display: block;
  src: url("../fonts/fa-brands-400.woff2") format("woff2");
}

.fa, .fas, .far, .fab {
  font-family: "Font Awesome 6 Free", "Font Awesome 6 Brands";
  font-weight: 900;
  font-style: normal;
  font-variant: normal;
  text-rendering: auto;
  line-height: 1;
  -webkit-font-smoothing: antialiased;
  -moz-osx-font-smoothing: grayscale;
}

.far {
  font-weight: 400;
}

.fab {
  font-family: "Font Awesome 6 Brands";
  font-weight: 400;
}
';

// Save custom CSS
file_put_contents('public/assets/css/fontawesome-local.css', $customFontAwesome);
echo "✅ Created custom FontAwesome CSS: public/assets/css/fontawesome-local.css\n";

echo "\n✅ FontAwesome paths fixed!\n";
echo "\nRecommendation: Use fontawesome-local.css for better compatibility\n";

?>
