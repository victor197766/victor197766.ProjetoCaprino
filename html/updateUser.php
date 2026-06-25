<?php
ob_start();

include '../db/connection.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['user_id'])) {
    $user_id  = intval($_POST['user_id']);
    $username = trim($_POST['username']);
    $email    = trim($_POST['email']);
    $password = trim($_POST['password']);

    // Validação básica
    if (empty($username) || empty($email)) {
        header('Location: editUser.php?id=' . $user_id . '&msg=campos_vazios');
        exit();
    }

    if ($password !== '') {
        // Atualiza também a senha
        $senha_hash = password_hash($password, PASSWORD_DEFAULT);
        // CORREÇÃO: coluna era 'password' (inexistente) — corrigido para 'senha'
        $stmt = mysqli_prepare($conexao, "UPDATE usuario SET username = ?, email = ?, senha = ? WHERE user_id = ?");
        mysqli_stmt_bind_param($stmt, "sssi", $username, $email, $senha_hash, $user_id);
    } else {
        // Mantém a senha atual
        $stmt = mysqli_prepare($conexao, "UPDATE usuario SET username = ?, email = ? WHERE user_id = ?");
        mysqli_stmt_bind_param($stmt, "ssi", $username, $email, $user_id);
    }

    if (mysqli_stmt_execute($stmt)) {
        header('Location: listUser.php?msg=atualizado');
        exit();
    } else {
        echo "Erro ao atualizar usuário: " . mysqli_error($conexao);
    }

    mysqli_stmt_close($stmt);
} else {
    header('Location: listUser.php');
    exit();
}

mysqli_close($conexao);
ob_end_flush();
?>
