<?php
session_start();
if (!isset($_SESSION['admin_logged']) || $_SESSION['admin_logged'] !== true) {
    header("Location: login.php");
    exit;
}

date_default_timezone_set('America/Sao_Paulo');
require_once '../inc/conexao.php'; // Certifique-se de que este arquivo define $pdo

// Processa as ações via POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $acao = $_POST['acao'] ?? '';
    
    if ($acao === 'adicionarUso') {
        $peca_id = (int) $_POST['peca_id'];
        $quantidade = (int) $_POST['quantidade'];
        if ($peca_id > 0 && $quantidade > 0) {
            $stmtInsert = $pdo->prepare("INSERT INTO usos_pecas (peca_id, quantidade, data_uso) VALUES (?, ?, ?)");
            $stmtInsert->execute([$peca_id, $quantidade, date('Y-m-d H:i:s')]);
        }
        header("Location: metas.php");
        exit;
    } elseif ($acao === 'reset') {
        $stmtReset = $pdo->prepare("DELETE FROM usos_pecas");
        $stmtReset->execute();
        header("Location: metas.php");
        exit;
    } elseif ($acao === 'alterMeta') {
        $novaMeta = (int) $_POST['nova_meta'];
        if ($novaMeta > 0) {
            $stmtCheck = $pdo->prepare("SELECT meta FROM meta_config WHERE id = 1");
            $stmtCheck->execute();
            if ($stmtCheck->rowCount() > 0) {
                $stmtMeta = $pdo->prepare("UPDATE meta_config SET meta = ? WHERE id = 1");
                $stmtMeta->execute([$novaMeta]);
            } else {
                $stmtMeta = $pdo->prepare("INSERT INTO meta_config (id, meta) VALUES (1, ?)");
                $stmtMeta->execute([$novaMeta]);
            }
        }
        header("Location: metas.php");
        exit;
    }
}

// Obtém a meta atual da tabela meta_config (ou usa 3000 se não existir)
$stmtMeta = $pdo->query("SELECT meta FROM meta_config WHERE id = 1");
$rowMeta = $stmtMeta->fetch(PDO::FETCH_ASSOC);
$META_PONTOS = $rowMeta ? (int)$rowMeta['meta'] : 3000;

// Busca todas as peças
$stmt = $pdo->query("SELECT * FROM pecas ORDER BY nome ASC");
$pecas = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Calcula o total de pontos acumulados
$sqlTotal = "
    SELECT SUM(up.quantidade * p.pontuacao) AS totalPontos
    FROM usos_pecas up
    JOIN pecas p ON up.peca_id = p.id
";
$totalRow = $pdo->query($sqlTotal)->fetch(PDO::FETCH_ASSOC);
$totalPontos = $totalRow['totalPontos'] ? $totalRow['totalPontos'] : 0;

// Calcula a porcentagem da meta atingida
$porcentagem = ($META_PONTOS > 0) ? min(100, ($totalPontos / $META_PONTOS) * 100) : 0;

// Mensagem de exemplo
$message = "Prepare-se para o desafio!";
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <!-- Meta para Responsividade -->
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>CAJU - OPERAÇÃO FINAL</title>
  <!-- Bootstrap CSS e Font Awesome -->
  <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
  <!-- Fonte temática (Press Start 2P) -->
  <link href="https://fonts.googleapis.com/css2?family=Press+Start+2P&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.1/css/all.min.css">
  <style>
    /* Contêiner do Logo com fundo azul capri */
    .logo-container {
      position: absolute;
      top: 20px;
      left: 20px;
      background-color: #2C3E50;
      padding: 5px 8px;
      border-radius: 5px;
      z-index: 1000;
    }
    .logo-container img {
      max-height: 80px;
      display: block;
    }
    /* Fundo sólido navy */
    body {
      background-color: #1B263B;
      color: #fff;
      font-family: 'Press Start 2P', cursive;
      padding: 20px;
      margin: 0;
    }
    .container {
      max-width: 1200px;
      margin: 120px auto 0 auto;
      position: relative;
    }
    h1, h2 {
      text-align: center;
      margin-bottom: 20px;
    }
    /* Cartões */
    .card {
      background-color: #212F45;
      border: 2px solid #F39C12;
      border-radius: 10px;
      margin-bottom: 30px;
      box-shadow: 0 4px 8px rgba(0,0,0,0.4);
    }
    .card-header {
      background-color: #F39C12;
      color: #1B263B;
      padding: 15px;
      font-size: 1.2rem;
      text-align: center;
      font-weight: bold;
      border-bottom: 2px solid #1B263B;
    }
    .card-body {
      padding: 20px;
      text-align: center;
    }
    /* Barra de Progresso */
    .progress {
      height: 40px;
      background-color: #495164;
      border-radius: 20px;
      overflow: visible;
      margin-bottom: 15px;
      position: relative;
    }
    .progress-bar {
      background-color: #00BFFF; /* Azul Capri */
      transition: width 1s ease-in-out;
      height: 100%;
    }
    /* Caju PNG que acompanha a barra */
    .caju-run {
      position: absolute;
      top: -40px;
      width: 80px;
      z-index: 10;
      transition: left 1s ease-in-out;
    }
    /* Removemos o efeito de animação da barra */
    /* Tabela de Pontuação */
    .table-responsive {
      margin-top: 20px;
    }
    .table-dark th, .table-dark td {
      background-color: #2C3E50;
      border: 1px solid #F39C12;
      color: #fff;
    }
    /* Botões para Uso de Peça */
    .btn-stock {
      background-color: #F39C12;
      border: none;
      color: #1B263B;
      padding: 10px 20px;
      border-radius: 8px;
      font-size: 1rem;
      cursor: pointer;
      transition: all 0.3s ease;
      margin: 5px;
      text-decoration: none;
    }
    .btn-stock:hover {
      transform: scale(1.05);
    }
    /* Botão Administrativo (apenas para superadmin) */
    .admin-actions {
      position: fixed;
      top: 20px;
      right: 20px;
      z-index: 1100;
    }
    .btn-admin {
      background-color: #00BFFF;
      border: none;
      color: #1B263B;
      padding: 10px 20px;
      border-radius: 8px;
      font-size: 1rem;
      cursor: pointer;
      transition: all 0.3s ease;
      margin: 5px;
      text-decoration: none;
    }
    .btn-admin:hover {
      transform: scale(1.05);
    }
    /* Overlay e Mensagem "CAJU AMADURECEU" */
    .caju-overlay {
      position: fixed;
      top: 0; left: 0;
      width: 100%; height: 100%;
      background: rgba(0,0,0,0.8);
      display: flex;
      align-items: center;
      justify-content: center;
      z-index: 9999;
      flex-direction: column;
    }
    .caju-message {
      color: #F39C12;
      font-size: 3rem;
      text-transform: uppercase;
      text-align: center;
      padding: 20px;
      border: 4px dashed #F39C12;
      margin-bottom: 20px;
      animation: blink 1s infinite alternate;
    }
    @keyframes blink {
      from { opacity: 1; }
      to { opacity: 0.5; }
    }
    .btn-close-caju {
      background-color: #F39C12;
      color: #1B263B;
      padding: 10px 20px;
      border: none;
      border-radius: 8px;
      font-size: 1.2rem;
      cursor: pointer;
      transition: all 0.3s ease;
    }
    .btn-close-caju:hover {
      transform: scale(1.05);
    }
    /* Responsividade */
    @media (max-width: 576px) {
      .logo-container {
        position: static;
        display: block;
        margin: 0 auto 10px auto;
      }
      .logo-container img {
        max-height: 60px;
      }
      .container {
        margin-top: 20px;
      }
      h1, h2 {
        font-size: 1.3rem;
      }
      .progress {
        height: 30px;
      }
      .caju-run {
        top: -25px;
        width: 60px;
      }
      .caju-message {
        font-size: 2rem;
      }
      .btn-close-caju, .btn-admin, .btn-stock {
        font-size: 0.8rem;
        padding: 8px 16px;
      }
    }
  </style>
</head>
<body>
  <!-- Logo com fundo azul capri (nesse caso, usamos o fundo navy-escuro do contêiner) -->
  <div class="logo-container">
    <img src="../assets/img/logo.png" alt="Logo da Empresa">
  </div>

  <!-- Botão Administrativo (apenas para superadmin) -->
  <?php if (isset($_SESSION['nivel_acesso']) && $_SESSION['nivel_acesso'] == 2): ?>
  <div class="admin-actions">
    <button type="button" class="btn-admin" data-toggle="modal" data-target="#adminModal">
      <i class="fas fa-cogs"></i>
    </button>
  </div>
  <?php endif; ?>

<div class="container">
  <h1>OPERAÇÃO CAJU</h1>
  <h2><?= htmlspecialchars($message) ?></h2>
  
  <!-- Barra de Progresso -->
  <div class="card">
    <div class="card-header">
      Barra de Progresso da Meta
    </div>
    <div class="card-body">
      <p>Total de Pontos Acumulados: <strong><?= $totalPontos ?></strong> / <?= $META_PONTOS ?></p>
      <div class="progress">
        <!-- Barra interna sem efeito especial -->
        <div class="progress-bar progress-bar-striped progress-bar-animated" 
             style="width: <?= $porcentagem ?>%;"
             role="progressbar" 
             aria-valuenow="<?= $porcentagem ?>" 
             aria-valuemin="0" 
             aria-valuemax="100">
        </div>
        <img src="../assets/img/caju.png" alt="Caju" class="caju-run"
             style="left: calc(<?= $porcentagem ?>% - 40px);">
      </div>
    </div>
  </div>
  
  <!-- Tabela de Pontuação das Peças -->
  <div class="card">
    <div class="card-header">
      Tabela de Pontuação das Peças
    </div>
    <div class="card-body">
      <div class="table-responsive">
        <table class="table table-dark table-bordered">
          <thead>
            <tr>
              <th>ID</th>
              <th>Peça</th>
              <th>Pontuação</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($pecas as $peca): ?>
              <tr>
                <td><?= $peca['id'] ?></td>
                <td><?= htmlspecialchars($peca['nome']) ?></td>
                <td><?= htmlspecialchars($peca['pontuacao']) ?></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
  
  <!-- Formulário para Adicionar Uso de Peça -->
  <div class="card">
    <div class="card-header">
      Adicionar Uso de Peça
    </div>
    <div class="card-body">
      <form method="POST" action="metas.php">
        <input type="hidden" name="acao" value="adicionarUso">
        <div class="form-group">
          <label for="peca_id">Selecione a Peça:</label>
          <select name="peca_id" id="peca_id" class="form-control" required>
            <option value="">-- Escolha --</option>
            <?php foreach ($pecas as $peca): ?>
              <option value="<?= $peca['id'] ?>">
                <?= htmlspecialchars($peca['nome']) ?> (<?= $peca['pontuacao'] ?> pts)
              </option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="form-group">
          <label for="quantidade">Quantidade utilizada:</label>
          <input type="number" name="quantidade" id="quantidade" class="form-control" required>
        </div>
        <button type="submit" class="btn-stock">
          <i class="fas fa-save"></i> Registrar Uso
        </button>
      </form>
    </div>
  </div>
  
</div>

<!-- Modal Administrativo (apenas para superadmin) -->
<?php if (isset($_SESSION['nivel_acesso']) && $_SESSION['nivel_acesso'] == 2): ?>
<div class="modal fade" id="adminModal" tabindex="-1" role="dialog" aria-labelledby="adminModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered" role="document">
    <div class="modal-content" style="background-color: #212F45; border: 2px solid #F39C12;">
      <div class="modal-header" style="background-color: #F39C12; color: #1B263B;">
        <h5 class="modal-title" id="adminModalLabel">Ações Administrativas</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Fechar" style="color: #1B263B;">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body" style="text-align: center; color: #fff;">
        <p>Meta Atual: <strong><?= $META_PONTOS ?></strong> pontos</p>
        <form method="POST" action="metas.php" style="display:inline;">
          <input type="hidden" name="acao" value="reset">
          <button type="submit" class="btn-admin">
            <i class="fas fa-undo"></i> Resetar Pontos
          </button>
        </form>
        <hr style="border-color: #F39C12;">
        <form method="POST" action="metas.php" style="display:inline;">
          <input type="hidden" name="acao" value="alterMeta">
          <input type="number" name="nova_meta" placeholder="Nova Meta" required style="width:100px; padding:5px; margin-right:5px;">
          <button type="submit" class="btn-admin">
            <i class="fas fa-edit"></i> Alterar Meta
          </button>
        </form>
      </div>
    </div>
  </div>
</div>
<?php endif; ?>

<!-- Overlay com aviso "CAJU AMADURECEU" -->
<?php if ($porcentagem == 100): ?>
  <div class="caju-overlay" id="cajuOverlay">
    <div class="caju-message">CAJU AMADURECEU</div>
    <button id="cajuCloseBtn" class="btn-close-caju">Fechar</button>
  </div>
<?php endif; ?>

<!-- Bootstrap JS e dependências -->
<script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.bundle.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
  var closeBtn = document.getElementById('cajuCloseBtn');
  if (closeBtn) {
    closeBtn.addEventListener('click', function() {
      var overlay = document.getElementById('cajuOverlay');
      if (overlay) {
        overlay.style.display = 'none';
      }
    });
  }
});
</script>
</body>
</html>
