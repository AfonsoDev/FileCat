<?php
ini_set('display_errors', 0);
while(ob_get_level()) { ob_end_clean(); }

require_once '../config.php';
require_once 'api.php';

// Apenas administradores podem atualizar o sistema
require_admin();

$action = $_GET['action'] ?? 'check';

// Helper to run git commands with error capturing and path discovery
function run_git($cmd) {
    $output = [];
    $return_var = 0;
    
    // 1. Try configured binary (from config.php)
    $binary = defined('GIT_BINARY') ? GIT_BINARY : 'git';
    
    exec("$binary $cmd 2>&1", $output, $return_var);
    
    // 2. If not found, try 'which git'
    if ($return_var === 127 || (count($output) > 0 && strpos($output[0], 'not found') !== false)) {
        $which_out = shell_exec('which git');
        if ($which_out) {
            $binary = trim($which_out);
            $output = [];
            exec("$binary $cmd 2>&1", $output, $return_var);
        }
    }
    
    // 3. If still not found, try common absolute paths
    if ($return_var === 127 || (count($output) > 0 && strpos($output[0], 'not found') !== false)) {
        $common_paths = ['/usr/bin/git', '/usr/local/bin/git', '/bin/git', '/usr/lib/git-core/git'];
        foreach ($common_paths as $path) {
            if (@is_executable($path)) {
                $output = [];
                exec("$path $cmd 2>&1", $output, $return_var);
                $binary = $path;
                break;
            }
        }
    }
    
    return [
        'success' => ($return_var === 0),
        'output' => implode("\n", $output),
        'return_code' => $return_var,
        'binary_used' => $binary
    ];
}

if ($action === 'check') {
    $fetch = run_git('fetch');
    $local = run_git('rev-parse HEAD');
    $local_commit = $local['success'] ? trim($local['output']) : null;
    
    $remote = run_git('rev-parse @{u}');
    if (!$remote['success']) {
        $remote = run_git('rev-parse origin/main');
        if (!$remote['success']) {
            $remote = run_git('rev-parse origin/master');
        }
    }
    
    $remote_commit = $remote['success'] ? trim($remote['output']) : null;
    $update_available = (is_string($local_commit) && is_string($remote_commit) && $local_commit !== $remote_commit);
    
    json_response([
        'update_available' => $update_available,
        'local_version' => $local_commit ? substr($local_commit, 0, 7) : '--',
        'remote_version' => $remote_commit ? substr($remote_commit, 0, 7) : '--',
        'last_check' => date('d/m/Y H:i:s'),
        'debug' => [
            'fetch' => $fetch,
            'local' => $local,
            'remote' => $remote,
            'env_path' => getenv('PATH')
        ]
    ]);

} elseif ($action === 'apply') {
    $pull = run_git('pull');
    if ($pull['success']) {
        json_response(['success' => true, 'message' => 'Sistema atualizado com sucesso!', 'output' => $pull['output']]);
    } else {
        json_response(['success' => false, 'error' => 'Falha ao atualizar o sistema (git pull).', 'output' => $pull['output']], 500);
    }
} else {
    json_response(['error' => 'Ação inválida'], 400);
}
