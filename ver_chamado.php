<?php
session_start();
require_once 'config.php';

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) { die("Chamado não encontrado."); }
$chamado_id = $_GET['id'];
$is_admin = isset($_GET['admin']) && $_GET['admin'] == '1';

if (!$is_admin && (!isset($_SESSION['usuario_logado']) || empty($_SESSION['usuario_logado']))) { header("Location: index.php"); exit(); }

$stmt = $conexao->prepare("SELECT id, usuario_login, hostname, titulo, descricao, anexo_path, status, data_abertura, data_ultima_atualizacao FROM chamados WHERE id = ?");
$stmt->bind_param("i", $chamado_id);
$stmt->execute();
$stmt->bind_result($id, $usuario_login, $hostname, $titulo, $descricao, $anexo_path, $status, $data_abertura, $data_ultima_atualizacao);
$stmt->fetch();

if (!$id) { die("Chamado não encontrado com o ID fornecido."); }
$chamado = ['id' => $id, 'usuario_login' => $usuario_login, 'hostname' => $hostname, 'titulo' => $titulo, 'descricao' => $descricao, 'anexo_path' => $anexo_path, 'status' => $status, 'data_abertura' => $data_abertura, 'data_ultima_atualizacao' => $data_ultima_atualizacao];
$stmt->close();

if (!$is_admin && $chamado['usuario_login'] !== $_SESSION['usuario_logado']) { die("Acesso negado. Este chamado não pertence a você."); }

$interacoes_array = [];
$interacoes_stmt = $conexao->prepare("SELECT id, autor, mensagem, data_interacao FROM interacoes WHERE chamado_id = ? ORDER BY id ASC");
$interacoes_stmt->bind_param("i", $chamado_id);
$interacoes_stmt->execute();
$interacoes_stmt->bind_result($interacao_id, $interacao_autor, $interacao_mensagem, $interacao_data);
while ($interacoes_stmt->fetch()) { $interacoes_array[] = ['id' => $interacao_id, 'autor' => $interacao_autor, 'mensagem' => $interacao_mensagem, 'data_interacao' => $interacao_data]; }
$interacoes_stmt->close();

$ultima_interacao_id = !empty($interacoes_array) ? end($interacoes_array)['id'] : 0;
$autor_interacao = $is_admin ? "NUTIC-SVO" : $_SESSION['usuario_logado'];
$voltar_link = $is_admin ? "painel_admin.php" : "painel_usuario.php";
$voltar_texto = $is_admin ? "Voltar ao Painel Admin" : "Voltar aos Meus Chamados";
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Detalhes do Chamado #<?php echo $chamado['id']; ?></title>
    <link rel="stylesheet" href="css/style.css?v=3.2">
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
        <a href="<?php echo $voltar_link; ?>" class="nav-button"><?php echo $voltar_texto; ?></a>
    </header>
    <h2 class="page-title">Detalhes do Chamado #<?php echo $chamado['id']; ?></h2>
    <div class="container-full">
        <div class="chamado-detalhe">
            <p><strong>Usuário:</strong> <?php echo htmlspecialchars($chamado['usuario_login']); ?> (Hostname: <?php echo htmlspecialchars($chamado['hostname']); ?>)</p>
            <p><strong>Data de Abertura:</strong> <?php echo date('d/m/Y H:i', strtotime($chamado['data_abertura'])); ?></p>
            <p><strong>Status:</strong> <span id="status-display" class="status-<?php echo strtolower($chamado['status']); ?>"><?php echo $chamado['status']; ?></span></p>
            <div class="descricao">
                <h3>Descrição do Problema</h3>
                <p><?php echo nl2br(htmlspecialchars($chamado['descricao'])); ?></p>
            </div>
            <?php if ($chamado['anexo_path']): ?>
            <div class="anexo"><strong>Anexo:</strong> <a href="<?php echo htmlspecialchars($chamado['anexo_path']); ?>" target="_blank">Ver Imagem</a></div>
            <?php endif; ?>
        </div>
        <div id="lista-interacoes" class="interacoes">
            <h3>Histórico de Interações</h3>
            <?php foreach($interacoes_array as $interacao): ?>
            <div class="interacao-item <?php echo ($interacao['autor'] === 'NUTIC-SVO') ? 'nutic' : 'user'; ?>">
                <p><strong><?php echo htmlspecialchars($interacao['autor']); ?></strong> <small>em <?php echo date('d/m/Y H:i', strtotime($interacao['data_interacao'])); ?></small></p>
                <p><?php echo nl2br(htmlspecialchars($interacao['mensagem'])); ?></p>
            </div>
            <?php endforeach; ?>
             <?php if (empty($interacoes_array)): ?>
                <p id="sem-interacoes-msg">Nenhuma interação neste chamado ainda.</p>
            <?php endif; ?>
        </div>
        <?php if ($chamado['status'] !== 'Fechado'): ?>
        <div class="responder-chamado">
            <h3>Responder ou Atualizar</h3>
            <form action="salvar_interacao.php" method="post">
                <input type="hidden" name="chamado_id" value="<?php echo $chamado_id; ?>">
                <input type="hidden" name="autor" value="<?php echo $autor_interacao; ?>">
                <input type="hidden" name="is_admin" value="<?php echo $is_admin ? '1' : '0'; ?>">
                <textarea name="mensagem" rows="5" placeholder="Digite sua mensagem aqui..." required></textarea>
                <?php if ($is_admin): ?>
                <div class="admin-actions">
                    <label for="status">Alterar Status:</label>
                    <select name="novo_status" id="status">
                        <option value="Aberto" <?php if($chamado['status'] == 'Aberto') echo 'selected'; ?>>Aberto</option>
                        <option value="Pendente" <?php if($chamado['status'] == 'Pendente') echo 'selected'; ?>>Pendente</option>
                        <option value="Fechado" <?php if($chamado['status'] == 'Fechado') echo 'selected'; ?>>Fechar Chamado</option>
                    </select>
                </div>
                <?php endif; ?>
                <button type="submit" class="button">Enviar Mensagem</button>
            </form>
        </div>
        <?php endif; ?>
    </div>
    <?php if (!$is_admin): ?>
    <script>
        let ultimoIdInteracao = <?php echo $ultima_interacao_id; ?>;
        const chamadoId = <?php echo $chamado_id; ?>;
        const listaInteracoes = document.getElementById('lista-interacoes');
        const statusDisplay = document.getElementById('status-display');
        async function verificarAtualizacoes() {
            try {
                const response = await fetch(`api_updates.php?acao=atualizacoes_chamado&chamado_id=${chamadoId}&ultima_interacao_id=${ultimoIdInteracao}&t=${Date.now()}`);
                if (!response.ok) throw new Error(`Erro na rede: ${response.statusText}`);
                const data = await response.json();
                if (data.error) throw new Error(`Erro da API: ${data.error}`);
                if (data.status && statusDisplay.textContent !== data.status) {
                    statusDisplay.textContent = data.status;
                    statusDisplay.className = `status-${data.status.toLowerCase()}`;
                    statusDisplay.classList.add('novo-item'); 
                }
                if (data.novas_interacoes.length > 0) {
                    ultimoIdInteracao = data.novas_interacoes[data.novas_interacoes.length - 1].id;
                    const msgSemInteracoes = document.getElementById('sem-interacoes-msg');
                    if(msgSemInteracoes) msgSemInteracoes.remove();
                    data.novas_interacoes.forEach(interacao => {
                        const autorClasse = interacao.autor === 'NUTIC-SVO' ? 'nutic' : 'user';
                        const novaInteracaoHTML = `<div class="interacao-item ${autorClasse} novo-item"><p><strong>${interacao.autor}</strong> <small>em ${interacao.data_formatada}</small></p><p>${interacao.mensagem.replace(/\n/g, '<br>')}</p></div>`;
                        listaInteracoes.insertAdjacentHTML('beforeend', novaInteracaoHTML);
                    });
                }
            } catch (error) {
                console.error("Erro ao verificar atualizações do chamado:", error);
            }
        }
        if ('<?php echo $chamado['status']; ?>' !== 'Fechado') {
            setInterval(verificarAtualizacoes, 5000);
        }
    </script>
    <?php endif; ?>
</body>
</html>