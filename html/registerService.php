<?php
include '../db/connection.php';

function emailJaExiste($conexao, $email) {
    $stmt = mysqli_prepare($conexao, "SELECT user_id FROM usuario WHERE email = ?");
    mysqli_stmt_bind_param($stmt, "s", $email);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_store_result($stmt);
    $existe = mysqli_stmt_num_rows($stmt) > 0;
    mysqli_stmt_close($stmt);
    return $existe;
}

if (isset($_POST['farm-name'])) {
    // Produtor
    $email      = trim($_POST['farm-email'] ?? '');
    $password   = $_POST['farm-password'] ?? '';
    $username   = trim($_POST['farm-name'] ?? '');
    $propriedade = trim($_POST['farm-property'] ?? '');
    $tipo        = 'produtor';

    if ($email === '' || $password === '' || $username === '') {
        header('Location: recuperarConta.html?erro=campos_vazios&form=produtor');
        exit();
    }

    if (emailJaExiste($conexao, $email)) {
        header('Location: recuperarConta.html?erro=email_repetido&form=produtor');
        exit();
    }

    $hashpass = password_hash($password, PASSWORD_DEFAULT);
    $stmt     = mysqli_prepare($conexao, "INSERT INTO usuario (email, senha, username, tipo, nome_propriedade) VALUES (?, ?, ?, ?, ?)");
    mysqli_stmt_bind_param($stmt, "sssss", $email, $hashpass, $username, $tipo, $propriedade);

    if (mysqli_stmt_execute($stmt)) {
        mysqli_stmt_close($stmt);
        mysqli_close($conexao);
        header('Location: recuperarConta.html?sucesso=cadastro');
        exit();
    } else {
        header('Location: recuperarConta.html?erro=db&form=produtor');
        exit();
    }
    mysqli_stmt_close($stmt);
} else {
    // Visitante / Empregado Rural
    $email    = trim($_POST['vis-email'] ?? '');
    $password = $_POST['vis-password'] ?? '';
    $username = trim($_POST['vis-name'] ?? '');
    $tipo     = 'visitante';

    if ($email === '' || $password === '' || $username === '') {
        header('Location: recuperarConta.html?erro=campos_vazios&form=visitante');
        exit();
    }

    if (emailJaExiste($conexao, $email)) {
        header('Location: recuperarConta.html?erro=email_repetido&form=visitante');
        exit();
    }

    $hashpass = password_hash($password, PASSWORD_DEFAULT);
    $stmt     = mysqli_prepare($conexao, "INSERT INTO usuario (email, senha, username, tipo) VALUES (?, ?, ?, ?)");
    mysqli_stmt_bind_param($stmt, "ssss", $email, $hashpass, $username, $tipo);

    if (mysqli_stmt_execute($stmt)) {
        mysqli_stmt_close($stmt);
        mysqli_close($conexao);
        header('Location: recuperarConta.html?sucesso=cadastro');
        exit();
    } else {
        header('Location: recuperarConta.html?erro=db&form=visitante');
        exit();
    }
    mysqli_stmt_close($stmt);
}

mysqli_close($conexao);
