<?php
// Configurações
$templatePath = 'CCB RECITATIVO.png';
$fontFile = __DIR__ . '/Times New Roman.ttf';
$outputDir = 'certificados_gerados/';

// Criar diretório se não existir
if (!file_exists($outputDir)) {
    mkdir($outputDir, 0777, true);
}

// Receber dados do formulário
$data = [
    'dia' => str_pad($_POST['dia'], 2, '0', STR_PAD_LEFT),
    'mes' => $_POST['mes'],
    'ano' => str_pad($_POST['ano'], 2, '0', STR_PAD_LEFT),
    'livro' => $_POST['livro'],
    'capitulo' => $_POST['capitulo'],
    'nome' => $_POST['nome']
];

$versoInicio = (int)$_POST['verso_inicio'];
$versoFim = (int)$_POST['verso_fim'];

// Configurações da página A4
$pageWidth = 2480;
$pageHeight = 3508;
$margin = 50;
$certWidth = (int)(($pageWidth - 3*$margin)/2);
$certHeight = (int)(($pageHeight - 6*$margin)/5);

// Função para gerar certificado individual
function generateCertificate($templatePath, $data, $fontFile, $versoAtual) {
    $image = @imagecreatefrompng($templatePath);
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
    // Preencher dados fixos
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
    
    // Preencher versículos
    imagettftext(
        $image,
        $fontSize,
        0,
        (int)($width * $positions['verso_inicio']['x']),
        (int)($width * $positions['verso_inicio']['y']),
        $black,
        $fontFile,
        (string)$versoAtual
    );
    
    imagettftext(
        $image,
        $fontSize,
        0,
        (int)($width * $positions['verso_fim']['x']),
        (int)($width * $positions['verso_fim']['y']),
        $black,
        $fontFile,
        '-'
    );
    
    return $image;
}

// Gerar páginas com 10 certificados cada
$versoAtual = $versoInicio;
$pagina = 1;

while ($versoAtual <= $versoFim) {
    // Criar nova página A4
    $page = imagecreatetruecolor($pageWidth, $pageHeight);
    $white = imagecolorallocate($page, 255, 255, 255);
    imagefill($page, 0, 0, $white);
    
    // Adicionar 10 certificados na página
    for ($i = 0; $i < 10 && $versoAtual <= $versoFim; $i++, $versoAtual++) {
        $data['verso_atual'] = $versoAtual;
        $cert = generateCertificate($templatePath, $data, $fontFile, $versoAtual);
        if (!$cert) continue;
        
        // Calcular posição na página
        $col = $i % 2;
        $row = (int)($i / 2);
        $x = $margin + ($col * ($certWidth + $margin));
        $y = $margin + ($row * ($certHeight + $margin));
        
        // Redimensionar e adicionar à página
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
    
    // Salvar página
    $outputFile = $outputDir . 'pagina_' . $pagina . '.png';
    imagepng($page, $outputFile);
    imagedestroy($page);
    
    $pagina++;
}

// Redirecionar para página de sucesso
echo 'Gerado com sucesso!';
exit;
?>