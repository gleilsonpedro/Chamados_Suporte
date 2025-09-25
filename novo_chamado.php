<?php
session_start();
if (!isset($_SESSION['usuario_logado'])) {
    header("Location: index.php");
    exit();
}
$usuario_logado = $_SESSION['usuario_logado'];
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Abrir Novo Chamado - NUTIC-SVO</title>
    <link rel="stylesheet" href="css/style.css?v=3.6">
</head>
<body>
    <header class="site-header">
        <div class="branding">
            <span class="logo">💻</span>
            <div class="titles">
                <h1>NUTIC - SVO</h1>
                <p>por Gleilson Pedro</p>
            </div>
        </div>
        <a href="painel_usuario.php" class="nav-button">Cancelar e Voltar</a>
    </header>
    <h2 class="page-title">Formulário de Abertura de Chamado</h2>

    <div class="container form-container">
        <form action="salvar_chamado.php" method="post" enctype="multipart/form-data" id="form-chamado">
            <div class="form-group">
                <label for="usuario">Usuário</label>
                <input type="text" id="usuario" name="usuario" value="<?php echo htmlspecialchars($usuario_logado); ?>" readonly>
            </div>
            <div class="form-group">
                <label for="titulo">Título do Chamado</label>
                <input type="text" id="titulo" name="titulo" required>
            </div>
            <div class="form-group">
                <label for="descricao">Descrição do Problema</label>
                <textarea id="descricao" name="descricao" rows="10" required></textarea>
            </div>
            <div class="form-group">
                <label for="anexo">Anexar Imagem (Opcional)</label>
                <input type="file" id="anexo" name="anexo" accept="image/*">
            </div>
            <div class="form-group">
                <button type="submit" class="button" id="btn-submit">Adicionar Chamado</button>
            </div>
        </form>
    </div>

    <script>
        // Pega o formulário e o botão pelos IDs que adicionamos
        const formChamado = document.getElementById('form-chamado');
        const btnSubmit = document.getElementById('btn-submit');

        // Adiciona um "ouvinte" para o evento de envio do formulário
        formChamado.addEventListener('submit', function() {
            // Desativa o botão para evitar cliques duplos
            btnSubmit.disabled = true;

            // Adiciona a classe de estilo de carregamento
            btnSubmit.classList.add('btn-loading');

            // Muda o conteúdo do botão para mostrar a mensagem e o spinner
            btnSubmit.innerHTML = '<div class="spinner"></div> Aguarde, abrindo chamado...';
        });
    </script>
</body>
</html>