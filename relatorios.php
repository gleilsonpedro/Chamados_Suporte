<?php
session_start();
// GUARDA DE SEGURANÇA: Se a sessão de admin não existir ou for falsa, redireciona para o login
if (!isset($_SESSION['admin_logado']) || $_SESSION['admin_logado'] !== true) {
    header("Location: login_admin.php");
    exit();
}

require_once 'config.php';

// Função para formatar a diferença de tempo de forma legível
function formatarTempoDeAtendimento($segundos) {
    if ($segundos < 0 || $segundos === null) return 'N/A';
    $dias = floor($segundos / 86400);
    $segundos %= 86400;
    $horas = floor($segundos / 3600);
    $segundos %= 3600;
    $minutos = floor($segundos / 60);
    $resultado = '';
    if ($dias > 0) $resultado .= $dias . 'd ';
    if ($horas > 0) $resultado .= $horas . 'h ';
    if ($minutos > 0) $resultado .= $minutos . 'm';
    return trim($resultado) ?: '0m';
}

// Busca dados para os filtros
$usuarios = [];
$result_usuarios = $conexao->query("SELECT DISTINCT usuario_login FROM chamados ORDER BY usuario_login ASC");
if ($result_usuarios) { while($row = $result_usuarios->fetch_assoc()) { $usuarios[] = $row['usuario_login']; } }

$hostnames = [];
$result_hostnames = $conexao->query("SELECT DISTINCT hostname FROM chamados WHERE hostname IS NOT NULL AND hostname != '' ORDER BY hostname ASC");
if ($result_hostnames) { while($row = $result_hostnames->fetch_assoc()) { $hostnames[] = $row['hostname']; } }

$chamados_filtrados = [];
$total_segundos = 0;
$chamados_fechados_count = 0;
$tempo_medio_atendimento = 'N/A';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $sql = "SELECT id, usuario_login, hostname, titulo, status, data_abertura, data_fechamento FROM chamados WHERE 1=1";
    $params = [];
    $types = '';

    if (!empty($_POST['usuario'])) { $sql .= " AND usuario_login = ?"; $params[] = $_POST['usuario']; $types .= 's'; }
    if (!empty($_POST['hostname'])) { $sql .= " AND hostname = ?"; $params[] = $_POST['hostname']; $types .= 's'; }
    if (!empty($_POST['data_inicio'])) { $sql .= " AND data_abertura >= ?"; $params[] = $_POST['data_inicio'] . ' 00:00:00'; $types .= 's'; }
    if (!empty($_POST['data_fim'])) { $sql .= " AND data_abertura <= ?"; $params[] = $_POST['data_fim'] . ' 23:59:59'; $types .= 's'; }

    $sql .= " ORDER BY data_abertura DESC";
    
    $stmt = $conexao->prepare($sql);
    if (!empty($params)) {
        $bind_names = [];
        $bind_names[] = $types;
        for ($i = 0; $i < count($params); $i++) {
            $bind_names[] = &$params[$i];
        }
        call_user_func_array([$stmt, 'bind_param'], $bind_names);
    }
    $stmt->execute();
    
    $stmt->bind_result($id, $usuario_login, $hostname, $titulo, $status, $data_abertura, $data_fechamento);
    while ($stmt->fetch()) {
        $tempo_atendimento_segundos = null;
        if ($status == 'Fechado' && !empty($data_fechamento)) {
            $inicio = strtotime($data_abertura);
            $fim = strtotime($data_fechamento);
            $tempo_atendimento_segundos = $fim - $inicio;
            if ($tempo_atendimento_segundos >= 0) {
                $total_segundos += $tempo_atendimento_segundos;
                $chamados_fechados_count++;
            }
        }
        $chamados_filtrados[] = ['id' => $id, 'usuario_login' => $usuario_login, 'hostname' => $hostname, 'titulo' => $titulo, 'status' => $status, 'data_abertura' => $data_abertura, 'data_fechamento' => $data_fechamento, 'tempo_atendimento' => formatarTempoDeAtendimento($tempo_atendimento_segundos)];
    }
    $stmt->close();

    if ($chamados_fechados_count > 0) {
        $tempo_medio_segundos = $total_segundos / $chamados_fechados_count;
        $tempo_medio_atendimento = formatarTempoDeAtendimento($tempo_medio_segundos);
    }
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Relatórios - NUTIC-SVO</title>
    <link rel="stylesheet" href="css/style.css?v=final">
    <link rel="stylesheet" href="css/relatorios.css?v=final">
</head>
<body>
    <header class="site-header no-print">
        <div class="branding">
            <span class="logo">📊</span>
            <div class="titles">
                <h1>Gerador de Relatórios</h1>
                <p>NUTIC - SVO</p>
            </div>
        </div>
        <div>
            <a href="painel_admin.php" class="nav-button" style="margin-right: 10px;">Voltar ao Painel</a>
            <a href="logout.php" class="nav-button">Sair</a>
        </div>
    </header>
    
    <div class="container-full">
        <form action="relatorios.php" method="post" class="form-filtros no-print">
            <div class="filtro-item"><label for="usuario">Usuário:</label><select name="usuario" id="usuario"><option value="">Todos</option><?php foreach($usuarios as $user): ?><option value="<?php echo htmlspecialchars($user); ?>" <?php if(isset($_POST['usuario']) && $_POST['usuario'] == $user) echo 'selected'; ?>><?php echo htmlspecialchars($user); ?></option><?php endforeach; ?></select></div>
            <div class="filtro-item"><label for="hostname">Máquina:</label><select name="hostname" id="hostname"><option value="">Todas</option><?php foreach($hostnames as $host): ?><option value="<?php echo htmlspecialchars($host); ?>" <?php if(isset($_POST['hostname']) && $_POST['hostname'] == $host) echo 'selected'; ?>><?php echo htmlspecialchars($host); ?></option><?php endforeach; ?></select></div>
            <div class="filtro-item"><label for="data_inicio">De:</label><input type="date" name="data_inicio" id="data_inicio" value="<?php echo isset($_POST['data_inicio']) ? $_POST['data_inicio'] : ''; ?>"></div>
            <div class="filtro-item"><label for="data_fim">Até:</label><input type="date" name="data_fim" id="data_fim" value="<?php echo isset($_POST['data_fim']) ? $_POST['data_fim'] : ''; ?>"></div>
            <div class="filtro-item"><button type="submit" class="button">Gerar Relatório</button></div>
        </form>

        <?php if ($_SERVER["REQUEST_METHOD"] == "POST"): ?>
            <div class="area-relatorio">
                <div class="header-impressao">
                    <div class="branding">
                        <span class="logo">🛠️</span>
                        <div class="titles">
                            <h1>NUTIC - SVO</h1>
                            <p>by Gleilson Pedro</p>
                        </div>
                    </div>
                </div>
                <div class="header-relatorio">
                    <h2>Relatório de Chamados</h2>
                    <button onclick="window.print()" class="button no-print">Imprimir / Salvar PDF</button>
                </div>
                <div class="caixa-metricas">
                    <div>Total de Chamados: <strong><?php echo count($chamados_filtrados); ?></strong></div>
                    <div>Chamados Encerrados no Período: <strong><?php echo $chamados_fechados_count; ?></strong></div>
                    <div>Tempo Médio de Atendimento: <strong><?php echo $tempo_medio_atendimento; ?></strong></div>
                </div>
                <table class="tabela-relatorio">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Usuário</th>
                            <th>Título</th>
                            <th>Status</th>
                            <th>Abertura</th>
                            <th>Encerramento</th>
                            <th>Tempo de Atendimento (SLA)</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($chamados_filtrados)): ?>
                            <?php foreach ($chamados_filtrados as $chamado): ?>
                                <tr>
                                    <td><?php echo $chamado['id']; ?></td>
                                    <td><?php echo htmlspecialchars($chamado['usuario_login']); ?></td>
                                    <td><?php echo htmlspecialchars($chamado['titulo']); ?></td>
                                    <td><?php echo $chamado['status']; ?></td>
                                    <td><?php echo date('d/m/Y H:i', strtotime($chamado['data_abertura'])); ?></td>
                                    <td><?php echo !empty($chamado['data_fechamento']) ? date('d/m/Y H:i', strtotime($chamado['data_fechamento'])) : '-'; ?></td>
                                    <td><?php echo $chamado['tempo_atendimento']; ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="7">Nenhum chamado encontrado com os filtros selecionados.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>