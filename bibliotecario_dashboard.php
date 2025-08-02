<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard de Bibliotecario - Biblioteca</title>
    
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background-color: #f0f2f5;
        }
        .container-card {
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
            background-color: #bfdbfe; /* Un tono más claro de azul para hover */
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
            background-color: #f8fafc;
            border-radius: 0.75rem;
            padding: 1.5rem;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
            border: 1px solid #e2e8f0;
        }
        .card-title {
            font-size: 1.5rem;
            font-weight: 700;
            color: #1d4ed8; /* Azul oscuro */
            margin-bottom: 1rem;
        }
        .metric-value {
            font-size: 2.5rem;
            font-weight: 800;
            color: #3b82f6; /* Azul vibrante */
        }
        .metric-label {
            font-size: 1rem;
            color: #64748b;
        }
        .form-input {
            border-radius: 0.5rem;
            border: 1px solid #d1d5db;
            padding: 0.75rem 1rem;
            width: 100%;
        }
        .action-button {
            background-color: #22c55e; /* Verde */
            color: white;
            padding: 0.75rem 1.5rem;
            border-radius: 0.5rem;
            font-weight: 600;
            transition: background-color 0.3s ease;
        }
        .action-button:hover {
            background-color: #16a34a;
        }
        .return-button {
            background-color: #f97316; /* Naranja */
        }
        .return-button:hover {
            background-color: #ea580c;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 1.5rem;
        }
        th, td {
            padding: 0.75rem;
            text-align: left;
            border-bottom: 1px solid #e2e8f0;
        }
        th {
            background-color: #f1f5f9;
            font-weight: 600;
            color: #334155;
        }
        tr:hover {
            background-color: #f8fafc;
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

    // --- CONFIGURACIÓN DE LA BASE DE DATOS ---
    $dbHost = 'localhost';
    $dbName = 'biblioteca1';
    $dbUser = 'root';
    $dbPass = '';

    // --- CONEXIÓN A LA BASE DE DATOS  ---
    $pdo = null;
    try {
        $pdo = new PDO("mysql:host=$dbHost;dbname=$dbName;charset=utf8mb4", $dbUser, $dbPass);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        die("<div class='text-center text-red-500'>Error de conexión a la base de datos: " . $e->getMessage() . "</div>");
    }

    // Obtener los datos del usuario actual para verificar su rol
    $currentUser = null;
    if (isset($_SESSION['user_id'])) {
        try {
            $stmt = $pdo->prepare("SELECT id, name, last_name, email, type, status FROM users WHERE id = :id LIMIT 1");
            $stmt->execute([':id' => $_SESSION['user_id']]);
            $currentUser = $stmt->fetch();
        } catch (PDOException $e) {
            error_log("Error al obtener datos del usuario actual en bibliotecario_dashboard: " . $e->getMessage());
            header('Location: /login_biblioteca/login.php');
            exit();
        }
    }

    // Si el usuario no es bibliotecario redirigir
    if (!$currentUser || $currentUser['type'] != 1) {
        header('Location: /login_biblioteca/dashboard.php'); // Redirige al dashboard general
        exit();
    }

    // --- LÓGICA DE GESTIÓN DE PRÉSTAMOS Y DEVOLUCIONES ---
    $message = '';
    $messageType = '';

    //  (necesaria para el periodo de préstamo)
    function get_setting($pdo, $key, $defaultValue = null) {
        try {
            $stmt = $pdo->prepare("SELECT setting_value FROM settings WHERE setting_key = :key LIMIT 1");
            $stmt->execute([':key' => $key]);
            $result = $stmt->fetchColumn();
            return $result !== false ? $result : $defaultValue;
        } catch (PDOException $e) {
            error_log("Error al obtener configuración '$key': " . $e->getMessage());
            return $defaultValue;
        }
    }

    // Manejar Préstamo de Libro
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'checkout_book') {
        $userDui = trim($_POST['user_dui'] ?? '');
        $bookCopyCode = trim($_POST['book_copy_code'] ?? '');

        if (empty($userDui) || empty($bookCopyCode)) {
            $message = 'Por favor, introduce el DUI del usuario y el Código Interno del Ejemplar.';
            $messageType = 'error';
        } else {
            try {
                // Encontrar al usuario por DUI
                $stmtUser = $pdo->prepare("SELECT id FROM users WHERE dui = :dui LIMIT 1");
                $stmtUser->execute([':dui' => $userDui]);
                $user = $stmtUser->fetch();

                if (!$user) {
                    $message = 'Usuario no encontrado con el DUI proporcionado.';
                    $messageType = 'error';
                } else {
                    // Encontrar el ejemplar del libro por Código Interno
                    $stmtCopy = $pdo->prepare("SELECT id, book_id, status FROM copies WHERE internal_code = :code LIMIT 1");
                    $stmtCopy->execute([':code' => $bookCopyCode]);
                    $copy = $stmtCopy->fetch();

                    if (!$copy) {
                        $message = 'Ejemplar de libro no encontrado con el Código Interno proporcionado.';
                        $messageType = 'error';
                    } elseif ($copy['status'] !== 'disponible') {
                        $message = 'El ejemplar no está disponible para préstamo (estado actual: ' . htmlspecialchars($copy['status']) . ').';
                        $messageType = 'error';
                    } else {
                        // Registrar el préstamo
                        $periodoPrestamo = (int) get_setting($pdo, 'periodo_prestamo', 14); 
                        $fechaPrestamo = date('Y-m-d H:i:s');
                        $fechaDevolucionEsperada = date('Y-m-d H:i:s', strtotime("+$periodoPrestamo days"));

                        $pdo->beginTransaction(); // Iniciar transacción

                        $stmtLoan = $pdo->prepare("INSERT INTO loans (user_id, copy_id, loan_date, expected_return_date, status)
                                                   VALUES (:user_id, :copy_id, :loan_date, :expected_return_date, 'activo')");
                        $loanSuccess = $stmtLoan->execute([
                            ':user_id' => $user['id'],
                            ':copy_id' => $copy['id'],
                            ':loan_date' => $fechaPrestamo,
                            ':expected_return_date' => $fechaDevolucionEsperada
                        ]);

                        //  Actualizar el estado del ejemplar a 'prestado'
                        $stmtUpdateCopy = $pdo->prepare("UPDATE copies SET status = 'prestado' WHERE id = :id");
                        $updateCopySuccess = $stmtUpdateCopy->execute([':id' => $copy['id']]);

                        if ($loanSuccess && $updateCopySuccess) {
                            $pdo->commit(); 
                            $message = '¡Préstamo registrado exitosamente!';
                            $messageType = 'success';
                            // Limpiar
                            $_POST['user_dui'] = '';
                            $_POST['book_copy_code'] = '';
                        } else {
                            $pdo->rollBack(); 
                            $message = 'Error al registrar el préstamo o actualizar el ejemplar.';
                            $messageType = 'error';
                        }
                    }
                }
            } catch (PDOException $e) {
                $pdo->rollBack();
                error_log("Error al procesar préstamo: " . $e->getMessage());
                $message = 'Error en la base de datos al registrar el préstamo. Inténtalo de nuevo.';
                $messageType = 'error';
            }
        }
    }

    // Manejar Devolución de Libro
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'return_book') {
        $loanId = $_POST['loan_id'] ?? null;

        if (!$loanId || !is_numeric($loanId)) {
            $message = 'ID de préstamo no válido.';
            $messageType = 'error';
        } else {
            try {
                // Obtener detalles del préstamo
                $stmtLoan = $pdo->prepare("SELECT id, copy_id, expected_return_date FROM loans WHERE id = :id AND status = 'activo' LIMIT 1");
                $stmtLoan->execute([':id' => $loanId]);
                $loan = $stmtLoan->fetch();

                if (!$loan) {
                    $message = 'Préstamo no encontrado o ya no está activo.';
                    $messageType = 'error';
                } else {
                    $pdo->beginTransaction(); // Iniciar transacción

                    $fechaDevolucionReal = date('Y-m-d H:i:s');
                    $multa = 0.00;

                    // Calcular multa si hay atraso
                    $expectedDate = new DateTime($loan['expected_return_date']);
                    $actualDate = new DateTime($fechaDevolucionReal);
                    if ($actualDate > $expectedDate) {
                        $interval = $actualDate->diff($expectedDate);
                        $diasAtraso = $interval->days;
                        $multaPorDia = (float) get_setting($pdo, 'multa_atraso', 0.25);
                        $multa = $diasAtraso * $multaPorDia;
                    }

                    // Actualizar el préstamo a 'completado' y registrar fecha de devolución real y multa
                    $stmtUpdateLoan = $pdo->prepare("UPDATE loans SET fecha_devolucion_real = :return_date, multa = :multa, status = 'completado' WHERE id = :id");
                    $updateLoanSuccess = $stmtUpdateLoan->execute([
                        ':return_date' => $fechaDevolucionReal,
                        ':multa' => $multa,
                        ':id' => $loanId
                    ]);

                    // Actualizar el estado del ejemplar a 'disponible'
                    $stmtUpdateCopy = $pdo->prepare("UPDATE copies SET status = 'disponible' WHERE id = :id");
                    $updateCopySuccess = $stmtUpdateCopy->execute([':id' => $loan['copy_id']]);

                    if ($updateLoanSuccess && $updateCopySuccess) {
                        $pdo->commit(); 
                        $message = '¡Devolución registrada exitosamente!';
                        if ($multa > 0) {
                            $message .= " Multa aplicada: $" . number_format($multa, 2);
                        }
                        $messageType = 'success';
                    } else {
                        $pdo->rollBack(); // Revertir la transacción
                        $message = 'Error al registrar la devolución o actualizar el ejemplar.';
                        $messageType = 'error';
                    }
                }
            } catch (PDOException $e) {
                $pdo->rollBack();
                error_log("Error al procesar devolución: " . $e->getMessage());
                $message = 'Error en la base de datos al registrar la devolución. Inténtalo de nuevo.';
                $messageType = 'error';
            }
        }
        // Redirigir para limpiar la URL
        header('Location: /login_biblioteca/bibliotecario_dashboard.php?msg=' . urlencode($message) . '&type=' . $messageType);
        exit();
    }

    // (después de redirecciones)
    if (isset($_GET['msg']) && isset($_GET['type'])) {
        $message = htmlspecialchars($_GET['msg']);
        $messageType = htmlspecialchars($_GET['type']);
    }

    // --- OBTENER DATOS PARA EL DASHBOARD ---
    $activeLoans = [];
    $overdueLoans = [];
    $recentActivity = [];
    $totalUsersCount = 0; // Inicializar la variable

    try {
        // Préstamos Activos
        $stmtActiveLoans = $pdo->query("
            SELECT
                l.id AS loan_id,
                u.name AS user_name,
                u.last_name AS user_last_name,
                u.dui AS user_dui,
                c.internal_code AS copy_code,
                b.title AS book_title,
                l.loan_date,
                l.expected_return_date
            FROM loans l
            JOIN users u ON l.user_id = u.id
            JOIN copies c ON l.copy_id = c.id
            JOIN books b ON c.book_id = b.id
            WHERE l.status = 'activo'
            ORDER BY l.expected_return_date ASC
        ");
        $activeLoans = $stmtActiveLoans->fetchAll();

        // Préstamos Atrasados (filtrar los activos que ya pasaron la fecha esperada)
        foreach ($activeLoans as $loan) {
            $expectedDate = new DateTime($loan['expected_return_date']);
            $currentDate = new DateTime();
            if ($currentDate > $expectedDate) {
                $overdueLoans[] = $loan;
            }
        }

        // Actividad Reciente (últimos 5 préstamos/devoluciones completadas)
        $stmtRecentActivity = $pdo->query("
            SELECT
                l.id AS loan_id,
                u.name AS user_name,
                u.last_name AS user_last_name,
                b.title AS book_title,
                l.loan_date,
                l.fecha_devolucion_real,
                l.status
            FROM loans l
            JOIN users u ON l.user_id = u.id
            JOIN copies c ON l.copy_id = c.id
            JOIN books b ON c.book_id = b.id
            WHERE l.status IN ('activo', 'completado', 'atrasado') -- Incluir activos para ver todos los recientes
            ORDER BY l.loan_date DESC
            LIMIT 5
        ");
        $recentActivity = $stmtRecentActivity->fetchAll();

        // Obtener el total de usuarios registrados
        $stmtTotalUsers = $pdo->query("SELECT COUNT(id) FROM users");
        $totalUsersCount = $stmtTotalUsers->fetchColumn();

    } catch (PDOException $e) {
        error_log("Error al obtener datos del dashboard de bibliotecario: " . $e->getMessage());
        $message = 'Error al cargar datos del dashboard.';
        $messageType = 'error';
    }
    ?>

    <header class="w-full bg-blue-700 text-white p-4 shadow-md">
        <div class="container mx-auto flex justify-between items-center">
            <h2 class="text-2xl font-bold">Panel de Bibliotecario</h2>
            <nav>
                <ul class="flex space-x-4">
                    <li><a href="/login_biblioteca/dashboard.php" class="nav-link text-white hover:bg-blue-800">Inicio</a></li>
                    <li><a href="#" class="nav-link text-white hover:bg-blue-800">Gestión de Libros</a></li>
                    <li><a href="#" class="nav-link text-white hover:bg-blue-800">Reportes de Préstamos</a></li>
                    <li><a href="/login_biblioteca/logout.php" class="nav-link logout-button">Cerrar Sesión</a></li>
                </ul>
            </nav>
        </div>
    </header>

    <main class="container mx-auto mt-8 p-8 w-full max-w-6xl container-card">
        <h1 class="text-4xl font-extrabold text-gray-900 mb-6 text-center">
            ¡Bienvenido, Bibliotecario <span class="text-blue-700"><?php echo htmlspecialchars($currentUser['name']); ?></span>!
        </h1>
        <p class="text-center text-gray-600 mb-8">Aquí puedes gestionar los préstamos y devoluciones de la biblioteca.</p>



        <!-- Métricas Clave -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-8">
            <div class="card text-center">
                <p class="metric-value"><?php echo count($activeLoans); ?></p>
                <p class="metric-label">Préstamos Activos</p>
            </div>
            <div class="card text-center">
                <p class="metric-value text-red-600"><?php echo count($overdueLoans); ?></p>
                <p class="metric-label">Préstamos Atrasados</p>
            </div>
            <div class="card text-center">
                <p class="metric-value"><?php echo $totalUsersCount; ?></p>
                <p class="metric-label">Usuarios Registrados</p>
            </div>
        </div>

        <!-- Sección de Préstamo de Libro -->
        <div class="mb-8 p-6 border border-gray-200 rounded-lg bg-blue-50">
            <h2 class="text-2xl font-semibold text-blue-700 mb-4">Registrar Nuevo Préstamo</h2>
            <form action="bibliotecario_dashboard.php" method="POST" class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <input type="hidden" name="action" value="checkout_book">
                <div>
                    <label for="user_dui" class="block text-sm font-medium text-gray-700 mb-1">Carnert de identidad CI o DUI del Usuario</label>
                    <input type="text" id="user_dui" name="user_dui" required
                           class="form-input focus:ring-blue-500 focus:border-blue-500"
                           placeholder="Ej: 12345678-9"
                           value="<?php echo htmlspecialchars($_POST['user_dui'] ?? ''); ?>">
                </div>
                <div>
                    <label for="book_copy_code" class="block text-sm font-medium text-gray-700 mb-1">Código Interno del Ejemplar</label>
                    <input type="text" id="book_copy_code" name="book_copy_code" required
                           class="form-input focus:ring-blue-500 focus:border-blue-500"
                           placeholder="Ej: LIB-001-C01"
                           value="<?php echo htmlspecialchars($_POST['book_copy_code'] ?? ''); ?>">
                </div>
                <div class="md:col-span-2 text-right">
                    <button type="submit" class="action-button">
                        Prestar Libro
                    </button>
                </div>
            </form>
        </div>

        <!-- Sección de Devolución de Libro -->
        <div class="mb-8 p-6 border border-gray-200 rounded-lg bg-orange-50">
            <h2 class="text-2xl font-semibold text-orange-700 mb-4">Registrar Devolución</h2>
            <form action="bibliotecario_dashboard.php" method="POST" class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <input type="hidden" name="action" value="return_book">
                <div>
                    <label for="loan_id" class="block text-sm font-medium text-gray-700 mb-1">ID del Préstamo a Devolver</label>
                    <input type="number" id="loan_id" name="loan_id" required
                           class="form-input focus:ring-orange-500 focus:border-orange-500"
                           placeholder="Ej: 123">
                </div>
                <div class="md:col-span-2 text-right">
                    <button type="submit" class="action-button return-button">
                        Devolver Libro
                    </button>
                </div>
            </form>
        </div>

        <!-- Sección de Préstamos Activos -->
        <div class="p-6 border border-gray-200 rounded-lg bg-white mb-8">
            <h2 class="text-2xl font-semibold text-gray-800 mb-4">Préstamos Activos</h2>
            <?php if (empty($activeLoans)): ?>
                <p class="text-gray-600 text-center">No hay préstamos activos actualmente.</p>
            <?php else: ?>
                <div class="overflow-x-auto">
                    <table>
                        <thead>
                            <tr>
                                <th>ID Préstamo</th>
                                <th>Usuario</th>
                                <th>DUI Usuario</th>
                                <th>Libro</th>
                                <th>Cód. Ejemplar</th>
                                <th>Fecha Préstamo</th>
                                <th>Fecha Esperada</th>
                                <th>Estado</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($activeLoans as $loan): ?>
                                <tr class="<?php echo (new DateTime() > new DateTime($loan['expected_return_date'])) ? 'bg-red-100' : ''; ?>">
                                    <td><?php echo htmlspecialchars($loan['loan_id']); ?></td>
                                    <td><?php echo htmlspecialchars($loan['user_name'] . ' ' . $loan['user_last_name']); ?></td>
                                    <td><?php echo htmlspecialchars($loan['user_dui']); ?></td>
                                    <td><?php echo htmlspecialchars($loan['book_title']); ?></td>
                                    <td><?php echo htmlspecialchars($loan['copy_code']); ?></td>
                                    <td><?php echo htmlspecialchars(date('d/m/Y', strtotime($loan['loan_date']))); ?></td>
                                    <td><?php echo htmlspecialchars(date('d/m/Y', strtotime($loan['expected_return_date']))); ?></td>
                                    <td><?php echo (new DateTime() > new DateTime($loan['expected_return_date'])) ? '<span class="text-red-600 font-semibold">Atrasado</span>' : 'Activo'; ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>

        <!-- Sección de Actividad Reciente -->
        <div class="p-6 border border-gray-200 rounded-lg bg-white">
            <h2 class="text-2xl font-semibold text-gray-800 mb-4">Actividad Reciente</h2>
            <?php if (empty($recentActivity)): ?>
                <p class="text-gray-600 text-center">No hay actividad reciente.</p>
            <?php else: ?>
                <ul class="list-disc list-inside text-gray-700 space-y-2">
                    <?php foreach ($recentActivity as $activity): ?>
                        <li>
                            [<?php echo htmlspecialchars(date('d/m/Y', strtotime($activity['loan_date']))); ?>]
                            <?php echo htmlspecialchars($activity['user_name'] . ' ' . $activity['user_last_name']); ?>
                            <?php
                                if ($activity['status'] == 'completado') {
                                    echo 'devolvió el libro "' . htmlspecialchars($activity['book_title']) . '".';
                                } elseif ($activity['status'] == 'activo') {
                                    echo 'prestó el libro "' . htmlspecialchars($activity['book_title']) . '".';
                                } elseif ($activity['status'] == 'atrasado') {
                                    echo 'tiene el libro "' . htmlspecialchars($activity['book_title']) . '" atrasado.';
                                }
                            ?>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </div>
    </main>

    <footer class="w-full bg-gray-800 text-white text-center p-4 mt-8">
        <p>&copy; <?php echo date('Y'); ?> Biblioteca Todos los derechos reservados.</p>
    </footer>

</body>
</html>
