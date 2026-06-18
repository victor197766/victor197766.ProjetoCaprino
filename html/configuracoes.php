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
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ControlCabra - Configurações</title>
    <link rel="stylesheet" href="configuracoes.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
</head>
<body>

    <header class="mobile-header">
        <div class="mobile-header-left">
            <img src="logoControlCabra.png" alt="Logo" class="mobile-logo">
        </div>
        <div class="mobile-header-center">
            <span class="mobile-page-title">Configurações e Sistema</span>
        </div>
        <div class="mobile-header-right">
            <button class="menu-toggle" id="menuToggle" aria-label="Abrir menu">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="3" y1="12" x2="21" y2="12"></line><line x1="3" y1="6" x2="21" y2="6"></line><line x1="3" y1="18" x2="21" y2="18"></line></svg>
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
                <a href="estatisticas.php" class="nav-item">Estatísticas</a>
                <a href="saude.php" class="nav-item">Saúde</a>
                <a href="cuidados.php" class="nav-item">Cuidados</a>
                <a href="configuracoes.php" class="nav-item active">Configurações</a>
            </nav>

            <div class="user-profile">
                <div class="user-avatar"><?php echo $iniciais; ?></div>
                <div class="user-info">
                    <strong><?php echo $nomeUsuario; ?></strong>
                    <span><?php echo $emailUsuario; ?></span>
                </div>
            </div>
        </aside>

        <div class="sidebar-overlay" id="sidebarOverlay"></div>

        <main class="main-content">
            <header class="page-header">
                <div>
                    <h1 class="page-title">Configurações e Atendimento</h1>
                    <p class="page-subtitle">Gerencie suas preferências, conta e obtenha suporte</p>
                </div>
            </header>

            <div class="tabs-container">
                <nav class="tabs">
                    <a href="#" class="tab active" data-tab="aparencia">Aparência</a>
                    <a href="#" class="tab" data-tab="conta">Conta</a>
                </nav>
            </div>

            <!-- ===== ABA APARÊNCIA ===== -->
            <div class="tab-content active" id="tab-aparencia">
                <div class="settings-grid settings-grid-single">
                    <div class="settings-column">
                        <section class="card">
                            <div class="card-header">
                                <h3 class="card-title">Aparência do Sistema</h3>
                                <p class="card-subtitle">Personalize a aparência do sistema</p>
                            </div>

                            <div class="setting-group">
                                <label class="setting-label">Tema</label>
                                <div class="options-row">
                                    <button class="option-btn" data-group="theme" data-tema="claro">
                                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="5"></circle><line x1="12" y1="1" x2="12" y2="3"></line><line x1="12" y1="21" x2="12" y2="23"></line><line x1="4.22" y1="4.22" x2="5.64" y2="5.64"></line><line x1="18.36" y1="18.36" x2="19.78" y2="19.78"></line><line x1="1" y1="12" x2="3" y2="12"></line><line x1="21" y1="12" x2="23" y2="12"></line><line x1="4.22" y1="19.78" x2="5.64" y2="18.36"></line><line x1="18.36" y1="5.64" x2="19.78" y2="4.22"></line></svg> Claro
                                    </button>
                                    <button class="option-btn" data-group="theme" data-tema="escuro">
                                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"></path></svg> Escuro
                                    </button>
                                    <button class="option-btn" data-group="theme" data-tema="sistema">
                                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="3" width="20" height="14" rx="2" ry="2"></rect><line x1="8" y1="21" x2="16" y2="21"></line><line x1="12" y1="17" x2="12" y2="21"></line></svg> Sistema
                                    </button>
                                </div>
                            </div>

                            <button class="btn btn-primary mt-3">Salvar alterações</button>
                        </section>
                    </div>
                </div>
            </div>

            <!-- ===== ABA CONTA ===== -->
            <div class="tab-content" id="tab-conta">
                <div class="settings-grid settings-grid-dual">

                    <!-- COLUNA 1: Conta, Informação Empresarial e Dados -->
                    <div class="settings-column">
                        <section class="card mb-3">
                            <div class="card-header">
                                <h3 class="card-title">Conta, Informação Empresarial e Dados</h3>
                                <p class="card-subtitle">Gerencie suas informações e dados da empresa</p>
                            </div>
                            
                            <div class="action-list">
                                <a href="#" class="action-list-item">
                                    <div class="action-icon"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg></div>
                                    <div class="action-text">
                                        <strong>Informações do Perfil</strong>
                                        <span>Atualize seus dados pessoais</span>
                                    </div>
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="9 18 15 12 9 6"></polyline></svg>
                                </a>
                                <a href="#" class="action-list-item">
                                    <div class="action-icon"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect><line x1="3" y1="9" x2="21" y2="9"></line><line x1="9" y1="21" x2="9" y2="9"></line></svg></div>
                                    <div class="action-text">
                                        <strong>Informações Empresariais</strong>
                                        <span>Dados da sua propriedade e empresa</span>
                                    </div>
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="9 18 15 12 9 6"></polyline></svg>
                                </a>
                                <a href="#" class="action-list-item">
                                    <div class="action-icon"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="1" y="4" width="22" height="16" rx="2" ry="2"></rect><line x1="1" y1="10" x2="23" y2="10"></line></svg></div>
                                    <div class="action-text">
                                        <strong>Dados Bancários</strong>
                                        <span>Contas e informações de pagamento</span>
                                    </div>
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="9 18 15 12 9 6"></polyline></svg>
                                </a>
                            </div>
                        </section>

                        <!-- Card unificado de Ações da Conta com botões lado a lado -->
                        <section class="card mb-3">
                            <div class="card-header">
                                <h3 class="card-title">Ações da Conta</h3>
                                <p class="card-subtitle">Gerencie sua conta e preferências de acesso</p>
                            </div>
                            <div class="action-buttons-row">
                                <button id="btnSuspender" class="btn btn-outline-warning">
                                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"></path><line x1="12" y1="9" x2="12" y2="13"></line><line x1="12" y1="17" x2="12.01" y2="17"></line></svg>
                                    Suspender conta
                                </button>
                                <button id="btnDeletar" class="btn btn-outline-error">
                                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path></svg>
                                    Deletar conta
                                </button>
                                <button id="btnSair" class="btn btn-outline-secondary">
                                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path><polyline points="16 17 21 12 16 7"></polyline><line x1="21" y1="12" x2="9" y2="12"></line></svg>
                                    Sair da conta
                                </button>
                            </div>
                        </section>
                    </div>

                    <!-- COLUNA 2: Notificações -->
                    <div class="settings-column">
                        <section class="card">
                            <div class="card-header">
                                <h3 class="card-title">Notificações</h3>
                                <p class="card-subtitle">Escolha como e quando receber notificações</p>
                            </div>
                            
                            <div class="toggle-list">
                                <div class="toggle-item">
                                    <div class="toggle-info">
                                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"></path><path d="M13.73 21a2 2 0 0 1-3.46 0"></path></svg>
                                        <div>
                                            <strong>Lembretes de manejo</strong>
                                            <span>Receba lembretes sobre atividades</span>
                                        </div>
                                    </div>
                                    <label class="toggle-switch">
                                        <input type="checkbox" checked>
                                        <span class="slider"></span>
                                    </label>
                                </div>
                                <div class="toggle-item">
                                    <div class="toggle-info">
                                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path><path d="M12 8v4"></path><path d="M12 16h.01"></path></svg>
                                        <div>
                                            <strong>Alertas de saúde</strong>
                                            <span>Notificações sobre problemas de saúde</span>
                                        </div>
                                    </div>
                                    <label class="toggle-switch">
                                        <input type="checkbox" checked>
                                        <span class="slider"></span>
                                    </label>
                                </div>
                            </div>
                        </section>
                    </div>

                </div>
            </div>

        </main>
    </div>

    <script src="configuracoes.js"></script>
    <script>
    // Tab switching logic
    document.querySelectorAll('.tabs .tab').forEach(tab => {
        tab.addEventListener('click', function(e) {
            e.preventDefault();
            // Remove active from all tabs
            document.querySelectorAll('.tabs .tab').forEach(t => t.classList.remove('active'));
            // Hide all tab contents
            document.querySelectorAll('.tab-content').forEach(tc => tc.classList.remove('active'));
            // Activate clicked tab
            this.classList.add('active');
            // Show corresponding content
            const targetId = 'tab-' + this.dataset.tab;
            document.getElementById(targetId).classList.add('active');
        });
    });
    </script>
</body>
</html>