<?php
session_start();
if (!isset($_SESSION['usuario_id'])) {
    header('Location: recuperarConta.html');
    exit();
}
include '../db/connection.php';

$user_id     = intval($_SESSION['usuario_id']);
$nomeUsuario = htmlspecialchars($_SESSION['usuario_nome'] ?? 'Usuário');
$fazenda     = htmlspecialchars(trim($_SESSION['usuario_fazenda'] ?? '') !== '' ? $_SESSION['usuario_fazenda'] : 'Nenhuma propriedade cadastrada.');
$tipoUsuario = strtolower($_SESSION['usuario_tipo'] ?? 'produtor');

if ($tipoUsuario === 'visitante') {
    header('Location: lista_animais.php');
    exit();
}

// Iniciais para o avatar
$partes = explode(' ', $nomeUsuario);
$iniciais = strtoupper(substr($partes[0], 0, 1));
if (count($partes) > 1) $iniciais .= strtoupper(substr(end($partes), 0, 1));

// Buscar Lotes do usuário
$lotes = [];
if ($tipoUsuario === 'admin' || $tipoUsuario === 'administrador') {
    $stmtL = mysqli_prepare($conexao, "SELECT id, nome FROM lote ORDER BY nome ASC");
} else {
    $stmtL = mysqli_prepare($conexao, "SELECT id, nome FROM lote WHERE user_id = ? ORDER BY nome ASC");
    mysqli_stmt_bind_param($stmtL, "i", $user_id);
}
mysqli_stmt_execute($stmtL);
$resL = mysqli_stmt_get_result($stmtL);
while ($l = mysqli_fetch_assoc($resL)) {
    $lotes[] = $l;
}
mysqli_stmt_close($stmtL);

// Buscar animais para Reprodutor/Matriz (vamos pegar os adultos do usuário)
$animaisReprodutores = [];
$animaisMatrizes = [];
if ($tipoUsuario === 'admin' || $tipoUsuario === 'administrador') {
    $stmtA = mysqli_prepare($conexao, "SELECT id, nome, identificador, sexo FROM animal");
} else {
    $stmtA = mysqli_prepare($conexao, "SELECT a.id, a.nome, a.identificador, a.sexo 
                                       FROM animal a 
                                       LEFT JOIN lote l ON a.lote_id = l.id 
                                       WHERE l.user_id = ?");
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

?>
<!DOCTYPE html>
<html lang="pt-BR" data-theme="sistema">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registro de Animais - ControlCabra</title>
    <link rel="stylesheet" href="identificacao.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        .sidebar-brand { padding: 30px 20px; display: flex; align-items: center; gap: 15px; border-bottom: 1px solid rgba(255,255,255,0.1); }
        .sidebar-logo { width: 60px; height: 60px; border-radius: 50%; object-fit: contain; flex-shrink: 0; }
        .toast-msg-container {
            display: none; background: #fff5f5; border: 1.5px solid #f5c2c7; color: #842029; 
            border-radius: 8px; padding: 10px 14px; margin-bottom: 14px; font-size: 0.88rem; font-weight: 500;
        }
        .toast-success {
            background: #f0fdf4 !important; border-color: #bbf7d0 !important; color: #14532d !important;
        }
    </style>
</head>
<body>

    <header class="mobile-header">
        <div class="mobile-header-left" style="width:auto; flex:1; display:flex; align-items:center; gap:10px; min-width:0;">
            <img src="logoControlCabra.png" alt="Logo" class="mobile-logo" style="flex-shrink:0;">
            <span class="mobile-page-title" style="text-align:left;">Identificação</span>
        </div>
        <div class="mobile-header-right">
            <button class="menu-toggle" id="menuToggle">
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
                <a href="cuidados.php" class="nav-item active">Cuidados</a>
                <a href="propriedades.php" class="nav-item">Propriedades</a>
                <a href="configuracoes.php" class="nav-item">Configurações</a>
                <a href="administracao.php" class="nav-item">Administração</a>
            </nav>

            <div class="user-profile">
                <div class="user-avatar">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>
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
                    <h1 class="page-title">Identificar Novo Animal</h1>
                    <p class="page-subtitle">Preencha os dados abaixo para registrar o caprino ou ovino</p>
                </div>
            </header>

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
                            <input type="number" name="idade" id="idade" class="form-control" placeholder="Ex: 6" required>
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

                    <div id="form-error" class="toast-msg-container"></div>
                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary btn-block">Salvar Identificação</button>
                        <button type="reset" class="btn btn-outline btn-block" onclick="document.getElementById('form-error').style.display='none'">Limpar Campos</button>
                    </div>
                </form>
            </section>
        </main>
    </div>

    <script src="identificacao.js"></script>
    <script>
        // Carregar status da URL (erro/sucesso)
        const params = new URLSearchParams(window.location.search);
        const errBox = document.getElementById('form-error');
        if (params.get('sucesso') === '1') {
            errBox.textContent = "Animal registrado com sucesso!";
            errBox.classList.add('toast-success');
            errBox.style.display = 'block';
            history.replaceState(null, '', window.location.pathname);
        } else if (params.get('erro')) {
            errBox.textContent = "Erro ao registrar: " + params.get('erro');
            errBox.classList.remove('toast-success');
            errBox.style.display = 'block';
            history.replaceState(null, '', window.location.pathname);
        }
    </script>
</body>
</html>