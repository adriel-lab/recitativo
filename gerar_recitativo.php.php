<?php
session_start();
require 'config/conexao.php';

// Verificar se o usuário está logado
if (!isset($_SESSION['logado']) || $_SESSION['logado'] !== true) {
    header("Location: index.php");
    exit;
}

// Enviar o HTML inicial imediatamente
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gerador de Recitativos CCB - Processando</title>
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
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
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
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        .fa-spin {
            animation: spin 2s linear infinite;
        }
    </style>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
</head>
<body>
    <div class="container">
        <h1><i class="fas fa-cog fa-spin"></i> Gerando Recitativos</h1>
        <div class="success-message">Processando seus recitativos, por favor aguarde...</div>
    </div>
<?php
// Enviar o buffer para o navegador
ob_flush();
flush();

// Configurações do sistema
$templatePath = 'CCB RECITATIVO.png';
$fontFile = __DIR__ . '/Times New Roman.ttf';
$outputDir = 'certificados_gerados/';

// Criar diretório se não existir
if (!file_exists($outputDir)) {
    if (!mkdir($outputDir, 0777, true)) {
        echo '<script>
            setTimeout(function() {
                document.querySelector(".success-message").className = "error-message";
                document.querySelector(".success-message").innerHTML = "Erro ao criar diretório de saída!";
                document.querySelector(".fa-spin").style.display = "none";
            }, 10000);
        </script>';
        exit;
    }
}

// Validar e sanitizar dados de entrada
$requiredFields = ['dia', 'mes', 'ano', 'livro', 'capitulo', 'verso_inicio', 'verso_fim', 'nome'];
foreach ($requiredFields as $field) {
    if (empty($_POST[$field])) {
        echo '<script>
            setTimeout(function() {
                document.querySelector(".success-message").className = "error-message";
                document.querySelector(".success-message").innerHTML = "Todos os campos são obrigatórios!";
                document.querySelector(".fa-spin").style.display = "none";
            }, 10000);
        </script>';
        exit;
    }
}

// Preparar dados
$data = [
    'dia' => str_pad(intval($_POST['dia']), 2, '0', STR_PAD_LEFT),
    'mes' => htmlspecialchars($_POST['mes']),
    'ano' => str_pad(intval($_POST['ano']), 2, '0', STR_PAD_LEFT),
    'livro' => htmlspecialchars($_POST['livro']),
    'capitulo' => intval($_POST['capitulo']),
    'nome' => htmlspecialchars($_POST['nome'])
];

$versoInicio = intval($_POST['verso_inicio']);
$versoFim = intval($_POST['verso_fim']);
$usuario_id = $_SESSION['usuario_id'];

// Validar intervalos
if ($data['dia'] < 1 || $data['dia'] > 31) {
    echo '<script>
        setTimeout(function() {
            document.querySelector(".success-message").className = "error-message";
            document.querySelector(".success-message").innerHTML = "Dia inválido!";
            document.querySelector(".fa-spin").style.display = "none";
        }, 10000);
    </script>';
    exit;
}

if ($versoInicio <= 0 || $versoFim <= 0 || $versoInicio > $versoFim) {
    echo '<script>
        setTimeout(function() {
            document.querySelector(".success-message").className = "error-message";
            document.querySelector(".success-message").innerHTML = "Intervalo de versículos inválido!";
            document.querySelector(".fa-spin").style.display = "none";
        }, 10000);
    </script>';
    exit;
}

// Configurações da página A4
$pageWidth = 2480;
$pageHeight = 3508;
$margin = 50;
$certWidth = (int)(($pageWidth - 3*$margin)/2);
$certHeight = (int)(($pageHeight - 6*$margin)/5);

// Função para gerar certificado individual
function generateCertificate($templatePath, $data, $fontFile, $versoAtual) {
    $image = @imagecreatefrompng($templatePath);
    if (!$image) {
        error_log("Erro ao carregar template: " . $templatePath);
        return false;
    }
    
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
$arquivos_gerados = [];
$data_recitativo = date('Y') . '-' . date('m') . '-' . $data['dia'];

try {
    while ($versoAtual <= $versoFim) {
        // Criar nova página A4
        $page = @imagecreatetruecolor($pageWidth, $pageHeight);
        if (!$page) {
            throw new Exception("Erro ao criar imagem");
        }
        
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
        $outputFile = 'pagina_' . $pagina . '_' . time() . '.png';
        $arquivos_gerados[] = $outputFile;
        $fullPath = $outputDir . $outputFile;
        
        if (!imagepng($page, $fullPath)) {
            throw new Exception("Erro ao salvar imagem: " . $fullPath);
        }
        
        imagedestroy($page);
        $pagina++;
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
        arquivo_gerado
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";

    $stmt = $conexao->prepare($sql);
    if (!$stmt) {
        throw new Exception("Erro ao preparar query: " . $conexao->error);
    }

    $arquivos_str = implode(',', $arquivos_gerados);
    
    $stmt->bind_param(
        "issiiiss",
        $usuario_id,
        $data['nome'],
        $data['livro'],
        $data['capitulo'],
        $versoInicio,
        $versoFim,
        $data_recitativo,
        $arquivos_str
    );

    if (!$stmt->execute()) {
        throw new Exception("Erro ao executar query: " . $stmt->error);
    }

    $recitativo_id = $stmt->insert_id;
    $stmt->close();
    
    // Registrar log
    registrarLog($usuario_id, 'GERACAO_RECITATIVO', "Gerados versículos $versoInicio-$versoFim");

    // Mostrar resultado após 10 segundos
    echo '<script>
        setTimeout(function() {
            document.querySelector(".fa-spin").style.display = "none";
            document.querySelector("h1").innerHTML = "Recitativos Gerados com Sucesso!";
            document.querySelector(".success-message").innerHTML = `
                <div style="margin: 20px 0;">
                    <a href="visualizar.php?id=' . $recitativo_id . '" class="btn">
                        <i class="fas fa-eye"></i> Visualizar Recitativos
                    </a>
                    <a href="lista_recitativos.php" class="btn">
                        <i class="fas fa-list"></i> Ver Todos os Recitativos
                    </a>
                    <a href="novo.php" class="btn">
                        <i class="fas fa-plus"></i> Criar Novo Recitativo
                    </a>
                </div>
                <p>Foram gerados ' . ($versoFim - $versoInicio + 1) . ' recitativos.</p>
                <p>Arquivos salvos em: ' . htmlspecialchars($outputDir) . '</p>
            `;
        }, 10000);
    </script>';

} catch (Exception $e) {
    // Limpar arquivos gerados em caso de erro
    foreach ($arquivos_gerados as $arquivo) {
        @unlink($outputDir . $arquivo);
    }
    
    error_log("Erro ao gerar recitativos: " . $e->getMessage());
    echo '<script>
        setTimeout(function() {
            document.querySelector(".success-message").className = "error-message";
            document.querySelector(".success-message").innerHTML = "Erro ao gerar recitativos: ' . htmlspecialchars($e->getMessage()) . '<br>
            <a href=\"novo.php\" class=\"btn\"><i class=\"fas fa-arrow-left\"></i> Voltar</a>";
            document.querySelector(".fa-spin").style.display = "none";
        }, 10000);
    </script>';
}

// Fechar conexão
if (isset($conexao)) {
    $conexao->close();
}
?>
</body>
</html>