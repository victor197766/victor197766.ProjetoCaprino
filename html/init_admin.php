<?php
/**
 * init_admin.php
 * Include silencioso — executa o setup do administrador automaticamente.
 * Não produz nenhuma saída. Seguro para incluir em qualquer página.
 */

// Só roda se ainda não tiver uma conexão aberta
if (!isset($conexao) || !$conexao) {
    include_once '../db/connection.php';
}

// -------------------------------------------------------
// Verifica se o usuário administrador já existe
// -------------------------------------------------------
$adminEmail = 'admin@email.com';

$stmtCheck = mysqli_prepare($conexao, "SELECT user_id FROM usuario WHERE email = ? LIMIT 1");
mysqli_stmt_bind_param($stmtCheck, "s", $adminEmail);
mysqli_stmt_execute($stmtCheck);
mysqli_stmt_store_result($stmtCheck);
$adminJaExiste = mysqli_stmt_num_rows($stmtCheck) > 0;
mysqli_stmt_close($stmtCheck);

if (!$adminJaExiste) {
    // -------------------------------------------------------
    // 1. Adiciona 'administrador' ao ENUM da coluna 'tipo'
    // -------------------------------------------------------
    mysqli_query($conexao, "ALTER TABLE usuario MODIFY COLUMN tipo ENUM('produtor','visitante','administrador') NOT NULL DEFAULT 'produtor'");

    // -------------------------------------------------------
    // 2. Garante que a coluna 'suspenso' existe
    // -------------------------------------------------------
    $checkCol = mysqli_query($conexao, "SHOW COLUMNS FROM usuario LIKE 'suspenso'");
    if (mysqli_num_rows($checkCol) == 0) {
        mysqli_query($conexao, "ALTER TABLE usuario ADD COLUMN suspenso TINYINT(1) NOT NULL DEFAULT 0");
    }

    // -------------------------------------------------------
    // 3. Cria o usuário administrador
    // -------------------------------------------------------
    $adminSenha    = password_hash('admin', PASSWORD_DEFAULT);
    $adminUsername = 'Administrador';
    $adminTipo     = 'administrador';

    $stmtIns = mysqli_prepare($conexao, "INSERT INTO usuario (username, email, senha, tipo, suspenso) VALUES (?, ?, ?, ?, 0)");
    mysqli_stmt_bind_param($stmtIns, "ssss", $adminUsername, $adminEmail, $adminSenha, $adminTipo);
    mysqli_stmt_execute($stmtIns);
    mysqli_stmt_close($stmtIns);
}
