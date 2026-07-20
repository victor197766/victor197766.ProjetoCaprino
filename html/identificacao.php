<?php
session_start();
if (!isset($_SESSION['usuario_id'])) {
    header('Location: recuperarConta.html');
    exit();
}
include 'db/connection.php';

$user_id     = intval($_SESSION['usuario_id']);
$nomeUsuario = htmlspecialchars($_SESSION['usuario_nome'] ?? 'Usuário');
$fazenda     = htmlspecialchars(trim($_SESSION['usuario_fazenda'] ?? '') !== '' ? $_SESSION['usuario_fazenda'] : 'Nenhuma propriedade cadastrada.');
$tipoUsuario = strtolower($_SESSION['usuario_tipo'] ?? 'produtor');

if ($tipoUsuario === 'visitante') {
    header('Location: lista_animais.php');
    exit();
}

// Iniciais para o avatar
$partes  = explode(' ', $nomeUsuario);
$iniciais = strtoupper(substr($partes[0], 0, 1));
if (count($partes) > 1) $iniciais .= strtoupper(substr(end($partes), 0, 1));

// =============================================
// BUSCAR LOTES DO USUÁRIO (para selects)
// =============================================
$lotes = [];
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

// =============================================
// BUSCAR REPRODUTORES E MATRIZES (para selects)
// =============================================
$animaisReprodutores = [];
$animaisMatrizes     = [];
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
    if (strtolower($a['sexo']) === 'macho') {
        $animaisReprodutores[] = ['id' => $a['id'], 'label' => $label];
    } else {
        $animaisMatrizes[] = ['id' => $a['id'], 'label' => $label];
    }
}
mysqli_stmt_close($stmtA);

// =============================================
// BUSCAR LISTA DE ANIMAIS (para a aba de lista)
// =============================================
if ($tipoUsuario === 'admin' || $tipoUsuario === 'administrador') {
    $sqlAnimais = "SELECT a.id, a.nome, a.especie, a.sexo, a.raca, a.peso_kg, a.idade,
                          a.identificador, a.estado_atual, a.esta_prenha, a.tempo_gestacao,
                          a.nascimento_fazenda, a.vacinado_prev, a.info_extras,
                          a.lote_id, a.reprodutor_id, a.matriz_id,
                          l.nome AS lote_nome
                   FROM animal a LEFT JOIN lote l ON a.lote_id = l.id
                   ORDER BY a.id DESC";
    $stmtLA = mysqli_prepare($conexao, $sqlAnimais);
} else {
    $sqlAnimais = "SELECT a.id, a.nome, a.especie, a.sexo, a.raca, a.peso_kg, a.idade,
                          a.identificador, a.estado_atual, a.esta_prenha, a.tempo_gestacao,
                          a.nascimento_fazenda, a.vacinado_prev, a.info_extras,
                          a.lote_id, a.reprodutor_id, a.matriz_id,
                          l.nome AS lote_nome
                   FROM animal a LEFT JOIN lote l ON a.lote_id = l.id
                   WHERE l.user_id = ?
                   ORDER BY a.id DESC";
    $stmtLA = mysqli_prepare($conexao, $sqlAnimais);
    mysqli_stmt_bind_param($stmtLA, "i", $user_id);
}
mysqli_stmt_execute($stmtLA);
$resAnimais = mysqli_stmt_get_result($stmtLA);
$animais    = [];
while ($an = mysqli_fetch_assoc($resAnimais)) $animais[] = $an;
mysqli_stmt_close($stmtLA);

// Contagens para os cards de resumo
$totalAnimais = count($animais);
$caprinos     = count(array_filter($animais, fn($a) => strtolower($a['especie'] ?? '') === 'caprino'));
$ovinos       = count(array_filter($animais, fn($a) => strtolower($a['especie'] ?? '') === 'ovino'));
$machos       = count(array_filter($animais, fn($a) => strtolower($a['sexo'] ?? '') === 'macho'));
$femeas       = count(array_filter($animais, fn($a) => strtolower($a['sexo'] ?? '') === 'fêmea' || strtolower($a['sexo'] ?? '') === 'femea'));

// Aba ativa vinda da URL (após redirect do service)
$activeTab = ($_GET['tab'] ?? 'registrar') === 'lista' ? 'lista' : 'registrar';
?>
<!DOCTYPE html>
<html lang="pt-BR" data-theme="sistema">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Identificação de Animais - ControlCabra</title>
    <link rel="stylesheet" href="identificacao.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
</head>
<body>

    <header class="mobile-header">
        <div class="mobile-header-left" style="flex:1;display:flex;align-items:center;gap:10px;min-width:0;">
            <img src="logoControlCabra.png" alt="Logo" class="mobile-logo" style="flex-shrink:0;">
            <span class="mobile-page-title" style="text-align:left;">Identificação</span>
        </div>
        <div class="mobile-header-right">
            <button class="menu-toggle" id="menuToggle">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="18" x2="21" y2="18"/></svg>
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
                <a href="cuidados.php" class="nav-item active">Cuidados</a>
                <?php if ($tipoUsuario !== 'visitante'): ?>
                    <a href="propriedades.php" class="nav-item">Propriedades</a>
                <?php endif; ?>
                <a href="configuracoes.php" class="nav-item">Configurações</a>
                <a href="administracao.php" class="nav-item">Administração</a>
            </nav>

            <div class="user-profile">
                <div class="user-avatar-circle">
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="rgba(255,255,255,0.85)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                        <circle cx="12" cy="7" r="4"></circle>
                    </svg>
                </div>
                <div class="user-info">
                    <strong class="user-name"><?= $nomeUsuario ?></strong>
                    <span class="user-farm"><?= $fazenda ?></span>
                </div>
            </div>
        </aside>

        <div class="sidebar-overlay" id="sidebarOverlay"></div>

        <main class="main-content">

            <!-- Cabeçalho da página -->
            <div class="page-header-row">
                <div>
                    <h1 class="page-title">
                        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="var(--primary)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20.59 13.41l-7.17 7.17a2 2 0 0 1-2.83 0L2 12V2h10l8.59 8.59a2 2 0 0 1 0 2.82z"/><line x1="7" y1="7" x2="7.01" y2="7"/></svg>
                        Identificação de Animais
                    </h1>
                    <p class="page-subtitle">Registre, pesquise e gerencie os animais do seu rebanho</p>
                </div>
            </div>

            <!-- Feedback (sucesso / erro) -->
            <div id="feedback-bar" class="feedback-bar" style="display:none;"></div>

            <!-- Abas -->
            <div class="tab-switcher">
                <button class="tab-btn <?= $activeTab === 'registrar' ? 'active' : '' ?>" data-tab="registrar">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                    Registrar Animal
                </button>
                <button class="tab-btn <?= $activeTab === 'lista' ? 'active' : '' ?>" data-tab="lista">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="8" y1="6" x2="21" y2="6"/><line x1="8" y1="12" x2="21" y2="12"/><line x1="8" y1="18" x2="21" y2="18"/><line x1="3" y1="6" x2="3.01" y2="6"/><line x1="3" y1="12" x2="3.01" y2="12"/><line x1="3" y1="18" x2="3.01" y2="18"/></svg>
                    Meus Animais
                    <?php if ($totalAnimais > 0): ?>
                        <span class="tab-count"><?= $totalAnimais ?></span>
                    <?php endif; ?>
                </button>
            </div>

            <!-- ======================== ABA: REGISTRAR ======================== -->
            <div id="tab-registrar" class="tab-panel <?= $activeTab === 'registrar' ? 'active' : '' ?>">
                <section class="card form-card">
                    <form id="registroAnimalForm" method="POST" action="identificacaoService.php">
                        <input type="hidden" name="especie" id="inputEspecie" value="Caprino">
                        <input type="hidden" name="sexo" id="inputSexo" value="Macho">

                        <div class="form-section">
                            <h3 class="section-title">Origem e Saúde</h3>
                            <label class="checkbox-container">
                                <input type="checkbox" name="nasceu_fazenda" value="1">
                                <span class="checkmark"></span>
                                Nasceu em sua fazenda?
                            </label>
                            <label class="checkbox-container">
                                <input type="checkbox" name="vacinado" value="1">
                                <span class="checkmark"></span>
                                Previamente vacinado e medicado?
                            </label>
                        </div>

                        <div class="form-section">
                            <label class="setting-label">É Ovino ou Caprino?</label>
                            <div class="options-row">
                                <button type="button" class="option-btn active" data-group="especie" data-value="Caprino">Caprino</button>
                                <button type="button" class="option-btn" data-group="especie" data-value="Ovino">Ovino</button>
                            </div>
                        </div>

                        <div class="form-section">
                            <label class="setting-label">É Macho ou Fêmea?</label>
                            <div class="options-row">
                                <button type="button" class="option-btn active" data-group="sexo" data-value="Macho">Macho</button>
                                <button type="button" class="option-btn" data-group="sexo" data-value="Fêmea">Fêmea</button>
                            </div>
                        </div>

                        <div class="form-grid">
                            <div class="form-group">
                                <label>Nome do Animal (Opcional)</label>
                                <input type="text" name="nome" id="nome" class="form-control" placeholder="Ex: Mimosa">
                            </div>
                            <div class="form-group">
                                <label>Raça *</label>
                                <input type="text" name="raca" id="raca" class="form-control" placeholder="Informe a raça" required>
                            </div>
                        </div>

                        <div class="form-grid">
                            <div class="form-group">
                                <label>Peso (Kg) *</label>
                                <input type="number" name="peso" id="peso" class="form-control" placeholder="0.00" step="0.01" min="0.1" required>
                            </div>
                            <div class="form-group">
                                <label>Idade (Meses) *</label>
                                <input type="number" name="idade" id="idade" class="form-control" placeholder="Ex: 6" min="0" required>
                            </div>
                        </div>

                        <div class="form-group">
                            <label>Está em tratamento? (Se sim, qual?)</label>
                            <input type="text" name="tratamento" class="form-control" placeholder="Descreva o tratamento ou deixe vazio">
                        </div>

                        <div class="form-group" id="groupPrenha" style="display:none;">
                            <label>Está prenha? (Se sim, quanto tempo?)</label>
                            <div style="display:flex; gap:10px;">
                                <label class="checkbox-container" style="flex-shrink:0; margin-top:10px;">
                                    <input type="checkbox" name="esta_prenha" id="checkPrenha" value="1">
                                    <span class="checkmark"></span>
                                    Sim
                                </label>
                                <input type="text" name="tempo_gestacao" id="tempoGestacao" class="form-control" placeholder="Tempo de gestação" disabled>
                            </div>
                        </div>

                        <div class="form-group">
                            <label>Número do brinco, tatuagem ou microchip *</label>
                            <input type="text" name="identificador" id="identificador" class="form-control" placeholder="Identificador único" required>
                        </div>

                        <div class="form-group">
                            <label>Está em qual lote?</label>
                            <select name="lote_id" class="form-control">
                                <option value="">Nenhum lote selecionado</option>
                                <?php foreach ($lotes as $lote): ?>
                                    <option value="<?= $lote['id'] ?>"><?= htmlspecialchars($lote['nome']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="form-grid">
                            <div class="form-group">
                                <label>Reprodutor (Pai)</label>
                                <select name="reprodutor_id" class="form-control">
                                    <option value="">Desconhecido / Não se aplica</option>
                                    <?php foreach ($animaisReprodutores as $macho): ?>
                                        <option value="<?= $macho['id'] ?>"><?= $macho['label'] ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Matriz (Mãe)</label>
                                <select name="matriz_id" class="form-control">
                                    <option value="">Desconhecida / Não se aplica</option>
                                    <?php foreach ($animaisMatrizes as $femea): ?>
                                        <option value="<?= $femea['id'] ?>"><?= $femea['label'] ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>

                        <div class="form-group">
                            <label>Informações extras / Observações</label>
                            <textarea name="info_extras" class="form-control" rows="4" placeholder="Algum detalhe relevante..."></textarea>
                        </div>

                        <div id="form-error" class="feedback-bar" style="display:none;"></div>
                        <div class="form-actions">
                            <button type="submit" class="btn btn-primary btn-block">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
                                Salvar Identificação
                            </button>
                            <button type="reset" class="btn btn-outline btn-block" onclick="document.getElementById('form-error').style.display='none'">
                                Limpar Campos
                            </button>
                        </div>
                    </form>
                </section>
            </div>

            <!-- ======================== ABA: MEUS ANIMAIS ======================== -->
            <div id="tab-lista" class="tab-panel <?= $activeTab === 'lista' ? 'active' : '' ?>">

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

                <!-- Seção de lista -->
                <section class="animal-list-section">
                    <div class="section-header-row">
                        <h2 class="card-title" style="margin:0;">Animais Cadastrados</h2>
                        <div style="display:flex; gap:10px; flex-wrap:wrap; flex:1; justify-content:flex-end; align-items:center;">
                            <!-- Busca -->
                            <div class="search-wrap">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="search-icon"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                                <input type="text" id="searchAnimal" placeholder="Buscar por nome, ID, raça ou lote...">
                            </div>
                            <!-- Filtro -->
                            <select id="filterSelect" class="form-control filter-select">
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
                                Nenhum animal cadastrado ainda.
                                <small>Use a aba "Registrar Animal" para adicionar seu primeiro caprino ou ovino.</small>
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
                                 data-id="<?= $an['id'] ?>"
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
                                            <span class="especie-badge <?= $espClass ?>"><?= htmlspecialchars(ucfirst($an['especie'] ?? 'Desconhecido')) ?></span>
                                            <?php if (!empty($an['sexo'])): ?>
                                                <span class="especie-badge badge-sexo"><?= htmlspecialchars(ucfirst($an['sexo'])) ?></span>
                                            <?php endif; ?>
                                        </div>
                                        <div class="animal-meta">
                                            ID: <strong><?= htmlspecialchars($an['identificador'] ?? '—') ?></strong>
                                            · Raça: <?= !empty($an['raca']) ? htmlspecialchars($an['raca']) : '—' ?>
                                            <?php if (!empty($an['peso_kg'])): ?> · <?= number_format($an['peso_kg'], 1, ',', '') ?> Kg<?php endif; ?>
                                            · <?= $loteInfo ?>
                                        </div>
                                    </div>
                                </div>

                                <div class="animal-actions">
                                    <button type="button" class="action-btn btn-edit"
                                            title="Editar animal"
                                            onclick="abrirModalEdicao(<?= htmlspecialchars(json_encode($an), ENT_QUOTES) ?>)">
                                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                                        Editar
                                    </button>
                                    <button type="button" class="action-btn btn-delete"
                                            title="Excluir animal"
                                            onclick="confirmarExclusao(<?= $an['id'] ?>, '<?= htmlspecialchars(addslashes($nomeAnimal)) ?>')">
                                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14H6L5 6"/><path d="M10 11v6"/><path d="M14 11v6"/><path d="M9 6V4h6v2"/></svg>
                                        Excluir
                                    </button>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>

                    <!-- Mensagem de nenhum resultado na busca -->
                    <div id="noResults" class="empty-state" style="display:none; margin:24px;">
                        Nenhum animal encontrado para a busca realizada.
                        <small>Tente outros termos ou limpe o filtro.</small>
                    </div>
                </section>
            </div>

        </main>
    </div>

    <!-- ======================== MODAL DE EDIÇÃO ======================== -->
    <div class="modal-overlay" id="modalEdicao">
        <div class="modal-box">
            <div class="modal-header">
                <h3>Editar Animal</h3>
                <button class="modal-close" id="fecharModal" aria-label="Fechar">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                </button>
            </div>
            <div class="modal-body">
                <form id="formEdicao" method="POST" action="animalEditService.php">
                    <input type="hidden" name="animal_id" id="edit_animal_id">
                    <input type="hidden" name="especie"   id="edit_inputEspecie" value="Caprino">
                    <input type="hidden" name="sexo"      id="edit_inputSexo" value="Macho">

                    <div class="form-section">
                        <h4 class="section-title">Origem</h4>
                        <label class="checkbox-container">
                            <input type="checkbox" name="nasceu_fazenda" id="edit_nasceu" value="1">
                            <span class="checkmark"></span>
                            Nasceu em sua fazenda?
                        </label>
                        <label class="checkbox-container">
                            <input type="checkbox" name="vacinado" id="edit_vacinado" value="1">
                            <span class="checkmark"></span>
                            Previamente vacinado e medicado?
                        </label>
                    </div>

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

                    <div class="form-grid">
                        <div class="form-group">
                            <label>Nome (Opcional)</label>
                            <input type="text" name="nome" id="edit_nome" class="form-control" placeholder="Ex: Mimosa">
                        </div>
                        <div class="form-group">
                            <label>Raça *</label>
                            <input type="text" name="raca" id="edit_raca" class="form-control" required>
                        </div>
                    </div>

                    <div class="form-grid">
                        <div class="form-group">
                            <label>Peso (Kg) *</label>
                            <input type="number" name="peso" id="edit_peso" class="form-control" step="0.01" min="0.1" required>
                        </div>
                        <div class="form-group">
                            <label>Idade (Meses) *</label>
                            <input type="number" name="idade" id="edit_idade" class="form-control" min="0" required>
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Tratamento atual</label>
                        <input type="text" name="tratamento" id="edit_tratamento" class="form-control" placeholder="Deixe vazio se não houver">
                    </div>

                    <div class="form-group" id="edit_groupPrenha" style="display:none;">
                        <label>Está prenha?</label>
                        <div style="display:flex; gap:10px;">
                            <label class="checkbox-container" style="flex-shrink:0; margin-top:10px;">
                                <input type="checkbox" name="esta_prenha" id="edit_prenha" value="1">
                                <span class="checkmark"></span>
                                Sim
                            </label>
                            <input type="text" name="tempo_gestacao" id="edit_gestacao" class="form-control" placeholder="Tempo de gestação" disabled>
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Identificador (brinco/tatuagem/microchip) *</label>
                        <input type="text" name="identificador" id="edit_identificador" class="form-control" required>
                    </div>

                    <div class="form-group">
                        <label>Lote</label>
                        <select name="lote_id" id="edit_lote_id" class="form-control">
                            <option value="">Nenhum lote selecionado</option>
                            <?php foreach ($lotes as $lote): ?>
                                <option value="<?= $lote['id'] ?>"><?= htmlspecialchars($lote['nome']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-grid">
                        <div class="form-group">
                            <label>Reprodutor (Pai)</label>
                            <select name="reprodutor_id" id="edit_reprodutor" class="form-control">
                                <option value="">Desconhecido / Não se aplica</option>
                                <?php foreach ($animaisReprodutores as $macho): ?>
                                    <option value="<?= $macho['id'] ?>"><?= $macho['label'] ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Matriz (Mãe)</label>
                            <select name="matriz_id" id="edit_matriz" class="form-control">
                                <option value="">Desconhecida / Não se aplica</option>
                                <?php foreach ($animaisMatrizes as $femea): ?>
                                    <option value="<?= $femea['id'] ?>"><?= $femea['label'] ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Observações</label>
                        <textarea name="info_extras" id="edit_info" class="form-control" rows="3" placeholder="Informações extras..."></textarea>
                    </div>

                    <div class="form-actions" style="margin-top:20px;">
                        <button type="submit" class="btn btn-primary btn-block">
                            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
                            Salvar Alterações
                        </button>
                        <button type="button" class="btn btn-outline btn-block" id="cancelarEdicao">Cancelar</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- ======================== MODAL DE EXCLUSÃO ======================== -->
    <div class="modal-overlay" id="modalExclusao">
        <div class="modal-box modal-box--sm">
            <div class="modal-header">
                <h3>Confirmar Exclusão</h3>
                <button class="modal-close" id="fecharModalExclusao">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                </button>
            </div>
            <div class="modal-body">
                <p class="delete-warning">
                    <svg width="36" height="36" viewBox="0 0 24 24" fill="none" stroke="#d32f2f" stroke-width="1.5"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
                    Tem certeza que deseja excluir <strong id="deleteAnimalNome"></strong>? Esta ação não pode ser desfeita.
                </p>
                <form id="formExclusao" method="POST" action="animalDeleteService.php">
                    <input type="hidden" name="animal_id" id="delete_animal_id">
                    <div class="form-actions" style="margin-top:20px;">
                        <button type="submit" class="btn btn-danger btn-block">
                            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14H6L5 6"/><path d="M9 6V4h6v2"/></svg>
                            Sim, excluir
                        </button>
                        <button type="button" class="btn btn-outline btn-block" id="cancelarExclusao">Cancelar</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="identificacao.js"></script>
</body>
</html>