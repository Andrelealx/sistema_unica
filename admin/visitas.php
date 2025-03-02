<?php
session_start();
if (!isset($_SESSION['admin_logged']) || $_SESSION['admin_logged'] !== true) {
    header("Location: login.php");
    exit;
}
require_once '../inc/conexao.php';

// Define o mês atual (formato "YYYY-MM")
$currentMonth = date("Y-m");

// Consulta dos registros das tabelas de locais
$stmtSec = $pdo->query("SELECT * FROM secretarias ORDER BY nome ASC");
$secretarias = $stmtSec->fetchAll(PDO::FETCH_ASSOC);

$stmtInst = $pdo->query("SELECT * FROM instituicoes ORDER BY nome ASC");
$instituicoes = $stmtInst->fetchAll(PDO::FETCH_ASSOC);

/**
 * Função para obter o status de visita para um local.
 * Retorna 1 se visitado, 0 caso contrário.
 */
function getVisitStatus($pdo, $tipo, $local_id, $currentMonth) {
    $stmt = $pdo->prepare("SELECT visitado FROM visitas WHERE tipo = ? AND local_id = ? AND mes = ?");
    $stmt->execute([$tipo, $local_id, $currentMonth]);
    $data = $stmt->fetch(PDO::FETCH_ASSOC);
    return $data ? (int)$data['visitado'] : 0;
}

/**
 * Função para renderizar as linhas da tabela.
 */
function renderTable($data, $tipo, $pdo, $currentMonth) {
    foreach ($data as $row) {
        $visitado = getVisitStatus($pdo, $tipo, $row['id'], $currentMonth);
        echo "<tr data-local-id='{$row['id']}' data-tipo='{$tipo}'>";
        echo "<td>" . htmlspecialchars($row['nome']) . "</td>";
        echo "<td>" . htmlspecialchars($row['endereco']) . "</td>";
        echo "<td>" . htmlspecialchars($row['bairro']) . "</td>";
        echo "<td class='text-center'>";
        echo "<label class='switch'>";
        echo "<input type='checkbox' class='visitado-checkbox' " . ($visitado ? "checked" : "") . ">";
        echo "<span class='slider round'></span>";
        echo "</label>";
        echo "</td>";
        echo "</tr>";
    }
}

// Cálculo dos dados para o dashboard de visitas
$totalSecretarias = count($secretarias);
$totalInstituicoes = count($instituicoes);
$totalLocais = $totalSecretarias + $totalInstituicoes;

$visitedSecretarias = 0;
foreach ($secretarias as $sec) {
    $visitedSecretarias += getVisitStatus($pdo, 'secretaria', $sec['id'], $currentMonth);
}
$pendingSecretarias = $totalSecretarias - $visitedSecretarias;

$visitedInstituicoes = 0;
foreach ($instituicoes as $inst) {
    $visitedInstituicoes += getVisitStatus($pdo, 'instituicao', $inst['id'], $currentMonth);
}
$pendingInstituicoes = $totalInstituicoes - $visitedInstituicoes;
?>
<!DOCTYPE html>
<html lang="pt">
<head>
  <meta charset="UTF-8">
  <title>Visitas Programadas - Admin - Unica Serviços</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <!-- Bootstrap CSS e Font Awesome -->
  <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.1/css/all.min.css">
  <!-- Animate.css -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css"/>
  <!-- CSS Customizado (pode ser integrado ao admin-estilos.css) -->
  <link rel="stylesheet" href="../assets/css/teste.css">
  
</head>
<body>
  <!-- Cabeçalho: Inclua seu header padrão -->
  <?php include 'header.php'; ?>
  
  <!-- Botão Voltar para Index -->
  <div class="container mt-3">
    <div class="text-right">
      <a href="painel.php" class="voltar-button animate__animated animate__pulse animate__infinite">
        <i class="fas fa-arrow-left"></i> Voltar
      </a>
    </div>
  </div>
  
  <!-- Seção de Dashboard Resumido -->
  <div class="container mt-4">
    <div class="row resumo-cards">
      <div class="col-md-6 mb-3">
        <div class="card">
          <div class="card-body">
            <h5 class="card-title">Secretarias</h5>
            <p class="card-text">
              Total: <?php echo $totalSecretarias; ?><br>
              Visitadas: <?php echo $visitedSecretarias; ?><br>
              Pendentes: <?php echo $pendingSecretarias; ?>
            </p>
          </div>
        </div>
      </div>
      <div class="col-md-6 mb-3">
        <div class="card">
          <div class="card-body">
            <h5 class="card-title">Instituições</h5>
            <p class="card-text">
              Total: <?php echo $totalInstituicoes; ?><br>
              Visitadas: <?php echo $visitedInstituicoes; ?><br>
              Pendentes: <?php echo $pendingInstituicoes; ?>
            </p>
          </div>
        </div>
      </div>
    </div>
  </div>
  
  <!-- Botões Marcar / Limpar Todos -->
  <div class="container">
    <div class="btn-group-custom">
      <button id="marcarTodos" class="btn btn-success">Marcar Todos</button>
      <button id="limparTodos" class="btn btn-danger">Limpar Todos</button>
    </div>
  </div>
  
  <!-- Conteúdo Principal: Abas com Tabelas -->
  <div class="visitas-container">
    <h2>Visitas Programadas</h2>
    
    <ul class="nav nav-tabs" id="visitasTab" role="tablist">
      <li class="nav-item">
        <a class="nav-link active" id="secretarias-tab" data-toggle="tab" href="#secretarias" role="tab" aria-controls="secretarias" aria-selected="true">Secretarias</a>
      </li>
      <li class="nav-item">
        <a class="nav-link" id="instituicoes-tab" data-toggle="tab" href="#instituicoes" role="tab" aria-controls="instituicoes" aria-selected="false">Escolas/Creches</a>
      </li>
    </ul>
    <div class="tab-content" id="visitasTabContent">
      <!-- Tab Secretarias -->
      <div class="tab-pane fade show active" id="secretarias" role="tabpanel" aria-labelledby="secretarias-tab">
        <div class="table-responsive mt-3">
          <table class="table table-striped table-bordered">
            <thead>
              <tr>
                <th>Secretaria</th>
                <th>Endereço</th>
                <th>Bairro</th>
                <th>Visitado</th>
              </tr>
            </thead>
            <tbody>
              <?php renderTable($secretarias, "secretaria", $pdo, $currentMonth); ?>
            </tbody>
          </table>
        </div>
      </div>
      <!-- Tab Instituições -->
      <div class="tab-pane fade" id="instituicoes" role="tabpanel" aria-labelledby="instituicoes-tab">
        <div class="table-responsive mt-3">
          <table class="table table-striped table-bordered">
            <thead>
              <tr>
                <th>Instituição</th>
                <th>Endereço</th>
                <th>Bairro</th>
                <th>Visitado</th>
              </tr>
            </thead>
            <tbody>
              <?php renderTable($instituicoes, "instituicao", $pdo, $currentMonth); ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
    
    <!-- Botão de Exportação de Dados -->
    <div class="text-right mb-4">
      <a href="export_visitas.php" class="btn btn-success"><i class="fas fa-file-csv"></i> Exportar Dados (CSV)</a>
    </div>
  </div>
  
  <!-- jQuery e Bootstrap JS -->
  <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
  <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
  <script>
    // Atualização automática via toggle switch
    $('.visitado-checkbox').change(function(){
      var row = $(this).closest('tr');
      var localId = row.data('local-id');
      var tipo = row.data('tipo');
      var visitado = $(this).is(':checked') ? 1 : 0;
      
      $.ajax({
        url: 'update_visita.php',
        type: 'POST',
        data: {
          tipo: tipo,
          local_id: localId,
          visitado: visitado,
          mes: '<?php echo $currentMonth; ?>'
        },
        success: function(response){
          console.log("Status atualizado para local_id: " + localId);
        },
        error: function(){
          alert("Erro ao atualizar o status!");
        }
      });
    });
    
    // Botão Marcar Todos: marca todos os checkboxes que não estão marcados
    $('#marcarTodos').click(function(){
      $('.visitado-checkbox:not(:checked)').each(function(){
        $(this).prop('checked', true).trigger('change');
      });
    });
    
    // Botão Limpar Todos: desmarca todos os checkboxes que estão marcados
    $('#limparTodos').click(function(){
      $('.visitado-checkbox:checked').each(function(){
        $(this).prop('checked', false).trigger('change');
      });
    });
  </script>
  
  <?php
  if(isset($_SESSION['sucesso'])){
    echo '<div class="alert alert-success text-center" role="alert">' . $_SESSION['sucesso'] . '</div>';
    unset($_SESSION['sucesso']);
  }
  if(isset($_SESSION['erro'])){
    echo '<div class="alert alert-danger text-center" role="alert">' . $_SESSION['erro'] . '</div>';
    unset($_SESSION['erro']);
  }
  ?>
</body>
</html>
