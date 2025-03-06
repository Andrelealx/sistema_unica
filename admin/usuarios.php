<?php 
include 'header.php'; 
session_start();
require_once '../inc/conexao.php';

// Processa ações de criação, exclusão e atualização
if ($action = ($_POST['action'] ?? '')) {
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
  <link rel="stylesheet" href="../assets/css/usuarios.css">
</head>
<body>

  <!-- Mensagens de Sucesso/Erro -->
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
    
  <!-- Formulário para Criar Novo Usuário -->
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
      <!-- .table-responsive ajuda a evitar que a tabela quebre em telas pequenas -->
      <div class="table-responsive">
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
                <!-- Botão para abrir modal de edição -->
                <button type="button" class="btn btn-warning btn-sm" data-toggle="modal" data-target="#editModal<?php echo $usuario['id']; ?>">
                  <i class="fas fa-edit"></i> Editar
                </button>
                <!-- Botão de Exclusão (impede exclusão do Super Admin) -->
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
            
            <!-- Modal de Edição do Usuário -->
            <div class="modal fade" id="editModal<?php echo $usuario['id']; ?>" tabindex="-1" role="dialog" aria-labelledby="editModalLabel<?php echo $usuario['id']; ?>" aria-hidden="true">
              <div class="modal-dialog modal-dialog-scrollable" role="document">
                <div class="modal-content">
                  <div class="modal-header">
                    <h5 class="modal-title" id="editModalLabel<?php echo $usuario['id']; ?>">Editar Usuário (ID: <?php echo $usuario['id']; ?>)</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Fechar">
                      <span aria-hidden="true">&times;</span>
                    </button>
                  </div>
                  <div class="modal-body">
                    <!-- Formulário de edição -->
                    <form action="usuarios.php" method="POST">
                      <input type="hidden" name="action" value="update">
                      <input type="hidden" name="user_id" value="<?php echo $usuario['id']; ?>">
                      
                      <div class="form-group">
                        <label for="nome-<?php echo $usuario['id']; ?>">Nome</label>
                        <input type="text" name="nome" id="nome-<?php echo $usuario['id']; ?>" class="form-control" value="<?php echo htmlspecialchars($usuario['nome']); ?>" required>
                      </div>
                      <div class="form-group">
                        <label for="email-<?php echo $usuario['id']; ?>">E-mail</label>
                        <input type="email" name="email" id="email-<?php echo $usuario['id']; ?>" class="form-control" value="<?php echo htmlspecialchars($usuario['email']); ?>" required>
                      </div>
                      <div class="form-group">
                        <label for="nivel-<?php echo $usuario['id']; ?>">Nível de Acesso</label>
                        <select name="nivel_acesso" id="nivel-<?php echo $usuario['id']; ?>" class="form-control">
                          <option value="2" <?php echo ($usuario['nivel_acesso'] == 2) ? 'selected' : ''; ?>>Super Admin</option>
                          <option value="1" <?php echo ($usuario['nivel_acesso'] == 1) ? 'selected' : ''; ?>>Administrador</option>
                          <option value="3" <?php echo ($usuario['nivel_acesso'] == 3) ? 'selected' : ''; ?>>Técnico de Helpdesk</option>
                        </select>
                      </div>
                      <button type="submit" class="btn btn-primary">Salvar Alterações</button>
                    </form>
                  </div>
                  <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Fechar</button>
                  </div>
                </div>
              </div>
            </div>
            <!-- Fim do Modal de Edição -->
            
            <?php endforeach; ?>
          </tbody>
        </table>
      </div> <!-- .table-responsive -->
    </div>
  </div>
  
  <!-- jQuery e Bootstrap JS -->
  <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
  <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.bundle.min.js"></script>
</body>
</html>
