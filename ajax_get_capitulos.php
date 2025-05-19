<?php
require 'config/conexao.php';

$livroId = (int)$_GET['livro_id'];
$versaoId = (int)$_GET['versao_id'];

$conexao->select_db('biblia');

$query = "SELECT DISTINCT ver_capitulo 
          FROM versiculos 
          WHERE ver_liv_id = ? AND ver_vrs_id = ? 
          ORDER BY ver_capitulo";
$stmt = $conexao->prepare($query);
$stmt->bind_param("ii", $livroId, $versaoId);
$stmt->execute();
$result = $stmt->get_result();

$options = '';
while ($row = $result->fetch_assoc()) {
    $options .= "<option value='{$row['ver_capitulo']}'>{$row['ver_capitulo']}</option>";
}

echo $options;
$stmt->close();
?>