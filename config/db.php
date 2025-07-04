<?php
$host = 'localhost';
$dbname = 'Honsu_Tickets';
$user = 'root';
$pass = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
 
} catch (PDOException $e) {
    die("Error en la conexión: " . $e->getMessage());
}
?>

