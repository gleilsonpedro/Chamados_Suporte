<?php
require_once 'config.php';

// Inclui os arquivos do PHPMailer
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'PHPMailer/src/Exception.php';
require 'PHPMailer/src/PHPMailer.php';
require 'PHPMailer/src/SMTP.php';


if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $chamado_id = (int)$_POST['chamado_id'];
    $autor = $_POST['autor'];
    $mensagem = trim($_POST['mensagem']);
    $is_admin = $_POST['is_admin'] == '1';

    // Salva a nova mensagem, se houver
    if (!empty($mensagem)) {
        $stmt_insert = $conexao->prepare("INSERT INTO interacoes (chamado_id, autor, mensagem) VALUES (?, ?, ?)");
        $stmt_insert->bind_param("iss", $chamado_id, $autor, $mensagem);
        $stmt_insert->execute();
        $stmt_insert->close();
    }
    
    // Se for o admin, atualiza o status e envia o e-mail de notificação
    if ($is_admin) {
        $novo_status = $_POST['novo_status'];
        
        // Atualiza o chamado no banco
        $stmt_update = $conexao->prepare("UPDATE chamados SET status = ?, data_fechamento = ? WHERE id = ?");
        $data_fechamento = ($novo_status == 'Fechado') ? date('Y-m-d H:i:s') : null;
        $stmt_update->bind_param("ssi", $novo_status, $data_fechamento, $chamado_id);
        $stmt_update->execute();
        $stmt_update->close();

        // --- INÍCIO DA NOVA LÓGICA DE E-MAIL DE ATUALIZAÇÃO ---
        
        // 1. Busca os dados do chamado para pegar o e-mail e nome do usuário
        $stmt_chamado = $conexao->prepare("SELECT usuario_login, email_notificacao FROM chamados WHERE id = ?");
        $stmt_chamado->bind_param("i", $chamado_id);
        $stmt_chamado->execute();
        $stmt_chamado->bind_result($usuario_login, $email_notificacao);
        $stmt_chamado->fetch();
        $stmt_chamado->close();

        // 2. Verifica se o usuário tem um e-mail de notificação cadastrado
        if (!empty($email_notificacao)) {
            try {
                // Monta a mensagem dinâmica
                $linha_principal = "Seu chamado de nº ${chamado_id} recebeu uma nova atualização.";
                if ($novo_status == 'Fechado') {
                    $linha_principal = "Seu chamado de nº ${chamado_id} foi finalizado.";
                }

                // Corpo do E-mail
                $corpo_email = "<h1>Atualização do seu Chamado</h1>
                                <p>Olá, ${usuario_login}.</p>
                                <p>${linha_principal}</p>";
                
                if ($novo_status) {
                    $corpo_email .= "<p><strong>Novo status:</strong> ${novo_status}</p>";
                }
                if (!empty($mensagem)) {
                    $corpo_email .= "<h4>Resposta do Suporte:</h4>
                                     <p style='padding: 10px; background-color: #f4f4f9; border-left: 3px solid #ccc;'>" 
                                     . nl2br(htmlspecialchars($mensagem)) . 
                                     "</p>";
                }
                $corpo_email .= "<p style='margin-top: 20px;'>Verifique o seu chamado no link da intranet do SVO inserindo seu nome de usuário ou email cadastrado.</p>
                                 <pstyle='margin-top: 20px;'>'Sistema desenvolvido por Gleilson Pedro - NUTIC - SVO.</p>";


                // Configura e envia o e-mail
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
                $mail->addAddress($email_notificacao, $usuario_login);
                $mail->isHTML(true);
                $mail->Subject = "Chamado NUTIC -SVO #${chamado_id} foi atualizado";
                $mail->Body    = $corpo_email;

                $mail->send();

            } catch (Exception $e) {
                error_log("PHPMailer Error (Update): {$mail->ErrorInfo}");
            }
        }
        // --- FIM DA NOVA LÓGICA DE E-MAIL DE ATUALIZAÇÃO ---

    } else {
        // Se for o usuário, apenas força a atualização da data
        $conexao->query("UPDATE chamados SET data_ultima_atualizacao = NOW() WHERE id = " . $chamado_id);
    }

    $conexao->close();

    // Redireciona de volta para a página do chamado
    $redirect_url = "ver_chamado.php?id=" . $chamado_id;
    if ($is_admin) {
        $redirect_url .= "&admin=1";
    }
    header("Location: " . $redirect_url);
    exit();
}
?>