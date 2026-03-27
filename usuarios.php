<?php
session_start();
if (!isset($_SESSION['usuario_id']) || $_SESSION['usuario_nivel'] !== 'cliente') {
    header("Location: login.php");
    exit();
}
include("conexao.php");

$id_user = $_SESSION['usuario_id'];

// Busca os restaurantes que o usuário favoritou
$sql_fav = "SELECT r.*, c.nome as categoria_nome 
            FROM favoritos f
            JOIN restaurantes r ON f.id_restaurante = r.id
            LEFT JOIN categorias c ON r.id_categoria = c.id
            WHERE f.id_usuario = $id_user";
$favoritos = $conn->query($sql_fav);
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <title>Meus Favoritos</title>
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
    color: #333;
}

/* HEADER */
.header-resultado {
    display: flex;
    align-items: center;
    justify-content: space-between;
    background: #1e293b;
    color: white;
    padding: 15px 20px;
}

.header-resultado h2 {
    font-size: 18px;
}

/* BOTÃO VOLTAR */
.btn-voltar {
    color: white;
    text-decoration: none;
    background: #334155;
    padding: 8px 12px;
    border-radius: 8px;
    transition: 0.2s;
}

.btn-voltar:hover {
    background: #475569;
}

/* CONTAINER */
.container-resultados {
    max-width: 1000px;
    margin: 30px auto;
    padding: 0 20px;
}

/* TEXTO */
.subtitulo {
    color: #64748b;
    margin-top: 5px;
}

/* GRID */
.grid-restaurantes {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
    gap: 20px;
}

/* CARD */
.card-restaurante {
    background: white;
    padding: 18px;
    border-radius: 14px;
    box-shadow: 0 6px 20px rgba(0,0,0,0.05);
    display: flex;
    flex-direction: column;
    justify-content: space-between;
    transition: 0.2s;
    position: relative;
}

.card-restaurante:hover {
    transform: translateY(-4px);
}

/* BORDA FAVORITO */
.card-restaurante {
    border-left: 5px solid #ef4444;
}

/* INFO */
.card-info h4 {
    margin-bottom: 6px;
    color: #0f172a;
}

.badge-categoria {
    display: inline-block;
    background: #dbeafe;
    color: #1d4ed8;
    padding: 4px 10px;
    border-radius: 999px;
    font-size: 12px;
    margin-bottom: 8px;
}

/* ICON TEXT */
.distancia {
    font-size: 13px;
    color: #64748b;
    margin-top: 4px;
}

.distancia i {
    margin-right: 5px;
}

/* BOTÃO */
.btn-secundario {
    margin-top: 15px;
    display: block;
    text-align: center;
    padding: 10px;
    background: #2563eb;
    color: white;
    border-radius: 10px;
    text-decoration: none;
    transition: 0.2s;
}

.btn-secundario:hover {
    background: #1d4ed8;
}

/* ALERTA */
.alerta {
    background: #fff3cd;
    color: #856404;
    padding: 15px;
    border-radius: 10px;
    text-align: center;
}

/* BOTÃO GRANDE */
.btn-grande {
    background: linear-gradient(135deg, #2563eb, #1e40af);
    color: white;
    padding: 14px;
    border-radius: 12px;
    text-decoration: none;
}

/* LOGOUT */
.logout {
    display: inline-block;
    margin-top: 20px;
    color: #ef4444;
    font-weight: bold;
    text-decoration: none;
}

.logout:hover {
    text-decoration: underline;
}

/* RESPONSIVO */
@media (max-width: 600px) {
    .header-resultado {
        flex-direction: column;
        gap: 10px;
    }
}
</style>
</head>
<body class="bg-cinza">
    
    <header class="header-resultado">
        <a href="index.php" class="btn-voltar"><i class="fas fa-arrow-left"></i> Início</a>
        <h2>Meu Perfil 👤</h2>
    </header>

    <div class="container-resultados">
        <h3>Olá, <?= explode(' ', $_SESSION['usuario_nome'])[0] ?>!</h3>
        <p class="subtitulo">Aqui estão seus locais favoritos:</p>

        <div class="grid-restaurantes" style="margin-top: 20px;">
            <?php if($favoritos->num_rows > 0): ?>
                
                <?php while($r = $favoritos->fetch_assoc()): ?>
                    <div class="card-restaurante" style="border-left: 5px solid #e74c3c;">
                        <div class="card-info">
                            <h4><?= $r['nome'] ?></h4>
                            <span class="badge-categoria"><?= $r['categoria_nome'] ?></span>
                            <p class="distancia"><i class="far fa-calendar-alt"></i> <?= $r['dias_funcionamento'] ?></p>
                            <p class="distancia"><i class="far fa-clock"></i> <?= substr($r['horario_abre'], 0, 5) ?> às <?= substr($r['horario_fecha'], 0, 5) ?></p>
                        </div>
                        <a href="ver_restaurante.php?id=<?= $r['id'] ?>" class="btn-secundario">Ver Local</a>
                    </div>
                <?php endwhile; ?>

            <?php else: ?>
                <div class="alerta">Você ainda não favoritou nenhum restaurante. Que tal descobrir um agora?</div>
                <br>
                <a href="index.php" class="btn-grande" style="text-align: center; display: block;">Buscar Opções</a>
            <?php endif; ?>
        </div>
        
        <br><br>
<a href="logout.php" class="logout">
    <i class="fas fa-sign-out-alt"></i> Sair da Conta
</a>    </div>
</body>
</html>