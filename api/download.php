<?php
require_once 'api.php';

$path = $_GET['path'] ?? '';

if (empty($path)) {
    http_response_code(400);
    die('Caminho não especificado');
}

$file_path = get_safe_path($path);

if ($file_path === false || !is_file($file_path)) {
    http_response_code(404);
    die('Arquivo não encontrado ou acesso negado');
}

// Proteção para arquivos secretos
$filename = basename($file_path);
if (strpos($filename, '.secret_') === 0) {
    if (!verify_current_user_password($_GET['password'] ?? '')) {
        http_response_code(403);
        die('Acesso negado: Este arquivo exige a senha correta.');
    }
}

$filename = basename($file_path);
$finfo = finfo_open(FILEINFO_MIME_TYPE);
$mime_type = finfo_file($finfo, $file_path);
finfo_close($finfo);

header('Content-Description: File Transfer');
header('Content-Type: ' . $mime_type);
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Expires: 0');
header('Cache-Control: must-revalidate');
header('Pragma: public');
header('Content-Length: ' . filesize($file_path));

// Limpa qualquer output anterior e envia o arquivo
ob_clean();
flush();
readfile($file_path);
exit;
