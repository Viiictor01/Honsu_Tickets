<?php
session_start();
require_once '../config/db.php';

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);

    if (empty($username) || empty($password)) {
        header("Location: ../views/login.php?error=Por favor completa todos los campos.");
        exit();
    }

    try {
        // Seleccionar también el rol y el AreaId del usuario
        $stmt = $pdo->prepare("SELECT Id, Username, Password, Role, AreaId FROM `User` WHERE Username = :username LIMIT 1");
        $stmt->execute(['username' => $username]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user && password_verify($password, $user['Password'])) {
            $_SESSION['user_id'] = $user['Id'];
            $_SESSION['username'] = $user['Username'];
            $_SESSION['user_role'] = $user['Role'];
            $_SESSION['user_area_id'] = $user['AreaId']; // Guardar el AreaId en la sesión

            // Redirigir según el rol
            if ($_SESSION['user_role'] === 'admin') {
                header("Location: ../views/dashboard.php");
            } else {
                header("Location: ../views/user_tickets.php");
            }
            exit();
        } else {
            header("Location: ../views/login.php?error=Usuario o contraseña incorrectos.");
            exit();
        }

    } catch (PDOException $e) {
        // En un entorno de producción, es mejor loggear el error y mostrar un mensaje genérico
        // error_log("Error de login: " . $e->getMessage());
        header("Location: ../views/login.php?error=Error del servidor. Por favor, inténtalo de nuevo más tarde.");
        exit();
    }
} else {
    header("Location: ../views/login.php");
    exit();
}