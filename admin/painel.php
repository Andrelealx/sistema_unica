<?php include 'header.php'; ?>


<?php
session_start();
if (!isset($_SESSION['admin_logged']) || $_SESSION['admin_logged'] !== true) {
    header("Location: login.php");
    exit;
}
date_default_timezone_set('America/Sao_Paulo');
require_once '../inc/conexao.php';

// Recebe os filtros e a busca via GET
$statusFiltro   = isset($_GET['status']) ? $_GET['status'] : '';
$setorFiltro    = isset($_GET['setor']) ? $_GET['setor'] : '';
$urgenciaFiltro = isset($_GET['urgencia']) ? $_GET['urgencia'] : '';
$dataIniFiltro  = isset($_GET['data_inicial']) ? $_GET['data_inicial'] : '';
$dataFimFiltro  = isset($_GET['data_final']) ? $_GET['data_final'] : '';
$busca          = isset($_GET['q']) ? trim($_GET['q']) : '';

// Constrói a query dinamicamente com base nos filtros
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
if (!empty($busca)) {
    $query .= " AND (nome LIKE ? OR protocolo LIKE ?)";
    $params[] = "%$busca%";
    $params[] = "%$busca%";
}

$query .= " ORDER BY data_criacao DESC";

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$tickets = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="pt">
<head>
  <meta charset="UTF-8">
  <title>Painel Administrativo - Unica Serviços</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
 <!-- Bootstrap CSS e Font Awesome -->
  <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.1/css/all.min.css">
  <!-- CSS Customizado para o Dashboard (pode ser integrado ao admin-estilos.css) -->
  <link rel="stylesheet" href="../assets/css/admin-estilos.css">
</head>
<body>

  <!-- Área de Filtros e Busca -->
  <div class="container-fluid search-bar">
    <form action="painel.php" method="GET" class="form-inline flex-wrap">
      <div class="form-group mr-2 mb-2">
        <label for="status" class="mr-2">Status:</label>
        <select name="status" id="status" class="form-control">
          <option value="">Todos</option>
          <option value="Aberto" <?php if($statusFiltro=='Aberto') echo 'selected'; ?>>Aberto</option>
          <option value="Em Andamento" <?php if($statusFiltro=='Em Andamento') echo 'selected'; ?>>Em Andamento</option>
          <option value="Resolvido" <?php if($statusFiltro=='Resolvido') echo 'selected'; ?>>Resolvido</option>
          <option value="Cancelado" <?php if($statusFiltro=='Cancelado') echo 'selected'; ?>>Cancelado</option>
        </select>
      </div>
      <div class="form-group mr-2 mb-2">
        <label for="setor" class="mr-2">Setor:</label>
        <input type="text" name="setor" id="setor" class="form-control" placeholder="Setor" value="<?php echo htmlspecialchars($setorFiltro); ?>">
      </div>
      <div class="form-group mr-2 mb-2">
        <label for="urgencia" class="mr-2">Urgência:</label>
        <select name="urgencia" id="urgencia" class="form-control">
          <option value="">Todos</option>
          <option value="Baixo" <?php if($urgenciaFiltro=='Baixo') echo 'selected'; ?>>Baixo</option>
          <option value="Médio" <?php if($urgenciaFiltro=='Médio') echo 'selected'; ?>>Médio</option>
          <option value="Alto" <?php if($urgenciaFiltro=='Alto') echo 'selected'; ?>>Alto</option>
          <option value="Crítico" <?php if($urgenciaFiltro=='Crítico') echo 'selected'; ?>>Crítico</option>
        </select>
      </div>
      <div class="form-group mr-2 mb-2">
        <label for="data_inicial" class="mr-2">Data Inicial:</label>
        <input type="date" name="data_inicial" id="data_inicial" class="form-control" value="<?php echo htmlspecialchars($dataIniFiltro); ?>">
      </div>
      <div class="form-group mr-2 mb-2">
        <label for="data_final" class="mr-2">Data Final:</label>
        <input type="date" name="data_final" id="data_final" class="form-control" value="<?php echo htmlspecialchars($dataFimFiltro); ?>">
      </div>
      <div class="form-group mr-2 mb-2">
        <label for="q" class="mr-2">Busca:</label>
        <input type="text" name="q" id="q" class="form-control" placeholder="Nome ou Protocolo" value="<?php echo htmlspecialchars($busca); ?>">
      </div>
      <button type="submit" class="btn btn-primary mb-2">Filtrar</button>
      <a href="painel.php" class="btn btn-secondary mb-2 ml-2">Limpar</a>
      <a href="export.php?<?php echo http_build_query($_GET); ?>" class="btn btn-success mb-2 ml-2">
        <i class="fas fa-file-csv"></i> Exportar CSV
      </a>
    </form>
  </div>
  
  <!-- Conteúdo Principal - Lista de Chamados -->
  <div class="container-fluid mt-4">
    <h2 class="mb-4">Lista de Chamados</h2>
    
    <?php if (isset($_SESSION['sucesso'])): ?>
      <div class="alert alert-success alert-dismissible fade show" role="alert">
          <?php echo $_SESSION['sucesso']; unset($_SESSION['sucesso']); ?>
          <button type="button" class="close" data-dismiss="alert" aria-label="Fechar">
              <span aria-hidden="true">&times;</span>
          </button>
      </div>
    <?php endif; ?>
    <?php if (isset($_SESSION['error'])): ?>
      <div class="alert alert-danger alert-dismissible fade show" role="alert">
          <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
          <button type="button" class="close" data-dismiss="alert" aria-label="Fechar">
              <span aria-hidden="true">&times;</span>
          </button>
      </div>
    <?php endif; ?>
    
    <div class="table-responsive">
      <table class="table table-striped table-bordered table-hover">
        <thead class="thead-dark">
          <tr>
            <th>ID</th>
            <th>Protocolo</th>
            <th>Nome</th>
            <th>Setor</th>
            <th>Urgência</th>
            <th>Status</th>
            <th>Data de Criação</th>
            <th>Ações</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($tickets as $ticket): ?>
          <tr>
            <td><?php echo $ticket['id']; ?></td>
            <td><?php echo $ticket['protocolo']; ?></td>
            <td><?php echo $ticket['nome']; ?></td>
            <td><?php echo $ticket['setor']; ?></td>
            <td><?php echo $ticket['urgencia']; ?></td>
            <td>
              <?php
                $status = $ticket['status'];
                switch($status) {
                  case 'Aberto': $badge = 'badge badge-danger'; break;
                  case 'Em Andamento': $badge = 'badge badge-warning'; break;
                  case 'Resolvido': $badge = 'badge badge-success'; break;
                  case 'Cancelado': $badge = 'badge badge-secondary'; break;
                  default: $badge = 'badge badge-light'; break;
                }
                echo "<span class=\"$badge\">$status</span>";
              ?>
            </td>
            <td><?php echo date('d/m/Y H:i:s', strtotime($ticket['data_criacao'])); ?></td>
            <td>
              <!-- Botão para abrir detalhes em um modal -->
              <button class="btn btn-sm btn-info" data-toggle="modal" data-target="#detalhesModal<?php echo $ticket['id']; ?>">
                <i class="fas fa-eye"></i> Detalhes
              </button>
            </td>
          </tr>
          <!-- Modal de Detalhes do Chamado -->
          <div class="modal fade" id="detalhesModal<?php echo $ticket['id']; ?>" tabindex="-1" role="dialog" aria-labelledby="modalLabel<?php echo $ticket['id']; ?>" aria-hidden="true">
            <div class="modal-dialog modal-lg" role="document">
              <div class="modal-content">
                <div class="modal-header">
                  <h5 class="modal-title" id="modalLabel<?php echo $ticket['id']; ?>">Detalhes do Chamado (Protocolo: <?php echo $ticket['protocolo']; ?>)</h5>
                  <button type="button" class="close" data-dismiss="modal" aria-label="Fechar">
                    <span aria-hidden="true">&times;</span>
                  </button>
                </div>
                <div class="modal-body">
                  <h6>Descrição:</h6>
                  <p><?php echo nl2br(htmlspecialchars($ticket['descricao'])); ?></p>
                  <?php if (!empty($ticket['anexo'])): ?>
                  <hr>
                  <h6>Anexo:</h6>
                  <p>
                    <a href="../<?php echo $ticket['anexo']; ?>" target="_blank">
                      <i class="fas fa-download"></i> Ver/Download
                    </a>
                    <?php 
                      $ext = strtolower(pathinfo($ticket['anexo'], PATHINFO_EXTENSION));
                      $img_ext = ['jpg', 'jpeg', 'png', 'gif'];
                      if (in_array($ext, $img_ext)):
                    ?>
                    <br>
                    <img src="../<?php echo $ticket['anexo']; ?>" alt="Anexo" style="max-width:200px; margin-top:10px;">
                    <?php endif; ?>
                  </p>
                  <?php endif; ?>
                  <hr>
                 <!-- Área de Comentários -->
<div class="comentarios-section">
  <h6 class="comentarios-title">Comentários:</h6>
  <div class="comentarios-list">
    <?php
      $stmtComent = $pdo->prepare("SELECT th.*, u.nome FROM ticket_historico th JOIN usuarios u ON th.usuario_id = u.id WHERE th.ticket_id = ? ORDER BY th.data DESC");
      $stmtComent->execute([$ticket['id']]);
      $comentarios = $stmtComent->fetchAll();
      if(count($comentarios) > 0):
        foreach($comentarios as $comentario):
          // Supondo que a data salva está em UTC, convertemos para o fuso de Brasília:
          $date = new DateTime($comentario['data'], new DateTimeZone('UTC'));
          $date->setTimezone(new DateTimeZone('America/Sao_Paulo'));
    ?>
    <div class="comentario">
      <div class="comentario-header">
        <span class="comentario-author"><?php echo htmlspecialchars($comentario['nome']); ?></span>
        <span class="comentario-date"><?php echo $date->format('d/m/Y H:i:s'); ?></span>
      </div>
      <div class="comentario-body">
        <?php echo nl2br(htmlspecialchars($comentario['mensagem'])); ?>
      </div>
    </div>
    <?php
        endforeach;
      else:
    ?>
    <p class="comentarios-empty">Sem comentários.</p>
    <?php endif; ?>
  </div>
  <div class="comentario-form">
    <h6>Adicionar Comentário:</h6>
    <form action="adicionar_comentario.php" method="POST">
      <input type="hidden" name="ticket_id" value="<?php echo $ticket['id']; ?>">
      <textarea name="mensagem" class="form-control" placeholder="Digite seu comentário" required></textarea>
      <button type="submit" class="btn btn-primary mt-2">Adicionar Comentário</button>
    </form>
  </div>
</div>
<hr>

                  <!-- Atualizar Status -->
                  <h6>Atualizar Status:</h6>
                  <form action="atualizar_status.php" method="POST" class="form-inline">
                    <input type="hidden" name="ticket_id" value="<?php echo $ticket['id']; ?>">
                    <div class="form-group mb-2">
                      <label for="statusModal<?php echo $ticket['id']; ?>" class="sr-only">Status</label>
                      <select name="status" id="statusModal<?php echo $ticket['id']; ?>" class="form-control">
                        <option value="Aberto" <?php echo ($ticket['status'] == 'Aberto') ? 'selected' : ''; ?>>Aberto</option>
                        <option value="Em Andamento" <?php echo ($ticket['status'] == 'Em Andamento') ? 'selected' : ''; ?>>Em Andamento</option>
                        <option value="Resolvido" <?php echo ($ticket['status'] == 'Resolvido') ? 'selected' : ''; ?>>Resolvido</option>
                        <option value="Cancelado" <?php echo ($ticket['status'] == 'Cancelado') ? 'selected' : ''; ?>>Cancelado</option>
                      </select>
                    </div>
                    <button type="submit" class="btn btn-success btn-sm ml-2 mb-2">
                      <i class="fas fa-save"></i> Salvar
                    </button>
                  </form>
                  <!-- Botão de Excluir Chamado -->
                  <hr>
                  <form action="excluir_chamado.php" method="POST" onsubmit="return confirm('Tem certeza que deseja excluir esse chamado?');" style="display: inline;">
                    <input type="hidden" name="ticket_id" value="<?php echo $ticket['id']; ?>">
                    <button type="submit" class="btn btn-danger btn-sm">
                      <i class="fas fa-trash"></i> Excluir Chamado
                    </button>
                  </form>
                </div>
                <div class="modal-footer">
                  <button type="button" class="btn btn-secondary" data-dismiss="modal">Fechar</button>
                </div>
              </div>
            </div>
          </div>
          <!-- Fim do Modal -->
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
  
  <!-- jQuery e Bootstrap JS -->
  <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
  <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.bundle.min.js"></script>
</body>
</html>
