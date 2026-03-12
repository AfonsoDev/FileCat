<?php
header('Content-Type: text/plain');

echo "Git Diagnostic:\n";

$git_version = shell_exec('git --version 2>&1');
echo "Git version output: " . trim($git_version) . "\n";

$whoami = shell_exec('whoami');
echo "Current user: " . trim($whoami) . "\n";

$cwd = getcwd();
echo "Current Directory: " . $cwd . "\n";

echo "\nGit Fetch Check:\n";
exec('git fetch 2>&1', $fetch_output, $fetch_res);
echo "Fetch Return Code: $fetch_res\n";
echo "Fetch Output:\n" . implode("\n", $fetch_output) . "\n";

echo "\nGit Rev-Parse Check:\n";
$local = shell_exec('git rev-parse HEAD 2>&1');
echo "Local HEAD: " . trim($local) . "\n";

$remote = shell_exec('git rev-parse @{u} 2>&1');
echo "Remote (tracking): " . trim($remote) . "\n";

$git_dir = shell_exec('git rev-parse --git-dir 2>&1');
echo "Git Directory: " . trim($git_dir) . "\n";
