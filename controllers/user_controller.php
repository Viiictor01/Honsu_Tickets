<?php
session_start();
require_once '../config/db.php';

// Asegurar que solo usuarios admin puedan acceder a este controlador
if (!isset($_SESSION['user_id']) || (isset($_SESSION['user_role']) && $_SESSION['user_role'] !== 'admin')) {
    header("Location: ../views/login.php");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $action = $_POST['action'] ?? ''; // 'edit'
    $user_id_to_manage = $_POST['user_id'] ?? null;
    $current_admin_id = $_SESSION['user_id'];

    if (empty($user_id_to_manage)) {
        header("Location: ../views/list_users.php?error=ID de usuario no especificado.");
        exit();
    }

    try {
        if ($action === 'edit') {
            $username = trim($_POST['username'] ?? '');
            $email = trim($_POST['email'] ?? '');
            $role = $_POST['role'] ?? '';
            $password = trim($_POST['password'] ?? ''); // Nueva contraseña, opcional

            // Validaciones básicas
            if (empty($username) || empty($email) || empty($role)) {
                header("Location: ../views/edit_user.php?id=" . $user_id_to_manage . "&error=Todos los campos son obligatorios (excepto la contraseña si no se cambia).");
                exit();
            }

            // No permitir que un admin se cambie su propio rol o se elimine a sí mismo (para evitar bloquear el acceso)
            if ($user_id_to_manage == $current_admin_id) {
                if ($role !== $_SESSION['user_role']) {
                    header("Location: ../views/edit_user.php?id=" . $user_id_to_manage . "&error=No puedes cambiar tu propio rol.");
                    exit();
                }
                // Si hay un campo de contraseña vacio para si mismo
                if (!empty($password) && strlen($password) < 6) {
                    header("Location: ../views/edit_user.php?id=" . $user_id_to_manage . "&error=La contraseña debe tener al menos 6 caracteres.");
                    exit();
                }
            }


            $sql = "UPDATE `User` SET `Username` = :username, `Email` = :email, `Role` = :role";
            $params = [
                'username' => $username,
                'email' => $email,
                'role' => $role,
                'user_id' => $user_id_to_manage
            ];

            if (!empty($password)) {
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                $sql .= ", `Password` = :password";
                $params['password'] = $hashed_password;
            }

            $sql .= " WHERE `Id` = :user_id";
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);

            header("Location: ../views/list_users.php?success=Usuario actualizado exitosamente.");
            exit();

        } else {
            header("Location: ../views/list_users.php?error=Acción no válida.");
            exit();
        }

    } catch (PDOException $e) {
        // error_log("Error en user_controller (POST): " . $e->getMessage());
        header("Location: ../views/list_users.php?error=Error del servidor al procesar el usuario: " . $e->getMessage()); // Para depurar
        exit();
    }

} elseif ($_SERVER["REQUEST_METHOD"] === "GET" && (isset($_GET['action']) && $_GET['action'] === 'delete')) {
    // Lógica para eliminar un usuario (via GET, POST es más seguro para deletes)
    $user_id_to_delete = $_GET['id'] ?? null;
    $current_admin_id = $_SESSION['user_id'];

    if (empty($user_id_to_delete)) {
        header("Location: ../views/list_users.php?error=ID de usuario no especificado para eliminar.");
        exit();
    }

    // Prevenir que un admin se elimine a sí mismo
    if ($user_id_to_delete == $current_admin_id) {
        header("Location: ../views/list_users.php?error=No puedes eliminarte a ti mismo.");
        exit();
    }

    try {
        $stmt = $pdo->prepare("DELETE FROM `User` WHERE `Id` = :id");
        $stmt->execute(['id' => $user_id_to_delete]);

        header("Location: ../views/list_users.php?success=Usuario eliminado exitosamente.");
        exit();

    } catch (PDOException $e) {
        // error_log("Error en user_controller (DELETE): " . $e->getMessage());
        header("Location: ../views/list_users.php?error=Error del servidor al eliminar el usuario.");
        exit();
    }

} else {
    // Si se intenta acceder directamente sin POST o DELETE con ID, redirigir
    header("Location: ../views/list_users.php");
    exit();
}