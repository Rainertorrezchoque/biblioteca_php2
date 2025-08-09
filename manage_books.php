<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Administrar Libros - Biblioteca</title>

    <!-- Enlace a Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>

    <!-- Estilos personalizados para un diseño moderno y consistente -->
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

    // Redirige al login si el usuario no está autenticado
    if (!isset($_SESSION['user_id'])) {
        header('Location: /login_biblioteca/login.php');
        exit();
    }

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

    // Obtener los datos del usuario actual
    $currentUser = null;
    if (isset($_SESSION['user_id'])) {
        try {
            $stmt = $pdo->prepare("SELECT type FROM users WHERE id = :id LIMIT 1");
            $stmt->execute([':id' => $_SESSION['user_id']]);
            $currentUser = $stmt->fetch();
        } catch (PDOException $e) {
            error_log("Error al obtener datos del usuario actual en manage_books: " . $e->getMessage());
            header('Location: /login_biblioteca/login.php');
            exit();
        }
    }

    // Si el usuario no es administrador (0) o bibliotecario (1), redirigir
    if (!$currentUser || ($currentUser['type'] != 0 && $currentUser['type'] != 1)) {
        header('Location: /login_biblioteca/dashboard.php');
        exit();
    }

    // Mapeo de estados de libros (ajustado a tu columna 'status' en la tabla books)
    $statusMap = [
        0 => 'Disponible',
        1 => 'Prestado',
        2 => 'Reservado',
        3 => 'Mantenimiento'
    ];

    // Variables para mensajes y datos del libro a editar
    $message = '';
    $messageType = '';
    $editingBook = null;

    // --- LÓGICA DE PROCESAMIENTO DE ACCIONES (Crear, Editar, Eliminar) ---

    // 1. Manejar eliminación de libro
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete_book') {
        $bookIdToDelete = $_POST['book_id'] ?? null;
        if ($bookIdToDelete && is_numeric($bookIdToDelete)) {
            try {
                $stmt = $pdo->prepare("DELETE FROM books WHERE id = :id");
                if ($stmt->execute([':id' => $bookIdToDelete])) {
                    $message = 'Libro eliminado exitosamente.';
                    $messageType = 'success';
                } else {
                    $message = 'Error al eliminar el libro.';
                    $messageType = 'error';
                }
            } catch (PDOException $e) {
                error_log("Error al eliminar libro: " . $e->getMessage());
                $message = 'Error en la base de datos al eliminar libro.';
                $messageType = 'error';
            }
        } else {
            $message = 'ID de libro no válido para eliminar.';
            $messageType = 'error';
        }
        header('Location: /login_biblioteca/manage_books.php?msg=' . urlencode($message) . '&type=' . $messageType);
        exit();
    }

    // 2. Manejar carga de datos para edición (GET)
    if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action']) && $_GET['action'] === 'edit') {
        $bookIdToEdit = $_GET['id'] ?? null;
        if ($bookIdToEdit && is_numeric($bookIdToEdit)) {
            try {
                $stmt = $pdo->prepare("SELECT id, code, title, isbn, publication_year, publisher_id, category_id, status, edition, total_copies, location FROM books WHERE id = :id LIMIT 1");
                $stmt->execute([':id' => $bookIdToEdit]);
                $editingBook = $stmt->fetch();
                if (!$editingBook) {
                    $message = 'Libro no encontrado para editar.';
                    $messageType = 'error';
                }
            } catch (PDOException $e) {
                error_log("Error al cargar libro para editar: " . $e->getMessage());
                $message = 'Error en la base de datos al cargar libro para editar.';
                $messageType = 'error';
            }
        } else {
            $message = 'ID de libro no válido para editar.';
            $messageType = 'error';
        }
    }

    // 3. Manejar el envío del formulario de edición (POST)
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_book') {
        $bookId = $_POST['book_id'] ?? null;
        $code = trim($_POST['code'] ?? '');
        $title = trim($_POST['title'] ?? '');
        $isbn = trim($_POST['isbn'] ?? '');
        $edition = trim($_POST['edition'] ?? '');
        $publicationYear = trim($_POST['publication_year'] ?? '');
        $publisherId = $_POST['publisher_id'] ?? '';
        $categoryId = $_POST['category_id'] ?? '';
        $totalCopies = trim($_POST['total_copies'] ?? '');
        $location = trim($_POST['location'] ?? '');
        $status = $_POST['status'] ?? '';

        if (!$bookId || !is_numeric($bookId)) {
            $message = 'ID de libro no válido para actualizar.';
            $messageType = 'error';
        } elseif (empty($code) || empty($title) || empty($isbn) || empty($edition) || empty($publicationYear) || $publisherId === '' || $categoryId === '' || $totalCopies === '' || empty($location) || $status === '') {
            $message = 'Todos los campos obligatorios deben ser completados.';
            $messageType = 'error';
        } elseif (!is_numeric($publicationYear) || strlen($publicationYear) != 4) {
            $message = 'El año de publicación debe ser un año válido de 4 dígitos.';
            $messageType = 'error';
        } else {
            try {
                $stmt = $pdo->prepare("SELECT COUNT(*) FROM books WHERE isbn = :isbn AND id != :id");
                $stmt->execute([':isbn' => $isbn, ':id' => $bookId]);
                if ($stmt->fetchColumn() > 0) {
                    $message = 'El ISBN ya está registrado para otro libro.';
                    $messageType = 'error';
                } else {
                    $sql = "UPDATE books SET code = :code, title = :title, isbn = :isbn, edition = :edition, publication_year = :publication_year, publisher_id = :publisher_id, category_id = :category_id, total_copies = :total_copies, location = :location, status = :status WHERE id = :id";
                    $params = [
                        ':code' => $code,
                        ':title' => $title,
                        ':isbn' => $isbn,
                        ':edition' => $edition,
                        ':publication_year' => $publicationYear,
                        ':publisher_id' => $publisherId,
                        ':category_id' => $categoryId,
                        ':total_copies' => $totalCopies,
                        ':location' => $location,
                        ':status' => $status,
                        ':id' => $bookId
                    ];
                    $stmt = $pdo->prepare($sql);
                    $success = $stmt->execute($params);

                    if ($success) {
                        $message = '¡Libro actualizado exitosamente!';
                        $messageType = 'success';
                        $editingBook = null;
                        header('Location: /login_biblioteca/manage_books.php?msg=' . urlencode($message) . '&type=' . $messageType);
                        exit();
                    } else {
                        $message = 'Ocurrió un error al actualizar el libro. Inténtalo de nuevo.';
                        $messageType = 'error';
                    }
                }
            } catch (PDOException $e) {
                error_log("Error al actualizar libro: " . $e->getMessage());
                $message = 'Error en la base de datos al actualizar libro. Por favor, inténtalo más tarde.';
                $messageType = 'error';
            }
        }
    }

    // 4. Manejar el envío del formulario de creación (POST)
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'create_book' && !$editingBook) {
        $code = trim($_POST['code'] ?? '');
        $title = trim($_POST['title'] ?? '');
        $isbn = trim($_POST['isbn'] ?? '');
        $edition = trim($_POST['edition'] ?? '');
        $publicationYear = trim($_POST['publication_year'] ?? '');
        $publisherId = $_POST['publisher_id'] ?? '';
        $categoryId = $_POST['category_id'] ?? '';
        $totalCopies = trim($_POST['total_copies'] ?? '');
        $location = trim($_POST['location'] ?? '');
        $status = $_POST['status'] ?? '';
        

        if (empty($code) || empty($title) || empty($isbn) || empty($edition) || empty($publicationYear) || $publisherId === '' || $categoryId === '' || $totalCopies === '' || empty($location) || $status === '') {
            $message = 'Todos los campos obligatorios deben ser completados.';
            $messageType = 'error';
        } elseif (!is_numeric($publicationYear) || strlen($publicationYear) != 4) {
            $message = 'El año de publicación debe ser un año válido de 4 dígitos.';
            $messageType = 'error';
        } else {
            try {
                $stmt = $pdo->prepare("SELECT COUNT(*) FROM books WHERE isbn = :isbn");
                $stmt->execute([':isbn' => $isbn]);
                if ($stmt->fetchColumn() > 0) {
                    $message = 'El ISBN ya está registrado para otro libro.';
                    $messageType = 'error';
                } else {
                    $sql = "INSERT INTO books (code, title, isbn, edition, publication_year, publisher_id, category_id, total_copies, location, status) VALUES (:code, :title, :isbn, :edition, :publication_year, :publisher_id, :category_id, :total_copies, :location, :status)";
                    $stmt = $pdo->prepare($sql);
                    $success = $stmt->execute([
                        ':code' => $code,
                        ':title' => $title,
                        ':isbn' => $isbn,
                        ':edition' => $edition,
                        ':publication_year' => $publicationYear,
                        ':publisher_id' => $publisherId,
                        ':category_id' => $categoryId,
                        ':total_copies' => $totalCopies,
                        ':location' => $location,
                        ':status' => $status
                    ]);

                    if ($success) {
                        $message = '¡Libro creado exitosamente!';
                        $messageType = 'success';
                        // Redireccionamos para evitar el reenvío del formulario y limpiar los campos
                        header('Location: /login_biblioteca/manage_books.php?msg=' . urlencode($message) . '&type=' . $messageType);
                        exit();
                    } else {
                        $message = 'Ocurrió un error al crear el libro. Inténtalo de nuevo.';
                        $messageType = 'error';
                    }
                }
            } catch (PDOException $e) {
                error_log("Error al crear libro: " . $e->getMessage());
                $message = 'Error en la base de datos al crear libro. Por favor, inténtalo más tarde.';
                $messageType = 'error';
            }
        }
    }

    if (isset($_GET['msg']) && isset($_GET['type'])) {
        $message = htmlspecialchars($_GET['msg']);
        $messageType = htmlspecialchars($_GET['type']);
    }

    // --- OBTENER DATOS PARA LOS DESPLEGABLES ---
    $categories = [];
    $publishers = [];
    try {
        $categories = $pdo->query("SELECT id, name FROM categories ORDER BY name ASC")->fetchAll();
        $publishers = $pdo->query("SELECT id, name FROM publishers ORDER BY name ASC")->fetchAll();
    } catch (PDOException $e) {
        error_log("Error al cargar categorías, editoriales: " . $e->getMessage());
        $message = "Error al cargar datos para los menús desplegables.";
        $messageType = 'error';
    }


    // --- LÓGICA PARA LISTAR LIBROS ---
    $books = [];
    try {
        // Consulta corregida para incluir todos los campos solicitados y ordenar por 'code'
        $sql = "SELECT b.id, b.code, b.title, b.edition, b.isbn, b.publication_year, b.total_copies, b.location, b.status, c.name AS category_name, p.name AS publisher_name FROM books b LEFT JOIN categories c ON b.category_id = c.id LEFT JOIN publishers p ON b.publisher_id = p.id ORDER BY b.code ASC";
        $stmt = $pdo->query($sql);
        $books = $stmt->fetchAll();
    } catch (PDOException $e) {
        error_log("Error al listar libros: " . $e->getMessage());
        if (empty($message)) {
            $message = 'Error al cargar la lista de libros.';
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
            Administrar Libros
        </h1>

        <?php if ($message): ?>
            <p class="message <?php echo $messageType === 'error' ? 'error-message' : 'success-message'; ?> mb-6"><?php echo $message; ?></p>
        <?php endif; ?>

        <!-- Sección para Crear/Editar Libro -->
        <div class="mb-8 p-6 border border-gray-200 rounded-lg bg-gray-50">
            <h2 class="text-2xl font-semibold text-gray-800 mb-4">
                <?php echo $editingBook ? 'Editar Libro' : 'Crear Nuevo Libro'; ?>
            </h2>
            <form action="manage_books.php" method="POST" class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <input type="hidden" name="action" value="<?php echo $editingBook ? 'update_book' : 'create_book'; ?>">
                <?php if ($editingBook): ?>
                    <input type="hidden" name="book_id" value="<?php echo htmlspecialchars($editingBook['id']); ?>">
                <?php endif; ?>

                <div>
                    <label for="code" class="block text-sm font-medium text-gray-700 mb-1">Código</label>
                    <input type="text" id="code" name="code" required
                        class="input-field w-full focus:ring-indigo-500 focus:border-indigo-500"
                        value="<?php echo htmlspecialchars($editingBook['code'] ?? $_POST['code'] ?? ''); ?>">
                </div>

                <div>
                    <label for="title" class="block text-sm font-medium text-gray-700 mb-1">Título</label>
                    <input type="text" id="title" name="title" required
                        class="input-field w-full focus:ring-indigo-500 focus:border-indigo-500"
                        value="<?php echo htmlspecialchars($editingBook['title'] ?? $_POST['title'] ?? ''); ?>">
                </div>
                
                <div>
                    <label for="isbn" class="block text-sm font-medium text-gray-700 mb-1">ISBN</label>
                    <input type="text" id="isbn" name="isbn" required
                        class="input-field w-full focus:ring-indigo-500 focus:border-indigo-500"
                        placeholder="Ej: 978-0-321-76572-3"
                        value="<?php echo htmlspecialchars($editingBook['isbn'] ?? $_POST['isbn'] ?? ''); ?>">
                </div>

                <div>
                    <label for="edition" class="block text-sm font-medium text-gray-700 mb-1">Edición</label>
                    <input type="text" id="edition" name="edition" required
                        class="input-field w-full focus:ring-indigo-500 focus:border-indigo-500"
                        value="<?php echo htmlspecialchars($editingBook['edition'] ?? $_POST['edition'] ?? ''); ?>">
                </div>

                <div>
                    <label for="publication_year" class="block text-sm font-medium text-gray-700 mb-1">Año de Publicación</label>
                    <input type="number" id="publication_year" name="publication_year" required
                        class="input-field w-full focus:ring-indigo-500 focus:border-indigo-500"
                        placeholder="Ej: 2023"
                        value="<?php echo htmlspecialchars($editingBook['publication_year'] ?? $_POST['publication_year'] ?? ''); ?>">
                </div>
                
                <div>
                    <label for="publisher_id" class="block text-sm font-medium text-gray-700 mb-1">Editorial</label>
                    <select id="publisher_id" name="publisher_id" required
                        class="input-field w-full focus:ring-indigo-500 focus:border-indigo-500">
                        <option value="" disabled <?php echo empty($editingBook) && empty($_POST['publisher_id']) ? 'selected' : ''; ?>>Selecciona una editorial</option>
                        <?php foreach ($publishers as $publisher): ?>
                            <option value="<?php echo htmlspecialchars($publisher['id']); ?>"
                                <?php echo (isset($editingBook['publisher_id']) && $editingBook['publisher_id'] == $publisher['id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($publisher['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div>
                    <label for="category_id" class="block text-sm font-medium text-gray-700 mb-1">Categoría</label>
                    <select id="category_id" name="category_id" required
                        class="input-field w-full focus:ring-indigo-500 focus:border-indigo-500">
                        <option value="" disabled <?php echo empty($editingBook) && empty($_POST['category_id']) ? 'selected' : ''; ?>>Selecciona una categoría</option>
                        <?php foreach ($categories as $category): ?>
                            <option value="<?php echo htmlspecialchars($category['id']); ?>"
                                <?php echo (isset($editingBook['category_id']) && $editingBook['category_id'] == $category['id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($category['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div>
                    <label for="total_copies" class="block text-sm font-medium text-gray-700 mb-1">Copias Totales</label>
                    <input type="number" id="total_copies" name="total_copies" required
                        class="input-field w-full focus:ring-indigo-500 focus:border-indigo-500"
                        value="<?php echo htmlspecialchars($editingBook['total_copies'] ?? $_POST['total_copies'] ?? '0'); ?>">
                </div>

                <div>
                    <label for="location" class="block text-sm font-medium text-gray-700 mb-1">Ubicación</label>
                    <input type="text" id="location" name="location" required
                        class="input-field w-full focus:ring-indigo-500 focus:border-indigo-500"
                        value="<?php echo htmlspecialchars($editingBook['location'] ?? $_POST['location'] ?? ''); ?>">
                </div>

                <div>
                    <label for="status" class="block text-sm font-medium text-gray-700 mb-1">Estado</label>
                    <select id="status" name="status" required
                        class="input-field w-full focus:ring-indigo-500 focus:border-indigo-500">
                        <option value="" disabled <?php echo empty($editingBook) && empty($_POST['status']) ? 'selected' : ''; ?>>Selecciona un estado</option>
                        <?php foreach ($statusMap as $value => $name): ?>
                            <option value="<?php echo htmlspecialchars($value); ?>"
                                <?php echo (isset($editingBook['status']) && $editingBook['status'] == $value) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($name); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>


                <div class="md:col-span-2 text-right flex justify-between items-center mt-4">
                    <?php if ($editingBook): ?>
                        <a href="/login_biblioteca/manage_books.php" class="action-button bg-gray-500 hover:bg-gray-600">Cancelar Edición</a>
                    <?php endif; ?>
                    <button type="submit" class="submit-button ml-auto">
                        <?php echo $editingBook ? 'Actualizar Libro' : 'Crear Libro'; ?>
                    </button>
                </div>
            </form>
        </div>

        <!-- Lista de Libros Existentes -->
        <div class="container-card p-6">
            <h2 class="text-2xl font-bold mb-4 text-purple-700">Listado de Libros</h2>
            <?php if (count($books) > 0): ?>
                <div class="overflow-x-auto">
                    <table class="min-w-full bg-white rounded-lg overflow-hidden">
                        <thead class="bg-gray-200">
                            <tr>
                                <th class="py-2 px-4 border-b">Código</th>
                                <th class="py-2 px-4 border-b">Título</th>
                                <th class="py-2 px-4 border-b">Editorial</th>
                                <th class="py-2 px-4 border-b">Edición</th>
                                <th class="py-2 px-4 border-b">Año</th>
                                <th class="py-2 px-4 border-b">Categoría</th>
                                <th class="py-2 px-4 border-b">ISBN</th>
                                <th class="py-2 px-4 border-b">Copias</th>
                                <th class="py-2 px-4 border-b">Ubicación</th>
                                <th class="py-2 px-4 border-b">Estado</th>
                                <th class="py-2 px-4 border-b">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($books as $book): ?>
                                <tr>
                                    <td><span class="table-cell-content"><?php echo htmlspecialchars($book['code']); ?></span></td>
                                    <td class="py-2 px-4 border-b"><span class="table-cell-content"><?php echo htmlspecialchars($book['title']); ?></span></td>
                                    <td class="py-2 px-4 border-b"><span class="table-cell-content"><?php echo htmlspecialchars($book['publisher_name'] ?? 'Desconocida'); ?></span></td>
                                    <td class="py-2 px-4 border-b"><span class="table-cell-content"><?php echo htmlspecialchars($book['edition']); ?></span></td>
                                    <td class="py-2 px-4 border-b"><span class="table-cell-content"><?php echo htmlspecialchars($book['publication_year']); ?></span></td>
                                    <td class="py-2 px-4 border-b"><span class="table-cell-content"><?php echo htmlspecialchars($book['category_name'] ?? 'Desconocida'); ?></span></td>
                                    <td class="py-2 px-4 border-b"><span class="table-cell-content"><?php echo htmlspecialchars($book['isbn']); ?></span></td>
                                    <td class="py-2 px-4 border-b"><span class="table-cell-content"><?php echo htmlspecialchars($book['total_copies']); ?></span></td>
                                    <td class="py-2 px-4 border-b"><span class="table-cell-content"><?php echo htmlspecialchars($book['location']); ?></span></td>
                                    <td class="py-2 px-4 border-b"><span class="table-cell-content"><?php echo htmlspecialchars($statusMap[$book['status']] ?? 'Desconocido'); ?></span></td>
                                    <td>
                                        <a href="/login_biblioteca/manage_books.php?action=edit&id=<?php echo $book['id']; ?>" class="action-button edit-button mr-2">Editar</a>
                                        <form action="manage_books.php" method="POST" class="inline-block" onsubmit="return confirm('¿Estás seguro de que quieres eliminar el libro \'<?php echo htmlspecialchars($book['title']); ?>\'? Esta acción es irreversible.');">
                                            <input type="hidden" name="action" value="delete_book">
                                            <input type="hidden" name="book_id" value="<?php echo htmlspecialchars($book['id']); ?>">
                                            <button type="submit" class="action-button delete-button">Eliminar</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <p class="text-gray-600 text-center">No hay libros registrados.</p>
            <?php endif; ?>
        </div>
    </main>

    <footer class="w-full bg-gray-800 text-white text-center p-4 mt-8">
        <p>&copy; <?php echo date('Y'); ?> Biblioteca Todos los derechos reservados.</p>
    </footer>

</body>

</html>
