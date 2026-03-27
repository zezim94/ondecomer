<?php
session_start();
include("conexao.php");

// Adicionar categoria
if (isset($_POST['add_cat'])) {
    $nome = $_POST['nome_cat'];
    $conn->query("INSERT INTO categorias (nome) VALUES ('$nome')");
}

$categorias = $conn->query("SELECT * FROM categorias");
?>

<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Administração de Categorias</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', sans-serif;
        }

        body {
            background: #f4f6f9;
        }

        header {
            background: #1e293b;
            color: white;
            padding: 15px 25px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 3px solid #2563eb;
        }

        header h1 {
            font-size: 1.5rem;
        }

        header a {
            color: white;
            text-decoration: none;
            font-weight: 500;
            display: flex;
            align-items: center;
        }

        header a i {
            margin-right: 8px;
        }

        .container {
            max-width: 700px;
            margin: 40px auto;
            background: white;
            padding: 25px;
            border-radius: 16px;
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.05);
        }

        h2 {
            margin-bottom: 20px;
            color: #1e293b;
            text-align: center;
        }

        form.row {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
        }

        form.row input {
            flex: 1;
            padding: 12px;
            border-radius: 10px;
            border: 1px solid #ddd;
            outline: none;
            transition: 0.3s;
        }

        form.row input:focus {
            border-color: #2563eb;
        }

        .btn-principal {
            background: linear-gradient(135deg, #2563eb, #1e40af);
            color: white;
            border: none;
            padding: 12px 16px;
            border-radius: 10px;
            cursor: pointer;
            transition: 0.3s;
        }

        .btn-principal:hover {
            transform: translateY(-2px);
        }

        table.tabela-adm {
            width: 100%;
            border-collapse: collapse;
            border-radius: 12px;
            overflow: hidden;
        }

        table.tabela-adm thead {
            background: #2563eb;
            color: white;
        }

        table.tabela-adm th,
        table.tabela-adm td {
            padding: 12px;
            text-align: left;
        }

        table.tabela-adm tr {
            border-bottom: 1px solid #eee;
        }

        table.tabela-adm tr:hover {
            background: #f1f5f9;
        }

        table.tabela-adm a {
            color: #ef4444;
            text-decoration: none;
            font-weight: 500;
        }

        table.tabela-adm a:hover {
            text-decoration: underline;
        }

        a.back {
            display: inline-block;
            margin-top: 15px;
            padding: 10px 15px;
            background: #1e293b;
            color: white;
            border-radius: 10px;
            text-decoration: none;
            transition: 0.3s;
        }

        a.back:hover {
            background: #334155;
        }

        /* RESPONSIVO */
        @media (max-width: 600px) {
            form.row {
                flex-direction: column;
            }

            .btn-principal {
                width: 100%;
            }
        }
    </style>

</head>

<body>

    <header>
        <h1>Administração</h1>
        <a href="adm.php"><i class="fas fa-arrow-left"></i> Painel</a>
    </header>

    <div class="container">
        <h2>🍔 Categorias de Comida</h2>

        <form method="POST" class="row">
            <input type="text" name="nome_cat" placeholder="Nova Categoria (Ex: Italiana)" required>
            <button type="submit" name="add_cat" class="btn-principal"><i class="fas fa-plus"></i>Adicionar</button>
        </form>

        <table class="tabela-adm">
            <thead>
                <tr>
                    <th>Categoria</th>
                    <th>Ação</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($c = $categorias->fetch_assoc()): ?>
                    <tr>
                        <td><?= htmlspecialchars($c['nome']) ?></td>
                        <td>
                            <a href="?excluir=<?= $c['id'] ?>"
                                onclick="return confirm('Deseja realmente excluir esta categoria?');">
                                <i class="fas fa-trash"></i> Excluir
                            </a>
                        </td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>

        <a href="adm.php" class="back"><i class="fas fa-home"></i> Voltar ao Painel</a>
    </div>

</body>

</html>