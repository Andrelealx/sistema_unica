<?php
session_start();
require_once '../inc/conexao.php';

// Verifica se o usuário está autenticado (ajuste conforme seu sistema)
if(!isset($_SESSION['admin_logged']) || $_SESSION['admin_logged'] !== true){
    header("Location: login.php");
    exit;
}

// Verifica se o ticket_id foi enviado via POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ticket_id'])) {
    $ticket_id = intval($_POST['ticket_id']);
    
    // Executa a exclusão no banco de dados
    $stmt = $pdo->prepare("DELETE FROM tickets WHERE id = ?");
    if($stmt->execute([$ticket_id])) {
        $_SESSION['sucesso'] = "Chamado excluído com sucesso!";
    } else {
        $_SESSION['erro'] = "Erro ao excluir o chamado. Tente novamente.";
    }
} else {
    $_SESSION['erro'] = "Dados inválidos para exclusão.";
}

// Redireciona de volta para o painel
header("Location: painel.php");
exit;
?>
