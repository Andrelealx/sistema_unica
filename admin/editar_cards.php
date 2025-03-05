<?php
session_start();
if (
    !isset($_SESSION['admin_logged']) || $_SESSION['admin_logged'] !== true ||
    !isset($_SESSION['nivel_acesso']) || $_SESSION['nivel_acesso'] != 2
) {
    header("Location: login.php");
    exit;
}
?>

require_once '../inc/conexao.php';

// Processa atualizações quando o formulário for enviado
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach ($_POST['cards'] as $id => $data) {
        $card_title = trim($data['card_title']);
        $card_text  = trim($data['card_text']);
        $stmt = $pdo->prepare("UPDATE dashboard_cards SET card_title = ?, card_text = ? WHERE id = ?");
        $stmt->execute([$card_title, $card_text, $id]);
    }
    $_SESSION['sucesso'] = "Cards atualizados com sucesso.";
    header("Location: editar_cards.php");
    exit;
}

// Busca todos os cards do dashboard
$stmt = $pdo->query("SELECT * FROM dashboard_cards ORDER BY id ASC");
$cards = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="pt">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
  <title>Editar Cards - Dashboard</title>
  <!-- Bootstrap CSS e Font Awesome -->
  <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.1/css/all.min.css">
  <link rel="stylesheet" href="../assets/css/editar_cards.css">
</head>
<body>
  <div class="container mt-4">
    <h1 class="mb-4">Editar Cards do Dashboard</h1>
    
    <?php if (isset($_SESSION['sucesso'])): ?>
      <div class="alert alert-success">
          <?php echo $_SESSION['sucesso']; unset($_SESSION['sucesso']); ?>
      </div>
    <?php endif; ?>
    
    <form action="editar_cards.php" method="POST">
      <?php foreach ($cards as $card): ?>
      <div class="card">
        <div class="card-header">
          <?php echo ucfirst($card['card_type']); ?>
        </div>
        <div class="card-body">
          <div class="form-group">
            <label for="card_title_<?php echo $card['id']; ?>">Título:</label>
            <input type="text" name="cards[<?php echo $card['id']; ?>][card_title]" id="card_title_<?php echo $card['id']; ?>" class="form-control" value="<?php echo htmlspecialchars($card['card_title']); ?>" required>
          </div>
          <div class="form-group">
            <label for="card_text_<?php echo $card['id']; ?>">Texto:</label>
            <textarea name="cards[<?php echo $card['id']; ?>][card_text]" id="card_text_<?php echo $card['id']; ?>" class="form-control" rows="4" required><?php echo htmlspecialchars($card['card_text']); ?></textarea>
          </div>
        </div>
      </div>
      <?php endforeach; ?>
      <button type="submit" class="btn btn-primary">Salvar Alterações</button>
      <a href="painel.php" class="btn btn-secondary">Voltar</a>
    </form>
  </div>
  
  <!-- Bootstrap JS e dependências -->
  <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
  <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.bundle.min.js"></script>
</body>
</html>
