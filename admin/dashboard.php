<?php
// Ativa exibição de erros para depuração (remova em produção)
//error_reporting(E_ALL);
//ini_set('display_errors', 1);

session_start();

if (!isset($_SESSION['admin_logged']) || $_SESSION['admin_logged'] !== true) {
    header("Location: login.php");
    exit;
}

date_default_timezone_set('America/Sao_Paulo');
require_once '../inc/conexao.php';

// Total de Chamados
$totalTickets = $pdo->query("SELECT COUNT(*) as total FROM tickets")->fetch(PDO::FETCH_ASSOC)['total'];

// Chamados por Status
$statusData = [];
$stmt = $pdo->query("SELECT status, COUNT(*) as total FROM tickets GROUP BY status");
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $statusData[$row['status']] = $row['total'];
}
$statuses = ['Aberto', 'Em Andamento', 'Resolvido', 'Cancelado'];
$statusCounts = [];
foreach ($statuses as $status) {
    $statusCounts[] = isset($statusData[$status]) ? (int)$statusData[$status] : 0;
}

// Média de Resolução (calculada em PHP, considerando que as datas estão armazenadas no horário local)
$stmtResolved = $pdo->query("SELECT data_criacao, data_atualizacao FROM tickets WHERE status = 'Resolvido'");
$resolvedTickets = $stmtResolved->fetchAll(PDO::FETCH_ASSOC);
$totalResolved = count($resolvedTickets);
$sumSeconds = 0;
foreach ($resolvedTickets as $ticket) {
    // Se as datas estão no horário local, utilize 'America/Sao_Paulo'
    $start = new DateTime($ticket['data_criacao'], new DateTimeZone('America/Sao_Paulo'));
    $end   = new DateTime($ticket['data_atualizacao'], new DateTimeZone('America/Sao_Paulo'));
    $diffSeconds = $end->getTimestamp() - $start->getTimestamp();
    $sumSeconds += $diffSeconds;
}
if ($totalResolved > 0) {
    $avgSeconds = $sumSeconds / $totalResolved;
    // Subtrai 3 horas (10.800 segundos) do resultado médio
    $avgSeconds -= 10800;
    if ($avgSeconds < 0) {
        $avgSeconds = 0;
    }
    $hours   = (int) floor($avgSeconds / 3600);
    $minutes = (int) floor((($avgSeconds % 3600) / 60));
    $seconds = (int) floor($avgSeconds % 60);
    $avgResolutionTime = sprintf("%02dh %02dm %02ds", $hours, $minutes, $seconds);
} else {
    $avgResolutionTime = "N/A";
}





// Tendência Mensal (últimos 12 meses)
$monthlyTrend = [];
for ($i = 11; $i >= 0; $i--) {
    $start = date('Y-m-01', strtotime("-$i months"));
    $end = date('Y-m-t', strtotime("-$i months"));
    $stmtMonth = $pdo->prepare("SELECT COUNT(*) as total FROM tickets WHERE data_criacao BETWEEN ? AND ?");
    $stmtMonth->execute([$start . " 00:00:00", $end . " 23:59:59"]);
    $resultMonth = $stmtMonth->fetch(PDO::FETCH_ASSOC);
    $monthlyTrend[date('M Y', strtotime($start))] = (int)$resultMonth['total'];
}
$monthlyLabels = array_keys($monthlyTrend);
$monthlyData = array_values($monthlyTrend);

// Top 5 Setores com mais Chamados
$sectorData = $pdo->query("SELECT setor, COUNT(*) as total FROM tickets GROUP BY setor ORDER BY total DESC LIMIT 5")->fetchAll(PDO::FETCH_ASSOC);
$sectorLabels = [];
$sectorCounts = [];
foreach ($sectorData as $row) {
    $sectorLabels[] = $row['setor'];
    $sectorCounts[] = (int)$row['total'];
}

// Tendência Diária (últimos 7 dias)
$dailyTrend = [];
for ($i = 6; $i >= 0; $i--) {
    $day = date("Y-m-d", strtotime("-$i days"));
    $stmtDay = $pdo->prepare("SELECT COUNT(*) as total FROM tickets WHERE DATE(data_criacao) = ?");
    $stmtDay->execute([$day]);
    $resultDay = $stmtDay->fetch(PDO::FETCH_ASSOC);
    $dailyTrend[date("d/m", strtotime($day))] = (int)$resultDay['total'];
}
$dailyLabels = array_keys($dailyTrend);
$dailyData = array_values($dailyTrend);

// Inclui o header (certifique-se de que ele não inicia a sessão novamente)
include 'header.php';
?>
<!DOCTYPE html>
<html lang="pt">
<head>
  <meta charset="UTF-8">
  <title>Dashboard Administrativo - Unica Serviços</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <!-- Bootstrap CSS e Font Awesome -->
  <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.1/css/all.min.css">
  <!-- CSS Customizado para o Dashboard -->
  <link rel="stylesheet" href="../assets/css/admin-estilos.css">
  <!-- Chart.js -->
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
  <style>
    .card-header i { margin-right: 8px; }
    .refresh-btn { cursor: pointer; }

    /* Força o texto a usar um tom de cinza-escuro agradável (#343a40) */
    .card .card-header,
    .card .card-body,
    .card .card-body * {
      color: #343a40 !important;
    }
  </style>
</head>
<body>
  <div class="container-fluid mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
      <h2>Dashboard Administrativo</h2>
      <div>
        <span class="badge badge-info p-2">Última atualização: <?php echo date('d/m/Y H:i:s'); ?></span>
        <button class="btn btn-sm btn-outline-secondary refresh-btn" onclick="location.reload();">
          <i class="fas fa-sync-alt"></i> Atualizar
        </button>
      </div>
    </div>
    
    <!-- Cards com Métricas -->
    <div class="row">
      <div class="col-md-2 mb-3">
        <div class="card bg-primary shadow">
          <div class="card-body text-center">
            <i class="fas fa-ticket-alt fa-2x"></i>
            <h6 class="card-title mt-2">Total de Chamados</h6>
            <p class="card-text display-4"><?php echo $totalTickets; ?></p>
          </div>
        </div>
      </div>
      <div class="col-md-2 mb-3">
        <div class="card bg-danger shadow">
          <div class="card-body text-center">
            <i class="fas fa-exclamation-circle fa-2x"></i>
            <h6 class="card-title mt-2">Abertos</h6>
            <p class="card-text display-4"><?php echo isset($statusData['Aberto']) ? $statusData['Aberto'] : 0; ?></p>
          </div>
        </div>
      </div>
      <div class="col-md-2 mb-3">
        <div class="card bg-warning shadow">
          <div class="card-body text-center">
            <i class="fas fa-spinner fa-2x"></i>
            <h6 class="card-title mt-2">Em Andamento</h6>
            <p class="card-text display-4"><?php echo isset($statusData['Em Andamento']) ? $statusData['Em Andamento'] : 0; ?></p>
          </div>
        </div>
      </div>
      <div class="col-md-2 mb-3">
        <div class="card bg-success shadow">
          <div class="card-body text-center">
            <i class="fas fa-check-circle fa-2x"></i>
            <h6 class="card-title mt-2">Resolvidos</h6>
            <p class="card-text display-4"><?php echo isset($statusData['Resolvido']) ? $statusData['Resolvido'] : 0; ?></p>
          </div>
        </div>
      </div>
      <div class="col-md-2 mb-3">
        <div class="card bg-secondary shadow">
          <div class="card-body text-center">
            <i class="fas fa-times-circle fa-2x"></i>
            <h6 class="card-title mt-2">Cancelados</h6>
            <p class="card-text display-4"><?php echo isset($statusData['Cancelado']) ? $statusData['Cancelado'] : 0; ?></p>
          </div>
        </div>
      </div>
      <div class="col-md-2 mb-3">
        <div class="card bg-info shadow">
          <div class="card-body text-center">
            <i class="fas fa-clock fa-2x"></i>
            <h6 class="card-title mt-2">Média Resolução</h6>
            <p class="card-text" style="font-size:1.2rem;"><?php echo $avgResolutionTime; ?></p>
          </div>
        </div>
      </div>
    </div>
    
    <!-- Novidades / Avisos -->
    <div class="row">
      <div class="col-md-4 mb-3">
        <div class="card border-info shadow">
          <div class="card-header bg-info">
            <i class="fas fa-bullhorn"></i> Novidade
          </div>
          <div class="card-body">
            <h5 class="card-title">Atualização do Sistema</h5>
            <p class="card-text">Lançada nova versão com melhorias de desempenho, segurança e uma interface mais intuitiva.</p>
          </div>
        </div>
      </div>
      <div class="col-md-4 mb-3">
        <div class="card border-warning shadow">
          <div class="card-header bg-warning">
            <i class="fas fa-tools"></i> Aviso
          </div>
          <div class="card-body">
            <h5 class="card-title">Manutenção Programada</h5>
            <p class="card-text">O sistema passará por manutenção neste sábado, das 22h às 02h. Planeje suas atividades.</p>
          </div>
        </div>
      </div>
      <div class="col-md-4 mb-3">
        <div class="card border-success shadow">
          <div class="card-header bg-success">
            <i class="fas fa-lightbulb"></i> Novidade
          </div>
          <div class="card-body">
            <h5 class="card-title">Nova Funcionalidade</h5>
            <p class="card-text">Agora é possível filtrar chamados por prioridade e tempo de resposta, facilitando a análise.</p>
          </div>
        </div>
      </div>
    </div>
    
    <!-- Gráficos -->
    <div class="row">
      <!-- Status dos Chamados (Doughnut) -->
      <div class="col-md-6 mb-4">
        <div class="card shadow">
          <div class="card-header">
            <i class="fas fa-chart-pie"></i> Status dos Chamados
          </div>
          <div class="card-body">
            <canvas id="statusChart"></canvas>
          </div>
        </div>
      </div>
      <!-- Tendência Mensal (Linha) -->
      <div class="col-md-6 mb-4">
        <div class="card shadow">
          <div class="card-header">
            <i class="fas fa-chart-line"></i> Chamados por Mês (Últimos 12 meses)
          </div>
          <div class="card-body">
            <canvas id="monthlyChart"></canvas>
          </div>
        </div>
      </div>
    </div>
    
    <div class="row">
      <!-- Chamados por Setor (Pizza) -->
      <div class="col-md-6 mb-4">
        <div class="card shadow">
          <div class="card-header">
            <i class="fas fa-th-list"></i> Chamados por Setor (Top 5)
          </div>
          <div class="card-body">
            <canvas id="sectorChart"></canvas>
          </div>
        </div>
      </div>
      <!-- Chamados Diários (Barra) -->
      <div class="col-md-6 mb-4">
        <div class="card shadow">
          <div class="card-header">
            <i class="fas fa-calendar-day"></i> Chamados Diários (Últimos 7 dias)
          </div>
          <div class="card-body">
            <canvas id="dailyChart"></canvas>
          </div>
        </div>
      </div>
    </div>
    
  </div>
  
  <!-- Scripts para Gráficos -->
  <script>
    // Gráfico de Status (Doughnut)
    const statusCtx = document.getElementById('statusChart').getContext('2d');
    const statusChart = new Chart(statusCtx, {
      type: 'doughnut',
      data: {
        labels: <?php echo json_encode($statuses); ?>,
        datasets: [{
          data: <?php echo json_encode($statusCounts); ?>,
          backgroundColor: [
            'rgba(220,53,69,0.7)',    // Aberto
            'rgba(255,193,7,0.7)',     // Em Andamento
            'rgba(40,167,69,0.7)',     // Resolvido
            'rgba(108,117,125,0.7)'    // Cancelado
          ],
          borderColor: [
            'rgba(220,53,69,1)',
            'rgba(255,193,7,1)',
            'rgba(40,167,69,1)',
            'rgba(108,117,125,1)'
          ],
          borderWidth: 1
        }]
      },
      options: {
        responsive: true,
        plugins: { legend: { position: 'bottom' } }
      }
    });
    
    // Gráfico de Tendência Mensal (Linha)
    const monthlyCtx = document.getElementById('monthlyChart').getContext('2d');
    const monthlyChart = new Chart(monthlyCtx, {
      type: 'line',
      data: {
        labels: <?php echo json_encode($monthlyLabels); ?>,
        datasets: [{
          label: 'Chamados',
          data: <?php echo json_encode($monthlyData); ?>,
          backgroundColor: 'rgba(0, 123, 255, 0.2)',
          borderColor: 'rgba(0, 123, 255, 1)',
          borderWidth: 2,
          fill: true,
          tension: 0.3
        }]
      },
      options: {
        responsive: true,
        scales: { y: { beginAtZero: true } }
      }
    });
    
    // Gráfico de Setores (Pizza)
    const sectorCtx = document.getElementById('sectorChart').getContext('2d');
    const sectorChart = new Chart(sectorCtx, {
      type: 'pie',
      data: {
        labels: <?php echo json_encode($sectorLabels); ?>,
        datasets: [{
          data: <?php echo json_encode($sectorCounts); ?>,
          backgroundColor: [
            'rgba(54, 162, 235, 0.7)',
            'rgba(255, 99, 132, 0.7)',
            'rgba(255, 206, 86, 0.7)',
            'rgba(75, 192, 192, 0.7)',
            'rgba(153, 102, 255, 0.7)'
          ],
          borderColor: [
            'rgba(54, 162, 235, 1)',
            'rgba(255, 99, 132, 1)',
            'rgba(255, 206, 86, 1)',
            'rgba(75, 192, 192, 1)',
            'rgba(153, 102, 255, 1)'
          ],
          borderWidth: 1
        }]
      },
      options: {
        responsive: true,
        plugins: { legend: { position: 'bottom' } }
      }
    });
    
    // Gráfico de Chamados Diários (Barra)
    const dailyCtx = document.getElementById('dailyChart').getContext('2d');
    const dailyChart = new Chart(dailyCtx, {
      type: 'bar',
      data: {
        labels: <?php echo json_encode($dailyLabels); ?>,
        datasets: [{
          label: 'Chamados',
          data: <?php echo json_encode($dailyData); ?>,
          backgroundColor: 'rgba(255, 159, 64, 0.7)',
          borderColor: 'rgba(255, 159, 64, 1)',
          borderWidth: 1
        }]
      },
      options: {
        responsive: true,
        scales: { y: { beginAtZero: true } }
      }
    });
  </script>
  
  <!-- Bootstrap JS e dependências -->
  <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
  <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.bundle.min.js"></script>
</body>
</html>
