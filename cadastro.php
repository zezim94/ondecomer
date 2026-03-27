<?php
include("conexao.php");

$mensagem = "";
$tipo_mensagem = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nome = $conn->real_escape_string($_POST['nome']);
    $email = $conn->real_escape_string($_POST['email']);
    $senha = $_POST['senha'];
    

    // Validar se o e-mail já existe
    $check_email = $conn->query("SELECT id FROM usuarios WHERE email = '$email'");
    
    if ($check_email->num_rows > 0) {
        $mensagem = "Este e-mail já está cadastrado.";
        $tipo_mensagem = "erro";
    } else {
        // Criptografar a senha
        $senha_hash = password_hash($senha, PASSWORD_DEFAULT);

        $sql = "INSERT INTO usuarios (nome, email, senha) VALUES ('$nome', '$email', '$senha_hash')";
        
        if ($conn->query($sql)) {
            $mensagem = "Cadastro realizado com sucesso! Redirecionando...";
            $tipo_mensagem = "sucesso";
            // Redireciona para o login após 2 segundos
            header("refresh:2;url=login.php");
        } else {
            $mensagem = "Erro ao cadastrar: " . $conn->error;
            $tipo_mensagem = "erro";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cadastro - Onde Comer Agora?</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body class="auth-page">

    <main class="container">
        <div class="logo-pequena">🍤</div>
        <h2>Crie sua conta</h2>
        <p class="subtitulo">Para salvar seus restaurantes favoritos.</p>

        <?php if ($mensagem): ?>
            <div class="alerta <?= $tipo_mensagem ?>">
                <?= $mensagem ?>
            </div>
        <?php endif; ?>

        <form action="cadastro.php" method="POST" class="form-auth">
            <div class="campo">
                <label for="nome">Nome Completo</label>
                <input type="text" id="nome" name="nome" placeholder="Ex: João Silva" required>
            </div>

            <div class="campo">
                <label for="email">E-mail</label>
                <input type="email" id="email" name="email" placeholder="seu@email.com" required>
            </div>

            <div class="campo">
                <label for="senha">Senha</label>
                <input type="password" id="senha" name="senha" placeholder="••••••••" required>
            </div>

            <button type="submit" class="btn-grande">Cadastrar</button>
        </form>

        <p class="footer-auth">
            Já tem conta? <a href="login.php">Fazer Login</a>
        </p>
        <p class="footer-auth">
            <a href="index.php">← Voltar ao início</a>
        </p>
    </main>

</body>
</html>