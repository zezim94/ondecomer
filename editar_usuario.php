<?php
session_start();
include("conexao.php");

// 1. Proteção: Apenas ADM pode acessar
if (!isset($_SESSION['usuario_id']) || $_SESSION['usuario_nivel'] !== 'adm') {
    header("Location: login.php");
    exit();
}

// 2. Pega o ID do usuário que será editado
$id_edicao = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($id_edicao == 0) {
    header("Location: gerenciar_usuarios.php");
    exit();
}

// 3. Processa o formulário quando o ADM clicar em Salvar
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nome = $conn->real_escape_string($_POST['nome']);
    $email = $conn->real_escape_string($_POST['email']);
    $nivel = $_POST['nivel'];
    $nova_senha = $_POST['senha'];

    // Prepara a query de atualização base (sem mexer na senha ainda)
    $sql_update = "UPDATE usuarios SET nome = '$nome', email = '$email', nivel = '$nivel'";

    // Se o ADM digitou uma nova senha, adiciona ela na query
    if (!empty($nova_senha)) {
        $senha_hash = password_hash($nova_senha, PASSWORD_DEFAULT);
        $sql_update .= ", senha = '$senha_hash'";
    }

    $sql_update .= " WHERE id = $id_edicao";

    if ($conn->query($sql_update)) {
        // Redireciona de volta para a lista com mensagem de sucesso
        header("Location: gerenciar_usuarios.php?msg=editado");
        exit();
    } else {
        $erro = "Erro ao atualizar: " . $conn->error;
    }
}

// 4. Busca os dados atuais do usuário para preencher o formulário
$res = $conn->query("SELECT * FROM usuarios WHERE id = $id_edicao");
if ($res->num_rows == 0) {
    echo "Usuário não encontrado!";
    exit();
}
$usuario_edit = $res->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Editar Usuário</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body class="bg-cinza">

    <div class="container" style="margin-top: 50px;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
            <h2><i class="fas fa-user-edit"></i> Editar Usuário</h2>
            <a href="gerenciar_usuarios.php" class="btn-secundario" style="margin-top: 0; padding: 8px 15px;">Cancelar</a>
        </div>

        <?php if(isset($erro)): ?>
            <div class="alerta" style="background: #f8d7da; color: #721c24; border-color: #f5c6cb;">
                <?= $erro ?>
            </div>
        <?php endif; ?>

        <div class="form-auth">
            <form action="" method="POST">
                
                <div class="campo">
                    <label>Nome Completo</label>
                    <input type="text" name="nome" value="<?= $usuario_edit['nome'] ?>" required>
                </div>

                <div class="campo">
                    <label>E-mail</label>
                    <input type="email" name="email" value="<?= $usuario_edit['email'] ?>" required>
                </div>

                <div class="campo">
                    <label>Nível de Acesso</label>
                    <select name="nivel" required style="width: 100%; padding: 12px; border-radius: 8px; border: 1px solid #ddd;">
                        <option value="cliente" <?= $usuario_edit['nivel'] == 'cliente' ? 'selected' : '' ?>>Cliente (Usuário Comum)</option>
                        <option value="adm" <?= $usuario_edit['nivel'] == 'adm' ? 'selected' : '' ?>>Administrador (Dono de Restaurante)</option>
                    </select>
                </div>

                <div class="campo">
                    <label>Nova Senha</label>
                    <input type="password" name="senha" placeholder="Deixe em branco para manter a senha atual">
                    <small style="color: #7f8c8d; font-size: 0.8rem;">Preencha apenas se quiser redefinir a senha do usuário.</small>
                </div>

                <button type="submit" class="btn-grande" style="width: 100%; margin-top: 10px;">
                    <i class="fas fa-save"></i> Salvar Alterações
                </button>
            </form>
        </div>
    </div>

</body>
</html>