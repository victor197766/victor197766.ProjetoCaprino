<?php
include '../db/connection.php';

function emailJaExiste($conexao, $email)
{
    $stmt = mysqli_prepare($conexao, "SELECT user_id FROM usuario WHERE email = ?");
    mysqli_stmt_bind_param($stmt, "s", $email);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_store_result($stmt);
    $existe = mysqli_stmt_num_rows($stmt) > 0;
    mysqli_stmt_close($stmt);
    return $existe;
}

if (isset($_POST['farm-name'])) {
    // Código para Produtor
    $email = $_POST['farm-email'];
    $password = $_POST['farm-password'];
    $username = $_POST['farm-name'];
    $propriedade = $_POST['farm-property'];
    $tipo = 'produtor';
    $hashpass = password_hash($password, PASSWORD_DEFAULT);

    if (emailJaExiste($conexao, $email)) {
        echo "Este e-mail já está cadastrado.";
        exit();
    }

    // Prepared statement para prevenir SQL Injection
    $stmt = mysqli_prepare($conexao, "INSERT INTO usuario (email, senha, username, tipo, nome_propriedade) VALUES (?, ?, ?, ?, ?)");
    mysqli_stmt_bind_param($stmt, "sssss", $email, $hashpass, $username, $tipo, $propriedade);
    if (mysqli_stmt_execute($stmt)) {
        header('Location: recuperarConta.html');
        exit();
    } else {
        echo "Erro ao cadastrar produtor: " . mysqli_error($conexao);
    }
    mysqli_stmt_close($stmt);
} else {
    // Código para Visitante
    $email = $_POST['vis-email'];
    $password = $_POST['vis-password'];
    $username = $_POST['vis-name'];
    $tipo = 'visitante';
    $propriedade = NULL;

    $hashpass = password_hash($password, PASSWORD_DEFAULT);
    if (emailJaExiste($conexao, $email)) {
        echo "Este e-mail já está cadastrado.";
        exit();
    }

    // Prepared statement para prevenir SQL Injection
    $stmt = mysqli_prepare($conexao, "INSERT INTO usuario (email, senha, username, tipo, nome_propriedade) VALUES (?, ?, ?, ?, ?)");
    mysqli_stmt_bind_param($stmt, "sssss", $email, $hashpass, $username, $tipo, $propriedade);

    if (mysqli_stmt_execute($stmt)) {
        header('Location: recuperarConta.html');
        exit();
    } else {
        echo "Erro ao cadastrar visitante: " . mysqli_error($conexao);
    }
    mysqli_stmt_close($stmt);
}

mysqli_close($conexao);
