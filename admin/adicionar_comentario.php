<?php
session_start();
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_once '../inc/conexao.php';
    $ticket_id = isset($_POST['ticket_id']) ? intval($_POST['ticket_id']) : 0;
    $mensagem = isset($_POST['mensagem']) ? trim($_POST['mensagem']) : '';
    
    // Validação simples
    if ($ticket_id > 0 && !empty($mensagem)) {
        // Aqui, assume-se que o usuário que comenta é o admin logado.
        $usuario_id = $_SESSION['admin_id'];
        $stmt = $pdo->prepare("INSERT INTO ticket_historico (ticket_id, usuario_id, mensagem, data) VALUES (?, ?, ?, NOW())");
        if ($stmt->execute([$ticket_id, $usuario_id, $mensagem])) {
            $_SESSION['sucesso'] = "Comentário adicionado com sucesso!";
        } else {
            $_SESSION['error'] = "Erro ao adicionar comentário.";
        }
    } else {
        $_SESSION['error'] = "Dados inválidos para comentário.";
    }
    header("Location: painel.php");
    exit;
}
?>
