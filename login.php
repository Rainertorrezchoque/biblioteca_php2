<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Iniciar Sesión - Biblioteca</title>
    
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        
        body {
            font-family: 'Inter', sans-serif;
            background-color: #f0f2f5; /*fondo */
        }
        .login-card {
            background-color: #ffffff;
            border-radius: 1rem; /* bordes redondeados */
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1), 0 1px 3px rgba(0, 0, 0, 0.08); /* Sombra suave */
        }
        .input-field {
            border-radius: 0.5rem; /* bordes inputs */
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
        .error-message {
            color: #ef4444; 
            font-size: 0.875rem;
            margin-top: 0.5rem;
        }
    </style>
</head>
<body class="flex items-center justify-center min-h-screen">

    <?php
    
    session_start();

   
    
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

    

    /**
     * Hashea una contraseña usando el algoritmo recomendado (PASSWORD_BCRYPT).
     * @param string $password La contraseña en texto plano.
     * @return string La contraseña hasheada.
     */
    function hash_password($password) {
        return password_hash($password, PASSWORD_BCRYPT);
    }

    /**
     * Autentica un usuario por email y contraseña.
     *
     * @param PDO $pdo Instancia de la conexión PDO.
     * @param string $email El email del usuario.
     * @param string $password La contraseña en texto plano.
     * @return array|false Los datos del usuario si la autenticación es exitosa, o false.
     */
    function authenticate_user($pdo, $email, $password) {
        $sql = "SELECT * FROM users WHERE email = :email LIMIT 1";
        try {
            $stmt = $pdo->prepare($sql);
            $stmt->execute([':email' => $email]);
            $user = $stmt->fetch();

            // Verifica contraseña
            if ($user && password_verify($password, $user['password'])) {
                
                unset($user['password']);
                return $user;
            }
            return false;
        } catch (PDOException $e) {
            
            error_log("Error al autenticar usuario: " . $e->getMessage());
            return false;
        }
    }

    
    $errorMessage = ''; 
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $email = $_POST['email'] ?? '';
        $password = $_POST['password'] ?? '';

        if (empty($email) || empty($password)) {
            $errorMessage = 'Por favor, introduce tu email y contraseña.';
        } else {
            $user = authenticate_user($pdo, $email, $password);

            if ($user) {
                
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_name'] = $user['name'];
                $_SESSION['user_type'] = $user['type']; 

                // Redirigir al usuario
                header('Location: dashboard.php'); 
                exit();
            } else {
                
                $errorMessage = 'Email o contraseña incorrectos.';
            }
        }
    }
    ?>

    <div class="login-card p-8 max-w-md w-full">
        <h1 class="text-3xl font-bold text-center text-gray-800 mb-6">Iniciar Sesión</h1>

        <?php if ($errorMessage): ?>
            <p class="error-message text-center mb-4"><?php echo $errorMessage; ?></p>
        <?php endif; ?>

        <form action="login.php" method="POST" class="space-y-6">
            <div>
                <label for="email" class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                <input type="email" id="email" name="email" required
                       class="input-field w-full focus:ring-indigo-500 focus:border-indigo-500"
                       placeholder="tu.email@example.com"
                       value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
            </div>

            <div>
                <label for="password" class="block text-sm font-medium text-gray-700 mb-1">Contraseña</label>
                <input type="password" id="password" name="password" required
                       class="input-field w-full focus:ring-indigo-500 focus:border-indigo-500"
                       placeholder="Tu contraseña">
            </div>

            <div>
                <button type="submit" class="submit-button w-full flex justify-center items-center">
                    Entrar
                </button>
            </div>
        </form>

        <p class="text-center text-sm text-gray-600 mt-6">
            ¿No tienes una cuenta? <a href="register.php" class="text-indigo-600 hover:text-indigo-800 font-medium">Regístrate aquí</a>
        </p>
    </div>

</body>
</html>
