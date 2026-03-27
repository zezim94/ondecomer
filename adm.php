<?php
session_start();
// Proteção: Permite a entrada se for ADM ou MASTER
if (!isset($_SESSION['usuario_id']) || ($_SESSION['usuario_nivel'] !== 'adm' && $_SESSION['usuario_nivel'] !== 'master')) {
    header("Location: login.php");
    exit();
}
include("conexao.php");

$id_logado = $_SESSION['usuario_id'];
$nivel_logado = $_SESSION['usuario_nivel'];

// 1. Consultas Dinâmicas (Muda dependendo de quem logou)
if ($nivel_logado == 'master') {
    $titulo_painel = "Painel Master 👑";
    $filtro_sql = ""; // Sem filtro, busca tudo
    $q_rest = "SELECT count(*) as total FROM restaurantes";
    $q_card = "SELECT count(*) as total FROM cardapio";
    $q_users = "SELECT count(*) as total FROM usuarios";
} else {
    $titulo_painel = "Painel do Parceiro 🏢";
    $filtro_sql = "WHERE r.id_usuario = $id_logado"; // Filtra só os do dono logado

    $q_rest = "SELECT count(*) as total FROM restaurantes r WHERE r.id_usuario = $id_logado";
    $q_card = "SELECT count(c.id) as total FROM cardapio c JOIN restaurantes r ON c.id_restaurante = r.id WHERE r.id_usuario = $id_logado";
}

$total_restaurantes = $conn->query($q_rest)->fetch_assoc()['total'];
$total_cardapio = $conn->query($q_card)->fetch_assoc()['total'];
if ($nivel_logado == 'master') {
    $total_usuarios = $conn->query($q_users)->fetch_assoc()['total'];
}

// 2. Busca Ranking e Avaliações (Master vê de todos, ADM vê só os dele)
$sql_ranking = "SELECT r.nome, AVG(a.nota) as media, COUNT(a.id) as total_av 
                FROM restaurantes r 
                LEFT JOIN avaliacoes a ON r.id = a.id_restaurante 
                $filtro_sql
                GROUP BY r.id ORDER BY media DESC LIMIT 5";
$ranking = $conn->query($sql_ranking);

$sql_comentarios = "SELECT a.nota, a.comentario, a.data_avaliacao, u.nome as cliente, r.nome as loja
                    FROM avaliacoes a
                    JOIN usuarios u ON a.id_usuario = u.id
                    JOIN restaurantes r ON a.id_restaurante = r.id
                    $filtro_sql
                    ORDER BY a.data_avaliacao DESC LIMIT 5";
$comentarios = $conn->query($sql_comentarios);
?>
<!DOCTYPE html>
<html lang="pt-br">

<head>
    <title><?= $titulo_painel ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        /* MANTIVE TODO O SEU CSS ORIGINAL AQUI */
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

        .container-adm {
            display: flex;
            min-height: 100vh;
        }

        .sidebar {
            width: 240px;
            background: #1e293b;
            color: #fff;
            padding: 20px;
        }

        .sidebar h3 {
            margin-bottom: 20px;
            text-align: center;
        }

        .sidebar nav a {
            display: block;
            padding: 12px;
            margin-bottom: 10px;
            color: #cbd5e1;
            text-decoration: none;
            border-radius: 8px;
            transition: 0.3s;
        }

        .sidebar nav a:hover {
            background: #334155;
            color: #fff;
        }

        .sidebar .sair {
            margin-top: 20px;
            background: #ef4444;
            color: white;
        }

        .conteudo-principal {
            flex: 1;
            padding: 30px;
        }

        .conteudo-principal h1 {
            margin-bottom: 20px;
        }

        .estatisticas {
            display: flex;
            gap: 20px;
            margin-bottom: 30px;
        }

        .card-mini {
            flex: 1;
            background: white;
            padding: 20px;
            border-radius: 12px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.05);
            text-align: center;
            transition: transform 0.2s;
        }

        .card-mini:hover {
            transform: translateY(-5px);
        }

        .card-mini h4 {
            margin-bottom: 10px;
            color: #64748b;
        }

        .card-mini span {
            font-size: 28px;
            font-weight: bold;
            color: #0f172a;
        }

        .grid-acoes {
            display: flex;
            gap: 15px;
            margin-bottom: 30px;
        }

        .btn-acao {
            padding: 15px 20px;
            background: #2563eb;
            color: white;
            text-decoration: none;
            border-radius: 10px;
            transition: 0.3s;
        }

        .btn-acao:hover {
            background: #1d4ed8;
        }

        /* NOVO CSS PARA A TABELA DE RANKING E COMENTÁRIOS */
        .painel-duplo {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }

        .card-lista {
            background: white;
            padding: 20px;
            border-radius: 12px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.05);
        }

        .card-lista h3 {
            color: #1e293b;
            margin-bottom: 15px;
            font-size: 1.1rem;
        }

        .tabela-ranking {
            width: 100%;
            border-collapse: collapse;
        }

        .tabela-ranking th,
        .tabela-ranking td {
            padding: 10px;
            text-align: left;
            border-bottom: 1px solid #eee;
        }

        .comentario-item {
            border-bottom: 1px solid #eee;
            padding-bottom: 10px;
            margin-bottom: 10px;
        }

        @media (max-width: 768px) {
            .container-adm {
                flex-direction: column;
            }

            .sidebar {
                width: 100%;
                text-align: center;
            }

            .estatisticas,
            .grid-acoes,
            .painel-duplo {
                flex-direction: column;
                display: flex;
            }
        }
    </style>
</head>

<body>
    <div class="container-adm">

        <aside class="sidebar">
            <h3><?= $nivel_logado == 'master' ? 'Painel Master' : 'Painel ADM' ?></h3>
            <nav>
                <a href="adm.php"><i class="fas fa-home"></i> Início</a>

                <?php if ($nivel_logado == 'master'): ?>
                    <a href="gerenciar_usuarios.php"><i class="fas fa-users"></i> Usuários</a>
                    <a href="gerenciar_categorias.php"><i class="fas fa-tags"></i> Categorias</a>
                <?php endif; ?>

                <a href="meus_restaurantes.php"><i class="fas fa-store"></i>
                    <?= $nivel_logado == 'master' ? 'Todos os Restaurantes' : 'Meus Restaurantes' ?></a>
                <a href="logout.php" class="sair"><i class="fas fa-sign-out-alt"></i> Sair</a>
            </nav>
        </aside>

        <main class="conteudo-principal">
            <h1>Bem-vindo, <?= explode(' ', $_SESSION['usuario_nome'])[0] ?>! 👋</h1>

            <div class="estatisticas">
                <div class="card-mini">
                    <h4>Restaurantes</h4>
                    <span><?= $total_restaurantes ?></span>
                </div>
                <div class="card-mini">
                    <h4>Itens no Cardápio</h4>
                    <span><?= $total_cardapio ?></span>
                </div>

                <?php if ($nivel_logado == 'master'): ?>
                    <div class="card-mini" style="border-bottom: 4px solid #3498db;">
                        <h4>Total de Usuários</h4>
                        <span><?= $total_usuarios ?></span>
                    </div>
                <?php endif; ?>
            </div>

            <h2>Ações Rápidas</h2>
            <div class="grid-acoes">
                <a href="cadastrar_restaurante.php" class="btn-acao"><i class="fas fa-plus"></i> Novo Restaurante</a>
                <?php if ($nivel_logado == 'master'): ?>
                    <a href="gerenciar_usuarios.php" class="btn-acao" style="background: #e67e22;"><i
                            class="fas fa-users-cog"></i> Gerenciar Usuários</a>
                <?php endif; ?>
            </div>

            <div class="painel-duplo">
                <div class="card-lista">
                    <h3>🏆 <?= $nivel_logado == 'master' ? 'Top 5 da Plataforma' : 'Ranking das Minhas Lojas' ?></h3>
                    <?php if ($ranking && $ranking->num_rows > 0): ?>
                        <table class="tabela-ranking">
                            <tr>
                                <th>Loja</th>
                                <th>Nota</th>
                                <th>Votos</th>
                            </tr>
                            <?php while ($rk = $ranking->fetch_assoc()): ?>
                                <tr>
                                    <td><strong><?= $rk['nome'] ?></strong></td>
                                    <td><i class="fas fa-star" style="color: #f1c40f;"></i>
                                        <?= $rk['media'] ? number_format($rk['media'], 1) : '-' ?></td>
                                    <td><?= $rk['total_av'] ?></td>
                                </tr>
                            <?php endwhile; ?>
                        </table>
                    <?php else: ?>
                        <p style="color: #7f8c8d; font-size: 0.9rem;">Nenhum dado para o ranking ainda.</p>
                    <?php endif; ?>
                </div>

                <div class="card-lista">
                    <h3>💬 Últimas Avaliações</h3>
                    <div style="max-height: 250px; overflow-y: auto; padding-right: 10px;">
                        <?php if ($comentarios && $comentarios->num_rows > 0): ?>
                            <?php while ($com = $comentarios->fetch_assoc()): ?>
                                <div class="comentario-item">
                                    <span
                                        style="font-size: 0.75rem; color: #7f8c8d; text-transform: uppercase;"><?= $com['loja'] ?></span><br>
                                    <strong style="font-size: 0.9rem;"><?= $com['cliente'] ?></strong>:
                                    <span style="color: #f1c40f; font-size: 0.8rem;">
                                        <?php for ($i = 1; $i <= 5; $i++)
                                            echo $i <= $com['nota'] ? '★' : '☆'; ?>
                                    </span>
                                    <p style="font-style: italic; margin-top: 4px; font-size: 0.85rem; color: #555;">
                                        "<?= $com['comentario'] ?>"</p>
                                    <div style="font-size: 0.7rem; color: #aaa; margin-top: 4px;">
                                        <?= date('d/m/Y H:i', strtotime($com['data_avaliacao'])) ?>
                                    </div>
                                </div>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <p style="color: #7f8c8d; font-size: 0.9rem;">Nenhuma avaliação recebida ainda.</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

        </main>
    </div>
</body>

</html>