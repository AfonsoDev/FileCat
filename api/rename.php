<?php
require_once 'api.php';

$input_data = json_decode(file_get_contents('php://input'), true);
$target_path = escapeshellcmd($input_data['path'] ?? '');
$new_name = escapeshellcmd($input_data['new_name'] ?? '');

if (empty($target_path) || empty($new_name)) {
    json_response(['error' => 'Caminho ou novo nome não especificado'], 400);
}

// Validar nome para evitar subpastas (apenas renomeia no mesmo nível)
if (strpos($new_name, '/') !== false || strpos($new_name, '\\') !== false) {
    json_response(['error' => 'Novo nome inválido'], 400);
}

$old_real_path = get_safe_path($target_path);

if ($old_real_path === false || !file_exists($old_real_path)) {
    json_response(['error' => 'Arquivo/Pasta origem não encontrado'], 404);
}

// Não permitir renomear o diretório raiz do usuário
if ($old_real_path === realpath(STORAGE_PATH . DIRECTORY_SEPARATOR . get_logged_in_username())) {
    json_response(['error' => 'Não é possível renomear a pasta raiz'], 403);
}

$parent_dir = dirname($old_real_path);
$new_real_path = $parent_dir . DIRECTORY_SEPARATOR . $new_name;

if (file_exists($new_real_path)) {
    json_response(['error' => 'Já existe um arquivo/pasta com esse nome'], 409);
}

if (rename($old_real_path, $new_real_path)) {
    json_response(['status' => 'success']);
} else {
    json_response(['error' => 'Falha ao renomear'], 500);
}
