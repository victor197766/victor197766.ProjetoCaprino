<?php
include '../db/connection.php';

if (isset($_POST['farm-name'])) {
    // Código para Produtor
    // Ajustar BD p/ colocar FK do nome/id do lote!
    $email = $_POST['farm-email'];
    $password = $_POST['farm-password'];
    $name = $_POST['farm-name'];
    $sql = "INSERT INTO usuario (email, senha, username) VALUES ('$email', '$password', '$username')";
    mysqli_query($conexao,$sql);
    header('Location: recuperarconta.html');
} else {
    // Código para Visitante
    $email = $_POST['vis-email'];
    $password = $_POST['vis-password'];
    $username = $_POST['vis-name'];
    $sql = "INSERT INTO usuario (email, senha, username) VALUES ('$email', '$password', '$username')";
    mysqli_query($conexao,$sql);
    header('Location: recuperarconta.html');
}

?>