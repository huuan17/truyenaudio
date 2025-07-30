<?php
$image = imagecreate(800, 600);
$bg = imagecolorallocate($image, 100, 150, 200);
$text_color = imagecolorallocate($image, 255, 255, 255);
imagefill($image, 0, 0, $bg);
imagestring($image, 5, 300, 280, 'TEST IMAGE', $text_color);
imagejpeg($image, 'public/test-image.jpg');
imagedestroy($image);
echo 'Test image created at public/test-image.jpg';
?>
