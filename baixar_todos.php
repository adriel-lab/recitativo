<?php
session_start();
require 'config/conexao.php';

// Verificar se o usuário está logado
if (!isset($_SESSION['logado']) || $_SESSION['logado'] !== true) {
    header("Location: index.php");
    exit;
}

// Obter ID do recitativo
$recitativo_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($recitativo_id > 0) {
    // Buscar informações do recitativo
    $sql = "SELECT arquivo_gerado FROM recitativos WHERE id = ?";
    $stmt = $conexao->prepare($sql);
    $stmt->bind_param("i", $recitativo_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $recitativo = $result->fetch_assoc();
    $stmt->close();
    
    if ($recitativo) {
        $arquivos = explode(',', $recitativo['arquivo_gerado']);
        $zip = new ZipArchive();
        $zip_name = "recitativo_{$recitativo_id}.zip";
        
        if ($zip->open($zip_name, ZipArchive::CREATE) === TRUE) {
            foreach ($arquivos as $arquivo) {
                $caminho_arquivo = "certificados_gerados/" . $arquivo;
                if (file_exists($caminho_arquivo)) {
                    $zip->addFile($caminho_arquivo, $arquivo);
                }
            }
            $zip->close();
            
            // Configurar headers para download
            header('Content-Type: application/zip');
            header('Content-Disposition: attachment; filename="' . $zip_name . '"');
            header('Content-Length: ' . filesize($zip_name));
            readfile($zip_name);
            
            // Apagar o arquivo zip após o download
            unlink($zip_name);
            exit;
        } else {
            die("Não foi possível criar o arquivo ZIP.");
        }
    }
}

// Se algo der errado, redirecionar
header("Location: visualizar.php?id={$recitativo_id}");
exit;
?>