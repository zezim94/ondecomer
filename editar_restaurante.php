<?php
session_start();
include("conexao.php");

if (!isset($_GET['id'])) {
    header("Location: meus_restaurantes.php");
    exit();
}

$id_rest = intval($_GET['id']);

// Busca os dados do restaurante
$sql_rest = "SELECT * FROM restaurantes WHERE id = $id_rest LIMIT 1";
$res_rest = $conn->query($sql_rest);
if ($res_rest->num_rows == 0) {
    header("Location: meus_restaurantes.php");
    exit();
}
$restaurante = $res_rest->fetch_assoc();

// Busca categorias para o select
$categorias = $conn->query("SELECT * FROM categorias ORDER BY nome ASC");

// Atualização
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

    $sql = "UPDATE restaurantes SET 
        nome='$nome', 
        id_categoria='$id_cat', 
        preco='$preco', 
        latitude='$lat', 
        longitude='$lon', 
        horario_abre='$abre', 
        horario_fecha='$fecha', 
        dias_funcionamento='$dias' 
        WHERE id=$id_rest";

    if ($conn->query($sql)) {
        header("Location: meus_restaurantes.php?msg=atualizado");
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <title>Editar Restaurante - Mapa</title>
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        /* MESMO ESTILO DA PÁGINA DE CADASTRO */
        *{margin:0;padding:0;box-sizing:border-box;font-family:'Segoe UI',sans-serif;}
        body{background:#f4f6f9;color:#333;}
        .container-larga{max-width:800px;margin:40px auto;background:white;padding:30px;border-radius:16px;box-shadow:0 8px 25px rgba(0,0,0,0.05);}
        h2{margin-bottom:20px;color:#1e293b;}
        .form-auth input,.form-auth select{width:100%;padding:12px;margin-top:8px;margin-bottom:15px;border:1px solid #ddd;border-radius:10px;outline:none;transition:0.2s;}
        .form-auth input:focus,.form-auth select:focus{border-color:#2563eb;}
        .row{display:flex;gap:15px;}
        .row-cat{display:flex;gap:10px;align-items:flex-end;}
        .campo{flex:1;}
        .btn-add-modal{background:#2563eb;color:white;border:none;padding:12px;border-radius:10px;cursor:pointer;height:45px;transition:0.2s;}
        .btn-add-modal:hover{background:#1d4ed8;}
        #map{height:300px;width:100%;border-radius:12px;margin-bottom:15px;border:2px solid #e5e7eb;}
        .instrucao-mapa{margin:10px 0;color:#64748b;font-size:14px;}
        .btn-grande{width:100%;padding:15px;background:linear-gradient(135deg,#2563eb,#1e40af);color:white;border:none;border-radius:12px;font-size:16px;cursor:pointer;transition:0.3s;}
        .btn-grande:hover{transform:translateY(-2px);opacity:0.95;}
        .modal{display:none;position:fixed;z-index:2000;left:0;top:0;width:100%;height:100%;background:rgba(15,23,42,0.6);}
        .modal-content{background:white;margin:10% auto;padding:25px;border-radius:14px;width:320px;text-align:center;box-shadow:0 10px 30px rgba(0,0,0,0.1);animation:fadeIn 0.3s ease;}
        @keyframes fadeIn{from{opacity:0;transform:translateY(-10px);}to{opacity:1;transform:translateY(0);}}
        .close{float:right;cursor:pointer;font-size:22px;color:#999;}
        .close:hover{color:#000;}
        .btn-principal{background:#22c55e;color:white;border:none;padding:12px;border-radius:10px;cursor:pointer;transition:0.2s;}
        .btn-principal:hover{background:#16a34a;}
        @media(max-width:600px){.row{flex-direction:column;}.container-larga{margin:20px;padding:20px;}}
    </style>
</head>

<body>
    <div class="container-larga">
        <h2>✏️ Editar Restaurante</h2>
        <form action="" method="POST" class="form-auth">
            <input type="text" name="nome" placeholder="Nome do Restaurante" value="<?= htmlspecialchars($restaurante['nome']) ?>" required>

            <div class="row-cat">
                <div style="flex:1;">
                    <label>Categoria:</label>
                    <select name="id_categoria" id="select-categoria" required>
                        <option value="">Selecione...</option>
                        <?php while($c=$categorias->fetch_assoc()): ?>
                            <option value="<?= $c['id'] ?>" <?= $restaurante['id_categoria']==$c['id']?'selected':'' ?>><?= $c['nome'] ?></option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <button type="button" class="btn-add-modal" onclick="abrirModal()" title="Nova Categoria">
                    <i class="fas fa-plus"></i>
                </button>
            </div>

            <div class="row">
                <select name="preco">
                    <option value="barato" <?= $restaurante['preco']=='barato'?'selected':'' ?>>Barato ($)</option>
                    <option value="medio" <?= $restaurante['preco']=='medio'?'selected':'' ?>>Médio ($$)</option>
                    <option value="alto" <?= $restaurante['preco']=='alto'?'selected':'' ?>>Caro ($$$)</option>
                </select>
            </div>

            <p class="instrucao-mapa">Clique no mapa para marcar a localização:</p>
            <div id="map"></div>

            <div class="row">
                <input type="text" name="latitude" id="lat" placeholder="Latitude" value="<?= $restaurante['latitude'] ?>" readonly required>
                <input type="text" name="longitude" id="lon" placeholder="Longitude" value="<?= $restaurante['longitude'] ?>" readonly required>
            </div>

            <div class="row">
                <div class="campo">
                    <label>Abre:</label>
                    <input type="time" name="horario_abre" value="<?= $restaurante['horario_abre'] ?>" required>
                </div>
                <div class="campo">
                    <label>Fecha:</label>
                    <input type="time" name="horario_fecha" value="<?= $restaurante['horario_fecha'] ?>" required>
                </div>
            </div>

            <div class="campo">
                <label>Dias de Funcionamento:</label>
                <div style="display:flex;gap:10px;flex-wrap:wrap;margin-top:5px;">
                    <?php 
                    $dias_sel = explode(", ", $restaurante['dias_funcionamento']);
                    $dias_tot = ['Seg','Ter','Qua','Qui','Sex','Sab','Dom'];
                    foreach($dias_tot as $d): ?>
                        <label><input type="checkbox" name="dias[]" value="<?= $d ?>" <?= in_array($d,$dias_sel)?'checked':'' ?>> <?= $d ?></label>
                    <?php endforeach; ?>
                </div>
            </div>

            <button type="submit" class="btn-grande">Atualizar Restaurante</button>
        </form>
    </div>

    <div id="modalCategoria" class="modal">
        <div class="modal-content">
            <span class="close" onclick="fecharModal()">&times;</span>
            <h3>Nova Categoria</h3>
            <input type="text" id="nova-cat-nome" placeholder="Ex: Hamburgueria" class="campo" style="width:90%;margin-top:10px;">
            <button type="button" onclick="salvarCategoria()" class="btn-principal" style="width:100%;margin-top:10px;">Salvar</button>
        </div>
    </div>

    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script>
        // Mapa
        var latInicial = <?= $restaurante['latitude'] ?: '-23.9608' ?>;
        var lonInicial = <?= $restaurante['longitude'] ?: '-46.3339' ?>;
        var map = L.map('map').setView([latInicial, lonInicial], 13);
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png').addTo(map);
        var marker = L.marker([latInicial, lonInicial]).addTo(map);

        map.on('click', function(e){
            var lat = e.latlng.lat.toFixed(6);
            var lon = e.latlng.lng.toFixed(6);
            marker.setLatLng(e.latlng);
            document.getElementById('lat').value = lat;
            document.getElementById('lon').value = lon;
        });

        // Modal
        function abrirModal(){ document.getElementById('modalCategoria').style.display='block'; }
        function fecharModal(){ document.getElementById('modalCategoria').style.display='none'; }
        function salvarCategoria(){
            const nome = document.getElementById('nova-cat-nome').value;
            if(!nome) return alert("Digite o nome da categoria");
            fetch('ajax_salvar_categoria.php',{
                method:'POST',
                headers:{'Content-Type':'application/x-www-form-urlencoded'},
                body:'nome='+encodeURIComponent(nome)
            })
            .then(res=>res.json())
            .then(data=>{
                if(data.sucesso){
                    const select=document.getElementById('select-categoria');
                    const option=new Option(nome,data.id);
                    select.add(option);
                    select.value=data.id;
                    fecharModal();
                    document.getElementById('nova-cat-nome').value='';
                } else { alert("Erro ao salvar categoria"); }
            });
        }
    </script>
</body>
</html>