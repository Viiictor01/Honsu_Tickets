<?php
session_start();
require_once '../config/db.php';

// Asegurar que solo usuarios admin puedan acceder
if (!isset($_SESSION['user_id']) || (isset($_SESSION['user_role']) && $_SESSION['user_role'] !== 'admin')) {
    header("Location: login.php");
    exit();
}

$dashboard_id = $_GET['id'] ?? null;
$dashboard = null;
$error_message = '';
$areas = [];

if (!$dashboard_id) {
    header("Location: dashboard.php?error=ID de tablero no especificado.");
    exit();
}

try {
    // Obtener los datos del dashboard a editar
    $stmt_dashboard = $pdo->prepare("SELECT Id, Name, Description, UserId, AreaId, IsIncomingDefault FROM `Dashboard` WHERE Id = :id LIMIT 1");
    $stmt_dashboard->execute(['id' => $dashboard_id]);
    $dashboard = $stmt_dashboard->fetch(PDO::FETCH_ASSOC);

    // Verificar que el dashboard exista y pertenezca al usuario logueado
    if (!$dashboard || $dashboard['UserId'] !== $_SESSION['user_id']) {
        header("Location: dashboard.php?error=Tablero no encontrado o no tienes permiso para editarlo.");
        exit();
    }

    // Cargar todas las áreas disponibles
    $stmt_areas = $pdo->query("SELECT Id, Name FROM `Area` ORDER BY Name ASC");
    $areas = $stmt_areas->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    $error_message = "Error al cargar el tablero o las áreas: " . $e->getMessage();
}

?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Editar Tablero: <?php echo htmlspecialchars($dashboard['Name'] ?? 'Cargando...'); ?> - Honsu Tickets</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 min-h-screen flex items-center justify-center">

<div class="w-full max-w-lg bg-white shadow-lg rounded-lg p-8">
    <h1 class="text-3xl font-bold text-gray-800 mb-6 text-center">Editar Tablero</h1>

    <?php if ($dashboard): ?>
        <form action="../controllers/dashboard_controller.php" method="POST">
            <input type="hidden" name="action" value="edit">
            <input type="hidden" name="id" value="<?php echo htmlspecialchars($dashboard['Id']); ?>">

            <div class="mb-4">
                <label for="name" class="block text-gray-700 text-sm font-bold mb-2">Nombre del Tablero:</label>
                <input type="text" id="name" name="name" value="<?php echo htmlspecialchars($dashboard['Name']); ?>" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" required>
            </div>

            <div class="mb-4">
                <label for="description" class="block text-gray-700 text-sm font-bold mb-2">Descripción (Opcional):</label>
                <textarea id="description" name="description" rows="4" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline"><?php echo htmlspecialchars($dashboard['Description']); ?></textarea>
            </div>

            <div class="mb-4">
                <label for="area_id" class="block text-gray-700 text-sm font-bold mb-2">Asignar a Área:</label>
                <select id="area_id" name="area_id" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                    <option value="">-- Seleccione un Área (Opcional) --</option>
                    <?php foreach ($areas as $area): ?>
                        <option value="<?php echo htmlspecialchars($area['Id']); ?>" <?php echo ($dashboard['AreaId'] == $area['Id']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($area['Name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="mb-6 flex items-center">
                <input type="checkbox" id="is_incoming_default" name="is_incoming_default" value="1" class="form-checkbox h-5 w-5 text-indigo-600" <?php echo ($dashboard['IsIncomingDefault'] == 1) ? 'checked' : ''; ?>>
                <label for="is_incoming_default" class="ml-2 text-gray-700 text-sm font-bold">Marcar como tablero de entrada predeterminado para esta área</label>
            </div>

            <?php if (isset($_GET['error'])): ?>
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert">
                    <?php echo htmlspecialchars($_GET['error']); ?>
                </div>
            <?php endif; ?>

            <div class="flex items-center justify-between">
                <button type="submit" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline">Guardar Cambios</button>
                <a href="dashboard.php" class="bg-gray-300 hover:bg-gray-400 text-gray-800 font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline">Cancelar</a>
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