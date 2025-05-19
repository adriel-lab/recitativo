<?php
session_start();
require 'config/conexao.php';

// Verifica se o formulário foi submetido
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: formulario_versiculos.html');
    exit;
}

// Verificar se o usuário está logado
if (!isset($_SESSION['logado']) || $_SESSION['logado'] !== true) {
    header("Location: index.php");
    exit;
}

$gerar10PorFolha = isset($_POST['gerar_10']) && $_POST['gerar_10'] == '1';
?>
<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gerador de Versículos CCB - Processando</title>
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
            max-width: 800px;
            margin: 20px auto;
            background-color: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            text-align: center;
        }

        .success-message {
            color: #2ecc71;
            font-size: 18px;
            margin: 20px 0;
        }

        .error-message {
            color: #e74c3c;
            font-size: 18px;
            margin: 20px 0;
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

        @keyframes spin {
            0% {
                transform: rotate(0deg);
            }

            100% {
                transform: rotate(360deg);
            }
        }

        .fa-spin {
            animation: spin 2s linear infinite;
        }
    </style>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
</head>

<body>
    <div class="container">
        <h1><i class="fas fa-cog fa-spin"></i> Gerando Versículos</h1>
        <div class="success-message">Processando seus versículos, por favor aguarde...</div>
    </div>
    <?php
    // Enviar o buffer para o navegador
    ob_flush();
    flush();

    // Configurações
    $templatePath = 'CCB RECITATIVO-tras.png';
    $fontFile = __DIR__ . '/Times New Roman.ttf';
    $outputDir = 'certificados_verso/';
    $margin = 50; // Margem entre certificados
    $textMargin = 0.1; // Margem interna do texto

    // Dimensões A4 (2480x3508px em 300dpi)
    $pageWidth = 2480;
    $pageHeight = 3508;

    // Configuração por página (2 colunas x 5 linhas)
    $certsPerPage = 10;
    $certWidth = (int)(($pageWidth - 3*$margin)/2);
    $certHeight = (int)(($pageHeight - 6*$margin)/5);

    // Valida e sanitiza os dados do formulário
    $livro = filter_input(INPUT_POST, 'livro', FILTER_DEFAULT);
    $livro = $livro !== null ? htmlspecialchars(strip_tags($livro)) : '';
    $capitulo = filter_input(INPUT_POST, 'capitulo', FILTER_VALIDATE_INT);
    $versiculosTexto = filter_input(INPUT_POST, 'versiculos', FILTER_DEFAULT);
    $versiculosTexto = $versiculosTexto !== null ? htmlspecialchars(strip_tags($versiculosTexto)) : '';

    // Validação adicional
    if (empty($livro) || empty($capitulo) || empty($versiculosTexto)) {
        echo '<script>
        setTimeout(function() {
            document.querySelector(".success-message").className = "error-message";
            document.querySelector(".success-message").innerHTML = "Todos os campos são obrigatórios!";
            document.querySelector(".fa-spin").style.display = "none";
        }, 1000);
        </script>';
        exit;
    }

    // Processa os versículos
    $versiculos = explode("\n", $versiculosTexto);
    $versosFormatados = [];

    foreach ($versiculos as $linha) {
        $linha = trim($linha);
        if (empty($linha)) continue;
        
        // Extrai número e texto do versículo
        if (preg_match('/^(\d+)\s+(.+)$/', $linha, $matches)) {
            $numero = $matches[1];
            $texto = $matches[2];
            $versosFormatados[] = "$numero $texto";
        }
    }

    // Verifica se encontrou versículos válidos
    if (empty($versosFormatados)) {
        echo '<script>
        setTimeout(function() {
            document.querySelector(".success-message").className = "error-message";
            document.querySelector(".success-message").innerHTML = "Nenhum versículo válido foi encontrado no formato especificado!";
            document.querySelector(".fa-spin").style.display = "none";
        }, 1000);
        </script>';
        exit;
    }

    // Função para extrair número do versículo
    function extractVerseNumber($text) {
        preg_match('/^(\d+)/', $text, $matches);
        return isset($matches[1]) ? (int)$matches[1] : 0;
    }

    // Função para gerar imagem do verso
    function generateVerseImage($template, $font, $verseText, $certWidth, $certHeight, $textMargin) {
        $image = @imagecreatefrompng($template);
        if (!$image) return false;
        
        $black = imagecolorallocate($image, 0, 0, 0);
        $width = imagesx($image);
        $height = imagesy($image);
        
        // Tamanhos de fonte
        $titleSize = $width * 0.04;
        $textSize = $width * 0.025;
        
        // Extrai número do versículo
        $verseNumber = extractVerseNumber($verseText);
        $verseContent = substr($verseText, strpos($verseText, ' ') + 1);
        
        // Área segura para texto
        $safeX = $width * $textMargin;
        $safeY = $height * $textMargin;
        $safeWidth = $width * (1 - 2*$textMargin);
        
        // Adiciona título (número do versículo)
        $title = "Versículo " . $verseNumber;
        imagettftext(
            $image,
            $titleSize,
            0,
            (int)($safeX),
            (int)($safeY + $titleSize ),
            $black,
            $font,
            $title
        );
        
        // Adiciona livro e capítulo
        $bookChapter = "{$GLOBALS['livro']} {$GLOBALS['capitulo']}";
        imagettftext(
            $image,
            $titleSize * 0.8,
            0,
            (int)($width - $safeX - (strlen($bookChapter) * $titleSize * 0.50)),
            (int)($safeY + $titleSize + 1200),
            $black,
            $font,
            $bookChapter
        );
        
        // Quebra o conteúdo em linhas
        $maxChars = (int)($safeWidth / ($textSize * 0.6));
        $lines = explode("\n", wordwrap($verseContent, $maxChars, "\n", true));
        
        // Adiciona o texto
        $lineHeight = $textSize * 1.5;
        $currentY = $safeY + $titleSize + $lineHeight;
        
        foreach ($lines as $line) {
            if ($currentY > ($height - $safeY)) break;
            
            imagettftext(
                $image,
                $textSize,
                0,
                (int)$safeX,
                (int)$currentY + 200,
                $black,
                $font,
                trim($line)
            );
            $currentY += $lineHeight;
        }
        
        // Redimensiona
        $resized = imagecreatetruecolor($certWidth, $certHeight);
        imagecopyresampled($resized, $image, 0, 0, 0, 0, $certWidth, $certHeight, $width, $height);
        imagedestroy($image);
        
        return $resized;
    }

    // Cria diretório se não existir
    if (!file_exists($outputDir)) {
        if (!mkdir($outputDir, 0777, true)) {
            echo '<script>
            setTimeout(function() {
                document.querySelector(".success-message").className = "error-message";
                document.querySelector(".success-message").innerHTML = "Erro ao criar diretório de saída!";
                document.querySelector(".fa-spin").style.display = "none";
            }, 1000);
            </script>';
            exit;
        }
    }

    // Organiza os versículos em pares invertidos
    $pairedVerses = [];
    for ($i = 0; $i < count($versosFormatados); $i += 2) {
        if (isset($versosFormatados[$i + 1])) {
            $pairedVerses[] = $versosFormatados[$i + 1]; // Verso par primeiro
            $pairedVerses[] = $versosFormatados[$i];     // Verso ímpar depois
        } else {
            $pairedVerses[] = $versosFormatados[$i]; // Último verso se quantidade ímpar
        }
    }

    // Gera as páginas
    $pagina = 1;
    $totalVersos = count($pairedVerses);
    $arquivos_gerados = [];
    $data_recitativo = date('Y') . '-' . date('m') . '-' . date('d');

    try {
        // Se for 10 por folha, usa apenas o primeiro versículo
        $versosParaGerar = $gerar10PorFolha ? array_fill(0, 10, $pairedVerses[0]) : $pairedVerses;

        for ($i = 0; $i < ($gerar10PorFolha ? 1 : count($versosParaGerar)); $i += 10) {
            $page = imagecreatetruecolor($pageWidth, $pageHeight);
            $white = imagecolorallocate($page, 255, 255, 255);
            imagefill($page, 0, 0, $white);
            
            // Adiciona até 10 certificados por página
            $certCount = 0;
            for ($pos = 0; $pos < 10; $pos++) {
                $verseIndex = $i + $pos;
                
                // Se não for 10 por folha e acabou os versículos, para
                if (!$gerar10PorFolha && $verseIndex >= count($versosParaGerar)) {
                    break;
                }
                
                $cert = generateVerseImage(
                    $templatePath,
                    $fontFile,
                    $versosParaGerar[$verseIndex],
                    $certWidth,
                    $certHeight,
                    $textMargin
                );
                
                if ($cert) {
                    $col = $certCount % 2;
                    $row = (int)($certCount / 2);
                    $x = $margin + ($col * ($certWidth + $margin));
                    $y = $margin + ($row * ($certHeight + $margin));
                    
                    imagecopy($page, $cert, $x, $y, 0, 0, $certWidth, $certHeight);
                    imagedestroy($cert);
                    $certCount++;
                }
            }
            
            // Salva a página se tiver conteúdo
            if ($certCount > 0) {
                $outputFile = 'verso_pagina_' . $pagina . '_' . time() . '.png';
                $arquivos_gerados[] = $outputFile;
                $fullPath = $outputDir . $outputFile;

                if (!imagepng($page, $fullPath)) {
                    throw new Exception("Erro ao salvar imagem: " . $fullPath);
                }
                $pagina++;
            }
            
            imagedestroy($page);
        }

        // Registrar no banco de dados
        $sql = "INSERT INTO recitativos (
            usuario_id, 
            nome_leitor, 
            livro, 
            capitulo, 
            verso_inicio, 
            verso_fim, 
            data_recitativo,
            arquivo_gerado,
            tipo
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";

        $stmt = $conexao->prepare($sql);
        if (!$stmt) {
            throw new Exception("Erro ao preparar query: " . $conexao->error);
        }

        $arquivos_str = implode(',', $arquivos_gerados);
        $nome_leitor = "Versículos Gerados"; // Pode ajustar conforme necessário
        $verso_inicio = extractVerseNumber($versosFormatados[0]);
        $verso_fim = extractVerseNumber($versosFormatados[count($versosFormatados)-1]);
        $tipo = 1;

        $stmt->bind_param(
            "issiiissi",
            $_SESSION['usuario_id'],
            $nome_leitor,
            $livro,
            $capitulo,
            $verso_inicio,
            $verso_fim,
            $data_recitativo,
            $arquivos_str,
            $tipo
        );

        if (!$stmt->execute()) {
            throw new Exception("Erro ao executar query: " . $stmt->error);
        }

        $recitativo_id = $stmt->insert_id;
        $stmt->close();

        // Mostrar resultado após 10 segundos
        echo '<script>
        setTimeout(function() {
            document.querySelector(".fa-spin").style.display = "none";
            document.querySelector("h1").innerHTML = "Versículos Gerados com Sucesso!";
            document.querySelector(".success-message").innerHTML = `
                <div style="margin: 20px 0;">
                     <a href="visualizar.php?id=' . $recitativo_id . '&tipo=1" class="btn">
                        <i class="fas fa-eye"></i> Visualizar Versículos
                    </a>
                    <a href="lista_recitativos.php" class="btn">
                        <i class="fas fa-list"></i> Ver Todos os Recitativos
                    </a>
                    <a href="formulario_versiculos.html" class="btn">
                        <i class="fas fa-plus"></i> Criar Novo
                    </a>
                </div>
                <p>Foram gerados ' . count($versosFormatados) . ' versículos.</p>
                <p>Arquivos salvos em: ' . htmlspecialchars($outputDir) . '</p>
            `;
        }, 1000);
        </script>';

    } catch (Exception $e) {
        // Limpar arquivos gerados em caso de erro
        foreach ($arquivos_gerados as $arquivo) {
            @unlink($outputDir . $arquivo);
        }

        echo '<script>
        setTimeout(function() {
            document.querySelector(".success-message").className = "error-message";
            document.querySelector(".success-message").innerHTML = "Erro ao gerar versículos: ' . htmlspecialchars($e->getMessage()) . '<br>
            <a href=\"formulario_versiculos.html\" class=\"btn\"><i class=\"fas fa-arrow-left\"></i> Voltar</a>";
            document.querySelector(".fa-spin").style.display = "none";
        }, 1000);
        </script>';
    }

    // Fechar conexão
    if (isset($conexao)) {
        $conexao->close();
    }
    ?>
</body>
</html>