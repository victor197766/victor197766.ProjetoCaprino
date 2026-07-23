<?php
ob_start();

include '../db/connection.php';

$sql = "SELECT user_id, username, email, create_time FROM usuario ORDER BY user_id DESC";
$resultado = mysqli_query($conexao, $sql);
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ControlCabra - Gerenciar Usuários</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #2e7d32;
            --danger: #d32f2f;
            --bg: #f5f5f5;
            --card-bg: #ffffff;
            --text: #333333;
            --border: #e0e0e0;
        }

        body {
            font-family: 'Inter', sans-serif;
            background-color: var(--bg);
            color: var(--text);
            margin: 0;
            padding: 20px;
        }

        .container {
            max-width: 1000px;
            margin: 40px auto;
            background: var(--card-bg);
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.05);
        }

        h2 {
            margin-top: 0;
            color: var(--primary);
            border-bottom: 2px solid var(--border);
            padding-bottom: 10px;
        }

        .alert {
            padding: 12px;
            background-color: #d4edda;
            color: #155724;
            border-radius: 6px;
            margin-bottom: 20px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        th, td {
            text-align: left;
            padding: 14px;
            border-bottom: 1px solid var(--border);
        }

        th {
            background-color: #f9f9f9;
            font-weight: 600;
        }

        tr:hover {
            background-color: #fdfdfd;
        }

        .btn-delete {
            background-color: var(--danger);
            color: white;
            border: none;
            padding: 8px 14px;
            border-radius: 6px;
            cursor: pointer;
            text-decoration: none;
            font-size: 14px;
            transition: background 0.2s;
        }

        .btn-delete:hover {
            background-color: #b71c1c;
        }
    </style>
</head>
<body>

<div class="container">
    <h2>Usuários Cadastrados</h2>

    <?php if (isset($_GET['msg']) && $_GET['msg'] == 'deletado'): ?>
        <div class="alert">Usuário removido com sucesso!</div>
    <?php endif; ?>

    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Nome de Usuário</th>
                <th>E-mail</th>
                <th>Data de Cadastro</th>
                <th>Ações</th>
            </tr>
        </thead>
        <tbody>
            <?php 
            if (mysqli_num_rows($resultado) > 0) {
                while ($usuario = mysqli_fetch_assoc($resultado)) {
                    echo "<tr>";
                    echo "<td>" . $usuario['user_id'] . "</td>";
                    echo "<td>" . htmlspecialchars($usuario['username']) . "</td>";
                    echo "<td>" . htmlspecialchars($usuario['email']) . "</td>";
                    echo "<td>" . date('d/m/Y H:i', strtotime($usuario['create_time'])) . "</td>";
                    // CORREÇÃO: Link apontando para o novo deleteUser.php
                    echo "<td>
                            <a href='deleteUser.php?id=" . $usuario['user_id'] . "' 
                               class='btn-delete' 
                               onclick='return confirm(\"Tem certeza que deseja deletar este usuário?\")'>
                               Excluir
                            </a>
                          </td>";
                    echo "</tr>";
                }
            } else {
                echo "<tr><td colspan='5' style='text-align:center;'>Nenhum usuário cadastrado.</td></tr>";
            }
            ?>
        </tbody>
    </table>
</div>

</body>
</html>