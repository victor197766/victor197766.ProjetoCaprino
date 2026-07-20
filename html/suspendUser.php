<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['usuario_id'])) {
    echo json_encode(['success' => false, 'message' => 'Sessão expirada. Faça login novamente.']);
    exit();
}

include 'db/connection.php';

$user_id = intval($_SESSION['usuario_id']);

// Primeiro, garantir que a coluna 'suspenso' exista na tabela
$checkColumn = mysqli_query($conexao, "SHOW COLUMNS FROM usuario LIKE 'suspenso'");
if (mysqli_num_rows($checkColumn) == 0) {
    mysqli_query($conexao, "ALTER TABLE usuario ADD COLUMN suspenso TINYINT(1) NOT NULL DEFAULT 0");
}

// Verificar status atual
$stmt = mysqli_prepare($conexao, "SELECT suspenso FROM usuario WHERE user_id = ?");
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$user = mysqli_fetch_assoc($result);
mysqli_stmt_close($stmt);

if (!$user) {
    echo json_encode(['success' => false, 'message' => 'Usuário não encontrado.']);
    mysqli_close($conexao);
    exit();
}

// Suspender a conta (marcar como suspenso = 1)
$stmt = mysqli_prepare($conexao, "UPDATE usuario SET suspenso = 1 WHERE user_id = ?");
mysqli_stmt_bind_param($stmt, "i", $user_id);

if (mysqli_stmt_execute($stmt)) {
    mysqli_stmt_close($stmt);
    mysqli_close($conexao);

    // Destroi a sessão após suspender
    session_unset();
    session_destroy();

    echo json_encode(['success' => true, 'message' => 'Sua conta foi suspensa com sucesso.']);
} else {
    echo json_encode(['success' => false, 'message' => 'Erro ao suspender a conta: ' . mysqli_error($conexao)]);
    mysqli_stmt_close($stmt);
    mysqli_close($conexao);
}
?>
