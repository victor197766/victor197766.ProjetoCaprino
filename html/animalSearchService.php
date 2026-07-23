<?php
session_start();
include '../db/connection.php';
header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['usuario_id'])) {
    echo json_encode(['success' => false, 'error' => 'Não autenticado']);
    exit();
}

$user_id     = intval($_SESSION['usuario_id']);
$tipoUsuario = strtolower($_SESSION['usuario_tipo'] ?? 'produtor');
$q           = trim($_GET['q'] ?? '');

if (strlen($q) === 0) {
    echo json_encode(['success' => true, 'animais' => []]);
    exit();
}

$like = '%' . $q . '%';

if ($tipoUsuario === 'admin' || $tipoUsuario === 'administrador') {
    $sql = "SELECT a.id, a.nome, a.especie, a.sexo, a.raca, a.peso_kg,
                   a.identificador, a.estado_atual, l.nome AS lote_nome
            FROM animal a
            LEFT JOIN lote l ON a.lote_id = l.id
            WHERE a.identificador LIKE ? OR a.nome LIKE ? OR a.raca LIKE ?
            ORDER BY a.id DESC LIMIT 25";
    $stmt = mysqli_prepare($conexao, $sql);
    mysqli_stmt_bind_param($stmt, "sss", $like, $like, $like);
} else {
    $sql = "SELECT a.id, a.nome, a.especie, a.sexo, a.raca, a.peso_kg,
                   a.identificador, a.estado_atual, l.nome AS lote_nome
            FROM animal a
            LEFT JOIN lote l ON a.lote_id = l.id
            WHERE l.user_id = ?
              AND (a.identificador LIKE ? OR a.nome LIKE ? OR a.raca LIKE ?)
            ORDER BY a.id DESC LIMIT 25";
    $stmt = mysqli_prepare($conexao, $sql);
    mysqli_stmt_bind_param($stmt, "isss", $user_id, $like, $like, $like);
}

if (!$stmt) {
    echo json_encode(['success' => false, 'error' => mysqli_error($conexao)]);
    exit();
}

mysqli_stmt_execute($stmt);
$res     = mysqli_stmt_get_result($stmt);
$animais = [];
while ($row = mysqli_fetch_assoc($res)) {
    $animais[] = $row;
}
mysqli_stmt_close($stmt);
mysqli_close($conexao);

echo json_encode(['success' => true, 'animais' => $animais]);
