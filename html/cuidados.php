<?php
session_start();
if (!isset($_SESSION['usuario_id'])) {
    header('Location: recuperarConta.html');
    exit();
}
$nomeUsuario = htmlspecialchars($_SESSION['usuario_nome']);
$emailUsuario = htmlspecialchars($_SESSION['usuario_email']);
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
        </div>
    </header>

    <div class="app-container">
        <div class="sidebar-overlay" id="sidebarOverlay"></div>
        <aside class="sidebar" id="sidebar">
            <div class="sidebar-brand">
                <div class="brand-text">
                    <h2>ControlCabra</h2>
                    <p>Gestão de Rebanho</p>
                </div>
            </div>
            
            <nav class="sidebar-nav">
                <a href="estatisticas.php" class="nav-item">Estatísticas</a>
                <a href="saude.php" class="nav-item">Saúde</a>
                <a href="cuidados.php" class="nav-item active">Cuidados</a>
                <a href="configuracoes.php" class="nav-item">Configurações</a>
            </nav>

            <div class="user-profile">
                <div class="user-avatar"><?php echo $iniciais; ?></div>
                <div class="user-info">
                    <strong><?php echo $nomeUsuario; ?></strong>
                    <span><?php echo $emailUsuario; ?></span>
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
                    <button class="icon-btn notification-btn">
                        <i class="ph ph-bell"></i>
                        <span class="badge">3</span>
                    </button>
                    <button class="btn btn-outline"><i class="ph ph-question"></i> Ajuda</button>
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
                            <strong>128</strong>
                            <span class="percent">82%</span>
                        </div>
                    </div>

                    <div class="stat-item warning">
                        <div class="stat-header">
                            <i class="ph ph-warning-circle"></i>
                            <span>Atenção</span>
                        </div>
                        <div class="stat-values">
                            <strong>18</strong>
                            <span class="percent">12%</span>
                        </div>
                    </div>
                </div>
            </div>

        </main>
    </div>

    <script src="cuidados.js"></script>
</body>
</html>
