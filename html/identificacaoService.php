<?php
session_start();
include 'db/connection.php';

if (!isset($_SESSION['usuario_id']) || $_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: identificacao.php');
    exit();
}

try {
    $especie       = $_POST['especie'] ?? 'Caprino';
    $sexo          = $_POST['sexo'] ?? 'Macho';
    $nome          = !empty($_POST['nome']) ? trim($_POST['nome']) : null;
    $raca          = trim($_POST['raca'] ?? '');
    $peso_kg       = floatval($_POST['peso'] ?? 0);
    $idade         = intval($_POST['idade'] ?? 0);
    $estado_atual  = !empty($_POST['tratamento']) ? trim($_POST['tratamento']) : null;
    
    $esta_prenha   = isset($_POST['esta_prenha']) && $_POST['esta_prenha'] == '1' ? 1 : 0;
    $tempo_gestacao = !empty($_POST['tempo_gestacao']) && $esta_prenha ? trim($_POST['tempo_gestacao']) : null;
    
    $identificador = trim($_POST['identificador'] ?? '');
    
    $lote_id       = !empty($_POST['lote_id']) ? intval($_POST['lote_id']) : null;
    $reprodutor_id = !empty($_POST['reprodutor_id']) ? trim($_POST['reprodutor_id']) : null;
    $matriz_id     = !empty($_POST['matriz_id']) ? intval($_POST['matriz_id']) : null;
    
    $info_extras   = !empty($_POST['info_extras']) ? trim($_POST['info_extras']) : null;
    
    $nasceu_fazenda = isset($_POST['nasceu_fazenda']) && $_POST['nasceu_fazenda'] == '1' ? 1 : 0;
    $vacinado_prev  = isset($_POST['vacinado']) && $_POST['vacinado'] == '1' ? 1 : 0;

    // Destino do redirect (cuidados ou identificacao)
    $redirect_back = $_POST['redirect_back'] ?? 'identificacao';
    $baseOk  = ($redirect_back === 'cuidados') ? 'cuidados.php?tab=lista' : 'identificacao.php';
    $baseErr = ($redirect_back === 'cuidados') ? 'cuidados.php?tab=registrar' : 'identificacao.php';

    // Basic validation
    if (empty($identificador) || empty($raca) || $peso_kg <= 0) {
        header('Location: ' . $baseErr . '&erro=' . urlencode('Preencha os campos obrigatórios corretamente.'));
        exit();
    }

    $sql = "INSERT INTO animal (
                especie, sexo, nome, raca, peso_kg, idade, estado_atual,
                esta_prenha, tempo_gestacao, identificador, lote_id,
                reprodutor_id, matriz_id, info_extras, nascimento_fazenda, vacinado_prev
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            
    $stmt = mysqli_prepare($conexao, $sql);
    
    if (!$stmt) {
        throw new Exception(mysqli_error($conexao));
    }

    mysqli_stmt_bind_param($stmt, "ssssdisissisisii",
        $especie,
        $sexo,
        $nome,
        $raca,
        $peso_kg,
        $idade,
        $estado_atual,
        $esta_prenha,
        $tempo_gestacao,
        $identificador,
        $lote_id,
        $reprodutor_id,
        $matriz_id,
        $info_extras,
        $nasceu_fazenda,
        $vacinado_prev
    );

    if (mysqli_stmt_execute($stmt)) {
        header('Location: ' . $baseOk . '&sucesso=1');
    } else {
        header('Location: ' . $baseErr . '&erro=' . urlencode('Erro ao salvar no banco de dados.'));
    }

    mysqli_stmt_close($stmt);

} catch (Exception $e) {
    $redirect_back = $_POST['redirect_back'] ?? 'identificacao';
    $baseErr = ($redirect_back === 'cuidados') ? 'cuidados.php?tab=registrar' : 'identificacao.php';
    header('Location: ' . $baseErr . '&erro=' . urlencode($e->getMessage()));
}

mysqli_close($conexao);
