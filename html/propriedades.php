<?php
session_start();
if (!isset($_SESSION['usuario_id'])) {
    header('Location: recuperarConta.html');
    exit();
}

include '../db/connection.php';

// Criação da tabela de propriedades se não existir
$sqlPropriedadesTable = "CREATE TABLE IF NOT EXISTS propriedades (
    id INT(11) NOT NULL AUTO_INCREMENT,
    nome VARCHAR(255) NOT NULL,
    produtor_id INT(11) NOT NULL,
    PRIMARY KEY (id),
    CONSTRAINT fk_prop_produtor FOREIGN KEY (produtor_id) REFERENCES usuario (user_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;";
mysqli_query($conexao, $sqlPropriedadesTable);

// Verificação para a coluna propriedade_id na tabela usuario
$checkPropColumn = mysqli_query($conexao, "SHOW COLUMNS FROM usuario LIKE 'propriedade_id'");
if ($checkPropColumn && mysqli_num_rows($checkPropColumn) == 0) {
    mysqli_query($conexao, "ALTER TABLE usuario ADD COLUMN propriedade_id INT(11) NULL DEFAULT NULL");
}

// Adicionar colunas novas na tabela lote (se não existirem)
$colsLote = [
    'propriedade_id' => "INT(11) NULL DEFAULT NULL",
    'objetivo'       => "VARCHAR(50) NULL DEFAULT NULL",
    'tipo_alimentacao' => "VARCHAR(255) NULL DEFAULT NULL",
];
foreach ($colsLote as $colName => $colDef) {
    $chk = mysqli_query($conexao, "SHOW COLUMNS FROM lote LIKE '$colName'");
    if ($chk && mysqli_num_rows($chk) == 0) {
        mysqli_query($conexao, "ALTER TABLE lote ADD COLUMN $colName $colDef");
    }
}

// Determinação do perfil
$userTipo  = strtolower($_SESSION['usuario_tipo'] ?? 'produtor');
$isVisitante = ($userTipo === 'visitante');
$isAdmin     = ($userTipo !== 'produtor' && $userTipo !== 'visitante');
$user_id     = intval($_SESSION['usuario_id']);

// Visitantes são somente leitura nesta página, mas o usuário já está bloqueado mais abaixo para ações POST

// Fetch notifications
$query_avisos = "SELECT a.id, a.titulo, a.mensagem, a.data_criacao FROM avisos a WHERE a.destinatario_id IS NULL OR a.destinatario_id = ? ORDER BY a.id DESC";
$stmt_avisos = mysqli_prepare($conexao, $query_avisos);
mysqli_stmt_bind_param($stmt_avisos, "i", $user_id);
mysqli_stmt_execute($stmt_avisos);
$resultadoAvisos = mysqli_stmt_get_result($stmt_avisos);
$notificationCount = mysqli_num_rows($resultadoAvisos);

// ============================================================
// POST HANDLERS
// ============================================================
$acao = $_POST['form_action'] ?? '';
$msgRedir = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$isVisitante) {

    // --- PROPRIEDADES ---
    if ($acao === 'adicionar_propriedade') {
        $nome = trim($_POST['nome_propriedade'] ?? '');
        if ($nome !== '') {
            $ownerId = $isAdmin ? intval($_POST['produtor_id'] ?? $user_id) : $user_id;
            $stmt = mysqli_prepare($conexao, "INSERT INTO propriedades (nome, produtor_id) VALUES (?, ?)");
            mysqli_stmt_bind_param($stmt, "si", $nome, $ownerId);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);
            $msgRedir = 'sucesso_adicionar';
        }
        header("Location: propriedades.php?msg=$msgRedir"); exit();

    } elseif ($acao === 'deletar_propriedade') {
        $prop_id = intval($_POST['propriedade_id'] ?? 0);
        if ($prop_id > 0) {
            if ($isAdmin) {
                $stmt = mysqli_prepare($conexao, "DELETE FROM propriedades WHERE id = ?");
                mysqli_stmt_bind_param($stmt, "i", $prop_id);
            } else {
                $stmt = mysqli_prepare($conexao, "DELETE FROM propriedades WHERE id = ? AND produtor_id = ?");
                mysqli_stmt_bind_param($stmt, "ii", $prop_id, $user_id);
            }
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);
        }
        header("Location: propriedades.php?msg=sucesso_deletar"); exit();

    // --- LOTES ---
    } elseif ($acao === 'adicionar_lote') {
        $nome_lote  = trim($_POST['nome_lote'] ?? '');
        $prop_id    = intval($_POST['prop_id'] ?? 0);
        $objetivo   = trim($_POST['objetivo'] ?? '');
        $tipo_alim  = trim($_POST['tipo_alimentacao'] ?? '');
        $animais    = array_map('intval', $_POST['animais'] ?? []);
        $lote_owner = $isAdmin ? intval($_POST['lote_dono'] ?? $user_id) : $user_id;

        if ($nome_lote !== '' && $prop_id > 0) {
            $stmt = mysqli_prepare($conexao, "INSERT INTO lote (nome, tipo, user_id, propriedade_id, objetivo, tipo_alimentacao) VALUES (?, ?, ?, ?, ?, ?)");
            mysqli_stmt_bind_param($stmt, "ssiiss", $nome_lote, $objetivo, $lote_owner, $prop_id, $objetivo, $tipo_alim);
            mysqli_stmt_execute($stmt);
            $new_lote_id = mysqli_insert_id($conexao);
            mysqli_stmt_close($stmt);

            // Assign selected animals to this lote
            foreach ($animais as $aid) {
                if ($aid > 0) {
                    $sA = mysqli_prepare($conexao, "UPDATE animal SET lote_id = ? WHERE id = ?");
                    mysqli_stmt_bind_param($sA, "ii", $new_lote_id, $aid);
                    mysqli_stmt_execute($sA);
                    mysqli_stmt_close($sA);
                }
            }
            $msgRedir = 'lote_adicionado';
        }
        header("Location: propriedades.php?msg=$msgRedir"); exit();

    } elseif ($acao === 'editar_lote') {
        $lote_id   = intval($_POST['lote_id'] ?? 0);
        $nome_lote = trim($_POST['nome_lote'] ?? '');
        $prop_id   = intval($_POST['prop_id'] ?? 0);
        $objetivo  = trim($_POST['objetivo'] ?? '');
        $tipo_alim = trim($_POST['tipo_alimentacao'] ?? '');
        $animais   = array_map('intval', $_POST['animais'] ?? []);
        $lote_owner = $isAdmin ? intval($_POST['lote_dono'] ?? $user_id) : $user_id;

        if ($lote_id > 0 && $nome_lote !== '') {
            if ($isAdmin) {
                $stmt = mysqli_prepare($conexao, "UPDATE lote SET nome=?, tipo=?, user_id=?, propriedade_id=?, objetivo=?, tipo_alimentacao=? WHERE id=?");
                mysqli_stmt_bind_param($stmt, "ssiissi", $nome_lote, $objetivo, $lote_owner, $prop_id, $objetivo, $tipo_alim, $lote_id);
            } else {
                $stmt = mysqli_prepare($conexao, "UPDATE lote SET nome=?, tipo=?, propriedade_id=?, objetivo=?, tipo_alimentacao=? WHERE id=? AND user_id=?");
                mysqli_stmt_bind_param($stmt, "ssissii", $nome_lote, $objetivo, $prop_id, $objetivo, $tipo_alim, $lote_id, $user_id);
            }
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);

            // Reset animals: unset all from this lote, then re-assign selected
            $sClr = mysqli_prepare($conexao, "UPDATE animal SET lote_id = NULL WHERE lote_id = ?");
            mysqli_stmt_bind_param($sClr, "i", $lote_id);
            mysqli_stmt_execute($sClr);
            mysqli_stmt_close($sClr);

            foreach ($animais as $aid) {
                if ($aid > 0) {
                    $sA = mysqli_prepare($conexao, "UPDATE animal SET lote_id = ? WHERE id = ?");
                    mysqli_stmt_bind_param($sA, "ii", $lote_id, $aid);
                    mysqli_stmt_execute($sA);
                    mysqli_stmt_close($sA);
                }
            }
            $msgRedir = 'lote_editado';
        }
        header("Location: propriedades.php?msg=$msgRedir"); exit();

    } elseif ($acao === 'deletar_lote') {
        $lote_id = intval($_POST['lote_id'] ?? 0);
        if ($lote_id > 0) {
            // Unassign animals first
            $sClr = mysqli_prepare($conexao, "UPDATE animal SET lote_id = NULL WHERE lote_id = ?");
            mysqli_stmt_bind_param($sClr, "i", $lote_id);
            mysqli_stmt_execute($sClr);
            mysqli_stmt_close($sClr);

            if ($isAdmin) {
                $stmt = mysqli_prepare($conexao, "DELETE FROM lote WHERE id = ?");
                mysqli_stmt_bind_param($stmt, "i", $lote_id);
            } else {
                $stmt = mysqli_prepare($conexao, "DELETE FROM lote WHERE id = ? AND user_id = ?");
                mysqli_stmt_bind_param($stmt, "ii", $lote_id, $user_id);
            }
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);
        }
        header("Location: propriedades.php?msg=lote_deletado"); exit();
    }
}

// ============================================================
// FETCH DATA
// ============================================================

// Fetch properties
if ($isAdmin) {
    $resProps = mysqli_query($conexao,
        "SELECT p.id, p.nome, u.username AS produtor_nome, u.user_id AS produtor_id
         FROM propriedades p JOIN usuario u ON p.produtor_id = u.user_id ORDER BY p.nome ASC");
} else {
    $stmtP = mysqli_prepare($conexao,
        "SELECT p.id, p.nome, u.username AS produtor_nome, u.user_id AS produtor_id
         FROM propriedades p JOIN usuario u ON p.produtor_id = u.user_id WHERE p.produtor_id = ? ORDER BY p.nome ASC");
    mysqli_stmt_bind_param($stmtP, "i", $user_id);
    mysqli_stmt_execute($stmtP);
    $resProps = mysqli_stmt_get_result($stmtP);
}
$propriedades = [];
while ($p = mysqli_fetch_assoc($resProps)) $propriedades[] = $p;

// Fetch lotes grouped by propriedade_id
$lotesMap = [];
if (!empty($propriedades)) {
    $propIds      = array_column($propriedades, 'id');
    $inPlaceholders = implode(',', array_fill(0, count($propIds), '?'));
    $types        = str_repeat('i', count($propIds));
    $sqlL = "SELECT l.id, l.nome, l.tipo, l.user_id, l.propriedade_id, l.objetivo, l.tipo_alimentacao,
                    u.username AS dono_nome,
                    (SELECT COUNT(*) FROM animal a WHERE a.lote_id = l.id) AS qtd_animais
             FROM lote l
             LEFT JOIN usuario u ON l.user_id = u.user_id
             WHERE l.propriedade_id IN ($inPlaceholders)
             ORDER BY l.nome ASC";
    $stmtL = mysqli_prepare($conexao, $sqlL);
    mysqli_stmt_bind_param($stmtL, $types, ...$propIds);
    mysqli_stmt_execute($stmtL);
    $resL = mysqli_stmt_get_result($stmtL);
    while ($lote = mysqli_fetch_assoc($resL)) {
        $lotesMap[$lote['propriedade_id']][] = $lote;
    }
    mysqli_stmt_close($stmtL);
}

// Fetch animals available for the current user (for multi-select)
$animaisDisponiveis = [];
if ($isAdmin) {
    $resAn = mysqli_query($conexao,
        "SELECT a.id, a.especie, a.lote_id FROM animal a ORDER BY a.id ASC");
    if ($resAn) while ($an = mysqli_fetch_assoc($resAn)) $animaisDisponiveis[] = $an;
} else {
    $stmtAn = mysqli_prepare($conexao,
        "SELECT a.id, a.especie, a.lote_id
         FROM animal a
         LEFT JOIN lote l ON a.lote_id = l.id
         WHERE l.user_id = ? OR a.lote_id IS NULL
         ORDER BY a.id ASC");
    mysqli_stmt_bind_param($stmtAn, "i", $user_id);
    mysqli_stmt_execute($stmtAn);
    $resAn = mysqli_stmt_get_result($stmtAn);
    while ($an = mysqli_fetch_assoc($resAn)) $animaisDisponiveis[] = $an;
    mysqli_stmt_close($stmtAn);
}

// Fetch produtores list (for admin)
$produtoresList = [];
if ($isAdmin) {
    $resProd = mysqli_query($conexao, "SELECT user_id, username FROM usuario WHERE tipo = 'produtor' ORDER BY username ASC");
    if ($resProd) while ($pr = mysqli_fetch_assoc($resProd)) $produtoresList[] = $pr;
}

$nomeUsuario = htmlspecialchars($_SESSION['usuario_nome'] ?? 'Produtor');
$fazenda     = htmlspecialchars(trim($_SESSION['usuario_fazenda'] ?? '') !== '' ? $_SESSION['usuario_fazenda'] : 'Nenhuma propriedade cadastrada.');

// Serialize animals map for JS (lote_id -> [animal_ids])
$animalLoteMapJS = [];
foreach ($animaisDisponiveis as $an) {
    if ($an['lote_id']) $animalLoteMapJS[$an['lote_id']][] = $an['id'];
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ControlCabra - Propriedades e Lotes</title>
    <link rel="stylesheet" href="estatisticas.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        /* ===================== PROPRIEDADE CARDS ===================== */
        .propriedade-card {
            background-color: var(--card-bg);
            border: 1px solid var(--border-color);
            border-radius: 12px;
            margin-bottom: 16px;
            overflow: hidden;
            transition: border-color 0.2s, box-shadow 0.2s;
        }
        .propriedade-card:hover { border-color: var(--primary); box-shadow: 0 2px 12px rgba(18,85,52,0.08); }

        .prop-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 18px 20px;
            flex-wrap: wrap;
            gap: 12px;
        }
        .prop-header-left { display: flex; align-items: center; gap: 14px; }
        .prop-icon {
            width: 44px; height: 44px; border-radius: 10px;
            background: var(--primary-light);
            display: flex; align-items: center; justify-content: center;
            color: var(--primary); flex-shrink: 0;
        }
        .prop-info h4 { margin: 0 0 3px 0; color: var(--text-dark); font-size: 1.05rem; font-weight: 700; }
        .prop-info p  { margin: 0; color: var(--text-muted); font-size: 0.82rem; }
        .prop-actions { display: flex; align-items: center; gap: 8px; flex-wrap: wrap; }

        /* ===================== VER LOTES TOGGLE ===================== */
        .btn-ver-lotes {
            display: inline-flex; align-items: center; gap: 6px;
            padding: 7px 14px; border-radius: 8px; cursor: pointer;
            background: var(--bg-main); border: 1px solid var(--border-color);
            color: var(--text-dark); font-size: 0.85rem; font-weight: 600;
            transition: all 0.2s;
        }
        .btn-ver-lotes:hover { background: var(--primary-light); border-color: var(--primary); color: var(--primary); }
        .btn-ver-lotes .icon-expand { transition: transform 0.3s; display: inline-block; }
        .btn-ver-lotes.expanded .icon-expand { transform: rotate(45deg); }

        /* ===================== LOTES PANEL ===================== */
        .lotes-panel {
            display: none;
            border-top: 1px solid var(--border-color);
            padding: 0 20px 16px;
            animation: fadeInDown 0.22s ease;
        }
        .lotes-panel.open { display: block; }

        @keyframes fadeInDown {
            from { opacity: 0; transform: translateY(-6px); }
            to   { opacity: 1; transform: translateY(0); }
        }

        .lotes-panel-header {
            display: flex; align-items: center; justify-content: space-between;
            padding: 14px 0 10px;
        }
        .lotes-panel-header h5 {
            margin: 0; font-size: 0.9rem; font-weight: 600;
            color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.06em;
        }

        .lote-row {
            display: flex; align-items: center; justify-content: space-between;
            padding: 10px 14px; border-radius: 8px; margin-bottom: 6px;
            background: var(--bg-main); border: 1px solid var(--border-color);
            flex-wrap: wrap; gap: 10px;
            transition: background 0.2s;
        }
        .lote-row:hover { background: var(--primary-light); border-color: var(--primary); }
        .lote-row-left { display: flex; align-items: center; gap: 12px; }
        .lote-badge {
            background: var(--primary); color: white;
            font-size: 0.72rem; font-weight: 700;
            padding: 2px 8px; border-radius: 20px;
            white-space: nowrap;
        }
        .lote-info strong { display: block; font-size: 0.92rem; color: var(--text-dark); }
        .lote-info span   { font-size: 0.78rem; color: var(--text-muted); }
        .lote-row-actions { display: flex; gap: 6px; flex-shrink: 0; }

        .lote-empty {
            padding: 18px; text-align: center;
            color: var(--text-muted); font-size: 0.9rem;
            border: 1px dashed var(--border-color); border-radius: 8px;
            background: var(--bg-main); margin-bottom: 8px;
        }

        /* ===================== OBJETIVO BADGE ===================== */
        .obj-badge {
            display: inline-block; padding: 2px 8px; border-radius: 20px;
            font-size: 0.72rem; font-weight: 600; margin-left: 6px;
        }
        .obj-abate          { background:#ffebee; color:#c62828; }
        .obj-reproducao     { background:#e8f5e9; color:#1b5e20; }
        .obj-producao_carne { background:#fff8e1; color:#e65100; }
        .obj-producao_la    { background:#f3e5f5; color:#6a1b9a; }
        .obj-producao_pele  { background:#e3f2fd; color:#0d47a1; }

        /* ===================== MODAL LOTE ===================== */
        .form-group-lote { margin-bottom: 15px; }
        .form-group-lote label {
            display: block; margin-bottom: 5px; font-size: 0.88rem;
            font-weight: 600; color: var(--text-dark);
        }
        .form-group-lote input,
        .form-group-lote select,
        .form-group-lote textarea {
            width: 100%; padding: 10px 12px;
            border: 1px solid var(--border-color); border-radius: 8px;
            background-color: var(--bg-main); color: var(--text-dark);
            font-family: inherit; font-size: 0.9rem; box-sizing: border-box;
            transition: border-color 0.2s;
        }
        .form-group-lote input:focus,
        .form-group-lote select:focus { border-color: var(--primary); outline: none; }

        .animal-select-box {
            border: 1px solid var(--border-color); border-radius: 8px;
            background: var(--bg-main); max-height: 160px; overflow-y: auto;
            padding: 8px;
        }
        .animal-check-item {
            display: flex; align-items: center; gap: 8px;
            padding: 5px 4px; border-radius: 5px; cursor: pointer;
            font-size: 0.85rem; color: var(--text-dark);
        }
        .animal-check-item:hover { background: var(--primary-light); }
        .animal-check-item input[type="checkbox"] { accent-color: var(--primary); width: 15px; height: 15px; }

        .qtd-display {
            display: inline-flex; align-items: center; gap: 6px;
            background: var(--primary-light); color: var(--primary);
            border-radius: 20px; padding: 4px 12px; font-size: 0.85rem;
            font-weight: 700; margin-top: 6px;
        }

        .form-row-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 14px; }
        @media (max-width: 480px) { .form-row-2 { grid-template-columns: 1fr; } }

        .modal-actions { display: flex; justify-content: flex-end; gap: 10px; margin-top: 20px; }

        /* ===================== ALERT ===================== */
        .alert { padding: 12px 16px; border-radius: 8px; margin-bottom: 20px; font-weight: 500; }
        .alert-success { background: #d4edda; color: #155724; }
        .alert-error   { background: #ffebee; color: #c62828; }
    </style>
</head>
<body>

    <header class="mobile-header">
        <div class="mobile-header-left" style="width:auto; flex:1; display:flex; align-items:center; gap:10px; min-width:0;">
            <img src="logoControlCabra.png" alt="Logo" class="mobile-logo" style="flex-shrink:0;">
            <span class="mobile-page-title" style="text-align:left;">Propriedades</span>
        </div>
        <div class="mobile-header-right" style="width:auto; display:flex; align-items:center; gap:6px; flex-shrink:0;">
            <button class="notification-btn btn btn-icon" id="notificationBtn" aria-label="Notificações">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/>
                    <path d="M13.73 21a2 2 0 0 1-3.46 0"/>
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
                <?php if (!isset($_SESSION['usuario_tipo']) || strtolower($_SESSION['usuario_tipo']) !== 'visitante'): ?>
                    <a href="propriedades.php" class="nav-item active">Propriedades</a>
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
                            <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path>
                            <polyline points="9 22 9 12 15 12 15 22"></polyline>
                        </svg>
                        Propriedades e Lotes
                    </h1>
                    <p class="page-subtitle">Gerencie as propriedades e os lotes vinculados à sua conta.</p>
                </div>
                <div class="header-actions">
                    <button class="btn btn-icon notification-btn" id="notificationBtnDesktop" aria-label="Notificações">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"></path>
                            <path d="M13.73 21a2 2 0 0 1-3.46 0"></path>
                        </svg>
                        <?php if ($notificationCount > 0): ?>
                        <span class="badge" id="notificationCountDesktop"><?php echo $notificationCount; ?></span>
                        <?php endif; ?>
                    </button>
                </div>
            </header>

            <?php if (isset($_GET['msg'])): ?>
                <?php
                $alertas = [
                    'sucesso_adicionar' => 'Propriedade criada com sucesso!',
                    'sucesso_deletar'   => 'Propriedade removida com sucesso!',
                    'lote_adicionado'   => 'Lote criado com sucesso!',
                    'lote_editado'      => 'Lote atualizado com sucesso!',
                    'lote_deletado'     => 'Lote removido com sucesso!',
                ];
                $msg = $_GET['msg'];
                if (isset($alertas[$msg])): ?>
                    <div class="alert alert-success"><?= $alertas[$msg] ?></div>
                <?php else: ?>
                    <div class="alert alert-error">Operação não pôde ser concluída.</div>
                <?php endif; ?>
            <?php endif; ?>

            <!-- ===== SEÇÃO: PROPRIEDADES CADASTRADAS ===== -->
            <section class="card mt-4">
                <div class="card-header admin-tools">
                    <div class="admin-tools-left">
                        <h3 class="card-title" style="margin:0;">Propriedades Cadastradas</h3>
                    </div>
                    <?php if (!$isVisitante): ?>
                    <div class="admin-tools-right">
                        <button type="button" class="btn-add" onclick="abrirModal('addPropModal')">
                            <span style="font-size:1.1rem;">+</span> Nova Propriedade
                        </button>
                    </div>
                    <?php endif; ?>
                </div>

                <div style="padding: 0 20px 20px;">
                    <?php if (empty($propriedades)): ?>
                        <p style="text-align:center; color:var(--text-muted); padding:30px 0;">Nenhuma propriedade cadastrada ainda.</p>
                    <?php else: ?>
                        <?php foreach ($propriedades as $prop):
                            $pid      = $prop['id'];
                            $lotesProp = $lotesMap[$pid] ?? [];
                            $qtdLotes  = count($lotesProp);
                        ?>
                        <div class="propriedade-card" id="propcard-<?= $pid ?>">
                            <!-- Header da Propriedade -->
                            <div class="prop-header">
                                <div class="prop-header-left">
                                    <div class="prop-icon">
                                        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/>
                                            <polyline points="9 22 9 12 15 12 15 22"/>
                                        </svg>
                                    </div>
                                    <div class="prop-info">
                                        <h4><?= htmlspecialchars($prop['nome']) ?></h4>
                                        <p>ID: <?= $pid ?><?php if ($isAdmin): ?> &bull; Produtor: <?= htmlspecialchars($prop['produtor_nome']) ?><?php endif; ?> &bull; <?= $qtdLotes ?> lote<?= $qtdLotes !== 1 ? 's' : '' ?></p>
                                    </div>
                                </div>
                                <div class="prop-actions">
                                    <!-- Botão Ver Lotes -->
                                    <button type="button" class="btn-ver-lotes" id="btnLotes-<?= $pid ?>"
                                            onclick="toggleLotes(<?= $pid ?>)" title="Ver lotes desta propriedade">
                                        <span class="icon-expand" style="font-size:1.1rem; line-height:1;">+</span>
                                        Ver Lotes
                                    </button>
                                    <?php if (!$isVisitante): ?>
                                    <form method="POST" style="display:inline;"
                                          onsubmit="return confirm('Excluir esta propriedade? Os lotes vinculados a ela perderão o vínculo.');">
                                        <input type="hidden" name="form_action" value="deletar_propriedade">
                                        <input type="hidden" name="propriedade_id" value="<?= $pid ?>">
                                        <button type="submit" class="btn-sm btn-danger">Excluir</button>
                                    </form>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <!-- Painel de Lotes (oculto por padrão) -->
                            <div class="lotes-panel" id="lotes-panel-<?= $pid ?>">
                                <div class="lotes-panel-header">
                                    <h5>Lotes</h5>
                                    <?php if (!$isVisitante): ?>
                                    <button type="button" class="btn-add" style="height:32px; min-width:110px; font-size:0.8rem; padding:0 12px;"
                                            onclick="abrirModalNovoLote(<?= $pid ?>)">
                                        <span>+</span> Novo Lote
                                    </button>
                                    <?php endif; ?>
                                </div>

                                <?php if (empty($lotesProp)): ?>
                                    <div class="lote-empty">Nenhum lote cadastrado nesta propriedade ainda.</div>
                                <?php else: ?>
                                    <?php foreach ($lotesProp as $lote): ?>
                                    <?php
                                        $objLabel = [
                                            'abate'          => 'Abate',
                                            'reproducao'     => 'Reprodução',
                                            'producao_carne' => 'Prod. Carne',
                                            'producao_la'    => 'Prod. Lã',
                                            'producao_pele'  => 'Prod. Pele',
                                        ][$lote['objetivo'] ?? ''] ?? ucfirst($lote['objetivo'] ?? '—');
                                        $objClass = 'obj-' . ($lote['objetivo'] ?? '');
                                    ?>
                                    <div class="lote-row">
                                        <div class="lote-row-left">
                                            <span class="lote-badge">ID <?= $lote['id'] ?></span>
                                            <div class="lote-info">
                                                <strong><?= htmlspecialchars($lote['nome']) ?></strong>
                                                <span>
                                                    <?= $lote['qtd_animais'] ?> animal(is)
                                                    <?php if ($lote['objetivo']): ?>
                                                        &bull; <span class="obj-badge <?= $objClass ?>"><?= $objLabel ?></span>
                                                    <?php endif; ?>
                                                    <?php if ($lote['tipo_alimentacao']): ?>
                                                        &bull; <?= htmlspecialchars($lote['tipo_alimentacao']) ?>
                                                    <?php endif; ?>
                                                    <?php if ($isAdmin && $lote['dono_nome']): ?>
                                                        &bull; Dono: <?= htmlspecialchars($lote['dono_nome']) ?>
                                                    <?php endif; ?>
                                                </span>
                                            </div>
                                        </div>
                                        <?php if (!$isVisitante): ?>
                                        <div class="lote-row-actions">
                                            <button type="button" class="btn-sm btn-edit"
                                                    onclick="abrirModalEditarLote(<?= htmlspecialchars(json_encode($lote)) ?>)">
                                                Editar
                                            </button>
                                            <form method="POST" style="display:inline;"
                                                  onsubmit="return confirm('Excluir o lote \'<?= htmlspecialchars(addslashes($lote['nome'])) ?>\'? Os animais serão desvinculados.');">
                                                <input type="hidden" name="form_action" value="deletar_lote">
                                                <input type="hidden" name="lote_id" value="<?= $lote['id'] ?>">
                                                <button type="submit" class="btn-sm btn-danger">Excluir</button>
                                            </form>
                                        </div>
                                        <?php endif; ?>
                                    </div>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div><!-- /lotes-panel -->
                        </div><!-- /propriedade-card -->
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </section>
        </main>
    </div>

    <!-- ============================= MODAL: NOVA PROPRIEDADE ============================= -->
    <div id="addPropModal" class="modal">
        <div class="modal-content" style="max-width:460px;">
            <span class="close-modal" onclick="fecharModal('addPropModal')">&times;</span>
            <h2 style="margin-top:0; margin-bottom:20px; color:var(--primary);">Nova Propriedade</h2>
            <form method="POST">
                <input type="hidden" name="form_action" value="adicionar_propriedade">
                <div class="form-group-lote">
                    <label>Nome da Propriedade *</label>
                    <input type="text" name="nome_propriedade" required placeholder="Ex: Fazenda Boa Vista">
                </div>
                <?php if ($isAdmin && !empty($produtoresList)): ?>
                <div class="form-group-lote">
                    <label>Produtor Responsável *</label>
                    <select name="produtor_id" required>
                        <option value="">Selecione o produtor</option>
                        <?php foreach ($produtoresList as $pr): ?>
                            <option value="<?= $pr['user_id'] ?>"><?= htmlspecialchars($pr['username']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <?php endif; ?>
                <div class="modal-actions">
                    <button type="button" class="btn-sm btn-danger" onclick="fecharModal('addPropModal')">Cancelar</button>
                    <button type="submit" class="btn-sm btn-edit">Salvar</button>
                </div>
            </form>
        </div>
    </div>

    <!-- ============================= MODAL: NOVO LOTE ============================= -->
    <div id="addLoteModal" class="modal">
        <div class="modal-content" style="max-width:580px;">
            <span class="close-modal" onclick="fecharModal('addLoteModal')">&times;</span>
            <h2 style="margin-top:0; margin-bottom:20px; color:var(--primary);">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="vertical-align:middle; margin-right:6px;">
                    <rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/>
                    <rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/>
                </svg>
                Novo Lote
            </h2>
            <form method="POST" id="formAddLote">
                <input type="hidden" name="form_action" value="adicionar_lote">
                <input type="hidden" name="prop_id" id="addLote_propId">

                <div class="form-row-2">
                    <div class="form-group-lote">
                        <label>Nome do Lote *</label>
                        <input type="text" name="nome_lote" required placeholder="Ex: Rebanho Alfa">
                    </div>
                    <div class="form-group-lote">
                        <label>Objetivo do Lote</label>
                        <select name="objetivo">
                            <option value="">— Selecione —</option>
                            <option value="abate">Abate</option>
                            <option value="reproducao">Reprodução</option>
                            <option value="producao_carne">Produção de Carne</option>
                            <option value="producao_la">Produção de Lã</option>
                            <option value="producao_pele">Produção de Pele</option>
                        </select>
                    </div>
                </div>

                <div class="form-group-lote">
                    <label>Tipo de Alimentação</label>
                    <input type="text" name="tipo_alimentacao" placeholder="Ex: Pastagem nativa, Ração concentrada...">
                </div>

                <?php if ($isAdmin && !empty($produtoresList)): ?>
                <div class="form-group-lote">
                    <label>Dono do Lote</label>
                    <select name="lote_dono">
                        <option value="">— Produtor da propriedade —</option>
                        <?php foreach ($produtoresList as $pr): ?>
                            <option value="<?= $pr['user_id'] ?>"><?= htmlspecialchars($pr['username']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <?php endif; ?>

                <div class="form-group-lote">
                    <label>Animais do Lote</label>
                    <?php if (empty($animaisDisponiveis)): ?>
                        <p style="color:var(--text-muted); font-size:0.85rem;">Nenhum animal cadastrado ainda.</p>
                    <?php else: ?>
                    <div class="animal-select-box" id="addAnimalBox">
                        <?php foreach ($animaisDisponiveis as $an): ?>
                        <label class="animal-check-item">
                            <input type="checkbox" name="animais[]" value="<?= $an['id'] ?>" class="add-animal-cb">
                            <span>Animal #<?= $an['id'] ?> — <?= htmlspecialchars(ucfirst($an['especie'] ?? 'Esp. desconhecida')) ?></span>
                        </label>
                        <?php endforeach; ?>
                    </div>
                    <div class="qtd-display" id="addQtdDisplay">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/></svg>
                        <span id="addQtdNum">0</span> animal(is) selecionado(s)
                    </div>
                    <?php endif; ?>
                </div>

                <div class="modal-actions">
                    <button type="button" class="btn-sm btn-danger" onclick="fecharModal('addLoteModal')">Cancelar</button>
                    <button type="submit" class="btn-sm btn-edit">Criar Lote</button>
                </div>
            </form>
        </div>
    </div>

    <!-- ============================= MODAL: EDITAR LOTE ============================= -->
    <div id="editLoteModal" class="modal">
        <div class="modal-content" style="max-width:580px;">
            <span class="close-modal" onclick="fecharModal('editLoteModal')">&times;</span>
            <h2 style="margin-top:0; margin-bottom:20px; color:var(--primary);">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="vertical-align:middle; margin-right:6px;">
                    <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/>
                    <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/>
                </svg>
                Editar Lote
            </h2>
            <form method="POST" id="formEditLote">
                <input type="hidden" name="form_action" value="editar_lote">
                <input type="hidden" name="lote_id" id="edit_lote_id">
                <input type="hidden" name="prop_id" id="edit_prop_id">

                <div class="form-row-2">
                    <div class="form-group-lote">
                        <label>Nome do Lote *</label>
                        <input type="text" name="nome_lote" id="edit_nome_lote" required>
                    </div>
                    <div class="form-group-lote">
                        <label>Objetivo do Lote</label>
                        <select name="objetivo" id="edit_objetivo">
                            <option value="">— Selecione —</option>
                            <option value="abate">Abate</option>
                            <option value="reproducao">Reprodução</option>
                            <option value="producao_carne">Produção de Carne</option>
                            <option value="producao_la">Produção de Lã</option>
                            <option value="producao_pele">Produção de Pele</option>
                        </select>
                    </div>
                </div>

                <div class="form-group-lote">
                    <label>Tipo de Alimentação</label>
                    <input type="text" name="tipo_alimentacao" id="edit_tipo_alimentacao" placeholder="Ex: Pastagem nativa, Ração concentrada...">
                </div>

                <?php if ($isAdmin && !empty($produtoresList)): ?>
                <div class="form-group-lote">
                    <label>Dono do Lote</label>
                    <select name="lote_dono" id="edit_lote_dono">
                        <option value="">— Produtor da propriedade —</option>
                        <?php foreach ($produtoresList as $pr): ?>
                            <option value="<?= $pr['user_id'] ?>"><?= htmlspecialchars($pr['username']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <?php endif; ?>

                <div class="form-group-lote">
                    <label>Animais do Lote</label>
                    <?php if (empty($animaisDisponiveis)): ?>
                        <p style="color:var(--text-muted); font-size:0.85rem;">Nenhum animal cadastrado ainda.</p>
                    <?php else: ?>
                    <div class="animal-select-box" id="editAnimalBox">
                        <?php foreach ($animaisDisponiveis as $an): ?>
                        <label class="animal-check-item">
                            <input type="checkbox" name="animais[]" value="<?= $an['id'] ?>" class="edit-animal-cb"
                                   data-animal-id="<?= $an['id'] ?>">
                            <span>Animal #<?= $an['id'] ?> — <?= htmlspecialchars(ucfirst($an['especie'] ?? 'Esp. desconhecida')) ?></span>
                        </label>
                        <?php endforeach; ?>
                    </div>
                    <div class="qtd-display" id="editQtdDisplay">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/></svg>
                        <span id="editQtdNum">0</span> animal(is) selecionado(s)
                    </div>
                    <?php endif; ?>
                </div>

                <div class="modal-actions">
                    <button type="button" class="btn-sm btn-danger" onclick="fecharModal('editLoteModal')">Cancelar</button>
                    <button type="submit" class="btn-sm btn-edit">Salvar Alterações</button>
                </div>
            </form>
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
                    <p style="color: var(--text-muted); text-align: center; padding: 12px 0;">Nenhuma notificação pendente!</p>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="estatisticas.js"></script>
    <script src="notifications.js"></script>
    <script>
        // ===== Animal-to-Lote map from PHP =====
        const animalLoteMap = <?= json_encode($animalLoteMapJS) ?>;

        // ===== MODAL HELPERS =====
        function abrirModal(id) {
            document.getElementById(id).style.display = 'block';
        }
        function fecharModal(id) {
            document.getElementById(id).style.display = 'none';
        }
        window.addEventListener('click', function(e) {
            ['addPropModal', 'addLoteModal', 'editLoteModal'].forEach(id => {
                const el = document.getElementById(id);
                if (e.target === el) fecharModal(id);
            });
        });
        document.addEventListener('keydown', e => {
            if (e.key === 'Escape') {
                ['addPropModal', 'addLoteModal', 'editLoteModal'].forEach(fecharModal);
            }
        });

        // ===== TOGGLE LOTES PANEL =====
        function toggleLotes(propId) {
            const panel = document.getElementById('lotes-panel-' + propId);
            const btn   = document.getElementById('btnLotes-' + propId);
            const isOpen = panel.classList.contains('open');
            panel.classList.toggle('open', !isOpen);
            btn.classList.toggle('expanded', !isOpen);
        }

        // ===== NOVO LOTE =====
        function abrirModalNovoLote(propId) {
            document.getElementById('addLote_propId').value = propId;
            // Reset all checkboxes
            document.querySelectorAll('.add-animal-cb').forEach(cb => cb.checked = false);
            atualizarQtd('add');
            abrirModal('addLoteModal');
        }

        // ===== EDITAR LOTE =====
        function abrirModalEditarLote(lote) {
            document.getElementById('edit_lote_id').value          = lote.id;
            document.getElementById('edit_prop_id').value          = lote.propriedade_id;
            document.getElementById('edit_nome_lote').value        = lote.nome;
            document.getElementById('edit_objetivo').value         = lote.objetivo || '';
            document.getElementById('edit_tipo_alimentacao').value = lote.tipo_alimentacao || '';

            const donoEl = document.getElementById('edit_lote_dono');
            if (donoEl) donoEl.value = lote.user_id || '';

            // Pre-select animals
            const animaisDoLote = animalLoteMap[lote.id] || [];
            document.querySelectorAll('.edit-animal-cb').forEach(cb => {
                cb.checked = animaisDoLote.includes(parseInt(cb.dataset.animalId));
            });
            atualizarQtd('edit');
            abrirModal('editLoteModal');
        }

        // ===== CONTADOR DE ANIMAIS =====
        function atualizarQtd(prefix) {
            const cbs    = document.querySelectorAll('.' + prefix + '-animal-cb:checked');
            const numEl  = document.getElementById(prefix + 'QtdNum');
            if (numEl) numEl.textContent = cbs.length;
        }

        document.querySelectorAll('.add-animal-cb').forEach(cb =>
            cb.addEventListener('change', () => atualizarQtd('add')));
        document.querySelectorAll('.edit-animal-cb').forEach(cb =>
            cb.addEventListener('change', () => atualizarQtd('edit')));
    </script>
</body>
</html>
