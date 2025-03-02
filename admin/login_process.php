<?php
session_start();
require_once '../inc/conexao.php';

// Verifica se o método da requisição é POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: login.php");
    exit;
}

// Recebe e trata os dados do formulário
$login = trim($_POST['login']);
$senha = trim($_POST['senha']);

// Consulta para buscar o usuário com base no e-mail ou nome
$stmt = $pdo->prepare("SELECT * FROM usuarios WHERE email = ? OR nome = ? LIMIT 1");
$stmt->execute([$login, $login]);
$usuario = $stmt->fetch();

if (!$usuario || !password_verify($senha, $usuario['senha'])) {
    $_SESSION['error'] = "Credenciais inválidas!";
    header("Location: login.php");
    exit;
}

// Login bem-sucedido: define as variáveis de sessão
$_SESSION['admin_logged'] = true;
$_SESSION['admin_id'] = $usuario['id'];
$_SESSION['admin_nome'] = $usuario['nome'];
$_SESSION['nivel_acesso'] = $usuario['nivel_acesso'];  // Ex: 1 para admin, 2 para técnico, etc.

// Redireciona para o painel administrativo
header("Location: painel.php");
exit;
?>
