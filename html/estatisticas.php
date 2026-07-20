<?php
session_start();
if (!isset($_SESSION['usuario_id'])) {
    header('Location: recuperarConta.html');
    exit();
}

// Conexão com o banco (Ajuste o caminho se necessário)
include 'db/connection.php';

$usuario_id = $_SESSION['usuario_id'];
$nomeUsuario = htmlspecialchars($_SESSION['usuario_nome']);
$fazenda = htmlspecialchars(trim($_SESSION['usuario_fazenda'] ?? '') !== '' ? $_SESSION['usuario_fazenda'] : 'Nenhuma propriedade cadastrada.');

// ==========================================
// CONSULTA PARA AVISOS DO SISTEMA
// ==========================================
$query_avisos = "SELECT id, titulo, mensagem, data_criacao FROM avisos WHERE destinatario_id IS NULL OR destinatario_id = ? ORDER BY data_criacao DESC LIMIT 5";
$stmt_avisos = mysqli_prepare($conexao, $query_avisos);
mysqli_stmt_bind_param($stmt_avisos, "i", $usuario_id);
mysqli_stmt_execute($stmt_avisos);
$resultadoAvisos = mysqli_stmt_get_result($stmt_avisos);
$notificationCount = mysqli_num_rows($resultadoAvisos);

// ==========================================
// CONSULTAS PARA OS CARDS DE RESUMO
// ==========================================

// 1. Total de Animais do Usuário (juntando animal -> lote -> usuario)
$query_animais = "SELECT COUNT(a.id) as total FROM animal a INNER JOIN lote l ON a.lote_id = l.id WHERE l.user_id = ?";
$stmt = mysqli_prepare($conexao, $query_animais);
mysqli_stmt_bind_param($stmt, "i", $usuario_id);
mysqli_stmt_execute($stmt);
$total_animais = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt))['total'] ?? 0;

// 2. Nascimentos no período (usando a tabela nascimento ligada ao lote do usuário)
$query_nasc = "SELECT COUNT(n.id) as total FROM nascimento n INNER JOIN lote l ON n.lote_id = l.id WHERE l.user_id = ?";
$stmt = mysqli_prepare($conexao, $query_nasc);
mysqli_stmt_bind_param($stmt, "i", $usuario_id);
mysqli_stmt_execute($stmt);
$total_nascimentos = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt))['total'] ?? 0;

// 3. Mortes (Animais que possuem MORTE_id preenchido)
$query_mortes = "SELECT COUNT(a.id) as total FROM animal a INNER JOIN lote l ON a.lote_id = l.id WHERE a.MORTE_id IS NOT NULL AND a.MORTE_id > 0 AND l.user_id = ?";
$stmt = mysqli_prepare($conexao, $query_mortes);
mysqli_stmt_bind_param($stmt, "i", $usuario_id);
mysqli_stmt_execute($stmt);
$total_mortes = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt))['total'] ?? 0;

// 4. Cálculo da Taxa de Mortalidade
$taxa_mortalidade = ($total_animais > 0) ? ($total_mortes / $total_animais) * 100 : 0;

// ==========================================
// CONSULTA PARA A TABELA DE PRODUÇÃO
// ==========================================
// Agrupa as produções por lote e soma os valores dependendo do tipo
$query_producao = "
    SELECT 
        l.nome AS lote_nome, 
        l.tipo AS lote_tipo,
        SUM(CASE WHEN p.TIPO = 'Fibra' THEN p.QUANTIDADE_KG ELSE 0 END) AS total_fibra,
        SUM(CASE WHEN p.TIPO = 'Pele' THEN p.QUANTIDADE_KG ELSE 0 END) AS total_pele,
        SUM(CASE WHEN p.TIPO = 'Leite' THEN p.QUANTIDADE_KG ELSE 0 END) AS total_leite,
        SUM(CASE WHEN p.TIPO = 'Carne' THEN p.QUANTIDADE_KG ELSE 0 END) AS total_carne
    FROM lote l
    LEFT JOIN producao p ON l.id = p.lote_id
    WHERE l.user_id = ?
    GROUP BY l.id
";
$stmt_tabela = mysqli_prepare($conexao, $query_producao);
mysqli_stmt_bind_param($stmt_tabela, "i", $usuario_id);
mysqli_stmt_execute($stmt_tabela);
$resultado_producao = mysqli_stmt_get_result($stmt_tabela);

// Mapeamento dos tipos de lote para exibição formatada
$tipos_lote = [
    'producao_la'     => 'Produção de Lã',
    'producao_leite'  => 'Produção de Leite',
    'producao_carne'  => 'Produção de Carne',
    'producao_pele'   => 'Produção de Pele',
    'misto'           => 'Misto',
    'reproducao'      => 'Reprodução',
    'engorda'         => 'Engorda',
];

?>
<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ControlCabra - Estatísticas</title>
    <link rel="stylesheet" href="estatisticas.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
</head>

<body>

    <header class="mobile-header">
        <div class="mobile-header-left" style="width:auto; flex:1; display:flex; align-items:center; gap:10px; min-width:0;">
            <img src="logoControlCabra.png" alt="Logo" class="mobile-logo" style="flex-shrink:0;">
            <span class="mobile-page-title" style="text-align:left;">Estatísticas</span>
        </div>
        <div class="mobile-header-right" style="width:auto; display:flex; align-items:center; gap:6px; flex-shrink:0;">
            <button class="notification-btn btn btn-icon" id="notificationBtn" aria-label="Notificações">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/>
                    <path d="M13.73 21a2 2 0 0 1-3.46 0"/>
                </svg>
                <?php if($notificationCount > 0): ?>
<span class="badge" id="notificationCount"><?php echo $notificationCount; ?></span>
<?php endif; ?>
            </button>
            <button class="menu-toggle" id="menuToggle" aria-label="Abrir menu">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <line x1="3" y1="12" x2="21" y2="12"></line>
                    <line x1="3" y1="6" x2="21" y2="6"></line>
                    <line x1="3" y1="18" x2="21" y2="18"></line>
                </svg>
            </button>
        </div>
    </header>

    <div class="app-container">
        <aside class="sidebar" id="sidebar">
            <div class="sidebar-brand">
                <img src="logoControlCabra.png" alt="Logo" class="sidebar-logo">
                <div class="brand-text">
                    <h2>ControlCabra</h2>
                    <p>Gestão Inteligente</p>
                </div>
            </div>

            <nav class="sidebar-nav">
                <a href="#" class="nav-item active">Estatísticas</a>
                <a href="saude.php" class="nav-item">Saúde</a>
                <a href="cuidados.php" class="nav-item">Cuidados</a>

                <?php if (!isset($_SESSION['usuario_tipo']) || strtolower($_SESSION['usuario_tipo']) !== 'visitante'): ?>
                    <a href="propriedades.php" class="nav-item">Propriedades</a>
                <?php endif; ?>

                <a href="configuracoes.php" class="nav-item">Configurações</a>
                <a href="administracao.php" class="nav-item">Administração</a>
            </nav>

            <div class="user-profile">
                <div class="user-avatar">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                        <circle cx="12" cy="7" r="4"></circle>
                    </svg>
                </div>
                <div class="user-info">
                    <strong><?= $nomeUsuario ?></strong>
                    <span><?= $fazenda ?></span>
                </div>
            </div>
        </aside>

        <div class="sidebar-overlay" id="sidebarOverlay"></div>

        <main class="main-content">
            <header class="page-header">
                <div>
                    <h1 class="page-title">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M18 20V10M12 20V4M6 20v-6"></path>
                        </svg>
                        Estatísticas
                    </h1>
                    <p class="page-subtitle">Acompanhe o desempenho dos seus lotes</p>
                </div>
                <div class="header-actions">
                    <button class="btn btn-icon notification-btn" id="notificationBtnDesktop" aria-label="Notificações">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"></path>
                            <path d="M13.73 21a2 2 0 0 1-3.46 0"></path>
                        </svg>
                        <?php if($notificationCount > 0): ?>
                        <span class="badge" id="notificationCountDesktop"><?php echo $notificationCount; ?></span>
                        <?php endif; ?>
                    </button>
                </div>
            </header>

            <section class="summary-grid mt-4">
                <div class="summary-card">
                    <div class="icon-box bg-green-light">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#2a7144" stroke-width="2">
                            <path d="M12 4v16m8-8H4"></path>
                        </svg>
                    </div>
                    <div class="summary-info">
                        <span class="label">Quantidade</span>
                        <div class="value"><?= $total_animais ?> <span class="unit">animais</span></div>
                        <span class="desc">Total cadastrado</span>
                    </div>
                </div>

                <div class="summary-card">
                    <div class="icon-box bg-green-light">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#2a7144" stroke-width="2">
                            <circle cx="12" cy="12" r="10"></circle>
                            <path d="M12 8v8m-4-4h8"></path>
                        </svg>
                    </div>
                    <div class="summary-info">
                        <span class="label">Nascimentos</span>
                        <div class="value"><?= $total_nascimentos ?> <span class="unit">nascimentos</span></div>
                        <span class="desc">Total cadastrado</span>
                    </div>
                </div>

                <div class="summary-card">
                    <div class="icon-box bg-red-light">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#d32f2f" stroke-width="2">
                            <path d="M3 3h18v18H3zM9 9h6v6H9z"></path>
                        </svg>
                    </div>
                    <div class="summary-info">
                        <span class="label">Mortes</span>
                        <div class="value"><?= $total_mortes ?> <span class="unit">mortes</span></div>
                        <span class="desc">Total cadastrado</span>
                    </div>
                </div>

                <div class="summary-card">
                    <div class="icon-box bg-yellow-light">
                        <span style="font-weight: bold; color: #f57c00; font-size: 1.2rem;">%</span>
                    </div>
                    <div class="summary-info">
                        <span class="label">Taxa de Mortalidade</span>
                        <div class="value"><?= number_format($taxa_mortalidade, 2, ',', '.') ?>% <span class="unit">do total</span></div>
                        <span class="desc">Histórico geral</span>
                    </div>
                </div>
            </section>

            <section class="card production-section mt-4">
                <h3 class="card-title">Produção Por Lote (Fibra, Pele, Leite e Carne)</h3>
                <div style="width: 100%;">
                    <div class="table-container" style="overflow-x: auto; width: 100%;">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Lote</th>
                                    <th>Tipo</th>
                                    <th>Fibra (kg)</th>
                                    <th>Pele (unid.)</th>
                                    <th>Leite (L)</th>
                                    <th>Carne (kg)</th>
                                    <th>Soma Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $soma_fibra = $soma_pele = $soma_leite = $soma_carne = 0;

                                $tem_producao = mysqli_num_rows($resultado_producao) > 0;

                                if ($tem_producao):
                                    while ($linha = mysqli_fetch_assoc($resultado_producao)):
                                        $soma_fibra += $linha['total_fibra'];
                                        $soma_pele  += $linha['total_pele'];
                                        $soma_leite += $linha['total_leite'];
                                        $soma_carne += $linha['total_carne'];

                                        $total_linha = $linha['total_fibra'] + $linha['total_pele'] + $linha['total_leite'] + $linha['total_carne'];
                                ?>
                                        <tr>
                                            <td><?= htmlspecialchars($linha['lote_nome']) ?></td>
                                            <td><?= htmlspecialchars($tipos_lote[$linha['lote_tipo']] ?? ucwords(str_replace('_', ' ', $linha['lote_tipo']))) ?></td>
                                            <td><?= number_format($linha['total_fibra'], 1, ',', '.') ?></td>
                                            <td><?= number_format($linha['total_pele'], 0, ',', '.') ?></td>
                                            <td><?= number_format($linha['total_leite'], 1, ',', '.') ?></td>
                                            <td><?= number_format($linha['total_carne'], 1, ',', '.') ?></td>
                                            <td><strong><?= number_format($total_linha, 1, ',', '.') ?></strong></td>
                                        </tr>
                                    <?php
                                    endwhile;
                                else:
                                    ?>
                                    <tr>
                                        <td colspan="7" style="text-align: center;">Nenhum dado de produção disponível ainda.</td>
                                    </tr>
                                <?php endif; ?>

                                <?php if ($tem_producao): ?>
                                <tr class="total-row">
                                    <td>Total Geral</td>
                                    <td></td>
                                    <td><?= number_format($soma_fibra, 1, ',', '.') ?></td>
                                    <td><?= number_format($soma_pele, 0, ',', '.') ?></td>
                                    <td><?= number_format($soma_leite, 1, ',', '.') ?></td>
                                    <td><?= number_format($soma_carne, 1, ',', '.') ?></td>
                                    <td><strong><?= number_format(($soma_fibra + $soma_pele + $soma_leite + $soma_carne), 1, ',', '.') ?></strong></td>
                                </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </section>
        </main>
    </div>

    <!-- Notification Dropdown -->
    <div class="notification-dropdown" id="notificationModal">
        <div class="notification-dropdown-content">
            <h3 class="notification-dropdown-title">Notificações</h3>
            <div id="notificationList">
                <?php if (mysqli_num_rows($resultadoAvisos) > 0): ?>
                    <ul class="notification-ul">
                        <?php while ($aviso = mysqli_fetch_assoc($resultadoAvisos)): ?>
                            <li class="notification-item" data-status="unread">
                                <strong><?php echo date('d/m/Y H:i', strtotime($aviso['data_criacao'])); ?> - <?php echo htmlspecialchars($aviso['titulo'] ?? 'Aviso'); ?>:</strong><br>
                                <?php echo htmlspecialchars($aviso['mensagem']); ?>
                            </li>
                        <?php endwhile; ?>
                    </ul>
                <?php else: ?>
                    <p style="color: var(--text-muted); text-align: center; padding: 12px 0;">Nenhuma notificação pendente!</p>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="estatisticas.js"></script>
    <script src="notifications.js"></script>
</body>

</html>
<?php
// Encerrar conexões
if (isset($stmt)) mysqli_stmt_close($stmt);
if (isset($stmt_tabela)) mysqli_stmt_close($stmt_tabela);
mysqli_close($conexao);
?>