<?php
/**
 * Script de Setup do Administrador
 * Execute UMA VEZ para criar o usuário admin e atualizar o banco de dados.
 * Após executar, DELETE ou mova este arquivo por segurança.
 */
include '../db/connection.php';

$erros  = [];
$avisos = [];
$ok     = [];

// -------------------------------------------------------
// 1. Modificar o ENUM da coluna 'tipo' para incluir 'administrador'
// -------------------------------------------------------
$sqlAlterEnum = "ALTER TABLE usuario MODIFY COLUMN tipo ENUM('produtor', 'visitante', 'administrador') NOT NULL DEFAULT 'produtor'";
if (mysqli_query($conexao, $sqlAlterEnum)) {
    $ok[] = "✅ Coluna <code>tipo</code> alterada para incluir <strong>administrador</strong>.";
} else {
    $err = mysqli_error($conexao);
    if (strpos($err, 'Duplicate') !== false || strpos($err, 'already') !== false) {
        $avisos[] = "⚠️ A coluna <code>tipo</code> já inclui 'administrador' — nenhuma alteração necessária.";
    } else {
        $erros[] = "❌ Erro ao alterar coluna tipo: " . htmlspecialchars($err);
    }
}

// -------------------------------------------------------
// 2. Garantir que a coluna 'suspenso' exista
// -------------------------------------------------------
$checkCol = mysqli_query($conexao, "SHOW COLUMNS FROM usuario LIKE 'suspenso'");
if (mysqli_num_rows($checkCol) == 0) {
    if (mysqli_query($conexao, "ALTER TABLE usuario ADD COLUMN suspenso TINYINT(1) NOT NULL DEFAULT 0")) {
        $ok[] = "✅ Coluna <code>suspenso</code> adicionada.";
    } else {
        $erros[] = "❌ Erro ao adicionar coluna suspenso: " . htmlspecialchars(mysqli_error($conexao));
    }
} else {
    $avisos[] = "⚠️ Coluna <code>suspenso</code> já existe.";
}

// -------------------------------------------------------
// 3. Inserir (ou atualizar) o usuário administrador
// -------------------------------------------------------
$adminEmail    = 'admin@email.com';
$adminSenha    = 'admin';
$adminUsername = 'Administrador';
$adminTipo     = 'administrador';
$senhaHash     = password_hash($adminSenha, PASSWORD_DEFAULT);

// Verifica se já existe
$stmt = mysqli_prepare($conexao, "SELECT user_id, tipo FROM usuario WHERE email = ?");
mysqli_stmt_bind_param($stmt, "s", $adminEmail);
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);
$adminExistente = mysqli_fetch_assoc($res);
mysqli_stmt_close($stmt);

if ($adminExistente) {
    // Atualiza o tipo para administrador e redefine a senha
    $stmtUp = mysqli_prepare($conexao, "UPDATE usuario SET tipo = ?, senha = ?, username = ?, suspenso = 0 WHERE email = ?");
    mysqli_stmt_bind_param($stmtUp, "ssss", $adminTipo, $senhaHash, $adminUsername, $adminEmail);
    if (mysqli_stmt_execute($stmtUp)) {
        $ok[] = "✅ Usuário <strong>admin@email.com</strong> já existia — tipo atualizado para <strong>administrador</strong> e senha redefinida.";
    } else {
        $erros[] = "❌ Erro ao atualizar usuário admin: " . htmlspecialchars(mysqli_error($conexao));
    }
    mysqli_stmt_close($stmtUp);
} else {
    // Cria novo usuário admin
    $stmtIns = mysqli_prepare($conexao, "INSERT INTO usuario (username, email, senha, tipo, suspenso) VALUES (?, ?, ?, ?, 0)");
    mysqli_stmt_bind_param($stmtIns, "ssss", $adminUsername, $adminEmail, $senhaHash, $adminTipo);
    if (mysqli_stmt_execute($stmtIns)) {
        $ok[] = "✅ Usuário administrador criado com sucesso! <br>Email: <strong>admin@email.com</strong> | Senha: <strong>admin</strong>";
    } else {
        $erros[] = "❌ Erro ao criar usuário admin: " . htmlspecialchars(mysqli_error($conexao));
    }
    mysqli_stmt_close($stmtIns);
}

mysqli_close($conexao);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Setup Admin — ControlCabra</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 680px; margin: 60px auto; background: #0f1117; color: #e0e0e0; padding: 0 20px; }
        h1 { color: #4caf50; margin-bottom: 30px; }
        .card { background: #1a1d2e; border-radius: 10px; padding: 24px; margin-bottom: 16px; border: 1px solid #2a2d3e; }
        .ok { color: #4caf50; }
        .warn { color: #ffc107; }
        .err { color: #f44336; }
        li { margin-bottom: 8px; }
        .btn { display: inline-block; margin-top: 20px; padding: 12px 24px; background: #4caf50; color: #fff; text-decoration: none; border-radius: 6px; font-weight: bold; }
        .btn:hover { background: #388e3c; }
        .warning-box { background: #2d1a00; border: 1px solid #ffc107; border-radius: 8px; padding: 16px; margin-top: 24px; color: #ffc107; }
    </style>
</head>
<body>
    <h1>⚙️ Setup do Administrador — ControlCabra</h1>

    <?php if (!empty($ok)): ?>
    <div class="card">
        <h3 class="ok">✅ Concluído com sucesso</h3>
        <ul>
            <?php foreach ($ok as $msg): ?>
                <li class="ok"><?= $msg ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
    <?php endif; ?>

    <?php if (!empty($avisos)): ?>
    <div class="card">
        <h3 class="warn">⚠️ Avisos</h3>
        <ul>
            <?php foreach ($avisos as $msg): ?>
                <li class="warn"><?= $msg ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
    <?php endif; ?>

    <?php if (!empty($erros)): ?>
    <div class="card">
        <h3 class="err">❌ Erros</h3>
        <ul>
            <?php foreach ($erros as $msg): ?>
                <li class="err"><?= $msg ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
    <?php endif; ?>

    <div class="warning-box">
        <strong>⚠️ Importante:</strong> Por segurança, <strong>delete ou renomeie</strong> este arquivo após o setup.<br>
        Credenciais: email <code>admin@email.com</code> / senha <code>admin</code>
    </div>

    <a class="btn" href="recuperarConta.html">Ir para o Login</a>
</body>
</html>
