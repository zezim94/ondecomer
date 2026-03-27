<?php
include("conexao.php");

if (isset($_POST['nome'])) {
    $nome = $conn->real_escape_string($_POST['nome']);
    
    $sql = "INSERT INTO categorias (nome) VALUES ('$nome')";
    
    if ($conn->query($sql)) {
        // Retorna o ID gerado para o JavaScript
        echo json_encode(['sucesso' => true, 'id' => $conn->insert_id]);
    } else {
        echo json_encode(['sucesso' => false]);
    }
}
?>