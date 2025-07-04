<?php
session_start();
require_once '../config/db.php';

// Asegurar que solo usuarios admin puedan acceder
if (!isset($_SESSION['user_id']) || (isset($_SESSION['user_role']) && $_SESSION['user_role'] !== 'admin')) {
    header("Location: login.php");
    exit();
}

$task_id = $_GET['id'] ?? null;
$dashboard_id = $_GET['dashboard_id'] ?? null; // Obtener el dashboard_id de la URL
$task = null;
$error_message = '';

// Definir los estados permitidos
$allowed_statuses = ['pending', 'in progress', 'completed', 'closed'];

if (!$task_id || !$dashboard_id) {
    header("Location: dashboard.php?error=ID de tarea o tablero no especificado para editar.");
    exit();
}

try {
    // Obtener los datos de la tarea a editar
    $stmt = $pdo->prepare("SELECT Id, Title, Description, StartDate, EndDate, Status, DashboardId FROM `Task` WHERE Id = :id AND DashboardId = :dashboard_id LIMIT 1");
    $stmt->execute(['id' => $task_id, 'dashboard_id' => $dashboard_id]);
    $task = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$task) {
        header("Location: view_dashboard.php?id=" . htmlspecialchars($dashboard_id) . "&error=Tarea no encontrada en este tablero.");
        exit();
    }

} catch (PDOException $e) {
    $error_message = "Error al cargar la tarea: " . $e->getMessage();
}

?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Editar Tarea: <?php echo htmlspecialchars($task['Title'] ?? 'Cargando...'); ?> - Honsu Tickets</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 min-h-screen flex items-center justify-center">

<div class="w-full max-w-lg bg-white shadow-lg rounded-lg p-8">
    <h1 class="text-3xl font-bold text-gray-800 mb-6 text-center">Editar Tarea</h1>

    <?php if ($task): ?>
        <form action="../controllers/task_controller.php" method="POST">
            <input type="hidden" name="action" value="edit">
            <input type="hidden" name="id" value="<?php echo htmlspecialchars($task['Id']); ?>">
            <input type="hidden" name="dashboard_id" value="<?php echo htmlspecialchars($dashboard_id); ?>">

            <div class="mb-4">
                <label for="title" class="block text-gray-700 text-sm font-bold mb-2">Título de la Tarea:</label>
                <input type="text" id="title" name="title" value="<?php echo htmlspecialchars($task['Title']); ?>" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" required>
            </div>

            <div class="mb-4">
                <label for="description" class="block text-gray-700 text-sm font-bold mb-2">Descripción:</label>
                <textarea id="description" name="description" rows="5" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline"><?php echo htmlspecialchars($task['Description']); ?></textarea>
            </div>

            <div class="mb-4">
                <label for="start_date" class="block text-gray-700 text-sm font-bold mb-2">Fecha de Inicio:</label>
                <input type="datetime-local" id="start_date" name="start_date" value="<?php echo htmlspecialchars(date('Y-m-d\TH:i', strtotime($task['StartDate']))); ?>" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
            </div>

            <div class="mb-4">
                <label for="end_date" class="block text-gray-700 text-sm font-bold mb-2">Fecha de Finalización (Opcional):</label>
                <input type="datetime-local" id="end_date" name="end_date" value="<?php echo ($task['EndDate'] ? htmlspecialchars(date('Y-m-d\TH:i', strtotime($task['EndDate']))) : ''); ?>" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
            </div>

            <div class="mb-6">
                <label for="status" class="block text-gray-700 text-sm font-bold mb-2">Estado:</label>
                <select id="status" name="status" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" required>
                    <?php foreach ($allowed_statuses as $status_option): ?>
                        <option value="<?php echo htmlspecialchars($status_option); ?>" <?php echo (strtolower($task['Status']) === $status_option) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars(ucfirst($status_option)); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <?php if (isset($_GET['error'])): ?>
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert">
                    <?php echo htmlspecialchars($_GET['error']); ?>
                </div>
            <?php endif; ?>

            <div class="flex items-center justify-between">
                <button type="submit" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline">Guardar Cambios</button>
                <a href="view_dashboard.php?id=<?php echo htmlspecialchars($dashboard_id); ?>" class="bg-gray-300 hover:bg-gray-400 text-gray-800 font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline">Cancelar</a>
            </div>
        </form>
    <?php else: ?>
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert">
            <?php echo $error_message; ?>
        </div>
    <?php endif; ?>
</div>

</body>
</html>