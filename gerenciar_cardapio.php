<?php
session_start();
include("conexao.php");

$id_restaurante = $_GET['id'];

// Busca dados do restaurante para o título
$res_info = $conn->query("SELECT nome FROM restaurantes WHERE id = $id_restaurante");
$restaurante = $res_info->fetch_assoc();

// Lógica de Inserção de Prato
if (isset($_POST['add_prato'])) {
    $nome = $conn->real_escape_string($_POST['item_nome']);
    $desc = $conn->real_escape_string($_POST['descricao']);
    $valor = $_POST['valor'];

    $sql = "INSERT INTO cardapio (id_restaurante, item_nome, descricao, valor) 
            VALUES ('$id_restaurante', '$nome', '$desc', '$valor')";
    $conn->query($sql);
}

// Busca itens já cadastrados
$itens = $conn->query("SELECT * FROM cardapio WHERE id_restaurante = $id_restaurante");
?>

<!DOCTYPE html>
<html lang="pt-br">

<head>
    <title>Cardápio - <?= $restaurante['nome'] ?></title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', sans-serif;
        }

        body {
            background: #f4f6f9;
            color: #333;
        }

        /* CONTAINER */
        .container {
            max-width: 900px;
            margin: 40px auto;
            padding: 20px;
        }

        /* TÍTULOS */
        h2 {
            margin-bottom: 20px;
            color: #1e293b;
        }

        h3 {
            margin-bottom: 15px;
            color: #334155;
        }

        /* CARD FORM */
        .card {
            background: white;
            padding: 25px;
            border-radius: 16px;
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.05);
            margin-bottom: 25px;
        }

        /* FORM */
        .form-cardapio input,
        .form-cardapio textarea {
            width: 100%;
            padding: 12px;
            margin-bottom: 12px;
            border: 1px solid #ddd;
            border-radius: 10px;
            transition: 0.2s;
        }

        .form-cardapio textarea {
            resize: none;
            height: 80px;
        }

        .form-cardapio input:focus,
        .form-cardapio textarea:focus {
            border-color: #2563eb;
        }

        /* BOTÃO */
        .btn-grande {
            width: 100%;
            padding: 14px;
            background: linear-gradient(135deg, #22c55e, #16a34a);
            color: white;
            border: none;
            border-radius: 12px;
            cursor: pointer;
            font-size: 15px;
            transition: 0.3s;
        }

        .btn-grande:hover {
            transform: translateY(-2px);
            opacity: 0.95;
        }

        /* LISTA DE ITENS */
        .lista-itens {
            margin-top: 15px;
        }

        /* ITEM */
        .item-linha {
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: white;
            padding: 15px 20px;
            border-radius: 12px;
            margin-bottom: 12px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.05);
            transition: 0.2s;
        }

        .item-linha:hover {
            transform: translateY(-2px);
        }

        /* INFO */
        .item-linha .info strong {
            font-size: 16px;
            color: #0f172a;
        }

        .item-linha .info p {
            font-size: 13px;
            color: #64748b;
            margin-top: 4px;
        }

        /* PREÇO */
        .preco {
            font-size: 16px;
            font-weight: bold;
            color: #16a34a;
            white-space: nowrap;
        }

        /* SEPARADOR */
        hr {
            border: none;
            border-top: 1px solid #e5e7eb;
            margin: 25px 0;
        }

        /* VOLTAR */
        a[href="meus_restaurantes.php"] {
            display: inline-block;
            margin-top: 10px;
            padding: 10px 15px;
            background: #1e293b;
            color: white;
            border-radius: 10px;
            text-decoration: none;
        }

        a[href="meus_restaurantes.php"]:hover {
            background: #334155;
        }

        .btn-delete {
            background: #ef4444;
            color: white;
            padding: 6px 10px;
            border-radius: 8px;
            text-decoration: none;
        }

        .btn-delete:hover {
            background: #dc2626;
        }

        /* RESPONSIVO */
        @media (max-width: 600px) {
            .item-linha {
                flex-direction: column;
                align-items: flex-start;
                gap: 10px;
            }

            .preco {
                align-self: flex-end;
            }
        }
    </style>
</head>

<body>
    <div class="container">
        <h2>Cardápio: <?= $restaurante['nome'] ?></h2>

        <div class="card form-cardapio">
            <h3>+ Adicionar Novo Item</h3>
            <form method="POST">
                <input type="text" name="item_nome" placeholder="Nome do Prato (Ex: Pizza Calabresa)" required>
                <textarea name="descricao" placeholder="Descrição (Ingredientes, tamanho...)"></textarea>
                <input type="number" step="0.01" name="valor" placeholder="Preço (Ex: 45.90)" required>
                <button type="submit" name="add_prato" class="btn-grande">Adicionar ao Menu</button>
            </form>
        </div>

        <hr>

        <h3>Itens Atuais</h3>
        <div class="lista-itens">
            <?php if ($itens->num_rows == 0): ?>
                <p>Nenhum item cadastrado ainda.</p>
            <?php endif; ?>

            <?php while ($i = $itens->fetch_assoc()): ?>
                <div class="item-linha">
                    <div class="info">
                        <strong><?= $i['item_nome'] ?></strong>
                        <p><?= $i['descricao'] ?></p>
                    </div>

                    <div style="display:flex; align-items:center; gap:10px;">
                        <div class="preco">R$ <?= number_format($i['valor'], 2, ',', '.') ?></div>
                        <a href="?excluir=<?= $i['id'] ?>" class="btn-delete">🗑️</a>
                    </div>
                </div>
            <?php endwhile; ?>
        </div>

        <br>
        <a href="meus_restaurantes.php">← Voltar aos Restaurantes</a>
    </div>
</body>

</html>