<?php
require_once 'api.php';

// Proteção extra: Somente admins podem gerenciar usuários
require_admin();

$method = $_SERVER['REQUEST_METHOD'];
$users_file = AUTH_PATH . '/users.json';

if ($method === 'GET') {
    $users = json_decode(file_get_contents($users_file), true);
    $response = [];
    foreach ($users as $username => $data) {
        $response[] = [
            'username' => $username,
            'role' => $data['role'],
            'created_at' => $data['created_at'] ?? '--'
        ];
    }
    json_response($response);
}

if ($method === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    $username = trim($data['username'] ?? '');
    $password = $data['password'] ?? '';
    $role = $data['role'] ?? 'user';

    if (empty($username) || empty($password)) {
        json_response(['error' => 'Preencha todos os campos'], 400);
    }

    if (!preg_match('/^[a-zA-Z0-9_]+$/', $username)) {
        json_response(['error' => 'Usuário inválido'], 400);
    }

    $users = json_decode(file_get_contents($users_file), true);
    if (isset($users[$username])) {
        json_response(['error' => 'Usuário já existe'], 400);
    }

    // Criar pastas para o novo usuário
    $storage_path = STORAGE_PATH . DIRECTORY_SEPARATOR . $username;
    $messages_path = MESSAGES_PATH . DIRECTORY_SEPARATOR . $username;

    if (!is_dir($storage_path)) mkdir($storage_path, 0775, true);
    if (!is_dir($messages_path)) mkdir($messages_path, 0775, true);

    $users[$username] = [
        'password' => password_hash($password, PASSWORD_BCRYPT),
        'role' => $role,
        'created_at' => date('c')
    ];

    file_put_contents($users_file, json_encode($users, JSON_PRETTY_PRINT));
    json_response(['success' => true]);
}

if ($method === 'DELETE') {
    $data = json_decode(file_get_contents('php://input'), true);
    $username = $data['username'] ?? '';

    if ($username === get_logged_in_username()) {
        json_response(['error' => 'Não é possível excluir seu próprio usuário'], 400);
    }

    $users = json_decode(file_get_contents($users_file), true);
    if (!isset($users[$username])) {
        json_response(['error' => 'Usuário não encontrado'], 404);
    }

    unset($users[$username]);
    file_put_contents($users_file, json_encode($users, JSON_PRETTY_PRINT));
    json_response(['success' => true]);
}

json_response(['error' => 'Método não permitido'], 405);
