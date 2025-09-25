<?php
session_start();

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $identificacao = trim(strtolower($_POST['identificacao']));

    if (empty($identificacao)) {
        header("Location: index.php?erro=O campo de identificação é obrigatório.");
        exit();
    }

    // Limpa sessões anteriores para garantir consistência
    unset($_SESSION['usuario_logado']);
    unset($_SESSION['usuario_email']);

    // Verifica se a identificação é um e-mail
    if (filter_var($identificacao, FILTER_VALIDATE_EMAIL)) {
        // É um e-mail. Extrai o nome de usuário (parte antes do @)
        $partes = explode('@', $identificacao);
        $_SESSION['usuario_logado'] = $partes[0];
        
        // Se a checkbox de notificação foi marcada, guarda o e-mail
        if (isset($_POST['notificar']) && $_POST['notificar'] == '1') {
            $_SESSION['usuario_email'] = $identificacao;
        }
    } else {
        // Não é um e-mail, então é um nome de usuário de rede
        $_SESSION['usuario_logado'] = $identificacao;
        // Neste caso, não há como notificar por e-mail
    }

    // Redireciona para o painel do usuário
    header("Location: painel_usuario.php");
    exit();

} else {
    // Se alguém tentar acessar o arquivo diretamente, redireciona para o início
    header("Location: index.php");
    exit();
}
?>