<?php
session_start();
require_once '../config/db.php';

// Asegurar que solo usuarios admin puedan acceder a esta vista
if (!isset($_SESSION['user_id']) || (isset($_SESSION['user_role']) && $_SESSION['user_role'] !== 'admin')) {
    header("Location: login.php");
    exit();
}

$user_id_to_edit = $_GET['id'] ?? null;
$user = null;

if (!$user_id_to_edit) {
    header("Location: list_users.php?error=ID de usuario no especificado para editar.");
    exit();
}

try {
    // Obtener los datos del usuario a editar
    $stmt = $pdo->prepare("SELECT Id, Username, Email, Role FROM `User` WHERE Id = :id LIMIT 1");
    $stmt->execute(['id' => $user_id_to_edit]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        header("Location: list_users.php?error=Usuario no encontrado.");
        exit();
    }

} catch (PDOException $e) {
    // error_log("Error al cargar usuario para edición: " . $e->getMessage());
    header("Location: list_users.php?error=Error del servidor al cargar el usuario.");
    exit();
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Editar Usuario: <?php echo htmlspecialchars($user['Username'] ?? 'Cargando...'); ?> - Honsu Tickets</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 min-h-screen flex items-center justify-center">

<div class="w-full max-w-lg bg-white shadow-lg rounded-lg p-8">
    <h1 class="text-3xl font-bold text-gray-800 mb-6 text-center">Editar Usuario: <?php echo htmlspecialchars($user['Username']); ?></h1>

    <form action="../controllers/user_controller.php" method="POST">
        <input type="hidden" name="action" value="edit">
        <input type="hidden" name="user_id" value="<?php echo htmlspecialchars($user['Id']); ?>">

        <div class="mb-4">
            <label for="username" class="block text-gray-700 text-sm font-bold mb-2">Usuario:</label>
            <input type="text" id="username" name="username" value="<?php echo htmlspecialchars($user['Username']); ?>" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" required>
        </div>

        <div class="mb-4">
            <label for="email" class="block text-gray-700 text-sm font-bold mb-2">Correo Electrónico:</label>
            <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($user['Email']); ?>" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" required>
        </div>

        <div class="mb-4">
            <label for="role" class="block text-gray-700 text-sm font-bold mb-2">Rol:</label>
            <select id="role" name="role" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" <?php echo ($user['Id'] == $_SESSION['user_id']) ? 'disabled' : ''; ?>>
                <option value="admin" <?php echo ($user['Role'] === 'admin') ? 'selected' : ''; ?>>Admin</option>
                <option value="user" <?php echo ($user['Role'] === 'user') ? 'selected' : ''; ?>>Usuario Común</option>
            </select>
            <?php if ($user['Id'] == $_SESSION['user_id']): ?>
                <p class="text-xs text-gray-600 mt-1">No puedes cambiar tu propio rol.</p>
                <input type="hidden" name="role" value="<?php echo htmlspecialchars($user['Role']); ?>">
            <?php endif; ?>
        </div>

        <div class="mb-6">
            <label for="password" class="block text-gray-700 text-sm font-bold mb-2">Nueva Contraseña (Dejar vacío para no cambiar):</label>
            <input type="password" id="password" name="password" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
            <p class="text-xs text-gray-600 mt-1">Mínimo 6 caracteres si se cambia.</p>
        </div>

        <?php if (isset($_GET['error'])): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert">
                <?php echo htmlspecialchars($_GET['error']); ?>
            </div>
        <?php endif; ?>

        <div class="flex items-center justify-between">
            <button type="submit" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline">Guardar Cambios</button>
            <a href="list_users.php" class="bg-gray-300 hover:bg-gray-400 text-gray-800 font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline">Cancelar</a>
        </div>
    </form>
</div>

</body>
</html>