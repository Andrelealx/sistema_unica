<?php
include 'header.php';

ini_set('display_errors', 1);
error_reporting(E_ALL);

if (!isset($_SESSION['nivel_acesso']) || ($_SESSION['nivel_acesso'] != 1 && $_SESSION['nivel_acesso'] != 2)) {
    header("Location: login.php");
    exit;
}

require_once __DIR__ . '/../inc/conexao.php';

$message       = "";
$stockAdjust   = false;
$produtoAdjust = null;
$edit          = false;

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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['operation']) && in_array($_POST['operation'], ['entrada', 'saida'])) {
        $operation      = $_POST['operation'];
        $id             = (int)$_POST['id'];
        $adjustQuantity = (int)$_POST['adjustQuantity'];

        if ($operation === 'entrada') {
            $stmt = $pdo->prepare("UPDATE produtos SET quantidade = quantidade + ? WHERE id = ?");
            $stmt->execute([$adjustQuantity, $id]);
            $message = "Entrada de estoque realizada com sucesso!";
        } else {
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
    } elseif (isset($_POST['id']) && !empty($_POST['id'])) {
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
    } else {
        $nome       = $_POST['nome'];
        $descricao  = $_POST['descricao'];
        $quantidade = $_POST['quantidade'];
        $preco      = $_POST['preco'];
        $categoria  = $_POST['categoria'];

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

$stmt     = $pdo->query("SELECT * FROM produtos");
$produtos = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.1/css/all.min.css">
  <link rel="stylesheet" href="../assets/css/estoque.css">
  <title>Gerenciamento de Estoque</title>
</head>
<body>

<div class="container">
    <h2>Gerenciamento de Estoque</h2>

    <?php if (!empty($message)): ?>
        <div class="alert">
            <?= htmlspecialchars($message) ?>
        </div>
    <?php endif; ?>

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

    <?php if (!$edit): ?>
        <button id="toggleFormButton" class="btn btn-info">
            <i class="fas fa-plus"></i> Adicionar Produto
        </button>
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
                              <a href="estoque.php?action=edit&id=<?= $produto['id'] ?>"
                                 class="btn btn-success btn-sm">
                                  <i class="fas fa-edit"></i> Editar
                              </a>
                              <a href="estoque.php?action=delete&id=<?= $produto['id'] ?>"
                                 class="btn btn-danger btn-sm"
                                 onclick="return confirm('Deseja realmente apagar este produto?');">
                                  <i class="fas fa-trash"></i> Apagar
                              </a>
                          </td>
                          <td>
                              <div class="stock-buttons">
                                  <a href="estoque.php?action=entrada&id=<?= $produto['id'] ?>"
                                     class="btn-stock-entrada">
                                      <i class="fas fa-plus-circle"></i> Entrada
                                  </a>
                                  <a href="estoque.php?action=saida&id=<?= $produto['id'] ?>"
                                     class="btn-stock-saida">
                                      <i class="fas fa-minus-circle"></i> Saída
                                  </a>
                              </div>
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
    </div>
</div>

<script>
document.getElementById('toggleFormButton').addEventListener('click', function() {
    var form = document.getElementById('productForm');
    form.classList.toggle('hidden-form');
});
</script>

<script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.5.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>