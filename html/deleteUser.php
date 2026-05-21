<?php
ob_start(); 

include '../db/connection.php';

if (isset($_GET['id'])) {
    $user_id = intval($_GET['id']);

    $sql = "DELETE FROM usuario WHERE user_id = $user_id";

    if (mysqli_query($conexao, $sql)) {
        header('Location: listUser.php?msg=deletado');
        exit();
    } else {
        echo "Erro ao deletar usuário: " . mysqli_error($conexao);
    }
} else {
    header('Location: listUser.php');
    exit();
}

ob_end_flush();
?>