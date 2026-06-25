<?php
session_start();
if (!isset($_SESSION['usuario_id'])) {
    header('Location: recuperarConta.html');
    exit();
}

include '../db/connection.php';

// ===============================
// FUNÇÕES AUXILIARES
// ===============================
function somenteNumeros($valor) {
    return preg_replace('/\D/', '', $valor ?? '');
}

function redirecionarAdmin($msg) {
    header('Location: administracao.php?msg=' . urlencode($msg));
    exit();
}

function colunaExiste($conexao, $tabela, $coluna) {
    $coluna_escaped = mysqli_real_escape_string($conexao, $coluna);
    $tabela_escaped = mysqli_real_escape_string($conexao, $tabela);
    $sql = "SHOW COLUMNS FROM `$tabela_escaped` LIKE '$coluna_escaped'";
    $res = mysqli_query($conexao, $sql);
    $existe = false;
    if ($res) {
        $existe = mysqli_num_rows($res) > 0;
    }
    return $existe;
}

function valorSeguro($valor) {
    return htmlspecialchars($valor ?? '', ENT_QUOTES, 'UTF-8');
}

// CORREÇÃO: as verificações dinâmicas de SHOW COLUMNS e CREATE TABLE foram removidas.
// A coluna `suspenso` e a tabela `avisos` agora fazem parte do schema oficial (sql.sql),
// eliminando o overhead dessas queries de metadados em cada requisição.

$user_session_id = $_SESSION['usuario_id'];

// Verificar se o usuário logado é 'visitante'. Se sim, bloqueia o acesso.
$stmt_check = mysqli_prepare($conexao, "SELECT tipo FROM usuario WHERE user_id = ?");
mysqli_stmt_bind_param($stmt_check, "i", $user_session_id);
mysqli_stmt_execute($stmt_check);
$res_check = mysqli_stmt_get_result($stmt_check);
$user_logged_in = mysqli_fetch_assoc($res_check);
mysqli_stmt_close($stmt_check);

if ($user_logged_in && $user_logged_in['tipo'] === 'visitante') {
    header('Location: estatisticas.php');
    exit();
}

// ===============================
// ADICIONAR / EDITAR USUÁRIO
// ===============================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['form_action'])) {
    $acao = $_POST['form_action'];

    if ($acao === 'adicionar_aviso') {
        $destinatario = $_POST['destinatario'] === 'todos' ? null : intval($_POST['destinatario']);
        $lote = (empty($_POST['lote_aviso']) || $_POST['lote_aviso'] === 'todos') ? null : intval($_POST['lote_aviso']);
        $mensagem = trim($_POST['mensagem'] ?? '');

        if ($mensagem !== '') {
            $sql = "INSERT INTO avisos (destinatario_id, lote_id, mensagem) VALUES (?, ?, ?)";
            $stmt = mysqli_prepare($conexao, $sql);
            mysqli_stmt_bind_param($stmt, "iis", $destinatario, $lote, $mensagem);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);
            redirecionarAdmin('aviso_adicionado');
        } else {
            redirecionarAdmin('erro');
        }
    }

    if ($acao === 'deletar_aviso') {
        $aviso_id = intval($_POST['aviso_id']);
        if ($aviso_id > 0) {
            $sql = "DELETE FROM avisos WHERE id = ?";
            $stmt = mysqli_prepare($conexao, $sql);
            mysqli_stmt_bind_param($stmt, "i", $aviso_id);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);
            redirecionarAdmin('aviso_removido');
        }
    }

    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $tipo = $_POST['tipo'] ?? 'produtor';
    $propriedade_id = !empty($_POST['propriedade_id']) ? intval($_POST['propriedade_id']) : null;
    $num_telefone = somenteNumeros($_POST['num_telefone'] ?? '');
    $CPF = somenteNumeros($_POST['CPF'] ?? '');
    $CNPJ = somenteNumeros($_POST['CNPJ'] ?? '');

    if (!in_array($tipo, ['produtor', 'visitante'], true)) {
        $tipo = 'produtor';
    }

    if ($acao === 'adicionar_usuario') {
        $senha = $_POST['senha'] ?? '';

        if ($username === '' || $email === '' || $senha === '') {
            redirecionarAdmin('erro');
        }

        // Usa senha criptografada. Caso sua coluna tenha outro nome, o código tenta detectar automaticamente.
        $senhaHash = password_hash($senha, PASSWORD_DEFAULT);
        $colunaSenha = null;
        foreach (['senha', 'password', 'senha_usuario'] as $possivelColuna) {
            if (colunaExiste($conexao, 'usuario', $possivelColuna)) {
                $colunaSenha = $possivelColuna;
                break;
            }
        }

        // CORREÇÃO: substituído 'propriedade_id' (coluna inexistente) por 'nome_propriedade'
        // A coluna `senha` é garantida pelo schema, dispensando a detecção dinâmica de coluna
        $nome_prop_add = trim($_POST['nome_propriedade'] ?? '') ?: null;
        $sqlInsert = "INSERT INTO usuario (username, email, senha, tipo, nome_propriedade, num_telefone, CPF, CNPJ, suspenso) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 0)";
        $stmtInsert = mysqli_prepare($conexao, $sqlInsert);
        mysqli_stmt_bind_param($stmtInsert, "ssssssss",
            $username, $email, $senhaHash, $tipo,
            $nome_prop_add, $num_telefone, $CPF, $CNPJ
        );

        if ($stmtInsert && mysqli_stmt_execute($stmtInsert)) {
            mysqli_stmt_close($stmtInsert);
            redirecionarAdmin('adicionado');
        }

        if ($stmtInsert) mysqli_stmt_close($stmtInsert);
        redirecionarAdmin('erro');
    }

    if ($acao === 'editar_usuario') {
        $user_id = intval($_POST['user_id'] ?? 0);

        if ($user_id <= 0 || $username === '' || $email === '') {
            redirecionarAdmin('erro');
        }

        // CORREÇÃO: substituído 'propriedade_id' (coluna inexistente) por 'nome_propriedade'
        $nome_propriedade_edit = trim($_POST['nome_propriedade'] ?? '');
        $sqlUpdate = "UPDATE usuario SET username = ?, email = ?, tipo = ?, nome_propriedade = ?, num_telefone = ?, CPF = ?, CNPJ = ? WHERE user_id = ?";
        $stmtUpdate = mysqli_prepare($conexao, $sqlUpdate);
        mysqli_stmt_bind_param($stmtUpdate, "sssssssi", $username, $email, $tipo, $nome_propriedade_edit, $num_telefone, $CPF, $CNPJ, $user_id);

        if ($stmtUpdate && mysqli_stmt_execute($stmtUpdate)) {
            mysqli_stmt_close($stmtUpdate);
            redirecionarAdmin('editado');
        }

        if ($stmtUpdate) mysqli_stmt_close($stmtUpdate);
        redirecionarAdmin('erro');
    }
}

$nomeUsuario = htmlspecialchars($_SESSION['usuario_nome']);
$emailUsuario = htmlspecialchars($_SESSION['usuario_email']);
$fazenda = htmlspecialchars($_SESSION['usuario_fazenda'] ?? 'Minha Fazenda');
$partes = explode(' ', $nomeUsuario);
$iniciais = strtoupper(substr($partes[0], 0, 1));
if (count($partes) > 1) {
    $iniciais .= strtoupper(substr(end($partes), 0, 1));
}

// Fetch users
$sql = "SELECT user_id, username, email, tipo, nome_propriedade, num_telefone, CPF, CNPJ, create_time, suspenso FROM usuario ORDER BY user_id DESC";
$resultado = mysqli_query($conexao, $sql);

// Fetch avisos
$sqlAvisos = "SELECT a.id, a.mensagem, a.data_criacao, u.username as destinatario, l.nome as lote_nome
              FROM avisos a
              LEFT JOIN usuario u ON a.destinatario_id = u.user_id
              LEFT JOIN lote l ON a.lote_id = l.id
              ORDER BY a.id DESC";
$resultadoAvisos = mysqli_query($conexao, $sqlAvisos);

// Fetch lotes para os avisos
$sqlLotes = "SELECT id, nome, user_id FROM lote ORDER BY nome ASC";
$resultadoLotes = mysqli_query($conexao, $sqlLotes);
$lotes_options = [];
while($lote = mysqli_fetch_assoc($resultadoLotes)) {
    $lotes_options[] = $lote;
}

// Fetch users list for avisos
$sqlUsersList = "SELECT user_id, username FROM usuario ORDER BY username ASC";
$resultadoUsersList = mysqli_query($conexao, $sqlUsersList);
$users_options = [];
while($u = mysqli_fetch_assoc($resultadoUsersList)) {
    $users_options[] = $u;
}

// Fetch propriedades (tabela agora declarada corretamente no schema sql.sql)
$sqlPropriedades = "SELECT p.id, p.nome, u.username as produtor_nome FROM propriedades p JOIN usuario u ON p.produtor_id = u.user_id ORDER BY p.nome ASC";
$resultadoPropriedades = mysqli_query($conexao, $sqlPropriedades);
$propriedades_options = [];
if ($resultadoPropriedades) {
    while($p = mysqli_fetch_assoc($resultadoPropriedades)) {
        $propriedades_options[] = $p;
    }
}

// Fetch stats for charts
$query_users_type = "SELECT tipo, COUNT(*) as qtd FROM usuario GROUP BY tipo";
$res_users_type = mysqli_query($conexao, $query_users_type);
$users_types = ['produtor' => 0, 'empregado rural' => 0];
while ($row = mysqli_fetch_assoc($res_users_type)) {
    $t = $row['tipo'] ? strtolower($row['tipo']) : 'visitante';
    if ($t === 'visitante') {
        $users_types['empregado rural'] = ($users_types['empregado rural'] ?? 0) + $row['qtd'];
    } else {
        $users_types[$t] = ($users_types[$t] ?? 0) + $row['qtd'];
    }
}

$query_lots = "SELECT tipo, COUNT(*) as qtd FROM lote GROUP BY tipo";
$res_lots = mysqli_query($conexao, $query_lots);
$lots_types = [];
while ($row = mysqli_fetch_assoc($res_lots)) {
    $lots_types[$row['tipo'] ?: 'Outro'] = $row['qtd'];
}

$query_animals = "SELECT especie, COUNT(*) as qtd FROM animal GROUP BY especie";
$res_animals = mysqli_query($conexao, $query_animals);
$animals_types = [];
while ($row = mysqli_fetch_assoc($res_animals)) {
    $animals_types[$row['especie'] ?: 'Outro'] = $row['qtd'];
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ControlCabra - Administração</title>
    <link rel="stylesheet" href="estatisticas.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        .charts-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }
        .chart-card {
            background-color: var(--bg-main);
            border: 1px solid var(--border-color);
            border-radius: 8px;
            padding: 15px;
            display: flex;
            flex-direction: column;
            align-items: center;
        }
        .chart-card h4 {
            margin-bottom: 15px;
            color: var(--text-dark);
            font-size: 1rem;
        }
    </style>
</head>
<body>

    <header class="mobile-header">
        <div class="mobile-header-left">
            <img src="logoControlCabra.png" alt="Logo ControlCabra" class="mobile-logo">
        </div>

        <div class="mobile-header-center">
            <span class="mobile-page-title">Administração</span>
        </div>

        <div class="mobile-header-right">
            <button type="button" class="menu-toggle" id="menuToggle" aria-label="Abrir menu" aria-expanded="false" aria-controls="sidebar">
                <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <line x1="3" y1="6" x2="21" y2="6"></line>
                    <line x1="3" y1="12" x2="21" y2="12"></line>
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
                <a href="configuracoes.php" class="nav-item">Configurações</a>
                <a href="administracao.php" class="nav-item active">Administração</a>
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
                            <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                            <circle cx="9" cy="7" r="4"></circle>
                            <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
                            <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
                        </svg>
                        Administração
                    </h1>
                    <p class="page-subtitle">Gerenciamento de Usuários do Sistema</p>
                </div>
            </header>

            <?php if (isset($_GET['msg'])): ?>
                <?php if ($_GET['msg'] == 'erro'): ?>
                    <div class="alert alert-danger mt-3" style="background-color: #ffebee; color: #c62828; padding: 12px; border-radius: 6px; margin-bottom: 24px;">
                        Erro ao realizar a operação. Tente novamente ou verifique os dados.
                    </div>
                <?php else: ?>
                    <div class="alert alert-success mt-3" style="background-color: #d4edda; color: #155724; padding: 12px; border-radius: 6px; margin-bottom: 24px;">
                        <?php
                        if ($_GET['msg'] == 'deletado') echo 'Usuário removido com sucesso!';
                        elseif ($_GET['msg'] == 'editado') echo 'Dados do usuário alterados com sucesso!';
                        elseif ($_GET['msg'] == 'adicionado') echo 'Usuário adicionado com sucesso!';
                        elseif ($_GET['msg'] == 'status_alterado') echo 'Status do usuário atualizado com sucesso!';
                        elseif ($_GET['msg'] == 'aviso_adicionado') echo 'Aviso enviado com sucesso!';
                        elseif ($_GET['msg'] == 'aviso_removido') echo 'Aviso removido com sucesso!';
                        ?>
                    </div>
                <?php endif; ?>
            <?php endif; ?>

            <section class="card mt-4">
                <div class="card-header admin-tools">
                    <div class="admin-tools-left">
                        <h3 class="card-title" style="margin: 0;">Usuários Cadastrados</h3>
                    </div>

                    <div class="admin-tools-right">
                        <div class="search-wrapper">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <circle cx="11" cy="11" r="8"></circle>
                                <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                            </svg>
                            <input type="text" id="searchInput" placeholder="Pesquise por ID, nome, email ou tipo">
                        </div>

                        <select id="filterSelect" class="filter-select">
                            <option value="id_desc">ID decrescente</option>
                            <option value="id_asc">ID crescente</option>
                            <option value="produtor">Somente produtores</option>
                            <option value="visitante">Somente Empregados Rurais</option>
                            <option value="ativo">Somente ativos</option>
                            <option value="suspenso">Somente suspensos</option>
                            <option value="todos">Mostrar todos</option>
                        </select>

                        <button type="button" class="btn-add" onclick="abrirModalAdicionar()">
                            <span>+</span> Adicionar Usuário
                        </button>
                    </div>
                </div>

                <div class="table-container" style="width: 100%; overflow-x: auto;">
                    <table class="data-table" style="width: 100%;">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Nome de Usuário</th>
                                <th>E-mail</th>
                                <th>Tipo de Conta</th>
                                <th>Cadastro</th>
                                <th>Status</th>
                                <th>Ações</th>
                            </tr>
                        </thead>
                        <tbody id="usersTableBody">
                            <?php
                            if (mysqli_num_rows($resultado) > 0) {
                                while ($usuario = mysqli_fetch_assoc($resultado)) {
                                    $is_suspenso = $usuario['suspenso'] ? true : false;
                                    $tipoConta = $usuario['tipo'] ?: 'não informado';
                                    $tipoClasse = ($tipoConta === 'visitante') ? 'type-visitante' : 'type-produtor';
                                    $tipoLabel = ($tipoConta === 'visitante') ? 'Empregado Rural' : 'Produtor';

                                    $idAttr = valorSeguro($usuario['user_id']);
                                    $usernameAttr = valorSeguro($usuario['username']);
                                    $emailAttr = valorSeguro($usuario['email']);
                                    $tipoAttr = valorSeguro($usuario['tipo']);
                                    $propriedadeAttr = valorSeguro($usuario['nome_propriedade']); // CORREÇÃO: era propriedade_id
                                    $telefoneAttr = valorSeguro($usuario['num_telefone']);
                                    $cpfAttr = valorSeguro($usuario['CPF']);
                                    $cnpjAttr = valorSeguro($usuario['CNPJ']);

                                    echo "<tr data-id='{$idAttr}' data-tipo='{$tipoAttr}' data-status='" . ($is_suspenso ? 'suspenso' : 'ativo') . "'>";
                                    echo "<td><strong>" . valorSeguro($usuario['user_id']) . "</strong></td>";
                                    echo "<td>" . valorSeguro($usuario['username']) . "</td>";
                                    echo "<td>" . valorSeguro($usuario['email']) . "</td>";
                                    echo "<td><span class='type-badge {$tipoClasse}'>" . valorSeguro($tipoLabel) . "</span></td>";
                                    echo "<td>" . date('d/m/Y H:i', strtotime($usuario['create_time'])) . "</td>";

                                    echo "<td>";
                                    if ($is_suspenso) {
                                        echo "<span class='status-badge status-suspended'>Suspenso</span>";
                                    } else {
                                        echo "<span class='status-badge status-active'>Ativo</span>";
                                    }
                                    echo "</td>";

                                    echo "<td>
                                            <button class='btn-sm btn-edit'
                                                data-id='{$idAttr}'
                                                data-username='{$usernameAttr}'
                                                data-email='{$emailAttr}'
                                                data-tipo='{$tipoAttr}'
                                                data-propriedade-id='{$usuario['propriedade_id']}' data-propriedade='{$propriedadeAttr}'
                                                data-telefone='{$telefoneAttr}'
                                                data-cpf='{$cpfAttr}'
                                                data-cnpj='{$cnpjAttr}'
                                                onclick='abrirModalEdicao(this)'>Editar
                                            </button>

                                            <a href='toggleSuspendUser.php?id=" . valorSeguro($usuario['user_id']) . "' class='btn-sm btn-warning'>
                                                " . ($is_suspenso ? 'Ativar' : 'Suspender') . "
                                            </a>

                                            <a href='deleteUser.php?id=" . valorSeguro($usuario['user_id']) . "' class='btn-sm btn-danger' onclick='return confirm(\"Tem certeza que deseja deletar este usuário?\")'>Excluir</a>
                                          </td>";
                                    echo "</tr>";
                                }
                            } else {
                                echo "<tr class='empty-row'><td colspan='7' style='text-align:center; padding: 20px;'>Nenhum usuário cadastrado.</td></tr>";
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
            </section>

            <section class="card mt-4">
                <div class="card-header admin-tools">
                    <div class="admin-tools-left">
                        <h3 class="card-title" style="margin: 0;">Estatísticas Gerais</h3>
                    </div>
                </div>
                <div class="charts-container">
                    <div class="chart-card">
                        <h4>Usuários por Tipo</h4>
                        <?php if (array_sum($users_types) > 0): ?>
                            <canvas id="usersChart"></canvas>
                        <?php else: ?>
                            <p style="text-align: center; color: var(--muted); padding: 20px;">Nenhum usuário registrado ainda.</p>
                        <?php endif; ?>
                    </div>
                    <div class="chart-card">
                        <h4>Lotes por Tipo</h4>
                        <?php if (count($lots_types) > 0): ?>
                            <canvas id="lotsChart"></canvas>
                        <?php else: ?>
                            <p style="text-align: center; color: var(--muted); padding: 20px;">Nenhum lote registrado ainda.</p>
                        <?php endif; ?>
                    </div>
                    <div class="chart-card">
                        <h4>Animais por Espécie</h4>
                        <?php if (count($animals_types) > 0): ?>
                            <canvas id="animalsChart"></canvas>
                        <?php else: ?>
                            <p style="text-align: center; color: var(--muted); padding: 20px;">Nenhum animal registrado ainda.</p>
                        <?php endif; ?>
                    </div>
                </div>
            </section>

            <section class="card mt-4">
                <div class="card-header admin-tools">
                    <div class="admin-tools-left">
                        <h3 class="card-title" style="margin: 0;">Avisos do Sistema</h3>
                    </div>
                    <div class="admin-tools-right">
                        <button type="button" class="btn-add" onclick="abrirModalAviso()">
                            <span>+</span> Novo Aviso
                        </button>
                    </div>
                </div>

                <div class="table-container" style="width: 100%; overflow-x: auto;">
                    <table class="data-table" style="width: 100%;">
                        <thead>
                            <tr>
                                <th>Data</th>
                                <th>Destinatário</th>
                                <th>Lote</th>
                                <th>Mensagem</th>
                                <th>Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            if ($resultadoAvisos && mysqli_num_rows($resultadoAvisos) > 0) {
                                while ($aviso = mysqli_fetch_assoc($resultadoAvisos)) {
                                    $dest = $aviso['destinatario'] ? valorSeguro($aviso['destinatario']) : 'Todos os Usuários';
                                    $lote = $aviso['lote_nome'] ? valorSeguro($aviso['lote_nome']) : 'Todos/Geral';
                                    echo "<tr>";
                                    echo "<td>" . date('d/m/Y H:i', strtotime($aviso['data_criacao'])) . "</td>";
                                    echo "<td><span class='type-badge type-produtor'>{$dest}</span></td>";
                                    echo "<td>{$lote}</td>";
                                    echo "<td>" . valorSeguro($aviso['mensagem']) . "</td>";
                                    echo "<td>
                                            <form method='POST' action='administracao.php' style='display:inline;'>
                                                <input type='hidden' name='form_action' value='deletar_aviso'>
                                                <input type='hidden' name='aviso_id' value='{$aviso['id']}'>
                                                <button type='submit' class='btn-sm btn-danger' onclick='return confirm(\"Excluir este aviso?\")'>Excluir</button>
                                            </form>
                                          </td>";
                                    echo "</tr>";
                                }
                            } else {
                                echo "<tr class='empty-row'><td colspan='5' style='text-align:center; padding: 20px;'>Nenhum aviso cadastrado.</td></tr>";
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
            </section>

            
        </main>
    </div>

    <!-- Modal de Adição -->
    <div id="addModal" class="modal">
        <div class="modal-content">
            <span class="close-modal" onclick="fecharModal('addModal')">&times;</span>
            <h2 style="margin-top: 0; margin-bottom: 20px; color: var(--primary);">Adicionar Usuário</h2>
            <form method="POST" action="administracao.php">
                <input type="hidden" name="form_action" value="adicionar_usuario">

                <div class="form-row">
                    <div class="form-group">
                        <label>Nome de Usuário</label>
                        <input type="text" name="username" required>
                    </div>
                    <div class="form-group">
                        <label>E-mail</label>
                        <input type="email" name="email" required>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label>Senha</label>
                        <input type="password" name="senha" required>
                    </div>
                    <div class="form-group">
                        <label>Tipo de Conta</label>
                        <select name="tipo" required>
                            <option value="produtor">Produtor</option>
                            <option value="visitante">Empregado Rural</option>
                        </select>
                    </div>
                </div>

                <div class="form-group">
                    <label>Vincular Propriedade</label>
                    <select name="propriedade_id" id="add_propriedade">
                        <option value="">Nenhuma / Criação Própria</option>
                        <?php foreach($propriedades_options as $p): ?>
                            <option value="<?= $p['id'] ?>"><?= valorSeguro($p['nome']) ?> (Produtor: <?= valorSeguro($p['produtor_nome']) ?>)</option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label>Telefone</label>
                    <input type="text" id="add_telefone" name="num_telefone" inputmode="numeric" autocomplete="off" maxlength="15" placeholder="Digite apenas números">
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label>CPF</label>
                        <input type="text" id="add_cpf" name="CPF" inputmode="numeric" autocomplete="off" maxlength="14" placeholder="000.000.000-00">
                    </div>
                    <div class="form-group">
                        <label>CNPJ</label>
                        <input type="text" id="add_cnpj" name="CNPJ" inputmode="numeric" autocomplete="off" maxlength="18" placeholder="00.000.000/0000-00">
                    </div>
                </div>

                <div style="margin-top: 20px; text-align: right;">
                    <button type="button" class="btn-sm btn-danger" onclick="fecharModal('addModal')" style="margin-right: 10px;">Cancelar</button>
                    <button type="submit" class="btn-sm btn-edit">Cadastrar Usuário</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal de Edição -->
    <div id="editModal" class="modal">
        <div class="modal-content">
            <span class="close-modal" onclick="fecharModal('editModal')">&times;</span>
            <h2 style="margin-top: 0; margin-bottom: 20px; color: var(--primary);">Detalhes e Edição do Usuário</h2>
            <form method="POST" action="administracao.php">
                <input type="hidden" name="form_action" value="editar_usuario">
                <input type="hidden" id="modal_user_id" name="user_id">

                <div class="form-row">
                    <div class="form-group">
                        <label>Nome de Usuário</label>
                        <input type="text" id="modal_username" name="username" required>
                    </div>
                    <div class="form-group">
                        <label>E-mail</label>
                        <input type="email" id="modal_email" name="email" required>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label>Tipo de Conta</label>
                        <select id="modal_tipo" name="tipo" required>
                            <option value="produtor">Produtor</option>
                            <option value="visitante">Empregado Rural</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Nome da Propriedade</label>
                        <input type="text" id="modal_propriedade" name="nome_propriedade">
                    </div>
                </div>

                <div class="form-group">
                    <label>Telefone</label>
                    <input type="text" id="modal_telefone" name="num_telefone" inputmode="numeric" autocomplete="off" maxlength="15" placeholder="Digite apenas números">
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label>CPF</label>
                        <input type="text" id="modal_cpf" name="CPF" inputmode="numeric" autocomplete="off" maxlength="14" placeholder="000.000.000-00">
                    </div>
                    <div class="form-group">
                        <label>CNPJ</label>
                        <input type="text" id="modal_cnpj" name="CNPJ" inputmode="numeric" autocomplete="off" maxlength="18" placeholder="00.000.000/0000-00">
                    </div>
                </div>

                <div style="margin-top: 20px; text-align: right;">
                    <button type="button" class="btn-sm btn-danger" onclick="fecharModal('editModal')" style="margin-right: 10px;">Cancelar</button>
                    <button type="submit" class="btn-sm btn-edit">Salvar Alterações</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal de Aviso -->
    <div id="avisoModal" class="modal">
        <div class="modal-content">
            <span class="close-modal" onclick="fecharModal('avisoModal')">&times;</span>
            <h2 style="margin-top: 0; margin-bottom: 20px; color: var(--primary);">Enviar Novo Aviso</h2>
            <form method="POST" action="administracao.php">
                <input type="hidden" name="form_action" value="adicionar_aviso">

                <div class="form-row">
                    <div class="form-group">
                        <label>Destinatário</label>
                        <select name="destinatario" required>
                            <option value="todos">Todos os Usuários</option>
                            <?php foreach($users_options as $u): ?>
                                <option value="<?= $u['user_id'] ?>"><?= valorSeguro($u['username']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Lote Específico (Opcional)</label>
                        <select name="lote_aviso">
                            <option value="todos">Todos/Geral</option>
                            <?php foreach($lotes_options as $l): ?>
                                <option value="<?= $l['id'] ?>"><?= valorSeguro($l['nome']) ?> (User ID: <?= $l['user_id'] ?>)</option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="form-group">
                    <label>Mensagem</label>
                    <textarea name="mensagem" style="width: 100%; padding: 10px; border-radius: 6px; border: 1px solid var(--border-color); background-color: var(--bg-main); color: var(--text-dark); resize: vertical;" rows="4" required placeholder="Escreva o aviso aqui..."></textarea>
                </div>

                <div style="margin-top: 20px; text-align: right;">
                    <button type="button" class="btn-sm btn-danger" onclick="fecharModal('avisoModal')" style="margin-right: 10px;">Cancelar</button>
                    <button type="submit" class="btn-sm btn-edit">Enviar Aviso</button>
                </div>
            </form>
        </div>
    </div>

    <script src="estatisticas.js"></script>
    <script>
        const editModal = document.getElementById('editModal');
        const addModal = document.getElementById('addModal');
        const avisoModal = document.getElementById('avisoModal');
        const searchInput = document.getElementById('searchInput');
        const filterSelect = document.getElementById('filterSelect');
        const usersTableBody = document.getElementById('usersTableBody');

        function apenasNumeros(valor, limite) {
            return String(valor || '').replace(/\D/g, '').slice(0, limite);
        }

        function formatarCPF(valor) {
            let numeros = apenasNumeros(valor, 11);
            numeros = numeros.replace(/(\d{3})(\d)/, '$1.$2');
            numeros = numeros.replace(/(\d{3})(\d)/, '$1.$2');
            numeros = numeros.replace(/(\d{3})(\d{1,2})$/, '$1-$2');
            return numeros;
        }

        function formatarCNPJ(valor) {
            let numeros = apenasNumeros(valor, 14);
            numeros = numeros.replace(/^(\d{2})(\d)/, '$1.$2');
            numeros = numeros.replace(/^(\d{2})\.(\d{3})(\d)/, '$1.$2.$3');
            numeros = numeros.replace(/\.(\d{3})(\d)/, '.$1/$2');
            numeros = numeros.replace(/(\d{4})(\d)/, '$1-$2');
            return numeros;
        }

        function formatarTelefone(valor) {
            return apenasNumeros(valor, 15);
        }

        function aplicarMascara(input, tipo) {
            if (!input) return;

            input.addEventListener('input', function() {
                if (tipo === 'cpf') this.value = formatarCPF(this.value);
                if (tipo === 'cnpj') this.value = formatarCNPJ(this.value);
                if (tipo === 'telefone') this.value = formatarTelefone(this.value);
            });

            input.addEventListener('paste', function() {
                setTimeout(() => {
                    if (tipo === 'cpf') this.value = formatarCPF(this.value);
                    if (tipo === 'cnpj') this.value = formatarCNPJ(this.value);
                    if (tipo === 'telefone') this.value = formatarTelefone(this.value);
                }, 0);
            });
        }

        aplicarMascara(document.getElementById('modal_telefone'), 'telefone');
        aplicarMascara(document.getElementById('modal_cpf'), 'cpf');
        aplicarMascara(document.getElementById('modal_cnpj'), 'cnpj');
        aplicarMascara(document.getElementById('add_telefone'), 'telefone');
        aplicarMascara(document.getElementById('add_cpf'), 'cpf');
        aplicarMascara(document.getElementById('add_cnpj'), 'cnpj');

        function abrirModalAdicionar() {
            addModal.style.display = 'block';
        }

        function abrirModalAviso() {
            avisoModal.style.display = 'block';
        }

        function abrirModalEdicao(btn) {
            document.getElementById('modal_user_id').value = btn.getAttribute('data-id') || '';
            document.getElementById('modal_username').value = btn.getAttribute('data-username') || '';
            document.getElementById('modal_email').value = btn.getAttribute('data-email') || '';

            const tipo = btn.getAttribute('data-tipo') || 'produtor';
            document.getElementById('modal_tipo').value = tipo;

            document.getElementById('modal_propriedade').value = btn.getAttribute('data-propriedade-id') || '';
            document.getElementById('modal_telefone').value = formatarTelefone(btn.getAttribute('data-telefone') || '');
            document.getElementById('modal_cpf').value = formatarCPF(btn.getAttribute('data-cpf') || '');
            document.getElementById('modal_cnpj').value = formatarCNPJ(btn.getAttribute('data-cnpj') || '');

            editModal.style.display = 'block';
        }

        function fecharModal(modalId) {
            document.getElementById(modalId).style.display = 'none';
        }

        window.addEventListener('click', function(event) {
            if (event.target === editModal) fecharModal('editModal');
            if (event.target === addModal) fecharModal('addModal');
            if (event.target === avisoModal) fecharModal('avisoModal');
        });

        // Gráficos
        const usersData = <?= json_encode(array_values($users_types)) ?>;
        const usersLabels = <?= json_encode(array_keys($users_types)) ?>;
        
        const lotsData = <?= json_encode(array_values($lots_types)) ?>;
        const lotsLabels = <?= json_encode(array_keys($lots_types)) ?>;

        const animalsData = <?= json_encode(array_values($animals_types)) ?>;
        const animalsLabels = <?= json_encode(array_keys($animals_types)) ?>;

        const chartOptions = {
            responsive: true,
            plugins: {
                legend: { position: 'bottom', labels: { color: getComputedStyle(document.documentElement).getPropertyValue('--text-dark').trim() || '#333' } }
            }
        };

        if (usersLabels.length > 0) {
            new Chart(document.getElementById('usersChart'), {
                type: 'pie',
                data: {
                    labels: usersLabels.map(l => {
                        let words = String(l).split(' ');
                        return words.map(w => w.charAt(0).toUpperCase() + w.slice(1)).join(' ');
                    }),
                    datasets: [{
                        data: usersData,
                        backgroundColor: ['#4caf50', '#2196f3', '#ffc107', '#f44336']
                    }]
                },
                options: chartOptions
            });
        }

        if (lotsLabels.length > 0) {
            new Chart(document.getElementById('lotsChart'), {
                type: 'doughnut',
                data: {
                    labels: lotsLabels,
                    datasets: [{
                        data: lotsData,
                        backgroundColor: ['#9c27b0', '#ff9800', '#03a9f4', '#8bc34a']
                    }]
                },
                options: chartOptions
            });
        }

        if (animalsLabels.length > 0) {
            new Chart(document.getElementById('animalsChart'), {
                type: 'bar',
                data: {
                    labels: animalsLabels,
                    datasets: [{
                        label: 'Quantidade',
                        data: animalsData,
                        backgroundColor: '#ff5722'
                    }]
                },
                options: {
                    responsive: true,
                    plugins: { legend: { display: false } },
                    scales: { 
                        y: { beginAtZero: true, ticks: { color: getComputedStyle(document.documentElement).getPropertyValue('--text-dark').trim() || '#333' } },
                        x: { ticks: { color: getComputedStyle(document.documentElement).getPropertyValue('--text-dark').trim() || '#333' } }
                    }
                }
            });
        }

        function aplicarPesquisaEFiltro() {
            const termo = searchInput.value.toLowerCase().trim();
            const filtro = filterSelect.value;
            const rows = Array.from(usersTableBody.querySelectorAll('tr')).filter(row => !row.classList.contains('empty-row'));

            rows.forEach(row => {
                const textoLinha = row.textContent.toLowerCase();
                const tipo = (row.dataset.tipo || '').toLowerCase();
                const status = (row.dataset.status || '').toLowerCase();

                let mostrar = textoLinha.includes(termo);

                if (filtro === 'produtor') mostrar = mostrar && tipo === 'produtor';
                if (filtro === 'visitante') mostrar = mostrar && tipo === 'visitante';
                if (filtro === 'ativo') mostrar = mostrar && status === 'ativo';
                if (filtro === 'suspenso') mostrar = mostrar && status === 'suspenso';

                row.style.display = mostrar ? '' : 'none';
            });

            if (filtro === 'id_asc' || filtro === 'id_desc') {
                const linhasOrdenadas = rows.sort((a, b) => {
                    const idA = parseInt(a.dataset.id || '0', 10);
                    const idB = parseInt(b.dataset.id || '0', 10);
                    return filtro === 'id_asc' ? idA - idB : idB - idA;
                });

                linhasOrdenadas.forEach(row => usersTableBody.appendChild(row));
            }
        }

        searchInput.addEventListener('keyup', aplicarPesquisaEFiltro);
        filterSelect.addEventListener('change', aplicarPesquisaEFiltro);
        aplicarPesquisaEFiltro();
    </script>

    <script>
        // Menu responsivo para celular
        (function() {
            const menuToggle = document.getElementById('menuToggle');
            const sidebar = document.getElementById('sidebar');
            const sidebarOverlay = document.getElementById('sidebarOverlay');

            function fecharMenuMobile() {
                if (!sidebar || !sidebarOverlay || !menuToggle) return;
                sidebar.classList.remove('active');
                sidebarOverlay.classList.remove('active');
                document.body.classList.remove('menu-open');
                menuToggle.setAttribute('aria-expanded', 'false');
            }

            function alternarMenuMobile() {
                if (!sidebar || !sidebarOverlay || !menuToggle) return;
                const abriu = sidebar.classList.toggle('active');
                sidebarOverlay.classList.toggle('active', abriu);
                document.body.classList.toggle('menu-open', abriu);
                menuToggle.setAttribute('aria-expanded', abriu ? 'true' : 'false');
            }

            if (menuToggle) {
                menuToggle.addEventListener('click', alternarMenuMobile);
            }

            if (sidebarOverlay) {
                sidebarOverlay.addEventListener('click', fecharMenuMobile);
            }

            document.querySelectorAll('.sidebar-nav a').forEach(link => {
                link.addEventListener('click', fecharMenuMobile);
            });

            window.addEventListener('resize', function() {
                if (window.innerWidth > 768) {
                    fecharMenuMobile();
                }
            });
        })();
    </script>
</body>
</html>
<?php
mysqli_close($conexao);
?>
