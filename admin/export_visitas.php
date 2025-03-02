<?php
session_start();
require_once '../inc/conexao.php';

// Define o mês atual (formato "YYYY-MM")
$currentMonth = date("Y-m");

// Configura os cabeçalhos para forçar o download do arquivo CSV
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="visitas_' . $currentMonth . '.csv"');

// Abre a saída para escrever o CSV
$output = fopen('php://output', 'w');

// Escreve o cabeçalho do CSV
fputcsv($output, ['Tipo', 'Local', 'Mês', 'Visitado', 'Data Atualização']);

// Consulta os registros da tabela visitas para o mês atual
$stmt = $pdo->prepare("SELECT * FROM visitas WHERE mes = ?");
$stmt->execute([$currentMonth]);

while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $tipo = $row['tipo'];
    $localId = $row['local_id'];
    $nomeLocal = '';

    if ($tipo == 'secretaria') {
        // Obter o nome da secretaria
        $stmtSec = $pdo->prepare("SELECT nome FROM secretarias WHERE id = ?");
        $stmtSec->execute([$localId]);
        $sec = $stmtSec->fetch(PDO::FETCH_ASSOC);
        $nomeLocal = $sec ? $sec['nome'] : 'N/D';
    } elseif ($tipo == 'instituicao') {
        // Obter o nome da instituição
        $stmtInst = $pdo->prepare("SELECT nome FROM instituicoes WHERE id = ?");
        $stmtInst->execute([$localId]);
        $inst = $stmtInst->fetch(PDO::FETCH_ASSOC);
        $nomeLocal = $inst ? $inst['nome'] : 'N/D';
    } else {
        $nomeLocal = 'N/D';
    }
    
    // Escreve a linha no CSV
    fputcsv($output, [
        ucfirst($tipo),
        $nomeLocal,
        $row['mes'],
        $row['visitado'],
        $row['data_atualizacao']
    ]);
}

fclose($output);
exit;
?>
