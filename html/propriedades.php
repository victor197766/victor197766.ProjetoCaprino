<?php
session_start();
if (!isset($_SESSION['usuario_id'])) {
    header('Location: recuperarConta.html');
    exit();
}

include '../db/connection.php';

// Apenas produtores (ou admin) devem gerenciar propriedades.
// Empregados rurais (visitante no BD) não criam propriedades, eles são vinculados.
if (isset($_SESSION['usuario_tipo']) && strtolower($_SESSION['usuario_tipo']) === 'visitante') {
    header('Location: estatisticas.php?msg=acesso_negado');
    exit();
}

$user_id = intval($_SESSION['usuario_id']);
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
$fazenda = htmlspecialchars($_SESSION['usuario_fazenda'] ?? 'Gerenciamento');
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ControlCabra - Propriedades</title>
    <link rel="stylesheet" href="estatisticas.css">
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
        <div class="logo">
            <span class="logo-icon">🐐</span>
            <span>ControlCabra</span>
        </div>
        <button class="menu-toggle" id="menuToggle" aria-label="Abrir menu">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <line x1="3" y1="12" x2="21" y2="12"></line>
                <line x1="3" y1="6" x2="21" y2="6"></line>
                <line x1="3" y1="18" x2="21" y2="18"></line>
            </svg>
        </button>
    </header>

    <div class="app-container">
        <aside class="sidebar" id="sidebar">
            <div class="sidebar-header">
                <div class="logo">
                    <span class="logo-icon">🐐</span>
                    <span>ControlCabra</span>
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
                    <h1 class="page-title">🌾 Minhas Propriedades</h1>
                    <p class="page-subtitle">Gerencie as propriedades vinculadas à sua conta.</p>
                </div>
                <div class="header-actions">
                    <button class="btn-primary" onclick="document.getElementById('addPropModal').style.display='block'">+ Nova Propriedade</button>
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
                <div class="card-header">
                    <h3 class="card-title">Propriedades Cadastradas</h3>
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
                                        <button type="submit" class="btn-danger" style="padding: 8px 12px; border:none; border-radius:6px; cursor:pointer; background-color: var(--danger); color: white;">Excluir</button>
                                    </form>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <p style="text-align: center; color: var(--muted); padding: 20px;">Nenhuma propriedade registrada ainda.</p>
                    <?php endif; ?>
                </div>
            </section>
        </main>
    </div>

    <!-- Modal Adicionar Propriedade -->
    <div id="addPropModal" class="modal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); z-index:1000;">
        <div class="modal-content" style="background:var(--card-bg); max-width:400px; margin:100px auto; padding:20px; border-radius:8px;">
            <span class="close-modal" onclick="document.getElementById('addPropModal').style.display='none'" style="float:right; cursor:pointer; font-size:24px;">&times;</span>
            <h2 style="margin-top:0; color:var(--primary);">Criar Propriedade</h2>
            <form method="POST">
                <input type="hidden" name="form_action" value="adicionar_propriedade">
                <div style="margin-bottom:15px;">
                    <label style="display:block; margin-bottom:5px;">Nome da Fazenda/Propriedade</label>
                    <input type="text" name="nome_propriedade" required style="width:100%; padding:8px; border:1px solid var(--border-color); border-radius:4px; box-sizing:border-box;">
                </div>
                <div style="text-align:right;">
                    <button type="button" class="btn-danger" onclick="document.getElementById('addPropModal').style.display='none'" style="padding: 8px 12px; border:none; border-radius:4px; cursor:pointer; margin-right:10px;">Cancelar</button>
                    <button type="submit" class="btn-primary" style="padding: 8px 12px; border:none; border-radius:4px; cursor:pointer; background-color:var(--primary); color:white;">Salvar</button>
                </div>
            </form>
        </div>
    </div>
    
    <script src="estatisticas.js"></script>
</body>
</html>
