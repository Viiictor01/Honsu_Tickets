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
$recent_tasks = [];
$error_message = '';

try {
    // --- Sección: Carga de Tableros (como ya lo tenías) ---
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

    // --- Sección: Carga de Tareas Recientes (NUEVA) ---
    // Seleccionar las tareas más recientes, incluyendo el nombre del usuario que la envió
    // y el nombre del tablero al que pertenece.
    // Consideraremos las tareas del propio usuario o las asociadas a sus tableros gestionados.
    // Para simplificar, mostraremos las X tareas más recientes en general.
    // Si quieres filtrarlas por el área del admin, avísame.
    $stmt_recent_tasks = $pdo->prepare("
        SELECT
            t.Id,
            t.Title,
            t.Description,
            t.StartDate,
            t.Status,
            u.Username AS CreatorUsername,
            d.Name AS DashboardName
        FROM `Task` t
        LEFT JOIN `User` u ON t.CreatedByUserId = u.Id
        JOIN `Dashboard` d ON t.DashboardId = d.Id
        WHERE d.UserId = :current_admin_user_id -- Mostrar solo tareas de tableros que el admin maneja
        ORDER BY t.StartDate DESC
        LIMIT 10 -- Limitar a las 10 tareas más recientes, puedes cambiarlo
    ");
    $stmt_recent_tasks->execute(['current_admin_user_id' => $user_id]);
    $recent_tasks = $stmt_recent_tasks->fetchAll(PDO::FETCH_ASSOC);


} catch (PDOException $e) {
    $error_message = "Error al cargar los datos: " . $e->getMessage();
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
    <title>Bienvenido - Honsu Tickets</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        .status-pill {
            padding: 0.25rem 0.75rem;
            border-radius: 9999px;
            font-size: 0.75rem;
            font-weight: bold;
            display: inline-block;
            text-transform: capitalize;
        }
        .status-pending { background-color: #fbd38d; color: #9c4221; } /* Amarillo/Naranja */
        .status-in_progress { background-color: #a7f3d0; color: #065f46; } /* Verde claro */
        .status-completed { background-color: #bfdbfe; color: #1e40af; } /* Azul claro */
        .status-closed { background-color: #fecaca; color: #991b1b; } /* Rojo claro */
    </style>
</head>
<body class="bg-gray-100 min-h-screen">

<div class="container mx-auto px-4 py-8">
    <h1 class="text-4xl font-bold text-gray-800 mb-6">Dashboard de Administración</h1>

    <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-6" role="alert">
        Bienvenido, <strong class="font-bold"><?php echo htmlspecialchars($_SESSION['username']); ?></strong> 👋 (Rol: <?php echo htmlspecialchars($_SESSION['user_role']); ?>)
        <?php if ($user_area_id && isset($areas[$user_area_id])): ?>
            <span class="ml-2">(Área: <?php echo htmlspecialchars($areas[$user_area_id]); ?>)</span>
        <?php endif; ?>
    </div>

    <div class="flex flex-wrap items-start justify-start gap-4 mb-8">
        <a href="logout.php" class="bg-red-500 hover:bg-red-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline">Cerrar sesión</a>
        <a href="list_users.php" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline">Ver lista de usuarios</a>
        <a href="create_dashboard.php" class="bg-purple-500 hover:bg-purple-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline">Crear Nuevo Tablero</a>
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
    <?php endif; ?>

    <h2 class="text-3xl font-semibold text-gray-800 mb-4">Tareas Recientes</h2>
    <?php if (empty($recent_tasks)): ?>
        <div class="bg-yellow-100 border border-yellow-400 text-yellow-700 px-4 py-3 rounded relative mb-6" role="alert">
            No hay tareas recientes para mostrar en tus tableros.
        </div>
    <?php else: ?>
        <div class="bg-white shadow-lg rounded-lg p-6 mb-8 overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Título</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Descripción</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Creada Por</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tablero</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Fecha Creación</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Estado</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Acciones</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php foreach ($recent_tasks as $task): ?>
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900"><?php echo htmlspecialchars($task['Title']); ?></td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 max-w-xs overflow-hidden text-ellipsis"><?php echo htmlspecialchars($task['Description']); ?></td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo htmlspecialchars($task['CreatorUsername'] ?? 'Administrador'); ?></td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo htmlspecialchars($task['DashboardName']); ?></td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo htmlspecialchars(date('d/m/Y H:i', strtotime($task['StartDate']))); ?></td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm">
                                <span class="status-pill status-<?php echo htmlspecialchars(strtolower(str_replace(' ', '_', $task['Status']))); ?>">
                                    <?php echo htmlspecialchars($task['Status']); ?>
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                <a href="edit_task.php?id=<?php echo htmlspecialchars($task['Id']); ?>&dashboard_id=<?php echo htmlspecialchars($task['DashboardId']); ?>" class="text-indigo-600 hover:text-indigo-900 mr-2">Editar</a>
                                <a href="../controllers/task_controller.php?action=delete&id=<?php echo htmlspecialchars($task['Id']); ?>&dashboard_id=<?php echo htmlspecialchars($task['DashboardId']); ?>" onclick="return confirm('¿Estás seguro de que quieres eliminar esta tarea?');" class="text-red-600 hover:text-red-900">Eliminar</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>

    <h2 class="text-3xl font-semibold text-gray-800 mb-4">Mis Tableros (Dashboards)</h2>

    <?php if (empty($dashboards)): ?>
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
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

</div>

</body>
</html>