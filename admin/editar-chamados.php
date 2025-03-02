<?php
session_start();
if (!isset($_SESSION['admin_logged']) || $_SESSION['admin_logged'] !== true) {
    header("Location: login.php");
    exit;
}

require_once '../inc/conexao.php';

if (!isset($_GET['id'])) {
    header("Location: painel.php");
    exit;
}

$ticket_id = $_GET['id'];

// Buscar os detalhes do chamado
$stmt = $pdo->prepare("SELECT * FROM tickets WHERE id = ?");
$stmt->execute([$ticket_id]);
$ticket = $stmt->fetch();

if (!$ticket) {
    $_SESSION['error'] = "Chamado não encontrado!";
    header("Location: painel.php");
    exit;
}

// Processar atualização do chamado
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $novo_status = trim($_POST['status']);
    $comentario = trim($_POST['comentario']);

    // Atualizar o status do chamado
    $update_stmt = $pdo->prepare("UPDATE tickets SET status = ?, data_atualizacao = NOW() WHERE id = ?");
    $update_stmt->execute([$novo_status, $ticket_id]);

    // Inserir comentário no histórico, se houver
    if (!empty($comentario)) {
        $insert_stmt = $pdo->prepare("INSERT INTO ticket_historico (ticket_id, usuario_id, mensagem, data) VALUES (?, ?, ?, NOW())");
        $insert_stmt->execute([$ticket_id, $_SESSION['admin_id'], $comentario]);
    }

    $_SESSION['sucesso'] = "Chamado atualizado com sucesso!";
    header("Location: editar-chamado.php?id=" . $ticket_id);
    exit;
}

// Obter histórico do chamado
$stmt_history = $pdo->prepare("SELECT th.*, u.nome FROM ticket_historico th JOIN usuarios u ON th.usuario_id = u.id WHERE th.ticket_id = ? ORDER BY th.data DESC");
$stmt_history->execute([$ticket_id]);
$historico = $stmt_history->fetchAll();
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <title>Editar Chamado - Unica Serviços</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <!-- Bootstrap CSS -->
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <!-- CSS Customizado -->
    <link rel="stylesheet" href="../assets/css/estilos.css">
</head>
<body>
<header class="bg-primary text-white py-3">
    <div class="container d-flex justify-content-between align-items-center">
