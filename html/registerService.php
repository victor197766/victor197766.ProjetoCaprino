<?php
include '../db/connection.php';

if (isset($_POST['farm-name'])) {
    // Código para Produtor
    $email = $_POST['farm-email'];
    $password = $_POST['farm-password'];
    $username = $_POST['farm-name']; 
    
    $hashpass = password_hash($password, PASSWORD_DEFAULT);
    $sql = "INSERT INTO usuario (email, senha, username) VALUES ('$email', '$hashpass', '$username')";
    
    if(mysqli_query($conexao, $sql)) {
        header('Location: recuperarConta.html');
    } else {
        echo "Erro ao cadastrar produtor: " . mysqli_error($conexao);
    }
} else {
    // Código para Visitante
    $email = $_POST['vis-email'];
    $password = $_POST['vis-password'];
    $username = $_POST['vis-name'];
    
    $hashpass = password_hash($password, PASSWORD_DEFAULT);
    $sql = "INSERT INTO usuario (email, senha, username) VALUES ('$email', '$hashpass', '$username')";
    
    if(mysqli_query($conexao, $sql)) {
        header('Location: recuperarConta.html');
    } else {
        echo "Erro ao cadastrar visitante: " . mysqli_error($conexao);
    }
}
?>