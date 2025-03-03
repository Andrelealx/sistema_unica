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
  <link rel="stylesheet" href="../assets/css/estoque.css">
  <title>Gerenciamento de Estoque</title>
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
