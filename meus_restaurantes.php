<?php
session_start();
include("conexao.php");

if (!isset($_SESSION['usuario_id']) || !in_array($_SESSION['usuario_nivel'], ['master'])) {
    header("Location: login.php");
    exit();
}

// Lógica de Exclusão
if (isset($_GET['excluir'])) {
    $id = $_GET['excluir'];
    $conn->query("DELETE FROM restaurantes WHERE id = $id");
    header("Location: meus_restaurantes.php");
}

$id_logado = $_SESSION['usuario_id'];
$nivel_logado = $_SESSION['usuario_nivel'];

if ($nivel_logado == 'master') {
    // MASTER: Vê todos os restaurantes do sistema
    $sql_busca = "SELECT r.*, c.nome as categoria_nome, u.nome as nome_dono 
                  FROM restaurantes r 
                  LEFT JOIN categorias c ON r.id_categoria = c.id
                  LEFT JOIN usuarios u ON r.id_usuario = u.id";
} else {
    // ADM: Vê apenas os SEUS restaurantes
    $sql_busca = "SELECT r.*, c.nome as categoria_nome, 'Você' as nome_dono 
                  FROM restaurantes r 
                  LEFT JOIN categorias c ON r.id_categoria = c.id 
                  WHERE r.id_usuario = $id_logado";
}

$restaurantes = $conn->query($sql_busca);
?>

<!DOCTYPE html>
<html lang="pt-br">

<head>
    <title>Meus Restaurantes</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        /* RESET */
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
            background: #fff;
            padding: 30px;
            border-radius: 16px;
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.05);
        }

        /* BOTÃO EDITAR */
        .btn-editar {
            background: #fbbf24;
            /* amarelo */
            color: white;
            padding: 8px 12px;
            border-radius: 8px;
            text-decoration: none;
            font-size: 14px;
            transition: 0.2s;
        }

        .btn-editar:hover {
            background: #f59e0b;
        }

        /* HEADER */
        .header-acoes {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }

        .header-acoes h2 {
            color: #1e293b;
        }

        /* BOTÃO PRINCIPAL */
        .btn-principal {
            background: linear-gradient(135deg, #2563eb, #1e40af);
            color: #fff;
            padding: 12px 18px;
            border-radius: 10px;
            text-decoration: none;
            font-weight: 500;
            transition: 0.3s;
        }

        .btn-principal:hover {
            transform: translateY(-2px);
            opacity: 0.95;
        }

        /* TABELA */
        .tabela-adm {
            width: 100%;
            border-collapse: collapse;
            border-radius: 12px;
            overflow: hidden;
        }

        .tabela-adm thead {
            background: #1e293b;
            color: #fff;
        }

        .tabela-adm th,
        .tabela-adm td {
            padding: 14px;
            text-align: left;
        }

        .tabela-adm td {
            border-bottom: 1px solid #eee;
        }

        .tabela-adm tbody tr {
            transition: 0.2s;
        }

        .tabela-adm tbody tr:hover {
            background: #f1f5f9;
        }

        /* AÇÕES */
        td a {
            text-decoration: none;
            font-size: 14px;
            margin-right: 8px;
        }

        /* BOTÃO CARDÁPIO */
        .btn-cardapio {
            background: #22c55e;
            color: white;
            padding: 8px 12px;
            border-radius: 8px;
            transition: 0.2s;
        }

        .btn-cardapio:hover {
            background: #16a34a;
        }

        /* BOTÃO LIXEIRA */
        .btn-lixeira {
            background: #ef4444;
            color: white;
            padding: 8px 10px;
            border-radius: 8px;
            transition: 0.2s;
        }

        .btn-lixeira:hover {
            background: #dc2626;
        }

        /* VOLTAR */
        a[href="adm.php"] {
            display: inline-block;
            margin-top: 15px;
            padding: 10px 15px;
            background: #1e293b;
            color: white;
            border-radius: 10px;
            text-decoration: none;
        }

        a[href="adm.php"]:hover {
            background: #334155;
        }

        /* RESPONSIVO */
        @media (max-width: 768px) {
            .header-acoes {
                flex-direction: column;
                gap: 10px;
                align-items: flex-start;
            }

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
                color: #64748b;
                display: block;
                margin-bottom: 4px;
            }
        }
    </style>
</head>

<body>
    <div class="container-larga">
        <div class="header-acoes">
            <h2>🍴 Meus Restaurantes</h2>
            <a href="cadastrar_restaurante.php" class="btn-principal">+ Novo Restaurante</a>
        </div>

        <table class="tabela-adm">
            <thead>
                <tr>
                    <th>Nome</th>
                    <th>Categoria</th>
                    <th>Horário</th>
                    <th>Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($r = $restaurantes->fetch_assoc()): ?>
                    <tr>
                        <td data-label="Nome"><strong><?= $r['nome'] ?></strong></td>
                        <td data-label="Categoria"><?= $r['categoria_nome'] ?></td>
                        <td data-label="Horário"><?= $r['horario_abre'] ?> - <?= $r['horario_fecha'] ?></td>

                        <td data-label="Ações">
                            <a href="gerenciar_cardapio.php?id=<?= $r['id'] ?>" class="btn-cardapio"
                                title="Gerenciar Pratos">
                                <i class="fas fa-utensils"></i> Cardápio
                            </a>

                            <a href="editar_restaurante.php?id=<?= $r['id'] ?>" class="btn-editar"
                                title="Editar Restaurante">
                                <i class="fas fa-edit"></i> Editar
                            </a>

                            <a href="?excluir=<?= $r['id'] ?>" class="btn-lixeira"
                                onclick="return confirm('Excluir este restaurante?')">
                                <i class="fas fa-trash"></i>
                            </a>
                        </td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
        <br>
        <a href="adm.php">← Voltar ao Painel</a>
    </div>
</body>

</html>