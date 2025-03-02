<?php
$host   = 'localhost'; // Defina aqui o host do seu banco de dados
$db     = 'u305836601_chamadosunica';  // Nome do seu banco de dados
$user   = 'u305836601_unicachamados';   // Seu usuário do MySQL
$pass   = '?5RAmi;r7d';                // Sua senha do MySQL
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (\PDOException $e) {
    throw new \PDOException($e->getMessage(), (int)$e->getCode());
}
?>
