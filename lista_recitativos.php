<?php
session_start();
require 'config/conexao.php';

// Verificar e inicializar variáveis de sessão necessárias
$requiredSessionVars = ['logado', 'usuario_id', 'nome'];
foreach ($requiredSessionVars as $var) {
    if (!isset($_SESSION[$var])) {
        header("Location: index.php");
        exit;
    }
}

// Definir nível de acesso padrão se não existir
if (!isset($_SESSION['nivel_acesso'])) {
    $_SESSION['nivel_acesso'] = 'usuario';
}

// Configuração de paginação
$por_pagina = 10;
$pagina = isset($_GET['pagina']) ? max(1, (int)$_GET['pagina']) : 1;
$offset = ($pagina - 1) * $por_pagina;

// Construir consulta SQL com busca
$busca = isset($_GET['busca']) ? trim($_GET['busca']) : '';
$where = '';
$params = [];
$types = '';

if (!empty($busca)) {
    $where = "WHERE (r.nome_leitor LIKE ? OR r.livro LIKE ?)";
    $params = ["%$busca%", "%$busca%"];
    $types = 'ss';
}

// Consulta principal com paginação
$sql = "SELECT SQL_CALC_FOUND_ROWS r.*, u.nome as usuario_nome 
        FROM recitativos r
        JOIN usuarios u ON r.usuario_id = u.id
        $where
        ORDER BY r.data_geracao DESC
        LIMIT ?, ?";

$params[] = $offset;
$params[] = $por_pagina;
$types .= 'ii';

// Preparar e executar consulta
$stmt = $conexao->prepare($sql);

if ($where !== '') {
    $stmt->bind_param($types, ...$params);
} else {
    $stmt->bind_param($types, $offset, $por_pagina);
}

$stmt->execute();
$result = $stmt->get_result();
$recitativos = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Obter total de registros
$total_resultados = $conexao->query("SELECT FOUND_ROWS()")->fetch_row()[0];
$total_paginas = max(1, ceil($total_resultados / $por_pagina));

// Registrar acesso
registrarLog($_SESSION['usuario_id'], 'ACESSO_LISTA', 'Acessou lista de recitativos');
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lista de Recitativos - CCB</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f4;
            margin: 0;
            padding: 20px;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
            background-color: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        .user-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 1px solid #eee;
        }
        .user-info {
            color: #67458b;
            font-weight: bold;
        }
        .header-actions {
            display: flex;
            gap: 10px;
        }
        h1 {
            text-align: center;
            color: #333;
            margin-bottom: 30px;
        }
        .search-container {
            display: flex;
            justify-content: space-between;
            margin-bottom: 20px;
        }
        .search-box {
            display: flex;
            gap: 10px;
        }
        .search-box input {
            padding: 8px 12px;
            width: 300px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        .table-responsive {
            overflow-x: auto;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        th, td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        th {
            background-color: #67458b;
            color: white;
            position: sticky;
            top: 0;
        }
        tr:nth-child(even) {
            background-color: #f9f9f9;
        }
        tr:hover {
            background-color: #f1f1f1;
        }
        .badge {
            display: inline-block;
            padding: 3px 8px;
            border-radius: 10px;
            font-size: 12px;
            font-weight: bold;
        }
        .badge-primary {
            background-color: #e9e9ff;
            color: #4a4a8a;
        }
        .action-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 30px;
            height: 30px;
            border-radius: 4px;
            color: white;
            background-color: #67458b;
            margin: 0 2px;
        }
        .action-btn:hover {
            background-color: #9362C6;
            color: white;
        }
        .btn {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            padding: 8px 15px;
            border-radius: 4px;
            background-color: #67458b;
            color: white;
            text-decoration: none;
        }
        .btn:hover {
            background-color: #9362C6;
        }
        .btn i {
            font-size: 14px;
        }
        .pagination {
            display: flex;
            justify-content: center;
            margin-top: 20px;
            gap: 5px;
        }
        .pagination a, .pagination span {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 35px;
            height: 35px;
            border-radius: 4px;
            border: 1px solid #ddd;
        }
        .pagination a:hover {
            background-color: #67458b;
            color: white;
            border-color: #67458b;
        }
        .current-page {
            background-color: #67458b;
            color: white;
            border-color: #67458b;
        }
        .empty-state {
            text-align: center;
            padding: 40px;
            color: #666;
        }
        .empty-state i {
            font-size: 50px;
            color: #ddd;
            margin-bottom: 15px;
        }
    </style>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
</head>
<body>
    <div class="container">
        <!-- Cabeçalho -->
        <div class="user-header">
            <div class="user-info">
                <i class="fas fa-user"></i> <?= htmlspecialchars($_SESSION['nome']) ?>
                <small>(<?= htmlspecialchars($_SESSION['nivel_acesso']) ?>)</small>
            </div>
            <div class="header-actions">
                <a href="novo.php" class="btn"><i class="fas fa-plus"></i> Novo</a>
                <a href="index.php" class="btn"><i class="fas fa-home"></i> Início</a>
                <a href="logout.php" class="btn"><i class="fas fa-sign-out-alt"></i> Sair</a>
            </div>
        </div>

        <h1><i class="fas fa-list"></i> Lista de Recitativos</h1>

        <!-- Barra de pesquisa -->
        <div class="search-container">
            <div class="search-box">
                <form method="get" action="lista_recitativos.php" class="search-form">
                    <input type="text" name="busca" placeholder="Pesquisar leitor ou livro..." 
                           value="<?= htmlspecialchars($busca) ?>">
                    <button type="submit" class="btn"><i class="fas fa-search"></i></button>
                </form>
            </div>
            <div class="total-info">
                <span class="badge badge-primary">Total: <?= $total_resultados ?></span>
            </div>
        </div>

        <!-- Tabela de recitativos -->
        <div class="table-responsive">
            <?php if (count($recitativos) > 0): ?>
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Leitor</th>
                            <th>Referência</th>
                            <th>Data</th>
                            <th>Páginas</th>
                            <th>Criado por</th>
                            <th>Data Criação</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recitativos as $recitativo): 
                            $arquivos = explode(',', $recitativo['arquivo_gerado']);
                            $dataFormatada = date('d/m/Y', strtotime($recitativo['data_recitativo']));
                            $dataCriacao = date('d/m/Y H:i', strtotime($recitativo['data_geracao']));
                            $podeEditar = ($_SESSION['nivel_acesso'] === 'admin' || 
                                         ($_SESSION['nivel_acesso'] === 'editor' && $_SESSION['usuario_id'] == $recitativo['usuario_id']));
                        ?>
                            <tr>
                                <td><?= $recitativo['id'] ?></td>
                                <td><?= htmlspecialchars($recitativo['nome_leitor']) ?></td>
                                <td><?= htmlspecialchars($recitativo['livro']) ?> <?= $recitativo['capitulo'] ?>:<?= $recitativo['verso_inicio'] ?>-<?= $recitativo['verso_fim'] ?></td>
                                <td><?= $dataFormatada ?></td>
                                <td><span class="badge badge-primary"><?= count($arquivos) ?></span></td>
                                <td><?= htmlspecialchars($recitativo['usuario_nome']) ?></td>
                                <td><?= $dataCriacao ?></td>
                                <td>
                                    <a href="visualizar.php?id=<?= $recitativo['id'] ?>" class="action-btn" title="Visualizar">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <a href="baixar_todos.php?id=<?= $recitativo['id'] ?>" class="btn"><i class="fas fa-download"></i> Baixar Todas</a>
                                    <?php if ($podeEditar): ?>
                                        <a href="editar_recitativo.php?id=<?= $recitativo['id'] ?>" class="action-btn" title="Editar">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div class="empty-state">
                    <i class="fas fa-book-open"></i>
                    <h3>Nenhum recitativo encontrado</h3>
                    <p>Não foram encontrados recitativos com os critérios atuais.</p>
                    <a href="novo.php" class="btn"><i class="fas fa-plus"></i> Criar Novo Recitativo</a>
                </div>
            <?php endif; ?>
        </div>

        <!-- Paginação -->
        <?php if ($total_paginas > 1): ?>
            <div class="pagination">
                <?php if ($pagina > 1): ?>
                    <a href="?pagina=1<?= $busca ? '&busca=' . urlencode($busca) : '' ?>"><i class="fas fa-angle-double-left"></i></a>
                    <a href="?pagina=<?= $pagina - 1 ?><?= $busca ? '&busca=' . urlencode($busca) : '' ?>"><i class="fas fa-angle-left"></i></a>
                <?php endif; ?>

                <?php 
                $inicio = max(1, $pagina - 2);
                $fim = min($total_paginas, $pagina + 2);
                
                for ($i = $inicio; $i <= $fim; $i++): ?>
                    <?php if ($i == $pagina): ?>
                        <span class="current-page"><?= $i ?></span>
                    <?php else: ?>
                        <a href="?pagina=<?= $i ?><?= $busca ? '&busca=' . urlencode($busca) : '' ?>"><?= $i ?></a>
                    <?php endif; ?>
                <?php endfor; ?>

                <?php if ($pagina < $total_paginas): ?>
                    <a href="?pagina=<?= $pagina + 1 ?><?= $busca ? '&busca=' . urlencode($busca) : '' ?>"><i class="fas fa-angle-right"></i></a>
                    <a href="?pagina=<?= $total_paginas ?><?= $busca ? '&busca=' . urlencode($busca) : '' ?>"><i class="fas fa-angle-double-right"></i></a>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
<?php
$conexao->close();
?>