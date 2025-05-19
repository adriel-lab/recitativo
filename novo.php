<?php
session_start();
require 'config/conexao.php';

// Verifica se o usuário está logado
if (!isset($_SESSION['logado']) || $_SESSION['logado'] !== true) {
    header("Location: index.php");
    exit;
}

// Seleciona o banco de dados bíblico
$conexao->select_db('biblia');

// Consulta os livros da Bíblia
$livros = $conexao->query("SELECT * FROM livros ORDER BY liv_id")->fetch_all(MYSQLI_ASSOC);

// Define a versão padrão como 2 (Almeida Revisada e Corrigida)
$versaoPadrao = 2;
?>
<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gerador de Recitativos CCB - Novo</title>
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

        th,
        td {
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

        /* Estilos específicos para o formulário */
        .container {
            max-width: 800px;
            margin: 0 auto;
            background-color: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }

        .form-group {
            margin-bottom: 15px;
        }

        label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
            color: #555;
        }

        input,
        select {
            width: 100%;
            padding: 10px;
            box-sizing: border-box;
            border: 1px solid #ddd;
            border-radius: 4px;
        }

        input:focus,
        select:focus {
            border-color: #67458b;
            outline: none;
        }

        /* Estilo para o cabeçalho do usuário */
        .user-header {
            text-align: right;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 1px solid #eee;
        }

        .user-header span {
            color: #67458b;
            font-weight: bold;
        }

        .user-header a {
            margin-left: 15px;
            font-size: 14px;
        }

        /* Estilo para o checkbox */
        .checkbox-container {
            display: flex;
            align-items: center;
            margin: 15px 0;
        }

        .checkbox-container input[type="checkbox"] {
            width: auto;
            margin-right: 10px;
        }

        .checkbox-container label {
            margin-bottom: 0;
            font-weight: normal;
        }

        /* Estilos para o seletor de livros */
        .bible-selector {
            margin-bottom: 20px;
            padding: 15px;
            background-color: #f8f9fa;
            border-radius: 4px;
        }

        .selector-row {
            display: flex;
            gap: 10px;
            margin-bottom: 10px;
        }

        .selector-group {
            flex: 1;
        }

        /* Novos estilos adicionados */
        .notification {
            position: fixed;
            top: 20px;
            right: 20px;
            padding: 15px 20px;
            background-color: #4CAF50;
            color: white;
            border-radius: 4px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            display: none;
            z-index: 1000;
        }

        .notification.error {
            background-color: #f44336;
        }

        .loading {
            display: inline-block;
            width: 20px;
            height: 20px;
            border: 3px solid rgba(255,255,255,.3);
            border-radius: 50%;
            border-top-color: #fff;
            animation: spin 1s ease-in-out infinite;
            margin-left: 10px;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }

        .btn-clear {
            background-color: #f44336;
            color: white;
            border: none;
            padding: 8px 15px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 14px;
            margin-left: 10px;
        }

        .btn-clear:hover {
            background-color: #d32f2f;
        }

        .btn-select {
            background-color: #67458b;
            color: white;
            border: none;
            padding: 10px 15px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 14px;
        }

        .btn-select:hover {
            background-color: #9362C6;
        }

        .btn-select:disabled {
            background-color: #cccccc;
            cursor: not-allowed;
        }

        .date-info {
            font-size: 12px;
            color: #666;
            margin-top: 5px;
        }
    </style>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
</head>

<body>
    <!-- Notificação flutuante -->
    <div class="notification" id="notification"></div>

    <div class="container">
        <!-- Cabeçalho com informações do usuário -->
        <div class="user-header">
            <span><i class="fas fa-user"></i> <?php echo htmlspecialchars($_SESSION['nome']); ?></span>
            <a href="index.php?logout=1"><i class="fas fa-sign-out-alt"></i> Sair</a>
        </div>

        <h1><i class="fas fa-plus-circle"></i> Novo Recitativo CCB</h1>

        <!-- Seletor Bíblico -->
        <div class="bible-selector">
            <h3><i class="fas fa-bible"></i> Selecione a Passagem Bíblica</h3>
            <div class="selector-row">
                <div class="selector-group">
                    <label for="select-livro">Livro:</label>
                    <select id="select-livro" class="form-control">
                        <option value="">Selecione um livro</option>
                        <?php foreach ($livros as $livro): ?>
                            <option value="<?php echo $livro['liv_id']; ?>"><?php echo htmlspecialchars($livro['liv_nome']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="selector-group">
                    <label for="select-capitulo">Capítulo:</label>
                    <select id="select-capitulo" class="form-control" disabled>
                        <option value="">Selecione um capítulo</option>
                    </select>
                </div>
            </div>
            <div class="selector-row">
                <div class="selector-group">
                    <label for="select-verso-inicio">Verso Inicial:</label>
                    <select id="select-verso-inicio" class="form-control" disabled>
                        <option value="">Selecione um verso</option>
                    </select>
                </div>
                <div class="selector-group">
                    <label for="select-verso-fim">Verso Final:</label>
                    <select id="select-verso-fim" class="form-control" disabled>
                        <option value="">Selecione um verso</option>
                    </select>
                </div>
            </div>
            <div style="margin-top: 15px;">
                <button id="btn-preencher" class="btn-select" disabled>
                    <i class="fas fa-check"></i> Usar esta passagem
                </button>
                <button id="btn-limpar" class="btn-clear" type="button">
                    <i class="fas fa-trash"></i> Limpar Seleção
                </button>
            </div>
        </div>

        <table>
            <thead>
                <tr>
                    <th colspan="2">Preencha os dados para gerar o recitativo</th>
                </tr>
            </thead>
            <tbody>
                <form action="gerar_recitativo.php" method="post" id="recitativo-form">
                    <tr>
                        <td colspan="2">
                            <div class="form-group">
                                <label for="nome">Nome do Leitor:</label>
                                <input type="text" id="nome" name="nome" required placeholder="Digite o nome completo do leitor">
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <td>
                            <div class="form-group">
                                <label for="dia">Dia:</label>
                                <input type="number" id="dia" name="dia" min="1" max="31" required>
                                <div class="date-info">(1-31)</div>
                            </div>
                        </td>
                        <td>
                            <div class="form-group">
                                <label for="mes">Mês:</label>
                                <select id="mes" name="mes" required>
                                    <option value="">Selecione o mês</option>
                                    <option value="Janeiro">Janeiro</option>
                                    <option value="Fevereiro">Fevereiro</option>
                                    <option value="Março">Março</option>
                                    <option value="Abril">Abril</option>
                                    <option value="Maio">Maio</option>
                                    <option value="Junho">Junho</option>
                                    <option value="Julho">Julho</option>
                                    <option value="Agosto">Agosto</option>
                                    <option value="Setembro">Setembro</option>
                                    <option value="Outubro">Outubro</option>
                                    <option value="Novembro">Novembro</option>
                                    <option value="Dezembro">Dezembro</option>
                                </select>
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <td>
                            <div class="form-group">
                                <label for="ano">Ano (últimos 2 dígitos):</label>
                                <input type="number" id="ano" name="ano" min="0" max="99" required>
                                <div class="date-info">Ex: 23 para 2023</div>
                            </div>
                        </td>
                        <td>
                            <div class="form-group">
                                <label for="livro">Livro:</label>
                                <input type="text" id="livro" name="livro" required placeholder="Ex: Isaías">
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <td colspan="2">
                            <div class="checkbox-container">
                                <input type="checkbox" id="gerar_10" name="gerar_10" value="1">
                                <label for="gerar_10">Gerar em formato coletivo?</label>
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <td>
                            <div class="form-group">
                                <label for="capitulo">Capítulo:</label>
                                <input type="number" id="capitulo" name="capitulo" min="1" required placeholder="Número do capítulo">
                            </div>
                        </td>
                        <td>
                            <div class="form-group">
                                <label for="verso_inicio">Verso Inicial:</label>
                                <input type="number" id="verso_inicio" name="verso_inicio" min="1" required placeholder="Verso inicial">
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <td colspan="2">
                            <div class="form-group" id="verso_fim_container">
                                <label for="verso_fim">Verso Final:</label>
                                <input type="number" id="verso_fim" name="verso_fim" min="1" required placeholder="Verso final">
                            </div>
                        </td>
                    </tr>
            </tbody>
        </table>

        <div style="text-align: center;">
            <button type="submit" id="submit-btn">
                <i class="fas fa-certificate"></i> Gerar Recitativo
            </button>
            <button type="button" id="btn-limpar-form" class="btn-clear">
                <i class="fas fa-eraser"></i> Limpar Formulário
            </button>
        </div>
        </form>

        <div style="text-align: center; margin-top: 30px;">
            <a href="index.php"><i class="fas fa-arrow-left"></i> Voltar para a página inicial</a>
        </div>
    </div>

    <!-- Adicionando jQuery para AJAX -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        $(document).ready(function() {
            // Variáveis globais
            const versaoPadrao = <?php echo $versaoPadrao; ?>;
            let isLoading = false;
            
            // Mostrar notificação
            function showNotification(message, type = 'success') {
                const notification = $('#notification');
                notification.text(message);
                notification.removeClass('error');
                if (type === 'error') notification.addClass('error');
                notification.fadeIn(300);
                
                setTimeout(function() {
                    notification.fadeOut(300);
                }, 3000);
            }

            // Preencher data atual
            function preencherDataAtual() {
                const hoje = new Date();
                $('#dia').val(hoje.getDate());
                $('#mes').val(['Janeiro', 'Fevereiro', 'Março', 'Abril', 'Maio', 'Junho', 
                              'Julho', 'Agosto', 'Setembro', 'Outubro', 'Novembro', 'Dezembro'][hoje.getMonth()]);
                $('#ano').val(hoje.getFullYear() % 100);
            }

            // Chamar função para preencher data atual
            preencherDataAtual();

            // Função para atualizar a visibilidade do verso final
            function toggleVersoFimVisibility() {
                if ($('#gerar_10').is(':checked')) {
                    $('#verso_fim_container').hide();
                    $('#verso_fim').val($('#verso_inicio').val());
                } else {
                    $('#verso_fim_container').show();
                }
            }

            // Inicializa a visibilidade
            toggleVersoFimVisibility();

            // Evento para o checkbox coletivo
            $('#gerar_10').change(toggleVersoFimVisibility);

            // Evento para o verso inicial
            $('#verso_inicio').on('input', function() {
                if ($('#gerar_10').is(':checked')) {
                    $('#verso_fim').val($(this).val());
                }
            });

            // Limpar seleção bíblica
            $('#btn-limpar').click(function() {
                $('#select-livro').val('');
                $('#select-capitulo').empty().append('<option value="">Selecione um capítulo</option>').prop('disabled', true);
                $('#select-verso-inicio').empty().append('<option value="">Selecione um verso</option>').prop('disabled', true);
                $('#select-verso-fim').empty().append('<option value="">Selecione um verso</option>').prop('disabled', true);
                $('#btn-preencher').prop('disabled', true);
                showNotification('Seleção limpa com sucesso');
            });

            // Limpar formulário
            $('#btn-limpar-form').click(function() {
                if (confirm('Tem certeza que deseja limpar todos os campos do formulário?')) {
                    $('#recitativo-form')[0].reset();
                    preencherDataAtual();
                    showNotification('Formulário limpo com sucesso');
                }
            });

            // Quando o livro é selecionado
            $('#select-livro').change(function() {
                const livroId = $(this).val();
                $('#select-capitulo').empty().append('<option value="">Selecione um capítulo</option>').prop('disabled', true);
                $('#select-verso-inicio').empty().append('<option value="">Selecione um verso</option>').prop('disabled', true);
                $('#select-verso-fim').empty().append('<option value="">Selecione um verso</option>').prop('disabled', true);
                $('#btn-preencher').prop('disabled', true);

                if (livroId) {
                    // Mostrar loading
                    isLoading = true;
                    $('#select-capitulo').prop('disabled', true).html('<option value="">Carregando...</option>');
                    
                    // Busca os capítulos do livro selecionado
                    $.ajax({
                        url: 'ajax_get_capitulos.php',
                        type: 'GET',
                        data: {
                            livro_id: livroId,
                            versao_id: versaoPadrao
                        },
                        success: function(data) {
                            $('#select-capitulo').prop('disabled', false);
                            $('#select-capitulo').html('<option value="">Selecione um capítulo</option>' + data);
                            isLoading = false;
                        },
                        error: function() {
                            showNotification('Erro ao carregar capítulos', 'error');
                            isLoading = false;
                        }
                    });

                    // Atualiza o campo de livro no formulário
                    $('#livro').val($('#select-livro option:selected').text());
                }
            });

            // Quando o capítulo é selecionado
            $('#select-capitulo').change(function() {
                const livroId = $('#select-livro').val();
                const capitulo = $(this).val();
                $('#select-verso-inicio').empty().append('<option value="">Selecione um verso</option>').prop('disabled', true);
                $('#select-verso-fim').empty().append('<option value="">Selecione um verso</option>').prop('disabled', true);
                $('#btn-preencher').prop('disabled', true);

                if (livroId && capitulo) {
                    // Mostrar loading
                    isLoading = true;
                    $('#select-verso-inicio').prop('disabled', true).html('<option value="">Carregando...</option>');
                    $('#select-verso-fim').prop('disabled', true).html('<option value="">Carregando...</option>');
                    
                    // Busca os versículos do capítulo selecionado
                    $.ajax({
                        url: 'ajax_get_versiculos.php',
                        type: 'GET',
                        data: {
                            livro_id: livroId,
                            capitulo: capitulo,
                            versao_id: versaoPadrao
                        },
                        success: function(data) {
                            $('#select-verso-inicio').prop('disabled', false);
                            $('#select-verso-fim').prop('disabled', false);
                            $('#select-verso-inicio').html('<option value="">Selecione um verso</option>' + data);
                            $('#select-verso-fim').html('<option value="">Selecione um verso</option>' + data);
                            isLoading = false;
                        },
                        error: function() {
                            showNotification('Erro ao carregar versículos', 'error');
                            isLoading = false;
                        }
                    });

                    // Atualiza o campo de capítulo no formulário
                    $('#capitulo').val(capitulo);
                }
            });

            // Quando os versículos são selecionados
            $('#select-verso-inicio, #select-verso-fim').change(function() {
                const inicio = $('#select-verso-inicio').val();
                const fim = $('#select-verso-fim').val();

                if (inicio && fim) {
                    // Verifica se o início é maior que o fim
                    if (parseInt(inicio) > parseInt(fim)) {
                        showNotification('O verso inicial não pode ser maior que o final', 'error');
                        $('#btn-preencher').prop('disabled', true);
                    } else {
                        $('#btn-preencher').prop('disabled', false);
                    }
                } else {
                    $('#btn-preencher').prop('disabled', true);
                }
            });

            // Botão para preencher automaticamente
            $('#btn-preencher').click(function(e) {
                e.preventDefault();
                
                if (isLoading) {
                    showNotification('Aguarde o carregamento dos dados', 'error');
                    return;
                }
                
                const inicio = $('#select-verso-inicio').val();
                const fim = $('#select-verso-fim').val();
                
                $('#verso_inicio').val(inicio);
                $('#verso_fim').val(fim);
                
                // Se for coletivo, atualiza o verso final também
                if ($('#gerar_10').is(':checked')) {
                    $('#verso_fim').val(inicio);
                }
                
                showNotification('Passagem preenchida com sucesso!');
            });

            // Validação do formulário antes do envio
            $('#recitativo-form').submit(function(e) {
                // Validar dia
                const dia = $('#dia').val();
                if (dia < 1 || dia > 31) {
                    showNotification('Por favor, insira um dia válido (1-31)', 'error');
                    e.preventDefault();
                    return;
                }

                // Validar ano
                const ano = $('#ano').val();
                if (ano < 0 || ano > 99) {
                    showNotification('Por favor, insira um ano válido (00-99)', 'error');
                    e.preventDefault();
                    return;
                }

                // Validar versículos
                const inicio = $('#verso_inicio').val();
                const fim = $('#verso_fim').val();
                if (parseInt(inicio) > parseInt(fim)) {
                    showNotification('O verso inicial não pode ser maior que o final', 'error');
                    e.preventDefault();
                    return;
                }

                // Mostrar loading no botão de submit
                const btnText = $('#submit-btn').html();
                $('#submit-btn').html('<span class="loading"></span> Gerando...').prop('disabled', true);
            });
        });
    </script>
</body>

</html>