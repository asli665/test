<?php
$cssFiles = ['driver.css', 'admin_approval.css', 'rangantodapp.css'];

header('Content-Type: text/plain');

foreach ($cssFiles as $file) {
    echo "Checking file: $file\n";
    if (file_exists($file)) {
        echo " - Exists: Yes\n";
        echo " - Readable: " . (is_readable($file) ? "Yes" : "No") . "\n";
        echo " - Size: " . filesize($file) . " bytes\n";
    } else {
        echo " - Exists: No\n";
    }
    echo "\n";
}
?>
