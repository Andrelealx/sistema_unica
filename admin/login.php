<?php
session_start();
if(isset($_SESSION['admin_logged']) && $_SESSION['admin_logged'] === true){
  header("Location: painel.php");
  exit;
}
?>
<!DOCTYPE html>
<html lang="pt">
<head>
  <meta charset="UTF-8">
  <title>Admin - Login</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <!-- Bootstrap CSS -->
  <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
  <!-- Animate.css para animações -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css"/>
  <!-- Google Fonts -->
  <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap" rel="stylesheet">
  <!-- Font Awesome para ícones -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.1/css/all.min.css">
  <style>
    /* Fundo do site e tipografia base */
    body {
      background-color: #001f3f; /* Fundo azul escuro */
      font-family: 'Roboto', sans-serif;
      color: #f8f9fa;
      margin: 0;
      padding-bottom: 20px;
    }
    
    /* Centraliza o container de login verticalmente */
    .login-page {
      display: flex;
      align-items: center;
      justify-content: center;
      min-height: 100vh;
    }
    
    /* Container do login com fundo branco e detalhes em azul */
    .login-container {
      max-width: 400px;
      background-color: #ffffff; /* Fundo branco */
      border: 1px solid #0056b3;
      border-radius: 8px;
      padding: 30px;
      box-shadow: 0 0 20px rgba(0,0,0,0.5);
    }
    
    /* Logo centralizada */
    .login-logo {
      text-align: center;
      margin-bottom: 20px;
    }
    .login-logo img {
      max-width: 150px;
    }
    
    /* Título do login */
    .login-title {
      text-align: center;
      margin-bottom: 20px;
      font-weight: 700;
      color: #333; /* Texto escuro */
    }
    
    /* Estilização dos campos do formulário (inputs, selects e textarea) */
    .form-control {
      background-color: #ffffff;
      border: 1px solid #ccc;
      color: #333;
      border-radius: 5px;
    }
    .form-control:focus {
      background-color: #ffffff;
      border-color: #0056b3;
      color: #333;
      box-shadow: none;
    }
    
    /* Rótulos */
    label {
      color: #333;
      font-weight: 500;
    }
    
    /* Botão de login */
    .btn-login {
      background-color: #007bff;
      border: none;
      font-size: 1.1rem;
      font-weight: 500;
      border-radius: 5px;
      transition: background-color 0.3s;
    }
    .btn-login:hover {
      background-color: #0056b3;
    }
    
    /* Posição do ícone de visibilidade de senha */
    .position-relative {
      position: relative;
    }
    .toggle-password {
      position: absolute;
      right: 15px;        /* Distância da borda direita do input */
      top: 10px;          /* Alinha o ícone mais próximo do texto */
      cursor: pointer;
      color: #333;
    }
    /* Ajusta o espaçamento interno do input para não sobrepor o ícone */
    .form-group.position-relative .form-control {
      padding-right: 2.5rem; /* Espaço para o ícone */
    }
  </style>
</head>
<body class="animate__animated animate__fadeIn">
  <div class="login-page">
    <div class="login-container animate__animated animate__fadeInDown">
      <div class="login-logo">
        <img src="../assets/img/logo.png" alt="Unica Serviços">
      </div>
      <h2 class="login-title">Login Administrador</h2>
      <?php
        if(isset($_SESSION['error'])){
          echo '<div class="alert alert-danger text-center">' . $_SESSION['error'] . '</div>';
          unset($_SESSION['error']);
        }
      ?>
      <form action="login_process.php" method="POST">
        <div class="form-group">
          <label for="login">Usuário ou E-mail</label>
          <input type="text" name="login" id="login" class="form-control" placeholder="Seu usuário ou e-mail" required>
        </div>
        <div class="form-group position-relative">
          <label for="senha">Senha</label>
          <input type="password" name="senha" id="senha" class="form-control" placeholder="Sua senha" required>
          <span class="toggle-password"><i class="fas fa-eye"></i></span>
        </div>
        <button type="submit" class="btn btn-login btn-block animate__animated animate__pulse">Entrar</button>
      </form>
    </div>
  </div>
  
  <!-- jQuery e Bootstrap JS -->
  <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
  <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
  <script>
    $(document).ready(function(){
      $('.toggle-password').on('click', function(){
        var input = $('#senha');
        var icon = $(this).find('i');
        if(input.attr('type') === 'password'){
          input.attr('type', 'text');
          icon.removeClass('fa-eye').addClass('fa-eye-slash');
        } else {
          input.attr('type', 'password');
          icon.removeClass('fa-eye-slash').addClass('fa-eye');
        }
      });
    });
  </script>
</body>
</html>
