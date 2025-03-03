<?php
session_start();
require_once '../inc/conexao.php';  // Ajuste o caminho se precisar

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Recebe os dados do formulário
    $ticket_id = $_POST['ticket_id'] ?? 0;
    $nome      = trim($_POST['nome'] ?? '');
    $setor     = trim($_POST['setor'] ?? '');
    $urgencia  = trim($_POST['urgencia'] ?? '');
    $status    = trim($_POST['status'] ?? '');
    $descricao = trim($_POST['descricao'] ?? '');

    // Validações simples (exemplo)
    if (empty($ticket_id) || empty($nome) || empty($setor) || empty($urgencia) || empty($status) || empty($descricao)) {
        $_SESSION['error'] = "Preencha todos os campos obrigatórios para editar o chamado.";
        header("Location: painel.php");
        exit;
    }

    // Monta a query de UPDATE
    $sql = "UPDATE tickets 
               SET nome = ?, 
                   setor = ?, 
                   urgencia = ?, 
                   status = ?, 
                   descricao = ?
             WHERE id = ?";
    $stmt = $pdo->prepare($sql);
    $ok = $stmt->execute([
        $nome,
        $setor,
        $urgencia,
        $status,
        $descricao,
        $ticket_id
    ]);

    // Verifica se deu certo
    if ($ok) {
        $_SESSION['sucesso'] = "Chamado atualizado com sucesso.";
    } else {
        $_SESSION['error'] = "Erro ao atualizar o chamado.";
    }

    // Redireciona de volta para painel.php
    header("Location: painel.php");
    exit;
} else {
    // Se acessou sem ser via POST, redireciona
    header("Location: painel.php");
    exit;
}
