<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['usuario_logado']) || empty($_SESSION['usuario_logado'])) {
    header("Location: index.php");
    exit();
}
$usuario_logado = $_SESSION['usuario_logado'];

// Query para chamados em andamento
$chamados_abertos = [];
$sql_abertos = "SELECT 
                    c.id, c.titulo, c.status, c.data_ultima_atualizacao,
                    (SELECT i.mensagem FROM interacoes i WHERE i.chamado_id = c.id AND i.autor = 'NUTIC-SVO' ORDER BY i.id DESC LIMIT 1) AS ultima_resposta_admin
                FROM chamados c
                WHERE c.usuario_login = ? AND c.status IN ('Aberto', 'Pendente') 
                ORDER BY c.data_ultima_atualizacao DESC";
$stmt_abertos = $conexao->prepare($sql_abertos);
$stmt_abertos->bind_param("s", $usuario_logado);
$stmt_abertos->execute();
$stmt_abertos->bind_result($id, $titulo, $status, $data_ultima_atualizacao, $ultima_resposta_admin);
while ($stmt_abertos->fetch()) {
    $chamados_abertos[] = ['id' => $id, 'titulo' => $titulo, 'status' => $status, 'data_ultima_atualizacao' => $data_ultima_atualizacao, 'ultima_resposta_admin' => $ultima_resposta_admin];
}
$stmt_abertos->close();
$data_base_verificacao = !empty($chamados_abertos) ? $chamados_abertos[0]['data_ultima_atualizacao'] : '2000-01-01 00:00:00';

// Query para chamados encerrados
$chamados_fechados = [];
$sql_fechados = "SELECT id, titulo, status, data_ultima_atualizacao 
                 FROM chamados 
                 WHERE usuario_login = ? AND status = 'Fechado' 
                 ORDER BY data_ultima_atualizacao DESC";
$stmt_fechados = $conexao->prepare($sql_fechados);
$stmt_fechados->bind_param("s", $usuario_logado);
$stmt_fechados->execute();
$stmt_fechados->bind_result($id, $titulo, $status, $data_ultima_atualizacao);
while ($stmt_fechados->fetch()) {
    $chamados_fechados[] = ['id' => $id, 'titulo' => $titulo, 'status' => $status, 'data_ultima_atualizacao' => $data_ultima_atualizacao];
}
$stmt_fechados->close();
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Meus Chamados - NUTIC-SVO</title>
    <link rel="stylesheet" href="css/style.css?v=3.0">
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
        <a href="novo_chamado.php" class="nav-button">Novo Chamado</a>
    </header>
    <h2 class="page-title">Painel de Chamados de <?php echo htmlspecialchars($usuario_logado); ?></h2>
    <div class="container">
        <div class="panel-left">
            <h2>Chamados em Andamento</h2>
            <div id="lista-chamados-abertos" class="chamados-list">
                <?php if (!empty($chamados_abertos)): ?>
                    <?php foreach($chamados_abertos as $chamado): ?>
                        <a href="ver_chamado.php?id=<?php echo $chamado['id']; ?>" data-ticket-id="<?php echo $chamado['id']; ?>" class="chamado-item status-<?php echo strtolower($chamado['status']); ?>">
                            <h3>#<?php echo $chamado['id']; ?> - <?php echo htmlspecialchars($chamado['titulo']); ?></h3>
                            <?php if (!empty($chamado['ultima_resposta_admin'])): ?>
                                <p class="ultima-resposta"><strong>Última do Suporte:</strong> <?php echo htmlspecialchars(substr($chamado['ultima_resposta_admin'], 0, 90)) . '...'; ?></p>
                            <?php endif; ?>
                            <small>Última atualização: <?php echo date('d/m/Y H:i', strtotime($chamado['data_ultima_atualizacao'])); ?> | Status: <strong><?php echo $chamado['status']; ?></strong></small>
                        </a>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p id="sem-chamados-msg">Nenhum chamado em aberto no momento.</p>
                <?php endif; ?>
            </div>
        </div>
        <div class="panel-right">
            <h2>Chamados Encerrados</h2>
             <div class="chamados-list">
                <?php if (!empty($chamados_fechados)): ?>
                    <?php foreach($chamados_fechados as $chamado): ?>
                        <a href="ver_chamado.php?id=<?php echo $chamado['id']; ?>" class="chamado-item status-fechado">
                             <h3>#<?php echo $chamado['id']; ?> - <?php echo htmlspecialchars($chamado['titulo']); ?></h3>
                             <small>Encerrado em: <?php echo date('d/m/Y H:i', strtotime($chamado['data_ultima_atualizacao'])); ?></small>
                        </a>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p>Nenhum chamado encerrado.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <footer class="instrucoes-footer">
        <h4>Como Abrir um Chamado de Suporte</h4>
        <p>
            <strong>1.</strong> Clique no botão <strong>"Novo Chamado"</strong> no topo da página.<br>
            <strong>2.</strong> Preencha o <strong>Título</strong> e a <strong>Descrição</strong> detalhada do problema.<br>
            <strong>3.</strong> Se um chamado for atualizado pelo suporte, ele aparecerá no topo da lista "Em Andamento" e ficará destacado.
        </p>
    </footer>
    <script>
        let ultimaAtualizacao = '<?php echo $data_base_verificacao; ?>';
        const listaChamados = document.getElementById('lista-chamados-abertos');
        async function verificarAtualizacoesPainel() {
            try {
                const response = await fetch(`api_updates.php?acao=checar_atualizacoes_usuario&ultima_atualizacao=${encodeURIComponent(ultimaAtualizacao)}&t=${Date.now()}`);
                const atualizacoes = await response.json();
                if (atualizacoes.length > 0) {
                    ultimaAtualizacao = atualizacoes[0].data_ultima_atualizacao;
                    atualizacoes.forEach(chamado => {
                        const itemExistente = document.querySelector(`a[data-ticket-id='${chamado.id}']`);
                        if (itemExistente) itemExistente.remove();
                        const chamadoHTML = `<a href="ver_chamado.php?id=${chamado.id}" data-ticket-id="${chamado.id}" class="chamado-item status-${chamado.status.toLowerCase()} destacado"><h3>#${chamado.id} - ${chamado.titulo}</h3>${chamado.ultima_resposta_admin ? `<p class="ultima-resposta"><strong>Última do Suporte:</strong> ${chamado.ultima_resposta_admin.substring(0, 90)}...</p>` : ''}<small>Última atualização: ${chamado.data_formatada} | Status: <strong>${chamado.status}</strong></small></a>`;
                        listaChamados.insertAdjacentHTML('afterbegin', chamadoHTML);
                    });
                }
            } catch (error) {
                console.error("Erro ao verificar atualizações do painel:", error);
            }
        }
        listaChamados.addEventListener('click', function(event) {
            const target = event.target.closest('.chamado-item.destacado');
            if (target) target.classList.remove('destacado');
        });
        setInterval(verificarAtualizacoesPainel, 7000);
    </script>
</body>
</html>