<?php
require 'config/conexao.php';

$livroId = (int)$_GET['livro_id'];
$capitulo = (int)$_GET['capitulo'];
$inicio = (int)$_GET['inicio'];
$fim = (int)$_GET['fim'];
$versaoId = (int)$_GET['versao_id'];

$conexao->select_db('biblia');

$query = "SELECT ver_versiculo, ver_texto 
          FROM versiculos 
          WHERE ver_liv_id = ? AND ver_capitulo = ? AND ver_vrs_id = ? 
          AND ver_versiculo BETWEEN ? AND ?
          ORDER BY ver_versiculo";
$stmt = $conexao->prepare($query);
$stmt->bind_param("iiiii", $livroId, $capitulo, $versaoId, $inicio, $fim);
$stmt->execute();
$result = $stmt->get_result();

$texto = '';
while ($row = $result->fetch_assoc()) {
    $texto .= "{$row['ver_versiculo']} {$row['ver_texto']}\n";
}

echo trim($texto);
$stmt->close();
?>