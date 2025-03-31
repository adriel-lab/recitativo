<?php
session_start();
require 'config/conexao.php';

// Verifica se o usuário está logado
if (!isset($_SESSION['logado']) || $_SESSION['logado'] !== true) {
    header("Location: index.php");
    exit;
}
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
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
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
        
        input, select {
            width: 100%;
            padding: 10px;
            box-sizing: border-box;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        
        input:focus, select:focus {
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
    </style>
       <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
</head>
<body>
    <div class="container">
        <!-- Cabeçalho com informações do usuário -->
        <div class="user-header">
            <span><i class="fas fa-user"></i> <?php echo htmlspecialchars($_SESSION['nome']); ?></span>
            <a href="index.php?logout=1"><i class="fas fa-sign-out-alt"></i> Sair</a>
        </div>
        
        <h1><i class="fas fa-plus-circle"></i> Novo Recitativo CCB</h1>
        
        <table>
            <thead>
                <tr>
                    <th colspan="2">Preencha os dados para gerar o recitativo</th>
                </tr>
            </thead>
            <tbody>
                <form action="gerar_recitativo.php" method="post">
                <tr>
                    <td colspan="2">
                        <div class="form-group">
                            <label for="nome">Nome do Leitor:</label>
                            <input type="text" id="nome" name="nome" required>
                        </div>
                    </td>
                </tr>
                <tr>
                    <td>
                        <div class="form-group">
                            <label for="dia">Dia:</label>
                            <input type="number" id="dia" name="dia" min="1" max="31" required>
                        </div>
                    </td>
                    <td>
                        <div class="form-group">
                            <label for="mes">Mês:</label>
                            <select id="mes" name="mes" required>
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
                        </div>
                    </td>
                    <td>
                        <div class="form-group">
                            <label for="livro">Livro:</label>
                            <input type="text" id="livro" name="livro" required>
                        </div>
                    </td>
                </tr>
                <tr>
                    <td>
                        <div class="form-group">
                            <label for="capitulo">Capítulo:</label>
                            <input type="number" id="capitulo" name="capitulo" min="1" required>
                        </div>
                    </td>
                    <td>
                        <div class="form-group">
                            <label for="verso_inicio">Verso Inicial:</label>
                            <input type="number" id="verso_inicio" name="verso_inicio" min="1" required>
                        </div>
                    </td>
                </tr>
                <tr>
                    <td colspan="2">
                        <div class="form-group">
                            <label for="verso_fim">Verso Final:</label>
                            <input type="number" id="verso_fim" name="verso_fim" min="1" required>
                        </div>
                    </td>
                </tr>
            </tbody>
        </table>
        
        <button type="submit">
            <i class="fas fa-certificate"></i> Gerar Recitativo
        </button>
        </form>
        
        <div style="text-align: center; margin-top: 30px;">
            <a href="index.php"><i class="fas fa-arrow-left"></i> Voltar para a página inicial</a>
        </div>
    </div>
    
    <!-- Adicionando Font Awesome para os ícones -->
    <script src="https://kit.fontawesome.com/a076d05399.js" crossorigin="anonymous"></script>
</body>
</html>