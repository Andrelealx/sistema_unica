<?php
session_start();
require_once '../inc/conexao.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Recebe e valida os dados
    $ticket_id = isset($_POST['ticket_id']) ? intval($_POST['ticket_id']) : 0;
    $nome = isset($_POST['nome']) ? trim($_POST['nome']) : '';
    $setor = isset($_POST['setor']) ? trim($_POST['setor']) : '';
    $urgencia = isset($_POST['urgencia']) ? trim($_POST['urgencia']) : '';
    $status = isset($_POST['status']) ? trim($_POST['status']) : '';
    $descricao = isset($_POST['descricao']) ? trim($_POST['descricao']) : '';

    if ($ticket_id <= 0 || empty($nome) || empty($setor) || empty($urgencia) || empty($status) || empty($descricao)) {
        $_SESSION['error'] = "Por favor, preencha todos os campos obrigatórios.";
        header("Location: painel.php");
        exit;
    }

    // Atualiza o ticket no banco de dados
    $stmt = $pdo->prepare("UPDATE tickets SET nome = ?, setor = ?, urgencia = ?, status = ?, descricao = ? WHERE id = ?");
    $result = $stmt->execute([$nome, $setor, $urgencia, $status, $descricao, $ticket_id]);

    if ($result) {
        $_SESSION['sucesso'] = "Chamado atualizado com sucesso.";
    } else {
        $_SESSION['error'] = "Erro ao atualizar o chamado.";
    }
    header("Location: painel.php");
    exit;
} else {
    $_SESSION['error'] = "Ação inválida.";
    header("Location: painel.php");
    exit;
}
