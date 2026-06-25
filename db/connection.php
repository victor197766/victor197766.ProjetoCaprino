<?php
$servidor = "localhost";
$usuario  = "root";
$senha    = "";
$banco    = "mydb";
$porta    = 3306;

// Cria a conexão
$conexao = mysqli_connect($servidor, $usuario, $senha, $banco, $porta);

// Verifica a conexão
if (!$conexao) {
    die("Erro na conexão: " . mysqli_connect_error());
}

// CORREÇÃO: Define charset utf8mb4 para suporte completo a Unicode.
// Evita problemas de encoding com acentos, emojis e caracteres especiais.
mysqli_set_charset($conexao, 'utf8mb4');
