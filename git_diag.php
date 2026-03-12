<?php
header('Content-Type: text/plain');
require_once 'config.php';

echo "Git Diagnostic (Deep Search Mode):\n";

echo "Environment PATH: " . getenv('PATH') . "\n";
echo "Current user: " . trim(shell_exec('whoami')) . "\n";
echo "Current Directory: " . getcwd() . "\n";

// 1. Check 'which git'
echo "\nChecking 'which git': ";
$which = trim(shell_exec('which git'));
if ($which) {
    echo "FOUND at $which\n";
    $out = []; $res = 0;
    exec("$which --version 2>&1", $out, $res);
    echo "Version: " . ($out[0] ?? "unknown") . "\n";
} else {
    echo "NOT FOUND\n";
}

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
            echo "FOUND BUT NOT EXECUTABLE (Check permissions)\n";
        }
    } else {
        echo "NOT FOUND\n";
    }
}

echo "\n--- Repo Status ---\n";
if (file_exists('.git')) {
    echo "Checking .git folder: FOUND\n";
    exec('git rev-parse --is-inside-work-tree 2>&1', $ot, $rs);
    echo "Inside work tree? " . ($rs === 0 ? "YES" : "NO ($ot[0])") . "\n";
} else {
    echo ".git folder NOT FOUND in current directory (" . getcwd() . ")\n";
}

echo "\n--- End of Diagnostic ---\n";
