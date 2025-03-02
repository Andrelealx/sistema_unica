<?php
  session_start();
?>
<!DOCTYPE html>
<html lang="pt">
<head>
  <meta charset="UTF-8">
  <title>Unica Serviços - Abrir Chamado</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <!-- Favicon -->
  <link rel="icon" href="assets/img/favicon.ico" type="image/x-icon">
  <!-- Bootstrap CSS -->
  <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
  <!-- Animate.css para animações -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css"/>
  <!-- Google Fonts -->
  <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap" rel="stylesheet">
  <!-- CSS Customizado -->
  <link rel="stylesheet" href="assets/css/estilos.css">
  <style>
    /* Fundo com degradê tecnológico */
    body {
      background: linear-gradient(135deg, #001f3f 0%, #003366 100%);
      font-family: 'Roboto', sans-serif;
      color: #f8f9fa;
      margin: 0;
      padding-bottom: 20px;
    }

    /* Cabeçalho com efeito profissional */
    header.header {
      background-color: #001237;
      padding: 20px 0;
      box-shadow: 0 2px 6px rgba(0, 0, 0, 0.3);
      position: relative;
      z-index: 1000;
      animation: slideInDown 1s ease-out;
    }
    @keyframes slideInDown {
      0% { transform: translateY(-100%); opacity: 0; }
      100% { transform: translateY(0); opacity: 1; }
    }
    header.header .logo img {
      max-height: 60px;
      animation: bounceInLeft 1s;
    }
    @keyframes bounceInLeft {
      0% { transform: translateX(-100%); opacity: 0; }
      60% { transform: translateX(10%); opacity: 1; }
      80% { transform: translateX(-5%); }
      100% { transform: translateX(0); }
    }
    header.header .titulo h1 {
      font-size: 2.5rem;
      margin: 0;
      color: #f8f9fa;
      text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.5);
      animation: fadeInDown 1s;
    }
    header.header .admin-button a {
      font-size: 1rem;
      padding: 10px 20px;
      animation: fadeInRight 1s;
    }
    @keyframes fadeInRight {
      0% { transform: translateX(100%); opacity: 0; }
      100% { transform: translateX(0); opacity: 1; }
    }

    /* Container do formulário com efeito de card moderno */
    .container.my-4 {
      background-color: rgba(255, 255, 255, 0.05);
      padding: 30px;
      border-radius: 8px;
      box-shadow: 0 4px 12px rgba(0,0,0,0.2);
      margin-top: 30px;
      margin-bottom: 0; /* remove a margem inferior */
    }

    form#formChamado label {
      font-weight: 500;
      color: #f8f9fa;
    }
    form#formChamado .form-control,
    form#formChamado .form-control-file {
      background-color: #00274d;
      border: 1px solid #0056b3;
      color: #f8f9fa;
    }
    form#formChamado .form-control:focus {
      border-color: #66afe9;
      box-shadow: none;
    }
    form#formChamado button {
      background-color: #007bff;
      border: none;
      font-size: 1.1rem;
      font-weight: 500;
      transition: background-color 0.3s ease;
    }
    form#formChamado button:hover {
      background-color: #0056b3;
    }

    /* Notificação centralizada com animações aprimoradas */
    #notification {
      position: fixed;
      top: 50%;
      left: 50%;
      transform: translate(-50%, -50%);
      background-color: #28a745; /* Verde */
      color: #fff;
      padding: 20px 30px;
      border-radius: 8px;
      font-size: 1.25rem;
      z-index: 1050;
      box-shadow: 0 2px 10px rgba(0,0,0,0.3);
      display: none;
      animation: zoomIn 0.8s;
    }
    @keyframes zoomIn {
      0% { transform: translate(-50%, -50%) scale(0); opacity: 0; }
      100% { transform: translate(-50%, -50%) scale(1); opacity: 1; }
    }
    .fadeOutSlideUp {
      animation: fadeOutUp 1s forwards;
    }
    @keyframes fadeOutUp {
      0% { opacity: 1; transform: translate(-50%, -50%); }
      100% { opacity: 0; transform: translate(-50%, -60%); }
    }

    /* Rodapé com mesma cor do cabeçalho */
    footer.footer {
      background-color: #001237; /* Mesma cor do cabeçalho */
      color: #f8f9fa;
      text-align: center;
      padding: 15px 0;
      margin-top: 0; /* Garante que não haja faixa extra acima do rodapé */
    }
    footer.footer p {
      margin: 0;
    }
  </style>
</head>
<body class="animate__animated animate__fadeIn">
  <!-- Cabeçalho -->
  <header class="header">
    <div class="container d-flex justify-content-between align-items-center">
      <div class="logo">
        <img src="assets/img/logo.png" alt="Unica Serviços">
      </div>
      <div class="titulo">
        <h1>Abrir Chamado</h1>
      </div>
      <div class="admin-button">
        <a href="consulta.php" class="btn btn-outline-light animate__animated animate__pulse animate__infinite">Consultar Chamado</a>
        <a href="admin/login.php" class="btn btn-outline-light animate__animated animate__pulse animate__infinite">Admin</a>
      </div>
    </div>
  </header>

  <!-- Notificação centralizada (aparece apenas se houver mensagem na sessão) -->
  <?php
    if(isset($_SESSION['sucesso'])){
      echo '<div id="notification" class="animate__animated animate__zoomInDown">' . $_SESSION['sucesso'] . '</div>';
      unset($_SESSION['sucesso']);
    }
    if(isset($_SESSION['erro'])){
      echo '<div id="notification" class="animate__animated animate__zoomInDown" style="background-color:#dc3545;">' . $_SESSION['erro'] . '</div>';
      unset($_SESSION['erro']);
    }
  ?>

  <!-- Container do Formulário -->
  <div class="container my-4">
    <form id="formChamado" action="processar_chamado.php" method="POST" enctype="multipart/form-data" class="animate__animated animate__fadeInUp">
      <div class="form-group">
        <label for="nome">Nome</label>
        <input type="text" class="form-control" id="nome" name="nome" placeholder="Seu nome" required>
      </div>
      <div class="form-group">
        <label for="celular">Celular</label>
        <input type="tel" class="form-control" id="celular" name="celular" placeholder="Seu número de celular" required>
      </div>
      <div class="form-group">
        <label for="local">Local</label>
        <input type="text" class="form-control" id="local" name="local" placeholder="Local de atendimento" required>
      </div>
      <div class="form-group">
        <label for="setor">Setor</label>
        <input type="text" class="form-control" id="setor" name="setor" placeholder="Setor" required>
      </div>
      <div class="form-group">
        <label for="urgencia">Nível de Urgência</label>
        <select class="form-control" id="urgencia" name="urgencia" required>
          <option value="">Selecione</option>
          <option value="Baixo">Baixo</option>
          <option value="Médio">Médio</option>
          <option value="Alto">Alto</option>
          <option value="Crítico">Crítico</option>
        </select>
      </div>
      <div class="form-group">
        <label for="descricao">Descrição do Problema</label>
        <textarea class="form-control" id="descricao" name="descricao" rows="5" placeholder="Descreva o problema com detalhes" required></textarea>
      </div>
      <div class="form-group">
        <label for="anexo">Anexar Arquivo (opcional)</label>
        <input type="file" class="form-control-file" id="anexo" name="anexo">
      </div>
      <button type="submit" class="btn btn-primary btn-lg btn-block animate__animated animate__pulse">Enviar Chamado</button>
    </form>
  </div>

  <!-- Rodapé -->
  <footer class="footer">
    <div class="container">
      <p>Se preferir, você pode solicitar seu chamado pelo número <strong>21 2010-7081</strong>.</p>
      <p>Desenvolvido por <strong>Ùnica Serviços</strong></p>
    </div>
  </footer>

  <!-- jQuery e Bootstrap JS -->
  <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
  <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
  <!-- JS Customizado -->
  <script src="assets/js/scripts.js"></script>
  <script>
    $(document).ready(function(){
      if ($("#notification").length) {
        $("#notification").show();
        setTimeout(function(){
          $("#notification").fadeOut(1000);
        }, 5000);
      }
    });
  </script>
  <?php
session_start();
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
