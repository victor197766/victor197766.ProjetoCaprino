<?php
include_once('../db/connection.php');
session_start();
if (!isset($_SESSION['usuario_id'])) {
    header('Location: recuperarConta.html');
    exit();
}
// Fetch notifications for the current user
$query_avisos = "SELECT a.id, a.titulo, a.mensagem, a.data_criacao, u.username as destinatario, l.nome as lote_nome FROM avisos a LEFT JOIN usuario u ON a.destinatario_id = u.user_id LEFT JOIN lote l ON a.lote_id = l.id WHERE a.destinatario_id IS NULL OR a.destinatario_id = ? ORDER BY a.id DESC";
$stmt_avisos = mysqli_prepare($conexao, $query_avisos);
mysqli_stmt_bind_param($stmt_avisos, "i", $_SESSION['usuario_id']);
mysqli_stmt_execute($stmt_avisos);
$resultadoAvisos = mysqli_stmt_get_result($stmt_avisos);
$notificationCount = mysqli_num_rows($resultadoAvisos);

$nomeUsuario = htmlspecialchars($_SESSION['usuario_nome']);
$emailUsuario = htmlspecialchars($_SESSION['usuario_email']);
$fazenda = htmlspecialchars(trim($_SESSION['usuario_fazenda'] ?? '') !== '' ? $_SESSION['usuario_fazenda'] : 'Nenhuma propriedade cadastrada.');
$partes = explode(' ', $nomeUsuario);
$iniciais = strtoupper(substr($partes[0], 0, 1));
if (count($partes) > 1) {
    $iniciais .= strtoupper(substr(end($partes), 0, 1));
}
?>
<!DOCTYPE html>
<html lang="pt-BR" data-theme="sistema">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Saúde e Cuidados - ControlCabra</title>
    <link rel="stylesheet" href="cuidados.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
</head>

<body>

    <header class="mobile-header">
        <div class="mobile-header-left" style="width:auto; flex:1; display:flex; align-items:center; gap:10px; min-width:0;">
            <img src="logoControlCabra.png" alt="Logo" class="mobile-logo" style="flex-shrink:0;">
            <span class="mobile-page-title" style="text-align:left;">Cuidados</span>
        </div>
        <div class="mobile-header-right" style="width:auto; display:flex; align-items:center; gap:6px; flex-shrink:0;">
            <button class="notification-btn" id="notificationBtn" aria-label="Notificações">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"></path>
                    <path d="M13.73 21a2 2 0 0 1-3.46 0"></path>
                </svg>
                <?php if ($notificationCount > 0): ?>
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
        <div class="sidebar-overlay" id="sidebarOverlay"></div>
        <aside class="sidebar" id="sidebar">
            <div class="sidebar-brand">
                <img src="logoControlCabra.png" alt="Logo" class="sidebar-logo">
                <div class="brand-text">
                    <h2>ControlCabra</h2>
                    <p>Gestão Inteligente</p>
                </div>
            </div>

            <nav class="sidebar-nav">
                <a href="estatisticas.php" class="nav-item">Estatísticas</a>
                <a href="saude.php" class="nav-item">Saúde</a>
                <a href="cuidados.php" class="nav-item active">Cuidados</a>

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

        <main class="main-content">

            <div class="page-header header-health">
                <div>
                    <h1 class="page-title">
                        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="var(--primary)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="margin-right:8px; flex-shrink:0;"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/></svg>
                        Cuidados, Higiene e Monitoramento
                    </h1>
                    <p class="page-subtitle">Acompanhe e cuide do bem-estar do seu rebanho</p>
                </div>

                <div class="header-tools">
                    <button class="btn notification-btn" id="notificationBtnDesktop" aria-label="Notificações">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"></path>
                            <path d="M13.73 21a2 2 0 0 1-3.46 0"></path>
                        </svg>
                        <?php if ($notificationCount > 0): ?>
                            <span class="badge" id="notificationCountDesktop"><?php echo $notificationCount; ?></span>
                        <?php endif; ?>
                    </button>
                </div>
            </div>

            <div class="quick-actions-grid">
                <a href="identificacao.php" class="action-card">
                    <svg width="30" height="30" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20.59 13.41l-7.17 7.17a2 2 0 0 1-2.83 0L2 12V2h10l8.59 8.59a2 2 0 0 1 0 2.82z"/><line x1="7" y1="7" x2="7.01" y2="7"/></svg>
                    <span>Identificar um novo caprino</span>
                </a>
                <a href="busca_codigo.html" class="action-card">
                    <svg width="30" height="30" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                    <span>Pesquisar por código identificador</span>
                </a>
                <a href="agenda.html" class="action-card">
                    <svg width="30" height="30" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                    <span>Agenda de vacinação</span>
                </a>
                <a href="lista_animais.php" class="action-card">
                    <svg width="30" height="30" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="8" y1="6" x2="21" y2="6"/><line x1="8" y1="12" x2="21" y2="12"/><line x1="8" y1="18" x2="21" y2="18"/><line x1="3" y1="6" x2="3.01" y2="6"/><line x1="3" y1="12" x2="3.01" y2="12"/><line x1="3" y1="18" x2="3.01" y2="18"/></svg>
                    <span>Ver lista de animais</span>
                </a>
            </div>

            <div class="card mb-3">
                <div class="card-header">
                    <h2 class="card-title">Conhecer as doenças</h2>
                    <p class="card-subtitle">Informações sobre as principais doenças que podem afetar ovinos e caprinos.</p>
                </div>

                <div class="disease-scroll">
                    <div class="disease-card">
                        <div class="disease-img placeholder-img"></div>
                        <h3>Clostridiose</h3>
                        <p>Doença bacteriana grave que causa morte súbita. Afeta principalmente animais jovens.</p>
                        <a href="#" class="saiba-mais">Saiba mais &rarr;</a>
                    </div>
                    <div class="disease-card">
                        <img src="https://images.unsplash.com/photo-1484557985045-edf25e08da73?auto=format&fit=crop&q=80&w=300&h=200" alt="Ovinos" class="disease-img">
                        <h3>Verminoses</h3>
                        <p>Infestações por vermes gastrointestinais que causam anemia e perda de peso.</p>
                        <a href="#" class="saiba-mais">Saiba mais &rarr;</a>
                    </div>
                </div>

                <div class="mt-3">
                    <button class="btn btn-primary btn-block">Ver todas as doenças</button>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <h2 class="card-title">Avaliação dos animais</h2>
                    <p class="card-subtitle">Avalie o estado de saúde e informações básicas.</p>
                </div>

                <div class="stats-list">
                    <div class="stat-item success">
                        <div class="stat-header">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="var(--success)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
                            <span>Saudáveis</span>
                        </div>
                        <div class="stat-values">
                            <strong>0</strong>
                            <span class="percent">0%</span>
                        </div>
                    </div>

                    <div class="stat-item warning">
                        <div class="stat-header">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="var(--warning)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
                            <span>Atenção</span>
                        </div>
                        <div class="stat-values">
                            <strong>0</strong>
                            <span class="percent">0%</span>
                        </div>
                    </div>
                </div>

                <div class="empty-state mt-3">Nenhum dado de avaliação cadastrado ainda.</div>
            </div>

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
    <script src="cuidados.js"></script>
    <script src="notifications.js"></script>
</body>

</html>