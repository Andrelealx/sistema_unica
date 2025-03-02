<?php
session_start();
require_once 'inc/conexao.php';

$protocolo = trim($_GET['protocolo'] ?? '');
$chamado   = null;
$error     = '';

if ($protocolo) {
    $stmt = $pdo->prepare("SELECT * FROM tickets WHERE protocolo = ?");
    $stmt->execute([$protocolo]);
    $chamado = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$chamado) {
        $error = "Chamado não encontrado para o protocolo informado.";
    }
}
?>
<!DOCTYPE html>
<html lang="pt">
<head>
  <meta charset="UTF-8">
  <title>Consultar Chamado - Unica Serviços</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <!-- Bootstrap CSS -->
  <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
  <!-- CSS Customizado -->
  <link rel="stylesheet" href="assets/css/estilos.css">
  <!-- Animate.css para animações -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css"/>
  <style>
    /* Fundo com degradê tecnológico */
    body {
      background: linear-gradient(135deg, #001f3f 0%, #003366 100%);
      font-family: 'Roboto', sans-serif;
      color: #f8f9fa;
      margin: 0;
      padding-bottom: 20px;
    }
    
    /* Cabeçalho */
    header.header {
      background-color: #001237;
      padding: 20px 0;
      box-shadow: 0 2px 6px rgba(0,0,0,0.3);
    }
    header.header .container {
      display: flex;
      align-items: center;
      justify-content: space-between;
    }
    header.header .logo img {
      max-height: 60px;
      margin-right: 15px;
    }
    header.header .titulo h1 {
      font-size: 2.5rem;
      margin: 0;
      color: #fff;
    }
    /* Botão Voltar customizado */
    header.header .voltar-button a {
      font-size: 1rem;
      padding: 10px 20px;
      border: 1px solid #007bff;
      border-radius: 5px;
      background-color: #007bff; /* Fundo azul */
      color: #001237;           /* Texto azul escuro */
      text-decoration: none;
      transition: background-color 0.3s, color 0.3s;
    }
    header.header .voltar-button a:hover {
      background-color: #0056b3;
      color: #001237;
    }
    
    /* Container de consulta */
    .consulta-container {
      max-width: 600px;
      margin: 30px auto;
      background-color: #fff;
      color: #333;
      border-radius: 8px;
      padding: 30px;
      box-shadow: 0 4px 12px rgba(0,0,0,0.2);
    }
    .consulta-container h1 {
      font-size: 2rem;
      margin-bottom: 20px;
      text-align: center;
      color: #333;
    }
    .consulta-container label {
      font-weight: 500;
      color: #333;
    }
    .consulta-container .form-control {
      border: 1px solid #ccc;
      border-radius: 5px;
      color: #333;
    }
    .consulta-container .form-control:focus {
      border-color: #0056b3;
      box-shadow: none;
    }
    
    /* Card de exibição do chamado */
    .card {
      margin-top: 20px;
      border: none;
      border-radius: 8px;
      overflow: hidden;
      box-shadow: 0 4px 12px rgba(0,0,0,0.2);
      animation: fadeInUp 0.8s;
    }
    @keyframes fadeInUp {
      from { opacity: 0; transform: translateY(20px); }
      to { opacity: 1; transform: translateY(0); }
    }
    .card-header {
      background: linear-gradient(135deg, #0056b3 0%, #004080 100%);
      color: #fff;
      font-weight: 600;
      padding: 15px 20px;
    }
    .card-body {
      background-color: #fff;
      color: #333;
      padding: 20px;
    }
    
    /* Responsividade */
    @media (max-width: 768px) {
      .consulta-container {
        padding: 20px;
      }
      header.header .titulo h1 {
        font-size: 2rem;
      }
    }
  </style>
</head>
<body>
  <!-- Cabeçalho com logo e botão Voltar -->
  <header class="header">
    <div class="container">
      <div class="logo">
        <a href="index.php">
          <img src="assets/img/logo.png" alt="Unica Serviços">
        </a>
      </div>
      <div class="titulo">
        <h1>Consultar Chamado</h1>
      </div>
      <div class="voltar-button">
        <a href="index.php" class="animate__animated animate__pulse animate__infinite">Voltar</a>
      </div>
    </div>
  </header>
  
  <!-- Conteúdo da Página -->
  <div class="container consulta-container">
    <h1>Consultar Chamado</h1>
    <form action="consulta.php" method="GET" class="mb-4">
      <div class="form-group">
        <label for="protocolo">Número do Protocolo</label>
        <input type="text" id="protocolo" name="protocolo" class="form-control" placeholder="Digite seu número de protocolo" required>
      </div>
      <button type="submit" class="btn btn-primary btn-block">Consultar</button>
    </form>
    <?php if ($error): ?>
      <div class="alert alert-danger"><?php echo $error; ?></div>
    <?php endif; ?>

    <?php if ($chamado): ?>
      <div class="card">
        <div class="card-header">
          Protocolo: <?php echo htmlspecialchars($chamado['protocolo']); ?>
        </div>
        <div class="card-body">
          <p><strong>Status:</strong> <?php echo htmlspecialchars($chamado['status']); ?></p>
          <p><strong>Nome:</strong> <?php echo htmlspecialchars($chamado['nome']); ?></p>
          <p><strong>Setor:</strong> <?php echo htmlspecialchars($chamado['setor']); ?></p>
          <p><strong>Data de Criação:</strong> <?php echo date('d/m/Y H:i:s', strtotime($chamado['data_criacao'])); ?></p>
          <p><strong>Descrição:</strong><br><?php echo nl2br(htmlspecialchars($chamado['descricao'])); ?></p>
        </div>
      </div>
    <?php endif; ?>
  </div>
  
  <!-- jQuery e Bootstrap JS -->
  <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
  <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</body>
</html>
