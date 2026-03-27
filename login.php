<?php
include("conexao.php");
session_start();

$erro = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = $conn->real_escape_string($_POST['email']);
    $senha = $_POST['senha'];

    // Busca o usuário pelo e-mail
    $sql = "SELECT * FROM usuarios WHERE email = '$email'";
    $result = $conn->query($sql);

    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();

        // Verifica se a senha digitada bate com a senha criptografada no banco
        if (password_verify($senha, $user['senha'])) {
            $_SESSION['usuario_id'] = $user['id'];
            $_SESSION['usuario_nome'] = $user['nome'];
            $_SESSION['usuario_nivel'] = $user['nivel']; // SALVAR O NÍVEL AQUI

            // Redirecionamento inteligente
            if ($user['nivel'] == 'adm' || $user['nivel'] == 'master') {
                header("Location: adm.php");
            } else {
                header("Location: index.php");
            }
            exit();
        } else {
            $erro = "Senha incorreta!";
        }
    } else {
        $erro = "E-mail não encontrado!";
    }
}
?>

<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Onde Comer Agora?</title>
    <link rel="stylesheet" href="css/style.css">
</head>

<body class="auth-page">

    <main class="container">
        <div class="logo-pequena">🍤</div>
        <h2>Bem-vindo de volta!</h2>
        <p class="subtitulo">Acesse sua conta para continuar.</p>

        <?php if ($erro): ?>
            <div class="alerta erro">
                <?= $erro ?>
            </div>
        <?php endif; ?>

        <form action="login.php" method="POST" class="form-auth">
            <div class="campo">
                <label for="email">E-mail</label>
                <input type="email" id="email" name="email" placeholder="seu@email.com" required>
            </div>

            <div class="campo">
                <label for="senha">Senha</label>
                <input type="password" id="senha" name="senha" placeholder="••••••••" required>
            </div>

            <button type="submit" class="btn-grande">Entrar</button>
        </form>

        <p class="footer-auth">
            Ainda não tem conta? <a href="cadastro.php">Criar conta</a>
        </p>
        <p class="footer-auth">
            <a href="index.php">← Voltar ao início</a>
        </p>
    </main>

</body>

</html>