<?php
require_once 'api.php';

$path = $_GET['path'] ?? '';
$show_hidden = isset($_GET['show_hidden']) && $_GET['show_hidden'] === 'true';
$target_dir = get_safe_path($path);

if ($target_dir === false || !is_dir($target_dir)) {
    json_response(['error' => 'Diretório inválido ou acesso negado'], 403);
}

// Se o diretório atual for secreto, exige senha (validada via header ou query para listagem)
$basename = basename($target_dir);
if (strpos($basename, '.secret_') === 0) {
    $password = $_GET['password'] ?? '';
    if (!verify_current_user_password($password)) {
        json_response(['error' => 'Senha incorreta para acessar esta pasta', 'needs_password' => true], 403);
    }
}

$files = [];
$folders = [];

$dir_iterator = new DirectoryIterator($target_dir);

foreach ($dir_iterator as $fileinfo) {
    if ($fileinfo->isDot()) continue;
    
    $filename = $fileinfo->getFilename();
    
    // Ocultar arquivos do sistema .htaccess, .htpasswd
    if ($filename === '.htaccess' || $filename === '.htpasswd') continue;

    // Regras de Ocultos
    $is_hidden = strpos($filename, '.') === 0;
    if ($is_hidden && !$show_hidden) continue;

    $item = [
        'name' => $filename,
        'path' => empty($path) ? $filename : trim($path, '/') . '/' . $filename,
        'modified' => $fileinfo->getMTime(),
        'modified_human' => date('d/m/Y H:i', $fileinfo->getMTime()),
        'is_hidden' => $is_hidden,
        'is_secret' => strpos($filename, '.secret_') === 0
    ];

    if ($fileinfo->isDir()) {
        $item['type'] = 'folder';
        $folders[] = $item;
    } else {
        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        $item['type'] = 'file';
        $item['size'] = $fileinfo->getSize();
        $item['size_human'] = humanFileSize($item['size'], 1);
        $item['extension'] = $ext;
        $item['icon'] = getIconForExtension($ext);
        
        // Suporte para Preview de Imagem
        $image_exts = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg'];
        $item['is_image'] = in_array($ext, $image_exts);
        if ($item['is_image']) {
            $item['preview_url'] = 'api/preview.php?path=' . urlencode($item['path']) . '&raw=true';
        }
        
        $files[] = $item;
    }
}

// Ordenar alfabeticamente
usort($folders, function($a, $b) {
    return strcasecmp($a['name'], $b['name']);
});

usort($files, function($a, $b) {
    return strcasecmp($a['name'], $b['name']);
});

// Resposta com pastas primeiro, seguidas de arquivos
json_response([
    'current_path' => empty($path) ? '/' : $path, // Passa o path limpo para o frontend
    'items' => array_merge($folders, $files)
]);
