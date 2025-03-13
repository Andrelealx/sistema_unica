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

// Parâmetros para paginação
$page    = isset($_GET['page']) ? (int) $_GET['page'] : 1;
$perPage = 10; // Número de chamados por página
$offset  = ($page - 1) * $perPage;

// Monta a query para contagem total (para paginação)
$countQuery = "SELECT COUNT(*) FROM tickets WHERE 1=1";
$paramsCount = [];

if (!empty($statusFiltro)) {
    $countQuery .= " AND status = ?";
    $paramsCount[] = $statusFiltro;
}
if (!empty($setorFiltro)) {
    $countQuery .= " AND setor LIKE ?";
    $paramsCount[] = "%$setorFiltro%";
}
if (!empty($urgenciaFiltro)) {
    $countQuery .= " AND urgencia = ?";
    $paramsCount[] = $urgenciaFiltro;
}
if (!empty($dataIniFiltro)) {
    $countQuery .= " AND data_criacao >= ?";
    $paramsCount[] = $dataIniFiltro . " 00:00:00";
}
if (!empty($dataFimFiltro)) {
    $countQuery .= " AND data_criacao <= ?";
    $paramsCount[] = $dataFimFiltro . " 23:59:59";
}
if (!empty($busca)) {
    $countQuery .= " AND (nome LIKE ? OR protocolo LIKE ?)";
    $paramsCount[] = "%$busca%";
    $paramsCount[] = "%$busca%";
}

$stmtCount = $pdo->prepare($countQuery);
$stmtCount->execute($paramsCount);
$totalTickets = $stmtCount->fetchColumn();
$totalPages = ceil($totalTickets / $perPage);

// Monta a query principal com os filtros e paginação
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

$query .= " ORDER BY data_criacao DESC LIMIT $perPage OFFSET $offset";

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$tickets = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="pt">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
  <title>Painel Administrativo - Unica Serviços</title>

  <!-- Bootstrap CSS e Font Awesome -->
  <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.1/css/all.min.css">

  <!-- Estilos CSS -->
  <style>
    /* Alteração do cabeçalho da tabela para azul */
    .thead-blue th {
      background-color: #007bff;
      color: #fff;
    }
    
    /* Seção expansível de detalhes/edição */
    .details-section {
      background: linear-gradient(135deg, #ffffff 0%, #f9fbfc 100%);
      padding: 20px;
      border: 1px solid #e0e4e8;
      border-radius: 10px;
      margin: 15px 0;
      box-shadow: 0 4px 12px rgba(0,0,0,0.08);
      width: 100%;
      max-width: 700px;
      margin-left: auto;
      margin-right: auto;
      transition: all 0.3s ease;
      display: none;
    }
    .details-section.active {
      display: flex;
      flex-direction: column;
    }
    /* Cabeçalho com botão de fechar */
    .details-header {
      background-color: #007bff;
      color: #fff;
      padding: 10px 15px;
      border-top-left-radius: 10px;
      border-top-right-radius: 10px;
      font-size: 16px;
      font-weight: 600;
      margin: -20px -20px 20px -20px;
      display: flex;
      justify-content: space-between;
      align-items: center;
    }
    /* Flexbox para informações extras na área de detalhes */
    .details-flex {
      display: flex;
      flex-wrap: wrap;
      gap: 20px;
      align-items: center;
      justify-content: space-between;
    }
    @media (max-width: 576px) {
      .details-flex {
          flex-direction: column;
          align-items: flex-start;
      }
    }
    .details-section h6 {
      color: #1a3c6e;
      font-weight: 700;
      margin-bottom: 12px;
      font-size: 15px;
      display: flex;
      align-items: center;
      text-transform: uppercase;
      letter-spacing: 0.5px;
    }
    .details-section h6 i {
      margin-right: 10px;
      color: #007bff;
      font-size: 18px;
    }
    /* Botão Fechar */
    .close-details {
      background: transparent;
      border: none;
      color: #fff;
      font-size: 16px;
      cursor: pointer;
    }
    /* Outros estilos conforme o código original */
    .ticket-descricao {
      font-size: 14px;
      line-height: 1.7;
      color: #34495e;
      background-color: #fff;
      padding: 12px;
      border-radius: 8px;
      border-left: 5px solid #007bff;
      box-shadow: inset 0 1px 3px rgba(0,0,0,0.05);
      max-height: 120px;
      overflow-y: auto;
    }
    .ticket-anexo {
      max-width: 100%;
      height: auto;
      border-radius: 8px;
      margin-top: 12px;
      border: 1px solid #dfe4ea;
      transition: transform 0.2s;
    }
    .ticket-anexo:hover {
      transform: scale(1.02);
    }
    .comentario {
      padding: 12px;
      background-color: #e8f0e9;
      border-radius: 8px;
      margin-bottom: 12px;
      border-left: 4px solid #28a745;
      box-shadow: 0 2px 4px rgba(0,0,0,0.05);
      transition: background-color 0.2s;
    }
    .comentario:hover {
      background-color: #e0e9e1;
    }
    .comentario-header {
      display: flex;
      justify-content: space-between;
      font-size: 12px;
      color: #6c757d;
      margin-bottom: 6px;
      flex-wrap: wrap;
    }
    .comentario-author {
      font-weight: bold;
      color: #2d3b48;
    }
    .comentario-date {
      font-style: italic;
    }
    .comentario-body {
      font-size: 13px;
      color: #34495e;
      max-height: 70px;
      overflow-y: auto;
    }
    .ticket-comentario-textarea {
      width: 100%;
      min-height: 70px;
      resize: none;
      border: 1px solid #ced4da;
      border-radius: 8px;
      padding: 12px;
      font-size: 13px;
      background-color: #fff;
      transition: border-color 0.3s, box-shadow 0.3s;
    }
    .ticket-comentario-textarea:focus {
      border-color: #007bff;
      box-shadow: 0 0 8px rgba(0,123,255,0.2);
    }
    .btn-action {
      margin-right: 8px;
      padding: 6px 14px;
      font-size: 13px;
      border-radius: 25px;
      transition: all 0.3s ease;
      display: inline-flex;
      align-items: center;
    }
    .btn-action i {
      margin-right: 5px;
    }
    .btn-action:hover {
      transform: translateY(-2px);
      box-shadow: 0 3px 6px rgba(0,0,0,0.15);
    }
    .btn-primary.btn-action {
      background-color: #007bff;
      border-color: #007bff;
    }
    .btn-success.btn-action {
      background-color: #28a745;
      border-color: #28a745;
    }
    .btn-danger.btn-action {
      background-color: #dc3545;
      border-color: #dc3545;
    }
    hr {
      border: 0;
      border-top: 1px solid #e0e4e8;
      margin: 15px 0;
      opacity: 0.7;
    }
    /* Filtros de busca */
    .search-bar .form-group {
      margin-bottom: 10px;
    }
    .table td, .table th {
      vertical-align: middle;
    }
    .table .details-row td {
      padding: 0;
    }
    /* Estilos para paginação */
    .pagination {
      margin: 20px 0;
      display: flex;
      justify-content: center;
      list-style: none;
      padding: 0;
    }
    .pagination li {
      margin: 0 5px;
    }
    .pagination li a {
      color: #007bff;
      text-decoration: none;
      padding: 5px 10px;
      border: 1px solid #dee2e6;
      border-radius: 4px;
    }
    .pagination li a.active,
    .pagination li a:hover {
      background-color: #007bff;
      color: #fff;
    }
    /* Media Queries para Mobile */
    @media (max-width: 576px) {
      .details-section {
        padding: 15px;
        max-width: 100%;
        margin: 10px 0;
        border-radius: 8px;
      }
      .details-header {
        padding: 8px 12px;
        font-size: 14px;
        margin: -15px -15px 15px -15px;
      }
      .details-section h6 {
        font-size: 13px;
      }
      .details-section h6 i {
        font-size: 16px;
      }
      .ticket-descricao {
        font-size: 12px;
        padding: 10px;
        max-height: 100px;
      }
      .table td, .table th {
        font-size: 11px;
        padding: 4px;
      }
      .btn-sm, .btn-action {
        font-size: 11px;
        padding: 5px 10px;
      }
      .comentario {
        padding: 10px;
      }
      .comentario-header {
        font-size: 10px;
        flex-direction: column;
      }
      .comentario-body {
        font-size: 12px;
        max-height: 60px;
      }
      .ticket-comentario-textarea {
        min-height: 60px;
        font-size: 12px;
        padding: 10px;
      }
      .search-bar .form-group {
        width: 100%;
      }
      .search-bar .btn {
        width: 100%;
        margin-left: 0 !important;
        margin-top: 5px;
      }
    }
  </style>

  <!-- CSS Customizado externo (opcional) -->
  <link rel="stylesheet" href="../assets/css/admin-estilos.css">
</head>
<body>
  <!-- Área de Filtros e Busca -->
  <div class="container-fluid search-bar py-3">
    <form action="painel.php" method="GET" class="form-inline flex-wrap">
      <div class="form-group mr-2 mb-2">
        <label for="status" class="mr-2">Status:</label>
        <select name="status" id="status" class="form-control">
          <option value="">Todos</option>
          <option value="Aberto"       <?php if($statusFiltro=='Aberto')       echo 'selected'; ?>>Aberto</option>
          <option value="Em Andamento" <?php if($statusFiltro=='Em Andamento') echo 'selected'; ?>>Em Andamento</option>
          <option value="Resolvido"    <?php if($statusFiltro=='Resolvido')    echo 'selected'; ?>>Resolvido</option>
          <option value="Cancelado"    <?php if($statusFiltro=='Cancelado')    echo 'selected'; ?>>Cancelado</option>
        </select>
      </div>
      <div class="form-group mr-2 mb-2">
        <label for="setor" class="mr-2">Setor:</label>
        <input type="text" name="setor" id="setor" class="form-control" placeholder="Setor" 
               value="<?php echo htmlspecialchars($setorFiltro); ?>">
      </div>
      <div class="form-group mr-2 mb-2">
        <label for="urgencia" class="mr-2">Urgência:</label>
        <select name="urgencia" id="urgencia" class="form-control">
          <option value="">Todos</option>
          <option value="Baixo"   <?php if($urgenciaFiltro=='Baixo')   echo 'selected'; ?>>Baixo</option>
          <option value="Médio"   <?php if($urgenciaFiltro=='Médio')   echo 'selected'; ?>>Médio</option>
          <option value="Alto"    <?php if($urgenciaFiltro=='Alto')    echo 'selected'; ?>>Alto</option>
          <option value="Crítico" <?php if($urgenciaFiltro=='Crítico') echo 'selected'; ?>>Crítico</option>
        </select>
      </div>
      <div class="form-group mr-2 mb-2">
        <label for="data_inicial" class="mr-2">Data Inicial:</label>
        <input type="date" name="data_inicial" id="data_inicial" class="form-control" 
               value="<?php echo htmlspecialchars($dataIniFiltro); ?>">
      </div>
      <div class="form-group mr-2 mb-2">
        <label for="data_final" class="mr-2">Data Final:</label>
        <input type="date" name="data_final" id="data_final" class="form-control" 
               value="<?php echo htmlspecialchars($dataFimFiltro); ?>">
      </div>
      <div class="form-group mr-2 mb-2">
        <label for="q" class="mr-2">Busca:</label>
        <input type="text" name="q" id="q" class="form-control" 
               placeholder="Nome ou Protocolo" value="<?php echo htmlspecialchars($busca); ?>">
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
    <h2 class="mb-4">Lista de Chamados
        <span class="badge badge-info p-2">Última atualização: <?php echo date('d/m/Y H:i:s'); ?></span>
        <button class="btn btn-sm btn-outline-secondary refresh-btn" onclick="location.reload();">
          <i class="fas fa-sync-alt"></i> Atualizar
        </button>                                                           
    </h2>
  </div>
    
  <?php if (isset($_SESSION['sucesso'])): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <?php echo $_SESSION['sucesso']; unset($_SESSION['sucesso']); ?>
        <button type="button" class="close" data-dismiss="alert" aria-label="Fechar">
            <span aria-hidden="true">×</span>
        </button>
    </div>
  <?php endif; ?>
  <?php if (isset($_SESSION['error'])): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
        <button type="button" class="close" data-dismiss="alert" aria-label="Fechar">
            <span aria-hidden="true">×</span>
        </button>
    </div>
  <?php endif; ?>
  
  <div class="table-responsive">
    <table class="table table-striped table-bordered table-hover">
      <thead class="thead-blue">
        <tr>
          <th>ID</th>
          <th>Protocolo</th>
          <th>Nome</th>
          <th>Setor</th>
          <th>Prévia da Descrição</th>
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
          <td><?php echo htmlspecialchars($ticket['protocolo']); ?></td>
          <td><?php echo htmlspecialchars($ticket['nome']); ?></td>
          <td><?php echo htmlspecialchars($ticket['setor']); ?></td>
          <td>
            <?php
              $preview = substr($ticket['descricao'], 0, 50);
              echo htmlspecialchars($preview) . (strlen($ticket['descricao']) > 50 ? '...' : '');
            ?>
          </td>
          <td><?php echo htmlspecialchars($ticket['urgencia']); ?></td>
          <td>
            <?php
              $status = $ticket['status'];
              switch($status) {
                case 'Aberto':       $badge = 'badge badge-danger';   break;
                case 'Em Andamento': $badge = 'badge badge-warning';  break;
                case 'Resolvido':    $badge = 'badge badge-success';  break;
                case 'Cancelado':    $badge = 'badge badge-secondary';break;
                default:             $badge = 'badge badge-light';    break;
              }
              echo "<span class=\"$badge\">$status</span>";
            ?>
          </td>
          <td><?php echo date('d/m/Y H:i:s', strtotime($ticket['data_criacao'])); ?></td>
          <td>
            <button class="btn btn-sm btn-info toggle-details" data-target="details<?php echo $ticket['id']; ?>">
              <i class="fas fa-eye"></i> Detalhes
            </button>
            <button class="btn btn-sm btn-warning toggle-edit" data-target="editDetails<?php echo $ticket['id']; ?>">
              <i class="fas fa-edit"></i> Editar
            </button>
          </td>
        </tr>

        <!-- Seção Expansível de Detalhes -->
        <tr class="details-row">
          <td colspan="9">
            <div class="details-section" id="details<?php echo $ticket['id']; ?>">
              <div class="details-header">
                <span>Detalhes do Chamado #<?php echo $ticket['id']; ?></span>
                <div class="d-flex align-items-center">
                  <div class="details-flex mr-2">
                    <span><i class="fas fa-clock"></i> <?php echo date('d/m/Y H:i', strtotime($ticket['data_criacao'])); ?></span>
                    <span><i class="fas fa-info-circle"></i> Informações extras</span>
                  </div>
                  <button class="close-details btn btn-sm btn-outline-secondary">
                    <i class="fas fa-times"></i> Fechar
                  </button>
                </div>
              </div>
              <!-- Descrição -->
              <h6><i class="fas fa-info-circle"></i> Descrição</h6>
              <p class="ticket-descricao"><?php echo nl2br(htmlspecialchars($ticket['descricao'])); ?></p>
              <!-- Anexo -->
              <?php if (!empty($ticket['anexo'])): ?>
                <h6><i class="fas fa-paperclip"></i> Anexo</h6>
                <p>
                  <a href="../<?php echo $ticket['anexo']; ?>" target="_blank" class="btn btn-sm btn-outline-primary btn-action">
                    <i class="fas fa-download"></i> Ver/Download
                  </a>
                  <?php 
                    $ext = strtolower(pathinfo($ticket['anexo'], PATHINFO_EXTENSION));
                    $img_ext = ['jpg', 'jpeg', 'png', 'gif'];
                    if (in_array($ext, $img_ext)):
                  ?>
                  <br>
                  <img src="../<?php echo $ticket['anexo']; ?>" alt="Anexo" class="img-fluid mt-2 ticket-anexo">
                  <?php endif; ?>
                </p>
              <?php endif; ?>

              <hr>

              <!-- Comentários -->
              <div class="comentarios-section">
                <h6><i class="fas fa-comments"></i> Comentários</h6>
                <div class="comentarios-list">
                  <?php
                    $stmtComent = $pdo->prepare("
                      SELECT th.*, u.nome AS nome_usuario
                      FROM ticket_historico th
                      JOIN usuarios u ON th.usuario_id = u.id
                      WHERE th.ticket_id = ?
                      ORDER BY th.data DESC
                    ");
                    $stmtComent->execute([$ticket['id']]);
                    $comentarios = $stmtComent->fetchAll();
                    
                    if (count($comentarios) > 0):
                      foreach ($comentarios as $comentario):
                        $date = new DateTime($comentario['data'], new DateTimeZone('UTC'));
                        $date->setTimezone(new DateTimeZone('America/Sao_Paulo'));
                  ?>
                  <div class="comentario">
                    <div class="comentario-header">
                      <span class="comentario-author"><?php echo htmlspecialchars($comentario['nome_usuario']); ?></span>
                      <span class="comentario-date"><?php echo $date->format('d/m/Y H:i:s'); ?></span>
                    </div>
                    <div class="comentario-body"><?php echo nl2br(htmlspecialchars($comentario['mensagem'])); ?></div>
                  </div>
                  <?php
                      endforeach;
                    else:
                  ?>
                  <p class="comentarios-empty">Sem comentários.</p>
                  <?php endif; ?>
                </div>

                <!-- Formulário de Comentário -->
                <div class="comentario-form mt-3">
                  <h6><i class="fas fa-comment-dots"></i> Adicionar Comentário</h6>
                  <form action="adicionar_comentario.php" method="POST">
                    <input type="hidden" name="ticket_id" value="<?php echo $ticket['id']; ?>">
                    <textarea name="mensagem" class="form-control ticket-comentario-textarea" placeholder="Digite seu comentário" required></textarea>
                    <button type="submit" class="btn btn-primary btn-action mt-2">
                      <i class="fas fa-plus-circle"></i> Adicionar
                    </button>
                  </form>
                </div>
              </div>

              <hr>

              <!-- Atualizar Status -->
              <h6><i class="fas fa-sync-alt"></i> Atualizar Status</h6>
              <form action="atualizar_status.php" method="POST" class="form-group d-flex align-items-center">
                <input type="hidden" name="ticket_id" value="<?php echo $ticket['id']; ?>">
                <select name="status" id="status<?php echo $ticket['id']; ?>" class="form-control mr-2" style="width: auto;">
                  <option value="Aberto"       <?php echo ($ticket['status'] == 'Aberto')       ? 'selected' : ''; ?>>Aberto</option>
                  <option value="Em Andamento" <?php echo ($ticket['status'] == 'Em Andamento') ? 'selected' : ''; ?>>Em Andamento</option>
                  <option value="Resolvido"    <?php echo ($ticket['status'] == 'Resolvido')    ? 'selected' : ''; ?>>Resolvido</option>
                  <option value="Cancelado"    <?php echo ($ticket['status'] == 'Cancelado')    ? 'selected' : ''; ?>>Cancelado</option>
                </select>
                <button type="submit" class="btn btn-success btn-action">
                  <i class="fas fa-save"></i> Salvar
                </button>
              </form>

              <hr>

              <!-- Excluir Chamado -->
              <h6><i class="fas fa-trash-alt"></i> Excluir Chamado</h6>
              <form action="excluir_chamado.php" method="POST" 
                    onsubmit="return confirm('Tem certeza que deseja excluir esse chamado?');">
                <input type="hidden" name="ticket_id" value="<?php echo $ticket['id']; ?>">
                <button type="submit" class="btn btn-danger btn-action">
                  <i class="fas fa-trash"></i> Excluir
                </button>
              </form>
            </div>
          </td>
        </tr>

        <!-- Seção de Edição Expansível -->
        <tr class="edit-row">
          <td colspan="9">
            <div class="details-section" id="editDetails<?php echo $ticket['id']; ?>">
              <div class="details-header d-flex justify-content-between align-items-center">
                <span>Editar Chamado #<?php echo $ticket['id']; ?></span>
                <button class="close-details btn btn-sm btn-outline-secondary">
                  <i class="fas fa-times"></i> Fechar
                </button>
              </div>
              <form action="editar_chamados.php" method="POST">
                <input type="hidden" name="ticket_id" value="<?php echo $ticket['id']; ?>">
                <div class="form-row">
                  <div class="form-group col-md-6">
                    <label for="nome<?php echo $ticket['id']; ?>">Nome</label>
                    <input type="text" class="form-control" id="nome<?php echo $ticket['id']; ?>" 
                           name="nome" value="<?php echo htmlspecialchars($ticket['nome']); ?>" required>
                  </div>
                  <div class="form-group col-md-6">
                    <label for="setor<?php echo $ticket['id']; ?>">Setor</label>
                    <input type="text" class="form-control" id="setor<?php echo $ticket['id']; ?>" 
                           name="setor" value="<?php echo htmlspecialchars($ticket['setor']); ?>" required>
                  </div>
                </div>
                <div class="form-row">
                  <div class="form-group col-md-6">
                    <label for="urgencia<?php echo $ticket['id']; ?>">Urgência</label>
                    <select class="form-control" id="urgencia<?php echo $ticket['id']; ?>" name="urgencia" required>
                      <option value="Baixo"   <?php if($ticket['urgencia']=='Baixo')   echo 'selected'; ?>>Baixo</option>
                      <option value="Médio"   <?php if($ticket['urgencia']=='Médio')   echo 'selected'; ?>>Médio</option>
                      <option value="Alto"    <?php if($ticket['urgencia']=='Alto')    echo 'selected'; ?>>Alto</option>
                      <option value="Crítico" <?php if($ticket['urgencia']=='Crítico') echo 'selected'; ?>>Crítico</option>
                    </select>
                  </div>
                  <div class="form-group col-md-6">
                    <label for="status<?php echo $ticket['id']; ?>">Status</label>
                    <select class="form-control" id="status<?php echo $ticket['id']; ?>" name="status" required>
                      <option value="Aberto"       <?php if($ticket['status']=='Aberto')       echo 'selected'; ?>>Aberto</option>
                      <option value="Em Andamento" <?php if($ticket['status']=='Em Andamento') echo 'selected'; ?>>Em Andamento</option>
                      <option value="Resolvido"    <?php if($ticket['status']=='Resolvido')    echo 'selected'; ?>>Resolvido</option>
                      <option value="Cancelado"    <?php if($ticket['status']=='Cancelado')    echo 'selected'; ?>>Cancelado</option>
                    </select>
                  </div>
                </div>
                <div class="form-row">
                  <div class="form-group col-12">
                    <label for="descricao<?php echo $ticket['id']; ?>">Descrição</label>
                    <textarea class="form-control" id="descricao<?php echo $ticket['id']; ?>" 
                              name="descricao" rows="3" required><?php echo htmlspecialchars($ticket['descricao']); ?></textarea>
                  </div>
                </div>
                <button type="submit" class="btn btn-primary btn-action">
                  <i class="fas fa-save"></i> Salvar Alterações
                </button>
              </form>
            </div>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>

  <!-- Paginação -->
  <?php if ($totalPages > 1): ?>
  <nav aria-label="Navegação de página">
    <ul class="pagination">
      <?php
        // Mantém os filtros na query string
        $queryString = http_build_query(array_merge($_GET, ['page' => null]));
      ?>
      <?php if ($page > 1): ?>
        <li class="page-item"><a class="page-link" href="painel.php?<?php echo $queryString . '&page=' . ($page - 1); ?>">Anterior</a></li>
      <?php endif; ?>

      <?php for ($i = 1; $i <= $totalPages; $i++): ?>
        <li class="page-item"><a class="page-link <?php echo ($i == $page) ? 'active' : ''; ?>" href="painel.php?<?php echo $queryString . '&page=' . $i; ?>"><?php echo $i; ?></a></li>
      <?php endfor; ?>

      <?php if ($page < $totalPages): ?>
        <li class="page-item"><a class="page-link" href="painel.php?<?php echo $queryString . '&page=' . ($page + 1); ?>">Próximo</a></li>
      <?php endif; ?>
    </ul>
  </nav>
  <?php endif; ?>
  
  <!-- jQuery e Bootstrap JS -->
  <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
  <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.bundle.min.js"></script>
  <!-- Script para controlar a exibição das seções -->
  <script>
    $(document).ready(function() {
      $('.details-section').hide();

      $('.toggle-details').click(function(e) {
        e.preventDefault();
        var targetId = $(this).data('target');
        var $section = $('#' + targetId);
        if ($section.is(':visible')) {
          $section.slideUp(300, function() { $(this).removeClass('active'); });
        } else {
          $section.css('display', 'flex').hide().slideDown(300, function() { $(this).addClass('active'); });
        }
      });
      
      $('.toggle-edit').click(function(e) {
        e.preventDefault();
        var targetId = $(this).data('target');
        var $section = $('#' + targetId);
        if ($section.is(':visible')) {
          $section.slideUp(300, function() { $(this).removeClass('active'); });
        } else {
          $section.css('display', 'flex').hide().slideDown(300, function() { $(this).addClass('active'); });
        }
      });
      
      $(document).on('click', '.close-details', function(e) {
        e.preventDefault();
        var $section = $(this).closest('.details-section');
        $section.slideUp(300, function() { $(this).removeClass('active'); });
      });
    });
  </script>
</body>
</html>
