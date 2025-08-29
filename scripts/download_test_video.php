<?php
// Download a small sample MP4 into storage/app/test/test.mp4
$urls = [
    'https://samplelib.com/lib/preview/mp4/sample-5s.mp4',
    'https://file-examples.com/storage/fe4f2b39bcb03c333fd1346/2017/04/file_example_MP4_480_1_5MG.mp4',
    'https://archive.org/download/SampleVideo1280x7205mb/sample_640x360.mp4',
];

$targetDir = __DIR__ . '/../storage/app/test';
$target = $targetDir . '/test.mp4';
if (!is_dir($targetDir)) {
    mkdir($targetDir, 0777, true);
}

function download($url, $dest) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0');
    curl_setopt($ch, CURLOPT_TIMEOUT, 60);
    $data = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    if ($code === 200 && $data) {
        file_put_contents($dest, $data);
        return true;
    }
    return false;
}

$ok = false;
foreach ($urls as $u) {
    echo "Trying: {$u}\n";
    if (download($u, $target)) { $ok = true; break; }
}
if (!$ok) {
    fwrite(STDERR, "Failed to download any sample MP4.\n");
    exit(2);
}
$size = filesize($target);
echo "Saved: {$target} (" . number_format($size) . " bytes)\n";

