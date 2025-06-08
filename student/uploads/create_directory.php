<?php
// Create uploads directory if it doesn't exist
$uploadDir = __DIR__;

if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0755, true);
    echo "Uploads directory created successfully!";
} else {
    echo "Uploads directory already exists.";
}

// Check if default avatar exists
$defaultAvatar = $uploadDir . '/default-avatar.png';
if (!file_exists($defaultAvatar)) {
    // Create a simple default avatar placeholder
    $img = imagecreate(150, 150);
    $bg = imagecolorallocate($img, 200, 200, 200);
    $text_color = imagecolorallocate($img, 100, 100, 100);
    
    imagestring($img, 5, 35, 65, 'No Photo', $text_color);
    imagepng($img, $defaultAvatar);
    imagedestroy($img);
    
    echo "\nDefault avatar created!";
} else {
    echo "\nDefault avatar already exists.";
}

// Set proper permissions
chmod($uploadDir, 0755);
if (file_exists($defaultAvatar)) {
    chmod($defaultAvatar, 0644);
}

echo "\nDirectory permissions set to 755";
echo "\nFile permissions set to 644";
?>
