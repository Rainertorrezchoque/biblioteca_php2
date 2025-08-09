<?php


header('Content-Type: application/json');


$dbHost = 'localhost';
$dbName = 'biblioteca1';
$dbUser = 'root';
$dbPass = '';

$pdo = null;
$response = ['success' => false, 'data' => [], 'errorMessage' => ''];

try {
    
    $pdo = new PDO("mysql:host=$dbHost;dbname=$dbName;charset=utf8mb4", $dbUser, $dbPass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

    // 1. Obtener contadores
    $stmt_users = $pdo->query("SELECT COUNT(*) FROM users");
    $totalUsers = $stmt_users->fetchColumn();

    $stmt_books = $pdo->query("SELECT COUNT(*) FROM books");
    $totalBooks = $stmt_books->fetchColumn();

    
    // Por ahora, usamos queries de ejemplo.
    $stmt_loans = $pdo->query("SELECT COUNT(*) FROM loans WHERE status = 'activo'");
    $activeLoans = $stmt_loans->fetchColumn();

    $stmt_reservations = $pdo->query("SELECT COUNT(*) FROM reservations WHERE status = 'pendiente'");
    $pendingReservations = $stmt_reservations->fetchColumn();

    
    // Deberias tener una tabla de `activity_log` o `events` para esto.
    $recentActivity = [
        ['date' => '2025-07-31', 'text' => 'El administrador actualizó los datos de un libro.'],
        ['date' => '2025-07-30', 'text' => 'Se registró un nuevo usuario: Ana García.'],
        ['date' => '2025-07-29', 'text' => 'Se realizó un préstamo a Juan Pérez.'],
    ];

    
    // Esto podria ser generado por consultas a la base de datos.
    $importantAlerts = [
        '5 préstamos con más de 7 días de atraso.',
        '2 libros marcados como perdidos pendientes de revisión.',
        '¡Recuerda hacer la copia de seguridad semanal!',
    ];

    $response['success'] = true;
    $response['data'] = [
        'totalUsers' => $totalUsers,
        'totalBooks' => $totalBooks,
        'activeLoans' => $activeLoans,
        'pendingReservations' => $pendingReservations,
        'recentActivity' => $recentActivity,
        'importantAlerts' => $importantAlerts,
    ];

} catch (PDOException $e) {
    $response['errorMessage'] = "Error de base de datos: " . $e->getMessage();
}


echo json_encode($response);
?>
