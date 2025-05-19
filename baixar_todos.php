<?php
session_start();
require 'config/conexao.php';

// Verificar se o usuário está logado
if (!isset($_SESSION['logado']) || $_SESSION['logado'] !== true) {
    header("Location: index.php");
    exit;
}

// Obter ID e tipo do recitativo
$recitativo_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$tipo = isset($_GET['tipo']) ? (int)$_GET['tipo'] : 0;

if ($recitativo_id > 0) {
    // Buscar informações completas do recitativo
    $sql = "SELECT r.*, u.nome as usuario_nome 
            FROM recitativos r
            JOIN usuarios u ON r.usuario_id = u.id
            WHERE r.id = ?";
    $stmt = $conexao->prepare($sql);
    $stmt->bind_param("i", $recitativo_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $recitativo = $result->fetch_assoc();
    $stmt->close();
    
    if ($recitativo) {
        $arquivos = explode(',', $recitativo['arquivo_gerado']);
        $zip = new ZipArchive();
        
        // Criar nome descritivo para o ZIP
        $tipo_nome = ($tipo == 1) ? 'verso' : 'frente';
        $ref_biblia = htmlspecialchars($recitativo['livro']) . '_' . $recitativo['capitulo'];
        if ($tipo != 1) {
            $ref_biblia .= '_' . $recitativo['verso_inicio'] . '-' . $recitativo['verso_fim'];
        }
        $zip_name = "recitativo_{$ref_biblia}_{$tipo_nome}.zip";
        
        // Determinar o diretório baseado no tipo
        $diretorio = ($tipo == 1) ? 'certificados_verso/' : 'certificados_gerados/';
        
        if ($zip->open($zip_name, ZipArchive::CREATE) === TRUE) {
            // Adicionar arquivos ao ZIP
            foreach ($arquivos as $arquivo) {
                $arquivo = trim($arquivo);
                $caminho_arquivo = $diretorio . $arquivo;
                if (file_exists($caminho_arquivo)) {
                    $zip->addFile($caminho_arquivo, $arquivo);
                } else {
                    error_log("Arquivo não encontrado: " . $caminho_arquivo);
                }
            }
            
            // Adicionar arquivo de informações (README)
            $info_content = "Recitativo CCB\n";
            $info_content .= "================\n";
            $info_content .= "Tipo: " . ($tipo == 1 ? "Versículo" : "Normal") . "\n";
            $info_content .= "Livro: " . htmlspecialchars($recitativo['livro']) . "\n";
            $info_content .= "Capítulo: " . $recitativo['capitulo'] . "\n";
            if ($tipo != 1) {
                $info_content .= "Versículos: " . $recitativo['verso_inicio'] . "-" . $recitativo['verso_fim'] . "\n";
            }
            $info_content .= "Data: " . $recitativo['data_recitativo'] . "\n";
            $info_content .= "Leitor: " . htmlspecialchars($recitativo['nome_leitor']) . "\n";
            $info_content .= "Gerado por: " . htmlspecialchars($recitativo['usuario_nome']) . "\n";
            $info_content .= "Data geração: " . $recitativo['data_geracao'] . "\n";
            
            $zip->addFromString('INFO.txt', $info_content);
            
            if ($zip->numFiles > 0) {
                $zip->close();
                
                // Configurar headers para download
                header('Content-Type: application/zip');
                header('Content-Disposition: attachment; filename="' . $zip_name . '"');
                header('Content-Length: ' . filesize($zip_name));
                header('Cache-Control: no-cache, must-revalidate');
                header('Pragma: no-cache');
                header('Expires: 0');
                
                readfile($zip_name);
                
                // Apagar o arquivo zip após o download
                unlink($zip_name);
                exit;
            } else {
                $zip->close();
                unlink($zip_name);
                $_SESSION['erro'] = "Nenhum arquivo válido encontrado para download.";
                header("Location: visualizar.php?id={$recitativo_id}");
                exit;
            }
        } else {
            $_SESSION['erro'] = "Não foi possível criar o arquivo ZIP.";
            header("Location: visualizar.php?id={$recitativo_id}");
            exit;
        }
    }
}

// Se algo der errado, redirecionar com mensagem de erro
$_SESSION['erro'] = "Recitativo não encontrado.";
header("Location: lista_recitativos.php");
exit;
?>