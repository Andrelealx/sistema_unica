<?php
session_start();
if (!isset($_SESSION['admin_logged']) || $_SESSION['admin_logged'] !== true) {
    http_response_code(403);
    exit;
}

require_once '../inc/conexao.php';

// Buscar chamados do banco de dados
$stmt = $pdo->prepare("SELECT * FROM tickets ORDER BY data_criacao DESC");
$stmt->execute();
$tickets = $stmt->fetchAll();

// Retornar os dados em JSON
header('Content-Type: application/json');
echo json_encode($tickets);
?>
