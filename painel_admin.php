<?php
session_start();
// GUARDA DE SEGURANÇA: Se a sessão de admin não existir ou for falsa, redireciona para o login
if (!isset($_SESSION['admin_logado']) || $_SESSION['admin_logado'] !== true) {
    header("Location: login_admin.php");
    exit();
}

require_once 'config.php';

// Busca chamados Abertos e Pendentes
$chamados_abertos = [];
$sql_abertos = "SELECT id, usuario_login, hostname, titulo, descricao, data_abertura, status FROM chamados WHERE status IN ('Aberto', 'Pendente') ORDER BY data_ultima_atualizacao DESC";
$result_abertos = $conexao->query($sql_abertos);
if ($result_abertos) {
    while ($row = $result_abertos->fetch_assoc()) {
        $chamados_abertos[] = $row;
    }
}
$ultimo_id_aberto = 0;
if (!empty($chamados_abertos)) {
    $ids = array_map(function($chamado) { return $chamado['id']; }, $chamados_abertos);
    $ultimo_id_aberto = max($ids);
}

// Busca chamados Fechados
$chamados_fechados = [];
$sql_fechados = "SELECT id, usuario_login, hostname, titulo, descricao, data_abertura FROM chamados WHERE status = 'Fechado' ORDER BY data_ultima_atualizacao DESC";
$result_fechados = $conexao->query($sql_fechados);
if ($result_fechados) {
    while ($row = $result_fechados->fetch_assoc()) {
        $chamados_fechados[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Painel Admin - NUTIC-SVO</title>
    <link rel="stylesheet" href="css/style.css?v=final">
</head>
<body>
    <header class="site-header">
        <div class="branding">
            <span class="logo">🛠️</span>
            <div class="titles">
                <h1>Painel de Administração</h1>
                <p>Usuário: <?php echo htmlspecialchars($_SESSION['admin_username']); ?></p>
            </div>
        </div>
        <div>
            <a href="relatorios.php" class="nav-button" style="margin-right: 10px;">Gerar Relatórios</a>
            <a href="logout.php" class="nav-button">Sair</a>
        </div>
    </header>
    <div class="container">
        <div class="panel-left">
            <h2>Chamados Abertos / Pendentes</h2>
            <div id="lista-chamados-abertos" class="chamados-list">
                <?php if (!empty($chamados_abertos)): ?>
                    <?php foreach($chamados_abertos as $chamado): ?>
                        <a href="ver_chamado.php?id=<?php echo $chamado['id']; ?>&admin=1" class="chamado-item status-<?php echo strtolower($chamado['status']); ?>">
                            <h3>#<?php echo $chamado['id']; ?> - <?php echo htmlspecialchars($chamado['titulo']); ?></h3>
                            <p><strong>Usuário:</strong> <?php echo htmlspecialchars($chamado['usuario_login']); ?> (<?php echo htmlspecialchars($chamado['hostname']); ?>)</p>
                            <p><?php echo substr(htmlspecialchars($chamado['descricao']), 0, 80) . '...'; ?></p>
                            <small>Data: <?php echo date('d/m/Y H:i', strtotime($chamado['data_abertura'])); ?></small>
                        </a>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p id="sem-chamados-msg">Nenhum chamado aberto no momento.</p>
                <?php endif; ?>
            </div>
        </div>
        <div class="panel-right">
            <h2>Chamados Resolvidos</h2>
             <div class="chamados-list">
                <?php if (!empty($chamados_fechados)): ?>
                    <?php foreach($chamados_fechados as $chamado): ?>
                        <a href="ver_chamado.php?id=<?php echo $chamado['id']; ?>&admin=1" class="chamado-item status-fechado">
                             <h3>#<?php echo $chamado['id']; ?> - <?php echo htmlspecialchars($chamado['titulo']); ?></h3>
                            <p><strong>Usuário:</strong> <?php echo htmlspecialchars($chamado['usuario_login']); ?> (<?php echo htmlspecialchars($chamado['hostname']); ?>)</p>
                             <small>Data: <?php echo date('d/m/Y H:i', strtotime($chamado['data_abertura'])); ?></small>
                        </a>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p>Nenhum chamado fechado.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <script>
        let ultimoIdChamado = <?php echo $ultimo_id_aberto; ?>;
        const listaChamados = document.getElementById('lista-chamados-abertos');
        async function verificarNovosChamados() {
            try {
                const response = await fetch(`api_updates.php?acao=novos_chamados&ultimo_id=${ultimoIdChamado}&t=${Date.now()}`);
                if (!response.ok) throw new Error(`Erro na rede: ${response.statusText}`);
                const novosChamados = await response.json();
                if (novosChamados.length > 0) {
                    const maxId = Math.max(...novosChamados.map(c => c.id));
                    if (maxId > ultimoIdChamado) ultimoIdChamado = maxId;
                    const msgSemChamados = document.getElementById('sem-chamados-msg');
                    if(msgSemChamados) msgSemChamados.remove();
                    novosChamados.reverse().forEach(chamado => {
                        const novoChamadoHTML = `<a href="ver_chamado.php?id=${chamado.id}&admin=1" class="chamado-item novo-item status-${chamado.status.toLowerCase()}"><h3>#${chamado.id} - ${chamado.titulo}</h3><p><strong>Usuário:</strong> ${chamado.usuario_login} (${chamado.hostname})</p><p>${chamado.descricao.substring(0, 80)}...</p><small>Data: ${chamado.data_formatada}</small></a>`;
                        listaChamados.insertAdjacentHTML('afterbegin', novoChamadoHTML);
                    });
                }
            } catch (error) { 
                console.error("Erro ao verificar novos chamados:", error); 
            }
        }
        setInterval(verificarNovosChamados, 5000);
    </script>
</body>
</html>