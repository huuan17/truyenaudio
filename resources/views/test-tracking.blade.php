<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test Tracking Codes</title>
    
    @php
        use App\Helpers\SettingHelper;
        $headCodes = SettingHelper::getHeadTrackingCodes();
        $bodyCodes = SettingHelper::getBodyTrackingCodes();
        $metaTags = SettingHelper::getMetaVerificationTags();
        $seoTags = SettingHelper::getHomeSeoTags();
    @endphp
    
    <!-- Meta Verification Tags -->
    {!! $metaTags !!}
    
    <!-- SEO Tags -->
    <meta name="description" content="{{ $seoTags['description'] }}">
    <meta name="keywords" content="{{ $seoTags['keywords'] }}">
    
    <!-- Head Tracking Codes -->
    {!! $headCodes !!}
    
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .debug-section { margin: 20px 0; padding: 15px; border: 1px solid #ddd; background: #f9f9f9; }
        pre { background: #f0f0f0; padding: 10px; overflow-x: auto; }
    </style>
</head>
<body>
    <!-- Body Tracking Codes -->
    {!! $bodyCodes !!}
    
    <h1>🧪 Test Tracking Codes</h1>
    
    <div class="debug-section">
        <h2>📊 SEO Tags</h2>
        <pre>{{ print_r($seoTags, true) }}</pre>
    </div>
    
    <div class="debug-section">
        <h2>🔍 Meta Verification Tags</h2>
        <pre>{{ $metaTags ?: 'No meta verification tags' }}</pre>
    </div>
    
    <div class="debug-section">
        <h2>📈 Head Tracking Codes</h2>
        <pre>{{ $headCodes ?: 'No head tracking codes' }}</pre>
    </div>
    
    <div class="debug-section">
        <h2>🎯 Body Tracking Codes</h2>
        <pre>{{ $bodyCodes ?: 'No body tracking codes' }}</pre>
    </div>
    
    <div class="debug-section">
        <h2>🧪 JavaScript Tests</h2>
        <button onclick="testGA()">Test Google Analytics</button>
        <button onclick="testGTM()">Test Google Tag Manager</button>
        <button onclick="testFBPixel()">Test Facebook Pixel</button>
        
        <div id="test-results" style="margin-top: 10px;"></div>
    </div>
    
    <script>
        function testGA() {
            if (typeof gtag !== 'undefined') {
                gtag('event', 'test_event', {
                    'event_category': 'test',
                    'event_label': 'manual_test'
                });
                showResult('✅ Google Analytics: gtag function available');
            } else {
                showResult('❌ Google Analytics: gtag function not found');
            }
        }
        
        function testGTM() {
            if (typeof dataLayer !== 'undefined') {
                dataLayer.push({
                    'event': 'test_event',
                    'test_category': 'manual_test'
                });
                showResult('✅ Google Tag Manager: dataLayer available');
            } else {
                showResult('❌ Google Tag Manager: dataLayer not found');
            }
        }
        
        function testFBPixel() {
            if (typeof fbq !== 'undefined') {
                fbq('track', 'ViewContent', {
                    content_name: 'Test Page'
                });
                showResult('✅ Facebook Pixel: fbq function available');
            } else {
                showResult('❌ Facebook Pixel: fbq function not found');
            }
        }
        
        function showResult(message) {
            const results = document.getElementById('test-results');
            results.innerHTML += '<div>' + message + '</div>';
        }
        
        // Auto-test on page load
        window.addEventListener('load', function() {
            console.log('🧪 Testing tracking codes...');
            
            // Check if tracking codes are loaded
            setTimeout(function() {
                console.log('Google Analytics (gtag):', typeof gtag !== 'undefined' ? '✅ Loaded' : '❌ Not loaded');
                console.log('Google Tag Manager (dataLayer):', typeof dataLayer !== 'undefined' ? '✅ Loaded' : '❌ Not loaded');
                console.log('Facebook Pixel (fbq):', typeof fbq !== 'undefined' ? '✅ Loaded' : '❌ Not loaded');
                
                // Show custom body code test
                console.log('Custom body code test message should appear above');
            }, 2000);
        });
    </script>
</body>
</html>
