<?php
session_start();
if (!isset($_SESSION['usuario_id'])) {
    header('Location: recuperarConta.html');
    exit();
}

include '../db/connection.php';

// Verificação de segurança para a coluna suspenso
$checkColumn = mysqli_query($conexao, "SHOW COLUMNS FROM usuario LIKE 'suspenso'");
if (mysqli_num_rows($checkColumn) == 0) {
    mysqli_query($conexao, "ALTER TABLE usuario ADD COLUMN suspenso TINYINT(1) NOT NULL DEFAULT 0");
}

$user_session_id = $_SESSION['usuario_id'];

// Verificar se o usuário logado é 'visitante'. Se sim, bloqueia o acesso.
$stmt_check = mysqli_prepare($conexao, "SELECT tipo FROM usuario WHERE user_id = ?");
mysqli_stmt_bind_param($stmt_check, "i", $user_session_id);
mysqli_stmt_execute($stmt_check);
$res_check = mysqli_stmt_get_result($stmt_check);
$user_logged_in = mysqli_fetch_assoc($res_check);
mysqli_stmt_close($stmt_check);

if ($user_logged_in && $user_logged_in['tipo'] === 'visitante') {
    // Visitante não pode acessar administração
    header('Location: estatisticas.php');
    exit();
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
    <style>
        /* Estilos da tabela aumentados e mais compactos */
        table.data-table td, table.data-table th {
            padding: 8px 12px;
            font-size: 1.05rem; /* Texto maior */
        }
        
        .btn-sm {
            padding: 6px 12px;
            font-size: 0.9rem;
            margin-right: 8px; /* Botões levemente afastados */
            border-radius: 6px;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            transition: background 0.2s;
        }
        
        /* Botões preenchidos com cor - mais escuros */
        .btn-edit {
            background-color: #0d47a1;
            color: #ffffff;
            border: none;
        }
        .btn-edit:hover {
            background-color: #08285e;
        }
        
        .btn-warning {
            background-color: #e65100;
            color: #ffffff;
            border: none;
        }
        .btn-warning:hover {
            background-color: #bf360c;
        }
        
        .btn-danger {
            background-color: #b71c1c;
            color: #ffffff;
            border: none;
        }
        .btn-danger:hover {
            background-color: #7f0000;
        }
        
        /* Status com fundo mais escuro e texto claro */
        .status-badge {
            padding: 4px 10px;
            border-radius: 12px;
            font-size: 0.9rem;
            font-weight: 600;
        }
        .status-active {
            background-color: var(--primary); /* Usa o verde escuro do tema */
            color: #ffffff;
        }
        .status-suspended {
            background-color: #7f0000; /* Vermelho bem escuro */
            color: #ffffff;
        }

        /* Estilos do Modal (Popup) adaptados ao tema */
        .modal {
            display: none; 
            position: fixed; 
            z-index: 1000; 
            left: 0;
            top: 0;
            width: 100%; 
            height: 100%; 
            overflow: auto; 
            background-color: rgba(0,0,0,0.6); 
        }
        .modal-content {
            background-color: var(--card-bg);
            color: var(--text-dark);
            margin: 5% auto; 
            padding: 30px;
            border: 1px solid var(--border-color);
            width: 90%;
            max-width: 600px;
            border-radius: 12px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.5);
        }
        .close-modal {
            color: var(--text-muted);
            float: right;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
        }
        .close-modal:hover, .close-modal:focus {
            color: var(--text-dark);
            text-decoration: none;
        }
        .form-group {
            margin-bottom: 15px;
        }
        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: 600;
            color: var(--text-dark);
        }
        .form-group input, .form-group select {
            width: 100%;
            padding: 8px;
            border: 1px solid var(--border-color);
            background-color: var(--bg-main);
            color: var(--text-dark);
            border-radius: 6px;
            font-size: 1rem;
        }
        .form-row {
            display: flex;
            gap: 15px;
        }
        .form-row .form-group {
            flex: 1;
        }
    </style>
</head>
<body>

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
                        elseif ($_GET['msg'] == 'status_alterado') echo 'Status do usuário atualizado com sucesso!';
                        ?>
                    </div>
                <?php endif; ?>
            <?php endif; ?>

            <section class="card mt-4">
                <div class="card-header">
                    <h3 class="card-title">Usuários Cadastrados</h3>
                </div>
                
                <div class="table-container" style="width: 100%; overflow-x: auto;">
                    <table class="data-table" style="width: 100%;">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Nome de Usuário</th>
                                <th>E-mail</th>
                                <th>Cadastro</th>
                                <th>Status</th>
                                <th>Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            if (mysqli_num_rows($resultado) > 0) {
                                while ($usuario = mysqli_fetch_assoc($resultado)) {
                                    $is_suspenso = $usuario['suspenso'] ? true : false;
                                    echo "<tr>";
                                    echo "<td><strong>" . $usuario['user_id'] . "</strong></td>";
                                    echo "<td>" . htmlspecialchars($usuario['username']) . "</td>";
                                    echo "<td>" . htmlspecialchars($usuario['email']) . "</td>";
                                    echo "<td>" . date('d/m/Y H:i', strtotime($usuario['create_time'])) . "</td>";
                                    
                                    echo "<td>";
                                    if ($is_suspenso) {
                                        echo "<span class='status-badge status-suspended'>Suspenso</span>";
                                    } else {
                                        echo "<span class='status-badge status-active'>Ativo</span>";
                                    }
                                    echo "</td>";

                                    // Passando todos os dados para o JS via atributos data-*
                                    echo "<td>
                                            <button class='btn-sm btn-edit' 
                                                data-id='" . $usuario['user_id'] . "'
                                                data-username='" . htmlspecialchars($usuario['username']) . "'
                                                data-email='" . htmlspecialchars($usuario['email']) . "'
                                                data-tipo='" . htmlspecialchars($usuario['tipo'] ?? '') . "'
                                                data-propriedade='" . htmlspecialchars($usuario['nome_propriedade'] ?? '') . "'
                                                data-telefone='" . htmlspecialchars($usuario['num_telefone'] ?? '') . "'
                                                data-cpf='" . htmlspecialchars($usuario['CPF'] ?? '') . "'
                                                data-cnpj='" . htmlspecialchars($usuario['CNPJ'] ?? '') . "'
                                                onclick='abrirModal(this)'>Editar
                                            </button>
                                            
                                            <a href='toggleSuspendUser.php?id=" . $usuario['user_id'] . "' class='btn-sm btn-warning'>
                                                " . ($is_suspenso ? 'Ativar' : 'Suspender') . "
                                            </a>
                                            
                                            <a href='deleteUser.php?id=" . $usuario['user_id'] . "' class='btn-sm btn-danger' onclick='return confirm(\"Tem certeza que deseja deletar este usuário?\")'>Excluir</a>
                                          </td>";
                                    echo "</tr>";
                                }
                            } else {
                                echo "<tr><td colspan='6' style='text-align:center; padding: 20px;'>Nenhum usuário cadastrado.</td></tr>";
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
            </section>
        </main>
    </div>

    <!-- Modal de Edição -->
    <div id="editModal" class="modal">
        <div class="modal-content">
            <span class="close-modal" onclick="fecharModal()">&times;</span>
            <h2 style="margin-top: 0; margin-bottom: 20px; color: var(--primary);">Detalhes e Edição do Usuário</h2>
            <form method="POST" action="editUser.php">
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
                            <option value="visitante">Visitante</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Nome da Propriedade</label>
                        <input type="text" id="modal_propriedade" name="nome_propriedade">
                    </div>
                </div>

                <div class="form-group">
                    <label>Telefone</label>
                    <input type="text" id="modal_telefone" name="num_telefone">
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label>CPF</label>
                        <input type="text" id="modal_cpf" name="CPF">
                    </div>
                    <div class="form-group">
                        <label>CNPJ</label>
                        <input type="text" id="modal_cnpj" name="CNPJ">
                    </div>
                </div>

                <div style="margin-top: 20px; text-align: right;">
                    <button type="button" class="btn-sm btn-danger" onclick="fecharModal()" style="margin-right: 10px;">Cancelar</button>
                    <button type="submit" class="btn-sm btn-edit">Salvar Alterações</button>
                </div>
            </form>
        </div>
    </div>

    <script src="estatisticas.js"></script>
    <script>
        // Funções do Modal
        var modal = document.getElementById("editModal");

        function abrirModal(btn) {
            // Preenche os dados no form do modal
            document.getElementById('modal_user_id').value = btn.getAttribute('data-id');
            document.getElementById('modal_username').value = btn.getAttribute('data-username');
            document.getElementById('modal_email').value = btn.getAttribute('data-email');
            
            var tipo = btn.getAttribute('data-tipo');
            if (tipo) {
                document.getElementById('modal_tipo').value = tipo;
            }
            
            document.getElementById('modal_propriedade').value = btn.getAttribute('data-propriedade');
            document.getElementById('modal_telefone').value = btn.getAttribute('data-telefone');
            document.getElementById('modal_cpf').value = btn.getAttribute('data-cpf');
            document.getElementById('modal_cnpj').value = btn.getAttribute('data-cnpj');

            modal.style.display = "block";
        }

        function fecharModal() {
            modal.style.display = "none";
        }

        // Fechar ao clicar fora do modal
        window.onclick = function(event) {
            if (event.target == modal) {
                fecharModal();
            }
        }
    </script>
</body>
</html>
<?php
mysqli_close($conexao);
?>
