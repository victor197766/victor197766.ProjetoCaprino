<?php
session_start();
include 'db/connection.php';

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

    // Verificação de posse: admin pode editar qualquer; produtor só os seus
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
        throw new Exception('Animal não encontrado ou sem permissão para editar.');
    }
    mysqli_stmt_close($stmtCheck);

    // Dados do formulário
    $especie        = $_POST['especie'] ?? 'Caprino';
    $sexo           = $_POST['sexo'] ?? 'Macho';
    $nome           = !empty(trim($_POST['nome'] ?? '')) ? trim($_POST['nome']) : null;
    $raca           = trim($_POST['raca'] ?? '');
    $peso_kg        = floatval($_POST['peso'] ?? 0);
    $idade          = intval($_POST['idade'] ?? 0);
    $estado_atual   = !empty(trim($_POST['tratamento'] ?? '')) ? trim($_POST['tratamento']) : null;
    $esta_prenha    = ($sexo === 'Fêmea' && isset($_POST['esta_prenha']) && $_POST['esta_prenha'] == '1') ? 1 : 0;
    $tempo_gestacao = ($esta_prenha && !empty(trim($_POST['tempo_gestacao'] ?? ''))) ? trim($_POST['tempo_gestacao']) : null;
    $identificador  = trim($_POST['identificador'] ?? '');
    $lote_id        = !empty($_POST['lote_id']) ? intval($_POST['lote_id']) : null;
    $reprodutor_id  = !empty($_POST['reprodutor_id']) ? trim($_POST['reprodutor_id']) : null;
    $matriz_id      = !empty($_POST['matriz_id']) ? intval($_POST['matriz_id']) : null;
    $info_extras    = !empty(trim($_POST['info_extras'] ?? '')) ? trim($_POST['info_extras']) : null;
    $nascimento_faz = isset($_POST['nasceu_fazenda']) && $_POST['nasceu_fazenda'] == '1' ? 1 : 0;
    $vacinado_prev  = isset($_POST['vacinado']) && $_POST['vacinado'] == '1' ? 1 : 0;

    if (empty($identificador) || empty($raca) || $peso_kg <= 0) {
        throw new Exception('Preencha os campos obrigatórios: raça, identificador e peso.');
    }

    $sql = "UPDATE animal SET
                especie = ?, sexo = ?, nome = ?, raca = ?, peso_kg = ?,
                idade = ?, estado_atual = ?, esta_prenha = ?, tempo_gestacao = ?,
                identificador = ?, lote_id = ?, reprodutor_id = ?, matriz_id = ?,
                info_extras = ?, nascimento_fazenda = ?, vacinado_prev = ?
            WHERE id = ?";

    $stmt = mysqli_prepare($conexao, $sql);
    if (!$stmt) throw new Exception(mysqli_error($conexao));

    // tipos: s s s s d i s i s s i s i s i i i  (17 params)
    mysqli_stmt_bind_param($stmt, "ssssdisissisisiii",
        $especie,       // s
        $sexo,          // s
        $nome,          // s
        $raca,          // s
        $peso_kg,       // d
        $idade,         // i
        $estado_atual,  // s
        $esta_prenha,   // i
        $tempo_gestacao,// s
        $identificador, // s
        $lote_id,       // i
        $reprodutor_id, // s
        $matriz_id,     // i
        $info_extras,   // s
        $nascimento_faz,// i
        $vacinado_prev, // i
        $animal_id      // i
    );

    $redirect_back = $_POST['redirect_back'] ?? 'identificacao';
    $baseOk  = ($redirect_back === 'cuidados') ? 'cuidados.php?tab=lista' : 'identificacao.php?tab=lista';

    if (mysqli_stmt_execute($stmt)) {
        header('Location: ' . $baseOk . '&sucesso=editado');
    } else {
        throw new Exception('Erro ao atualizar no banco de dados: ' . mysqli_stmt_error($stmt));
    }

    mysqli_stmt_close($stmt);

} catch (Exception $e) {
    $redirect_back = $_POST['redirect_back'] ?? 'identificacao';
    $baseErr = ($redirect_back === 'cuidados') ? 'cuidados.php?tab=lista' : 'identificacao.php?tab=lista';
    header('Location: ' . $baseErr . '&erro=' . urlencode($e->getMessage()));
}

mysqli_close($conexao);
