<?php
header('Content-Type: application/json');
require_once 'config.php';
session_start();

function json_error($message) {
    echo json_encode(['error' => $message]);
    exit();
}

$acao = isset($_GET['acao']) ? $_GET['acao'] : '';

if ($acao == 'novos_chamados') {
    $ultimo_id = isset($_GET['ultimo_id']) ? (int)$_GET['ultimo_id'] : 0;
    $sql = "SELECT id, usuario_login, hostname, titulo, descricao, data_abertura, status FROM chamados WHERE id > ? AND status IN ('Aberto', 'Pendente') ORDER BY id DESC";
    $stmt = $conexao->prepare($sql);
    if (!$stmt) json_error("Erro ao preparar query de novos chamados: " . $conexao->error);
    
    $stmt->bind_param("i", $ultimo_id);
    $stmt->execute();
    $stmt->bind_result($id, $usuario_login, $hostname, $titulo, $descricao, $data_abertura, $status);
    $chamados = [];
    while ($stmt->fetch()) {
        // --- TYPO CORRIGIDO AQUI (era $usuario_loglogin) ---
        $chamados[] = ['id' => $id, 'usuario_login' => $usuario_login, 'hostname' => $hostname, 'titulo' => $titulo, 'descricao' => $descricao, 'data_abertura' => $data_abertura, 'status' => $status, 'data_formatada' => date('d/m/Y H:i', strtotime($data_abertura))];
    }
    $stmt->close();
    echo json_encode($chamados);
    exit();
}

if ($acao == 'checar_atualizacoes_usuario' && isset($_SESSION['usuario_logado'])) {
    $usuario_logado = $_SESSION['usuario_logado'];
    $ultima_atualizacao = isset($_GET['ultima_atualizacao']) ? $_GET['ultima_atualizacao'] : '2000-01-01 00:00:00';
    $chamados_atualizados = [];
    $sql = "SELECT c.id, c.titulo, c.status, c.data_ultima_atualizacao, (SELECT i.mensagem FROM interacoes i WHERE i.chamado_id = c.id AND i.autor = 'NUTIC-SVO' ORDER BY i.id DESC LIMIT 1) AS ultima_resposta_admin 
            FROM chamados c 
            WHERE c.usuario_login = ? AND c.data_ultima_atualizacao > ? AND c.status IN ('Aberto', 'Pendente')
            ORDER BY c.data_ultima_atualizacao DESC";
    $stmt = $conexao->prepare($sql);
    $stmt->bind_param("ss", $usuario_logado, $ultima_atualizacao);
    $stmt->execute();
    $stmt->bind_result($id, $titulo, $status, $data_ultima_atualizacao, $ultima_resposta_admin);
    while ($stmt->fetch()) {
        $chamados_atualizados[] = ['id' => $id, 'titulo' => $titulo, 'status' => $status, 'data_ultima_atualizacao' => $data_ultima_atualizacao, 'data_formatada' => date('d/m/Y H:i', strtotime($data_ultima_atualizacao)), 'ultima_resposta_admin' => $ultima_resposta_admin];
    }
    $stmt->close();
    echo json_encode($chamados_atualizados);
    exit();
}

if ($acao == 'atualizacoes_chamado') {
    $chamado_id = isset($_GET['chamado_id']) ? (int)$_GET['chamado_id'] : 0;
    $ultima_interacao_id = isset($_GET['ultima_interacao_id']) ? (int)$_GET['ultima_interacao_id'] : 0;
    if ($chamado_id === 0) json_error("ID do chamado não fornecido.");
    $resposta = ['status' => '', 'novas_interacoes' => []];
    $stmt_status = $conexao->prepare("SELECT status FROM chamados WHERE id = ?");
    $stmt_status->bind_param("i", $chamado_id);
    $stmt_status->execute();
    $stmt_status->bind_result($status_atual);
    if ($stmt_status->fetch()) { $resposta['status'] = $status_atual; }
    $stmt_status->close();
    $stmt_interacoes = $conexao->prepare("SELECT id, autor, mensagem, data_interacao FROM interacoes WHERE chamado_id = ? AND id > ? ORDER BY id ASC");
    $stmt_interacoes->bind_param("ii", $chamado_id, $ultima_interacao_id);
    $stmt_interacoes->execute();
    $stmt_interacoes->bind_result($id, $autor, $mensagem, $data_interacao);
    while ($stmt_interacoes->fetch()) { $resposta['novas_interacoes'][] = ['id' => $id, 'autor' => $autor, 'mensagem' => $mensagem, 'data_interacao' => $data_interacao, 'data_formatada' => date('d/m/Y H:i', strtotime($data_interacao))]; }
    $stmt_interacoes->close();
    echo json_encode($resposta);
    exit();
}

json_error('Ação inválida.');
?>