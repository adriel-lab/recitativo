<?php
// Deve ser a primeira linha do script!
error_reporting(E_ALL);
ini_set('display_errors', 1);
header('Content-Type: image/png');

// Configurações da página A4 em pixels (2480x3508 em 300dpi)
$pageWidth = 2480;
$pageHeight = 3508;
$margin = 50;

// Certificados por página (2 colunas x 5 linhas)
$certsPerPage = 10;
$certWidth = (int)(($pageWidth - 3*$margin)/2); 
$certHeight = (int)(($pageHeight - 6*$margin)/5);

// Criar página A4
$page = imagecreatetruecolor($pageWidth, $pageHeight);
$white = imagecolorallocate($page, 255, 255, 255);
imagefill($page, 0, 0, $white);

// Corrigir o aviso do perfil ICCP (opcional)
function loadImageWithoutWarnings($path) {
    return @imagecreatefrompng($path);
}

// Gerar certificado individual
function generateCertificate($templatePath, $data, $fontFile) {
    $image = loadImageWithoutWarnings($templatePath);
    if (!$image) return false;
    
    $black = imagecolorallocate($image, 0, 0, 0);
    $width = imagesx($image);
    $fontSize = (int)($width * 0.03);
    
    $positions = [
        'dia' => ['x' => 0.45, 'y' => 0.21],
        'mes' => ['x' => 0.55, 'y' => 0.21],
        'ano' => ['x' => 0.82, 'y' => 0.21],
        'livro' => ['x' => 0.225, 'y' => 0.26],
        'capitulo' => ['x' => 0.285, 'y' => 0.31],
        'verso_inicio' => ['x' => 0.5875, 'y' => 0.31],
        'verso_fim' => ['x' => 0.7925, 'y' => 0.31],
        'nome' => ['x' => 0.25, 'y' => 0.66]
    ];


    

    foreach ($data as $key => $value) {
        if (isset($positions[$key])) {
            imagettftext(
                $image, 
                $fontSize,
                0,
                (int)($width * $positions[$key]['x']),
                (int)($width * $positions[$key]['y']),
                $black,
                $fontFile,
                $value
            );
        }
    }
    
    return $image;
}

// Dados de exemplo (substitua pelos reais)
$fontFile = __DIR__.'/arial.ttf';
$template = 'CCB RECITATIVO.png';

// Gerar 10 certificados
for ($i = 0; $i < 10; $i++) {
    $data = [
        'dia' => str_pad(rand(1, 28), 2, '0', STR_PAD_LEFT),
        'mes' => ['Janeiro','Fevereiro','Março','Abril','Maio','Junho'][rand(0,5)],
        'ano' => str_pad(rand(20, 25), 2, '0', STR_PAD_LEFT),
        'livro' => ['Salmos','Provérbios','João','Mateus','Gênesis'][rand(0,4)],
        'capitulo' => rand(1, 50),
        'verso_inicio' => rand(1, 20),
        'verso_fim' => rand(21, 40),
        'nome' => 'Deus abençoe'
    ];
    
    $cert = generateCertificate($template, $data, $fontFile);
    if (!$cert) continue;
    
    // Calcular posição na página
    $col = $i % 2;
    $row = (int)($i / 2);
    $x = $margin + ($col * ($certWidth + $margin));
    $y = $margin + ($row * ($certHeight + $margin));
    
    // Redimensionar mantendo proporções
    $resizedCert = imagecreatetruecolor($certWidth, $certHeight);
    imagecopyresampled(
        $resizedCert, $cert, 
        0, 0, 0, 0, 
        $certWidth, $certHeight, 
        imagesx($cert), imagesy($cert)
    );
    imagecopy($page, $resizedCert, $x, $y, 0, 0, $certWidth, $certHeight);
    
    imagedestroy($cert);
    imagedestroy($resizedCert);
}

// Output final
imagepng($page);
imagedestroy($page);
exit;
?>