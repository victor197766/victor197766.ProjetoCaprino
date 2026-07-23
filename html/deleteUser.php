<?php
ob_start(); 

include '../db/connection.php';

if (isset($_GET['id'])) {
    $user_id = intval($_GET['id']);

    // Verificar se o alvo é administrador (conta protegida)
    $stmtCheck = mysqli_prepare($conexao, "SELECT tipo FROM usuario WHERE user_id = ?");
    mysqli_stmt_bind_param($stmtCheck, "i", $user_id);
    mysqli_stmt_execute($stmtCheck);
    $resCheck = mysqli_stmt_get_result($stmtCheck);
    $targetUser = mysqli_fetch_assoc($resCheck);
    mysqli_stmt_close($stmtCheck);

    if ($targetUser && $targetUser['tipo'] === 'administrador') {
        header('Location: administracao.php?msg=erro');
        exit();
    }

    // Prepared statement para prevenir SQL Injection
    $stmt = mysqli_prepare($conexao, "DELETE FROM usuario WHERE user_id = ?");
    mysqli_stmt_bind_param($stmt, "i", $user_id);

    if (mysqli_stmt_execute($stmt)) {
        header('Location: administracao.php?msg=deletado');
        exit();
    } else {
        echo "Erro ao deletar usuário: " . mysqli_error($conexao);
    }
    mysqli_stmt_close($stmt);
} else {
    header('Location: administracao.php');
    exit();
}

mysqli_close($conexao);
ob_end_flush();
?>