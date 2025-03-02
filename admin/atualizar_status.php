<?php
session_start();
require_once '../inc/conexao.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $ticket_id = isset($_POST['ticket_id']) ? intval($_POST['ticket_id']) : 0;
    $status = isset($_POST['status']) ? trim($_POST['status']) : '';
    
    // Valores permitidos para o status
    $allowed_status = ['Aberto', 'Em Andamento', 'Resolvido', 'Cancelado'];
    if ($ticket_id > 0 && in_array($status, $allowed_status)) {
        $stmt = $pdo->prepare("UPDATE tickets SET status = ?, data_atualizacao = NOW() WHERE id = ?");
        if ($stmt->execute([$status, $ticket_id])) {
            $_SESSION['sucesso'] = "Status do chamado atualizado com sucesso!";
        } else {
            $_SESSION['error'] = "Erro ao atualizar status.";
        }
    } else {
        $_SESSION['error'] = "Dados inválidos.";
    }
    
    header("Location: painel.php");
    exit;
}
?>
