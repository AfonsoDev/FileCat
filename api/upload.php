<?php
require_once 'api.php';

$path = $_POST['path'] ?? '';
$target_dir = get_safe_path($path);

if ($target_dir === false || !is_dir($target_dir)) {
    json_response(['error' => 'Diretório inválido ou acesso negado'], 403);
}

if (!isset($_FILES['files'])) {
    json_response(['error' => 'Nenhum arquivo enviado'], 400);
}

$results = [];
$files = $_FILES['files'];

// Reestrutura o array $_FILES para facilitar a iteração
$file_array = [];
$file_count = count($files['name']);
$file_keys = array_keys($files);

for ($i = 0; $i < $file_count; $i++) {
    foreach ($file_keys as $key) {
        $file_array[$i][$key] = $files[$key][$i];
    }
}

foreach ($file_array as $file) {
    $filename = basename($file['name']);
    $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

    if ($file['error'] !== UPLOAD_ERR_OK) {
        $results[] = ['name' => $filename, 'status' => 'error', 'message' => 'Erro no upload: ' . $file['error']];
        continue;
    }

    if ($file['size'] > MAX_UPLOAD_SIZE) {
        $results[] = ['name' => $filename, 'status' => 'error', 'message' => 'Arquivo excede o tamanho máximo permitido'];
        continue;
    }

    if (!empty(ALLOWED_EXTENSIONS) && !in_array($ext, ALLOWED_EXTENSIONS)) {
        $results[] = ['name' => $filename, 'status' => 'error', 'message' => 'Extensão não permitida'];
        continue;
    }

    $target_file = $target_dir . DIRECTORY_SEPARATOR . $filename;

    // Evita sobrescrever: adiciona número se o arquivo já existir
    $counter = 1;
    $original_filename = pathinfo($filename, PATHINFO_FILENAME);
    while (file_exists($target_file)) {
        $new_name = $original_filename . '_' . $counter . '.' . $ext;
        $target_file = $target_dir . DIRECTORY_SEPARATOR . $new_name;
        $filename = $new_name;
        $counter++;
    }

    if (move_uploaded_file($file['tmp_name'], $target_file)) {
        $results[] = ['name' => $filename, 'status' => 'success'];
    } else {
        $results[] = ['name' => $filename, 'status' => 'error', 'message' => 'Falha ao mover o arquivo'];
    }
}

json_response(['results' => $results]);
