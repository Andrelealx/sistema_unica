<?php
session_start();
if (!isset($_SESSION['admin_logged']) || $_SESSION['admin_logged'] !== true) {
    header("Location: login.php");
    exit;
}

$currentPage = basename($_SERVER['PHP_SELF'], ".php");
$pageTitles = [
  'painel'     => 'Painel',
  'dashboard'  => 'Dashboard',
  'visitas'    => 'Visitas',
  'usuarios'   => 'Gerenciar Usuários',
  'estoque'    => 'Estoque',
  'encomendas' => 'Controle de Encomendas',
  'metas'      => 'Metas'
];

$title = isset($pageTitles[$currentPage]) ? $pageTitles[$currentPage] : 'Admin';
?>
<!-- CSS Inline para o cabeçalho -->
<style>
  .navbar {
      background-color: #343a40; /* Fundo escuro */
      box-shadow: 0 2px 6px rgba(0,0,0,0.3);
  }
  .navbar-brand {
      display: flex;
      align-items: center;
  }
  .navbar-brand img {
      max-height: 40px;
      margin-right: 10px;
  }
  .navbar-brand span {
      font-size: 1.25rem;
      color: #fff;
  }
  .navbar-nav .nav-link {
      color: #fff !important;
      font-weight: 500;
  }
  .navbar-nav .nav-link:hover {
      color: #ccc !important;
  }
  .navbar-text {
      color: #fff;
      font-size: 1rem;
  }
  /* Destaca a página ativa com Azul Capri (#00BFFF) */
  .nav-item.active .nav-link {
      font-weight: bold;
      color: #00BFFF !important;
  }
</style>

<nav class="navbar navbar-expand-lg navbar-dark bg-dark shadow-sm" role="navigation">
  <a class="navbar-brand" href="../index.php">
    <img src="../assets/img/logo.png" alt="Logo">
    <span><?php echo($title); ?></span>
  </a>
  <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#adminNavbar" 
          aria-controls="adminNavbar" aria-expanded="false" aria-label="Alternar navegação">
    <span class="navbar-toggler-icon"></span>
  </button>
  
  <div class="collapse navbar-collapse" id="adminNavbar">
    <ul class="navbar-nav mr-auto">
      <li class="nav-item <?php echo ($currentPage == 'painel') ? 'active' : ''; ?>">
        <a class="nav-link" href="painel.php"><i class="fas fa-tachometer-alt"></i> Chamados</a>
      </li>
      <li class="nav-item <?php echo ($currentPage == 'dashboard') ? 'active' : ''; ?>">
        <a class="nav-link" href="dashboard.php"><i class="fas fa-chart-line"></i> Dashboard</a>
      </li>
      <li class="nav-item <?php echo ($currentPage == 'visitas') ? 'active' : ''; ?>">
        <a class="nav-link" href="visitas.php"><i class="fas fa-calendar-alt"></i> Visitas</a>
      </li>
      <?php if(isset($_SESSION['admin_id']) && $_SESSION['nivel_acesso'] == 2): ?>
        <li class="nav-item <?php echo ($currentPage == 'usuarios') ? 'active' : ''; ?>">
          <a class="nav-link" href="usuarios.php"><i class="fas fa-users-cog"></i> Gerenciar Usuários</a>
        </li>
        <li class="nav-item <?php echo ($currentPage == 'estoque') ? 'active' : ''; ?>">
          <a class="nav-link" href="estoque.php"><i class="fas fa-boxes"></i> Estoque</a>
        </li>
        <li class="nav-item <?php echo ($currentPage == 'encomendas') ? 'active' : ''; ?>">
          <a class="nav-link" href="encomendas.php"><i class="fas fa-truck"></i> Encomendas</a>
        </li>
        <!-- Nova página metas.php -->
        <li class="nav-item <?php echo ($currentPage == 'metas') ? 'active' : ''; ?>">
          <a class="nav-link" href="metas.php"><i class="fas fa-bullhorn"></i> Metas</a>
        </li>
      <?php endif; ?>
    </ul>
    <ul class="navbar-nav">
      <li class="nav-item">
        <span class="navbar-text mr-3">Bem-vindo, <?php echo htmlspecialchars($_SESSION['admin_nome']); ?></span>
      </li>
      <li class="nav-item">
        <a class="nav-link" href="logout.php"><i class="fas fa-sign-out-alt"></i> Sair</a>
      </li>
    </ul>
  </div>
</nav>
