<?php
session_start();
require_once '../inc/conexao.php'; // Ajuste o caminho conforme sua estrutura

// header.php já inicia sessão, faz verificação, etc.
include 'header.php';


// Verifica se o usuário possui nível de acesso 2 (administrativo)
if (!isset($_SESSION['nivel_acesso']) || $_SESSION['nivel_acesso'] != 2) {
    header("Location: login.php");
    exit;
}

// Define o timezone para Brasília
date_default_timezone_set('America/Sao_Paulo');

$message = "";
$edit = false;
$encomendaEdit = null;

// Processa ações via GET
if (isset($_GET['action'])) {
    $action = $_GET['action'];
    
    // Apagar encomenda
    if ($action === 'delete' && isset($_GET['id'])) {
        $id = (int) $_GET['id'];
        $stmt = $pdo->prepare("DELETE FROM encomendas WHERE id = ?");
        $stmt->execute([$id]);
        header("Location: encomendas.php?message=" . urlencode("Encomenda apagada com sucesso!"));
        exit;
    }
    
    // Marcar como entregue
    if ($action === 'entregar' && isset($_GET['id'])) {
        $id = (int) $_GET['id'];
        $now = date('Y-m-d H:i:s');
        $stmt = $pdo->prepare("UPDATE encomendas SET status = 'Entregue', data_entrega = ?, data_atualizacao = ? WHERE id = ?");
        $stmt->execute([$now, $now, $id]);
        header("Location: encomendas.php?message=" . urlencode("Encomenda marcada como entregue!"));
        exit;
    }
    
    // Editar encomenda
    if ($action === 'edit' && isset($_GET['id'])) {
        $edit = true;
        $id = (int) $_GET['id'];
        $stmt = $pdo->prepare("SELECT * FROM encomendas WHERE id = ?");
        $stmt->execute([$id]);
        $encomendaEdit = $stmt->fetch();
    }
}

// Processa os dados enviados via POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Se estiver editando (existe id no POST)
    if (isset($_POST['id']) && !empty($_POST['id'])) {
        $id           = (int) $_POST['id'];
        $nome         = trim($_POST['nome'] ?? '');
        $descricao    = trim($_POST['descricao'] ?? '');
        $dataPrevisao = trim($_POST['data_previsao'] ?? '');
        
        $now = date('Y-m-d H:i:s');
        $stmt = $pdo->prepare("UPDATE encomendas 
                               SET nome = ?, descricao = ?, data_previsao = ?, data_atualizacao = ?
                               WHERE id = ?");
        $stmt->execute([$nome, $descricao, $dataPrevisao, $now, $id]);
        $message = "Encomenda atualizada com sucesso!";
    }
    // Inserção de nova encomenda
    else {
        $nome         = trim($_POST['nome'] ?? '');
        $descricao    = trim($_POST['descricao'] ?? '');
        $dataPrevisao = trim($_POST['data_previsao'] ?? '');
        
        // Validação simples
        if (empty($nome) || empty($descricao) || empty($dataPrevisao)) {
            $_SESSION['erro'] = "Preencha todos os campos obrigatórios.";
            header("Location: encomendas.php");
            exit;
        }
        
        // Lógica para reutilizar o menor ID disponível na tabela "encomendas"
        $stmt = $pdo->query("SELECT MIN(id) AS min_id FROM encomendas");
        $min_id = $stmt->fetchColumn();
        if (!$min_id || $min_id > 1) {
            $next_id = 1;
        } else {
            $stmt = $pdo->query("
                SELECT t1.id + 1 AS next_available
                FROM encomendas t1
                LEFT JOIN encomendas t2 ON t1.id + 1 = t2.id
                WHERE t2.id IS NULL
                ORDER BY t1.id ASC
                LIMIT 1
            ");
            $next_id = $stmt->fetchColumn();
        }
        
        $now = date('Y-m-d H:i:s');
        // Insere a nova encomenda com status padrão "Pendente"
        $stmt = $pdo->prepare("INSERT INTO encomendas (id, nome, descricao, data_previsao, status, data_criacao, data_atualizacao)
                               VALUES (?, ?, ?, ?, 'Pendente', ?, ?)");
        $stmt->execute([$next_id, $nome, $descricao, $dataPrevisao, $now, $now]);
        $message = "Encomenda cadastrada com sucesso!";
    }
    
    header("Location: encomendas.php?message=" . urlencode($message));
    exit;
}

if (isset($_GET['message'])) {
    $message = $_GET['message'];
}

// Consulta para listar todas as encomendas
$stmt = $pdo->query("SELECT * FROM encomendas ORDER BY id ASC");
$encomendas = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <title>Controle de Encomendas</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <!-- Bootstrap CSS e Font Awesome -->
  <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.1/css/all.min.css">
  <!-- CSS Customizado -->
  <link rel="stylesheet" href="../assets/css/admin-estilos.css">
  <style>
    body {
      margin: 0;
      background-color: #001f3f;
      color: #f8f9fa;
      font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
    }
    .container {
      width: 90%;
      max-width: 1200px;
      margin: 0 auto;
      padding-top: 20px;
      padding-bottom: 20px;
    }
    h2 {
      font-weight: 600;
      margin-bottom: 20px;
      font-size: 1.75rem;
      color: #f8f9fa;
    }
    .table {
      width: 100%;
      border-collapse: collapse;
      background-color: #00274d;
      border-radius: 4px;
      box-shadow: 0 2px 4px rgba(0,0,0,0.1);
      overflow: hidden;
    }
    .table thead th {
      background-color: #0056b3;
      color: #f8f9fa;
      padding: 10px;
      text-align: center;
    }
    .table tbody td {
      padding: 10px;
      vertical-align: middle;
      font-size: 0.95rem;
      text-align: center;
      border-bottom: 1px solid #0056b3;
    }
    .table-striped tbody tr:nth-of-type(odd) {
      background-color: #003d73;
    }
    .table-bordered {
      border: 1px solid #0056b3;
    }
    /* Botões padrão */
    .btn {
      display: inline-block;
      padding: 6px 12px;
      font-size: 0.9rem;
      font-weight: 500;
      text-align: center;
      text-decoration: none;
      border-radius: 4px;
      cursor: pointer;
      margin: 2px;
    }
    .btn-info {
      background-color: #17a2b8;
      border: none;
      color: #fff;
      transition: background-color 0.3s ease, transform 0.3s ease;
    }
    .btn-info:hover {
      background-color: #138496;
      transform: scale(1.02);
    }
    .btn-success {
      background-color: #28a745;
      border: none;
      color: #fff;
      transition: background-color 0.3s ease, transform 0.3s ease;
    }
    .btn-success:hover {
      background-color: #218838;
      transform: scale(1.02);
    }
    .btn-danger {
      background-color: #dc3545;
      border: none;
      color: #fff;
      transition: background-color 0.3s ease, transform 0.3s ease;
    }
    .btn-danger:hover {
      background-color: #c82333;
      transform: scale(1.02);
    }
    .btn-secondary {
      background-color: #6c757d;
      border: none;
      color: #fff;
      transition: background-color 0.3s ease;
    }
    .btn-secondary:hover {
      background-color: #5a6268;
    }
    /* Alertas e Cards */
    .alert {
      border-radius: 4px;
      background-color: #0056b3;
      color: #f8f9fa;
      padding: 10px;
      margin-bottom: 20px;
      text-align: center;
    }
    .card {
      background-color: #00274d;
      border: 1px solid #0056b3;
      border-radius: 4px;
      margin-bottom: 20px;
    }
    .card-header {
      background-color: #0056b3;
      color: #f8f9fa;
      padding: 10px;
      font-size: 1.1rem;
      border-bottom: 1px solid #0056b3;
    }
    .card-body {
      padding: 15px;
    }
    .form-label {
      display: block;
      margin-bottom: 5px;
      font-weight: 500;
    }
    .form-control {
      width: 100%;
      padding: 8px;
      border: 1px solid #ccc;
      border-radius: 4px;
      margin-bottom: 10px;
      font-size: 0.9rem;
      color: #000;
    }
    /* Formulário de adicionar/encomenda inicia oculto */
    .hidden-form {
      display: none;
    }
  </style>
</head>
<body>

<div class="container">
    <h2>Controle de Encomendas</h2>
    
    <!-- Exibe mensagens de sucesso/erro -->
    <?php if (!empty($message)): ?>
        <div class="alert">
            <?= htmlspecialchars($message) ?>
        </div>
    <?php endif; ?>
    
    <!-- Formulário para editar encomenda -->
    <?php if ($edit && isset($encomendaEdit)): ?>
        <div class="card">
            <div class="card-header">
                <i class="fas fa-edit"></i> Editar Encomenda
            </div>
            <div class="card-body">
                <form method="POST" action="encomendas.php">
                    <input type="hidden" name="id" value="<?= htmlspecialchars($encomendaEdit['id']) ?>">
                    
                    <label for="nome" class="form-label">Nome da Encomenda:</label>
                    <input type="text" name="nome" id="nome" class="form-control" required
                           value="<?= htmlspecialchars($encomendaEdit['nome']) ?>">
                    
                    <label for="descricao" class="form-label">Descrição:</label>
                    <textarea name="descricao" id="descricao" class="form-control" required><?= htmlspecialchars($encomendaEdit['descricao']) ?></textarea>
                    
                    <label for="data_previsao" class="form-label">Data Prevista para Entrega:</label>
                    <input type="date" name="data_previsao" id="data_previsao" class="form-control" required
                           value="<?= htmlspecialchars($encomendaEdit['data_previsao']) ?>">
                    
                    <button type="submit" class="btn btn-info">
                        <i class="fas fa-check"></i> Atualizar Encomenda
                    </button>
                    <a href="encomendas.php" class="btn btn-secondary">
                        <i class="fas fa-times"></i> Cancelar
                    </a>
                </form>
            </div>
        </div>
    <?php else: ?>
        <!-- Botão para exibir o formulário de nova encomenda -->
        <button id="toggleFormButton" class="btn btn-info">
            <i class="fas fa-plus"></i> Cadastrar Nova Encomenda
        </button>
        <div id="encomendaForm" class="card hidden-form">
            <div class="card-header">
                <i class="fas fa-plus-circle"></i> Nova Encomenda
            </div>
            <div class="card-body">
                <form method="POST" action="encomendas.php">
                    <label for="nome" class="form-label">Nome da Encomenda:</label>
                    <input type="text" name="nome" id="nome" class="form-control" required>
                    
                    <label for="descricao" class="form-label">Descrição:</label>
                    <textarea name="descricao" id="descricao" class="form-control" required></textarea>
                    
                    <label for="data_previsao" class="form-label">Data Prevista para Entrega:</label>
                    <input type="date" name="data_previsao" id="data_previsao" class="form-control" required>
                    
                    <button type="submit" class="btn btn-info">
                        <i class="fas fa-save"></i> Cadastrar Encomenda
                    </button>
                </form>
            </div>
        </div>
    <?php endif; ?>
    
    <!-- Tabela de Encomendas -->
    <h2>Lista de Encomendas</h2>
    <table class="table table-striped table-bordered">
        <thead>
            <tr>
                <th>ID</th>
                <th>Nome</th>
                <th>Descrição</th>
                <th>Data Prevista</th>
                <th>Status</th>
                <th>Ações</th>
            </tr>
        </thead>
        <tbody>
            <?php if ($encomendas): ?>
                <?php foreach ($encomendas as $encomenda): ?>
                    <tr>
                        <td><?= htmlspecialchars($encomenda['id']) ?></td>
                        <td><?= htmlspecialchars($encomenda['nome']) ?></td>
                        <td><?= htmlspecialchars($encomenda['descricao']) ?></td>
                        <td><?= htmlspecialchars($encomenda['data_previsao']) ?></td>
                        <td><?= htmlspecialchars($encomenda['status']) ?></td>
                        <td>
                            <!-- Botão Editar -->
                            <a href="encomendas.php?action=edit&id=<?= $encomenda['id'] ?>" class="btn btn-success btn-sm">
                                <i class="fas fa-edit"></i> Editar
                            </a>
                            <!-- Botão Apagar -->
                            <a href="encomendas.php?action=delete&id=<?= $encomenda['id'] ?>" class="btn btn-danger btn-sm"
                               onclick="return confirm('Deseja realmente apagar esta encomenda?');">
                                <i class="fas fa-trash"></i> Apagar
                            </a>
                            <!-- Botão Marcar como Entregue (se estiver pendente) -->
                            <?php if ($encomenda['status'] === 'Pendente'): ?>
                                <a href="encomendas.php?action=entregar&id=<?= $encomenda['id'] ?>" class="btn btn-info btn-sm"
                                   onclick="return confirm('Marcar esta encomenda como entregue?');">
                                    <i class="fas fa-check-circle"></i> Entregue
                                </a>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="6">Nenhuma encomenda cadastrada.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
    
</div><!-- /.container -->

<script>
document.getElementById('toggleFormButton').addEventListener('click', function() {
    document.getElementById('encomendaForm').classList.toggle('hidden-form');
});
</script>

<!-- Bootstrap JS e dependências -->
<script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.5.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
