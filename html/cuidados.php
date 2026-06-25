<?php
include '../db/connection.php';
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
    <script src="https://unpkg.com/@phosphor-icons/web"></script>
</head>
<body>

    <header class="mobile-header">
        <div class="mobile-header-left">
            <div class="mobile-logo">
                <i class="ph-fill ph-leaf" style="color: var(--primary); font-size: 24px;"></i>
            </div>
        </div>
        <div class="mobile-header-center">
            <span class="mobile-page-title">Saúde e Cuidados</span>
        </div>
        <div class="mobile-header-right">
            <button class="menu-toggle" id="menuToggle">
                <i class="ph ph-list" style="font-size: 28px;"></i>
            </button>
            <button class="notification-btn btn btn-icon" id="notificationBtn" aria-label="Notificações" style="border: none; background: transparent; padding: 6px; cursor: pointer;">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"></path>
                    <path d="M13.73 21a2 2 0 0 1-3.46 0"></path>
                </svg>
                <?php if($notificationCount > 0): ?>
<span class="badge" id="notificationCount" style="position: absolute; top: -4px; right: -4px; background: #e53935; color: #fff; border-radius: 50%; font-size: 0.7rem; width: 16px; height: 16px; display: flex; align-items: center; justify-content: center;"><?php echo $notificationCount; ?></span>
<?php endif; ?>
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
                    <h1 class="page-title"><i class="ph ph-pulse" style="color: var(--primary); margin-right: 8px;"></i> Cuidados, Higiene e Monitoramento</h1>
                    <p class="page-subtitle">Acompanhe e cuide do bem-estar do seu rebanho</p>
                </div>
                
                <div class="header-tools">
                    <button class="icon-btn notification-btn" id="notificationBtnDesktop" style="border: none; background: transparent; padding: 6px; cursor: pointer; position: relative; margin-right: 15px;">
                        <i class="ph ph-bell"></i>
                        <?php if($notificationCount > 0): ?>
<span class="badge" id="notificationCountDesktop" style="position: absolute; top: -4px; right: -4px; background: #e53935; color: #fff; border-radius: 50%; font-size: 0.7rem; width: 16px; height: 16px; display: flex; align-items: center; justify-content: center;"><?php echo $notificationCount; ?></span>
<?php endif; ?>
                    </button>
                </div>
            </div>

            <div class="quick-actions-grid">
                <a href="identificacao.html" class="action-card">
                    <i class="ph ph-tag"></i>
                    <span>Identificar um novo caprino</span>
                </a>
                <a href="#" class="action-card">
                    <i class="ph ph-magnifying-glass"></i>
                    <span>Pesquisar por código identificador</span>
                </a>
                <a href="#" class="action-card">
                    <i class="ph ph-syringe"></i>
                    <span>Agenda de vacinação</span>
                </a>
                <a href="lista_animais.html" class="action-card">
                    <i class="ph ph-list-numbers"></i>
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
                            <i class="ph ph-check-circle"></i>
                            <span>Saudáveis</span>
                        </div>
                        <div class="stat-values">
                            <strong>0</strong>
                            <span class="percent">0%</span>
                        </div>
                    </div>

                    <div class="stat-item warning">
                        <div class="stat-header">
                            <i class="ph ph-warning-circle"></i>
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
