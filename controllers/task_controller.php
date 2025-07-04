<?php
session_start();
require_once '../config/db.php';

// Asegurar que solo usuarios admin puedan acceder a este controlador
if (!isset($_SESSION['user_id']) || (isset($_SESSION['user_role']) && $_SESSION['user_role'] !== 'admin')) {
    header("Location: ../views/login.php");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $action = $_POST['action'] ?? ''; // 'create' o 'edit'
    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $start_date = $_POST['start_date'] ?? null;
    $end_date = $_POST['end_date'] ?? null;
    $dashboard_id = $_POST['dashboard_id'] ?? null;
    $status = $_POST['status'] ?? 'pending'; // Nuevo: Status de la tarea

    // Convertir fechas vacías a NULL para la base de datos
    if (empty($start_date)) {
        $start_date = null;
    }
    if (empty($end_date)) {
        $end_date = null;
    }

    // Definir los estados permitidos para validación (¡importante!)
    $allowed_statuses = ['pending', 'in progress', 'completed', 'closed'];
    if (!in_array(strtolower($status), $allowed_statuses)) {
        $status = 'pending'; // Por defecto a 'pending' si el status es inválido
    }

    if (empty($title) || empty($dashboard_id)) {
        $redirect_page = "../views/view_dashboard.php?id=" . htmlspecialchars($dashboard_id);
        if ($action === 'create') {
            $redirect_page = "../views/create_task.php?dashboard_id=" . htmlspecialchars($dashboard_id);
        } elseif ($action === 'edit' && isset($_POST['id'])) {
            $redirect_page = "../views/edit_task.php?id=" . htmlspecialchars($_POST['id']) . "&dashboard_id=" . htmlspecialchars($dashboard_id);
        }
        header("Location: " . $redirect_page . "&error=El título de la tarea y el ID del tablero son obligatorios.");
        exit();
    }

    try {
        if ($action === 'create') {
            // Lógica para crear una nueva tarea
            $stmt = $pdo->prepare("INSERT INTO `Task` (`Title`, `Description`, `StartDate`, `EndDate`, `DashboardId`, `Status`) VALUES (:title, :description, :start_date, :end_date, :dashboard_id, :status)");
            $stmt->execute([
                'title' => $title,
                'description' => $description,
                'start_date' => $start_date,
                'end_date' => $end_date,
                'dashboard_id' => $dashboard_id,
                'status' => $status
            ]);
            header("Location: ../views/view_dashboard.php?id=" . htmlspecialchars($dashboard_id) . "&success=Tarea creada exitosamente.");
            exit();

        } elseif ($action === 'edit') {
            // Lógica para editar una tarea existente
            $id = $_POST['id'] ?? null;
            if (empty($id)) {
                header("Location: ../views/view_dashboard.php?id=" . htmlspecialchars($dashboard_id) . "&error=ID de tarea no especificado para editar.");
                exit();
            }

            // Asegurarse de que la tarea pertenezca al dashboard y el dashboard al usuario logueado
            $stmt_check = $pdo->prepare("SELECT t.Id FROM `Task` t JOIN `Dashboard` d ON t.DashboardId = d.Id WHERE t.Id = :task_id AND t.DashboardId = :dashboard_id AND d.UserId = :user_id LIMIT 1");
            $stmt_check->execute(['task_id' => $id, 'dashboard_id' => $dashboard_id, 'user_id' => $_SESSION['user_id']]);
            $task_owner_check = $stmt_check->fetch(PDO::FETCH_ASSOC);

            if (!$task_owner_check) {
                header("Location: ../views/view_dashboard.php?id=" . htmlspecialchars($dashboard_id) . "&error=No tienes permiso para editar esta tarea o no existe.");
                exit();
            }

            $stmt = $pdo->prepare("UPDATE `Task` SET `Title` = :title, `Description` = :description, `StartDate` = :start_date, `EndDate` = :end_date, `Status` = :status WHERE `Id` = :id AND `DashboardId` = :dashboard_id");
            $stmt->execute([
                'title' => $title,
                'description' => $description,
                'start_date' => $start_date,
                'end_date' => $end_date,
                'status' => $status, 
                'id' => $id,
                'dashboard_id' => $dashboard_id
            ]);
            header("Location: ../views/view_dashboard.php?id=" . htmlspecialchars($dashboard_id) . "&success=Tarea actualizada exitosamente.");
            exit();

        } else {
            header("Location: ../views/dashboard.php?error=Acción no válida.");
            exit();
        }

    } catch (PDOException $e) {
        // En un entorno de producción, loggear el error: error_log("Error en task_controller: " . $e->getMessage());
        $redirect_page = "../views/view_dashboard.php?id=" . htmlspecialchars($dashboard_id);
        if ($action === 'create') {
            $redirect_page = "../views/create_task.php?dashboard_id=" . htmlspecialchars($dashboard_id);
        } elseif ($action === 'edit' && isset($_POST['id'])) {
            $redirect_page = "../views/edit_task.php?id=" . htmlspecialchars($_POST['id']) . "&dashboard_id=" . htmlspecialchars($dashboard_id);
        }
        header("Location: " . $redirect_page . "&error=Error del servidor al procesar la tarea: " . $e->getMessage());
        exit();
    }

} elseif ($_SERVER["REQUEST_METHOD"] === "GET" && (isset($_GET['action']) && $_GET['action'] === 'delete')) {
    // Lógica para eliminar una tarea (vía GET)
    $id = $_GET['id'] ?? null;
    $dashboard_id = $_GET['dashboard_id'] ?? null; 

    if (empty($id) || empty($dashboard_id)) {
        header("Location: ../views/dashboard.php?error=ID de tarea o tablero no especificado para eliminar.");
        exit();
    }

    try {
        // Asegurarse de que la tarea pertenezca al dashboard y el dashboard al usuario logueado
        $stmt_check = $pdo->prepare("SELECT t.Id FROM `Task` t JOIN `Dashboard` d ON t.DashboardId = d.Id WHERE t.Id = :task_id AND t.DashboardId = :dashboard_id AND d.UserId = :user_id LIMIT 1");
        $stmt_check->execute(['task_id' => $id, 'dashboard_id' => $dashboard_id, 'user_id' => $_SESSION['user_id']]);
        $task_owner_check = $stmt_check->fetch(PDO::FETCH_ASSOC);

        if (!$task_owner_check) {
            header("Location: ../views/view_dashboard.php?id=" . htmlspecialchars($dashboard_id) . "&error=No tienes permiso para eliminar esta tarea o no existe.");
            exit();
        }

        $stmt = $pdo->prepare("DELETE FROM `Task` WHERE `Id` = :id AND `DashboardId` = :dashboard_id");
        $stmt->execute(['id' => $id, 'dashboard_id' => $dashboard_id]);
        header("Location: ../views/view_dashboard.php?id=" . htmlspecialchars($dashboard_id) . "&success=Tarea eliminada exitosamente.");
        exit();

    } catch (PDOException $e) {
        // error_log("Error en task_controller (DELETE): " . $e->getMessage());
        header("Location: ../views/view_dashboard.php?id=" . htmlspecialchars($dashboard_id) . "&error=Error del servidor al eliminar la tarea.");
        exit();
    }

} else {
    // Si se intenta acceder directamente sin POST o DELETE con ID, redirigir
    header("Location: ../views/dashboard.php");
    exit();
}