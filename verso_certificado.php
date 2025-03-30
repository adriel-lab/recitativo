<?php
// Verifica se o formulário foi submetido
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: formulario_versiculos.html');
    exit;
}

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
    die("Todos os campos são obrigatórios.");
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
    die("Nenhum versículo válido foi encontrado no formato especificado.");
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
    mkdir($outputDir, 0777, true);
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

for ($i = 0; $i < $totalVersos; $i += 10) {
    $page = imagecreatetruecolor($pageWidth, $pageHeight);
    $white = imagecolorallocate($page, 255, 255, 255);
    imagefill($page, 0, 0, $white);
    
    // Adiciona até 10 certificados por página
    $certCount = 0;
    for ($pos = 0; $pos < 10 && ($i + $pos) < $totalVersos; $pos++) {
        $verseIndex = $i + $pos;
        $cert = generateVerseImage(
            $templatePath,
            $fontFile,
            $pairedVerses[$verseIndex],
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
        $outputFile = $outputDir . 'verso_pagina_' . $pagina . '.png';
        imagepng($page, $outputFile);
        $pagina++;
    }
    
    imagedestroy($page);
}

// Página de sucesso
header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Certificados Gerados</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            line-height: 1.6;
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
            background-color: #f5f5f5;
            color: #333;
        }
        .success-box {
            background-color: #e8f8f5;
            border-left: 5px solid #2ecc71;
            padding: 20px;
            margin: 20px 0;
        }
        .file-list {
            background-color: #f8f9fa;
            padding: 15px;
            border-radius: 4px;
            margin-top: 20px;
        }
        .btn {
            display: inline-block;
            background-color: #3498db;
            color: white;
            padding: 10px 15px;
            text-decoration: none;
            border-radius: 4px;
            margin-top: 15px;
        }
    </style>
</head>
<body>
    <div class="success-box">
        <h2>Certificados gerados com sucesso!</h2>
        <p><strong>Livro:</strong> <?= htmlspecialchars($livro) ?> <?= htmlspecialchars($capitulo) ?></p>
        <p><strong>Total de versículos processados:</strong> <?= count($versosFormatados) ?></p>
        <p><strong>Páginas geradas:</strong> <?= ($pagina - 1) ?></p>
        
        <div class="file-list">
            <h3>Arquivos gerados:</h3>
            <?php for ($i = 1; $i < $pagina; $i++): ?>
                <p>verso_pagina_<?= $i ?>.png</p>
            <?php endfor; ?>
        </div>
        
        <a href="formulario_versiculos.html" class="btn">Voltar ao formulário</a>
        <a href="<?= $outputDir ?>" class="btn">Abrir pasta dos certificados</a>
    </div>
</body>
</html>