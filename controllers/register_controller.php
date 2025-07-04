<?php
session_start();
require_once '../config/db.php'; // Asegúrate de que esta ruta sea correcta para tu db.php

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);
    $confirm_password = trim($_POST['confirm_password']);

    // 1. Validaciones básicas de campos
    if (empty($username) || empty($email) || empty($password) || empty($confirm_password)) {
        header("Location: ../views/register.php?error=Por favor completa todos los campos.");
        exit();
    }

    // 2. Validar que las contraseñas coincidan
    if ($password !== $confirm_password) {
        header("Location: ../views/register.php?error=Las contraseñas no coinciden.");
        exit();
    }

    // 3. Validar longitud mínima de la contraseña (opcional pero recomendado)
    if (strlen($password) < 6) { // Por ejemplo, 6 caracteres mínimos
        header("Location: ../views/register.php?error=La contraseña debe tener al menos 6 caracteres.");
        exit();
    }

    // 4. Validar formato de email (opcional pero recomendado)
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        header("Location: ../views/register.php?error=El formato del correo electrónico no es válido.");
        exit();
    }

    try {
        // 5. Verificar si el nombre de usuario o el correo electrónico ya existen
        $stmt_check = $pdo->prepare("SELECT id FROM User WHERE username = :username OR email = :email LIMIT 1");
        $stmt_check->execute(['username' => $username, 'email' => $email]);
        if ($stmt_check->fetch()) {
            header("Location: ../views/register.php?error=El nombre de usuario o el correo electrónico ya están registrados.");
            exit();
        }

        // 6. Hashear la contraseña antes de guardarla en la base de datos
        // ¡Esta es la parte clave para la seguridad!
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);

        // 7. Insertar el nuevo usuario en la base de datos
        $stmt_insert = $pdo->prepare("INSERT INTO User (username, email, password) VALUES (:username, :email, :password)");
        $stmt_insert->execute([
            'username' => $username,
            'email' => $email,
            'password' => $hashed_password // Guardamos la contraseña hasheada
        ]);

        // 8. Redirigir al usuario al login con un mensaje de éxito
        header("Location: ../views/login.php?success=Registro exitoso. Ahora puedes iniciar sesión.");
        exit();

    } catch (PDOException $e) {
        // Manejo de errores de la base de datos
        // Puedes loggear $e->getMessage() para depuración, pero no lo muestres directamente al usuario.
        header("Location: ../views/register.php?error=Error del servidor. Inténtalo de nuevo más tarde.");
        exit();
    }

} else {
    // Si la solicitud no es POST (alguien intenta acceder directamente al controlador), redirigir al formulario
    header("Location: ../views/register.php");
    exit();
}