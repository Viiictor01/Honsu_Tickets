<?php
session_start();
require_once '../config/db.php';

// Asegurar que solo usuarios admin puedan acceder a este controlador
if (!isset($_SESSION['user_id']) || (isset($_SESSION['user_role']) && $_SESSION['user_role'] !== 'admin')) {
    header("Location: ../views/login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $action = $_POST['action'] ?? ''; // 'create' o 'edit'
    $name = trim($_POST['name'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $area_id = $_POST['area_id'] ?? null; // Nuevo: AreaId
    $is_incoming_default = isset($_POST['is_incoming_default']) ? 1 : 0; // Nuevo: IsIncomingDefault

    // Convertir area_id vacío a NULL para la base de datos
    if (empty($area_id)) {
        $area_id = NULL;
    }

    if (empty($name)) {
        $redirect_page = "../views/dashboard.php";
        if ($action === 'create') {
            $redirect_page = "../views/create_dashboard.php";
        } elseif ($action === 'edit' && isset($_POST['id'])) {
            $redirect_page = "../views/edit_dashboard.php?id=" . $_POST['id'];
        }
        header("Location: " . $redirect_page . "&error=El nombre del tablero no puede estar vacío.");
        exit();
    }

    try {
        if ($action === 'create') {
            // Lógica para crear un nuevo dashboard
            $stmt = $pdo->prepare("INSERT INTO `Dashboard` (`Name`, `Description`, `UserId`, `AreaId`, `IsIncomingDefault`) VALUES (:name, :description, :user_id, :area_id, :is_incoming_default)");
            $stmt->execute([
                'name' => $name,
                'description' => $description,
                'user_id' => $user_id,
                'area_id' => $area_id,
                'is_incoming_default' => $is_incoming_default
            ]);
            header("Location: ../views/dashboard.php?success=Tablero creado exitosamente.");
            exit();

        } elseif ($action === 'edit') {
            // Lógica para editar un dashboard existente
            $id = $_POST['id'] ?? null;
            if (empty($id)) {
                header("Location: ../views/dashboard.php?error=ID de tablero no especificado para editar.");
                exit();
            }

            // Asegurarse de que el dashboard pertenezca al usuario logueado
            $stmt_check = $pdo->prepare("SELECT UserId FROM `Dashboard` WHERE Id = :id LIMIT 1");
            $stmt_check->execute(['id' => $id]);
            $owner_check = $stmt_check->fetch(PDO::FETCH_ASSOC);

            if (!$owner_check || $owner_check['UserId'] !== $user_id) {
                header("Location: ../views/dashboard.php?error=No tienes permiso para editar este tablero.");
                exit();
            }

            $stmt = $pdo->prepare("UPDATE `Dashboard` SET `Name` = :name, `Description` = :description, `AreaId` = :area_id, `IsIncomingDefault` = :is_incoming_default WHERE `Id` = :id");
            $stmt->execute([
                'name' => $name,
                'description' => $description,
                'area_id' => $area_id,
                'is_incoming_default' => $is_incoming_default,
                'id' => $id
            ]);
            header("Location: ../views/dashboard.php?success=Tablero actualizado exitosamente.");
            exit();

        } else {
            header("Location: ../views/dashboard.php?error=Acción no válida.");
            exit();
        }

    } catch (PDOException $e) {
        // En un entorno de producción, loggear el error: error_log("Error en dashboard_controller: " . $e->getMessage());
        $redirect_page = "../views/dashboard.php";
        if ($action === 'create') {
            $redirect_page = "../views/create_dashboard.php";
        } elseif ($action === 'edit' && isset($_POST['id'])) {
            $redirect_page = "../views/edit_dashboard.php?id=" . $_POST['id'];
        }
        header("Location: " . $redirect_page . "&error=Error del servidor al procesar el tablero: " . $e->getMessage()); // Para depurar
        exit();
    }

} elseif ($_SERVER["REQUEST_METHOD"] === "GET" && (isset($_GET['action']) && $_GET['action'] === 'delete')) {
    // Lógica para eliminar un dashboard (vía GET, aunque POST es más seguro para deletes)
    $id = $_GET['id'] ?? null;

    if (empty($id)) {
        header("Location: ../views/dashboard.php?error=ID de tablero no especificado para eliminar.");
        exit();
    }

    try {
        // Asegurarse de que el dashboard pertenezca al usuario logueado
        $stmt_check = $pdo->prepare("SELECT UserId FROM `Dashboard` WHERE Id = :id LIMIT 1");
        $stmt_check->execute(['id' => $id]);
        $owner_check = $stmt_check->fetch(PDO::FETCH_ASSOC);

        if (!$owner_check || $owner_check['UserId'] !== $user_id) {
            header("Location: ../views/dashboard.php?error=No tienes permiso para eliminar este tablero.");
            exit();
        }

        $stmt = $pdo->prepare("DELETE FROM `Dashboard` WHERE `Id` = :id");
        $stmt->execute(['id' => $id]);
        header("Location: ../views/dashboard.php?success=Tablero eliminado exitosamente.");
        exit();

    } catch (PDOException $e) {
        // error_log("Error en dashboard_controller (DELETE): " . $e->getMessage());
        header("Location: ../views/dashboard.php?error=Error del servidor al eliminar el tablero.");
        exit();
    }

} else {
    // Si se intenta acceder directamente sin POST o DELETE con ID, redirigir
    header("Location: ../views/dashboard.php");
    exit();
}