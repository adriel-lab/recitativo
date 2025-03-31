<?php
// conexao.php
$host = 'localhost';
$usuario = 'root';
$senha = '';
$banco = 'gerador_recitativos';

$conexao = new mysqli($host, $usuario, $senha, $banco);

if ($conexao->connect_error) {
    die("Erro na conexão: " . $conexao->connect_error);
}

// Criar tabelas necessárias
$sql = [
    "CREATE TABLE IF NOT EXISTS usuarios (
        id INT AUTO_INCREMENT PRIMARY KEY,
        username VARCHAR(50) NOT NULL UNIQUE,
        senha VARCHAR(255) NOT NULL,
        nome VARCHAR(100) NOT NULL,
        email VARCHAR(100) NOT NULL UNIQUE,
        nivel_acesso ENUM('admin', 'editor', 'usuario') DEFAULT 'usuario',
        ativo BOOLEAN DEFAULT TRUE,
        token_recuperacao VARCHAR(255) DEFAULT NULL,
        token_validade DATETIME DEFAULT NULL,
        criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        atualizado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )",
    
    "CREATE TABLE IF NOT EXISTS tentativas_login (
        id INT AUTO_INCREMENT PRIMARY KEY,
        ip VARCHAR(45) NOT NULL,
        username VARCHAR(50) NOT NULL,
        data_hora DATETIME DEFAULT CURRENT_TIMESTAMP,
        sucesso BOOLEAN NOT NULL
    )",
    
    "CREATE TABLE IF NOT EXISTS logs_acesso (
        id INT AUTO_INCREMENT PRIMARY KEY,
        usuario_id INT NOT NULL,
        acao VARCHAR(50) NOT NULL,
        descricao TEXT,
        ip VARCHAR(45) NOT NULL,
        user_agent TEXT,
        data_hora TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (usuario_id) REFERENCES usuarios(id)
    )"
];

foreach ($sql as $query) {
    if (!$conexao->query($query)) {
        die("Erro ao criar tabelas: " . $conexao->error);
    }
}

// Inserir usuário admin padrão se não existir
$sql = "INSERT IGNORE INTO usuarios (username, senha, nome, email, nivel_acesso) 
        VALUES ('admin', ?, 'Administrador', 'admin@ccb.com', 'admin')";
        
$stmt = $conexao->prepare($sql);
$senha_hash = password_hash('admin123', PASSWORD_BCRYPT);
$stmt->bind_param("s", $senha_hash);
$stmt->execute();
$stmt->close();

// Funções úteis
function registrarLog($usuario_id, $acao, $descricao = null) {
    global $conexao;
    
    $ip = $_SERVER['REMOTE_ADDR'];
    $user_agent = $_SERVER['HTTP_USER_AGENT'];
    
    $sql = "INSERT INTO logs_acesso (usuario_id, acao, descricao, ip, user_agent) 
            VALUES (?, ?, ?, ?, ?)";
            
    $stmt = $conexao->prepare($sql);
    $stmt->bind_param("issss", $usuario_id, $acao, $descricao, $ip, $user_agent);
    $stmt->execute();
    $stmt->close();
}

function verificarTentativasLogin($username) {
    global $conexao;
    
    $ip = $_SERVER['REMOTE_ADDR'];
    $limite_minutos = 30;
    $limite_tentativas = 5;
    
    $sql = "SELECT COUNT(*) as total FROM tentativas_login 
            WHERE ip = ? AND username = ? AND 
            data_hora > DATE_SUB(NOW(), INTERVAL ? MINUTE) AND sucesso = 0";
    
    $stmt = $conexao->prepare($sql);
    $stmt->bind_param("ssi", $ip, $username, $limite_minutos);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $stmt->close();
    
    return $row['total'] >= $limite_tentativas;
}
?>