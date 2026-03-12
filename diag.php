<?php
$files = [
    'config.php',
    'auth/auth.php',
    'api/api.php',
    'api/preview.php',
    'api/list.php',
    'api/upload.php'
];

foreach ($files as $file) {
    $content = file_get_contents(__DIR__ . '/' . $file);
    if ($content === false) {
        echo "$file: Could not read file\n";
        continue;
    }
    
    $hex = bin2hex(substr($content, 0, 10));
    echo "$file: Starting Hex: $hex\n";
    
    if (substr($content, 0, 5) !== '<?php') {
        echo "WARNING: $file DOES NOT start with '<?php' exactly! (First 5 bytes: " . substr($content, 0, 5) . ")\n";
    }
    
    if (preg_match('/^\xEF\xBB\xBF/', $content)) {
        echo "CRITICAL: $file HAS UTF-8 BOM!\n";
    }
}
