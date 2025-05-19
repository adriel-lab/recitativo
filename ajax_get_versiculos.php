<?php
require 'config/conexao.php';

$livroId = (int)$_GET['livro_id'];
$capitulo = (int)$_GET['capitulo'];
$versaoId = (int)$_GET['versao_id'];

$conexao->select_db('biblia');

$query = "SELECT ver_versiculo 
          FROM versiculos 
          WHERE ver_liv_id = ? AND ver_capitulo = ? AND ver_vrs_id = ? 
          ORDER BY ver_versiculo";
$stmt = $conexao->prepare($query);
$stmt->bind_param("iii", $livroId, $capitulo, $versaoId);
$stmt->execute();
$result = $stmt->get_result();

$options = '';
while ($row = $result->fetch_assoc()) {
    $options .= "<option value='{$row['ver_versiculo']}'>{$row['ver_versiculo']}</option>";
}

echo $options;
$stmt->close();
?>