<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['usuario_id'])) {
    echo json_encode(['success' => false, 'message' => 'Sessão expirada. Faça login novamente.']);
    exit();
}

include 'db/connection.php';

$user_id = intval($_SESSION['usuario_id']);

// Prepared statement para prevenir SQL Injection (mesmo padrão do deleteUser.php)
$stmt = mysqli_prepare($conexao, "DELETE FROM usuario WHERE user_id = ?");
mysqli_stmt_bind_param($stmt, "i", $user_id);

if (mysqli_stmt_execute($stmt)) {
    mysqli_stmt_close($stmt);
    mysqli_close($conexao);

    // Destroi a sessão após deletar
    session_unset();
    session_destroy();

    echo json_encode(['success' => true, 'message' => 'Sua conta foi deletada permanentemente.']);
} else {
    echo json_encode(['success' => false, 'message' => 'Erro ao deletar a conta: ' . mysqli_error($conexao)]);
    mysqli_stmt_close($stmt);
    mysqli_close($conexao);
}
?>
