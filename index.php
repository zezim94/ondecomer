<?php
session_start();
?>
<!DOCTYPE html>
<html lang="pt-br">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Onde Comer Agora?</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
  <style>
    /* RESET & FONT */
    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
      font-family: 'Poppins', 'Segoe UI', sans-serif;
    }

    body {
      background: linear-gradient(135deg, #fefefe, #e0e7ff);
      min-height: 100vh;
      display: flex;
      flex-direction: column;
      color: #111827;
    }

    /* HEADER */
    header {
      display: flex;
      justify-content: flex-end;
      align-items: center;
      padding: 20px 40px;
      background: #6366f1;
      color: white;
      box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
    }
    a{
      text-decoration: none;
    }

    .auth-buttons {
      display: flex;
      gap: 12px;
      align-items: center;
    }

    .user-name {
      font-size: 14px;
      color: #e0e7ff;
      font-weight: 500;
    }

    .btn-principal,
    .btn-secundario {
      padding: 10px 18px;
      border-radius: 8px;
      text-decoration: none;
      font-weight: 500;
      transition: 0.3s;
    }

    .btn-principal {
      background: #facc15;
      color: #111827;
    }

    .btn-principal:hover {
      transform: translateY(-2px);
      box-shadow: 0 4px 15px rgba(250, 204, 21, 0.4);
    }

    .btn-secundario {
      background: rgba(255, 255, 255, 0.2);
      color: white;
    }

    .btn-secundario:hover {
      background: rgba(255, 255, 255, 0.3);
    }

    /* MAIN */
    main {
      flex: 1;
      display: flex;
      flex-direction: column;
      justify-content: center;
      align-items: center;
      padding: 30px 20px;
      text-align: center;
    }

    /* LOGO */
    .logo {
      font-size: 80px;
      margin-bottom: 15px;
      animation: float 3s ease-in-out infinite;
    }

    @keyframes float {

      0%,
      100% {
        transform: translateY(0);
      }

      50% {
        transform: translateY(-10px);
      }
    }

    /* TITULO */
    h1 {
      font-size: 40px;
      margin-bottom: 15px;
      color: #1e293b;
    }

    .subtitulo {
      color: #4b5563;
      margin-bottom: 25px;
      font-size: 16px;
      max-width: 450px;
    }

    /* CLIMA */
    .clima-card {
      background: #ffffffcc;
      backdrop-filter: blur(8px);
      padding: 20px 25px;
      border-radius: 16px;
      margin-bottom: 25px;
      width: 280px;
      box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
      transition: 0.3s;
    }

    .clima-card:hover {
      transform: translateY(-5px);
    }

    .clima-info {
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 10px;
      font-size: 14px;
      color: #1e293b;
      font-weight: 500;
    }

    .clima-detalhes {
      margin-top: 10px;
      font-size: 18px;
      font-weight: 600;
    }

    /* BOTÃO GRANDE */
    .btn-grande {
      background: #10b981;
      color: white;
      border: none;
      padding: 18px 28px;
      border-radius: 16px;
      font-size: 18px;
      cursor: pointer;
      transition: 0.3s;
      margin-bottom: 15px;
      display: flex;
      align-items: center;
      gap: 8px;
    }

    .btn-grande:hover {
      transform: scale(1.05);
      box-shadow: 0 8px 20px rgba(16, 185, 129, 0.4);
    }

    .visitante-box {
      background: #f3f4f6;
      padding: 25px;
      border-radius: 16px;
      max-width: 400px;
      box-shadow: 0 6px 20px rgba(0, 0, 0, 0.05);
    }

    .btn-grande-outline {
      display: inline-block;
      border: 2px solid #6366f1;
      color: #6366f1;
      padding: 14px 24px;
      border-radius: 14px;
      text-decoration: none;
      font-weight: 500;
      transition: 0.3s;
    }

    .btn-grande-outline:hover {
      background: #6366f1;
      color: white;
    }

    a[href="explorar.php"] {
      margin-top: 12px;
      display: inline-block;
      color: #6366f1;
      text-decoration: none;
      font-weight: 500;
    }

    a[href="explorar.php"]:hover {
      text-decoration: underline;
    }

    /* RESPONSIVO */
    @media (max-width: 768px) {
      h1 {
        font-size: 32px;
      }

      .logo {
        font-size: 60px;
      }

      .clima-card {
        width: 100%;
        max-width: 320px;
      }

      .auth-buttons {
        flex-direction: column;
        align-items: flex-end;
        gap: 8px;
      }
    }

    @media (max-width: 480px) {
      header {
        justify-content: center;
        padding: 15px;
      }

      .auth-buttons {
        align-items: center;
        gap: 6px;
      }

      .btn-principal,
      .btn-secundario {
        width: 100%;
        text-align: center;
      }

      .btn-grande {
        width: 100%;
        justify-content: center;
      }

      .visitante-box {
        width: 100%;
        padding: 20px;
      }
    }
  </style>
</head>

<body>
  <header>
    <div class="auth-buttons">
      <?php if (isset($_SESSION['usuario_id'])): ?>
        <a href="usuarios.php"><span class="user-name">Olá,
            <strong><?= explode(' ', $_SESSION['usuario_nome'])[0] ?></strong>! 👋</span></a>
        <a href="logout.php" class="btn-secundario">Sair</a>
      <?php else: ?>
        <a href="login.php" class="btn-secundario">Entrar</a>
        <a href="cadastro.php" class="btn-principal">Cadastrar</a>
      <?php endif; ?>
    </div>
  </header>

  <main>
    <div class="logo">🍤</div>
    <h1>Onde Comer Agora?</h1>

    <div id="clima-container" class="clima-card" style="display: none;">
      <div class="clima-info">
        <i class="fas fa-cloud-sun"></i>
        <span id="cidade">Buscando cidade...</span>
      </div>
      <div class="clima-detalhes">
        <span id="temp">--°C</span>
        <span id="condicao">--</span>
      </div>
    </div>

    <?php if (isset($_SESSION['usuario_id'])): ?>
      <p class="subtitulo">Tudo pronto! Vamos encontrar o melhor restaurante para você agora?</p>
      <button class="btn-grande" onclick="buscarLocalizacao()">
        <i class="fas fa-utensils"></i> Descobrir Opção
      </button>
      <a href="explorar.php">Explorar todos</a>
    <?php else: ?>
      <div class="visitante-box">
        <p class="subtitulo">Para receber recomendações personalizadas baseadas no clima e na sua localização, faça login.
        </p>
        <div class="opcoes-visitante">
          <a href="login.php" class="btn-grande-outline">Entrar na minha conta</a>
        </div>
      </div>
    <?php endif; ?>
  </main>

  <script>
    const API_KEY = "3f8c42c005e69722c9b31cf1f9712eff";

    function buscarClima(lat, lon) {
      const url = `https://api.openweathermap.org/data/2.5/weather?lat=${lat}&lon=${lon}&appid=${API_KEY}&units=metric&lang=pt_br`;
      fetch(url)
        .then(res => res.json())
        .then(data => {
          document.getElementById('clima-container').style.display = 'block';
          document.getElementById('cidade').innerText = data.name;
          document.getElementById('temp').innerText = Math.round(data.main.temp) + "°C";
          document.getElementById('condicao').innerText = data.weather[0].description;
        })
        .catch(err => console.log("Erro ao buscar clima:", err));
    }

    function buscarLocalizacao() {
      navigator.geolocation.getCurrentPosition(function (pos) {
        const lat = pos.coords.latitude;
        const lon = pos.coords.longitude;
        window.location.href = `resultado.php?lat=${lat}&lon=${lon}`;
      });
    }

    window.onload = () => {
      if (navigator.geolocation) {
        navigator.geolocation.getCurrentPosition(pos => {
          buscarClima(pos.coords.latitude, pos.coords.longitude);
        });
      }
    };
  </script>
</body>

</html>