<?php
// debug.php - Arquivo de debug para testar o sistema

echo "<h1>Debug do Sistema GEstufas</h1>";

// Informações do servidor
echo "<h2>Informações do PHP:</h2>";
echo "PHP Version: " . phpversion() . "<br>";
echo "SERVER_SOFTWARE: " . $_SERVER['SERVER_SOFTWARE'] . "<br>";
echo "REQUEST_METHOD: " . $_SERVER['REQUEST_METHOD'] . "<br>";
echo "REQUEST_URI: " . $_SERVER['REQUEST_URI'] . "<br>";

// Parâmetros GET
echo "<h2>Parâmetros GET:</h2>";
echo "Controller: " . ($_GET['c'] ?? 'não definido') . "<br>";
echo "Action: " . ($_GET['a'] ?? 'não definido') . "<br>";

// Teste de autoload
echo "<h2>Teste de Classes:</h2>";
if (class_exists('Router')) {
    echo "✅ Classe Router encontrada<br>";
} else {
    echo "❌ Classe Router não encontrada<br>";
}

if (class_exists('AuthController')) {
    echo "✅ Classe AuthController encontrada<br>";
} else {
    echo "❌ Classe AuthController não encontrada<br>";
}

// Teste de conexão com banco
echo "<h2>Teste de Conexão com Banco:</h2>";
try {
    require_once 'startup/config.php';
    $connection = ActiveRecord\ConnectionManager::get_connection();
    echo "✅ Conexão com banco: OK<br>";
} catch (Exception $e) {
    echo "❌ Erro na conexão: " . $e->getMessage() . "<br>";
}

// Links para teste
echo "<h2>Links para Teste:</h2>";
echo "<a href='?c=home&a=index'>Home</a><br>";
echo "<a href='?c=auth&a=index'>Auth Index</a><br>";
echo "<a href='?c=auth&a=login'>Auth Login</a><br>";
echo "<a href='?c=auth&a=register'>Auth Register</a><br>";
?>
