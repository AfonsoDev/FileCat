<?php
require_once 'api.php';

// Lê o JSON do corpo da requisição (usamos POST com JSON ou DELETE)
$input_data = json_decode(file_get_contents('php://input'), true);
$target_path_input = $input_data['path'] ?? $_POST['path'] ?? $_GET['path'] ?? '';

if (empty($target_path_input)) {
    json_response(['error' => 'Caminho não especificado'], 400);
}

$target_real_path = get_safe_path($target_path_input);

if ($target_real_path === false || !file_exists($target_real_path)) {
    json_response(['error' => 'Arquivo/Pasta não encontrado ou acesso negado'], 404);
}

// Proteger a pasta raiz do usuário (não pode ser deletada)
$base_dir = realpath(STORAGE_PATH . DIRECTORY_SEPARATOR . get_logged_in_username());
if ($target_real_path === $base_dir) {
    json_response(['error' => 'Não é possível deletar a pasta raiz'], 403);
}

// Função para deletar pasta recursivamente
function delete_dir($dirPath) {
    if (!is_dir($dirPath)) {
        return false;
    }
    if (substr($dirPath, strlen($dirPath) - 1, 1) != DIRECTORY_SEPARATOR) {
        $dirPath .= DIRECTORY_SEPARATOR;
    }
    $files = glob($dirPath . '*', GLOB_MARK);
    foreach ($files as $file) {
        if (is_dir($file)) {
            delete_dir($file);
        } else {
            unlink($file);
        }
    }
    return rmdir($dirPath);
}

if (is_dir($target_real_path)) {
    $success = delete_dir($target_real_path);
} else {
    $success = unlink($target_real_path);
}

if ($success) {
    json_response(['status' => 'success', 'message' => 'Excluído com sucesso']);
} else {
    json_response(['error' => 'Falha ao excluir arquivo/pasta'], 500);
}
