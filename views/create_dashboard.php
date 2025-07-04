<?php
session_start();
require_once '../config/db.php';

// Asegurar que solo usuarios admin puedan acceder
if (!isset($_SESSION['user_id']) || (isset($_SESSION['user_role']) && $_SESSION['user_role'] !== 'admin')) {
    header("Location: login.php");
    exit();
}

$areas = [];
try {
    $stmt_areas = $pdo->query("SELECT Id, Name FROM `Area` ORDER BY Name ASC");
    $areas = $stmt_areas->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    // Manejar error de carga de áreas
    $error_message = "Error al cargar las áreas: " . $e->getMessage();
}

?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Crear Nuevo Tablero - Honsu Tickets</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 min-h-screen flex items-center justify-center">

<div class="w-full max-w-lg bg-white shadow-lg rounded-lg p-8">
    <h1 class="text-3xl font-bold text-gray-800 mb-6 text-center">Crear Nuevo Tablero</h1>

    <form action="../controllers/dashboard_controller.php" method="POST">
        <input type="hidden" name="action" value="create">

        <div class="mb-4">
            <label for="name" class="block text-gray-700 text-sm font-bold mb-2">Nombre del Tablero:</label>
            <input type="text" id="name" name="name" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" required>
        </div>

        <div class="mb-4">
            <label for="description" class="block text-gray-700 text-sm font-bold mb-2">Descripción (Opcional):</label>
            <textarea id="description" name="description" rows="4" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline"></textarea>
        </div>

        <div class="mb-4">
            <label for="area_id" class="block text-gray-700 text-sm font-bold mb-2">Asignar a Área:</label>
            <select id="area_id" name="area_id" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                <option value="">-- Seleccione un Área (Opcional) --</option>
                <?php foreach ($areas as $area): ?>
                    <option value="<?php echo htmlspecialchars($area['Id']); ?>"><?php echo htmlspecialchars($area['Name']); ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="mb-6 flex items-center">
            <input type="checkbox" id="is_incoming_default" name="is_incoming_default" value="1" class="form-checkbox h-5 w-5 text-indigo-600">
            <label for="is_incoming_default" class="ml-2 text-gray-700 text-sm font-bold">Marcar como tablero de entrada predeterminado para esta área</label>
        </div>

        <?php if (isset($_GET['error'])): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert">
                <?php echo htmlspecialchars($_GET['error']); ?>
            </div>
        <?php endif; ?>

        <div class="flex items-center justify-between">
            <button type="submit" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline">Crear Tablero</button>
            <a href="dashboard.php" class="bg-gray-300 hover:bg-gray-400 text-gray-800 font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline">Cancelar</a>
        </div>
    </form>
</div>

</body>
</html>