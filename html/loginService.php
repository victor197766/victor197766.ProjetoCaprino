<?php
session_start();
include 'db/connection.php';

$email    = $_POST['email'] ?? '';
$password = $_POST['password'] ?? '';

$stmt = mysqli_prepare($conexao, "SELECT * FROM usuario WHERE email = ?");
mysqli_stmt_bind_param($stmt, "s", $email);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if (mysqli_num_rows($result) == 1) {
    $user = mysqli_fetch_assoc($result);

    if (password_verify($password, $user['senha'])) {
        if (isset($user['suspenso']) && $user['suspenso'] == 1) {
            mysqli_stmt_close($stmt);
            mysqli_close($conexao);
            header('Location: recuperarConta.html?erro=suspensa');
            exit();
        }

        $_SESSION['usuario_id']      = $user['user_id'];
        $_SESSION['usuario_nome']    = $user['username'];
        $_SESSION['usuario_email']   = $user['email'];
        $_SESSION['usuario_fazenda'] = $user['nome_propriedade'] ?? '';
        $_SESSION['usuario_tipo']    = $user['tipo'] ?? 'produtor';

        mysqli_stmt_close($stmt);
        mysqli_close($conexao);
        header('Location: estatisticas.php');
        exit();
    } else {
        header('Location: recuperarConta.html?erro=senha');
        exit();
    }
} else {
    header('Location: recuperarConta.html?erro=email');
    exit();
}

mysqli_stmt_close($stmt);
mysqli_close($conexao);
