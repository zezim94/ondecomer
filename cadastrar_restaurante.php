<?php
session_start();
include("conexao.php");

// Busca categorias para o select inicial
$categorias = $conn->query("SELECT * FROM categorias ORDER BY nome ASC");

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['nome'])) {
    $nome = $conn->real_escape_string($_POST['nome']);
    $id_cat = $_POST['id_categoria'];
    $preco = $_POST['preco'];
    $lat = $_POST['latitude'];
    $lon = $_POST['longitude'];
    $abre = $_POST['horario_abre'];
    $fecha = $_POST['horario_fecha'];
    $dias_array = $_POST['dias'] ?? [];
    $dias = implode(", ", $dias_array);
    $id_dono = $_SESSION['usuario_id'];

    $sql = "INSERT INTO restaurantes (nome, id_usuario, id_categoria, preco, latitude, longitude, horario_abre, horario_fecha, dias_funcionamento) 
        VALUES ('$nome', '$id_dono', '$id_cat', '$preco', '$lat', '$lon', '$abre', '$fecha', '$dias')";
    if ($conn->query($sql)) {
        header("Location: adm.php?msg=sucesso");
    }
}
?>

<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <title>Novo Restaurante - Mapa</title>
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
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

        /* CONTAINER */
        .container-larga {
            max-width: 800px;
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

        /* FORM */
        .form-auth input,
        .form-auth select {
            width: 100%;
            padding: 12px;
            margin-top: 8px;
            margin-bottom: 15px;
            border: 1px solid #ddd;
            border-radius: 10px;
            outline: none;
            transition: 0.2s;
        }

        .form-auth input:focus,
        .form-auth select:focus {
            border-color: #2563eb;
        }

        /* ROWS */
        .row {
            display: flex;
            gap: 15px;
        }

        .row-cat {
            display: flex;
            gap: 10px;
            align-items: flex-end;
        }

        .campo {
            flex: 1;
        }

        /* BOTÃO + */
        .btn-add-modal {
            background: #2563eb;
            color: white;
            border: none;
            padding: 12px;
            border-radius: 10px;
            cursor: pointer;
            height: 45px;
            transition: 0.2s;
        }

        .btn-add-modal:hover {
            background: #1d4ed8;
        }

        /* MAPA */
        #map {
            height: 300px;
            width: 100%;
            border-radius: 12px;
            margin-bottom: 15px;
            border: 2px solid #e5e7eb;
        }

        /* TEXTO MAPA */
        .instrucao-mapa {
            margin: 10px 0;
            color: #64748b;
            font-size: 14px;
        }

        /* BOTÃO PRINCIPAL */
        .btn-grande {
            width: 100%;
            padding: 15px;
            background: linear-gradient(135deg, #2563eb, #1e40af);
            color: white;
            border: none;
            border-radius: 12px;
            font-size: 16px;
            cursor: pointer;
            transition: 0.3s;
        }

        .btn-grande:hover {
            transform: translateY(-2px);
            opacity: 0.95;
        }

        /* MODAL */
        .modal {
            display: none;
            position: fixed;
            z-index: 2000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background: rgba(15, 23, 42, 0.6);
        }

        .modal-content {
            background: white;
            margin: 10% auto;
            padding: 25px;
            border-radius: 14px;
            width: 320px;
            text-align: center;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            animation: fadeIn 0.3s ease;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .close {
            float: right;
            cursor: pointer;
            font-size: 22px;
            color: #999;
        }

        .close:hover {
            color: #000;
        }

        /* BOTÃO MODAL */
        .btn-principal {
            background: #22c55e;
            color: white;
            border: none;
            padding: 12px;
            border-radius: 10px;
            cursor: pointer;
            transition: 0.2s;
        }

        .btn-principal:hover {
            background: #16a34a;
        }

        /* RESPONSIVO */
        @media (max-width: 600px) {
            .row {
                flex-direction: column;
            }

            .container-larga {
                margin: 20px;
                padding: 20px;
            }
        }
    </style>
</head>

<body>

    <div class="container-larga">
        <h2>📍 Cadastrar Restaurante</h2>
        <form action="" method="POST" class="form-auth">
            <input type="text" name="nome" placeholder="Nome do Restaurante" required>

            <div class="row-cat">
                <div style="flex: 1;">
                    <label>Categoria:</label>
                    <select name="id_categoria" id="select-categoria" required>
                        <option value="">Selecione...</option>
                        <?php while ($c = $categorias->fetch_assoc()): ?>
                            <option value="<?= $c['id'] ?>"><?= $c['nome'] ?></option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <button type="button" class="btn-add-modal" onclick="abrirModal()" title="Nova Categoria">
                    <i class="fas fa-plus"></i>
                </button>
            </div>

            <div class="row">
                <select name="preco">
                    <option value="barato">Barato ($)</option>
                    <option value="medio">Médio ($$)</option>
                    <option value="alto">Caro ($$$)</option>
                </select>
            </div>

            <p class="instrucao-mapa">Clique no mapa para marcar a localização:</p>
            <div id="map"></div>

            <div class="row">
                <input type="text" name="latitude" id="lat" placeholder="Latitude" readonly required>
                <input type="text" name="longitude" id="lon" placeholder="Longitude" readonly required>
            </div>

            <div class="row">
                <div class="campo">
                    <label>Abre:</label>
                    <input type="time" name="horario_abre" required>
                </div>
                <div class="campo">
                    <label>Fecha:</label>
                    <input type="time" name="horario_fecha" required>
                </div>
            </div>
            <div class="campo">
                <label>Dias de Funcionamento:</label>
                <div style="display: flex; gap: 10px; flex-wrap: wrap; margin-top: 5px;">
                    <label><input type="checkbox" name="dias[]" value="Seg" checked> Seg</label>
                    <label><input type="checkbox" name="dias[]" value="Ter" checked> Ter</label>
                    <label><input type="checkbox" name="dias[]" value="Qua" checked> Qua</label>
                    <label><input type="checkbox" name="dias[]" value="Qui" checked> Qui</label>
                    <label><input type="checkbox" name="dias[]" value="Sex" checked> Sex</label>
                    <label><input type="checkbox" name="dias[]" value="Sab" checked> Sáb</label>
                    <label><input type="checkbox" name="dias[]" value="Dom" checked> Dom</label>
                </div>
            </div>

            <button type="submit" class="btn-grande">Salvar Restaurante</button>
        </form>
    </div>

    <div id="modalCategoria" class="modal">
        <div class="modal-content">
            <span class="close" onclick="fecharModal()">&times;</span>
            <h3>Nova Categoria</h3>
            <input type="text" id="nova-cat-nome" placeholder="Ex: Hamburgueria" class="campo"
                style="width: 90%; margin-top: 10px;">
            <button type="button" onclick="salvarCategoria()" class="btn-principal"
                style="width: 100%; margin-top: 10px;">Salvar</button>
        </div>
    </div>

    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script>
        // --- Lógica do Mapa ---
        var map = L.map('map').setView([-23.9608, -46.3339], 13);
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png').addTo(map);
        var marker;

        map.on('click', function (e) {
            var lat = e.latlng.lat.toFixed(6);
            var lon = e.latlng.lng.toFixed(6);
            if (marker) marker.setLatLng(e.latlng);
            else marker = L.marker(e.latlng).addTo(map);
            document.getElementById('lat').value = lat;
            document.getElementById('lon').value = lon;
        });

        // --- Lógica do Modal ---
        function abrirModal() { document.getElementById('modalCategoria').style.display = 'block'; }
        function fecharModal() { document.getElementById('modalCategoria').style.display = 'none'; }

        function salvarCategoria() {
            const nome = document.getElementById('nova-cat-nome').value;
            if (!nome) return alert("Digite o nome da categoria");

            // AJAX para salvar sem recarregar
            fetch('ajax_salvar_categoria.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'nome=' + encodeURIComponent(nome)
            })
                .then(res => res.json())
                .then(data => {
                    if (data.sucesso) {
                        // Adiciona a nova opção no select e seleciona ela
                        const select = document.getElementById('select-categoria');
                        const option = new Option(nome, data.id);
                        select.add(option);
                        select.value = data.id;
                        fecharModal();
                        document.getElementById('nova-cat-nome').value = '';
                    } else {
                        alert("Erro ao salvar categoria");
                    }
                });
        }
    </script>
</body>

</html>