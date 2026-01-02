<?php
$file = __DIR__ . '/storage/logs/laravel.log';
if (!file_exists($file)) {
    echo "Log file not found.";
    exit;
}
$content = file_get_contents($file);
// Get last 2000 chars roughly
$last = substr($content, -2000);
echo $last;
