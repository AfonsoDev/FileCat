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
$ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

// Mime Types suportados para preview direto
$previewable_extensions = [
    // Imagens (exibição nativa)
    'jpg'  => 'image/jpeg',
    'jpeg' => 'image/jpeg',
    'png'  => 'image/png',
    'gif'  => 'image/gif',
    'webp' => 'image/webp',
    'svg'  => 'image/svg+xml',
    // Vídeos (HTML5 video)
    'mp4'  => 'video/mp4',
    'webm' => 'video/webm',
    'ogg'  => 'video/ogg',
    // Áudio
    'mp3'  => 'audio/mpeg',
    'wav'  => 'audio/wav',
    // Texto e código (Syntax highlight no Frontend, serve como text/plain)
    'txt'  => 'text/plain',
    'md'   => 'text/plain',
    'csv'  => 'text/plain',
    'json' => 'application/json',
    'xml'  => 'text/xml',
    'html' => 'text/html',
    'js'   => 'text/javascript',
    'css'  => 'text/css',
    'php'  => 'text/plain', // Seguro enviar o backend como txt se pra ler
    // PDF
    'pdf'  => 'application/pdf'
];

if (array_key_exists($ext, $previewable_extensions)) {
    $mime_type = $previewable_extensions[$ext];
} else {
    // Tenta detecção dinâmica
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime_type = finfo_file($finfo, $file_path);
    finfo_close($finfo);
}

// Para texto puro ou desconhecido com mime text, força plain pra evitar script injection
if (strpos($mime_type, 'text/') === 0 && !in_array($ext, ['html', 'svg'])) {
   $mime_type = 'text/plain'; 
}

// Retorna JSON para uso com AJAX, incluindo conteúdo puro (para texto) ou uma URL assinada (temporário/cache)
// Neste caso simples do MVP, ou devolvemos a raw string se for txt, ou apenas uma ROTA de visualização
// Como é MVP e usamos ajax pro modal:
if (isset($_GET['raw']) && $_GET['raw'] === 'true') {
    // Desabilita exibição de erros para não corromper o binário
    ini_set('display_errors', 0);
    
    header('Content-Type: ' . $mime_type);
    header('Content-Length: ' . filesize($file_path));
    
    // Limpa qualquer buffer de saída anterior
    while (ob_get_level()) {
        ob_end_clean();
    }
    
    readfile($file_path);
    exit;
}

// Se não requested RAW, retorna metadata
json_response([
    'name' => $filename,
    'extension' => $ext,
    'mime' => $mime_type,
    'size' => filesize($file_path),
    // URL que o frontend vai embutir no <img>, <video> ou <iframe> (chama aqui mesmo com raw=true)
    'raw_url' => 'api/preview.php?path=' . urlencode($path) . '&raw=true'
]);
