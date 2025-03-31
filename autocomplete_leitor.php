<?php
session_start();
require 'config/conexao.php';

header('Content-Type: application/json');

$term = isset($_GET['term']) ? $_GET['term'] : '';

$results = [];
if (strlen($term) >= 2) {
    $sql = "SELECT DISTINCT nome_leitor FROM recitativos WHERE nome_leitor LIKE ? ORDER BY nome_leitor LIMIT 10";
    $stmt = $conexao->prepare($sql);
    $term = "%$term%";
    $stmt->bind_param("s", $term);
    $stmt->execute();
    $result = $stmt->get_result();
    
    while ($row = $result->fetch_assoc()) {
        $results[] = [
            'label' => $row['nome_leitor'],
            'value' => $row['nome_leitor']
        ];
    }
}

echo json_encode($results);
?>