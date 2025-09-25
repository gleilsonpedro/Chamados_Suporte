<?php
session_start();
// Se houver algum erro vindo da página de processo, exibe aqui
$erro = isset($_GET['erro']) ? htmlspecialchars($_GET['erro']) : '';
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Identificação - Suporte NUTIC-SVO</title>
    <link rel="stylesheet" href="css/style.css?v=3.9">
</head>
<body>
    <header class="site-header">
        <div class="branding">
            <span class="logo">👤</span>
            <div class="titles">
                <h1>Sistema de Chamados</h1>
                <p>NUTIC - SVO</p>
            </div>
        </div>
    </header>

    <div class="container-login">
        <form action="login_process.php" method="post" class="form-login">
            <h2><strong>Identificação de Usuário</strong></h2>
            <p>Para abrir ou consultar seus chamados, por favor, informe seu usuário de rede (ex: gleilson.pedro) ou seu e-mail completo.</p>
            
            <?php if ($erro): ?>
                <p class="mensagem-erro"><?php echo $erro; ?></p>
            <?php endif; ?>

            <div class="form-group">
                <label for="identificacao" style="display: block; text-align: center;">Usuário de Rede ou E-mail</label>
                <input type="text" id="identificacao" name="identificacao" required autocomplete="off">
            </div>
            
            <div class="form-group-checkbox">
                <input type="checkbox" id="notificar" name="notificar" value="1" checked>
                <label for="notificar" style="display: block; text-align: center;">
                    <strong style="display: block; text-align: center;">Notificações por e-mail</strong>
                    <small>Informe e-mail completo no campo acima (ex: seu.email@email.com.br). 
                    Para receber notificações por e-mail sobre o chamado.
                    Logins incompletos impedirão o recebimento das atualizações por e-mail.</small>
                </label>
            </div>

            <p id="aviso-email" class="mensagem-aviso" style="display: none;">
                Não será possível enviar notificações por e-mail com um nome de usuário.
            </p>
            
            <button type="submit" class="button">Continuar</button>
        </form>
    </div>

    <div style="text-align: center; margin-top: 50px; font-size: 0.8em;">
        <a href="login_admin.php" style="color: #6c757d;">Acesso Restrito</a>
    </div>

    <script>
        const campoIdentificacao = document.getElementById('identificacao');
        const campoCheckbox = document.getElementById('notificar');
        const avisoEmail = document.getElementById('aviso-email');

        campoIdentificacao.addEventListener('keyup', function() {
            const valor = campoIdentificacao.value;
            if (!valor.includes('@')) {
                campoCheckbox.checked = false;
                campoCheckbox.disabled = true;
                avisoEmail.style.display = 'block';
            } else {
                campoCheckbox.disabled = false;
                campoCheckbox.checked = true;
                avisoEmail.style.display = 'none';
            }
        });
    </script>
</body>
</html>