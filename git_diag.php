<?php
header('Content-Type: text/plain');
require_once 'config.php';

echo "Git Diagnostic (Path Discovery Mode):\n";

$whoami = shell_exec('whoami');
echo "Current user: " . trim($whoami) . "\n";

$cwd = getcwd();
echo "Current Directory: " . $cwd . "\n";

// 1. Check configured binary
$binary = defined('GIT_BINARY') ? GIT_BINARY : 'git';
echo "\nChecking configured binary: $binary\n";
$out1 = [];
$res1 = 0;
exec("$binary --version 2>&1", $out1, $res1);
echo "Result: " . ($res1 === 0 ? "SUCCESS" : "FAILED") . "\n";
echo "Output: " . (isset($out1[0]) ? $out1[0] : "no output") . "\n";

// 2. Probing common paths
echo "\nProbing common absolute paths:\n";
$common_paths = ['/usr/bin/git', '/usr/local/bin/git', '/bin/git', '/usr/lib/git-core/git', 'C:\Program Files\Git\bin\git.exe'];

foreach ($common_paths as $path) {
    echo "- Checking $path: ";
    if (file_exists($path)) {
        if (is_executable($path)) {
            $out = []; $res = 0;
            exec("$path --version 2>&1", $out, $res);
            echo "FOUND & EXECUTABLE (" . (isset($out[0]) ? $out[0] : "no version output") . ")\n";
        } else {
            echo "FOUND BUT NOT EXECUTABLE\n";
        }
    } else {
        echo "NOT FOUND\n";
    }
}

echo "\n--- End of Diagnostic ---\n";
echo "If one of the absolute paths worked, copy it to config.php in 'GIT_BINARY'.\n";
echo "Note: The system now tries to find these paths automatically in api/update.php.\n";
