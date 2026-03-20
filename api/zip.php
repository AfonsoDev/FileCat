<?php
require_once 'api.php';

// Aumenta o limite de tempo para ZIPs grandes
set_time_limit(300);

if (!isset($_GET['paths'])) {
    json_response(['error' => 'Nenhum item selecionado'], 400);
}

$paths = explode(',', $_GET['paths']);
$password = $_GET['password'] ?? '';

if (empty($paths)) {
    json_response(['error' => 'Nenhum caminho válido fornecido'], 400);
}

// action=check to prevent white screen downloads gracefully
if (isset($_GET['action']) && $_GET['action'] === 'check') {
    $added_count = 0;
    foreach ($paths as $path) {
        $full_path = get_safe_path($path);
        if ($full_path === false || !file_exists($full_path)) continue;
        $basename = basename($full_path);
        if (strpos($basename, '.secret_') === 0 && !verify_current_user_password($password)) continue;
        $added_count++;
    }
    if ($added_count === 0) {
        json_response(['error' => 'Nenhum arquivo válido pôde ser adicionado ao ZIP ou acesso negado'], 404);
    }
    json_response(['success' => true]);
}

// Cria arquivo temporário para o ZIP
$temp_zip = tempnam(sys_get_temp_dir(), 'fc_zip_');
$zip = new ZipArchive();

if ($zip->open($temp_zip, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== TRUE) {
    json_response(['error' => 'Não foi possível criar o arquivo ZIP'], 500);
}

$added_count = 0;

foreach ($paths as $path) {
    $full_path = get_safe_path($path);
    
    if ($full_path === false || !file_exists($full_path)) continue;

    // Verificação de senha para itens secretos
    $basename = basename($full_path);
    if (strpos($basename, '.secret_') === 0) {
        if (!verify_current_user_password($password)) {
            // Se for segredo e a senha estiver errada, pula o arquivo (ou falha o ZIP inteiro?)
            // Para o ZIP multi-download, vamos pular por segurança individual, mas avisar no ZIP? 
            // Melhor: Se um segredo falhar, o ZIP inteiro deve falhar ou exigir senha global?
            // Vamos exigir que a senha do ZIP (vinda do frontend) seja a correta se houver segredos.
            continue; 
        }
    }

    if (is_dir($full_path)) {
        // Adiciona diretório recursivamente
        $files = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($full_path),
            RecursiveIteratorIterator::LEAVES_ONLY
        );

        foreach ($files as $name => $file) {
            if (!$file->isDir()) {
                $filePath = $file->getRealPath();
                $relativePath = substr($filePath, strlen(dirname($full_path)) + 1);
                $zip->addFile($filePath, $relativePath);
                $added_count++;
            }
        }
    } else {
        $zip->addFile($full_path, basename($full_path));
        $added_count++;
    }
}

$zip->close();

if ($added_count === 0) {
    unlink($temp_zip);
    json_response(['error' => 'Nenhum arquivo válido pôde ser adicionado ao ZIP'], 404);
}

// Envia o arquivo ZIP
header('Content-Type: application/zip');
header('Content-Disposition: attachment; filename="filecat_export_' . date('Ymd_His') . '.zip"');
header('Content-Length: ' . filesize($temp_zip));
header('Pragma: no-cache');
header('Expires: 0');

// Limpa buffers
while (ob_get_level()) {
    ob_end_clean();
}

readfile($temp_zip);

// Remove arquivo temporário após envio
unlink($temp_zip);
exit;
