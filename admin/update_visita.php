<?php
session_start();
require_once '../inc/conexao.php';

// Verifica se o método de requisição é POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405); // Método não permitido
    exit;
}

// Recebe os dados via POST
$tipo = $_POST['tipo'] ?? '';
$local_id = $_POST['local_id'] ?? '';
$visitado = $_POST['visitado'] ?? '';
$mes = $_POST['mes'] ?? '';

// Validação básica dos parâmetros
if (empty($tipo) || empty($local_id) || empty($mes)) {
    http_response_code(400); // Bad Request
    echo json_encode(['status' => 'error', 'message' => 'Parâmetros insuficientes.']);
    exit;
}

// Converte os valores para os tipos corretos
$local_id = intval($local_id);
$visitado = intval($visitado);
$mes = trim($mes);

// Query para inserir ou atualizar o registro de visita
$sql = "INSERT INTO visitas (tipo, local_id, mes, visitado, data_atualizacao)
        VALUES (?, ?, ?, ?, NOW())
        ON DUPLICATE KEY UPDATE visitado = VALUES(visitado), data_atualizacao = NOW()";

$stmt = $pdo->prepare($sql);
$result = $stmt->execute([$tipo, $local_id, $mes, $visitado]);

if ($result) {
    echo json_encode(['status' => 'success', 'message' => 'Status atualizado com sucesso!']);
} else {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Erro ao atualizar o status.']);
}
?>
