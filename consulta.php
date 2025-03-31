<?php
session_start();
require 'config/conexao.php';

// Verificar se o usuário está logado
if (!isset($_SESSION['logado'])) {
    header("Location: index.php");
    exit;
}

// Configurações de paginação
$por_pagina = 10;
$pagina = isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
$offset = ($pagina - 1) * $por_pagina;

// Inicializar filtros
$filtros = [
    'termo_busca' => '',
    'livro' => '',
    'data_inicio' => '',
    'data_fim' => '',
    'usuario_id' => ''
];

// Processar busca quando o formulário é enviado
$resultados = [];
$total_resultados = 0;

// Obter lista de livros bíblicos para o select
$livros_biblia = [
    'Gênesis', 'Êxodo', 'Levítico', 'Números', 'Deuteronômio', 'Josué', 'Juízes', 'Rute', 
    '1 Samuel', '2 Samuel', '1 Reis', '2 Reis', '1 Crônicas', '2 Crônicas', 'Esdras', 'Neemias', 
    'Ester', 'Jó', 'Salmos', 'Provérbios', 'Eclesiastes', 'Cânticos', 'Isaías', 'Jeremias', 
    'Lamentações', 'Ezequiel', 'Daniel', 'Oséias', 'Joel', 'Amós', 'Obadias', 'Jonas', 
    'Miqueias', 'Naum', 'Habacuque', 'Sofonias', 'Ageu', 'Zacarias', 'Malaquias', 
    'Mateus', 'Marcos', 'Lucas', 'João', 'Atos', 'Romanos', '1 Coríntios', '2 Coríntios', 
    'Gálatas', 'Efésios', 'Filipenses', 'Colossenses', '1 Tessalonicenses', '2 Tessalonicenses', 
    '1 Timóteo', '2 Timóteo', 'Tito', 'Filemom', 'Hebreus', 'Tiago', '1 Pedro', '2 Pedro', 
    '1 João', '2 João', '3 João', 'Judas', 'Apocalipse'
];

// Obter lista de usuários para o select
$usuarios = [];
$sql_usuarios = "SELECT id, nome FROM usuarios ORDER BY nome";
$result_usuarios = $conexao->query($sql_usuarios);
while ($row = $result_usuarios->fetch_assoc()) {
    $usuarios[$row['id']] = $row['nome'];
}

// Processar filtros
if ($_SERVER['REQUEST_METHOD'] === 'GET' && !empty($_GET)) {
    $filtros = array_merge($filtros, $_GET);
    
    // Construir a consulta SQL com filtros
    $where = [];
    $params = [];
    $types = '';
    
    if (!empty($filtros['termo_busca'])) {
        // Verificar se é numérico (busca por ID)
        if (is_numeric($filtros['termo_busca'])) {
            $where[] = "r.id = ?";
            $params[] = $filtros['termo_busca'];
            $types .= 'i';
        } else {
            $where[] = "r.nome_leitor LIKE ?";
            $params[] = "%{$filtros['termo_busca']}%";
            $types .= 's';
        }
    }
    
    if (!empty($filtros['livro'])) {
        $where[] = "r.livro = ?";
        $params[] = $filtros['livro'];
        $types .= 's';
    }
    
    if (!empty($filtros['data_inicio'])) {
        $where[] = "r.data_recitativo >= ?";
        $params[] = $filtros['data_inicio'];
        $types .= 's';
    }
    
    if (!empty($filtros['data_fim'])) {
        $where[] = "r.data_recitativo <= ?";
        $params[] = $filtros['data_fim'];
        $types .= 's';
    }
    
    if (!empty($filtros['usuario_id'])) {
        $where[] = "r.usuario_id = ?";
        $params[] = $filtros['usuario_id'];
        $types .= 'i';
    }
    
    // Consulta para resultados
    $sql = "SELECT SQL_CALC_FOUND_ROWS r.*, u.nome as usuario_nome 
            FROM recitativos r
            JOIN usuarios u ON r.usuario_id = u.id";
    
    if (!empty($where)) {
        $sql .= " WHERE " . implode(" AND ", $where);
    }
    
    $sql .= " ORDER BY r.data_geracao DESC LIMIT ? OFFSET ?";
    $params[] = $por_pagina;
    $params[] = $offset;
    $types .= 'ii';
    
    $stmt = $conexao->prepare($sql);
    if ($stmt) {
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $result = $stmt->get_result();
        $resultados = $result->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        
        // Obter total de resultados para paginação
        $total_resultados = $conexao->query("SELECT FOUND_ROWS()")->fetch_row()[0];
        $total_paginas = ceil($total_resultados / $por_pagina);
        
        // Registrar a busca no log
        registrarLog($_SESSION['usuario_id'], 'BUSCA_RECITATIVO', "Buscou com filtros: " . json_encode($filtros));
    }
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Consultar Recitativos - CCB</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/jqueryui/1.12.1/jquery-ui.min.css">
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f4;
            margin: 0;
            padding: 20px;
        }
        .container {
            max-width: 1400px;
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
        .search-box {
            background-color: #f9f9f9;
            padding: 20px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        .search-box .form-row {
            display: flex;
            flex-wrap: wrap;
            margin-bottom: 10px;
        }
        .search-box .form-group {
            flex: 1;
            min-width: 200px;
            margin-right: 15px;
            margin-bottom: 10px;
        }
        .search-box label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }
        .search-box input[type="text"],
        .search-box select {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        .search-box button {
            padding: 10px 15px;
            background-color: #67458b;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            margin-top: 10px;
        }
        .search-box button:hover {
            background-color: #9362C6;
        }
        .results-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        .results-table th, .results-table td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }
        .results-table th {
            background-color: #67458b;
            color: white;
        }
        .results-table tr:nth-child(even) {
            background-color: #f2f2f2;
        }
        .results-table tr:hover {
            background-color: #e9e9e9;
        }
        .btn {
            display: inline-block;
            background-color: #67458b;
            color: white;
            padding: 5px 10px;
            border-radius: 3px;
            text-decoration: none;
            font-size: 14px;
        }
        .btn:hover {
            background-color: #9362C6;
        }
        .no-results {
            text-align: center;
            padding: 20px;
            color: #666;
        }
        .pagination {
            display: flex;
            justify-content: center;
            margin-top: 20px;
        }
        .pagination a {
            color: #67458b;
            padding: 8px 16px;
            text-decoration: none;
            border: 1px solid #ddd;
            margin: 0 4px;
        }
        .pagination a.active {
            background-color: #67458b;
            color: white;
            border: 1px solid #67458b;
        }
        .pagination a:hover:not(.active) {
            background-color: #ddd;
        }
        .clear-filters {
            display: inline-block;
            margin-left: 10px;
            color: #67458b;
            text-decoration: none;
        }
        .ui-autocomplete {
            max-height: 200px;
            overflow-y: auto;
            overflow-x: hidden;
        }
    </style>
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
        
        <h1><i class="fas fa-search"></i> Consultar Recitativos</h1>
        
        <!-- Formulário de busca avançada -->
        <div class="search-box">
            <form method="GET" action="consulta.php">
                <div class="form-row">
                    <div class="form-group">
                        <label for="termo_busca">Código ou Nome do Leitor</label>
                        <input type="text" id="termo_busca" name="termo_busca" 
                               value="<?= htmlspecialchars($filtros['termo_busca']) ?>" 
                               placeholder="Digite o código ou nome...">
                    </div>
                    
                    <div class="form-group">
                        <label for="livro">Livro Bíblico</label>
                        <select id="livro" name="livro">
                            <option value="">Todos os livros</option>
                            <?php foreach ($livros_biblia as $livro): ?>
                                <option value="<?= $livro ?>" <?= $filtros['livro'] === $livro ? 'selected' : '' ?>>
                                    <?= $livro ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="data_inicio">Data Recitativo (Início)</label>
                        <input type="date" id="data_inicio" name="data_inicio" 
                               value="<?= htmlspecialchars($filtros['data_inicio']) ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="data_fim">Data Recitativo (Fim)</label>
                        <input type="date" id="data_fim" name="data_fim" 
                               value="<?= htmlspecialchars($filtros['data_fim']) ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="usuario_id">Gerado por</label>
                        <select id="usuario_id" name="usuario_id">
                            <option value="">Todos os usuários</option>
                            <?php foreach ($usuarios as $id => $nome): ?>
                                <option value="<?= $id ?>" <?= $filtros['usuario_id'] == $id ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($nome) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                
                <button type="submit"><i class="fas fa-search"></i> Buscar</button>
                <a href="consulta.php" class="clear-filters"><i class="fas fa-times"></i> Limpar filtros</a>
            </form>
        </div>
        
        <!-- Resultados da busca -->
        <?php if ($_SERVER['REQUEST_METHOD'] === 'GET' && !empty($_GET)): ?>
            <h2>Resultados da Busca</h2>
            
            <?php if (!empty($resultados)): ?>
                <div class="results-info">
                    <p>Exibindo <?= count($resultados) ?> de <?= $total_resultados ?> resultados encontrados</p>
                </div>
                
                <table class="results-table">
                    <thead>
                        <tr>
                            <th>Código</th>
                            <th>Leitor</th>
                            <th>Referência</th>
                            <th>Data Recitativo</th>
                            <th>Gerado por</th>
                            <th>Data Geração</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($resultados as $recitativo): ?>
                            <tr>
                                <td><?= $recitativo['id'] ?></td>
                                <td><?= htmlspecialchars($recitativo['nome_leitor']) ?></td>
                                <td><?= htmlspecialchars($recitativo['livro']) ?> <?= $recitativo['capitulo'] ?>:<?= $recitativo['verso_inicio'] ?>-<?= $recitativo['verso_fim'] ?></td>
                                <td><?= date('d/m/Y', strtotime($recitativo['data_recitativo'])) ?></td>
                                <td><?= htmlspecialchars($recitativo['usuario_nome']) ?></td>
                                <td><?= date('d/m/Y H:i', strtotime($recitativo['data_geracao'])) ?></td>
                                <td>
                                    <a href="visualizar.php?id=<?= $recitativo['id'] ?>" class="btn" title="Visualizar">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                
                <!-- Paginação -->
                <?php if ($total_paginas > 1): ?>
                    <div class="pagination">
                        <?php if ($pagina > 1): ?>
                            <a href="?<?= http_build_query(array_merge($filtros, ['pagina' => 1])) ?>">«</a>
                            <a href="?<?= http_build_query(array_merge($filtros, ['pagina' => $pagina - 1])) ?>">‹</a>
                        <?php endif; ?>
                        
                        <?php 
                        $inicio = max(1, $pagina - 2);
                        $fim = min($total_paginas, $pagina + 2);
                        
                        if ($inicio > 1) echo '<a href="#">...</a>';
                        
                        for ($i = $inicio; $i <= $fim; $i++): ?>
                            <a href="?<?= http_build_query(array_merge($filtros, ['pagina' => $i])) ?>" 
                               <?= $i == $pagina ? 'class="active"' : '' ?>>
                                <?= $i ?>
                            </a>
                        <?php endfor;
                        
                        if ($fim < $total_paginas) echo '<a href="#">...</a>';
                        ?>
                        
                        <?php if ($pagina < $total_paginas): ?>
                            <a href="?<?= http_build_query(array_merge($filtros, ['pagina' => $pagina + 1])) ?>">›</a>
                            <a href="?<?= http_build_query(array_merge($filtros, ['pagina' => $total_paginas])) ?>">»</a>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            <?php else: ?>
                <div class="no-results">
                    <p>Nenhum recitativo encontrado com os filtros selecionados.</p>
                    <p>Tente novamente com outros critérios de busca.</p>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://code.jquery.com/ui/1.12.1/jquery-ui.min.js"></script>
    <script>
        $(function() {
            // Auto-complete para nomes de leitores
            $("#termo_busca").autocomplete({
                source: function(request, response) {
                    $.ajax({
                        url: "autocomplete_leitor.php",
                        dataType: "json",
                        data: {
                            term: request.term
                        },
                        success: function(data) {
                            response(data);
                        }
                    });
                },
                minLength: 2,
                select: function(event, ui) {
                    $("#termo_busca").val(ui.item.value);
                    return false;
                }
            }).autocomplete("instance")._renderItem = function(ul, item) {
                return $("<li>")
                    .append("<div>" + item.label + "</div>")
                    .appendTo(ul);
            };
        });
    </script>
</body>
</html>
<?php $conexao->close(); ?>