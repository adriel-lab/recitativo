<?php
session_start();
require 'config/conexao.php'; // Arquivo com a conexão MySQLi

// Verifica login
$usuarioLogado = isset($_SESSION['logado']) && $_SESSION['logado'] === true;

// Processa login
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {
    $usuario = $conexao->real_escape_string($_POST['usuario']);
    $senha = $_POST['senha'];
    
    // Consulta preparada para maior segurança
    $sql = "SELECT id, username, senha, nome, nivel_acesso FROM usuarios WHERE username = ?";
    $stmt = $conexao->prepare($sql);
    $stmt->bind_param("s", $usuario);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();
        if (password_verify($senha, $user['senha'])) {
            // Definir todas as variáveis de sessão necessárias
            $_SESSION['logado'] = true;
            $_SESSION['usuario_id'] = $user['id'];
            $_SESSION['usuario'] = $user['username'];
            $_SESSION['nome'] = $user['nome'];
            
            // Definir nível de acesso (com valor padrão se não existir)
            $_SESSION['nivel_acesso'] = isset($user['nivel_acesso']) ? $user['nivel_acesso'] : 'usuario';
            
            // Registrar log de acesso
            registrarLog($user['id'], 'LOGIN', 'Login realizado com sucesso');
            
            $usuarioLogado = true;
            
            // Redirecionar para página inicial
            header("Location: index.php");
            exit;
        } else {
            $erroLogin = "Senha incorreta";
            registrarLog(null, 'TENTATIVA_LOGIN', "Senha incorreta para usuário: $usuario");
        }
    } else {
        $erroLogin = "Usuário não encontrado";
        registrarLog(null, 'TENTATIVA_LOGIN', "Usuário não encontrado: $usuario");
    }
    $stmt->close();
}

// Processa logout
if (isset($_GET['logout'])) {
    // Registrar log de logout
    if (isset($_SESSION['usuario_id'])) {
        registrarLog($_SESSION['usuario_id'], 'LOGOUT', 'Logout realizado');
    }
    
    // Destruir sessão completamente
    session_unset();
    session_destroy();
    
    // Redirecionar para login
    header("Location: index.php");
    exit;
}

// Função para verificar permissões
function temPermissao($nivelRequerido) {
    if (!isset($_SESSION['nivel_acesso'])) return false;
    
    $niveisHierarquia = [
        'admin' => 3,
        'editor' => 2,
        'usuario' => 1
    ];
    
    $nivelUsuario = $niveisHierarquia[$_SESSION['nivel_acesso']] ?? 0;
    $nivelNecessario = $niveisHierarquia[$nivelRequerido] ?? 0;
    
    return $nivelUsuario >= $nivelNecessario;
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gerador de Recitativos CCB</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f4;
            margin: 0;
            padding: 20px;
        }
        h1 {
            text-align: center;
            color: #333;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.2);
        }
        th, td {
            padding: 12px;
            text-align: left;
            border: 1px solid #ddd;
        }
        th {
            background-color: #67458b;
            color: white;
        }
        tr:nth-child(even) {
            background-color: #f2f2f2;
        }
        tr:hover {
            background-color: #ddd;
        }
        button {
            background-color: #67458b;
            color: white;
            padding: 10px 15px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
            margin-top: 20px;
            display: block;
            margin-left: auto;
            margin-right: auto;
        }
        button:hover {
            background-color: #9362C6;
        }
        a {
            color: #9362C6;
            text-decoration: none;
        }
        a:hover {
            text-decoration: underline;
        }
        .submenu {
            margin-left: 20px;
            font-size: 14px;
            color: #555;
        }
        .container {
            max-width: 1000px;
            margin: 0 auto;
            background-color: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
        }
        .header img {
            max-width: 150px;
            margin-bottom: 15px;
        }
        .features {
            display: flex;
            justify-content: space-around;
            flex-wrap: wrap;
            margin: 30px 0;
        }
        .feature-box {
            width: 30%;
            min-width: 250px;
            text-align: center;
            padding: 20px;
            margin-bottom: 20px;
            background-color: #f9f9f9;
            border-radius: 5px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        .feature-box i {
            font-size: 40px;
            color: #67458b;
            margin-bottom: 15px;
        }
        .login-container {
            max-width: 400px;
            margin: 50px auto;
            padding: 30px;
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        .login-container h2 {
            text-align: center;
            color: #67458b;
            margin-bottom: 20px;
        }
        .form-group {
            margin-bottom: 20px;
        }
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: bold;
            color: #555;
        }
        .form-group input {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            box-sizing: border-box;
        }
        .error-message {
            color: #e74c3c;
            text-align: center;
            margin-bottom: 20px;
        }
        .user-info {
            text-align: right;
            margin-bottom: 20px;
            color: #67458b;
            font-weight: bold;
        }
    </style>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
</head>
<body>
    <?php if (!$usuarioLogado): ?>
        <div class="login-container">
            <h2><i class="fas fa-lock"></i> Acesso Restrito</h2>
            
            <?php if (isset($erroLogin)): ?>
                <div class="error-message"><?= $erroLogin ?></div>
            <?php endif; ?>
            
            <form method="post">
                <div class="form-group">
                    <label for="usuario">Usuário:</label>
                    <input type="text" id="usuario" name="usuario" required>
                </div>
                <div class="form-group">
                    <label for="senha">Senha:</label>
                    <input type="password" id="senha" name="senha" required>
                </div>
                <button type="submit" name="login">
                    <i class="fas fa-sign-in-alt"></i> Entrar
                </button>
            </form>
        </div>
    <?php else: ?>
        <div class="container">
            <div class="user-info">
                <i class="fas fa-user"></i> <?= htmlspecialchars($_SESSION['nome']) ?> 
                | <a href="index.php?logout=1"><i class="fas fa-sign-out-alt"></i> Sair</a>
            </div>
            
            <div class="header">
                <h1>Gerador de Recitativos CCB</h1>
                <p>Sistema especializado para criação de certificados (recitativos) da Congregação Cristã no Brasil</p>
            </div>
            
            <div class="features">
                <div class="feature-box">
                    <i class="fas fa-certificate"></i>
                    <h3>Recitativos Profissionais</h3>
                    <p>Gere recitativos com padrão profissional e layout adequado para a CCB</p>
                </div>
                <div class="feature-box">
                    <i class="fas fa-bolt"></i>
                    <h3>Rápido e Fácil</h3>
                    <p>Preencha os dados uma vez e gere múltiplos recitativos em segundos</p>
                </div>
                <div class="feature-box">
                    <i class="fas fa-database"></i>
                    <h3>Histórico Armazenado</h3>
                    <p>Todos os recitativos gerados ficam salvos para consultas futuras</p>
                </div>
            </div>
            
            <table>
                <thead>
                    <tr>
                        <th>Opção</th>
                        <th>Descrição</th>
                        <th>Acesso</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td><i class="fas fa-plus-circle"></i> Novo Recitativo (Frontal)</td>
                        <td>Crie um novo recitativo personalizado a parte frontal</td>
                        <td><a href="novo.php">Acessar <i class="fas fa-arrow-right"></i></a></td>
                    </tr>
                    <tr>
                        <td><i class="fas fa-plus-circle"></i> Novo Recitativo (Traseiro)</td>
                        <td>Crie um novo recitativo personalizado a parte traseira</td>
                        <td><a href="novo_traseiro.php">Acessar <i class="fas fa-arrow-right"></i></a></td>
                    </tr>
                    <tr>
                        <td><i class="fas fa-list"></i> Lista de Recitativos</td>
                        <td>Visualize todos os recitativos já gerados</td>
                        <td><a href="lista_recitativos.php">Acessar <i class="fas fa-arrow-right"></i></a></td>
                    </tr>
                    <tr>
                        <td><i class="fas fa-search"></i> Consultar Recitativo</td>
                        <td>Busque um recitativo específico por código ou nome</td>
                        <td><a href="consulta.php">Acessar <i class="fas fa-arrow-right"></i></a></td>
                    </tr>
                    <tr>
                        <td><i class="fas fa-cog"></i> Configurações</td>
                        <td>Personalize as opções do sistema</td>
                        <td><a href="config.php">Acessar <i class="fas fa-arrow-right"></i></a></td>
                    </tr>
                </tbody>
            </table>
            
            <button onclick="window.location.href='novo.php'">
                <i class="fas fa-plus"></i> Criar Novo Recitativo
            </button>
            
            <div style="text-align: center; margin-top: 30px; color: #666; font-size: 14px;">
                <p>Sistema desenvolvido para a Congregação Cristã no Brasil | © <?= date('Y') ?> Todos os direitos reservados</p>
                <p class="submenu">
                    <a href="sobre.php">Sobre o sistema</a> | 
                    <a href="ajuda.php">Ajuda</a> | 
                    <a href="contato.php">Contato</a>
                </p>
            </div>
        </div>
    <?php endif; ?>
</body>
</html>
<?php
// Fecha conexão se existir
if (isset($conexao)) {
    $conexao->close();
}
?>