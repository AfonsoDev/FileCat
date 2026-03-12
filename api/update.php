<?php
require_once 'api.php';

// Apenas administradores podem atualizar o sistema
require_admin();

$action = $_GET['action'] ?? 'check';

if ($action === 'check') {
    // Verifica atualizações remotas
    exec('git fetch', $output, $return_code);
    
    // Compara commit local com o remoto
    $local = shell_exec('git rev-parse HEAD');
    $remote = shell_exec('git rev-parse @{u}');
    
    $update_available = (trim($local) !== trim($remote));
    
    json_response([
        'update_available' => $update_available,
        'local_version' => substr(trim($local), 0, 7),
        'remote_version' => substr(trim($remote), 0, 7),
        'last_check' => date('d/m/Y H:i:s')
    ]);
} 

if ($action === 'apply') {
    // Executa o pull
    exec('git pull 2>&1', $output, $return_code);
    
    if ($return_code === 0) {
        json_response([
            'success' => true,
            'message' => 'Sistema atualizado com sucesso!',
            'output' => $output
        ]);
    } else {
        json_response([
            'success' => false,
            'error' => 'Falha ao atualizar o sistema via git pull.',
            'output' => $output
        ], 500);
    }
}

json_response(['error' => 'Ação inválida'], 400);
