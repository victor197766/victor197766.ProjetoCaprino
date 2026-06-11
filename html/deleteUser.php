<?php
ob_start(); 

include '../db/connection.php';

if (isset($_GET['id'])) {
    $user_id = intval($_GET['id']);

    // Prepared statement para prevenir SQL Injection
    $stmt = mysqli_prepare($conexao, "DELETE FROM usuario WHERE user_id = ?");
    mysqli_stmt_bind_param($stmt, "i", $user_id);

    if (mysqli_stmt_execute($stmt)) {
        header('Location: listUser.php?msg=deletado');
        exit();
    } else {
        echo "Erro ao deletar usuário: " . mysqli_error($conexao);
    }
    mysqli_stmt_close($stmt);
} else {
    header('Location: listUser.php');
    exit();
}

mysqli_close($conexao);
ob_end_flush();
?>