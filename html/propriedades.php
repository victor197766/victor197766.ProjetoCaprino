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

// Verificação para a coluna propriedade_id
$checkPropColumn = mysqli_query($conexao, "SHOW COLUMNS FROM usuario LIKE 'propriedade_id'");
if ($checkPropColumn && mysqli_num_rows($checkPropColumn) == 0) {
    mysqli_query($conexao, "ALTER TABLE usuario ADD COLUMN propriedade_id INT(11) NULL DEFAULT NULL");
}

// Apenas produtores (ou admin) devem gerenciar propriedades.
// Empregados rurais (visitante no BD) não criam propriedades, eles são vinculados.
if (isset($_SESSION['usuario_tipo']) && strtolower($_SESSION['usuario_tipo']) === 'visitante') {
    header('Location: estatisticas.php?msg=acesso_negado');
    exit();
}

$user_id = intval($_SESSION['usuario_id']);

// Fetch notifications for the current user
$query_avisos = "SELECT a.id, a.titulo, a.mensagem, a.data_criacao FROM avisos a WHERE a.destinatario_id IS NULL OR a.destinatario_id = ? ORDER BY a.id DESC";
$stmt_avisos = mysqli_prepare($conexao, $query_avisos);
mysqli_stmt_bind_param($stmt_avisos, "i", $_SESSION['usuario_id']);
mysqli_stmt_execute($stmt_avisos);
$resultadoAvisos = mysqli_stmt_get_result($stmt_avisos);
$notificationCount = mysqli_num_rows($resultadoAvisos);

$acao = $_POST['form_action'] ?? '';

// Lidar com criação e deleção
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($acao === 'adicionar_propriedade') {
        $nome = trim($_POST['nome_propriedade'] ?? '');
        if ($nome !== '') {
            $stmt = mysqli_prepare($conexao, "INSERT INTO propriedades (nome, produtor_id) VALUES (?, ?)");
            mysqli_stmt_bind_param($stmt, "si", $nome, $user_id);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);
            header("Location: propriedades.php?msg=sucesso_adicionar");
            exit();
        }
    } else if ($acao === 'deletar_propriedade') {
        $prop_id = intval($_POST['propriedade_id'] ?? 0);
        if ($prop_id > 0) {
            $stmt = mysqli_prepare($conexao, "DELETE FROM propriedades WHERE id = ? AND produtor_id = ?");
            mysqli_stmt_bind_param($stmt, "ii", $prop_id, $user_id);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);
            header("Location: propriedades.php?msg=sucesso_deletar");
            exit();
        }
    }
}

// Fetch Propriedades do Produtor
$sqlProps = "SELECT id, nome FROM propriedades WHERE produtor_id = ? ORDER BY nome ASC";
$stmtProps = mysqli_prepare($conexao, $sqlProps);
mysqli_stmt_bind_param($stmtProps, "i", $user_id);
mysqli_stmt_execute($stmtProps);
$resultProps = mysqli_stmt_get_result($stmtProps);

$nomeUsuario = htmlspecialchars($_SESSION['usuario_nome'] ?? 'Produtor');
$emailUsuario = htmlspecialchars($_SESSION['usuario_email'] ?? '');
$fazenda = htmlspecialchars(trim($_SESSION['usuario_fazenda'] ?? '') !== '' ? $_SESSION['usuario_fazenda'] : 'Nenhuma propriedade cadastrada.');
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ControlCabra - Propriedades</title>
    <link rel="stylesheet" href="estatisticas.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        .propriedade-card {
            background-color: var(--bg-main);
            border: 1px solid var(--border-color);
            border-radius: 8px;
            padding: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
        }
        .propriedade-info h4 {
            margin: 0 0 5px 0;
            color: var(--text-dark);
            font-size: 18px;
        }
        .propriedade-info p {
            margin: 0;
            color: var(--text-muted);
            font-size: 14px;
        }
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
                <?php if($notificationCount > 0): ?>
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
                <a href="propriedades.php" class="nav-item active">Propriedades</a>
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
                        Propriedades
                    </h1>
                    <p class="page-subtitle">Gerencie as propriedades vinculadas à sua conta.</p>
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

            <?php if (isset($_GET['msg'])): ?>
                <?php if ($_GET['msg'] == 'sucesso_adicionar'): ?>
                    <div class="alert alert-success" style="background-color: #d4edda; color: #155724; padding: 12px; border-radius: 6px; margin-bottom: 24px;">Propriedade criada com sucesso!</div>
                <?php elseif ($_GET['msg'] == 'sucesso_deletar'): ?>
                    <div class="alert alert-success" style="background-color: #d4edda; color: #155724; padding: 12px; border-radius: 6px; margin-bottom: 24px;">Propriedade removida com sucesso!</div>
                <?php endif; ?>
            <?php endif; ?>

            <section class="card mt-4">
                <div class="card-header admin-tools">
                    <div class="admin-tools-left">
                        <h3 class="card-title" style="margin: 0;">Propriedades Cadastradas</h3>
                    </div>
                    <div class="admin-tools-right">
                        <button type="button" class="btn-add" onclick="document.getElementById('addPropModal').style.display='flex'">
                            <span>+</span> Nova Propriedade
                        </button>
                    </div>
                </div>
                <div style="padding: 20px;">
                    <?php if (mysqli_num_rows($resultProps) > 0): ?>
                        <?php while ($prop = mysqli_fetch_assoc($resultProps)): ?>
                            <div class="propriedade-card">
                                <div class="propriedade-info">
                                    <h4><?= htmlspecialchars($prop['nome']) ?></h4>
                                    <p>ID: <?= $prop['id'] ?></p>
                                </div>
                                <div class="propriedade-acoes">
                                    <form method="POST" style="display:inline;" onsubmit="return confirm('Tem certeza que deseja excluir esta propriedade? Usuários vinculados perderão o acesso.');">
                                        <input type="hidden" name="form_action" value="deletar_propriedade">
                                        <input type="hidden" name="propriedade_id" value="<?= $prop['id'] ?>">
                                        <button type="submit" class="btn-sm btn-danger">Excluir</button>
                                    </form>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <p style="text-align: center; color: var(--muted); padding: 20px;">Nenhuma propriedade cadastrada ainda.</p>
                    <?php endif; ?>
                </div>
            </section>
        </main>
    </div>

    <!-- Modal Adicionar Propriedade -->
    <div id="addPropModal" class="modal">
        <div class="modal-content" style="max-width:450px;">
            <span class="close-modal" onclick="document.getElementById('addPropModal').style.display='none'" style="float:right; cursor:pointer; font-size:24px;">&times;</span>
            <h2 style="margin-top:0; margin-bottom: 20px; color:var(--primary);">Criar Propriedade</h2>
            <form method="POST">
                <input type="hidden" name="form_action" value="adicionar_propriedade">
                <div class="form-group" style="margin-bottom:15px;">
                    <label style="display:block; margin-bottom:5px;">Nome da propriedade</label>
                    <input type="text" name="nome_propriedade" required style="width:100%; padding:10px; border:1px solid var(--border-color); border-radius:6px; box-sizing:border-box; background-color: var(--bg-main); color: var(--text-dark);">
                </div>
                <div style="text-align:right; margin-top: 20px;">
                    <button type="button" class="btn-sm btn-danger" onclick="document.getElementById('addPropModal').style.display='none'" style="margin-right:10px;">Cancelar</button>
                    <button type="submit" class="btn-sm btn-edit">Salvar</button>
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
</body>
</html>
