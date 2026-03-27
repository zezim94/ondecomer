<?php
session_start();
include("conexao.php");

// Lógica de Deletar
if (isset($_GET['deletar'])) {
    $id = $_GET['deletar'];
    $conn->query("DELETE FROM usuarios WHERE id = $id");
}

$usuarios = $conn->query("SELECT * FROM usuarios");
?>

<!DOCTYPE html>
<html>

<head>
    <title>Gerenciar Usuários</title>
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
        .container-larga {
            max-width: 1000px;
            margin: 40px auto;
            background: white;
            padding: 30px;
            border-radius: 16px;
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.05);
        }

        h2 {
            margin-bottom: 20px;
            color: #1e293b;
        }

        /* TABELA */
        .tabela-adm {
            width: 100%;
            border-collapse: collapse;
            overflow: hidden;
            border-radius: 12px;
        }

        .tabela-adm thead {
            background: #1e293b;
            color: white;
        }

        .tabela-adm th {
            padding: 14px;
            text-align: left;
            font-weight: 500;
        }

        .tabela-adm td {
            padding: 14px;
            border-bottom: 1px solid #eee;
        }

        .tabela-adm tbody tr {
            transition: 0.2s;
        }

        .tabela-adm tbody tr:hover {
            background: #f1f5f9;
        }

        /* BADGE NÍVEL */
        .badge {
            padding: 6px 10px;
            border-radius: 999px;
            font-size: 12px;
            font-weight: bold;
            text-transform: uppercase;
        }

        .badge:contains("adm") {
            background: #fee2e2;
            color: #b91c1c;
        }

        .badge:contains("user") {
            background: #dbeafe;
            color: #1d4ed8;
        }

        /* fallback manual (caso contains não funcione em alguns navegadores) */
        .badge {
            background: #e5e7eb;
            color: #374151;
        }

        /* AÇÕES */
        td a {
            text-decoration: none;
            font-weight: 500;
            margin-right: 8px;
        }

        td a:first-child {
            color: #2563eb;
        }

        td a:last-child {
            color: #ef4444;
        }

        td a:hover {
            text-decoration: underline;
        }

        /* BOTÃO VOLTAR */
        a[href="adm.php"] {
            display: inline-block;
            margin-top: 15px;
            padding: 10px 15px;
            background: #1e293b;
            color: white;
            border-radius: 10px;
            text-decoration: none;
            transition: 0.2s;
        }

        a[href="adm.php"]:hover {
            background: #334155;
        }

        .badge.adm {
            background: #fee2e2;
            color: #b91c1c;
        }

        .badge.user {
            background: #dbeafe;
            color: #1d4ed8;
        }

        /* RESPONSIVO */
        @media (max-width: 768px) {
            .tabela-adm thead {
                display: none;
            }

            .tabela-adm,
            .tabela-adm tbody,
            .tabela-adm tr,
            .tabela-adm td {
                display: block;
                width: 100%;
            }

            .tabela-adm tr {
                margin-bottom: 15px;
                background: #fff;
                border-radius: 12px;
                padding: 10px;
                box-shadow: 0 4px 10px rgba(0, 0, 0, 0.05);
            }

            .tabela-adm td {
                border: none;
                padding: 8px 0;
            }

            .tabela-adm td::before {
                content: attr(data-label);
                font-weight: bold;
                display: block;
                color: #64748b;
                margin-bottom: 4px;
            }
        }
    </style>
</head>

<body>
    <div class="container-larga">
        <h2>Usuários do Sistema</h2>
        <table border="1" class="tabela-adm">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Nome</th>
                    <th>E-mail</th>
                    <th>Nível</th>
                    <th>Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($u = $usuarios->fetch_assoc()): ?>
                    <tr>
                        <td data-label="ID"><?= $u['id'] ?></td>
                        <td data-label="Nome"><?= $u['nome'] ?></td>
                        <td data-label="E-mail"><?= $u['email'] ?></td>
                        <td data-label="Nível"><span class="badge <?= $u['nivel'] == 'adm' ? 'adm' : 'user' ?>">
                                <?= $u['nivel'] ?>
                            </span></td>
                        <td data-label="Ações">
                            <a href="editar_usuario.php?id=<?= $u['id'] ?>">Editar</a> |
                            <a href="?deletar=<?= $u['id'] ?>" onclick="return confirm('Tem certeza?')">Excluir</a>
                        </td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
        <br>
        <a href="adm.php">Voltar</a>
    </div>
</body>

</html>