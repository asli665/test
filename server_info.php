<?php
header('Content-Type: text/plain');
echo "Document Root: " . $_SERVER['DOCUMENT_ROOT'] . "\n";
echo "Current Script Directory: " . dirname(__FILE__) . "\n";
?>
