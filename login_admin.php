<?php
session_start();
require_once 'config.php';

$erro_login = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = $_POST['username'];
    $password = $_POST['password'];

    if (empty($username) || empty($password)) {
        $erro_login = "Usuário e senha são obrigatórios.";
    } else {
        $stmt = $conexao->prepare("SELECT password_hash FROM administradores WHERE username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $stmt->bind_result($password_hash);
        
        if ($stmt->fetch() && password_verify($password, $password_hash)) {
            // Sucesso no login
            $_SESSION['admin_logado'] = true;
            $_SESSION['admin_username'] = $username;
            header("Location: painel_admin.php");
            exit();
        } else {
            // Falha no login
            $erro_login = "Usuário ou senha inválidos.";
        }
        $stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Login Administrador - NUTIC-SVO</title>
    <link rel="stylesheet" href="css/style.css?v=3.5">
</head>
<body>
    <header class="site-header">
        <div class="branding">
            <span class="logo">🔐</span>
            <div class="titles">
                <h1>Área Restrita</h1>
                <p>NUTIC - SVO</p>
            </div>
        </div>
    </header>
    <div class="container-login">
        <form action="login_admin.php" method="post" class="form-login">
            <h2>Login do Administrador</h2>
            <?php if ($erro_login): ?>
                <p class="mensagem-erro"><?php echo $erro_login; ?></p>
            <?php endif; ?>
            <div class="form-group">
                <label for="username">Usuário</label>
                <input type="text" id="username" name="username" required>
            </div>
            <div class="form-group">
                <label for="password">Senha</label>
                <input type="password" id="password" name="password" required>
            </div>
            <button type="submit" class="button">Entrar</button>
        </form>
    </div>
</body>
</html>