<?php
$servidor = "localhost";
$usuario = "root";     
$senha = "";           
$banco = "mydb";       
$porta = 3306;         

// Cria a conexão
$conexao = mysqli_connect($servidor, $usuario, $senha, $banco, $porta);

// Verifica a conexão
if (!$conexao) {
    die("Erro na conexão: " . mysqli_connect_error());
} else {
    echo "A conexão com o banco de dados foi feita com sucesso!";
}
?>