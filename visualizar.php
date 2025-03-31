<?php
session_start();
require 'config/conexao.php';

// Verificar se o usuário está logado
if (!isset($_SESSION['logado']) || $_SESSION['logado'] !== true) {
    header("Location: index.php");
    exit;
}


if (!class_exists('ZipArchive')) {
    die("A extensão Zip não está habilitada no servidor. Contate o administrador.");
}
// Obter ID do recitativo da URL
$recitativo_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Buscar informações do recitativo no banco de dados
$recitativo = null;
$arquivos = [];

if ($recitativo_id > 0) {
    $sql = "SELECT r.*, u.nome as usuario_nome 
            FROM recitativos r
            JOIN usuarios u ON r.usuario_id = u.id
            WHERE r.id = ?";
    
    $stmt = $conexao->prepare($sql);
    $stmt->bind_param("i", $recitativo_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $recitativo = $result->fetch_assoc();
    $stmt->close();
    
    if ($recitativo) {
        $arquivos = explode(',', $recitativo['arquivo_gerado']);
        // Registrar visualização no log
        registrarLog($_SESSION['usuario_id'], 'VISUALIZACAO_RECITATIVO', "Visualizou recitativo ID: $recitativo_id");
    }
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $recitativo ? 'Recitativo ' . $recitativo_id : 'Recitativos Gerados' ?> - CCB</title>
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
        .container {
            max-width: 1200px;
            margin: 0 auto;
            background-color: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        .user-info {
            text-align: right;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 1px solid #eee;
        }
        .user-info span {
            color: #67458b;
            font-weight: bold;
        }
        .recitativo-info {
            background-color: #f9f9f9;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        .recitativo-info p {
            margin: 5px 0;
        }
        .gallery {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
            margin-top: 30px;
        }
        .gallery-item {
            border: 1px solid #ddd;
            border-radius: 5px;
            overflow: hidden;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            transition: transform 0.3s;
        }
        .gallery-item:hover {
            transform: scale(1.02);
        }
        .gallery-item img {
            width: 100%;
            height: auto;
            display: block;
        }
        .gallery-caption {
            padding: 10px;
            background-color: #f9f9f9;
            text-align: center;
            font-weight: bold;
        }
        .btn {
            display: inline-block;
            background-color: #67458b;
            color: white;
            padding: 10px 15px;
            border-radius: 5px;
            text-decoration: none;
            margin: 5px;
        }
        .btn:hover {
            background-color: #9362C6;
        }
        .btn-group {
            text-align: center;
            margin: 20px 0;
        }
        .no-records {
            text-align: center;
            padding: 40px;
            font-size: 18px;
            color: #666;
        }
    </style>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
</head>
<body>
    <div class="container">
        <!-- Cabeçalho com informações do usuário -->
        <div class="user-info">
            <span><i class="fas fa-user"></i> <?= htmlspecialchars($_SESSION['nome']) ?></span>
            <a href="index.php" class="btn"><i class="fas fa-home"></i> Início</a>
            <a href="lista_recitativos.php" class="btn"><i class="fas fa-list"></i> Todos Recitativos</a>
            <a href="index.php?logout=1" class="btn"><i class="fas fa-sign-out-alt"></i> Sair</a>
        </div>
        
        <?php if ($recitativo): ?>
            <!-- Detalhes do recitativo específico -->
            <h1><i class="fas fa-file-alt"></i> Recitativo #<?= $recitativo_id ?></h1>
            
            <div class="recitativo-info">
                <p><strong>Leitor:</strong> <?= htmlspecialchars($recitativo['nome_leitor']) ?></p>
                <p><strong>Referência Bíblica:</strong> <?= htmlspecialchars($recitativo['livro']) ?> <?= $recitativo['capitulo'] ?>:<?= $recitativo['verso_inicio'] ?>-<?= $recitativo['verso_fim'] ?></p>
                <p><strong>Data do Recitativo:</strong> <?= date('d/m/Y', strtotime($recitativo['data_recitativo'])) ?></p>
                <p><strong>Gerado por:</strong> <?= htmlspecialchars($recitativo['usuario_nome']) ?></p>
                <p><strong>Data de Geração:</strong> <?= date('d/m/Y H:i', strtotime($recitativo['data_geracao'])) ?></p>
                <p><strong>Total de Páginas:</strong> <?= count($arquivos) ?></p>
            </div>
            
            <div class="btn-group">
                <a href="novo.php" class="btn"><i class="fas fa-plus"></i> Novo Recitativo</a>
                <a href="lista_recitativos.php" class="btn"><i class="fas fa-list"></i> Ver Todos</a>
                <a href="baixar_todos.php?id=<?= $recitativo_id ?>" class="btn"><i class="fas fa-download"></i> Baixar</a>
            </div>
            
            <div class="gallery">
                <?php foreach ($arquivos as $index => $arquivo): ?>
                    <div class="gallery-item">
                        <a href="certificados_gerados/<?= $arquivo ?>" target="_blank">
                            <img src="certificados_gerados/<?= $arquivo ?>" alt="Página <?= $index + 1 ?>">
                        </a>
                        <div class="gallery-caption">Página <?= $index + 1 ?></div>
                    </div>
                <?php endforeach; ?>
            </div>
            
        <?php else: ?>
            <!-- Visualização geral quando não há ID específico -->
            <h1><i class="fas fa-images"></i> Recitativos Gerados</h1>
            
            <?php 
            // Buscar todos os recitativos do usuário (ou todos se for admin)
            $sql = "SELECT r.*, u.nome as usuario_nome 
                    FROM recitativos r
                    JOIN usuarios u ON r.usuario_id = u.id
                    ORDER BY r.data_geracao DESC";
            $result = $conexao->query($sql);
            ?>
            
            <?php if ($result->num_rows > 0): ?>
                <div class="gallery">
                    <?php while ($row = $result->fetch_assoc()): 
                        $primeiroArquivo = explode(',', $row['arquivo_gerado'])[0];
                    ?>
                        <div class="gallery-item">
                            <a href="visualizar.php?id=<?= $row['id'] ?>">
                                <img src="certificados_gerados/<?= $primeiroArquivo ?>" alt="Recitativo <?= $row['id'] ?>">
                            </a>
                            <div class="gallery-caption">
                                <p><?= htmlspecialchars($row['nome_leitor']) ?></p>
                                <small><?= $row['livro'] ?> <?= $row['capitulo'] ?>:<?= $row['verso_inicio'] ?>-<?= $row['verso_fim'] ?></small>
                                <p><small>Gerado por: <?= htmlspecialchars($row['usuario_nome']) ?></small></p>
                            </div>
                        </div>
                    <?php endwhile; ?>
                </div>
            <?php else: ?>
                <div class="no-records">
                    <p>Nenhum recitativo foi gerado ainda.</p>
                    <a href="novo.php" class="btn"><i class="fas fa-plus-circle"></i> Criar Novo Recitativo</a>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
    
    <?php $conexao->close(); ?>
</body>
</html>