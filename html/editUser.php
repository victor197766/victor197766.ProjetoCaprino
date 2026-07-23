<?php
session_start();
if (!isset($_SESSION['usuario_id'])) {
    header('Location: recuperarConta.html');
    exit();
}

include '../db/connection.php';

// Verificar se o usuário logado é 'visitante'. Visitantes não podem editar.
$user_session_id = $_SESSION['usuario_id'];
$stmt_check = mysqli_prepare($conexao, "SELECT tipo FROM usuario WHERE user_id = ?");
mysqli_stmt_bind_param($stmt_check, "i", $user_session_id);
mysqli_stmt_execute($stmt_check);
$res_check = mysqli_stmt_get_result($stmt_check);
$user_logged_in = mysqli_fetch_assoc($res_check);
mysqli_stmt_close($stmt_check);

if ($user_logged_in && $user_logged_in['tipo'] === 'visitante') {
    header('Location: estatisticas.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $target_user_id = intval($_POST['user_id']);
    $new_username = $_POST['username'];
    $new_email = $_POST['email'];
    $new_tipo = $_POST['tipo'];
    $new_propriedade = $_POST['nome_propriedade'];
    $new_telefone = $_POST['num_telefone'];
    $new_cpf = $_POST['CPF'];
    $new_cnpj = $_POST['CNPJ'];

    $sql_update = "UPDATE usuario SET username = ?, email = ?, tipo = ?, nome_propriedade = ?, num_telefone = ?, CPF = ?, CNPJ = ? WHERE user_id = ?";
    $stmt_update = mysqli_prepare($conexao, $sql_update);
    
    mysqli_stmt_bind_param($stmt_update, "sssssssi", $new_username, $new_email, $new_tipo, $new_propriedade, $new_telefone, $new_cpf, $new_cnpj, $target_user_id);
    
    if (mysqli_stmt_execute($stmt_update)) {
        header('Location: administracao.php?msg=editado');
    } else {
        header('Location: administracao.php?msg=erro');
    }
    
    mysqli_stmt_close($stmt_update);
} else {
    header('Location: administracao.php');
}

mysqli_close($conexao);
exit();
?>
