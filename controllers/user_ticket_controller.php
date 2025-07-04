<?php
session_start();
require_once '../config/db.php';

// Asegurar que solo usuarios logueados (comunes o admin) puedan acceder a este controlador
if (!isset($_SESSION['user_id'])) {
    header("Location: ../views/login.php");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $title = trim($_POST['ticket_subject'] ?? '');
    $description = trim($_POST['ticket_description'] ?? '');
    $area_id = $_POST['area_id'] ?? null; // El AreaId seleccionado por el usuario común
    $user_id_requesting = $_SESSION['user_id']; // ID del usuario que solicita el ticket

    if (empty($title) || empty($description) || empty($area_id)) {
        header("Location: ../views/user_tickets.php?error=Por favor completa el asunto, la descripción y selecciona un área para tu ticket.");
        exit();
    }

    try {
        // 1. Encontrar el DashboardId de entrada predeterminado para el AreaId seleccionado
        $stmt_dashboard = $pdo->prepare("SELECT Id FROM `Dashboard` WHERE AreaId = :area_id AND IsIncomingDefault = TRUE LIMIT 1");
        $stmt_dashboard->execute(['area_id' => $area_id]);
        $target_dashboard = $stmt_dashboard->fetch(PDO::FETCH_ASSOC);

        if (!$target_dashboard) {
            header("Location: ../views/user_tickets.php?error=No se encontró un tablero de entrada disponible para el área seleccionada. Por favor, intenta de nuevo o contacta al soporte.");
            exit();
        }

        $dashboard_id = $target_dashboard['Id'];
        $current_datetime = date('Y-m-d H:i:s');

        // 2. Insertar el ticket en la tabla Task, asignándolo al tablero encontrado
        // AHORA INCLUYE CreatedByUserId
        $stmt = $pdo->prepare("INSERT INTO `Task` (`Title`, `Description`, `StartDate`, `EndDate`, `DashboardId`, `Status`, `CreatedByUserId`) VALUES (:title, :description, :start_date, :end_date, :dashboard_id, :status, :created_by_user_id)");
        $stmt->execute([
            'title' => $title,
            'description' => $description,
            'start_date' => $current_datetime,
            'end_date' => NULL,
            'dashboard_id' => $dashboard_id,
            'status' => 'pending',
            'created_by_user_id' => $user_id_requesting // Guarda el ID del usuario que la envió
        ]);

        header("Location: ../views/user_tickets.php?success=Tu ticket ha sido enviado exitosamente al área correspondiente. Pronto un administrador lo revisará.");
        exit();

    } catch (PDOException $e) {
        // error_log("Error al enviar ticket de usuario: " . $e->getMessage());
        header("Location: ../views/user_tickets.php?error=Error del servidor al enviar tu ticket: " . $e->getMessage());
        exit();
    }
} else {
    header("Location: ../views/user_tickets.php");
    exit();
}