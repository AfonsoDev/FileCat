<?php
ini_set('display_errors', 0);
while(ob_get_level()) { ob_end_clean(); }

require_once 'api.php';

// Apenas administradores podem atualizar o sistema
require_admin();

$action = $_GET['action'] ?? 'check';

// Helper to run git commands with error capturing
function run_git($cmd) {
    $output = [];
    $return_var = 0;
    
    // We can try to use full path if needed, e.g. /usr/bin/git
    $binary = 'git';
    
    exec("$binary $cmd 2>&1", $output, $return_var);
    return [
        'success' => ($return_var === 0),
        'output' => implode("\n", $output),
        'return_code' => $return_var
    ];
}

if ($action === 'check') {
    // 1. Fetch updates
    $fetch = run_git('fetch');
    
    // 2. Get local HEAD
    $local = run_git('rev-parse HEAD');
    $local_commit = $local['success'] ? trim($local['output']) : null;
    
    // 3. Get remote (try tracking first, then common branches)
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
            'remote' => $remote
        ]
    ]);

} elseif ($action === 'apply') {
    $pull = run_git('pull');
    
    if ($pull['success']) {
        json_response([
            'success' => true,
            'message' => 'Sistema atualizado com sucesso!',
            'output' => $pull['output']
        ]);
    } else {
        json_response([
            'success' => false,
            'error' => 'Falha ao atualizar o sistema (git pull).',
            'output' => $pull['output']
        ], 500);
    }
} else {
    json_response(['error' => 'Ação inválida'], 400);
}
