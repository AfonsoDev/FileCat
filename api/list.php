<?php
require_once 'api.php';

$path = $_GET['path'] ?? '';
$target_dir = get_safe_path($path);

if ($target_dir === false || !is_dir($target_dir)) {
    json_response(['error' => 'Diretório inválido ou acesso negado'], 403);
}

$files = [];
$folders = [];

$dir_iterator = new DirectoryIterator($target_dir);

foreach ($dir_iterator as $fileinfo) {
    if ($fileinfo->isDot()) continue;
    
    $filename = $fileinfo->getFilename();
    
    // Ocultar arquivos do sistema .htaccess, .htpasswd
    if (strpos($filename, '.') === 0) continue;

    $item = [
        'name' => $filename,
        'path' => empty($path) ? $filename : trim($path, '/') . '/' . $filename,
        'modified' => $fileinfo->getMTime(),
        'modified_human' => date('d/m/Y H:i', $fileinfo->getMTime()),
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
    'current_path' => empty($path) ? '/' : escapeshellarg($path), // escape to be safe on frontend JSON
    'items' => array_merge($folders, $files)
]);
