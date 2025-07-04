<?php
session_start();
require_once '../config/db.php';

// Asegurar que solo usuarios admin puedan acceder a esta vista
if (!isset($_SESSION['user_id']) || (isset($_SESSION['user_role']) && $_SESSION['user_role'] !== 'admin')) {
    header("Location: login.php");
    exit();
}

$dashboard_id = $_GET['dashboard_id'] ?? null;
$dashboard_name = '';

if (!$dashboard_id) {
    header("Location: dashboard.php?error=ID de tablero no especificado para crear tarea.");
    exit();
}

try {
    // Verificar que el dashboard existe y pertenece al usuario logueado
    $stmt = $pdo->prepare("SELECT Name, UserId FROM `Dashboard` WHERE Id = :id LIMIT 1");
    $stmt->execute(['id' => $dashboard_id]);
    $dashboard_info = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$dashboard_info || $dashboard_info['UserId'] !== $_SESSION['user_id']) {
        header("Location: dashboard.php?error=Tablero no encontrado o no tienes permiso para añadir tareas aquí.");
        exit();
    }
    $dashboard_name = $dashboard_info['Name'];

} catch (PDOException $e) {
    // error_log("Error al cargar info de dashboard: " . $e->getMessage());
    header("Location: dashboard.php?error=Error del servidor al cargar información del tablero.");
    exit();
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Crear Tarea - Honsu Tickets</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 min-h-screen flex items-center justify-center">

<div class="w-full max-w-lg bg-white shadow-lg rounded-lg p-8">
    <h1 class="text-3xl font-bold text-gray-800 mb-6 text-center">Crear Tarea para "<?php echo htmlspecialchars($dashboard_name); ?>"</h1>

    <form action="../controllers/task_controller.php" method="POST">
        <input type="hidden" name="action" value="create">
        <input type="hidden" name="dashboard_id" value="<?php echo htmlspecialchars($dashboard_id); ?>">

        <div class="mb-4">
            <label for="title" class="block text-gray-700 text-sm font-bold mb-2">Título de la Tarea:</label>
            <input type="text" id="title" name="title" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" required>
        </div>

        <div class="mb-4">
            <label for="description" class="block text-gray-700 text-sm font-bold mb-2">Descripción (Opcional):</label>
            <textarea id="description" name="description" rows="4" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline"></textarea>
        </div>

        <div class="mb-4">
            <label for="start_date" class="block text-gray-700 text-sm font-bold mb-2">Fecha y Hora de Inicio:</label>
            <input type="datetime-local" id="start_date" name="start_date" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" required>
        </div>

        <div class="mb-6">
            <label for="end_date" class="block text-gray-700 text-sm font-bold mb-2">Fecha y Hora de Fin:</label>
            <input type="datetime-local" id="end_date" name="end_date" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" required>
        </div>

        <?php if (isset($_GET['error'])): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert">
                <?php echo htmlspecialchars($_GET['error']); ?>
            </div>
        <?php endif; ?>

        <div class="flex items-center justify-between">
            <button type="submit" class="bg-green-500 hover:bg-green-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline">Crear Tarea</button>
            <a href="view_dashboard.php?id=<?php echo htmlspecialchars($dashboard_id); ?>" class="bg-gray-300 hover:bg-gray-400 text-gray-800 font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline">Cancelar</a>
        </div>
    </form>
</div>

</body>
</html>