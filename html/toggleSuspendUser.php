<?php
session_start();

if (!isset($_SESSION['usuario_id'])) {
    header('Location: recuperarConta.html');
    exit();
}

include 'db/connection.php';

if (isset($_GET['id'])) {
    $target_user_id = intval($_GET['id']);

    // Verificar se a coluna suspenso existe
    $checkColumn = mysqli_query($conexao, "SHOW COLUMNS FROM usuario LIKE 'suspenso'");
    if (mysqli_num_rows($checkColumn) == 0) {
        mysqli_query($conexao, "ALTER TABLE usuario ADD COLUMN suspenso TINYINT(1) NOT NULL DEFAULT 0");
    }

    // Obter o status atual do usuário alvo
    $stmt = mysqli_prepare($conexao, "SELECT suspenso FROM usuario WHERE user_id = ?");
    mysqli_stmt_bind_param($stmt, "i", $target_user_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $user = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);

    if ($user) {
        $novo_status = $user['suspenso'] ? 0 : 1; // Inverte o status
        
        $stmt_update = mysqli_prepare($conexao, "UPDATE usuario SET suspenso = ? WHERE user_id = ?");
        mysqli_stmt_bind_param($stmt_update, "ii", $novo_status, $target_user_id);
        
        if (mysqli_stmt_execute($stmt_update)) {
            header('Location: administracao.php?msg=status_alterado');
        } else {
            echo "Erro ao alterar status: " . mysqli_error($conexao);
        }
        mysqli_stmt_close($stmt_update);
    } else {
        echo "Usuário não encontrado.";
    }
} else {
    header('Location: administracao.php');
}

mysqli_close($conexao);
exit();
?>
