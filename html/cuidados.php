<?php
session_start();
if (!isset($_SESSION['usuario_id'])) {
    header('Location: recuperarConta.html');
    exit();
}
include_once('db/connection.php');

$user_id     = intval($_SESSION['usuario_id']);
$nomeUsuario = htmlspecialchars($_SESSION['usuario_nome'] ?? 'Usuário');
$fazenda     = htmlspecialchars(trim($_SESSION['usuario_fazenda'] ?? '') !== '' ? $_SESSION['usuario_fazenda'] : 'Nenhuma propriedade cadastrada.');
$tipoUsuario = strtolower($_SESSION['usuario_tipo'] ?? 'produtor');

$partes  = explode(' ', $nomeUsuario);
$iniciais = strtoupper(substr($partes[0], 0, 1));
if (count($partes) > 1) $iniciais .= strtoupper(substr(end($partes), 0, 1));

// Notificações
$query_avisos = "SELECT a.id, a.titulo, a.mensagem, a.data_criacao FROM avisos a WHERE a.destinatario_id IS NULL OR a.destinatario_id = ? ORDER BY a.id DESC";
$stmt_avisos = mysqli_prepare($conexao, $query_avisos);
mysqli_stmt_bind_param($stmt_avisos, "i", $user_id);
mysqli_stmt_execute($stmt_avisos);
$resultadoAvisos  = mysqli_stmt_get_result($stmt_avisos);
$notificationCount = mysqli_num_rows($resultadoAvisos);

// Aba ativa
$validTabs = ['registrar', 'pesquisar', 'agenda', 'lista'];
$activeTab = in_array($_GET['tab'] ?? '', $validTabs) ? $_GET['tab'] : 'registrar';

// ======================================================
// DADOS PARA ABA: REGISTRAR (e modal editar do lista)
// ======================================================
$lotes = [];
$animaisReprodutores = [];
$animaisMatrizes     = [];

if ($tipoUsuario !== 'visitante') {
    if ($tipoUsuario === 'admin' || $tipoUsuario === 'administrador') {
        $stmtL = mysqli_prepare($conexao, "SELECT id, nome FROM lote ORDER BY nome ASC");
    } else {
        $stmtL = mysqli_prepare($conexao, "SELECT id, nome FROM lote WHERE user_id = ? ORDER BY nome ASC");
        mysqli_stmt_bind_param($stmtL, "i", $user_id);
    }
    mysqli_stmt_execute($stmtL);
    $resL = mysqli_stmt_get_result($stmtL);
    while ($l = mysqli_fetch_assoc($resL)) $lotes[] = $l;
    mysqli_stmt_close($stmtL);

    if ($tipoUsuario === 'admin' || $tipoUsuario === 'administrador') {
        $stmtA = mysqli_prepare($conexao, "SELECT id, nome, identificador, sexo FROM animal ORDER BY nome ASC");
    } else {
        $stmtA = mysqli_prepare($conexao,
            "SELECT a.id, a.nome, a.identificador, a.sexo
             FROM animal a LEFT JOIN lote l ON a.lote_id = l.id
             WHERE l.user_id = ? ORDER BY a.nome ASC");
        mysqli_stmt_bind_param($stmtA, "i", $user_id);
    }
    mysqli_stmt_execute($stmtA);
    $resA = mysqli_stmt_get_result($stmtA);
    while ($a = mysqli_fetch_assoc($resA)) {
        $label = (!empty($a['nome']) ? htmlspecialchars($a['nome']) : 'Sem nome') . ' (ID: ' . htmlspecialchars($a['identificador']) . ')';
        if (strtolower($a['sexo']) === 'macho') $animaisReprodutores[] = ['id' => $a['id'], 'label' => $label];
        else                                     $animaisMatrizes[]     = ['id' => $a['id'], 'label' => $label];
    }
    mysqli_stmt_close($stmtA);
}

// ======================================================
// DADOS PARA ABA: LISTA
// ======================================================
$animais = [];
if ($tipoUsuario === 'admin' || $tipoUsuario === 'administrador') {
    $sqlLista = "SELECT a.id, a.nome, a.especie, a.sexo, a.raca, a.peso_kg, a.idade,
                        a.identificador, a.estado_atual, a.esta_prenha, a.tempo_gestacao,
                        a.nascimento_fazenda, a.vacinado_prev, a.info_extras,
                        a.lote_id, a.reprodutor_id, a.matriz_id, l.nome AS lote_nome
                 FROM animal a LEFT JOIN lote l ON a.lote_id = l.id ORDER BY a.id DESC";
    $stmtLista = mysqli_prepare($conexao, $sqlLista);
} else {
    $sqlLista = "SELECT a.id, a.nome, a.especie, a.sexo, a.raca, a.peso_kg, a.idade,
                        a.identificador, a.estado_atual, a.esta_prenha, a.tempo_gestacao,
                        a.nascimento_fazenda, a.vacinado_prev, a.info_extras,
                        a.lote_id, a.reprodutor_id, a.matriz_id, l.nome AS lote_nome
                 FROM animal a LEFT JOIN lote l ON a.lote_id = l.id
                 WHERE l.user_id = ? ORDER BY a.id DESC";
    $stmtLista = mysqli_prepare($conexao, $sqlLista);
    mysqli_stmt_bind_param($stmtLista, "i", $user_id);
}
mysqli_stmt_execute($stmtLista);
$resLista = mysqli_stmt_get_result($stmtLista);
while ($an = mysqli_fetch_assoc($resLista)) $animais[] = $an;
mysqli_stmt_close($stmtLista);

$totalAnimais = count($animais);
$caprinos = count(array_filter($animais, fn($a) => strtolower($a['especie'] ?? '') === 'caprino'));
$ovinos   = count(array_filter($animais, fn($a) => strtolower($a['especie'] ?? '') === 'ovino'));
$machos   = count(array_filter($animais, fn($a) => strtolower($a['sexo'] ?? '') === 'macho'));
$femeas   = count(array_filter($animais, fn($a) => in_array(strtolower($a['sexo'] ?? ''), ['fêmea','femea'])));

// ======================================================
// DADOS PARA ABA: AGENDA
// ======================================================
$agendaEventos = [];
$stmtAg = mysqli_prepare($conexao, "SELECT id, Titulo, data_hora, tipo FROM agenda ORDER BY data_hora ASC LIMIT 50");
if ($stmtAg) {
    mysqli_stmt_execute($stmtAg);
    $resAg = mysqli_stmt_get_result($stmtAg);
    while ($ev = mysqli_fetch_assoc($resAg)) $agendaEventos[] = $ev;
    mysqli_stmt_close($stmtAg);
}

// Mapa de cores por tipo de evento
$tipoAgendaCores = [
    'vacinacao'   => ['bg' => '#e8f5e9', 'color' => '#1b5e20', 'label' => 'Vacinação'],
    'vacinação'   => ['bg' => '#e8f5e9', 'color' => '#1b5e20', 'label' => 'Vacinação'],
    'medicamento' => ['bg' => '#e3f2fd', 'color' => '#0d47a1', 'label' => 'Medicamento'],
    'exame'       => ['bg' => '#fff3e0', 'color' => '#e65100', 'label' => 'Exame'],
    'consulta'    => ['bg' => '#f3e5f5', 'color' => '#6a1b9a', 'label' => 'Consulta'],
    'outro'       => ['bg' => '#fafafa', 'color' => '#555',    'label' => 'Outro'],
];
?>
<!DOCTYPE html>
<html lang="pt-BR" data-theme="sistema">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cuidados e Monitoramento - ControlCabra</title>
    <link rel="stylesheet" href="cuidados.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
</head>
<body>

    <header class="mobile-header">
        <div class="mobile-header-left" style="flex:1;display:flex;align-items:center;gap:10px;min-width:0;">
            <img src="logoControlCabra.png" alt="Logo" class="mobile-logo" style="flex-shrink:0;">
            <span class="mobile-page-title" style="text-align:left;">Cuidados</span>
        </div>
        <div class="mobile-header-right" style="display:flex;align-items:center;gap:6px;flex-shrink:0;">
            <button class="notification-btn" id="notificationBtn" aria-label="Notificações">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/></svg>
                <?php if ($notificationCount > 0): ?><span class="badge" id="notificationCount"><?= $notificationCount ?></span><?php endif; ?>
            </button>
            <button class="menu-toggle" id="menuToggle" aria-label="Abrir menu">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="18" x2="21" y2="18"/></svg>
            </button>
        </div>
    </header>

    <div class="app-container">
        <div class="sidebar-overlay" id="sidebarOverlay"></div>

        <aside class="sidebar" id="sidebar">
            <div class="sidebar-brand">
                <img src="logoControlCabra.png" alt="Logo" class="sidebar-logo">
                <div class="brand-text"><h2>ControlCabra</h2><p>Gestão Inteligente</p></div>
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
                <div class="user-avatar-circle">
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="rgba(255,255,255,0.85)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                </div>
                <div class="user-info">
                    <strong class="user-name"><?= $nomeUsuario ?></strong>
                    <span class="user-farm"><?= $fazenda ?></span>
                </div>
            </div>
        </aside>

        <main class="main-content">

            <!-- Cabeçalho -->
            <div class="page-header header-health">
                <div>
                    <h1 class="page-title">
                        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="var(--primary)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="margin-right:8px;flex-shrink:0;"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/></svg>
                        Cuidados, Higiene e Monitoramento
                    </h1>
                    <p class="page-subtitle">Acompanhe e cuide do bem-estar do seu rebanho</p>
                </div>
                <div class="header-tools">
                    <button class="btn notification-btn" id="notificationBtnDesktop" aria-label="Notificações">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/></svg>
                        <?php if ($notificationCount > 0): ?><span class="badge" id="notificationCountDesktop"><?= $notificationCount ?></span><?php endif; ?>
                    </button>
                </div>
            </div>

            <!-- Barra de feedback global -->
            <div id="feedback-bar" class="feedback-bar" style="display:none;"></div>

            <!-- ============================================================
                 NAVEGAÇÃO POR ABAS (as 4 action cards viram tabs)
                 ============================================================ -->
            <div class="ctab-nav">
                <button class="ctab-btn <?= $activeTab === 'registrar' ? 'active' : '' ?>" data-tab="registrar">
                    <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20.59 13.41l-7.17 7.17a2 2 0 0 1-2.83 0L2 12V2h10l8.59 8.59a2 2 0 0 1 0 2.82z"/><line x1="7" y1="7" x2="7.01" y2="7"/></svg>
                    <span>Identificar novo animal</span>
                </button>
                <button class="ctab-btn <?= $activeTab === 'pesquisar' ? 'active' : '' ?>" data-tab="pesquisar">
                    <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                    <span>Pesquisar por código</span>
                </button>
                <button class="ctab-btn <?= $activeTab === 'agenda' ? 'active' : '' ?>" data-tab="agenda">
                    <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                    <span>Agenda de vacinação</span>
                </button>
                <button class="ctab-btn <?= $activeTab === 'lista' ? 'active' : '' ?>" data-tab="lista">
                    <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="8" y1="6" x2="21" y2="6"/><line x1="8" y1="12" x2="21" y2="12"/><line x1="8" y1="18" x2="21" y2="18"/><line x1="3" y1="6" x2="3.01" y2="6"/><line x1="3" y1="12" x2="3.01" y2="12"/><line x1="3" y1="18" x2="3.01" y2="18"/></svg>
                    <span>Ver lista de animais<?php if ($totalAnimais > 0): ?> <span class="ctab-count"><?= $totalAnimais ?></span><?php endif; ?></span>
                </button>
            </div>

            <!-- ============================================================
                 ABA 1: REGISTRAR ANIMAL
                 ============================================================ -->
            <div id="ctab-registrar" class="ctab-panel <?= $activeTab === 'registrar' ? 'active' : '' ?>">
                <?php if ($tipoUsuario === 'visitante'): ?>
                    <div class="empty-state"><svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="var(--text-muted)" stroke-width="1.5"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg><br>Visitantes não podem registrar animais.</div>
                <?php else: ?>
                <section class="card form-card">
                    <div class="form-card-header">
                        <h2 class="card-title">Novo Animal</h2>
                        <p class="card-subtitle">Preencha os dados para registrar o caprino ou ovino</p>
                    </div>
                    <form id="registroAnimalForm" method="POST" action="identificacaoService.php">
                        <input type="hidden" name="redirect_back" value="cuidados">
                        <input type="hidden" name="especie" id="inputEspecie" value="Caprino">
                        <input type="hidden" name="sexo"    id="inputSexo"    value="Macho">

                        <div class="form-section">
                            <h3 class="section-title">Origem e Saúde</h3>
                            <label class="checkbox-container"><input type="checkbox" name="nasceu_fazenda" value="1"><span class="checkmark"></span>Nasceu em sua fazenda?</label>
                            <label class="checkbox-container"><input type="checkbox" name="vacinado" value="1"><span class="checkmark"></span>Previamente vacinado e medicado?</label>
                        </div>

                        <div class="form-two-col">
                            <div class="form-section">
                                <label class="setting-label">Espécie</label>
                                <div class="options-row">
                                    <button type="button" class="option-btn active" data-group="especie" data-value="Caprino">Caprino</button>
                                    <button type="button" class="option-btn" data-group="especie" data-value="Ovino">Ovino</button>
                                </div>
                            </div>
                            <div class="form-section">
                                <label class="setting-label">Sexo</label>
                                <div class="options-row">
                                    <button type="button" class="option-btn active" data-group="sexo" data-value="Macho">Macho</button>
                                    <button type="button" class="option-btn" data-group="sexo" data-value="Fêmea">Fêmea</button>
                                </div>
                            </div>
                        </div>

                        <div class="form-grid">
                            <div class="form-group"><label>Nome (Opcional)</label><input type="text" name="nome" class="form-control" placeholder="Ex: Mimosa"></div>
                            <div class="form-group"><label>Raça *</label><input type="text" name="raca" id="reg_raca" class="form-control" placeholder="Informe a raça" required></div>
                            <div class="form-group"><label>Peso (Kg) *</label><input type="number" name="peso" id="reg_peso" class="form-control" placeholder="0.00" step="0.01" min="0.1" required></div>
                            <div class="form-group"><label>Idade (Meses) *</label><input type="number" name="idade" id="reg_idade" class="form-control" placeholder="Ex: 6" min="0" required></div>
                        </div>

                        <div class="form-group"><label>Está em tratamento? (Qual?)</label><input type="text" name="tratamento" class="form-control" placeholder="Descreva o tratamento ou deixe vazio"></div>

                        <div class="form-group" id="groupPrenha" style="display:none;">
                            <label>Está prenha?</label>
                            <div style="display:flex;gap:10px;">
                                <label class="checkbox-container" style="flex-shrink:0;margin-top:10px;"><input type="checkbox" name="esta_prenha" id="checkPrenha" value="1"><span class="checkmark"></span>Sim</label>
                                <input type="text" name="tempo_gestacao" id="tempoGestacao" class="form-control" placeholder="Tempo de gestação" disabled>
                            </div>
                        </div>

                        <div class="form-group"><label>Identificador (brinco/tatuagem/microchip) *</label><input type="text" name="identificador" id="reg_identificador" class="form-control" placeholder="Identificador único" required></div>

                        <div class="form-grid">
                            <div class="form-group">
                                <label>Lote</label>
                                <select name="lote_id" class="form-control">
                                    <option value="">Nenhum lote selecionado</option>
                                    <?php foreach ($lotes as $lote): ?><option value="<?= $lote['id'] ?>"><?= htmlspecialchars($lote['nome']) ?></option><?php endforeach; ?>
                                </select>
                            </div>
                            <div class="form-group"></div>
                        </div>

                        <div class="form-grid">
                            <div class="form-group">
                                <label>Reprodutor (Pai)</label>
                                <select name="reprodutor_id" class="form-control">
                                    <option value="">Desconhecido / Não se aplica</option>
                                    <?php foreach ($animaisReprodutores as $m): ?><option value="<?= $m['id'] ?>"><?= $m['label'] ?></option><?php endforeach; ?>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Matriz (Mãe)</label>
                                <select name="matriz_id" class="form-control">
                                    <option value="">Desconhecida / Não se aplica</option>
                                    <?php foreach ($animaisMatrizes as $f): ?><option value="<?= $f['id'] ?>"><?= $f['label'] ?></option><?php endforeach; ?>
                                </select>
                            </div>
                        </div>

                        <div class="form-group"><label>Observações</label><textarea name="info_extras" class="form-control" rows="3" placeholder="Algum detalhe relevante..."></textarea></div>

                        <div id="form-error-reg" class="feedback-bar" style="display:none;"></div>
                        <div class="form-actions">
                            <button type="submit" class="btn btn-primary btn-block">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
                                Salvar Identificação
                            </button>
                            <button type="reset" class="btn btn-outline btn-block" onclick="document.getElementById('form-error-reg').style.display='none'">Limpar Campos</button>
                        </div>
                    </form>
                </section>
                <?php endif; ?>
            </div>

            <!-- ============================================================
                 ABA 2: PESQUISAR POR CÓDIGO
                 ============================================================ -->
            <div id="ctab-pesquisar" class="ctab-panel <?= $activeTab === 'pesquisar' ? 'active' : '' ?>">
                <section class="card">
                    <div class="form-card-header">
                        <h2 class="card-title">Pesquisar Animal</h2>
                        <p class="card-subtitle">Busque por nome, número de brinco, tatuagem, microchip ou raça</p>
                    </div>
                    <div class="search-hero">
                        <div class="search-hero-input-wrap">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="search-hero-icon"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                            <input type="text" id="searchQuery" class="search-hero-input" placeholder="Digite o nome ou código do animal..." autocomplete="off">
                            <button class="btn btn-primary" id="btnSearch" style="border-radius:0 8px 8px 0; margin:0; padding: 0 20px;">Buscar</button>
                        </div>
                    </div>
                    <div id="searchResults" class="search-results-area">
                        <div class="search-placeholder">
                            <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="var(--border-color)" stroke-width="1.5"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                            <p>Digite para buscar animais do seu rebanho</p>
                        </div>
                    </div>
                </section>
            </div>

            <!-- ============================================================
                 ABA 3: AGENDA DE VACINAÇÃO
                 ============================================================ -->
            <div id="ctab-agenda" class="ctab-panel <?= $activeTab === 'agenda' ? 'active' : '' ?>">
                <section class="card">
                    <div class="form-card-header" style="display:flex;justify-content:space-between;align-items:flex-start;flex-wrap:wrap;gap:12px;">
                        <div>
                            <h2 class="card-title">Agenda de Vacinação</h2>
                            <p class="card-subtitle">Eventos e procedimentos programados do rebanho</p>
                        </div>
                        <a href="agenda.html" class="btn btn-outline" style="text-decoration:none;font-size:0.85rem;padding:8px 16px;">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/><polyline points="15 3 21 3 21 9"/><line x1="10" y1="14" x2="21" y2="3"/></svg>
                            Abrir agenda completa
                        </a>
                    </div>

                    <?php if (empty($agendaEventos)): ?>
                        <div class="empty-state" style="margin-top:20px;">
                            <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="var(--border-color)" stroke-width="1.5"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                            Nenhum evento na agenda ainda.
                            <small>Acesse a agenda completa para adicionar eventos de vacinação e tratamentos.</small>
                        </div>
                    <?php else: ?>
                        <div class="agenda-list">
                            <?php foreach ($agendaEventos as $ev):
                                $tipoKey = strtolower($ev['tipo'] ?? 'outro');
                                $cor = $tipoAgendaCores[$tipoKey] ?? $tipoAgendaCores['outro'];
                                $dataFormatada = '';
                                try {
                                    $dt = new DateTime($ev['data_hora']);
                                    $dataFormatada = $dt->format('d/m/Y H:i');
                                } catch (Exception $e) {
                                    $dataFormatada = htmlspecialchars($ev['data_hora']);
                                }
                            ?>
                            <div class="agenda-item">
                                <div class="agenda-tipo-dot" style="background:<?= $cor['color'] ?>;"></div>
                                <div class="agenda-info">
                                    <div class="agenda-titulo"><?= htmlspecialchars($ev['Titulo'] ?? 'Evento') ?></div>
                                    <div class="agenda-meta">
                                        <span class="agenda-badge" style="background:<?= $cor['bg'] ?>;color:<?= $cor['color'] ?>;"><?= $cor['label'] ?></span>
                                        <span class="agenda-data">
                                            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                                            <?= $dataFormatada ?>
                                        </span>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </section>
            </div>

            <!-- ============================================================
                 ABA 4: LISTA DE ANIMAIS
                 ============================================================ -->
            <div id="ctab-lista" class="ctab-panel <?= $activeTab === 'lista' ? 'active' : '' ?>">

                <!-- Cards de resumo -->
                <div class="summary-cards">
                    <div class="summary-card"><div class="val"><?= $totalAnimais ?></div><div class="lbl">Total</div></div>
                    <div class="summary-card"><div class="val" style="color:#2e7d32;"><?= $caprinos ?></div><div class="lbl">Caprinos</div></div>
                    <div class="summary-card"><div class="val" style="color:#1565c0;"><?= $ovinos ?></div><div class="lbl">Ovinos</div></div>
                    <div class="summary-card"><div class="val" style="color:#6a1b9a;"><?= $machos ?></div><div class="lbl">Machos</div></div>
                    <div class="summary-card"><div class="val" style="color:#c62828;"><?= $femeas ?></div><div class="lbl">Fêmeas</div></div>
                </div>

                <section class="animal-list-section">
                    <div class="section-header-row">
                        <h2 class="card-title" style="margin:0;">Animais Cadastrados</h2>
                        <div style="display:flex;gap:10px;flex-wrap:wrap;flex:1;justify-content:flex-end;align-items:center;">
                            <div class="search-wrap">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="search-icon"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                                <input type="text" id="listSearch" placeholder="Buscar por nome, ID, raça ou lote...">
                            </div>
                            <select id="listFilter" class="form-control filter-select">
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
                            <div class="empty-state" style="margin:24px;">
                                <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="var(--border-color)" stroke-width="1.5"><line x1="8" y1="6" x2="21" y2="6"/><line x1="8" y1="12" x2="21" y2="12"/><line x1="8" y1="18" x2="21" y2="18"/><line x1="3" y1="6" x2="3.01" y2="6"/></svg>
                                Nenhum animal cadastrado ainda.
                                <small>Use a aba "Identificar novo animal" para registrar.</small>
                            </div>
                        <?php else: ?>
                            <?php foreach ($animais as $an):
                                $esp      = strtolower($an['especie'] ?? '');
                                $espClass = $esp === 'caprino' ? 'badge-caprino' : ($esp === 'ovino' ? 'badge-ovino' : 'badge-outro');
                                $loteInfo = !empty($an['lote_nome']) ? htmlspecialchars($an['lote_nome']) : 'Sem lote';
                                $nomeAnimal = !empty($an['nome']) ? htmlspecialchars($an['nome']) : 'Animal #' . $an['id'];
                                $searchStr  = strtolower(($an['nome'] ?? '') . ' ' . ($an['especie'] ?? '') . ' ' . ($an['raca'] ?? '') . ' ' . ($an['lote_nome'] ?? '') . ' ' . ($an['identificador'] ?? ''));
                            ?>
                            <div class="animal-item"
                                 data-search="<?= htmlspecialchars($searchStr) ?>"
                                 data-especie="<?= strtolower($an['especie'] ?? '') ?>"
                                 data-sexo="<?= strtolower($an['sexo'] ?? '') ?>"
                                 data-lote="<?= !empty($an['lote_nome']) ? 'sim' : 'nao' ?>">
                                <div class="animal-info">
                                    <div class="animal-avatar">
                                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="var(--primary)" stroke-width="1.5"><circle cx="9" cy="12" r="1.5"/><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/><path d="M6.5 17H20a2 2 0 0 1 0 4H6.5"/><path d="M9 12H4a2 2 0 0 0-1.5 3.3L4 19.5"/><path d="M20 17V9a2 2 0 0 0-2-2h-3.5"/><path d="M14.5 7C14.5 5 16 3 18 3s3.5 2 3.5 4-1 4-3 4-4.5-1-4.5-1"/></svg>
                                    </div>
                                    <div>
                                        <div class="animal-name">
                                            <?= $nomeAnimal ?>
                                            <span class="especie-badge <?= $espClass ?>"><?= htmlspecialchars(ucfirst($an['especie'] ?? '—')) ?></span>
                                            <?php if (!empty($an['sexo'])): ?><span class="especie-badge badge-sexo"><?= htmlspecialchars(ucfirst($an['sexo'])) ?></span><?php endif; ?>
                                        </div>
                                        <div class="animal-meta">
                                            ID: <strong><?= htmlspecialchars($an['identificador'] ?? '—') ?></strong>
                                            · Raça: <?= !empty($an['raca']) ? htmlspecialchars($an['raca']) : '—' ?>
                                            <?php if (!empty($an['peso_kg'])): ?> · <?= number_format($an['peso_kg'], 1, ',', '') ?> Kg<?php endif; ?>
                                            · <?= $loteInfo ?>
                                        </div>
                                    </div>
                                </div>
                                <?php if ($tipoUsuario !== 'visitante'): ?>
                                <div class="animal-actions">
                                    <button type="button" class="action-btn btn-edit" title="Editar"
                                            onclick="abrirModalEdicao(<?= htmlspecialchars(json_encode($an), ENT_QUOTES) ?>)">
                                        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                                        Editar
                                    </button>
                                    <button type="button" class="action-btn btn-delete" title="Excluir"
                                            onclick="confirmarExclusao(<?= $an['id'] ?>, '<?= htmlspecialchars(addslashes($nomeAnimal)) ?>')">
                                        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14H6L5 6"/><path d="M9 6V4h6v2"/></svg>
                                        Excluir
                                    </button>
                                </div>
                                <?php endif; ?>
                            </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                    <div id="listNoResults" class="empty-state" style="display:none;margin:24px;">Nenhum animal encontrado para a busca.<small>Tente outros termos ou limpe o filtro.</small></div>
                </section>
            </div>

        </main>
    </div>

    <!-- ============================================================
         MODAL: EDITAR ANIMAL
         ============================================================ -->
    <div class="modal-overlay" id="modalEdicao">
        <div class="modal-box">
            <div class="modal-header">
                <h3>Editar Animal</h3>
                <button class="modal-close" id="fecharModalEdicao"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg></button>
            </div>
            <div class="modal-body">
                <form id="formEdicao" method="POST" action="animalEditService.php">
                    <input type="hidden" name="redirect_back" value="cuidados">
                    <input type="hidden" name="animal_id"    id="edit_animal_id">
                    <input type="hidden" name="especie"      id="edit_inputEspecie" value="Caprino">
                    <input type="hidden" name="sexo"         id="edit_inputSexo"    value="Macho">

                    <div class="form-two-col">
                        <div class="form-section">
                            <label class="setting-label">Espécie</label>
                            <div class="options-row">
                                <button type="button" class="option-btn active" data-group="edit_especie" data-value="Caprino">Caprino</button>
                                <button type="button" class="option-btn" data-group="edit_especie" data-value="Ovino">Ovino</button>
                            </div>
                        </div>
                        <div class="form-section">
                            <label class="setting-label">Sexo</label>
                            <div class="options-row">
                                <button type="button" class="option-btn active" data-group="edit_sexo" data-value="Macho">Macho</button>
                                <button type="button" class="option-btn" data-group="edit_sexo" data-value="Fêmea">Fêmea</button>
                            </div>
                        </div>
                    </div>
                    <div class="form-section">
                        <label class="checkbox-container"><input type="checkbox" name="nasceu_fazenda" id="edit_nasceu" value="1"><span class="checkmark"></span>Nasceu na fazenda</label>
                        <label class="checkbox-container"><input type="checkbox" name="vacinado" id="edit_vacinado" value="1"><span class="checkmark"></span>Previamente vacinado</label>
                    </div>
                    <div class="form-grid">
                        <div class="form-group"><label>Nome</label><input type="text" name="nome" id="edit_nome" class="form-control"></div>
                        <div class="form-group"><label>Raça *</label><input type="text" name="raca" id="edit_raca" class="form-control" required></div>
                        <div class="form-group"><label>Peso (Kg) *</label><input type="number" name="peso" id="edit_peso" class="form-control" step="0.01" min="0.1" required></div>
                        <div class="form-group"><label>Idade (Meses)</label><input type="number" name="idade" id="edit_idade" class="form-control" min="0"></div>
                    </div>
                    <div class="form-group"><label>Tratamento atual</label><input type="text" name="tratamento" id="edit_tratamento" class="form-control" placeholder="Deixe vazio se não houver"></div>
                    <div class="form-group" id="edit_groupPrenha" style="display:none;">
                        <label>Está prenha?</label>
                        <div style="display:flex;gap:10px;">
                            <label class="checkbox-container" style="flex-shrink:0;margin-top:10px;"><input type="checkbox" name="esta_prenha" id="edit_prenha" value="1"><span class="checkmark"></span>Sim</label>
                            <input type="text" name="tempo_gestacao" id="edit_gestacao" class="form-control" placeholder="Tempo de gestação" disabled>
                        </div>
                    </div>
                    <div class="form-group"><label>Identificador *</label><input type="text" name="identificador" id="edit_identificador" class="form-control" required></div>
                    <div class="form-grid">
                        <div class="form-group">
                            <label>Lote</label>
                            <select name="lote_id" id="edit_lote_id" class="form-control">
                                <option value="">Nenhum lote</option>
                                <?php foreach ($lotes as $lote): ?><option value="<?= $lote['id'] ?>"><?= htmlspecialchars($lote['nome']) ?></option><?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Reprodutor (Pai)</label>
                            <select name="reprodutor_id" id="edit_reprodutor" class="form-control">
                                <option value="">Não se aplica</option>
                                <?php foreach ($animaisReprodutores as $m): ?><option value="<?= $m['id'] ?>"><?= $m['label'] ?></option><?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Matriz (Mãe)</label>
                            <select name="matriz_id" id="edit_matriz" class="form-control">
                                <option value="">Não se aplica</option>
                                <?php foreach ($animaisMatrizes as $f): ?><option value="<?= $f['id'] ?>"><?= $f['label'] ?></option><?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group"><label>Observações</label><textarea name="info_extras" id="edit_info" class="form-control" rows="2"></textarea></div>
                    </div>
                    <div class="form-actions" style="margin-top:16px;">
                        <button type="submit" class="btn btn-primary btn-block"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>Salvar Alterações</button>
                        <button type="button" class="btn btn-outline btn-block" id="cancelarEdicao">Cancelar</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- ============================================================
         MODAL: CONFIRMAR EXCLUSÃO
         ============================================================ -->
    <div class="modal-overlay" id="modalExclusao">
        <div class="modal-box modal-box--sm">
            <div class="modal-header">
                <h3>Confirmar Exclusão</h3>
                <button class="modal-close" id="fecharModalExclusao"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg></button>
            </div>
            <div class="modal-body">
                <div class="delete-warning">
                    <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="#d32f2f" stroke-width="1.5"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
                    Tem certeza que deseja excluir <strong id="deleteAnimalNome"></strong>? Esta ação não pode ser desfeita.
                </div>
                <form id="formExclusao" method="POST" action="animalDeleteService.php">
                    <input type="hidden" name="redirect_back" value="cuidados">
                    <input type="hidden" name="animal_id" id="delete_animal_id">
                    <div class="form-actions" style="margin-top:20px;">
                        <button type="submit" class="btn btn-danger btn-block"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14H6L5 6"/><path d="M9 6V4h6v2"/></svg>Sim, excluir</button>
                        <button type="button" class="btn btn-outline btn-block" id="cancelarExclusao">Cancelar</button>
                    </div>
                </form>
            </div>
        </div>
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
                    <p style="color:var(--text-muted);text-align:center;padding:12px 0;">Nenhuma notificação pendente!</p>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="cuidados.js"></script>
    <script src="notifications.js"></script>
</body>
</html>