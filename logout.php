<?php
require_once 'config.php';

// Destruir sessão
session_unset();
session_destroy();

// Redirecionar pro login
header('Location: auth/login.php');
exit;
