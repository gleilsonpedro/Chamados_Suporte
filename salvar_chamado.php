<?php
session_start();
require_once 'config.php';

// Inclui os arquivos do PHPMailer
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'PHPMailer/src/Exception.php';
require 'PHPMailer/src/PHPMailer.php';
require 'PHPMailer/src/SMTP.php';


if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_SESSION['usuario_logado'])) {
    
    // Coleta dos dados do formulário e da sessão
    $usuario_login = $_SESSION['usuario_logado'];
    $usuario_email = isset($_SESSION['usuario_email']) ? $_SESSION['usuario_email'] : null;
    $titulo = trim($_POST['titulo']);
    $descricao = trim($_POST['descricao']);
    $anexo_path = NULL;
    $hostname = gethostbyaddr($_SERVER['REMOTE_ADDR']);

    // Validação
    if (empty($titulo) || empty($descricao)) {
        die("Erro: Título e descrição são obrigatórios.");
    }

    // Processa o upload do anexo
    if (isset($_FILES['anexo']) && $_FILES['anexo']['error'] == 0) {
        $upload_dir = 'uploads/';
        $file_name = time() . '_' . basename($_FILES['anexo']['name']);
        $target_file = $upload_dir . $file_name;
        if (move_uploaded_file($_FILES['anexo']['tmp_name'], $target_file)) {
            $anexo_path = $target_file;
        }
    }

    // Insere no banco de dados, incluindo o novo campo 'email_notificacao'
    $sql = "INSERT INTO chamados (usuario_login, hostname, email_notificacao, titulo, descricao, anexo_path, data_ultima_atualizacao) VALUES (?, ?, ?, ?, ?, ?, NOW())";
    $stmt = $conexao->prepare($sql);
    $stmt->bind_param("ssssss", $usuario_login, $hostname, $usuario_email, $titulo, $descricao, $anexo_path);

    if ($stmt->execute()) {
        $novo_chamado_id = $conexao->insert_id; // Pega o ID do chamado que acabamos de criar

        // --- INÍCIO DA LÓGICA DE ENVIO DE E-MAIL ---
        try {
            $mail = new PHPMailer(true);
            $mail->isSMTP();
            $mail->Host       = SMTP_HOST;
            $mail->SMTPAuth   = true;
            $mail->Username   = SMTP_USERNAME;
            $mail->Password   = SMTP_PASSWORD;
            $mail->SMTPSecure = SMTP_SECURE;
            $mail->Port       = SMTP_PORT;
            $mail->CharSet    = 'UTF-8';
            $mail->setFrom(SMTP_USERNAME, 'Sistema de Chamados NUTIC-SVO');

            // 1. E-mail para o Administrador (você)
            $mail->addAddress(ADMIN_EMAIL, ADMIN_NAME);
            $mail->isHTML(true);
            $mail->Subject = "[Chamado #${novo_chamado_id}] Novo chamado aberto por ${usuario_login}";
            $mail->Body    = "<h1>Novo Chamado Aberto</h1>
                              <p>Um novo chamado foi registrado no sistema.</p>
                              <ul>
                                <li><strong>ID do Chamado:</strong> #${novo_chamado_id}</li>
                                <li><strong>Usuário:</strong> ${usuario_login}</li>
                                <li><strong>Máquina:</strong> ${hostname}</li>
                                <li><strong>Título:</strong> ${titulo}</li>
                                <li><strong>Descrição:</strong><br>" . nl2br(htmlspecialchars($descricao)) . "</li>
                              </ul>
                              <p>Acesse o painel para responder.</p>";
            $mail->send();

            // 2. E-mail para o usuário (se ele optou por receber)
            if ($usuario_email) {
                $mail->clearAddresses(); // Limpa o destinatário anterior
                $mail->addAddress($usuario_email, $usuario_login);
                $mail->Subject = "Confirmação de Abertura de Chamado [#${novo_chamado_id}]";
                $mail->Body    = "<h1>Seu Chamado Foi Aberto com Sucesso!</h1>
                                  <p>Olá, ${usuario_login}. Recebi seu chamado e em breve será atendido.</p>
                                  <p><strong>Detalhes do seu chamado:</strong></p>
                                  <ul>
                                    <li><strong>ID do Chamado:</strong> #${novo_chamado_id}</li>
                                    <li><strong>Título:</strong> ${titulo}</li>
                                  </ul>
                                  <p>Você pode acompanhar o andamento pelo sistema de chamados na INTRANET do SVO.</p>
                                  <p>Sistema desenvolvido por Gleilson Pedro</p>
                                  <p>Setor NUTIC - SVO</p>";
                $mail->send();
            }

        } catch (Exception $e) {
            // Não interrompe o fluxo do usuário se o e-mail falhar. Apenas registra o erro.
            error_log("PHPMailer Error: {$mail->ErrorInfo}");
        }
        // --- FIM DA LÓGICA DE ENVIO DE E-MAIL ---
        
        header("Location: painel_usuario.php");
        exit();

    } else {
        echo "Erro ao criar o chamado: " . $stmt->error;
    }
    $stmt->close();
    $conexao->close();

} else {
    header("Location: index.php");
    exit();
}
?>