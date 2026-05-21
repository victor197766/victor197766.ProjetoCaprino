<?php
    session_start();
    include '../db/connection.php';

    $email =$_POST['email'];
    $password = $_POST['senha'];

    $sql = "SELECT * FROM usuario WHERE email = '$email'";
    $result = mysqli_query($conexao, $sql);

    if (mysqli_num_rows($result) == 1) {
        $user = mysqli_fetch_assoc($result);

        if(password_verify($password, $user['senha'])) {
            $_SESSION['usuario_email'] = $usuario['email'];
            header('Location: saude.html');
        } else {
            echo 'Senha errada!';        
        }
    } else {
        echo 'Email não cadastrado!';
    }
?>