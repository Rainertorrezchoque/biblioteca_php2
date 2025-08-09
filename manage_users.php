<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Administrar Usuarios - Biblioteca</title>

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

        .input-field {
            border-radius: 0.5rem;
            border: 1px solid #d1d5db;
            padding: 0.75rem 1rem;
        }

        .submit-button {
            background-color: #4f46e5;
            color: white;
            padding: 0.75rem 1.5rem;
            border-radius: 0.5rem;
            font-weight: 600;
            transition: background-color 0.3s ease;
        }
        .logout-button {
            background-color: #ef4444;
            color: white;
            padding: 0.75rem 1.5rem;
            border-radius: 0.5rem;
            font-weight: 600;
            transition: background-color 0.3s ease;
        }
        

        .submit-button:hover {
            background-color: #4338ca;
        }

        .message {
            font-size: 0.875rem;
            margin-top: 0.5rem;
            padding: 0.75rem 1rem;
            border-radius: 0.5rem;
            text-align: center;
        }

        .error-message {
            color: #ef4444;
            background-color: #fef2f2;
            border: 1px solid #fecaca;
        }

        .success-message {
            color: #10b981;
            background-color: #ecfdf5;
            border: 1px solid #a7f3d0;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 1.5rem;
        }

        th,
        td {
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

        .action-button {
            padding: 0.3rem 0.6rem;
            border-radius: 0.375rem;
            font-size: 0.875rem;
            font-weight: 500;
            color: white;
            transition: background-color 0.2s ease;
        }

        .edit-button {
            background-color: #3b82f6;
        }

        .edit-button:hover {
            background-color: #2563eb;
        }

        .delete-button {
            background-color: #ef4444;
        }

        .delete-button:hover {
            background-color: #dc2626;
        }
    </style>
</head>

<body class="flex flex-col items-center min-h-screen py-8">

    <?php
    session_start();

    // Redirige al login si el usuario no está autenticado o no es administrador
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

    // Obtener los datos del usuario actual
    $currentUser = null;
    if (isset($_SESSION['user_id'])) {
        try {
            $stmt = $pdo->prepare("SELECT type FROM users WHERE id = :id LIMIT 1");
            $stmt->execute([':id' => $_SESSION['user_id']]);
            $currentUser = $stmt->fetch();
        } catch (PDOException $e) {
            error_log("Error al obtener datos del usuario actual en manage_users: " . $e->getMessage());
            header('Location: /login_biblioteca/login.php');
            exit();
        }
    }

    // Si el usuario no es administrador, redirigir
    if (!$currentUser || $currentUser['type'] != 0) {
        header('Location: /login_biblioteca/dashboard.php');
        exit();
    }

    // --- FUNCIONES AUXILIARES ---

    /**
     * Hashea una contraseña usando el algoritmo recomendado (PASSWORD_BCRYPT).
     * @param string $password La contraseña en texto plano.
     * @return string La contraseña hasheada.
     */
    function hash_password($password)
    {
        return password_hash($password, PASSWORD_BCRYPT);
    }

    // Mapeo de tipos de usuario 
    $userTypes = [
        0 => 'Administrador',
        1 => 'Bibliotecario',
        2 => 'Docente',
        3 => 'Estudiante',
        4 => 'Personal',
        5 => 'Visitante'
    ];

    // Mapeo de estados de usuario
    $userStatuses = [
        0 => 'Activo',
        1 => 'Inactivo'
    ];

    // Variables para mensajes y datos del usuario a editar
    $message = '';
    $messageType = '';
    $editingUser = null; // Almacenará los datos del usuario si estamos en modo edición

    // --- LÓGICA DE PROCESAMIENTO DE ACCIONES (Crear, Editar, Eliminar) ---

    // 1. Manejar eliminación de usuario
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete_user') {
        $userIdToDelete = $_POST['user_id'] ?? null;
        if ($userIdToDelete && is_numeric($userIdToDelete)) {
            try {
                $stmt = $pdo->prepare("DELETE FROM users WHERE id = :id");
                if ($stmt->execute([':id' => $userIdToDelete])) {
                    $message = 'Usuario eliminado exitosamente.';
                    $messageType = 'success';
                } else {
                    $message = 'Error al eliminar el usuario.';
                    $messageType = 'error';
                }
            } catch (PDOException $e) {
                error_log("Error al eliminar usuario: " . $e->getMessage());
                $message = 'Error en la base de datos al eliminar usuario.';
                $messageType = 'error';
            }
        } else {
            $message = 'ID de usuario no válido para eliminar.';
            $messageType = 'error';
        }
        // Redirigir para limpiar la URL y evitar reenvío del formulario
        header('Location: /login_biblioteca/manage_users.php?msg=' . urlencode($message) . '&type=' . $messageType);
        exit();
    }

    //  (carga de datos en el formulario)
    if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action']) && $_GET['action'] === 'edit') {
        $userIdToEdit = $_GET['id'] ?? null;
        if ($userIdToEdit && is_numeric($userIdToEdit)) {
            try {
                $stmt = $pdo->prepare("SELECT id, dui, nie, name, last_name, email, phone, type, status FROM users WHERE id = :id LIMIT 1");
                $stmt->execute([':id' => $userIdToEdit]);
                $editingUser = $stmt->fetch();
                if (!$editingUser) {
                    $message = 'Usuario no encontrado para editar.';
                    $messageType = 'error';
                }
            } catch (PDOException $e) {
                error_log("Error al cargar usuario para editar: " . $e->getMessage());
                $message = 'Error en la base de datos al cargar usuario para editar.';
                $messageType = 'error';
            }
        } else {
            $message = 'ID de usuario no válido para editar.';
            $messageType = 'error';
        }
    }

    // (envío del formulario de edición)
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_user') {
        $userId = $_POST['user_id'] ?? null;
        $dui = trim($_POST['dui'] ?? '');
        $nie = trim($_POST['nie'] ?? '');
        $name = trim($_POST['name'] ?? '');
        $lastName = trim($_POST['last_name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $password = $_POST['password'] ?? ''; // Opcional al editar
        $confirmPassword = $_POST['confirm_password'] ?? ''; // Opcional al editar
        $type = $_POST['type'] ?? '';
        $status = $_POST['status'] ?? '';
        $sectionId = $_POST['section_id'] ?? null;

        if (!$userId || !is_numeric($userId)) {
            $message = 'ID de usuario no válido para actualizar.';
            $messageType = 'error';
        } elseif (empty($name) || empty($lastName) || empty($email) || empty($phone) || $type === '' || $status === '') {
            $message = 'Todos los campos obligatorios (excepto contraseña si no se cambia) deben ser completados.';
            $messageType = 'error';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $message = 'El formato del email no es válido.';
            $messageType = 'error';
        } elseif (!empty($password) && $password !== $confirmPassword) {
            $message = 'Las contraseñas no coinciden.';
            $messageType = 'error';
        } elseif (!empty($password) && strlen($password) < 6) {
            $message = 'La nueva contraseña debe tener al menos 6 caracteres.';
            $messageType = 'error';
        } elseif (!array_key_exists($type, $userTypes)) {
            $message = 'Tipo de usuario no válido.';
            $messageType = 'error';
        } elseif (!array_key_exists($status, $userStatuses)) {
            $message = 'Estado de usuario no válido.';
            $messageType = 'error';
        } else {
            try {
                // Verificar si el email, DUI o NIE dupluicados
                $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE (email = :email OR dui = :dui OR nie = :nie) AND id != :id");
                $stmt->execute([':email' => $email, ':dui' => $dui, ':nie' => $nie, ':id' => $userId]);
                if ($stmt->fetchColumn() > 0) {
                    $message = 'El email, DUI o NIE ya está registrado por otro usuario.';
                    $messageType = 'error';
                } else {
                    $sql = "UPDATE users SET dui = :dui, nie = :nie, name = :name, last_name = :last_name, 
                            email = :email, phone = :phone, type = :type, status = :status, section_id = :section_id";
                    $params = [
                        ':dui' => $dui,
                        ':nie' => $nie,
                        ':name' => $name,
                        ':last_name' => $lastName,
                        ':email' => $email,
                        ':phone' => $phone,
                        ':type' => $type,
                        ':status' => $status,
                        ':section_id' => $sectionId,
                        ':id' => $userId
                    ];

                    if (!empty($password)) { // Solo actualiza la contraseña 
                        $sql .= ", password = :password";
                        $params[':password'] = hash_password($password);
                    }
                    $sql .= " WHERE id = :id";

                    $stmt = $pdo->prepare($sql);
                    $success = $stmt->execute($params);

                    if ($success) {
                        $message = '¡Usuario actualizado exitosamente!';
                        $messageType = 'success';
                        // Limpiar $editingUser para que el formulario vuelva a modo "crear"
                        $editingUser = null;
                    } else {
                        $message = 'Ocurrió un error al actualizar el usuario. Inténtalo de nuevo.';
                        $messageType = 'error';
                    }
                }
            } catch (PDOException $e) {
                error_log("Error al actualizar usuario: " . $e->getMessage());
                $message = 'Error en la base de datos al actualizar usuario. Por favor, inténtalo más tarde.';
                $messageType = 'error';
            }
        }
    }

    // (envío del formulario de creación)

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'create_user' && !$editingUser) {
        // Recoger y sanear los datos del formulario
        $dui = trim($_POST['dui'] ?? '');
        $nie = trim($_POST['nie'] ?? '');
        $name = trim($_POST['name'] ?? '');
        $lastName = trim($_POST['last_name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $password = $_POST['password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';
        $type = $_POST['type'] ?? '';
        $status = $_POST['status'] ?? '';
        $sectionId = $_POST['section_id'] ?? null;

        // (duplicadas para claridad, pero podrían refactorizarse)
        if (empty($name) || empty($lastName) || empty($email) || empty($phone) || empty($password) || empty($confirmPassword) || $type === '' || $status === '') {
            $message = 'Todos los campos obligatorios deben ser completados.';
            $messageType = 'error';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $message = 'El formato del email no es válido.';
            $messageType = 'error';
        } elseif ($password !== $confirmPassword) {
            $message = 'Las contraseñas no coinciden.';
            $messageType = 'error';
        } elseif (strlen($password) < 6) {
            $message = 'La contraseña debe tener al menos 6 caracteres.';
            $messageType = 'error';
        } elseif (!array_key_exists($type, $userTypes)) {
            $message = 'Tipo de usuario no válido.';
            $messageType = 'error';
        } elseif (!array_key_exists($status, $userStatuses)) {
            $message = 'Estado de usuario no válido.';
            $messageType = 'error';
        } else {
            // Verificar si el email o DUI/NIE ya existen duplicados
            try {
                $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE email = :email OR dui = :dui OR nie = :nie");
                $stmt->execute([':email' => $email, ':dui' => $dui, ':nie' => $nie]);
                if ($stmt->fetchColumn() > 0) {
                    $message = 'El email, DUI o NIE ya está registrado.';
                    $messageType = 'error';
                } else {
                    // Hashear la contraseña
                    $hashedPassword = hash_password($password);

                    // Insertar el nuevo usuario
                    $sql = "INSERT INTO users (dui, nie, name, last_name, email, phone, password, type, status, section_id)
                            VALUES (:dui, :nie, :name, :last_name, :email, :phone, :password, :type, :status, :section_id)";
                    $stmt = $pdo->prepare($sql);
                    $success = $stmt->execute([
                        ':dui' => $dui,
                        ':nie' => $nie,
                        ':name' => $name,
                        ':last_name' => $lastName,
                        ':email' => $email,
                        ':phone' => $phone,
                        ':password' => $hashedPassword,
                        ':type' => $type,
                        ':status' => $status,
                        ':section_id' => $sectionId
                    ]);

                    if ($success) {
                        $message = '¡Usuario creado exitosamente!';
                        $messageType = 'success';
                        // Limpiar los campos del formulario después del éxito
                        $_POST = [];
                    } else {
                        $message = 'Ocurrió un error al crear el usuario. Inténtalo de nuevo.';
                        $messageType = 'error';
                    }
                }
            } catch (PDOException $e) {
                error_log("Error al crear usuario: " . $e->getMessage());
                $message = 'Error en la base de datos al crear usuario. Por favor, inténtalo más tarde.';
                $messageType = 'error';
            }
        }
    }

    // (después de redirecciones)
    if (isset($_GET['msg']) && isset($_GET['type'])) {
        $message = htmlspecialchars($_GET['msg']);
        $messageType = htmlspecialchars($_GET['type']);
    }


    // --- LÓGICA PARA LISTAR USUARIOS ---
    $users = [];
    try {
        $stmt = $pdo->query("SELECT id, dui, nie, name, last_name, email, phone, type, status FROM users ORDER BY name ASC");
        $users = $stmt->fetchAll();
    } catch (PDOException $e) {
        error_log("Error al listar usuarios: " . $e->getMessage());
        // Si hay un error al listar, no sobrescribir un mensaje de éxito/error de una operación anterior
        if (empty($message)) {
            $message = 'Error al cargar la lista de usuarios.';
            $messageType = 'error';
        }
    }
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

    <main class="container mx-auto mt-8 p-8 w-full max-w-6xl container-card">
        <h1 class="text-4xl font-extrabold text-gray-900 mb-6 text-center">
            Administrar Usuarios
        </h1>

        <?php if ($message): ?>
            <p class="message <?php echo $messageType === 'error' ? 'error-message' : 'success-message'; ?> mb-6"><?php echo $message; ?></p>
        <?php endif; ?>

        <!-- Sección para Crear/Editar Usuario -->
        <div class="mb-8 p-6 border border-gray-200 rounded-lg bg-gray-50">
            <h2 class="text-2xl font-semibold text-gray-800 mb-4">
                <?php echo $editingUser ? 'Editar Usuario' : 'Crear Nuevo Usuario'; ?>
            </h2>
            <form action="manage_users.php" method="POST" class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <input type="hidden" name="action" value="<?php echo $editingUser ? 'update_user' : 'create_user'; ?>">
                <?php if ($editingUser): ?>
                    <input type="hidden" name="user_id" value="<?php echo htmlspecialchars($editingUser['id']); ?>">
                <?php endif; ?>

                <div>
                    <label for="name" class="block text-sm font-medium text-gray-700 mb-1">Nombre</label>
                    <input type="text" id="name" name="name" required
                        class="input-field w-full focus:ring-indigo-500 focus:border-indigo-500"
                        value="<?php echo htmlspecialchars($editingUser['name'] ?? $_POST['name'] ?? ''); ?>">
                </div>

                <div>
                    <label for="last_name" class="block text-sm font-medium text-gray-700 mb-1">Apellido</label>
                    <input type="text" id="last_name" name="last_name" required
                        class="input-field w-full focus:ring-indigo-500 focus:border-indigo-500"
                        value="<?php echo htmlspecialchars($editingUser['last_name'] ?? $_POST['last_name'] ?? ''); ?>">
                </div>

                <div>
                    <label for="email" class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                    <input type="email" id="email" name="email" required
                        class="input-field w-full focus:ring-indigo-500 focus:border-indigo-500"
                        placeholder="usuario@example.com"
                        value="<?php echo htmlspecialchars($editingUser['email'] ?? $_POST['email'] ?? ''); ?>">
                </div>

                <div>
                    <label for="phone" class="block text-sm font-medium text-gray-700 mb-1">Teléfono</label>
                    <input type="text" id="phone" name="phone" required
                        class="input-field w-full focus:ring-indigo-500 focus:border-indigo-500"
                        placeholder="Ej: 7123-4567"
                        value="<?php echo htmlspecialchars($editingUser['phone'] ?? $_POST['phone'] ?? ''); ?>">
                </div>

                <div>
                    <label for="dui" class="block text-sm font-medium text-gray-700 mb-1">Carnet de identidad CI o DUI (Opcional)</label>
                    <input type="text" id="dui" name="dui"
                        class="input-field w-full focus:ring-indigo-500 focus:border-indigo-500"
                        placeholder="Ej: 12345678-9"
                        value="<?php echo htmlspecialchars($editingUser['dui'] ?? $_POST['dui'] ?? ''); ?>">
                </div>

                <div>
                    <label for="nie" class="block text-sm font-medium text-gray-700 mb-1">N° de identidad extranjero NIE (Opcional)</label>
                    <input type="text" id="nie" name="nie"
                        class="input-field w-full focus:ring-indigo-500 focus:border-indigo-500"
                        placeholder="Ej: abcde12345"
                        value="<?php echo htmlspecialchars($editingUser['nie'] ?? $_POST['nie'] ?? ''); ?>">
                </div>

                <div>
                    <label for="password" class="block text-sm font-medium text-gray-700 mb-1">Contraseña <?php echo $editingUser ? '(dejar en blanco para no cambiar)' : ''; ?></label>
                    <input type="password" id="password" name="password" <?php echo $editingUser ? '' : 'required'; ?>
                        class="input-field w-full focus:ring-indigo-500 focus:border-indigo-500"
                        placeholder="<?php echo $editingUser ? 'Nueva contraseña (opcional)' : 'Mínimo 6 caracteres'; ?>">
                </div>

                <div>
                    <label for="confirm_password" class="block text-sm font-medium text-gray-700 mb-1">Confirmar Contraseña <?php echo $editingUser ? '(dejar en blanco para no cambiar)' : ''; ?></label>
                    <input type="password" id="confirm_password" name="confirm_password" <?php echo $editingUser ? '' : 'required'; ?>
                        class="input-field w-full focus:ring-indigo-500 focus:border-indigo-500"
                        placeholder="<?php echo $editingUser ? 'Repite nueva contraseña (opcional)' : 'Repite la contraseña'; ?>">
                </div>

                <div>
                    <label for="type" class="block text-sm font-medium text-gray-700 mb-1">Tipo de Usuario</label>
                    <select id="type" name="type" required
                        class="input-field w-full focus:ring-indigo-500 focus:border-indigo-500">
                        <?php foreach ($userTypes as $value => $label): ?>
                            <option value="<?php echo $value; ?>"
                                <?php
                                $selectedValue = $editingUser['type'] ?? $_POST['type'] ?? '';
                                if ($selectedValue !== '' && $selectedValue == $value) {
                                    echo 'selected';
                                }
                                ?>>
                                <?php echo $label; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div>
                    <label for="status" class="block text-sm font-medium text-gray-700 mb-1">Estado</label>
                    <select id="status" name="status" required
                        class="input-field w-full focus:ring-indigo-500 focus:border-indigo-500">
                        <?php foreach ($userStatuses as $value => $label): ?>
                            <option value="<?php echo $value; ?>"
                                <?php
                                $selectedValue = $editingUser['status'] ?? $_POST['status'] ?? '';
                                if ($selectedValue !== '' && $selectedValue == $value) {
                                    echo 'selected';
                                }
                                ?>>
                                <?php echo $label; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="md:col-span-2 text-right flex justify-between items-center">
                    <?php if ($editingUser): ?>
                        <a href="/login_biblioteca/manage_users.php" class="action-button bg-gray-500 hover:bg-gray-600">Cancelar Edición</a>
                    <?php endif; ?>
                    <button type="submit" class="submit-button ml-auto">
                        <?php echo $editingUser ? 'Actualizar Usuario' : 'Crear Usuario'; ?>
                    </button>
                </div>
            </form>
        </div>

        <!-- Sección para Listar Usuarios -->
        <div class="p-6 border border-gray-200 rounded-lg bg-white">
            <h2 class="text-2xl font-semibold text-gray-800 mb-4">Lista de Usuarios</h2>
            <?php if (empty($users)): ?>
                <p class="text-gray-600 text-center">No hay usuarios registrados.</p>
            <?php else: ?>
                <div class="overflow-x-auto">
                    <table>
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Nombre</th>
                                <th>Email</th>
                                <th>Teléfono</th>
                                <th>Tipo</th>
                                <th>Estado</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($users as $user): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($user['id']); ?></td>
                                    <td><?php echo htmlspecialchars($user['name'] . ' ' . $user['last_name']); ?></td>
                                    <td><?php echo htmlspecialchars($user['email']); ?></td>
                                    <td><?php echo htmlspecialchars($user['phone']); ?></td>
                                    <td><?php echo htmlspecialchars($userTypes[$user['type']] ?? 'Desconocido'); ?></td>
                                    <td><?php echo htmlspecialchars($userStatuses[$user['status']] ?? 'Desconocido'); ?></td>
                                    <td class="whitespace-nowrap">
                                        <a href="/login_biblioteca/manage_users.php?action=edit&id=<?php echo $user['id']; ?>" class="action-button edit-button mr-2">Editar</a>
                                        <form action="manage_users.php" method="POST" class="inline-block" onsubmit="return confirm('¿Estás seguro de que quieres eliminar a <?php echo htmlspecialchars($user['name']); ?>? Esta acción es irreversible.');">
                                            <input type="hidden" name="action" value="delete_user">
                                            <input type="hidden" name="user_id" value="<?php echo htmlspecialchars($user['id']); ?>">
                                            <button type="submit" class="action-button delete-button">Eliminar</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </main>

    <footer class="w-full bg-gray-800 text-white text-center p-4 mt-8">
        <p>&copy; <?php echo date('Y'); ?> Biblioteca Todos los derechos reservados.</p>
    </footer>

</body>

</html>