<?php
ob_start();

include 'db/connection.php';

function emailJaExiste($conexao, $email)
{
    $stmt = mysqli_prepare($conexao, "SELECT user_id FROM usuario WHERE email = ?");
    mysqli_stmt_bind_param($stmt, "s", $email);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_store_result($stmt);
    $existe = mysqli_stmt_num_rows($stmt) > 0;
    mysqli_stmt_close($stmt);
    return $existe;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: listUser.php');
    exit();
}

// Campos comuns
$tipo         = $_POST['tipo'] === 'produtor' ? 'produtor' : 'visitante';
$username     = trim($_POST['username']);
$email        = trim($_POST['email']);
$password     = $_POST['password'];
$num_telefone = trim($_POST['num_telefone']) ?: null;

// Campos exclusivos do produtor
$nome_propriedade = $tipo === 'produtor' ? trim($_POST['nome_propriedade']) ?: null : null;
$cpf              = $tipo === 'produtor' ? trim($_POST['cpf'])              ?: null : null;
$cnpj             = $tipo === 'produtor' ? trim($_POST['cnpj'])             ?: null : null;

// Validação básica
if (empty($username) || empty($email) || empty($password)) {
    echo "Preencha todos os campos obrigatórios.";
    exit();
}

if (strlen($password) < 6) {
    echo "A senha deve ter pelo menos 6 caracteres.";
    exit();
}

if (emailJaExiste($conexao, $email)) {
    echo "Este e-mail já está cadastrado.";
    exit();
}

$hashpass = password_hash($password, PASSWORD_DEFAULT);

$stmt = mysqli_prepare($conexao,
    "INSERT INTO usuario (username, email, senha, tipo, nome_propriedade, num_telefone, CPF, CNPJ)
     VALUES (?, ?, ?, ?, ?, ?, ?, ?)"
);

mysqli_stmt_bind_param($stmt, "ssssssss",
    $username,
    $email,
    $hashpass,
    $tipo,
    $nome_propriedade,
    $num_telefone,
    $cpf,
    $cnpj
);

if (mysqli_stmt_execute($stmt)) {
    header('Location: listUser.php?msg=criado');
    exit();
} else {
    echo "Erro ao criar usuário: " . mysqli_error($conexao);
}

mysqli_stmt_close($stmt);
mysqli_close($conexao);
ob_end_flush();
?>
