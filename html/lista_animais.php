<?php
session_start();
if (!isset($_SESSION['usuario_id'])) {
    header('Location: recuperarConta.html');
    exit();
}
include '../db/connection.php';

$user_id     = intval($_SESSION['usuario_id']);
$nomeUsuario = htmlspecialchars($_SESSION['usuario_nome'] ?? 'Usuário');
$emailUsuario = htmlspecialchars($_SESSION['usuario_email'] ?? '');
$fazenda     = htmlspecialchars(trim($_SESSION['usuario_fazenda'] ?? '') !== '' ? $_SESSION['usuario_fazenda'] : 'Nenhuma propriedade cadastrada.');
$tipoUsuario = strtolower($_SESSION['usuario_tipo'] ?? 'produtor');

// Iniciais para o avatar
$partes = explode(' ', $nomeUsuario);
$iniciais = strtoupper(substr($partes[0], 0, 1));
if (count($partes) > 1) $iniciais .= strtoupper(substr(end($partes), 0, 1));

// --- Buscar animais ---
// Admin vê todos; produtor vê apenas os dos seus lotes
if ($tipoUsuario === 'admin' || $tipoUsuario === 'administrador') {
    $sqlAnimais = "SELECT a.id, a.nome, a.especie, a.sexo, a.raca, a.peso_kg, a.identificador, a.estado_atual,
                          l.nome AS lote_nome, l.id AS lote_id
                   FROM animal a
                   LEFT JOIN lote l ON a.lote_id = l.id
                   ORDER BY a.id DESC";
    $stmtA = mysqli_prepare($conexao, $sqlAnimais);
} else {
    $sqlAnimais = "SELECT a.id, a.nome, a.especie, a.sexo, a.raca, a.peso_kg, a.identificador, a.estado_atual,
                          l.nome AS lote_nome, l.id AS lote_id
                   FROM animal a
                   LEFT JOIN lote l ON a.lote_id = l.id
                   WHERE l.user_id = ? OR a.lote_id IS NULL
                   ORDER BY a.id DESC";
    $stmtA = mysqli_prepare($conexao, $sqlAnimais);
    mysqli_stmt_bind_param($stmtA, "i", $user_id);
}
mysqli_stmt_execute($stmtA);
$resAnimais = mysqli_stmt_get_result($stmtA);
$animais = [];
while ($an = mysqli_fetch_assoc($resAnimais)) $animais[] = $an;
mysqli_stmt_close($stmtA);

// Contagens
$totalAnimais = count($animais);
$caprinos     = count(array_filter($animais, fn($a) => strtolower($a['especie'] ?? '') === 'caprino'));
$ovinos       = count(array_filter($animais, fn($a) => strtolower($a['especie'] ?? '') === 'ovino'));
$machos       = count(array_filter($animais, fn($a) => strtolower($a['sexo'] ?? '') === 'macho'));
$femeas       = count(array_filter($animais, fn($a) => strtolower($a['sexo'] ?? '') === 'fêmea' || strtolower($a['sexo'] ?? '') === 'femea'));

// Notificações
$notificationCount = 0;
$stmt_notif = mysqli_prepare($conexao, "SELECT COUNT(*) FROM avisos WHERE destinatario_id IS NULL OR destinatario_id = ?");
if ($stmt_notif) {
    mysqli_stmt_bind_param($stmt_notif, "i", $user_id);
    mysqli_stmt_execute($stmt_notif);
    mysqli_stmt_bind_result($stmt_notif, $notificationCount);
    mysqli_stmt_fetch($stmt_notif);
    mysqli_stmt_close($stmt_notif);
}
?>
<!DOCTYPE html>
<html lang="pt-BR" data-theme="sistema">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lista de Animais - ControlCabra</title>
    <link rel="stylesheet" href="lista_animais.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        /* Sidebar brand com logo */
        .sidebar-brand {
            padding: 24px 20px;
            display: flex;
            align-items: center;
            gap: 12px;
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }
        .sidebar-logo {
            width: 52px;
            height: 52px;
            border-radius: 50%;
            object-fit: contain;
            flex-shrink: 0;
            background: rgba(255,255,255,0.1);
            padding: 2px;
        }
        .brand-text h2 { font-size: 1.1rem; font-weight: 700; margin: 0; }
        .brand-text p  { font-size: 0.78rem; color: rgba(255,255,255,0.65); margin: 2px 0 0; }

        /* Notif btn padrão */
        .notification-btn {
            position: relative;
            background: transparent;
            border: none;
            color: inherit;
            cursor: pointer;
            padding: 6px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .notification-btn .badge {
            position: absolute;
            top: -4px; right: -4px;
            background: #e53935;
            color: white;
            border-radius: 50%;
            font-size: 0.65rem;
            font-weight: 700;
            width: 16px; height: 16px;
            display: flex; align-items: center; justify-content: center;
        }

        /* Summary cards no topo */
        .summary-cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(140px, 1fr));
            gap: 12px;
            margin-bottom: 24px;
        }
        .summary-card {
            background: var(--card-bg);
            border: 1px solid var(--border-color);
            border-radius: 12px;
            padding: 18px 16px;
            text-align: center;
        }
        .summary-card .val {
            font-size: 2rem;
            font-weight: 700;
            color: var(--primary);
            line-height: 1;
        }
        .summary-card .lbl {
            font-size: 0.8rem;
            color: var(--text-muted);
            margin-top: 6px;
        }

        /* Page header row */
        .page-header-row {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 20px;
            gap: 12px;
            flex-wrap: wrap;
        }
        .btn-add {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 9px 18px;
            background: var(--primary);
            color: white;
            border: none;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 600;
            font-size: 0.88rem;
            white-space: nowrap;
            cursor: pointer;
            transition: background 0.2s;
        }
        .btn-add:hover { background: var(--sidebar-hover); }

        /* Badges de espécie */
        .especie-badge {
            display: inline-block;
            padding: 2px 10px;
            border-radius: 20px;
            font-size: 0.72rem;
            font-weight: 600;
        }
        .badge-caprino { background: #e8f5e9; color: #1b5e20; }
        .badge-ovino   { background: #e3f2fd; color: #0d47a1; }
        .badge-outro   { background: #f3e5f5; color: #6a1b9a; }

        .animal-meta { font-size: 0.79rem; color: var(--text-muted); margin-top: 3px; }
        .animal-name { font-weight: 600; font-size: 0.97rem; display: flex; align-items: center; gap: 8px; flex-wrap: wrap; }

        /* Search bar */
        .search-wrap {
            margin-bottom: 0;
        }
        .search-wrap input {
            width: 100%;
            padding: 10px 15px;
            border: 1px solid var(--border-color);
            border-radius: 8px;
            background: var(--bg-main);
            color: var(--text-dark);
            font-size: 0.9rem;
        }
        .search-wrap input:focus { outline: none; border-color: var(--primary); }

        .section-header-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 18px 20px 12px;
            border-bottom: 1px solid var(--border-color);
            gap: 12px;
            flex-wrap: wrap;
        }

        @media (max-width: 768px) {
            .page-header-row { flex-direction: column; }
        }
    </style>
</head>
<body>

    <header class="mobile-header">
        <div style="flex:1; display:flex; align-items:center; gap:10px; min-width:0;">
            <img src="logoControlCabra.png" alt="Logo" class="mobile-logo" style="flex-shrink:0; width:36px; height:36px; border-radius:50%; object-fit:contain; background:rgba(255,255,255,0.15); padding:2px;">
            <span class="mobile-page-title" style="font-size:1rem; font-weight:600; color:white; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">Lista de Animais</span>
        </div>
        <div style="display:flex; align-items:center; gap:6px; flex-shrink:0;">
            <button class="notification-btn" id="notificationBtn" aria-label="Notificações" style="color:white;">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"></path><path d="M13.73 21a2 2 0 0 1-3.46 0"></path></svg>
                <?php if ($notificationCount > 0): ?>
                    <span class="badge"><?= $notificationCount ?></span>
                <?php endif; ?>
            </button>
            <button class="menu-toggle" id="menuToggle" aria-label="Menu" style="background:none; border:none; color:white; cursor:pointer; padding:5px; display:flex;">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="3" y1="12" x2="21" y2="12"></line><line x1="3" y1="6" x2="21" y2="6"></line><line x1="3" y1="18" x2="21" y2="18"></line></svg>
            </button>
        </div>
    </header>

    <div class="app-container">
        <aside class="sidebar" id="sidebar">
            <div class="sidebar-brand">
                <img src="logoControlCabra.png" alt="Logo ControlCabra" class="sidebar-logo">
                <div class="brand-text">
                    <h2>ControlCabra</h2>
                    <p>Gestão Inteligente</p>
                </div>
            </div>
            <nav class="sidebar-nav">
                <a href="estatisticas.php" class="nav-item">Estatísticas</a>
                <a href="saude.php" class="nav-item">Saúde</a>
                <a href="cuidados.php" class="nav-item active">Cuidados</a>
                <?php if ($tipoUsuario !== 'visitante'): ?>
                    <a href="propriedades.php" class="nav-item">Propriedades</a>
                <?php endif; ?>
                <a href="configuracoes.php" class="nav-item">Configurações</a>
                <a href="administracao.php" class="nav-item">Administração</a>
            </nav>
            <div class="user-profile">
                <div class="user-avatar">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>
                </div>
                <div class="user-info">
                    <strong><?= $nomeUsuario ?></strong>
                    <span style="font-size:0.78rem; opacity:0.8; display:block; word-break:break-word;"><?= $fazenda ?></span>
                </div>
            </div>
        </aside>

        <div class="sidebar-overlay" id="sidebarOverlay"></div>

        <main class="main-content">

            <!-- Cabeçalho da página -->
            <div class="page-header-row">
                <div>
                    <h1 style="font-size:1.5rem; font-weight:700; color:var(--text-dark); display:flex; align-items:center; gap:8px; margin:0;">
                        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="var(--primary)" stroke-width="2"><line x1="8" y1="6" x2="21" y2="6"/><line x1="8" y1="12" x2="21" y2="12"/><line x1="8" y1="18" x2="21" y2="18"/><line x1="3" y1="6" x2="3.01" y2="6"/><line x1="3" y1="12" x2="3.01" y2="12"/><line x1="3" y1="18" x2="3.01" y2="18"/></svg>
                        Meu Rebanho
                    </h1>
                    <p style="color:var(--text-muted); font-size:0.88rem; margin:4px 0 0;">
                        Olá, <strong><?= $nomeUsuario ?></strong> — <?= $fazenda ?>
                    </p>
                </div>
                <?php if ($tipoUsuario !== 'visitante'): ?>
                <a href="identificacao.php" class="btn-add">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                    Novo Animal
                </a>
                <?php endif; ?>
            </div>

            <!-- Cards de resumo -->
            <div class="summary-cards">
                <div class="summary-card">
                    <div class="val"><?= $totalAnimais ?></div>
                    <div class="lbl">Total</div>
                </div>
                <div class="summary-card">
                    <div class="val" style="color:#2e7d32;"><?= $caprinos ?></div>
                    <div class="lbl">Caprinos</div>
                </div>
                <div class="summary-card">
                    <div class="val" style="color:#1565c0;"><?= $ovinos ?></div>
                    <div class="lbl">Ovinos</div>
                </div>
                <div class="summary-card">
                    <div class="val" style="color:#6a1b9a;"><?= $machos ?></div>
                    <div class="lbl">Machos</div>
                </div>
                <div class="summary-card">
                    <div class="val" style="color:#c62828;"><?= $femeas ?></div>
                    <div class="lbl">Fêmeas</div>
                </div>
            </div>

            <!-- Lista de animais -->
            <section class="animal-list-section">
                <div class="section-header-row">
                    <h2 class="card-title" style="margin:0; color:var(--text-dark);">Animais Cadastrados</h2>
                    <div style="display:flex; gap:10px; flex-wrap:wrap; flex:1; justify-content:flex-end;">
                        <div class="search-wrap" style="flex:1; max-width:320px; position:relative;">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="position:absolute; left:12px; top:50%; transform:translateY(-50%); color:#999;"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
                            <input type="text" id="searchAnimal" placeholder="Buscar por nome, ID, raça ou lote..." style="padding-left:38px;">
                        </div>
                        <select id="filterSelect" class="form-control" style="width:auto; min-width:180px; padding: 10px 14px; border: 1px solid var(--border-color); border-radius: 8px;">
                            <option value="todos">Mostrar todos</option>
                            <option value="caprino">Somente Caprinos</option>
                            <option value="ovino">Somente Ovinos</option>
                            <option value="macho">Somente Machos</option>
                            <option value="femea">Somente Fêmeas</option>
                            <option value="lote">Com lote vinculado</option>
                            <option value="sem_lote">Sem lote vinculado</option>
                        </select>
                    </div>
                </div>

                <div class="animal-list" id="animalList">
                    <?php if (empty($animais)): ?>
                        <div class="empty-state" style="margin:20px;">
                            Nenhum animal cadastrado ainda.
                            <?php if ($tipoUsuario !== 'visitante'): ?>
                                <small>Use o botão "Novo Animal" para registrar seu primeiro caprino ou ovino.</small>
                            <?php endif; ?>
                        </div>
                    <?php else: ?>
                        <?php foreach ($animais as $an):
                            $esp      = strtolower($an['especie'] ?? '');
                            $espClass = $esp === 'caprino' ? 'badge-caprino' : ($esp === 'ovino' ? 'badge-ovino' : 'badge-outro');
                            $loteInfo = !empty($an['lote_nome']) ? 'Lote: ' . htmlspecialchars($an['lote_nome']) : 'Sem lote';
                            $nomeAnimal = !empty($an['nome']) ? htmlspecialchars($an['nome']) : 'Animal #' . $an['id'];
                            $racaAnimal = !empty($an['raca']) ? htmlspecialchars($an['raca']) : '—';
                            $searchStr  = strtolower($an['nome'] . ' ' . $an['especie'] . ' ' . $an['raca'] . ' ' . ($an['lote_nome'] ?? '') . ' ' . $an['identificador']);
                        ?>
                        <div class="animal-item" data-search="<?= htmlspecialchars($searchStr) ?>" data-especie="<?= strtolower($an['especie']) ?>" data-sexo="<?= strtolower($an['sexo']) ?>" data-lote="<?= !empty($an['lote_nome']) ? 'sim' : 'nao' ?>">
                            <div class="animal-info">
                                <div class="animal-avatar">
                                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="var(--primary)" stroke-width="1.5"><circle cx="9" cy="12" r="1.5"/><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/><path d="M6.5 17H20a2 2 0 0 1 0 4H6.5"/><path d="M9 12H4a2 2 0 0 0-1.5 3.3L4 19.5"/><path d="M20 17V9a2 2 0 0 0-2-2h-3.5"/><path d="M14.5 7C14.5 5 16 3 18 3s3.5 2 3.5 4-1 4-3 4-4.5-1-4.5-1"/></svg>
                                </div>
                                <div>
                                    <div class="animal-name">
                                        <?= $nomeAnimal ?>
                                        <span class="especie-badge <?= $espClass ?>"><?= htmlspecialchars(ucfirst($an['especie'] ?? 'Desconhecido')) ?></span>
                                        <?php if (!empty($an['sexo'])): ?>
                                            <span class="especie-badge" style="background:#fce4ec; color:#880e4f;"><?= htmlspecialchars(ucfirst($an['sexo'])) ?></span>
                                        <?php endif; ?>
                                    </div>
                                    <div class="animal-meta">
                                        Raça: <?= $racaAnimal ?>
                                        <?php if (!empty($an['peso_kg'])): ?> · <?= number_format($an['peso_kg'], 1, ',', '') ?> Kg<?php endif; ?>
                                        · <?= $loteInfo ?>
                                    </div>
                                </div>
                            </div>
                            <?php if ($tipoUsuario !== 'visitante'): ?>
                            <div style="display:flex; gap:6px; flex-shrink:0;">
                                <a href="edicao_animal.html?id=<?= $an['id'] ?>" class="action-btn" title="Editar animal" style="text-decoration:none;">
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                                </a>
                            </div>
                            <?php endif; ?>
                        </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </section>

        </main>
    </div>

    <script>
        // Tema
        (function() {
            const t = localStorage.getItem('controlCabra-theme') || 'sistema';
            if (t === 'sistema') {
                document.documentElement.setAttribute('data-theme', window.matchMedia('(prefers-color-scheme: dark)').matches ? 'escuro' : 'claro');
            } else {
                document.documentElement.setAttribute('data-theme', t);
            }
        })();

        // Menu hambúrguer
        document.addEventListener('DOMContentLoaded', function() {
            const toggle = document.getElementById('menuToggle');
            const sidebar = document.getElementById('sidebar');
            const overlay = document.getElementById('sidebarOverlay');
            if (toggle && sidebar && overlay) {
                toggle.addEventListener('click', () => { sidebar.classList.toggle('active'); overlay.classList.toggle('active'); });
                overlay.addEventListener('click', () => { sidebar.classList.remove('active'); overlay.classList.remove('active'); });
            }
            // Busca
            const searchInput = document.getElementById('searchAnimal');
            const filterSelect = document.getElementById('filterSelect');
            
            function filterAnimals() {
                const q = searchInput ? searchInput.value.toLowerCase() : '';
                const f = filterSelect ? filterSelect.value : 'todos';
                
                document.querySelectorAll('.animal-item').forEach(item => {
                    const textMatch = (item.dataset.search || '').includes(q);
                    
                    let filterMatch = true;
                    if (f === 'caprino' && item.dataset.especie !== 'caprino') filterMatch = false;
                    if (f === 'ovino' && item.dataset.especie !== 'ovino') filterMatch = false;
                    if (f === 'macho' && item.dataset.sexo !== 'macho') filterMatch = false;
                    if (f === 'femea' && item.dataset.sexo !== 'fêmea' && item.dataset.sexo !== 'femea') filterMatch = false;
                    if (f === 'lote' && item.dataset.lote !== 'sim') filterMatch = false;
                    if (f === 'sem_lote' && item.dataset.lote !== 'nao') filterMatch = false;
                    
                    item.style.display = (textMatch && filterMatch) ? '' : 'none';
                });
            }

            if (searchInput) searchInput.addEventListener('input', filterAnimals);
            if (filterSelect) filterSelect.addEventListener('change', filterAnimals);
        });
    </script>
</body>
</html>
