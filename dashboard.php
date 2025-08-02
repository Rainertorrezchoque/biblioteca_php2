<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Biblioteca</title>

    <script src="https://cdn.tailwindcss.com"></script>
    <style>
  
        body {
            font-family: 'Inter', sans-serif;
            background-color: #f0f2f5;
        }
        .dashboard-container {
            background-color: #ffffff;
            border-radius: 1rem;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1), 0 1px 3px rgba(0, 0, 0, 0.08);
        }
        .nav-link {
            padding: 0.5rem 1rem;
            border-radius: 0.5rem;
            transition: background-color 0.3s ease;
        }
        .nav-link:hover {
            background-color: #e0e7ff; 
        }
        .logout-button {
            background-color: #ef4444; 
            color: white;
            padding: 0.75rem 1.5rem;
            border-radius: 0.5rem;
            font-weight: 600;
            transition: background-color 0.3s ease;
        }
        .logout-button:hover {
            background-color: #dc2626; 
        }
    </style>
</head>
<body class="flex flex-col items-center min-h-screen">

    <?php
    
    session_start();

    // Redirige al login si el usuario 
    if (!isset($_SESSION['user_id'])) {
        header('Location: /login_biblioteca/login.php');
        exit();
    }

    // --- CONFIGURACIÓN DE LA BASE DE DATOS ---
    $dbHost = 'localhost';
    $dbName = 'biblioteca1';
    $dbUser = 'root';
    $dbPass = '';

    // --- CONEXIÓN A LA BASE DE DATOS (PDO) ---
    $pdo = null;
    try {
        $pdo = new PDO("mysql:host=$dbHost;dbname=$dbName;charset=utf8mb4", $dbUser, $dbPass);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        die("<div class='text-center text-red-500'>Error de conexión a la base de datos: " . $e->getMessage() . "</div>");
    }

    // datos usuario desde la base de datos
    $user = null;
    if (isset($_SESSION['user_id'])) {
        try {
            $stmt = $pdo->prepare("SELECT id, name, last_name, email, type, status FROM users WHERE id = :id LIMIT 1");
            $stmt->execute([':id' => $_SESSION['user_id']]);
            $user = $stmt->fetch();
        } catch (PDOException $e) {
            error_log("Error al obtener datos del usuario en dashboard: " . $e->getMessage());
            header('Location: /login_biblioteca/login.php');
            exit();
        }
    }

    
    if (!$user) {
        header('Location: /login_biblioteca/login.php');
        exit();
    }



    // Si el usuario es Administrador redirige a admin_dashboard.php
    if ($user['type'] == 0) {
        header('Location: /login_biblioteca/admin_dashboard.php');
        exit(); 
    }
    if ($user['type'] == 1) {
        header('Location: /login_biblioteca/bibliotecario_dashboard.php');
        exit(); 
    }

    // (basado en tu esquema TINYINT)
    $userTypes = [
        0 => 'Administrador',
        1 => 'Bibliotecario',
        2 => 'Docente',
        3 => 'Estudiante',
        4 => 'Personal',
        5 => 'Visitante'
    ];

    $userTypeName = $userTypes[$user['type']] ?? 'Desconocido';
    ?>

    <header class="w-full bg-indigo-600 text-white p-4 shadow-md">
        <div class="container mx-auto flex justify-between items-center">
            <h2 class="text-2xl font-bold">Panel de Biblioteca</h2>
            <nav>
                <ul class="flex space-x-4">
                    <li><a href="#" class="nav-link text-white hover:bg-indigo-700">Inicio</a></li>
                    <li><a href="#" class="nav-link text-white hover:bg-indigo-700">Libros</a></li>
                    <li><a href="#" class="nav-link text-white hover:bg-indigo-700">Préstamos</a></li>
                    <?php if ($user['type'] == 0 || $user['type'] == 1): // Admin o Bibliotecario ?>
                    <li><a href="#" class="nav-link text-white hover:bg-indigo-700">Gestión de Usuarios</a></li>
                    <?php endif; ?>
                    <li><a href="/login_biblioteca/logout.php" class="nav-link logout-button">Cerrar Sesión</a></li>
                </ul>
            </nav>
        </div>
    </header>

    <main class="container mx-auto mt-8 p-8 w-full max-w-4xl dashboard-container">
        <h1 class="text-4xl font-extrabold text-gray-900 mb-6 text-center">
            ¡Bienvenido, <span class="text-indigo-600"><?php echo htmlspecialchars($user['name']); ?></span>!
        </h1>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
            <div class="bg-indigo-50 p-6 rounded-lg shadow-sm">
                <h2 class="text-2xl font-semibold text-indigo-700 mb-3">Tu Perfil</h2>
                <p class="text-gray-700"><span class="font-medium">Nombre Completo:</span> <?php echo htmlspecialchars($user['name'] . ' ' . $user['last_name']); ?></p>
                <p class="text-gray-700"><span class="font-medium">Email:</span> <?php echo htmlspecialchars($user['email']); ?></p>
                <p class="text-gray-700"><span class="font-medium">Tipo de Usuario:</span> <?php echo htmlspecialchars($userTypeName); ?></p>
                <p class="text-gray-700"><span class="font-medium">Estado:</span> <?php echo $user['status'] == 0 ? 'Activo' : 'Inactivo'; ?></p>
            </div>

            <div class="bg-green-50 p-6 rounded-lg shadow-sm">
                <h2 class="text-2xl font-semibold text-green-700 mb-3">Notificaciones Rápidas</h2>
                <ul class="list-disc list-inside text-gray-700">
                    <li>Tienes 2 libros próximos a vencer.</li>
                    <li>Nueva reserva disponible para "El Señor de los Anillos".</li>
                    <li>Multa pendiente: $5.00 por "Cien Años de Soledad".</li>
                </ul>
                <button class="mt-4 bg-green-600 hover:bg-green-700 text-white font-bold py-2 px-4 rounded-lg transition duration-300">Ver Todas</button>
            </div>
        </div>

        <div class="text-center mt-8">
            <h2 class="text-2xl font-semibold text-gray-800 mb-4">Acciones Rápidas</h2>
            <div class="flex flex-wrap justify-center gap-4">
                <a href="#" class="bg-blue-500 hover:bg-blue-600 text-white font-bold py-3 px-6 rounded-lg transition duration-300">
                    Buscar Libros
                </a>
                <a href="#" class="bg-purple-500 hover:bg-purple-600 text-white font-bold py-3 px-6 rounded-lg transition duration-300">
                    Mis Préstamos
                </a>
                <a href="#" class="bg-yellow-500 hover:bg-yellow-600 text-white font-bold py-3 px-6 rounded-lg transition duration-300">
                    Mis Reservas
                </a>
                <?php if ($user['type'] == 0): // Solo para Administradores ?>
                <a href="#" class="bg-red-500 hover:bg-red-600 text-white font-bold py-3 px-6 rounded-lg transition duration-300">
                    Panel de Administración
                </a>
                <?php endif; ?>
            </div>
        </div>
    </main>

    <footer class="w-full bg-gray-800 text-white text-center p-4 mt-8">
        <p>&copy; <?php echo date('Y'); ?> Biblioteca Todos los derechos reservados.</p>
    </footer>

</body>
</html>
