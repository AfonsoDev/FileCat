<?php
require_once dirname(__DIR__) . '/auth/auth.php';

// Middleware: Força autenticação em todos os endpoints da API
if (!is_logged_in()) {
    http_response_code(401);
    echo json_encode(['error' => 'Não autorizado']);
    exit;
}

// Handler de Resposta JSON
function json_response($data, $status_code = 200) {
    http_response_code($status_code);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}

// Proteção contra Path Traversal
function get_safe_path($requested_path) {
    $base_dir = STORAGE_PATH . DIRECTORY_SEPARATOR . get_logged_in_username();
    
    // Remove barras iniciais e duplas
    $requested_path = ltrim($requested_path, '/\\');
    
    // Usa realpath no base_dir
    $base_dir = realpath($base_dir);
    
    if (!$base_dir) {
        return false; // Pasta base não existe
    }

    // Se path vazio, retorna a base do usuário
    if (empty($requested_path)) {
        return $base_dir;
    }

    // Resolve o caminho completo solicitado
    $target_dir = $base_dir . DIRECTORY_SEPARATOR . $requested_path;
    $real_target = realpath($target_dir);
    
    // Caso de novo arquivo/pasta que ainda não existe, precisamos validar apenas o diretório pai
    if ($real_target === false) {
        $parent_dir = dirname($target_dir);
        $real_parent = realpath($parent_dir);
        
        // Se o pai não existe ou sai da base, bloqueia
        if ($real_parent === false || strpos($real_parent, $base_dir) !== 0) {
            return false;
        }
        return $target_dir; // Retorna o caminho que será criado
    }

    // Se o caminho existe, garante que ele está "dentro" do base_dir do usuário
    if (strpos($real_target, $base_dir) !== 0) {
        return false; // Tentativa de directory traversal
    }

    return $real_target;
}

// Funções para tamanho humanizado
function humanFileSize($bytes, $decimals = 2) {
    $size = array('B','kB','MB','GB','TB','PB','EB','ZB','YB');
    $factor = floor((strlen($bytes) - 1) / 3);
    return sprintf("%.{$decimals}f", $bytes / pow(1024, $factor)) . @$size[$factor];
}

// Helpers para icons dependendo da extensão
function getIconForExtension($ext) {
    $ext = strtolower($ext);
    $icons = [
        'pdf' => 'pdf-icon',
        'jpg' => 'image-icon',
        'jpeg'=> 'image-icon',
        'png' => 'image-icon',
        'gif' => 'image-icon',
        'webp'=> 'image-icon',
        'zip' => 'archive-icon',
        'rar' => 'archive-icon',
        'mp4' => 'video-icon',
        'txt' => 'text-icon',
        'md'  => 'text-icon',
    ];
    return $icons[$ext] ?? 'file-icon';
}
