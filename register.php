<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registrarse - Biblioteca</title>
    <!-- Carga de Tailwind CSS para estilos rápidos y responsivos -->
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        
        body {
            font-family: 'Inter', sans-serif;
            background-color: #f0f2f5;
        }
        .register-card {
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
    </style>
</head>
<body class="flex items-center justify-center min-h-screen py-8">

    <?php
    // Inicia la sesión 
    session_start();

    // --- CONFIGURACIÓN DE LA BASE DE DATOS ---
    $dbHost = 'localhost';
    $dbName = 'biblioteca1';
    $dbUser = 'root';
    $dbPass = '';

    // --- CONEXIÓN A LA BASE DE DATOS ---
    $pdo = null;
    try {
        $pdo = new PDO("mysql:host=$dbHost;dbname=$dbName;charset=utf8mb4", $dbUser, $dbPass);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        die("<div class='text-center text-red-500'>Error de conexión a la base de datos: " . $e->getMessage() . "</div>");
    }

    // --- FUNCIONES AUXILIARES ---

    /**
     * Hashea una contraseña usando el algoritmo recomendado (PASSWORD_BCRYPT).
     * @param string $password La contraseña en texto plano.
     * @return string La contraseña hasheada.
     */
    function hash_password($password) {
        return password_hash($password, PASSWORD_BCRYPT);
    }

    // --- LÓGICA DEL FORMULARIO ---
    $message = '';
    $messageType = ''; 

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        
        $dui = trim($_POST['dui'] ?? '');
        $nie = trim($_POST['nie'] ?? '');
        $name = trim($_POST['name'] ?? '');
        $lastName = trim($_POST['last_name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $phone = trim($_POST['phone'] ?? ''); 
        $password = $_POST['password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';
       
        $type = 3; 
        $status = 0; 
        $sectionId = null; 

        // Mensaje
        
        if (empty($name) || empty($lastName) || empty($email) || empty($phone) || empty($password) || empty($confirmPassword)) {
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
        } else {
            // Verificar datos duplicados
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
                        $message = '¡Registro exitoso! Ahora puedes iniciar sesión.';
                        $messageType = 'success';
                        
                        header('Location: /login_biblioteca/login.php?registered=true');
                        exit();
                    } else {
                        $message = 'Ocurrió un error al registrar el usuario. Inténtalo de nuevo.';
                        $messageType = 'error';
                    }
                }
            } catch (PDOException $e) {
                error_log("Error al registrar usuario: " . $e->getMessage());
                $message = 'Error en la base de datos al registrar. Por favor, inténtalo más tarde.';
                $messageType = 'error';
            }
        }
    }
    ?>

    <div class="register-card p-8 max-w-lg w-full">
        <h1 class="text-3xl font-bold text-center text-gray-800 mb-6">Crear Cuenta</h1>

        <?php if ($message): ?>
            <p class="message <?php echo $messageType === 'error' ? 'error-message' : 'success-message'; ?> mb-4"><?php echo $message; ?></p>
        <?php endif; ?>

        <form action="register.php" method="POST" class="space-y-4">
            <div>
                <label for="name" class="block text-sm font-medium text-gray-700 mb-1">Nombre</label>
                <input type="text" id="name" name="name" required
                       class="input-field w-full focus:ring-indigo-500 focus:border-indigo-500"
                       value="<?php echo htmlspecialchars($_POST['name'] ?? ''); ?>">
            </div>

            <div>
                <label for="last_name" class="block text-sm font-medium text-gray-700 mb-1">Apellido</label>
                <input type="text" id="last_name" name="last_name" required
                       class="input-field w-full focus:ring-indigo-500 focus:border-indigo-500"
                       value="<?php echo htmlspecialchars($_POST['last_name'] ?? ''); ?>">
            </div>

            <div>
                <label for="email" class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                <input type="email" id="email" name="email" required
                       class="input-field w-full focus:ring-indigo-500 focus:border-indigo-500"
                       placeholder="tu.email@example.com"
                       value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
            </div>

            <div>
                <label for="dui" class="block text-sm font-medium text-gray-700 mb-1">Carnet de identidad (CI o DUI) </label>
                <input type="text" id="dui" name="dui"
                       class="input-field w-full focus:ring-indigo-500 focus:border-indigo-500"
                       placeholder="Ej: 12345678-9"
                       value="<?php echo htmlspecialchars($_POST['dui'] ?? ''); ?>">
            </div>

            <div>
                <label for="nie" class="block text-sm font-medium text-gray-700 mb-1">N° de Identificacion Extrangero (NIE) (Opcional)</label>
                <input type="text" id="nie" name="nie"
                       class="input-field w-full focus:ring-indigo-500 focus:border-indigo-500"
                       placeholder="Ej: ABCDEF12345"
                       value="<?php echo htmlspecialchars($_POST['nie'] ?? ''); ?>">
            </div>

            <div> 
                <label for="phone" class="block text-sm font-medium text-gray-700 mb-1">Teléfono</label>
                <input type="text" id="phone" name="phone" required
                       class="input-field w-full focus:ring-indigo-500 focus:border-indigo-500"
                       placeholder="Ej: 7123-4567"
                       value="<?php echo htmlspecialchars($_POST['phone'] ?? ''); ?>">
            </div>

            <div>
                <label for="password" class="block text-sm font-medium text-gray-700 mb-1">Contraseña</label>
                <input type="password" id="password" name="password" required
                       class="input-field w-full focus:ring-indigo-500 focus:border-indigo-500"
                       placeholder="Mínimo 6 caracteres">
            </div>

            <div>
                <label for="confirm_password" class="block text-sm font-medium text-gray-700 mb-1">Confirmar Contraseña</label>
                <input type="password" id="confirm_password" name="confirm_password" required
                       class="input-field w-full focus:ring-indigo-500 focus:border-indigo-500"
                       placeholder="Repite tu contraseña">
            </div>

            <div>
                <button type="submit" class="submit-button w-full flex justify-center items-center">
                    Registrarse
                </button>
            </div>
        </form>

        <p class="text-center text-sm text-gray-600 mt-6">
            ¿Ya tienes una cuenta? <a href="/login_biblioteca/login.php" class="text-indigo-600 hover:text-indigo-800 font-medium">Inicia sesión aquí</a>
        </p>
    </div>

</body>
</html>
