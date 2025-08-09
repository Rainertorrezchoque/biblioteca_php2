<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard de Administrador - Biblioteca</title>

    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background-color: #f0f2f5;
        }
        .admin-dashboard-container {
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
            background-color: #a78bfa; /* Un tono más claro de púrpura para hover */
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
        .card {
            background-color: #f8fafc; /* Gris muy claro */
            border-radius: 0.75rem;
            padding: 1.5rem;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
            border: 1px solid #e2e8f0;
        }
        .card-title {
            font-size: 1.5rem;
            font-weight: 700;
            color: #4338ca; /* Púrpura oscuro */
            margin-bottom: 1rem;
        }
        .metric-value {
            font-size: 2.5rem;
            font-weight: 800;
            color: #4f46e5; /* Púrpura vibrante */
        }
        .metric-label {
            font-size: 1rem;
            color: #64748b; /* Gris oscuro */
        }
    </style>
</head>
<body class="flex flex-col items-center min-h-screen">

    <?php
    session_start();

    
    if (!isset($_SESSION['user_id'])) {
        header('Location: /login_biblioteca/login.php');
        exit();
    }

    // BASE DE DATOS
    $dbHost = 'localhost';
    $dbName = 'biblioteca1';
    $dbUser = 'root';
    $dbPass = '';

   
    $pdo = null;
    try {
        $pdo = new PDO("mysql:host=$dbHost;dbname=$dbName;charset=utf8mb4", $dbUser, $dbPass);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        die("<div class='text-center text-red-500'>Error de conexión a la base de datos: " . $e->getMessage() . "</div>");
    }

    // Obtener los datos del usuario (relizado x noelia) 
    $user = null;
    if (isset($_SESSION['user_id'])) {
        try {
            $stmt = $pdo->prepare("SELECT id, name, last_name, email, type, status FROM users WHERE id = :id LIMIT 1");
            $stmt->execute([':id' => $_SESSION['user_id']]);
            $user = $stmt->fetch();
        } catch (PDOException $e) {
            error_log("Error al obtener datos del usuario en admin_dashboard: " . $e->getMessage());
            header('Location: /login_biblioteca/login.php');
            exit();
        }
    }

    
    if (!$user || $user['type'] != 0) {
        header('Location: /login_biblioteca/login.php');
        exit();
    }

    // Mapeo (roles) trabajado por rainer
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

    <header class="w-full bg-purple-700 text-white p-4 shadow-md">
        <div class="container mx-auto flex justify-between items-center">
            <h2 class="text-2xl font-bold">Panel de Administración</h2>
            <nav>
                <ul class="flex space-x-4">
                    <li><a href="/login_biblioteca/dashboard.php" class="nav-link text-white hover:bg-purple-800">Inicio</a></li>
                    <li><a href="/login_biblioteca/manage_users.php" class="nav-link text-white hover:bg-purple-800">Gestión de Usuarios</a></li>
                    <li><a href="/login_biblioteca/manage_books.php" class="nav-link text-white hover:bg-purple-800">Gestión de Libros</a></li>
                    <li><a href="#" class="nav-link text-white hover:bg-purple-800">Reportes</a></li>
                    <li><a href="/login_biblioteca/logout.php" class="nav-link logout-button">Cerrar Sesión</a></li>
                </ul>
            </nav>
        </div>
    </header>

    <main class="container mx-auto mt-8 p-8 w-full max-w-6xl admin-dashboard-container">
        <h1 class="text-4xl font-extrabold text-gray-900 mb-6 text-center">
            ¡Bienvenido, Administrador <span class="text-purple-700"><?php echo htmlspecialchars($user['name']); ?></span>!
        </h1>
        <p class="text-center text-gray-600 mb-8">Aquí tienes un resumen de la actividad de la biblioteca.</p>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
            <div class="card text-center">
                <p id="total-users" class="metric-value">...</p>
                <p class="metric-label">Usuarios Registrados</p>
            </div>
            <div class="card text-center">
                <p id="total-books" class="metric-value">...</p>
                <p class="metric-label">Libros en Catálogo</p>
            </div>
            <div class="card text-center">
                <p id="active-loans" class="metric-value">...</p>
                <p class="metric-label">Préstamos Activos</p>
            </div>
            <div class="card text-center">
                <p id="pending-reservations" class="metric-value">...</p>
                <p class="metric-label">Reservas Pendientes</p>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
            <div class="card">
                <h2 class="card-title">Actividad Reciente</h2>
                <ul id="recent-activity" class="list-disc list-inside text-gray-700 space-y-2">
                    <li>Cargando actividad...</li>
                </ul>
                <button class="mt-4 bg-purple-600 hover:bg-purple-700 text-white font-bold py-2 px-4 rounded-lg transition duration-300">Ver Bitácora Completa</button>
            </div>

            <div class="card">
                <h2 class="card-title">Alertas Importantes</h2>
                <ul id="important-alerts" class="list-disc list-inside text-red-600 space-y-2">
                    <li>Cargando alertas...</li>
                </ul>
                <button class="mt-4 bg-red-600 hover:bg-red-700 text-white font-bold py-2 px-4 rounded-lg transition duration-300">Gestionar Alertas</button>
            </div>
        </div>

        <div class="text-center mt-8">
            <h2 class="text-2xl font-semibold text-gray-800 mb-4">Herramientas de Administración</h2>
            <div class="flex flex-wrap justify-center gap-4">
                <a href="/login_biblioteca/manage_users.php" class="bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-3 px-6 rounded-lg transition duration-300">
                    Administrar Usuarios
                </a>
                <a href="/login_biblioteca/manage_books.php" class="bg-green-600 hover:bg-green-700 text-white font-bold py-3 px-6 rounded-lg transition duration-300">
                    Administrar Libros
                </a>
                <a href="#" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 px-6 rounded-lg transition duration-300">
                    Generar Reportes
                </a>
                <a href="#" class="bg-orange-600 hover:bg-orange-700 text-white font-bold py-3 px-6 rounded-lg transition duration-300">
                    Configuración del Sistema
                </a>
            </div>
        </div>
    </main>

    <footer class="w-full bg-gray-800 text-white text-center p-4 mt-8">
        <p>&copy; <?php echo date('Y'); ?> Biblioteca Todos los derechos reservados.</p>
    </footer>

    
    <script src="admin_dashboard.js"></script>

</body>
</html>
