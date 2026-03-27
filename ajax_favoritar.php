<?php
session_start();
include("conexao.php");

if (isset($_POST['id_restaurante']) && isset($_SESSION['usuario_id'])) {
    $id_rest = intval($_POST['id_restaurante']);
    $id_user = $_SESSION['usuario_id'];

    // Verifica se já favoritou
    $check = $conn->query("SELECT id FROM favoritos WHERE id_usuario = $id_user AND id_restaurante = $id_rest");

    if ($check->num_rows > 0) {
        // Se já tem, remove (Desfavoritar)
        $conn->query("DELETE FROM favoritos WHERE id_usuario = $id_user AND id_restaurante = $id_rest");
        echo json_encode(['status' => 'removido']);
    } else {
        // Se não tem, adiciona (Favoritar)
        $conn->query("INSERT INTO favoritos (id_usuario, id_restaurante) VALUES ($id_user, $id_rest)");
        echo json_encode(['status' => 'adicionado']);
    }
}
?>