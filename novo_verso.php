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
    <title>Envio de Versículos Bíblicos - CCB</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f4;
            margin: 0;
            padding: 20px;
        }

        h1 {
            color: #333;
            text-align: center;
            margin-bottom: 30px;
            border-bottom: 2px solid #67458b;
            padding-bottom: 10px;
        }

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
        select,
        textarea {
            width: 100%;
            padding: 10px;
            box-sizing: border-box;
            border: 1px solid #ddd;
            border-radius: 4px;
        }

        textarea {
            min-height: 300px;
            resize: vertical;
            line-height: 1.6;
        }

        input:focus,
        select:focus,
        textarea:focus {
            border-color: #67458b;
            outline: none;
        }

        .btn-submit {
            background-color: #67458b;
            color: white;
            border: none;
            padding: 12px 20px;
            font-size: 16px;
            cursor: pointer;
            border-radius: 4px;
            transition: background-color 0.3s;
            display: block;
            width: 200px;
            margin: 20px auto;
        }

        .btn-submit:hover {
            background-color: #9362C6;
        }

        .instructions {
            background-color: #f8f9fa;
            padding: 15px;
            border-radius: 4px;
            margin-bottom: 25px;
            border-left: 4px solid #67458b;
        }

        .format-example {
            background-color: #f8f9fa;
            border-left: 4px solid #67458b;
            padding: 10px 15px;
            margin: 15px 0;
            font-family: monospace;
            white-space: pre-wrap;
        }

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
            color: #9362C6;
            text-decoration: none;
        }

        .user-header a:hover {
            text-decoration: underline;
        }

        a {
            color: #9362C6;
            text-decoration: none;
        }

        a:hover {
            text-decoration: underline;
        }

        .bible-selector {
            background-color: #f8f9fa;
            padding: 15px;
            border-radius: 4px;
            margin-bottom: 20px;
            border: 1px solid #ddd;
        }

        .selector-row {
            display: flex;
            gap: 10px;
            margin-bottom: 10px;
        }

        .selector-group {
            flex: 1;
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

        .checkbox-group {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-top: 10px;
        }

        .checkbox-group input[type="checkbox"] {
            width: auto;
            margin: 0;
        }

        .checkbox-group label {
            margin: 0;
            font-weight: normal;
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

        .text-counter {
            font-size: 12px;
            color: #666;
            text-align: right;
            margin-top: 5px;
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

        <h1><i class="fas fa-bible"></i> Envio de Versículos Bíblicos - CCB</h1>

        <div class="instructions">
            <h3><i class="fas fa-info-circle"></i> Instruções:</h3>
            <p><b>Você pode adicionar versículos de duas maneiras: </b></p>
            <p> 1° Selecionando a passagem:<br><br>
                Escolha o <var> Livro, Capítulo, Verso Inicial e Verso Final,</var> depois clique em <i class="fas fa-plus"></i> <b>Adicionar Passagem</b>.</p>

            <p>2° manualmente abaixo: </p>

            <p>Digite ou cole os versículos no formato abaixo, com cada versículo em uma linha começando pelo número:</p>
            <div class="format-example">
1 Peso do vale da visão. Que tens agora, pois que com todos...
2 Tu, cheia de clamores, cidade turbulenta, cidade alegre, os teus ...
3 Todos os teus governadores juntamente fugiram, foram ...
            </div>
        </div>

        <form action="novo_traseiro.php" method="post" id="versiculos-form">
            <!-- Seletor Bíblico -->
            <div class="bible-selector">
                <h3><i class="fas fa-search"></i> Selecionar Passagem Bíblica</h3>
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
                        <select id="select-verso-inicio" name="verso_inicio" class="form-control" disabled>
                            <option value="">Selecione um verso</option>
                        </select>
                    </div>
                    <div class="selector-group">
                        <label for="select-verso-fim">Verso Final:</label>
                        <select id="select-verso-fim" name="verso_fim" class="form-control" disabled>
                            <option value="">Selecione um verso</option>
                        </select>
                    </div>
                </div>

                <div class="checkbox-group">
                    <input type="checkbox" id="gerar_10" name="gerar_10" value="1">
                    <label for="gerar_10">Gerar em formato coletivo?</label>
                </div>

                <div style="margin-top: 15px;">
                    <button id="btn-adicionar" class="btn-select" disabled>
                        <i class="fas fa-plus"></i> Adicionar Passagem
                    </button>
                    <button id="btn-limpar" class="btn-clear" type="button">
                        <i class="fas fa-trash"></i> Limpar Seleção
                    </button>
                </div>
            </div>

            <div class="form-group">
                <label for="leitor"><i class="fas fa-book"></i> Leitor:</label>
                <input type="text" id="leitor" name="nome" required placeholder="Nome do leitor">
            </div>

            <div class="form-group">
                <label for="livro"><i class="fas fa-book"></i> Livro Bíblico:</label>
                <input type="text" id="livro" name="livro" required placeholder="Ex: Isaías">
            </div>

            <div class="form-group">
                <label for="capitulo"><i class="fas fa-list-ol"></i> Capítulo:</label>
                <input type="number" id="capitulo" name="capitulo" required min="1" placeholder="Ex: 22">
            </div>

            <div class="form-group">
                <label for="versiculos"><i class="fas fa-align-left"></i> Versículos (um por linha):</label>
                <textarea id="versiculos" name="versiculos" required placeholder="Cole os versículos no formato especificado"></textarea>
                <div class="text-counter" id="text-counter">0 versículos adicionados</div>
            </div>

            <div style="text-align: center;">
                <button type="submit" class="btn-submit" id="submit-btn">
                    <i class="fas fa-certificate"></i> Gerar Certificados
                </button>
                <button type="button" id="btn-limpar-texto" class="btn-clear">
                    <i class="fas fa-eraser"></i> Limpar Texto
                </button>
            </div>
        </form>

        <div style="text-align: center; margin-top: 30px;">
            <a href="index.php"><i class="fas fa-arrow-left"></i> Voltar para a página inicial</a>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        $(document).ready(function() {
            // Variáveis globais
            let versiculosSelecionados = [];
            const versaoPadrao = <?php echo $versaoPadrao; ?>;
            let isLoading = false;

            // Mostrar notificação
            function showNotification(message, type = 'success') {
                const notification = $('#notification');
                notification.text(message);
                notification.css('background-color', type === 'success' ? '#4CAF50' : '#f44336');
                notification.fadeIn(300);
                
                setTimeout(function() {
                    notification.fadeOut(300);
                }, 3000);
            }

            // Contar versículos no textarea
            function countVerses() {
                const text = $('#versiculos').val();
                const lines = text.split('\n').filter(line => line.trim() !== '' && /^\d+\s+.+/.test(line.trim()));
                $('#text-counter').text(`${lines.length} versículo${lines.length !== 1 ? 's' : ''} adicionado${lines.length !== 1 ? 's' : ''}`);
            }

            // Atualizar contador quando o texto muda
            $('#versiculos').on('input', countVerses);

            // Limpar seleção
            $('#btn-limpar').click(function() {
                $('#select-livro').val('');
                $('#select-capitulo').empty().append('<option value="">Selecione um capítulo</option>').prop('disabled', true);
                $('#select-verso-inicio').empty().append('<option value="">Selecione um verso</option>').prop('disabled', true);
                $('#select-verso-fim').empty().append('<option value="">Selecione um verso</option>').prop('disabled', true);
                $('#btn-adicionar').prop('disabled', true);
            });

            // Limpar texto
            $('#btn-limpar-texto').click(function() {
                if (confirm('Tem certeza que deseja limpar todos os versículos?')) {
                    $('#versiculos').val('');
                    versiculosSelecionados = [];
                    countVerses();
                    showNotification('Texto limpo com sucesso!');
                }
            });

            // Quando o livro é selecionado
            $('#select-livro').change(function() {
                const livroId = $(this).val();
                $('#select-capitulo').empty().append('<option value="">Selecione um capítulo</option>').prop('disabled', true);
                $('#select-verso-inicio').empty().append('<option value="">Selecione um verso</option>').prop('disabled', true);
                $('#select-verso-fim').empty().append('<option value="">Selecione um verso</option>').prop('disabled', true);
                $('#btn-adicionar').prop('disabled', true);

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
                $('#btn-adicionar').prop('disabled', true);

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
                        $('#btn-adicionar').prop('disabled', true);
                    } else {
                        $('#btn-adicionar').prop('disabled', false);
                    }
                } else {
                    $('#btn-adicionar').prop('disabled', true);
                }
            });

            // Botão para adicionar passagem
            $('#btn-adicionar').click(function(e) {
                e.preventDefault();
                
                if (isLoading) {
                    showNotification('Aguarde o carregamento dos dados', 'error');
                    return;
                }

                const livroId = $('#select-livro').val();
                const livroNome = $('#select-livro option:selected').text();
                const capitulo = $('#select-capitulo').val();
                const inicio = $('#select-verso-inicio').val();
                const fim = $('#select-verso-fim').val();

                // Verifica se já foi adicionado
                const chave = `${livroId}-${capitulo}-${inicio}-${fim}`;
                if (versiculosSelecionados.includes(chave)) {
                    showNotification('Esta passagem já foi adicionada', 'error');
                    return;
                }

                // Mostrar loading no botão
                const btnText = $(this).html();
                $(this).html('<span class="loading"></span> Carregando...').prop('disabled', true);

                // Busca os textos dos versículos selecionados
                $.ajax({
                    url: 'ajax_get_texto_versiculos.php',
                    type: 'GET',
                    data: {
                        livro_id: livroId,
                        capitulo: capitulo,
                        inicio: inicio,
                        fim: fim,
                        versao_id: versaoPadrao
                    },
                    success: function(data) {
                        // Adiciona os versículos ao textarea
                        const textarea = $('#versiculos');
                        const currentText = textarea.val();

                        if (currentText) {
                            textarea.val(currentText + '\n\n' + data);
                        } else {
                            textarea.val(data);
                        }

                        // Adiciona aos selecionados para evitar duplicatas
                        versiculosSelecionados.push(chave);
                        
                        // Atualiza contador
                        countVerses();
                        
                        // Mostra notificação
                        showNotification('Passagem adicionada com sucesso!');
                        
                        // Restaura botão
                        $('#btn-adicionar').html(btnText).prop('disabled', false);
                    },
                    error: function() {
                        showNotification('Erro ao carregar os versículos', 'error');
                        $('#btn-adicionar').html(btnText).prop('disabled', false);
                    }
                });
            });

            // Validação básica do formato antes do envio
            $('#versiculos-form').submit(function(e) {
                const textarea = $('#versiculos');
                const lines = textarea.val().split('\n').filter(line => line.trim() !== '');

                if (lines.length === 0) {
                    showNotification('Por favor, adicione pelo menos um versículo', 'error');
                    e.preventDefault();
                    return;
                }

                for (let i = 0; i < lines.length; i++) {
                    if (!/^\d+\s+.+/.test(lines[i])) {
                        showNotification(`Formato inválido na linha ${i+1}:\n\n"${lines[i]}"\n\nCada versículo deve começar com o número seguido do texto.`, 'error');
                        e.preventDefault();
                        return;
                    }
                }

                // Mostrar loading no botão de submit
                $('#submit-btn').html('<span class="loading"></span> Processando...').prop('disabled', true);
            });
        });
    </script>
</body>

</html>