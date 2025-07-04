<?php
session_start();
require_once '../config/db.php';

// Redirigir al login si el usuario no está logueado o no es admin
if (!isset($_SESSION['user_id']) || (isset($_SESSION['user_role']) && $_SESSION['user_role'] !== 'admin')) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$user_area_id = $_SESSION['user_area_id'] ?? null; // Obtener el AreaId del admin logueado
$dashboards = [];
$error_message = '';

try {
    // Carga de Tableros
    // Si el admin tiene un AreaId asignado, solo mostrar dashboards de esa área o sin área específica
    if ($user_area_id !== null) {
        $stmt = $pdo->prepare("SELECT Id, Name, Description, AreaId FROM `Dashboard` WHERE UserId = :user_id AND (AreaId = :area_id OR AreaId IS NULL) ORDER BY Id DESC");
        $stmt->execute(['user_id' => $user_id, 'area_id' => $user_area_id]);
    } else {
        // Si el admin no tiene área asignada (e.g., un super-admin), se le muestran todos los dashboards que le pertenecen
        $stmt = $pdo->prepare("SELECT Id, Name, Description, AreaId FROM `Dashboard` WHERE UserId = :user_id ORDER BY Id DESC");
        $stmt->execute(['user_id' => $user_id]);
    }
    
    $dashboards = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    $error_message = "Error al cargar los tableros: " . $e->getMessage();
}

// Opcional: Obtener los nombres de las áreas para mostrarlas
$areas = [];
try {
    $stmt_areas = $pdo->query("SELECT Id, Name FROM `Area`");
    while ($row = $stmt_areas->fetch(PDO::FETCH_ASSOC)) {
        $areas[$row['Id']] = $row['Name'];
    }
} catch (PDOException $e) {
    // Manejar error de carga de áreas si es necesario
}

?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Administrar Tableros - Honsu Tickets</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 min-h-screen">

<div class="container mx-auto px-4 py-8">
    <h1 class="text-4xl font-bold text-gray-800 mb-6">Administrar Mis Tableros</h1>

    <div class="flex flex-wrap items-start justify-start gap-4 mb-8">
        <a href="dashboard.php" class="bg-gray-500 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline">Volver al Dashboard Principal</a>
        <a href="create_dashboard.php" class="bg-purple-500 hover:bg-purple-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline">Crear Nuevo Tablero</a>
        <a href="logout.php" class="bg-red-500 hover:bg-red-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline">Cerrar sesión</a>
    </div>

    <?php if (isset($_GET['success'])): ?>
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4" role="alert">
            <?php echo htmlspecialchars($_GET['success']); ?>
        </div>
    <?php endif; ?>
    <?php if (isset($_GET['error'])): ?>
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert">
            <?php echo htmlspecialchars($_GET['error']); ?>
        </div>
    <?php endif; ?>

    <?php if (isset($error_message) && !empty($error_message)): ?>
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert">
            <?php echo $error_message; ?>
        </div>
    <?php elseif (empty($dashboards)): ?>
        <div class="bg-yellow-100 border border-yellow-400 text-yellow-700 px-4 py-3 rounded relative mb-4" role="alert">
            No tienes tableros asignados. ¡Crea uno nuevo!
        </div>
    <?php else: ?>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <?php foreach ($dashboards as $dashboard): ?>
                <div class="bg-white shadow-lg rounded-lg p-6 hover:shadow-xl transition-shadow duration-300">
                    <h3 class="text-xl font-bold text-gray-900 mb-2"><?php echo htmlspecialchars($dashboard['Name']); ?></h3>
                    <p class="text-gray-600 text-sm mb-4"><?php echo htmlspecialchars($dashboard['Description']); ?></p>
                    <?php if ($dashboard['AreaId'] && isset($areas[$dashboard['AreaId']])): ?>
                        <p class="text-gray-500 text-xs mb-4">Área: <span class="font-semibold"><?php echo htmlspecialchars($areas[$dashboard['AreaId']]); ?></span></p>
                    <?php endif; ?>
                    <div class="flex justify-end">
                        <a href="edit_dashboard.php?id=<?php echo htmlspecialchars($dashboard['Id']); ?>" class="bg-indigo-500 hover:bg-indigo-700 text-white text-sm font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline mr-2">Editar</a>
                        <a href="view_dashboard.php?id=<?php echo htmlspecialchars($dashboard['Id']); ?>" class="bg-green-500 hover:bg-green-700 text-white text-sm font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline">Ver Tablero</a>
                        <a href="../controllers/dashboard_controller.php?action=delete&id=<?php echo htmlspecialchars($dashboard['Id']); ?>" onclick="return confirm('¿Estás seguro de que quieres eliminar este tablero? Todas las tareas asociadas se eliminarán permanentemente.');" class="bg-red-500 hover:bg-red-700 text-white text-sm font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline ml-2">Eliminar</a>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

</div>

</body>
</html>