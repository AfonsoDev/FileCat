<?php
// Configurações Globais do File Cat

// Definir modo estrito e reporting de erros
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Definir fuso horário padrão (ex: America/Sao_Paulo)
date_default_timezone_set('America/Sao_Paulo');

// Informações da Aplicação
define('APP_NAME', 'File Cat');
define('APP_VERSION', '1.0.0');

// Caminhos do Sistema
define('ROOT_PATH', __DIR__);
define('STORAGE_PATH', ROOT_PATH . DIRECTORY_SEPARATOR . 'storage');
define('AUTH_PATH', ROOT_PATH . DIRECTORY_SEPARATOR . 'auth');
define('MESSAGES_PATH', ROOT_PATH . DIRECTORY_SEPARATOR . 'messages');

// Configurações de Upload
define('MAX_UPLOAD_SIZE', 50 * 1024 * 1024); // 50MB
define('ALLOWED_EXTENSIONS', [
    // Imagens
    'jpg', 'jpeg', 'png', 'gif', 'svg', 'webp',
    // Documentos
    'pdf', 'doc', 'docx', 'xls', 'xlsx', 'txt', 'csv', 'md', 'json', 'xml', 'html',
    // Arquivos compactados
    'zip', 'rar', 'tar', 'gz', '7z',
    // Mídia
    'mp4', 'mp3', 'ogg', 'wav'
]);

// Funções Helpers Globais (opcional, podem ser movidas para um 'helpers.php' depois)
function is_installed() {
    return file_exists(AUTH_PATH . '/.installed');
}

// Início de sessão seguro
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
