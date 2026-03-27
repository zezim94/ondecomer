<?php
include("conexao.php");
$id = $_GET['id'];

// Busca dados do restaurante
$rest = $conn->query("SELECT * FROM restaurantes WHERE id = $id")->fetch_assoc();

// Busca itens do cardápio
$itens = $conn->query("SELECT * FROM cardapio WHERE id_restaurante = $id");
?>

<h1><?= $rest['nome'] ?></h1>
<p>Tipo: <?= $rest['tipo'] ?></p>

<h3>Menu:</h3>
<?php while($item = $itens->fetch_assoc()): ?>
    <div class="item-cardapio">
        <strong><?= $item['item_nome'] ?></strong> - R$ <?= number_format($item['valor'], 2, ',', '.') ?>
        <p><?= $item['descricao'] ?></p>
    </div>
<?php endwhile; ?>