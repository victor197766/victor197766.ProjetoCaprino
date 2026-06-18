<?php
session_start();
include '../db/connection.php';

$email = $_POST['email'];
$password = $_POST['password'];

// Prepared statement para prevenir SQL Injection
$stmt = mysqli_prepare($conexao, "SELECT * FROM usuario WHERE email = ?");
mysqli_stmt_bind_param($stmt, "s", $email);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if (mysqli_num_rows($result) == 1) {
    $user = mysqli_fetch_assoc($result);

    if (password_verify($password, $user['senha'])) {
        // Verificar se a conta está suspensa
        if (isset($user['suspenso']) && $user['suspenso'] == 1) {
            echo 'Sua conta está suspensa. Entre em contato com o suporte para reativá-la.';
            mysqli_stmt_close($stmt);
            mysqli_close($conexao);
            exit();
        }

        $_SESSION['usuario_id']     = $user['user_id'];
        $_SESSION['usuario_nome']   = $user['username'];
        $_SESSION['usuario_email']  = $user['email'];
        $_SESSION['usuario_fazenda'] = $user['nome_propriedade'];
        header('Location: estatisticas.php');
        exit();
    } else {
        echo 'Senha errada!';
    }
} else {
    echo 'Email não cadastrado!';
}

mysqli_stmt_close($stmt);
mysqli_close($conexao);
