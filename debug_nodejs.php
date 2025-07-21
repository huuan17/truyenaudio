<?php

echo "=== Debug Node.js Environment ===\n";

// Test Node.js availability
echo "1. Testing Node.js:\n";
$nodeVersion = shell_exec('node --version 2>&1');
if ($nodeVersion) {
    echo "  ✅ Node.js version: " . trim($nodeVersion) . "\n";
} else {
    echo "  ❌ Node.js not found\n";
    exit(1);
}

// Test npm
echo "\n2. Testing npm:\n";
$npmVersion = shell_exec('npm --version 2>&1');
if ($npmVersion) {
    echo "  ✅ npm version: " . trim($npmVersion) . "\n";
} else {
    echo "  ❌ npm not found\n";
}

// Test puppeteer installation
echo "\n3. Testing Puppeteer:\n";
$puppeteerCheck = shell_exec('npm list puppeteer 2>&1');
echo "  Puppeteer check: " . trim($puppeteerCheck) . "\n";

// Test simple Node.js script
echo "\n4. Testing simple Node.js script:\n";
$testScript = 'console.log("Hello from Node.js"); process.exit(0);';
file_put_contents('test_node.js', $testScript);

$output = shell_exec('node test_node.js 2>&1');
echo "  Output: " . trim($output) . "\n";

if (trim($output) === 'Hello from Node.js') {
    echo "  ✅ Node.js execution works\n";
} else {
    echo "  ❌ Node.js execution failed\n";
}

// Cleanup
unlink('test_node.js');

// Test crawl script existence
echo "\n5. Testing crawl script:\n";
$scriptPath = 'node_scripts/crawl_original_cjs.js';
if (file_exists($scriptPath)) {
    echo "  ✅ Script exists: {$scriptPath}\n";
    $scriptSize = round(filesize($scriptPath) / 1024, 2);
    echo "  Script size: {$scriptSize} KB\n";
} else {
    echo "  ❌ Script not found: {$scriptPath}\n";
}

// Test script syntax
echo "\n6. Testing script syntax:\n";
$syntaxCheck = shell_exec("node -c {$scriptPath} 2>&1");
if (empty(trim($syntaxCheck))) {
    echo "  ✅ Script syntax is valid\n";
} else {
    echo "  ❌ Script syntax error: " . trim($syntaxCheck) . "\n";
}

// Test with minimal parameters
echo "\n7. Testing script execution:\n";
$testCommand = "node {$scriptPath} \"https://example.com/\" 1 1 \"storage/app/temp/test_debug\" 1";
echo "  Command: {$testCommand}\n";

$output = [];
$exitCode = 0;
exec($testCommand . ' 2>&1', $output, $exitCode);

echo "  Exit code: {$exitCode}\n";
echo "  Output:\n";
foreach ($output as $line) {
    echo "    {$line}\n";
}

// Test directory creation
echo "\n8. Testing directory creation:\n";
$testDir = 'storage/app/temp/test_debug';
if (is_dir($testDir)) {
    echo "  ✅ Directory created: {$testDir}\n";
    
    $files = glob($testDir . '/*');
    echo "  Files in directory: " . count($files) . "\n";
    
    // Cleanup
    if (is_dir($testDir)) {
        array_map('unlink', glob($testDir . '/*'));
        rmdir($testDir);
    }
} else {
    echo "  ❌ Directory not created\n";
}

echo "\n✅ Debug completed!\n";

echo "\nRecommendations:\n";
if ($exitCode !== 0) {
    echo "- Node.js script failed to execute\n";
    echo "- Check Puppeteer installation: npm install puppeteer\n";
    echo "- Check Chrome installation\n";
    echo "- Try running script manually: node {$scriptPath}\n";
} else {
    echo "- Node.js environment seems OK\n";
    echo "- Issue might be with specific website or network\n";
    echo "- Try testing with a simple website first\n";
}

?>
