<?php
require_once 'api.php';

$input_data = json_decode(file_get_contents('php://input'), true);
$parent_path = escapeshellcmd($input_data['path'] ?? '');
$folder_name = escapeshellcmd($input_data['name'] ?? '');

if (empty($folder_name)) {
    json_response(['error' => 'Nome da pasta não especificado'], 400);
}

if (strpos($folder_name, '/') !== false || strpos($folder_name, '\\') !== false) {
    json_response(['error' => 'Nome da pasta inválido'], 400);
}

// O parent path pode ser vazio (raiz do usuário)
$parent_real_path = get_safe_path($parent_path);

if ($parent_real_path === false || !is_dir($parent_real_path)) {
    json_response(['error' => 'Diretório pai inválido ou acesso negado'], 403);
}

$new_folder_path = $parent_real_path . DIRECTORY_SEPARATOR . $folder_name;

if (file_exists($new_folder_path)) {
    json_response(['error' => 'Já existe um arquivo/pasta com esse nome'], 409);
}

if (mkdir($new_folder_path, 0755)) {
    json_response(['status' => 'success']);
} else {
    json_response(['error' => 'Falha ao criar a pasta'], 500);
}
