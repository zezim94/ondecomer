<?php
session_start();
include("conexao.php");

// 1. PRIMEIRO: Pega o ID do restaurante que veio da URL
$id_restaurante = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($id_restaurante == 0) {
    header("Location: index.php");
    exit();
}

// 2. SEGUNDO: Verifica se é favorito
$is_favorito = false;
if (isset($_SESSION['usuario_id'])) {
    $id_user = $_SESSION['usuario_id'];
    $sql_fav = "SELECT id FROM favoritos WHERE id_usuario = $id_user AND id_restaurante = $id_restaurante";
    $check_fav = $conn->query($sql_fav);
    if ($check_fav && $check_fav->num_rows > 0) {
        $is_favorito = true;
    }
}

// --- NOVA LÓGICA: SALVAR AVALIAÇÃO ---
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['enviar_avaliacao']) && isset($_SESSION['usuario_id'])) {
    $nota = intval($_POST['nota']);
    $comentario = $conn->real_escape_string($_POST['comentario']);
    $id_user_av = $_SESSION['usuario_id'];

    // Verifica se já avaliou antes
    $check_av = $conn->query("SELECT id FROM avaliacoes WHERE id_usuario = $id_user_av AND id_restaurante = $id_restaurante");

    if ($check_av && $check_av->num_rows > 0) {
        // Atualiza a avaliação existente
        $conn->query("UPDATE avaliacoes SET nota = $nota, comentario = '$comentario', data_avaliacao = NOW() 
                      WHERE id_usuario = $id_user_av AND id_restaurante = $id_restaurante");
    } else {
        // Cria nova avaliação
        $conn->query("INSERT INTO avaliacoes (id_usuario, id_restaurante, nota, comentario) 
                      VALUES ($id_user_av, $id_restaurante, $nota, '$comentario')");
    }

    // Recarrega a página para atualizar a média
    header("Location: ver_restaurante.php?id=$id_restaurante");
    exit();
}

// --- NOVA LÓGICA: BUSCAR MÉDIA DE NOTAS ---
$sql_media = "SELECT AVG(nota) as media_nota, COUNT(id) as total_avaliacoes FROM avaliacoes WHERE id_restaurante = $id_restaurante";
$res_media = $conn->query($sql_media)->fetch_assoc();
$media_geral = $res_media['media_nota'] ? number_format($res_media['media_nota'], 1) : 'Sem notas';
$total_avaliacoes = $res_media['total_avaliacoes'];

// --- NOVA LÓGICA: BUSCAR LISTA DE AVALIAÇÕES ---
$sql_avaliacoes = "SELECT a.*, u.nome FROM avaliacoes a JOIN usuarios u ON a.id_usuario = u.id 
                   WHERE a.id_restaurante = $id_restaurante ORDER BY a.data_avaliacao DESC";
$lista_avaliacoes = $conn->query($sql_avaliacoes);


// 3. TERCEIRO: Busca os dados do restaurante e da categoria
$sql_rest = "SELECT r.*, c.nome as categoria_nome 
             FROM restaurantes r 
             LEFT JOIN categorias c ON r.id_categoria = c.id 
             WHERE r.id = $id_restaurante";
$resultado_rest = $conn->query($sql_rest);

if (!$resultado_rest || $resultado_rest->num_rows == 0) {
    echo "Restaurante não encontrado!";
    exit();
}

$restaurante = $resultado_rest->fetch_assoc();

// 4. QUARTO: Busca o cardápio deste restaurante
$sql_cardapio = "SELECT * FROM cardapio WHERE id_restaurante = $id_restaurante";
$itens = $conn->query($sql_cardapio);
?>

<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $restaurante['nome'] ?> - Onde Comer Agora?</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <link rel="stylesheet" href="https://unpkg.com/leaflet-routing-machine@latest/dist/leaflet-routing-machine.css" />
</head>

<body class="bg-cinza">

    <header class="header-resultado">
        <a href="javascript:history.back()" class="btn-voltar"><i class="fas fa-arrow-left"></i> Voltar</a>
    </header>

    <main class="container-resultados">

        <div class="perfil-restaurante">
            <div class="capa-fake"></div>
            <div class="info-principal">
                <h2><?= $restaurante['nome'] ?></h2>
                <span class="badge-categoria"><?= $restaurante['categoria_nome'] ?></span>

                <div class="detalhes-loja">
                    <p><i class="far fa-calendar-alt"></i> Dias: <?= $restaurante['dias_funcionamento'] ?></p>
                    <p><i class="far fa-clock"></i> Horário: <?= substr($restaurante['horario_abre'], 0, 5) ?> às
                        <?= substr($restaurante['horario_fecha'], 0, 5) ?>
                    </p>
                    <p><i class="fas fa-dollar-sign"></i> Faixa de Preço: <?= ucfirst($restaurante['preco']) ?></p>
                    <p><i class="fas fa-star" style="color: #f1c40f;"></i> Nota: <strong><?= $media_geral ?></strong>
                        (<?= $total_avaliacoes ?> avaliações)</p>

                    <div style="display: flex; gap: 10px; margin-top: 15px;">
                        <a href="https://www.google.com/maps/dir/?api=1&destination=<?= $restaurante['latitude'] ?>,<?= $restaurante['longitude'] ?>"
                            target="_blank" class="btn-rota" style="flex: 1;">
                            <i class="fas fa-map-marked-alt"></i> Rota
                        </a>

                        <?php if (isset($_SESSION['usuario_id'])): ?>
                            <button onclick="favoritar(<?= $id_restaurante ?>)" id="btn-fav" class="btn-rota"
                                style="background: <?= $is_favorito ? '#e74c3c' : '#bdc3c7' ?>; width: 60px;">
                                <i class="fas fa-heart"></i>
                            </button>
                        <?php endif; ?>
                    </div>
                    <h3 class="titulo-secao" style="margin-top: 30px;"><i class="fas fa-map-marked-alt"></i> Como Chegar
                    </h3>

                    <div id="mapa-rota"
                        style="height: 450px; width: 100%; border-radius: 12px; z-index: 1; border: 2px solid #eee;">
                    </div>

                    <div id="info-rota"
                        style="margin-top: 10px; font-weight: bold; color: #27ae60; background: #e8f8f5; padding: 10px; border-radius: 8px; text-align: center; display: none;">
                        Calculando rota...
                    </div>
                </div>
            </div>
        </div>

        <h3 class="titulo-secao" style="margin-top: 30px;"><i class="fas fa-book-open"></i> Cardápio</h3>

        <div class="lista-cardapio-cliente">
            <?php if ($itens && $itens->num_rows > 0): ?>

                <?php while ($item = $itens->fetch_assoc()): ?>
                    <div class="item-cardapio-cliente">
                        <div class="item-textos">
                            <h4><?= $item['item_nome'] ?></h4>
                            <p class="descricao"><?= $item['descricao'] ?></p>
                            <span class="preco-tag">R$ <?= number_format($item['valor'], 2, ',', '.') ?></span>
                        </div>
                    </div>
                <?php endwhile; ?>

            <?php else: ?>
                <div class="alerta" style="text-align: center;">
                    Este restaurante ainda não cadastrou o cardápio. 😕
                </div>
            <?php endif; ?>
        </div>

        <h3 class="titulo-secao" style="margin-top: 40px;"><i class="fas fa-star"></i> Avaliações</h3>

        <?php if (isset($_SESSION['usuario_id']) && $_SESSION['usuario_nivel'] == 'cliente'): ?>
            <div class="form-avaliacao">
                <h4>Deixe sua opinião</h4>
                <form method="POST" action="">
                    <div class="estrelas-input">
                        <label>Nota:</label>
                        <select name="nota" required>
                            <option value="5">⭐⭐⭐⭐⭐ Excelente</option>
                            <option value="4">⭐⭐⭐⭐ Muito Bom</option>
                            <option value="3">⭐⭐⭐ Bom</option>
                            <option value="2">⭐⭐ Regular</option>
                            <option value="1">⭐ Ruim</option>
                        </select>
                    </div>
                    <textarea name="comentario" placeholder="Como foi sua experiência?" required></textarea>
                    <button type="submit" name="enviar_avaliacao" class="btn-principal">Enviar Avaliação</button>
                </form>
            </div>
        <?php elseif (!isset($_SESSION['usuario_id'])): ?>
            <div class="alerta" style="margin-bottom: 20px;">
                Faça <a href="login.php" style="color: #e67e22; font-weight:bold;">login</a> para deixar sua avaliação.
            </div>
        <?php endif; ?>

        <div class="lista-avaliacoes">
            <?php if ($lista_avaliacoes && $lista_avaliacoes->num_rows > 0): ?>
                <?php while ($av = $lista_avaliacoes->fetch_assoc()): ?>
                    <div class="card-comentario">
                        <div class="comentario-topo">
                            <strong><i class="fas fa-user-circle"></i> <?= explode(' ', $av['nome'])[0] ?></strong>
                            <span class="nota-estrelas">
                                <?php for ($i = 1; $i <= 5; $i++) {
                                    echo $i <= $av['nota'] ? '<i class="fas fa-star" style="color: #f1c40f;"></i>' : '<i class="far fa-star" style="color: #ccc;"></i>';
                                } ?>
                            </span>
                        </div>
                        <p class="texto-comentario">"<?= $av['comentario'] ?>"</p>
                        <span class="data-comentario"><?= date('d/m/Y', strtotime($av['data_avaliacao'])) ?></span>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <p style="text-align: center; color: #7f8c8d; margin-bottom: 30px;">Seja o primeiro a avaliar este local!
                </p>
            <?php endif; ?>
        </div>

    </main>

    <script>
        function favoritar(id_restaurante) {
            fetch('ajax_favoritar.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'id_restaurante=' + id_restaurante
            })
                .then(res => res.json())
                .then(data => {
                    const btn = document.getElementById('btn-fav');
                    if (data.status == 'adicionado') {
                        btn.style.background = '#e74c3c';
                    } else {
                        btn.style.background = '#bdc3c7';
                    }
                });
        }
    </script>

    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

    <script>
        // Coordenadas do Restaurante (garantindo que são floats)
        const latRestaurante = parseFloat(<?= json_encode($restaurante['latitude']) ?>);
        const lonRestaurante = parseFloat(<?= json_encode($restaurante['longitude']) ?>);

        // Inicializa o mapa focado no restaurante
        var map = L.map('mapa-rota').setView([latRestaurante, lonRestaurante], 14);

        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '© OpenStreetMap',
            maxZoom: 18
        }).addTo(map);

        // Ícone vermelho para o destino
        var iconeDestino = L.icon({
            iconUrl: 'https://raw.githubusercontent.com/pointhi/leaflet-color-markers/master/img/marker-icon-2x-red.png',
            shadowUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/0.7.7/images/marker-shadow.png',
            iconSize: [25, 41], iconAnchor: [12, 41], popupAnchor: [1, -34], shadowSize: [41, 41]
        });

        // Adiciona o marcador do destino (restaurante)
        L.marker([latRestaurante, lonRestaurante], { icon: iconeDestino })
            .bindPopup('<b>Destino:</b><br><?= addslashes($restaurante['nome']) ?>').addTo(map);

        // Função que traça a rota
        function tracarRota(latUser, lonUser) {
            latUser = parseFloat(latUser);
            lonUser = parseFloat(lonUser);

            // Marcador da origem
            L.marker([latUser, lonUser]).bindPopup('<b>Sua Localização</b>').addTo(map);

            // Distância em linha reta (fallback)
            var distanciaDireta = (map.distance([latUser, lonUser], [latRestaurante, lonRestaurante]) / 1000).toFixed(1);

            // Monta a URL da API do OSRM para traçar a rota real
            var url = `https://router.project-osrm.org/route/v1/driving/${lonUser},${latUser};${lonRestaurante},${latRestaurante}?overview=full&geometries=geojson`;

            fetch(url)
                .then(res => {
                    if (!res.ok) throw new Error("Falha ao contatar a API de rotas.");
                    return res.json();
                })
                .then(data => {
                    if (!data.routes || data.routes.length === 0) throw new Error("Nenhuma rota encontrada.");

                    var rota = data.routes[0];
                    var distanciaKM = (rota.distance / 1000).toFixed(1);
                    var tempoMin = Math.ceil(rota.duration / 60);

                    // Exibe as informações
                    var infoBox = document.getElementById('info-rota');
                    infoBox.style.display = 'block';
                    infoBox.style.background = '#e8f8f5';
                    infoBox.style.color = '#27ae60';
                    infoBox.innerHTML = `🚗 Rota: ${distanciaKM} km | ⏳ Tempo estimado: ${tempoMin} min`;

                    // Desenha a linha da rota
                    var pontos = rota.geometry.coordinates.map(c => [c[1], c[0]]); // OSRM retorna Lng,Lat; Leaflet usa Lat,Lng

                    L.polyline(pontos, {
                        color: '#3498db',
                        weight: 6
                    }).addTo(map);

                    // Ajusta o zoom para mostrar a rota toda
                    map.fitBounds(pontos, { padding: [50, 50] });
                })
                .catch(err => {
                    console.warn("Usando fallback de linha reta:", err);

                    var infoBox = document.getElementById('info-rota');
                    infoBox.style.display = 'block';
                    infoBox.style.background = '#fcf3cf';
                    infoBox.style.color = '#d35400';
                    infoBox.innerHTML = `📍 ~${distanciaDireta} km (Traçado em linha reta)`;

                    var pontosFallback = [
                        [latUser, lonUser],
                        [latRestaurante, lonRestaurante]
                    ];

                    L.polyline(pontosFallback, {
                        color: '#e74c3c',
                        dashArray: '10,10',
                        weight: 4
                    }).addTo(map);

                    map.fitBounds(pontosFallback, { padding: [50, 50] });
                });
        }

        // Execução principal da geolocalização
        document.addEventListener("DOMContentLoaded", function () {
            const urlParams = new URLSearchParams(window.location.search);
            const latURL = parseFloat(urlParams.get('lat'));
            const lonURL = parseFloat(urlParams.get('lon'));

            if (!isNaN(latURL) && !isNaN(lonURL) && latURL !== 0 && lonURL !== 0) {
                document.getElementById('info-rota').style.display = 'block';
                tracarRota(latURL, lonURL);
            } else {
                if (navigator.geolocation) {
                    document.getElementById('info-rota').style.display = 'block';
                    document.getElementById('info-rota').innerHTML = "Buscando sua localização...";

                    navigator.geolocation.getCurrentPosition(
                        function (pos) {
                            tracarRota(pos.coords.latitude, pos.coords.longitude);
                        },
                        function (erro) {
                            var infoBox = document.getElementById('info-rota');
                            infoBox.style.background = "#f9ebea";
                            infoBox.style.color = "#c0392b";
                            infoBox.innerHTML = "📍 Rota indisponível. Ative a localização no seu dispositivo.";
                            map.setView([latRestaurante, lonRestaurante], 15);
                        },
                        { enableHighAccuracy: true, timeout: 5000 }
                    );
                } else {
                    document.getElementById('info-rota').innerHTML = "Geolocalização não suportada pelo navegador.";
                }
            }
        });
    </script>
</body>

</html>