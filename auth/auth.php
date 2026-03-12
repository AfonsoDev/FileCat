<?php
require_once dirname(__DIR__) . '/config.php';

function is_logged_in() {
    return isset($_SESSION['user_id']);
}

function require_login() {
    if (!is_logged_in()) {
        header('Location: /auth/login.php');
        exit;
    }
}

function get_logged_in_username() {
    return $_SESSION['username'] ?? null;
}

function authenticate($username, $password) {
    $users_file = AUTH_PATH . '/users.json';
    if (!file_exists($users_file)) {
        return false;
    }

    $users = json_decode(file_get_contents($users_file), true);
    
    if (isset($users[$username])) {
        if (password_verify($password, $users[$username]['password'])) {
            $_SESSION['user_id'] = $username;
            $_SESSION['username'] = $username;
            $_SESSION['role'] = $users[$username]['role'];
            return true;
        }
    }
    return false;
}
