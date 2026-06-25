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
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ControlCabra - Cuidados e Saúde</title>
    <link rel="stylesheet" href="saude.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
</head>
<body>
    <div class="app-container">
        <div class="sidebar-overlay" id="sidebarOverlay"></div>
        <header class="mobile-header">
            <img src="logoControlCabra.png" alt="Logo" class="mini-logo">
            <span>Saúde e Cuidados</span>
            <button id="menuToggle" class="icon-btn">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <line x1="3" y1="12" x2="21" y2="12"></line>
                    <line x1="3" y1="6" x2="21" y2="6"></line>
                    <line x1="3" y1="18" x2="21" y2="18"></line>
                </svg>
            </button>
            <button class="notification-btn btn btn-icon" id="notificationBtn" aria-label="Notificações">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"></path>
                    <path d="M13.73 21a2 2 0 0 1-3.46 0"></path>
                </svg>
                <?php if($notificationCount > 0): ?>
<span class="badge" id="notificationCount"><?php echo $notificationCount; ?></span>
<?php endif; ?>
            </button>
        </header>
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
                <a href="saude.php" class="nav-item active">Saúde</a>
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
        <main class="main-content">
            <header class="page-header">
                <div class="header-titles">
                    <h1 class="page-title">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M22 12h-4l-3 9L9 3l-3 9H2"></path>
                        </svg>
                        Saúde, Higiene e Monitoramento
                    </h1>
                    <p class="page-subtitle">Acompanhe e cuide do bem-estar do seu rebanho</p>
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
            <!-- Page specific content from original saude.html -->
            <div class="dashboard-grid">
                <section class="card card-doencas span-col-2">
                    <div class="card-header">
                        <h3 class="card-title">Conhecer as doenças</h3>
                        <p class="card-subtitle">Informações sobre as principais doenças que podem afetar ovinos e caprinos.</p>
                    </div>
                    <div class="doencas-slider">
                        <div class="doenca-card">
                            <div class="doenca-img bg-gray" style="background-image: url('https://images.unsplash.com/photo-1511216113906-8f56bb201c10?q=80&w=200&auto=format&fit=crop'); background-size: cover;"></div>
                            <h4>Clostridiose</h4>
                            <p>Doença bacteriana grave que causa morte súbita. Afeta principalmente animais jovens.</p>
                            <a href="#" class="link-action">Saiba mais &rarr;</a>
                        </div>
                        <div class="doenca-card">
                            <div class="doenca-img bg-gray" style="background-image: url('https://images.unsplash.com/photo-1484557985045-edf25e08da73?q=80&w=200&auto=format&fit=crop'); background-size: cover;"></div>
                            <h4>Verminoses</h4>
                            <p>Infestações por vermes gastrointestinais que causam anemia e perda de peso.</p>
                            <a href="#" class="link-action">Saiba mais &rarr;</a>
                        </div>
                        <div class="doenca-card">
                            <div class="doenca-img bg-gray" style="background-image: url('https://images.unsplash.com/photo-1542834759-4fa973b185fa?q=80&w=200&auto=format&fit=crop'); background-size: cover;"></div>
                            <h4>Ectima Contagioso</h4>
                            <p>Infecção viral que causa lesões na boca e ao redor das narinas.</p>
                            <a href="#" class="link-action">Saiba mais &rarr;</a>
                        </div>
                    </div>
                    <button class="btn btn-primary mt-3">Ver todas as doenças</button>
                </section>

                <section class="card card-gestantes">
                    <div class="card-header">
                        <h3 class="card-title">Avaliação em gestantes</h3>
                        <p class="card-subtitle">Acompanhe a gestação e garanta o bem-estar.</p>
                    </div>
                
                    <div class="gestantes-content">
                        <div class="gestante-icon">
                            <svg width="60" height="60" viewBox="0 0 24 24" fill="none" stroke="var(--primary)" stroke-width="1.5">
                                <path d="M12 21a9 9 0 1 0 0-18 9 9 0 0 0 0 18z"></path>
                                <path d="M12 8v4l3 3"></path>
                            </svg>
                        </div>
                
                        <ul>
                            <li>Diagnóstico de gestação</li>
                            <li>Acompanhamento</li>
                            <li>Nutrição adequada</li>
                            <li>Preparação para o parto</li>
                        </ul>
                    </div>
                
                    <div class="gestantes-extra">
                        <div class="gestante-info-box">
                            <span>Gestantes monitoradas</span>
                            <strong>0</strong>
                        </div>
                
                        <div class="gestante-info-box">
                            <span>Próximos partos</span>
                            <strong>0</strong>
                        </div>
                    </div>
                
                    <div class="gestantes-alerta">
                        <strong>Cuidados importantes</strong>
                        <p>Verifique alimentação, hidratação, vacinação e sinais de desconforto durante toda a gestação.</p>
                    </div>
                
                    <button class="btn btn-outline btn-block mt-3">Acompanhar gestantes</button>
                </section>
                <section class="card card-alimentacao span-col-full">
                    <div class="alimentacao-wrapper">
                        <div class="alimentacao-main">
                            <div class="card-header">
                                <h3 class="card-title">Controle de alimentação apropriada</h3>
                                <p class="card-subtitle">Garanta uma alimentação balanceada.</p>
                            </div>
                
                            <div class="alimentacao-content">
                                <div class="empty-state">Nenhum plano alimentar cadastrado ainda.<small>Os dados aparecerão aqui quando houver registros de alimentação.</small></div>
                            </div>
                        </div>
                    </div>
                </section>
                <section class="card card-avaliacao span-col-full">
                    <div class="card-header">
                        <h3 class="card-title">Avaliação dos animais</h3>
                        <p class="card-subtitle">Avalie o estado de saúde e informações básicas.</p>
                    </div>
                    <div class="status-overview">
                        <div class="status-box success">
                            <div class="status-icon"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg></div>
                            <div class="status-info">
                                <span>Saudáveis</span>
                                <strong>0</strong>
                            </div>
                            <span class="percent">0%</span>
                        </div>
                        <div class="status-box warning">
                            <div class="status-icon"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="12"></line><line x1="12" y1="16" x2="12.01" y2="16"></line></svg></div>
                            <div class="status-info">
                                <span>Atenção</span>
                                <strong>0</strong>
                            </div>
                            <span class="percent">0%</span>
                        </div>
                        <div class="status-box danger">
                            <div class="status-icon"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg></div>
                            <div class="status-info">
                                <span>Doentes</span>
                                <strong>0</strong>
                            </div>
                            <span class="percent">0%</span>
                        </div>
                    </div>
                    <div class="table-responsive">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Animal</th>
                                    <th>Espécie</th>
                                    <th>Idade</th>
                                    <th>Estado</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr><td colspan="4" style="text-align: center; padding: 20px;">Nenhum animal cadastrado ainda.</td></tr></tbody>
                        </table>
                    </div>
                    <button class="btn btn-outline btn-block mt-3">Ver todos os animais</button>
                </section>
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
    <script src="saude.js"></script>
    <script src="notifications.js"></script>
</body>
</html>
