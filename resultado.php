<?php
session_start();

date_default_timezone_set('America/Sao_Paulo');

include("conexao.php");

// 1. Pegar dados da URL (Localização e Clima se houver)
$latUsuario = $_GET['lat'] ?? 0;
$lonUsuario = $_GET['lon'] ?? 0;
$clima = $_GET['clima'] ?? 'Clear';

$horaAtual = date("H:i");

// 2. SQL Avançado: Agora inclui a Média de Avaliações (AVG) e o total de pessoas (COUNT)
$sql = "SELECT r.*, c.nome as categoria_nome, 
               AVG(a.nota) as media_nota, 
               COUNT(a.id) as total_avaliacoes
        FROM restaurantes r
        LEFT JOIN categorias c ON r.id_categoria = c.id
        LEFT JOIN avaliacoes a ON r.id = a.id_restaurante
        WHERE (
            (r.horario_abre <= r.horario_fecha AND '$horaAtual' BETWEEN r.horario_abre AND r.horario_fecha)
            OR 
            (r.horario_abre > r.horario_fecha AND ('$horaAtual' >= r.horario_abre OR '$horaAtual' <= r.horario_fecha))
        )
        GROUP BY r.id";

$result = $conn->query($sql);

function calcularDistancia($lat1, $lon1, $lat2, $lon2)
{
    if ($lat1 == 0 || $lon1 == 0)
        return 999;
    $raio_terra = 6371;
    $dLat = deg2rad($lat2 - $lat1);
    $dLon = deg2rad($lon2 - $lon1);
    $a = sin($dLat / 2) * sin($dLat / 2) + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLon / 2) * sin($dLon / 2);
    $c = 2 * asin(sqrt($a));
    return $raio_terra * $c;
}

$recomendados = [];
$outros_abertos = [];

while ($row = $result->fetch_assoc()) {
    $distancia = calcularDistancia($latUsuario, $lonUsuario, $row['latitude'], $row['longitude']);
    $row['distancia_km'] = round($distancia, 1);

    // Formata a nota visualmente (ex: 4.5 ou 'Novo')
    $row['media_formatada'] = $row['media_nota'] ? number_format($row['media_nota'], 1) : 'Novo';

    $pontuacao = 0;
    $categoria = strtolower($row['categoria_nome']);

    // --- 🧠 O "CÉREBRO" DA RECOMENDAÇÃO ---
    if ($horaAtual >= "11:00" && $horaAtual <= "15:00") {
        if (strpos($categoria, 'caseira') !== false || strpos($categoria, 'almoço') !== false) {
            $pontuacao += 10;
        }
    } elseif ($horaAtual >= "15:00" && $horaAtual <= "18:00") {
        if (strpos($categoria, 'açaí') !== false || strpos($categoria, 'café') !== false || strpos($categoria, 'doce') !== false) {
            $pontuacao += 10;
        }
    } elseif ($horaAtual >= "18:00" || $horaAtual <= "04:00") {
        if (strpos($categoria, 'pizza') !== false || strpos($categoria, 'hamburguer') !== false || strpos($categoria, 'japonesa') !== false) {
            $pontuacao += 10;
        }
    }

    if ($clima == 'Rain' || $clima == 'Drizzle') {
        if (strpos($categoria, 'pizza') !== false || strpos($categoria, 'caldo') !== false) {
            $pontuacao += 5;
        }
    }

    if ($row['distancia_km'] <= 2.0) {
        $pontuacao += 5;
    }

    // Lugares bem avaliados (Média acima de 4) ganham +2 pontos de bônus no algoritmo
    if ($row['media_nota'] >= 4) {
        $pontuacao += 2;
    }

    $row['pontuacao'] = $pontuacao;

    if ($pontuacao >= 10) {
        $recomendados[] = $row;
    } else {
        $outros_abertos[] = $row;
    }
}

// Ordenar os Recomendados
usort($recomendados, function ($a, $b) {
    if ($b['pontuacao'] == $a['pontuacao'])
        return $a['distancia_km'] <=> $b['distancia_km'];
    return $b['pontuacao'] <=> $a['pontuacao'];
});

// Ordenar os "Outros Abertos"
usort($outros_abertos, function ($a, $b) {
    return $a['distancia_km'] <=> $b['distancia_km'];
});
?>

<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <title>Suas Recomendações</title>
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

        .header-resultado {
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: #1e293b;
            color: white;
            padding: 15px 20px;
        }

        .header-resultado h2 {
            font-size: 18px;
        }

        .btn-voltar {
            background: #334155;
            color: white;
            padding: 8px 12px;
            border-radius: 8px;
            text-decoration: none;
        }

        .btn-voltar:hover {
            background: #475569;
        }

        .container-resultados {
            max-width: 1100px;
            margin: 30px auto;
            padding: 0 20px;
        }

        .titulo-secao {
            margin-bottom: 15px;
            color: #1e293b;
        }

        .grid-restaurantes {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 20px;
        }

        .card-restaurante {
            background: white;
            padding: 18px;
            border-radius: 14px;
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.05);
            transition: 0.2s;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }

        .card-restaurante:hover {
            transform: translateY(-5px);
        }

        .card-restaurante.destaque {
            border: 2px solid #f1c40f;
            background: linear-gradient(180deg, #fffbe6, #ffffff);
        }

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

        .distancia,
        .preco {
            font-size: 13px;
            color: #64748b;
            margin-top: 4px;
        }

        .score-info {
            display: flex;
            gap: 10px;
            margin-top: 8px;
            font-size: 13px;
            font-weight: bold;
        }

        .nota-estrelas {
            color: #f59e0b;
        }

        .match-score {
            color: #10b981;
        }

        /* Verde para a pontuação do algoritmo */

        .btn-principal {
            margin-top: 15px;
            display: block;
            text-align: center;
            padding: 10px;
            background: linear-gradient(135deg, #22c55e, #16a34a);
            color: white;
            border-radius: 10px;
            text-decoration: none;
            transition: 0.2s;
        }

        .btn-principal:hover {
            opacity: 0.9;
        }

        .btn-secundario {
            margin-top: 15px;
            display: block;
            text-align: center;
            padding: 10px;
            background: #2563eb;
            color: white;
            border-radius: 10px;
            text-decoration: none;
        }

        .btn-secundario:hover {
            background: #1d4ed8;
        }

        .alerta {
            background: #fff3cd;
            color: #856404;
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 20px;
        }

        hr {
            border: none;
            border-top: 1px solid #e5e7eb;
        }

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
        <a href="index.php" class="btn-voltar"><i class="fas fa-arrow-left"></i> Voltar</a>
        <h2>Opções para Agora (<?= $horaAtual ?>)</h2>
    </header>

    <main class="container-resultados">

        <?php if (count($recomendados) > 0): ?>
            <h3 class="titulo-secao"><i class="fas fa-star" style="color: #f1c40f;"></i> O Melhor para o seu momento</h3>
            <div class="grid-restaurantes">
                <?php foreach ($recomendados as $r): ?>
                    <div class="card-restaurante destaque">
                        <div class="card-info">
                            <h4><?= $r['nome'] ?></h4>
                            <span class="badge-categoria"><?= $r['categoria_nome'] ?></span>
                            <p class="distancia"><i class="fas fa-map-marker-alt"></i> A <?= $r['distancia_km'] ?> km de você
                            </p>
                            <p class="preco">Custo: <?= ucfirst($r['preco']) ?></p>

                            <div class="score-info">
                                <span class="nota-estrelas"><i class="fas fa-star"></i> <?= $r['media_formatada'] ?> <span
                                        style="color:#94a3b8; font-weight:normal; font-size:11px;">(<?= $r['total_avaliacoes'] ?>)</span></span>
                                <span class="match-score"><i class="fas fa-fire"></i> Match: <?= $r['pontuacao'] ?> pts</span>
                            </div>
                        </div>
                        <a href="ver_restaurante.php?id=<?= $r['id'] ?>" class="btn-principal">Ver Cardápio</a>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="alerta">Não encontramos nenhuma "combinação perfeita" pro seu horário, mas veja as opções abertas
                abaixo!</div>
        <?php endif; ?>

        <hr style="margin: 30px 0; border: 1px solid #ddd;">

        <h3 class="titulo-secao">Outros locais Abertos Agora</h3>
        <div class="grid-restaurantes">
            <?php foreach ($outros_abertos as $r): ?>
                <div class="card-restaurante">
                    <div class="card-info">
                        <h4><?= $r['nome'] ?></h4>
                        <span class="badge-categoria"><?= $r['categoria_nome'] ?></span>
                        <p class="distancia"><i class="fas fa-map-marker-alt"></i> A <?= $r['distancia_km'] ?> km de você
                        </p>

                        <div class="score-info">
                            <span class="nota-estrelas"><i class="fas fa-star"></i> <?= $r['media_formatada'] ?> <span
                                    style="color:#94a3b8; font-weight:normal; font-size:11px;">(<?= $r['total_avaliacoes'] ?>)</span></span>
                        </div>
                    </div>
                    <a href="ver_restaurante.php?id=<?= $r['id'] ?>" class="btn-secundario">Ver Cardápio</a>
                </div>
            <?php endforeach; ?>

            <?php if (count($outros_abertos) == 0 && count($recomendados) == 0): ?>
                <p style="text-align: center;">Poxa, parece que está tudo fechado por perto agora. 😴</p>
            <?php endif; ?>
        </div>

    </main>

</body>

</html>