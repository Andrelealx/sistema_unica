<?php include 'header.php'; ?>
<?php
session_start();
require_once '../inc/conexao.php';

$action = $_POST['action'] ?? '';

// Processa ações de criação, exclusão e atualização
if ($action === 'create') {
    $nome         = trim($_POST['nome'] ?? '');
    $email        = trim($_POST['email'] ?? '');
    $senha        = trim($_POST['senha'] ?? '');
    // 2: Super Admin, 1: Administrador, 3: Técnico de Helpdesk
    $nivel_acesso = intval($_POST['nivel_acesso'] ?? 2);

    if (empty($nome) || empty($email) || empty($senha)) {
        $_SESSION['error'] = "Preencha todos os campos obrigatórios para criar o usuário.";
    } else {
        // Verifica se o e-mail já existe
        $stmt = $pdo->prepare("SELECT id FROM usuarios WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            $_SESSION['error'] = "Já existe um usuário com esse e-mail.";
        } else {
            // Cria o hash da senha e insere no banco
            $senha_hash = password_hash($senha, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO usuarios (nome, email, senha, nivel_acesso) VALUES (?, ?, ?, ?)");
            if ($stmt->execute([$nome, $email, $senha_hash, $nivel_acesso])) {
                $_SESSION['sucesso'] = "Usuário criado com sucesso.";
            } else {
                $_SESSION['error'] = "Erro ao criar o usuário.";
            }
        }
    }
    header("Location: usuarios.php");
    exit;
    
} elseif ($action === 'delete') {
    $user_id = intval($_POST['user_id'] ?? 0);

    // Impede a exclusão do Super Admin (ID = 2, conforme sua lógica)
    if ($user_id == 2) {
        $_SESSION['error'] = "Não é permitido excluir o Super Admin.";
    } else {
        $stmt = $pdo->prepare("DELETE FROM usuarios WHERE id = ?");
        if ($stmt->execute([$user_id])) {
            $_SESSION['sucesso'] = "Usuário excluído com sucesso.";
        } else {
            $_SESSION['error'] = "Erro ao excluir o usuário.";
        }
    }
    header("Location: usuarios.php");
    exit;

} elseif ($action === 'update') {
    $user_id      = intval($_POST['user_id'] ?? 0);
    $nome         = trim($_POST['nome'] ?? '');
    $email        = trim($_POST['email'] ?? '');
    $nivel_acesso = intval($_POST['nivel_acesso'] ?? 2);

    if (empty($nome) || empty($email)) {
        $_SESSION['error'] = "Preencha os campos obrigatórios.";
    } else {
        $stmt = $pdo->prepare("UPDATE usuarios SET nome = ?, email = ?, nivel_acesso = ? WHERE id = ?");
        if ($stmt->execute([$nome, $email, $nivel_acesso, $user_id])) {
            $_SESSION['sucesso'] = "Usuário atualizado com sucesso.";
        } else {
            $_SESSION['error'] = "Erro ao atualizar o usuário.";
        }
    }
    header("Location: usuarios.php");
    exit;
}

// Seleciona todos os usuários para exibição na tabela
$stmt = $pdo->query("SELECT * FROM usuarios ORDER BY id ASC");
$usuarios = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="pt">
<head>
  <meta charset="UTF-8">
  <title>Gerenciamento de Usuários - Admin</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <!-- Bootstrap CSS e Font Awesome -->
  <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.1/css/all.min.css">
  <!-- CSS Customizado -->
  <link rel="stylesheet" href="../assets/css/admin-estilos.css">
  <style>
    /* Corpo com fundo escuro (mesmo do painel) */
    body {
      background-color: #001f3f;
      font-family: 'Roboto', sans-serif;
      color: #f8f9fa;
      margin: 0;
      padding-bottom: 20px;
    }
    /* Navbar mantendo o estilo do painel */
    .navbar {
      background-color: #001237 !important;
      border-bottom: 2px solid #0056b3;
      padding: 15px 20px;
    }
    /* Container principal */
    .container {
      margin-top: 40px;
    }
    /* Cards */
    .card {
      background-color: #ffffff;
      border: none;
      border-radius: 8px;
      box-shadow: 0 2px 8px rgba(0,0,0,0.2);
      margin-bottom: 30px;
    }
    .card-header {
      background-color: #0056b3;
      color: #ffffff;
      font-weight: 600;
      padding: 15px 20px;
      border-top-left-radius: 8px;
      border-top-right-radius: 8px;
    }
    .card-body {
      padding: 20px;
      color: #333333;
    }
    /* Formulário: campos de entrada com fundo branco e texto escuro */
    .form-control {
      background-color: #ffffff;
      color: #333333;
      border: 1px solid #ccc;
      border-radius: 4px;
    }
    .form-control:focus {
      border-color: #66afe9;
      box-shadow: none;
    }
    label {
      color: #333333;
      font-weight: 500;
    }
    /* Botões */
    .btn {
      border-radius: 4px;
      font-weight: 500;
    }
    .btn-primary {
      background-color: #007bff;
      border: none;
    }
    .btn-primary:hover {
      background-color: #0056b3;
    }
    /* Tabela de Usuários */
    .table {
      margin-bottom: 0;
    }
    .table thead th {
      background-color: #0056b3;
      color: #ffffff;
      padding: 10px;
      text-align: center;
    }
    .table tbody td {
      background-color: #ffffff;
      color: #333333;
      padding: 10px;
      text-align: center;
    }
    .table-striped tbody tr:nth-child(odd) {
      background-color: #f8f8f8;
    }
    .table-bordered {
      border: 1px solid #ddd;
    }
    /* Botões de ação na tabela */
    .btn-warning, .btn-danger {
      margin-right: 5px;
    }
    /* Responsividade */
    @media (max-width: 768px) {
      .container {
        margin-top: 20px;
      }
    }
  </style>
</head>
<body>

    <!-- Exibe mensagens de sucesso/erro -->
    <?php if(isset($_SESSION['sucesso'])): ?>
      <div class="alert alert-success alert-dismissible fade show" role="alert">
        <?php echo $_SESSION['sucesso']; unset($_SESSION['sucesso']); ?>
        <button type="button" class="close" data-dismiss="alert" aria-label="Fechar">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
    <?php endif; ?>
    <?php if(isset($_SESSION['error'])): ?>
      <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
        <button type="button" class="close" data-dismiss="alert" aria-label="Fechar">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
    <?php endif; ?>
    
    <!-- Formulário para criar novo usuário -->
    <div class="card mb-4">
      <div class="card-header">
        Criar Novo Usuário
      </div>
      <div class="card-body">
        <form action="usuarios.php" method="POST">
          <input type="hidden" name="action" value="create">
          <div class="form-group">
            <label for="nome">Nome</label>
            <input type="text" name="nome" id="nome" class="form-control" required>
          </div>
          <div class="form-group">
            <label for="email">E-mail</label>
            <input type="email" name="email" id="email" class="form-control" required>
          </div>
          <div class="form-group">
            <label for="senha">Senha</label>
            <input type="password" name="senha" id="senha" class="form-control" required>
          </div>
          <div class="form-group">
            <label for="nivel_acesso">Nível de Acesso</label>
            <select name="nivel_acesso" id="nivel_acesso" class="form-control">
              <option value="2">Super Admin</option>
              <option value="1">Administrador</option>
              <option value="3">Técnico de Helpdesk</option>
            </select>
          </div>
          <button type="submit" class="btn btn-primary btn-block">Criar Usuário</button>
        </form>
      </div>
    </div>
    
    <!-- Tabela de Usuários -->
    <div class="card">
      <div class="card-header">
        Lista de Usuários
      </div>
      <div class="card-body">
        <table class="table table-bordered table-hover">
          <thead class="thead-dark">
            <tr>
              <th>ID</th>
              <th>Nome</th>
              <th>E-mail</th>
              <th>Nível de Acesso</th>
              <th>Ações</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($usuarios as $usuario): ?>
            <tr>
              <td><?php echo $usuario['id']; ?></td>
              <td><?php echo htmlspecialchars($usuario['nome']); ?></td>
              <td><?php echo htmlspecialchars($usuario['email']); ?></td>
              <td>
                <?php 
                  if ($usuario['nivel_acesso'] == 2) {
                    echo "Super Admin";
                  } elseif ($usuario['nivel_acesso'] == 1) {
                    echo "Administrador";
                  } elseif ($usuario['nivel_acesso'] == 3) {
                    echo "Técnico de Helpdesk";
                  } else {
                    echo "Outro";
                  }
                ?>
              </td>
              <td>
                <!-- Botão Editar -->
                <a href="editar_usuario.php?id=<?php echo $usuario['id']; ?>" class="btn btn-warning btn-sm">
                  <i class="fas fa-edit"></i> Editar
                </a>
                <!-- Botão Excluir: não permite excluir o Super Admin (ID = 2) -->
                <?php if ($usuario['id'] != 2): ?>
                  <form action="usuarios.php" method="POST" style="display:inline;" onsubmit="return confirm('Tem certeza que deseja excluir esse usuário?');">
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="user_id" value="<?php echo $usuario['id']; ?>">
                    <button type="submit" class="btn btn-danger btn-sm">
                      <i class="fas fa-trash"></i> Excluir
                    </button>
                  </form>
                <?php endif; ?>
              </td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
  
  <!-- jQuery e Bootstrap JS -->
  <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
  <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</body>
</html>
