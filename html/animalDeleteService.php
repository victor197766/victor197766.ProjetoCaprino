<?php
session_start();
include '../db/connection.php';

if (!isset($_SESSION['usuario_id']) || $_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: identificacao.php');
    exit();
}

$user_id     = intval($_SESSION['usuario_id']);
$tipoUsuario = strtolower($_SESSION['usuario_tipo'] ?? 'produtor');

if ($tipoUsuario === 'visitante') {
    header('Location: identificacao.php?tab=lista&erro=' . urlencode('Sem permissão.'));
    exit();
}

try {
    $animal_id = intval($_POST['animal_id'] ?? 0);
    if ($animal_id <= 0) {
        throw new Exception('ID do animal inválido.');
    }

    // Verificação de posse
    if ($tipoUsuario === 'admin' || $tipoUsuario === 'administrador') {
        $stmtCheck = mysqli_prepare($conexao, "SELECT id FROM animal WHERE id = ?");
        mysqli_stmt_bind_param($stmtCheck, "i", $animal_id);
    } else {
        $stmtCheck = mysqli_prepare($conexao,
            "SELECT a.id FROM animal a
             LEFT JOIN lote l ON a.lote_id = l.id
             WHERE a.id = ? AND (l.user_id = ? OR a.lote_id IS NULL)");
        mysqli_stmt_bind_param($stmtCheck, "ii", $animal_id, $user_id);
    }
    mysqli_stmt_execute($stmtCheck);
    $resCheck = mysqli_stmt_get_result($stmtCheck);
    if (mysqli_num_rows($resCheck) === 0) {
        throw new Exception('Animal não encontrado ou sem permissão para excluir.');
    }
    mysqli_stmt_close($stmtCheck);

    $stmtDel = mysqli_prepare($conexao, "DELETE FROM animal WHERE id = ?");
    if (!$stmtDel) throw new Exception(mysqli_error($conexao));
    mysqli_stmt_bind_param($stmtDel, "i", $animal_id);

    $redirect_back = $_POST['redirect_back'] ?? 'identificacao';
    $baseOk = ($redirect_back === 'cuidados') ? 'cuidados.php?tab=lista' : 'identificacao.php?tab=lista';

    if (mysqli_stmt_execute($stmtDel)) {
        header('Location: ' . $baseOk . '&sucesso=deletado');
    } else {
        throw new Exception('Erro ao excluir: ' . mysqli_stmt_error($stmtDel));
    }

    mysqli_stmt_close($stmtDel);

} catch (Exception $e) {
    $redirect_back = $_POST['redirect_back'] ?? 'identificacao';
    $baseErr = ($redirect_back === 'cuidados') ? 'cuidados.php?tab=lista' : 'identificacao.php?tab=lista';
    header('Location: ' . $baseErr . '&erro=' . urlencode($e->getMessage()));
}

mysqli_close($conexao);
