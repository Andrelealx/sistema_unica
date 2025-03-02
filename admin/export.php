<?php
session_start();
date_default_timezone_set('America/Sao_Paulo');
require_once '../inc/conexao.php';

// Recebe os filtros (igual ao painel)
$statusFiltro   = isset($_GET['status']) ? $_GET['status'] : '';
$setorFiltro    = isset($_GET['setor']) ? $_GET['setor'] : '';
$urgenciaFiltro = isset($_GET['urgencia']) ? $_GET['urgencia'] : '';
$dataIniFiltro  = isset($_GET['data_inicial']) ? $_GET['data_inicial'] : '';
$dataFimFiltro  = isset($_GET['data_final']) ? $_GET['data_final'] : '';

$query = "SELECT * FROM tickets WHERE 1=1";
$params = [];
if (!empty($statusFiltro)) {
    $query .= " AND status = ?";
    $params[] = $statusFiltro;
}
if (!empty($setorFiltro)) {
    $query .= " AND setor LIKE ?";
    $params[] = "%$setorFiltro%";
}
if (!empty($urgenciaFiltro)) {
    $query .= " AND urgencia = ?";
    $params[] = $urgenciaFiltro;
}
if (!empty($dataIniFiltro)) {
    $query .= " AND data_criacao >= ?";
    $params[] = $dataIniFiltro . " 00:00:00";
}
if (!empty($dataFimFiltro)) {
    $query .= " AND data_criacao <= ?";
    $params[] = $dataFimFiltro . " 23:59:59";
}
$query .= " ORDER BY data_criacao DESC";

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$tickets = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Define cabeçalhos para CSV
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename=chamados.csv');

$output = fopen('php://output', 'w');
fputcsv($output, ['ID', 'Protocolo', 'Nome', 'Setor', 'Urgência', 'Status', 'Data de Criação', 'Descrição']);
foreach ($tickets as $ticket) {
    fputcsv($output, [
        $ticket['id'],
        $ticket['protocolo'],
        $ticket['nome'],
        $ticket['setor'],
        $ticket['urgencia'],
        $ticket['status'],
        date('d/m/Y H:i:s', strtotime($ticket['data_criacao'])),
        $ticket['descricao']
    ]);
}
fclose($output);
exit;
?>
