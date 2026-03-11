<?php
require_once 'config.php';

// Redireciona se já estiver instalado
if (is_installed()) {
    header('Location: index.php');
    exit;
}

$error = '';
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    if (empty($username) || empty($password)) {
        $error = 'Por favor, preencha todos os campos.';
    } elseif ($password !== $confirm_password) {
        $error = 'As senhas não coincidem.';
    } elseif (!preg_match('/^[a-zA-Z0-9_]+$/', $username)) {
        $error = 'O usuário deve conter apenas letras, números e underlines.';
    } else {
        // Criar pasta do usuário admin
        $user_storage = STORAGE_PATH . DIRECTORY_SEPARATOR . $username;
        if (!is_dir($user_storage)) {
            mkdir($user_storage, 0755, true);
        }

        // Criar pasta de mensagens do usuário admin
        $user_messages = MESSAGES_PATH . DIRECTORY_SEPARATOR . $username;
        if (!is_dir($user_messages)) {
            mkdir($user_messages, 0755, true);
        }

        // Criar users.json
        $hashed_password = password_hash($password, PASSWORD_BCRYPT);
        $users_data = [
            $username => [
                'password' => $hashed_password,
                'role' => 'admin',
                'created_at' => date('c')
            ]
        ];
        
        file_put_contents(AUTH_PATH . '/users.json', json_encode($users_data, JSON_PRETTY_PRINT));
        
        // Criar lock file de instalação
        file_put_contents(AUTH_PATH . '/.installed', date('c'));

        $success = true;
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Instalação - <?= htmlspecialchars(APP_NAME) ?></title>
    <!-- Tailwind CSS (CDN for MVP phase) -->
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="assets/css/app.css">
    <style>
        .glass-panel {
            background: rgba(255, 255, 255, 0.05);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            box-shadow: 0 8px 32px 0 rgba(0, 0, 0, 0.37);
        }
    </style>
</head>
<body class="bg-gray-900 text-gray-100 min-h-screen flex items-center justify-center bg-[radial-gradient(ellipse_at_top,_var(--tw-gradient-stops))] from-gray-800 to-gray-950">
    <div class="glass-panel p-8 rounded-2xl shadow-xl w-full max-w-md">
        
        <div class="text-center mb-8">
            <h1 class="text-3xl font-bold bg-clip-text text-transparent bg-gradient-to-r from-blue-400 to-emerald-400">File Cat</h1>
            <p class="text-gray-400 mt-2 text-sm">Setup Inicial da Aplicação</p>
        </div>

        <?php if ($success): ?>
            <div class="bg-green-500/10 border-l-4 border-green-500 p-4 mb-6 rounded">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <svg class="h-5 w-5 text-green-400" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                        </svg>
                    </div>
                    <div class="ml-3">
                        <p class="text-sm text-green-400 font-medium">Instalação concluída com sucesso!</p>
                        <p class="text-sm text-green-300 mt-2">Você será redirecionado em 3 segundos...</p>
                    </div>
                </div>
            </div>
            <script>
                setTimeout(() => window.location.href = 'auth/login.php', 3000);
            </script>
        <?php else: ?>
            
            <?php if ($error): ?>
                <div class="bg-red-500/10 border border-red-500/50 text-red-400 px-4 py-3 rounded mb-6 text-sm">
                    <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="install.php" class="space-y-5">
                <div>
                    <label for="username" class="block text-sm font-medium text-gray-300 mb-1">Usuário Administrador</label>
                    <input type="text" id="username" name="username" required 
                           class="w-full px-4 py-2 bg-gray-800/50 border border-gray-700 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent outline-none transition-all text-white placeholder-gray-500"
                           placeholder="admin">
                </div>
                
                <div>
                    <label for="password" class="block text-sm font-medium text-gray-300 mb-1">Senha</label>
                    <input type="password" id="password" name="password" required 
                           class="w-full px-4 py-2 bg-gray-800/50 border border-gray-700 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent outline-none transition-all text-white">
                </div>

                <div>
                    <label for="confirm_password" class="block text-sm font-medium text-gray-300 mb-1">Confirmar Senha</label>
                    <input type="password" id="confirm_password" name="confirm_password" required 
                           class="w-full px-4 py-2 bg-gray-800/50 border border-gray-700 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent outline-none transition-all text-white">
                </div>

                <button type="submit" class="w-full py-2.5 px-4 bg-gradient-to-r from-blue-500 to-blue-600 hover:from-blue-600 hover:to-blue-700 text-white font-medium rounded-lg shadow-lg hover:shadow-blue-500/25 transition-all outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 focus:ring-offset-gray-900 mt-6">
                    Finalizar Instalação
                </button>
            </form>
        <?php endif; ?>
    </div>
</body>
</html>
