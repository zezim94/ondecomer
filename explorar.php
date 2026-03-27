<?php
session_start();
include("conexao.php");

// Pega os dados do filtro (se o formulário foi enviado)
$busca_nome = $_GET['busca_nome'] ?? '';
$filtro_cat = $_GET['categoria'] ?? '';
$filtro_preco = $_GET['preco'] ?? '';
$filtro_nota = isset($_GET['nota_minima']) ? floatval($_GET['nota_minima']) : 0;
$filtro_distancia = isset($_GET['distancia_max']) ? floatval($_GET['distancia_max']) : 50; // Padrão 50km
$aberto_agora = isset($_GET['aberto_agora']) ? true : false;

$latUsuario = $_GET['lat'] ?? 0;
$lonUsuario = $_GET['lon'] ?? 0;
$horaAtual = date("H:i");

// 1. Montar a Query SQL Base
$sql = "SELECT r.*, c.nome as categoria_nome, AVG(a.nota) as media_nota, COUNT(a.id) as total_avaliacoes
        FROM restaurantes r
        LEFT JOIN categorias c ON r.id_categoria = c.id
        LEFT JOIN avaliacoes a ON r.id = a.id_restaurante
        WHERE 1=1 "; // 1=1 é um truque para facilitar a adição de 'AND' depois

if (!empty($busca_nome)) {
    $sql .= " AND r.nome LIKE '%" . $conn->real_escape_string($busca_nome) . "%' ";
}
if (!empty($filtro_cat)) {
    $sql .= " AND r.id_categoria = " . intval($filtro_cat) . " ";
}
if (!empty($filtro_preco)) {
    $sql .= " AND r.preco = '" . $conn->real_escape_string($filtro_preco) . "' ";
}
if ($aberto_agora) {
    $sql .= " AND ((r.horario_abre <= r.horario_fecha AND '$horaAtual' BETWEEN r.horario_abre AND r.horario_fecha)
              OR (r.horario_abre > r.horario_fecha AND ('$horaAtual' >= r.horario_abre OR '$horaAtual' <= r.horario_fecha))) ";
}

$sql .= " GROUP BY r.id HAVING media_nota >= $filtro_nota OR media_nota IS NULL";

$result = $conn->query($sql);

// Função de Distância
function calcularDistancia($lat1, $lon1, $lat2, $lon2)
{
    if ($lat1 == 0 || $lon1 == 0)
        return 999;
    $raio_terra = 6371;
    $dLat = deg2rad($lat2 - $lat1);
    $dLon = deg2rad($lon2 - $lon1);
    $a = sin($dLat / 2) * sin($dLat / 2) + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLon / 2) * sin($dLon / 2);
    return $raio_terra * (2 * asin(sqrt($a)));
}

$restaurantes_filtrados = [];
while ($row = $result->fetch_assoc()) {
    $distancia = calcularDistancia($latUsuario, $lonUsuario, $row['latitude'], $row['longitude']);
    $row['distancia_km'] = round($distancia, 1);
    $row['media_formatada'] = $row['media_nota'] ? number_format($row['media_nota'], 1) : 'Novo';

    // Aplica o filtro de distância final no PHP
    if ($row['distancia_km'] <= $filtro_distancia || $latUsuario == 0) {
        $restaurantes_filtrados[] = $row;
    }
}

// Ordenar do mais perto pro mais longe
usort($restaurantes_filtrados, function ($a, $b) {
    return $a['distancia_km'] <=> $b['distancia_km'];
});

// Busca categorias para o `<select>`
$categorias = $conn->query("SELECT * FROM categorias ORDER BY nome ASC");
?>

<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <title>Explorar Restaurantes</title>

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
            background: linear-gradient(180deg, #f8fafc, #eef2f7);
            color: #1e293b;
        }

        /* HEADER */
        .header-resultado {
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: #0f172a;
            color: white;
            padding: 15px 25px;
        }

        .btn-voltar {
            background: #1e293b;
            padding: 8px 12px;
            border-radius: 8px;
            color: white;
            text-decoration: none;
        }

        .btn-voltar:hover {
            background: #334155;
        }

        /* CONTAINER */
        .container-resultados {
            max-width: 1300px;
            margin: 30px auto;
            padding: 0 20px;
        }

        /* CARD FILTRO (GLASS EFFECT) */
        .card-restaurante form,
        .filtro-box {
            backdrop-filter: blur(10px);
            background: rgba(255, 255, 255, 0.7);
            border-radius: 16px;
            padding: 20px;
        }

        /* INPUTS */
        form input,
        form select {
            padding: 10px;
            border-radius: 10px;
            border: 1px solid #e2e8f0;
            outline: none;
            transition: 0.2s;
        }

        form input:focus,
        form select:focus {
            border-color: #2563eb;
        }

        /* GRID FILTROS */
        #form-busca>div:first-of-type {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 10px;
        }

        /* BOTÃO */
        .btn-principal {
            background: linear-gradient(135deg, #2563eb, #1e40af);
            color: white;
            border-radius: 10px;
            padding: 10px 20px;
            border: none;
            cursor: pointer;
        }

        .btn-principal:hover {
            opacity: 0.9;
        }

        /* GRID RESTAURANTES - 4 POR LINHA */
        .grid-restaurantes {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 18px;
        }

        /* RESPONSIVO */
        @media (max-width: 1100px) {
            .grid-restaurantes {
                grid-template-columns: repeat(3, 1fr);
            }
        }

        @media (max-width: 800px) {
            .grid-restaurantes {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        @media (max-width: 500px) {
            .grid-restaurantes {
                grid-template-columns: 1fr;
            }
        }

        /* CARD */
        .card-restaurante {
            background: white;
            border-radius: 16px;
            padding: 16px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.05);
            transition: 0.25s;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }

        .card-restaurante:hover {
            transform: translateY(-6px);
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.08);
        }

        /* INFO */
        .card-info h4 {
            font-size: 16px;
            margin-bottom: 6px;
        }

        .badge-categoria {
            display: inline-block;
            background: #dbeafe;
            color: #1d4ed8;
            padding: 4px 10px;
            border-radius: 999px;
            font-size: 11px;
            margin-bottom: 8px;
        }

        /* TEXTO */
        .distancia,
        .preco {
            font-size: 13px;
            color: #64748b;
            margin-top: 4px;
        }

        /* SCORE */
        .score-info {
            margin-top: 8px;
        }

        .nota-estrelas {
            font-size: 13px;
            color: #f59e0b;
            font-weight: bold;
        }

        /* BOTÃO */
        .btn-secundario {
            margin-top: 12px;
            padding: 10px;
            text-align: center;
            background: #22c55e;
            color: white;
            border-radius: 10px;
            text-decoration: none;
            font-size: 14px;
        }

        .btn-secundario:hover {
            background: #16a34a;
        }

        /* TÍTULO */
        .titulo-secao {
            margin: 20px 0;
            font-weight: 600;
        }
    </style>
</head>

<body class="bg-cinza">

    <header class="header-resultado">
        <a href="index.php" class="btn-voltar"><i class="fas fa-arrow-left"></i> Voltar</a>
        <h2>Explorar Opções</h2>
    </header>

    <main class="container-resultados">

       <div class="filtro-box" style="margin-bottom: 25px;">
            <h3 style="margin-bottom: 15px;"><i class="fas fa-filter"></i> Filtros</h3>
            <form action="explorar.php" method="GET" id="form-busca">

                <input type="hidden" name="lat" id="latUsuario" value="<?= $latUsuario ?>">
                <input type="hidden" name="lon" id="lonUsuario" value="<?= $lonUsuario ?>">

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px; margin-bottom: 15px;">
                    <input type="text" name="busca_nome" placeholder="Nome do local..." value="<?= $busca_nome ?>"
                        style="padding: 10px; border-radius: 8px; border: 1px solid #ccc; grid-column: span 2;">

                    <select name="categoria" style="padding: 10px; border-radius: 8px; border: 1px solid #ccc;">
                        <option value="">Todas as Categorias</option>
                        <?php while ($c = $categorias->fetch_assoc()): ?>
                            <option value="<?= $c['id'] ?>" <?= $filtro_cat == $c['id'] ? 'selected' : '' ?>><?= $c['nome'] ?>
                            </option>
                        <?php endwhile; ?>
                    </select>

                    <select name="preco" style="padding: 10px; border-radius: 8px; border: 1px solid #ccc;">
                        <option value="">Qualquer Preço</option>
                        <option value="barato" <?= $filtro_preco == 'barato' ? 'selected' : '' ?>>Barato ($)</option>
                        <option value="medio" <?= $filtro_preco == 'medio' ? 'selected' : '' ?>>Médio ($$)</option>
                        <option value="alto" <?= $filtro_preco == 'alto' ? 'selected' : '' ?>>Caro ($$$)</option>
                    </select>

                    <select name="nota_minima" style="padding: 10px; border-radius: 8px; border: 1px solid #ccc;">
                        <option value="0">Qualquer Nota</option>
                        <option value="4" <?= $filtro_nota == 4 ? 'selected' : '' ?>>⭐⭐⭐⭐ 4+ Estrelas</option>
                        <option value="4.5" <?= $filtro_nota == 4.5 ? 'selected' : '' ?>>⭐⭐⭐⭐✨ 4.5+ Estrelas</option>
                    </select>

                    <select name="distancia_max" style="padding: 10px; border-radius: 8px; border: 1px solid #ccc;">
                        <option value="50">Qualquer Distância</option>
                        <option value="2" <?= $filtro_distancia == 2 ? 'selected' : '' ?>>Até 2 km</option>
                        <option value="5" <?= $filtro_distancia == 5 ? 'selected' : '' ?>>Até 5 km</option>
                        <option value="10" <?= $filtro_distancia == 10 ? 'selected' : '' ?>>Até 10 km</option>
                    </select>
                </div>

                <div style="display: flex; justify-content: space-between; align-items: center;">
                    <label style="cursor: pointer; font-weight: bold; color: #27ae60;">
                        <input type="checkbox" name="aberto_agora" <?= $aberto_agora ? 'checked' : '' ?>> Somente Abertos
                        Agora
                    </label>
                    <button type="submit" class="btn-principal"
                        style="margin-top: 0; padding: 10px 20px;">Buscar</button>
                </div>
            </form>
        </div>

        <h3 class="titulo-secao"><?= count($restaurantes_filtrados) ?> locais encontrados</h3>
        <div class="grid-restaurantes">
            <?php foreach ($restaurantes_filtrados as $r): ?>
                <div class="card-restaurante">
                    <div class="card-info">
                        <h4><?= $r['nome'] ?></h4>
                        <span class="badge-categoria"><?= $r['categoria_nome'] ?></span>
                        <p class="distancia"><i class="fas fa-map-marker-alt"></i> A <?= $r['distancia_km'] ?> km de você
                        </p>
                        <p class="preco">Custo: <?= ucfirst($r['preco']) ?></p>
                        <div class="score-info">
                            <span class="nota-estrelas"><i class="fas fa-star"></i> <?= $r['media_formatada'] ?> <span
                                    style="color:#94a3b8; font-size:11px;">(<?= $r['total_avaliacoes'] ?>)</span></span>
                        </div>
                    </div>
                    <a href="ver_restaurante.php?id=<?= $r['id'] ?>&lat=<?= $latUsuario ?>&lon=<?= $lonUsuario ?>"
                        class="btn-secundario">Ver Detalhes e Rota</a>
                </div>
            <?php endforeach; ?>
        </div>

    </main>

    <script>
        // Pega a localização automaticamente e atualiza os campos ocultos se for a primeira vez
        if (document.getElementById('latUsuario').value == 0) {
            navigator.geolocation.getCurrentPosition(function (pos) {
                document.getElementById('latUsuario').value = pos.coords.latitude;
                document.getElementById('lonUsuario').value = pos.coords.longitude;
                // Opcional: Submeter o formulário automaticamente a primeira vez que pegar a localização
                // document.getElementById('form-busca').submit();
            });
        }
    </script>
</body>

</html>