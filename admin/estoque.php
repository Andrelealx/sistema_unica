<?php
// header.php já inicia sessão, faz verificação, etc.
include 'header.php';

ini_set('display_errors', 1);
error_reporting(E_ALL);

// Se não estiver iniciada a sessão, inicie (caso header.php não faça isso)
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../inc/conexao.php';

$message       = "";
$stockAdjust   = false;
$produtoAdjust = null;
$edit          = false;

// Ações via GET
if (isset($_GET['action'])) {
    $action = $_GET['action'];

    if ($action === 'delete' && isset($_GET['id'])) {
        $id = (int)$_GET['id'];
        $stmt = $pdo->prepare("DELETE FROM produtos WHERE id = ?");
        $stmt->execute([$id]);
        header("Location: estoque.php?message=" . urlencode("Produto excluído com sucesso!"));
        exit;
    }

    if (($action === 'entrada' || $action === 'saida') && isset($_GET['id'])) {
        $stockAdjust = true;
        $operation   = $action;
        $id          = (int)$_GET['id'];
        $stmt        = $pdo->prepare("SELECT * FROM produtos WHERE id = ?");
        $stmt->execute([$id]);
        $produtoAdjust = $stmt->fetch();
    }

    if ($action === 'edit' && isset($_GET['id'])) {
        $edit = true;
        $id   = (int)$_GET['id'];
        $stmt = $pdo->prepare("SELECT * FROM produtos WHERE id = ?");
        $stmt->execute([$id]);
        $produtoEdit = $stmt->fetch();
    }
}

// Ações via POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Ajuste de estoque (Entrada/Saída)
    if (isset($_POST['operation']) && in_array($_POST['operation'], ['entrada', 'saida'])) {
        $operation      = $_POST['operation'];
        $id             = (int)$_POST['id'];
        $adjustQuantity = (int)$_POST['adjustQuantity'];

        if ($operation === 'entrada') {
            $stmt = $pdo->prepare("UPDATE produtos SET quantidade = quantidade + ? WHERE id = ?");
            $stmt->execute([$adjustQuantity, $id]);
            $message = "Entrada de estoque realizada com sucesso!";
        } else {
            // Saída
            $stmt = $pdo->prepare("SELECT quantidade FROM produtos WHERE id = ?");
            $stmt->execute([$id]);
            $current = $stmt->fetchColumn();
            if ($current < $adjustQuantity) {
                $message = "Erro: quantidade insuficiente para saída!";
            } else {
                $stmt = $pdo->prepare("UPDATE produtos SET quantidade = quantidade - ? WHERE id = ?");
                $stmt->execute([$adjustQuantity, $id]);
                $message = "Saída de estoque realizada com sucesso!";
            }
        }
    }
    // Edição de produto
    elseif (isset($_POST['id']) && !empty($_POST['id'])) {
        $id         = (int)$_POST['id'];
        $nome       = $_POST['nome'];
        $descricao  = $_POST['descricao'];
        $quantidade = $_POST['quantidade'];
        $preco      = $_POST['preco'];
        $categoria  = $_POST['categoria'];

        $stmt = $pdo->prepare("UPDATE produtos 
                               SET nome = ?, descricao = ?, quantidade = ?, preco = ?, categoria = ?
                               WHERE id = ?");
        $stmt->execute([$nome, $descricao, $quantidade, $preco, $categoria, $id]);
        $message = "Produto atualizado com sucesso!";
    }
    // Inserção de novo produto com reutilização do menor id disponível
    else {
        $nome       = $_POST['nome'];
        $descricao  = $_POST['descricao'];
        $quantidade = $_POST['quantidade'];
        $preco      = $_POST['preco'];
        $categoria  = $_POST['categoria'];

        // Buscar o menor id disponível:
        $stmt = $pdo->query("SELECT MIN(id) AS min_id FROM produtos");
        $min_id = $stmt->fetchColumn();

        if (!$min_id || $min_id > 1) {
            $next_id = 1;
        } else {
            $stmt = $pdo->query("
                SELECT t1.id + 1 AS next_available
                FROM produtos t1
                LEFT JOIN produtos t2 ON t1.id + 1 = t2.id
                WHERE t2.id IS NULL
                ORDER BY t1.id ASC
                LIMIT 1
            ");
            $next_id = $stmt->fetchColumn();
        }
        
        $stmt = $pdo->prepare("INSERT INTO produtos (id, nome, descricao, quantidade, preco, categoria) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$next_id, $nome, $descricao, $quantidade, $preco, $categoria]);
        $message = "Produto adicionado com sucesso!";
    }
    header("Location: estoque.php?message=" . urlencode($message));
    exit;
}

if (isset($_GET['message'])) {
    $message = $_GET['message'];
}

// Carrega todos os produtos
$stmt     = $pdo->query("SELECT * FROM produtos");
$produtos = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <!-- Meta Tag para Responsividade -->
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <!-- Bootstrap CSS e Font Awesome -->
  <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.1/css/all.min.css">
  <!-- CSS Customizado para o Dashboard -->
  <link rel="stylesheet" href="../assets/css/admin-estilos.css">
  <title>Gerenciamento de Estoque</title>
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
    /* Responsividade para tabelas */
    .table-responsive {
      margin-top: 20px;
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
    /* Botões gerais */
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
    /* Botões para Entrada/Saída de estoque com visual aprimorado */
    .btn-stock-entrada {
      background: linear-gradient(45deg, #20c997, #17a2b8);
      border: none;
      color: #fff;
      padding: 8px 16px;
      font-size: 1rem;
      border-radius: 6px;
      transition: all 0.3s ease;
      box-shadow: 0 2px 4px rgba(0,0,0,0.15);
      text-decoration: none;
      margin-right: 10px; /* Espaço entre os botões */
    }
    .btn-stock-entrada:hover {
      background: linear-gradient(45deg, #17a2b8, #138f9a);
      transform: translateY(-2px);
      box-shadow: 0 4px 8px rgba(0,0,0,0.2);
    }
    .btn-stock-saida {
      background: linear-gradient(45deg, #fd7e14, #e8590c);
      border: none;
      color: #fff;
      padding: 8px 16px;
      font-size: 1rem;
      border-radius: 6px;
      transition: all 0.3s ease;
      box-shadow: 0 2px 4px rgba(0,0,0,0.15);
      text-decoration: none;
    }
    .btn-stock-saida:hover {
      background: linear-gradient(45deg, #e8590c, #d74700);
      transform: translateY(-2px);
      box-shadow: 0 4px 8px rgba(0,0,0,0.2);
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
    /* Formulário de adicionar produto inicia oculto */
    .hidden-form {
      display: none;
    }

    /* Media Query para dispositivos mobile */
    @media (max-width: 576px) {
      .container {
        width: 100%;
        padding: 0 10px;
      }
      h2 {
        font-size: 1.5rem;
      }
      .table thead th, .table tbody td {
        padding: 8px;
        font-size: 0.85rem;
      }
      .btn, .btn-stock-entrada, .btn-stock-saida {
        font-size: 0.8rem;
        padding: 4px 8px;
      }
    }
  </style>
</head>
<body>

<!-- A navbar do header.php já foi incluída -->

<div class="container">
    <h2>Gerenciamento de Estoque</h2>

    <!-- Exibe mensagem (sucesso/erro) -->
    <?php if (!empty($message)): ?>
        <div class="alert">
            <?= htmlspecialchars($message) ?>
        </div>
    <?php endif; ?>

    <!-- Ajuste de Estoque (entrada/saída) -->
    <?php if ($stockAdjust && $produtoAdjust): ?>
        <div class="card">
            <div class="card-header">
                <i class="fas fa-check"></i> <?= ucfirst($operation) ?> de Estoque – Produto: <?= htmlspecialchars($produtoAdjust['nome']) ?>
            </div>
            <div class="card-body">
                <form method="POST" action="estoque.php">
                    <input type="hidden" name="id" value="<?= htmlspecialchars($produtoAdjust['id']) ?>">
                    <input type="hidden" name="operation" value="<?= htmlspecialchars($operation) ?>">
                    <label for="adjustQuantity" class="form-label">
                        Quantidade para <?= $operation === 'entrada' ? 'entrada' : 'saída' ?>:
                    </label>
                    <input type="number" name="adjustQuantity" id="adjustQuantity" class="form-control" required>
                    <button type="submit" class="btn btn-info">
                        <i class="fas fa-check"></i> <?= ucfirst($operation) ?>
                    </button>
                    <a href="estoque.php" class="btn btn-secondary">
                        <i class="fas fa-times"></i> Cancelar
                    </a>
                </form>
            </div>
        </div>
    <?php endif; ?>

    <!-- Botão que exibe/oculta formulário de adicionar produto -->
    <?php if (!$edit): ?>
        <button id="toggleFormButton" class="btn btn-info">
            <i class="fas fa-plus"></i> Adicionar Produto
        </button>
        <!-- Formulário de adicionar produto (inicialmente oculto) -->
        <div id="productForm" class="card hidden-form">
            <div class="card-header">
                <i class="fas fa-plus-circle"></i> Adicionar Produto
            </div>
            <div class="card-body">
                <form method="POST" action="estoque.php">
                    <label for="nome" class="form-label">Nome:</label>
                    <input type="text" name="nome" id="nome" class="form-control" required>
                    <label for="descricao" class="form-label">Descrição:</label>
                    <input type="text" name="descricao" id="descricao" class="form-control" required>
                    <label for="quantidade" class="form-label">Quantidade:</label>
                    <input type="number" name="quantidade" id="quantidade" class="form-control" required>
                    <label for="preco" class="form-label">Preço:</label>
                    <input type="number" step="0.01" name="preco" id="preco" class="form-control" required>
                    <label for="categoria" class="form-label">Categoria:</label>
                    <input type="text" name="categoria" id="categoria" class="form-control" required>
                    <button type="submit" class="btn btn-info">
                        <i class="fas fa-save"></i> Adicionar Produto
                    </button>
                </form>
            </div>
        </div>
    <?php endif; ?>

    <!-- Formulário de edição de produto -->
    <?php if ($edit && isset($produtoEdit)): ?>
        <div class="card">
            <div class="card-header">
                <i class="fas fa-edit"></i> Editar Produto
            </div>
            <div class="card-body">
                <form method="POST" action="estoque.php">
                    <input type="hidden" name="id" value="<?= htmlspecialchars($produtoEdit['id']) ?>">
                    <label for="nome" class="form-label">Nome:</label>
                    <input type="text" name="nome" id="nome" class="form-control" required
                           value="<?= htmlspecialchars($produtoEdit['nome']) ?>">
                    <label for="descricao" class="form-label">Descrição:</label>
                    <input type="text" name="descricao" id="descricao" class="form-control" required
                           value="<?= htmlspecialchars($produtoEdit['descricao']) ?>">
                    <label for="quantidade" class="form-label">Quantidade:</label>
                    <input type="number" name="quantidade" id="quantidade" class="form-control" required
                           value="<?= htmlspecialchars($produtoEdit['quantidade']) ?>">
                    <label for="preco" class="form-label">Preço:</label>
                    <input type="number" step="0.01" name="preco" id="preco" class="form-control" required
                           value="<?= htmlspecialchars($produtoEdit['preco']) ?>">
                    <label for="categoria" class="form-label">Categoria:</label>
                    <input type="text" name="categoria" id="categoria" class="form-control" required
                           value="<?= htmlspecialchars($produtoEdit['categoria']) ?>">
                    <button type="submit" class="btn btn-info">
                        <i class="fas fa-check"></i> Atualizar Produto
                    </button>
                    <a href="estoque.php" class="btn btn-secondary">
                        <i class="fas fa-times"></i> Cancelar
                    </a>
                </form>
            </div>
        </div>
    <?php endif; ?>

    <!-- Lista de Produtos -->
    <h2>Lista de Produtos</h2>
    <div class="table-responsive">
      <table class="table table-striped table-bordered">
          <thead>
              <tr>
                  <th>ID</th>
                  <th>Nome</th>
                  <th>Descrição</th>
                  <th>Quantidade</th>
                  <th>Preço</th>
                  <th>Categoria</th>
                  <th>Ações</th>
                  <th>Estoque</th>
              </tr>
          </thead>
          <tbody>
              <?php if ($produtos): ?>
                  <?php foreach ($produtos as $produto): ?>
                      <tr>
                          <td><?= htmlspecialchars($produto['id']) ?></td>
                          <td><?= htmlspecialchars($produto['nome']) ?></td>
                          <td><?= htmlspecialchars($produto['descricao']) ?></td>
                          <td><?= htmlspecialchars($produto['quantidade']) ?></td>
                          <td><?= htmlspecialchars($produto['preco']) ?></td>
                          <td><?= htmlspecialchars($produto['categoria']) ?></td>
                          <td>
                              <!-- Botão Editar -->
                              <a href="estoque.php?action=edit&id=<?= $produto['id'] ?>"
                                 class="btn btn-success btn-sm">
                                  <i class="fas fa-edit"></i> Editar
                              </a>
                              <!-- Botão Apagar -->
                              <a href="estoque.php?action=delete&id=<?= $produto['id'] ?>"
                                 class="btn btn-danger btn-sm"
                                 onclick="return confirm('Deseja realmente apagar este produto?');">
                                  <i class="fas fa-trash"></i> Apagar
                              </a>
                          </td>
                          <td>
                              <!-- Botão Entrada -->
                              <a href="estoque.php?action=entrada&id=<?= $produto['id'] ?>"
                                 class="btn-stock-entrada">
                                  <i class="fas fa-plus-circle"></i> Entrada
                              </a>
                              <!-- Botão Saída -->
                              <a href="estoque.php?action=saida&id=<?= $produto['id'] ?>"
                                 class="btn-stock-saida">
                                  <i class="fas fa-minus-circle"></i> Saída
                              </a>
                          </td>
                      </tr>
                  <?php endforeach; ?>
              <?php else: ?>
                  <tr>
                      <td colspan="8">Nenhum produto cadastrado.</td>
                  </tr>
              <?php endif; ?>
          </tbody>
      </table>
    </div><!-- /.table-responsive -->

</div><!-- /.container -->

<script>
document.getElementById('toggleFormButton').addEventListener('click', function() {
    var form = document.getElementById('productForm');
    form.classList.toggle('hidden-form');
});
</script>

<!-- Bootstrap JS (opcional) -->
<script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.5.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
