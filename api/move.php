<?php
require_once 'api.php';

$input_data = json_decode(file_get_contents('php://input'), true);
$source_path = escapeshellcmd($input_data['source'] ?? '');
$dest_path = escapeshellcmd($input_data['destination'] ?? '');

if (empty($source_path) || (!isset($input_data['destination']))) {
    json_response(['error' => 'Origem ou destino não especificados'], 400);
}

$source_real = get_safe_path($source_path);
$dest_real = get_safe_path($dest_path); // target directory to move into

if ($source_real === false || !file_exists($source_real)) {
    json_response(['error' => 'Origem não encontrada ou acesso negado'], 404);
}

if ($dest_real === false || !is_dir($dest_real)) {
    json_response(['error' => 'Destino não é um diretório válido ou acesso negado'], 403);
}

// Proteger a pasta raiz e evitar mover pra dentro dela mesmo se for a raiz pai
$base_dir = realpath(STORAGE_PATH . DIRECTORY_SEPARATOR . get_logged_in_username());
if ($source_real === $base_dir) {
    json_response(['error' => 'Não é possível mover a pasta raiz'], 403);
}

// Evitar mover uma pasta para dentro de si mesma
if (strpos($dest_real, $source_real) === 0) {
    json_response(['error' => 'Destino não pode ser subpasta da origem'], 400);
}

$filename = basename($source_real);
$final_dest = $dest_real . DIRECTORY_SEPARATOR . $filename;

if (file_exists($final_dest)) {
    json_response(['error' => 'Já existe um item com o mesmo nome no destino'], 409);
}

if (rename($source_real, $final_dest)) {
    json_response(['status' => 'success']);
} else {
    json_response(['error' => 'Falha ao mover arquivo/pasta'], 500);
}
