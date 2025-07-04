<?php
session_start();
require_once '../config/db.php'; // Asegúrate de incluir la conexión

// Asegúrate de que solo los usuarios logueados (y preferiblemente con el rol 'user') puedan acceder
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Opcional: Si quieres redirigir a los admins al dashboard si intentan acceder aquí directamente.
if (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin') {
    header("Location: dashboard.php");
    exit();
}

$areas = [];
try {
    $stmt_areas = $pdo->query("SELECT Id, Name FROM `Area` ORDER BY Name ASC");
    $areas = $stmt_areas->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    // Manejar error de carga de áreas si es necesario
    $error_message_areas = "Error al cargar las áreas disponibles: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Solicitar Tickets - Honsu Tickets</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 min-h-screen">

<div class="container mx-auto px-4 py-8">
    <h1 class="text-4xl font-bold text-gray-800 mb-6">Solicitud de Tickets</h1>

    <div class="bg-blue-100 border border-blue-400 text-blue-700 px-4 py-3 rounded relative mb-6" role="alert">
        Bienvenido, <strong class="font-bold"><?php echo htmlspecialchars($_SESSION['username']); ?></strong>. Por favor, describe tu problema o solicitud a continuación.
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
    <?php if (isset($error_message_areas)): ?>
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert">
            <?php echo $error_message_areas; ?>
        </div>
    <?php endif; ?>

    <div class="bg-white shadow-md rounded-lg p-6 mb-8">
        <h2 class="text-2xl font-semibold text-gray-700 mb-4">Enviar Nuevo Ticket</h2>
        <form action="../controllers/user_ticket_controller.php" method="POST">
            <div class="mb-4">
                <label for="ticket_subject" class="block text-gray-700 text-sm font-bold mb-2">Asunto del Ticket:</label>
                <input type="text" id="ticket_subject" name="ticket_subject" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" required>
            </div>
            <div class="mb-4">
                <label for="ticket_description" class="block text-gray-700 text-sm font-bold mb-2">Descripción Detallada:</label>
                <textarea id="ticket_description" name="ticket_description" rows="5" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" required></textarea>
            </div>
            
            <div class="mb-6">
                <label for="area_id" class="block text-gray-700 text-sm font-bold mb-2">Selecciona el Área de tu Solicitud:</label>
                <select id="area_id" name="area_id" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" required>
                    <option value="">-- Selecciona un Área --</option>
                    <?php foreach ($areas as $area): ?>
                        <option value="<?php echo htmlspecialchars($area['Id']); ?>"><?php echo htmlspecialchars($area['Name']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <button type="submit" class="bg-indigo-500 hover:bg-indigo-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline">Enviar Solicitud</button>
        </form>
    </div>

    <div class="flex space-x-4">
        <a href="logout.php" class="bg-red-500 hover:bg-red-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline">Cerrar sesión</a>
    </div>
</div>

</body>
</html>