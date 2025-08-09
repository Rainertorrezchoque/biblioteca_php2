<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard de Bibliotecario - Automatizado</title>
    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- Font Awesome CDN para iconos -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <!-- Firebase SDK -->
    <script type="module">
        import { initializeApp } from "https://www.gstatic.com/firebasejs/11.6.1/firebase-app.js";
        import { getAuth, signInWithCustomToken, signInAnonymously, onAuthStateChanged } from "https://www.gstatic.com/firebasejs/11.6.1/firebase-auth.js";
        import { getFirestore, collection, onSnapshot, addDoc, deleteDoc, doc, query, where, updateDoc } from "https://www.gstatic.com/firebasejs/11.6.1/firebase-firestore.js";

        // Variables globales del entorno
        const appId = typeof __app_id !== 'undefined' ? __app_id : 'default-app-id';
        const firebaseConfig = typeof __firebase_config !== 'undefined' ? JSON.parse(__firebase_config) : {};
        const authToken = typeof __initial_auth_token !== 'undefined' ? __initial_auth_token : '';

        // Inicializar Firebase
        const app = initializeApp(firebaseConfig);
        const auth = getAuth(app);
        const db = getFirestore(app);

        let currentUserId = '';

        // Autenticación de usuario
        onAuthStateChanged(auth, async (user) => {
            if (user) {
                currentUserId = user.uid;
                // Mostrar el ID del usuario en el UI
                const userIdDisplay = document.getElementById('user-id-display');
                if (userIdDisplay) {
                    userIdDisplay.textContent = `ID de Sesión: ${currentUserId}`;
                }
                console.log("Usuario autenticado:", currentUserId);
                // Iniciar la escucha de datos de Firestore
                startFirestoreListeners();
            } else {
                console.log("Usuario no autenticado, intentando iniciar sesión...");
                try {
                    if (authToken) {
                        await signInWithCustomToken(auth, authToken);
                    } else {
                        await signInAnonymously(auth);
                    }
                    console.log("Sesión iniciada correctamente.");
                } catch (error) {
                    console.error("Error al iniciar sesión:", error);
                    showMessage(`Error de autenticación: ${error.message}`, 'error');
                }
            }
        });

        // Colección de préstamos (pública)
        const loansCollectionPath = `artifacts/${appId}/public/data/loans`;

        // Datos de ejemplo para la simulación inicial
        let allLoans = [];

        // Función para renderizar la tabla de préstamos
        function renderLoansTable(loansToDisplay) {
            const tbody = document.getElementById('loans-table-body');
            tbody.innerHTML = '';
            if (loansToDisplay.length === 0) {
                tbody.innerHTML = '<tr><td colspan="7" class="text-center py-4 text-gray-500">No hay préstamos activos.</td></tr>';
                return;
            }

            loansToDisplay.forEach(loan => {
                const row = document.createElement('tr');
                row.classList.add('transition-colors', 'duration-200');
                const returnDateClass = loan.isOverdue ? 'text-red-600 font-semibold' : 'text-gray-600';
                row.innerHTML = `
                    <td class="py-4 px-6">${loan.id}</td>
                    <td class="py-4 px-6 text-gray-800">${loan.user}</td>
                    <td class="py-4 px-6 text-gray-600">${loan.userDui}</td>
                    <td class="py-4 px-6 text-gray-800">${loan.book}</td>
                    <td class="py-4 px-6 text-gray-600">${loan.bookCode}</td>
                    <td class="py-4 px-6 text-gray-600">${loan.loanDate}</td>
                    <td class="py-4 px-6 ${returnDateClass}">${loan.returnDate}</td>
                `;
                tbody.appendChild(row);
            });
        }

        // Función para actualizar las métricas clave
        function updateMetrics(loans) {
            const activeLoans = loans.length;
            const overdueLoans = loans.filter(loan => {
                const today = new Date();
                const returnDate = new Date(loan.returnDate);
                return returnDate < today;
            }).length;

            document.getElementById('metric-loans').textContent = activeLoans;
            document.getElementById('metric-overdue').textContent = overdueLoans;
        }

        // Escuchar cambios en la base de datos de Firestore
        function startFirestoreListeners() {
            const loansQuery = collection(db, loansCollectionPath);
            onSnapshot(loansQuery, (snapshot) => {
                allLoans = snapshot.docs.map(doc => ({ id: doc.id, ...doc.data() }));
                console.log("Datos de Firestore actualizados:", allLoans);
                renderLoansTable(allLoans);
                updateMetrics(allLoans);
            }, (error) => {
                console.error("Error al escuchar los cambios en Firestore:", error);
                showMessage(`Error de base de datos: ${error.message}`, 'error');
            });
        }

        // Función para mostrar mensajes en una caja de notificaciones
        function showMessage(message, type) {
            const container = document.getElementById('message-container');
            container.innerHTML = `<div class="message-box ${type}"><i class="fas fa-info-circle mr-2"></i>${message}</div>`;
            container.classList.remove('opacity-0');
            container.classList.add('opacity-100');
            setTimeout(() => {
                container.classList.remove('opacity-100');
                container.classList.add('opacity-0');
            }, 5000);
        }

        // Simula la lectura de un código de barras o QR
        window.simulateScan = function(inputFieldId) {
            const inputField = document.getElementById(inputFieldId);
            const dummyData = {
                'user_dui': '08765432-1',
                'book_copy_code': 'LIB-001-C01',
                'return_code': 'LIB-001-C01'
            };
            const scannedValue = dummyData[inputFieldId] || null;

            if (scannedValue) {
                inputField.value = scannedValue;
                if (inputFieldId === 'user_dui') {
                    showMessage('DUI escaneado. Usuario encontrado: Ana Pérez.', 'info');
                    document.getElementById('user-name').textContent = 'Ana Pérez';
                    document.getElementById('checkout-step-1').classList.add('hidden');
                    document.getElementById('checkout-step-2').classList.remove('hidden');
                } else {
                    showMessage('Código de ejemplar escaneado.', 'info');
                }
            } else {
                showMessage('Error al simular el escaneo.', 'error');
            }
        };

        // Lógica para registrar un nuevo préstamo
        document.getElementById('checkout-form').addEventListener('submit', async function(event) {
            event.preventDefault();
            const userDui = document.getElementById('user_dui').value;
            const bookCopyCode = document.getElementById('book_copy_code').value;
            
            if (!userDui || !bookCopyCode) {
                showMessage('Por favor, escanea o ingresa el DUI del usuario y el código del ejemplar.', 'error');
                return;
            }

            // Simular búsqueda de libro y usuario (en un entorno real esto sería una consulta)
            const today = new Date().toISOString().slice(0, 10);
            const returnDate = new Date();
            returnDate.setDate(returnDate.getDate() + 15);
            const returnDateString = returnDate.toISOString().slice(0, 10);

            try {
                // Añadir el documento a Firestore
                await addDoc(collection(db, loansCollectionPath), {
                    user: 'Ana Pérez', // Nombre simulado
                    userDui: userDui,
                    book: 'El Principito', // Título simulado
                    bookCode: bookCopyCode,
                    loanDate: today,
                    returnDate: returnDateString,
                    isOverdue: false,
                    // Otros campos relevantes
                });

                showMessage('¡Préstamo registrado exitosamente!', 'success');
                // Restablecer el formulario
                document.getElementById('user_dui').value = '';
                document.getElementById('book_copy_code').value = '';
                document.getElementById('checkout-step-2').classList.add('hidden');
                document.getElementById('checkout-step-1').classList.remove('hidden');

            } catch (error) {
                console.error("Error al registrar el préstamo:", error);
                showMessage(`Error al registrar el préstamo: ${error.message}`, 'error');
            }
        });

        // Lógica para registrar una devolución
        document.getElementById('return-form').addEventListener('submit', async function(event) {
            event.preventDefault();
            const returnCode = document.getElementById('return_code').value;

            if (!returnCode) {
                showMessage('Por favor, escanea o ingresa el código del ejemplar a devolver.', 'error');
                return;
            }

            // Buscar el préstamo activo en la base de datos
            const loanToReturn = allLoans.find(loan => loan.bookCode === returnCode);

            if (loanToReturn) {
                try {
                    await deleteDoc(doc(db, loansCollectionPath, loanToReturn.id));
                    showMessage(`¡Devolución de "${loanToReturn.book}" registrada exitosamente!`, 'success');
                    document.getElementById('return_code').value = '';
                } catch (error) {
                    console.error("Error al devolver el libro:", error);
                    showMessage(`Error al devolver el libro: ${error.message}`, 'error');
                }
            } else {
                showMessage(`No se encontró un préstamo activo para el código ${returnCode}.`, 'error');
            }
        });
        
        // Lógica para la búsqueda en la tabla
        document.getElementById('search-query').addEventListener('input', function(event) {
            const query = event.target.value.toLowerCase();
            const filteredLoans = allLoans.filter(loan =>
                loan.user.toLowerCase().includes(query) ||
                loan.userDui.toLowerCase().includes(query) ||
                loan.book.toLowerCase().includes(query) ||
                loan.bookCode.toLowerCase().includes(query)
            );
            renderLoansTable(filteredLoans);
        });

        // No es necesario llamar a renderLoansTable() o updateMetrics() aquí,
        // ya que el listener de onSnapshot lo hará automáticamente.
    </script>
    <style>
        /* Estilos personalizados para un mejor aspecto */
        body { font-family: 'Inter', sans-serif; background-color: #f0f2f5; }
        .container-card { background-color: #ffffff; border-radius: 1.5rem; box-shadow: 0 10px 15px rgba(0, 0, 0, 0.1), 0 4px 6px rgba(0, 0, 0, 0.05); }
        .nav-link { padding: 0.75rem 1.25rem; border-radius: 0.75rem; transition: background-color 0.3s ease; }
        .nav-link:hover { background-color: #d1d5db; color: #1f2937; }
        .logout-button { background-color: #ef4444; color: white; padding: 0.75rem 1.5rem; border-radius: 0.75rem; font-weight: 600; transition: background-color 0.3s ease; }
        .logout-button:hover { background-color: #dc2626; }
        .card { background: linear-gradient(135deg, #f3f4f6, #e5e7eb); border-radius: 1rem; padding: 1.5rem; box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05); border: 1px solid #d1d5db; }
        .card-title { font-size: 1.5rem; font-weight: 700; color: #1d4ed8; margin-bottom: 0.5rem; }
        .metric-value { font-size: 3rem; font-weight: 800; color: #3b82f6; }
        .metric-label { font-size: 1rem; color: #64748b; }
        .form-input { border-radius: 0.75rem; border: 1px solid #d1d5db; padding: 0.75rem 1rem; width: 100%; transition: border-color 0.3s, box-shadow 0.3s; }
        .form-input:focus { outline: none; border-color: #3b82f6; box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.2); }
        .action-button {
            background: linear-gradient(45deg, #10b981, #059669);
            color: white;
            padding: 0.75rem 1.5rem;
            border-radius: 0.75rem;
            font-weight: 600;
            transition: transform 0.2s, box-shadow 0.2s;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        .action-button:hover { transform: translateY(-2px); box-shadow: 0 6px 8px rgba(0, 0, 0, 0.15); }
        .return-button { background: linear-gradient(45deg, #f97316, #ea580c); }
        .scan-button {
            background: #6366f1;
            color: white;
            padding: 0.75rem 1rem;
            border-radius: 0.75rem;
            font-weight: 600;
            transition: background-color 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            margin-left: 0.5rem;
        }
        .scan-button:hover { background-color: #4338ca; }
        .message-box { padding: 1.25rem; margin-bottom: 1rem; border-radius: 1rem; animation: fadein 0.5s; font-weight: 600;}
        .success { background-color: #d1fae5; color: #065f46; border: 2px solid #34d399; }
        .error { background-color: #fee2e2; color: #991b1b; border: 2px solid #f87171; }
        .info { background-color: #dbeafe; color: #1e40af; border: 2px solid #60a5fa; }
        @keyframes fadein { from { opacity: 0; } to { opacity: 1; } }

        /* Estilos de tabla mejorados */
        .table-container { border-radius: 1rem; overflow: hidden; box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05); }
        table { width: 100%; border-collapse: separate; border-spacing: 0; }
        th, td { text-align: left; padding: 1rem; border-bottom: 1px solid #e5e7eb; }
        th { background-color: #e5e7eb; color: #374151; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em; font-size: 0.875rem; }
        tr:last-child td { border-bottom: none; }
        tbody tr:hover { background-color: #f9fafb; }
        @media (max-width: 768px) {
            .nav-link { padding: 0.5rem 0.75rem; }
            .metric-value { font-size: 2rem; }
            .metric-label { font-size: 0.875rem; }
        }
    </style>
</head>
<body class="flex flex-col items-center min-h-screen">

    <!-- Header -->
    <header class="w-full bg-blue-700 text-white p-4 shadow-md">
        <div class="container mx-auto flex flex-col md:flex-row justify-between items-center">
            <h2 class="text-3xl font-extrabold mb-2 md:mb-0">Panel de Bibliotecario</h2>
            <nav>
                <ul class="flex space-x-2 md:space-x-4">
                    <li><a href="#" class="nav-link text-white hover:bg-blue-800">Inicio</a></li>
                    <li><a href="#" class="nav-link text-white hover:bg-blue-800">Gestión de Libros</a></li>
                    <li><a href="#" class="nav-link text-white hover:bg-blue-800">Usuarios</a></li>
                    <li><button class="nav-link logout-button flex items-center">
                        <i class="fas fa-sign-out-alt mr-2"></i> Cerrar Sesión
                    </button></li>
                </ul>
            </nav>
        </div>
    </header>

    <!-- Main Content -->
    <main class="container mx-auto mt-8 p-6 w-full max-w-7xl container-card">
        <h1 class="text-4xl md:text-5xl font-extrabold text-gray-900 mb-4 text-center leading-tight">
            ¡Bienvenido, <span class="text-blue-700">Javier</span>!
        </h1>
        <p class="text-center text-gray-600 mb-8 max-w-xl mx-auto">
            Utiliza este panel para gestionar préstamos y devoluciones.
        </p>
        <p id="user-id-display" class="text-center text-gray-400 text-sm mb-4"></p>

        <!-- Mensajes del sistema -->
        <div id="message-container" class="opacity-0 transition-opacity duration-500">
            <!-- Los mensajes se mostrarán aquí -->
        </div>

        <!-- Métricas Clave (Valores actualizados dinámicamente) -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-12">
            <div class="card text-center">
                <p id="metric-loans" class="metric-value">0</p>
                <p class="metric-label">Préstamos Activos</p>
            </div>
            <div class="card text-center">
                <p id="metric-overdue" class="metric-value text-red-600">0</p>
                <p class="metric-label">Préstamos Atrasados</p>
            </div>
            <div class="card text-center">
                <p id="metric-users" class="metric-value">0</p>
                <p class="metric-label">Usuarios Registrados</p>
            </div>
        </div>

        <!-- Sección de Préstamo de Libro con flujo guiado -->
        <div class="mb-12 p-8 border border-gray-200 rounded-2xl bg-blue-50">
            <h2 class="text-2xl font-bold text-blue-700 mb-6 flex items-center">
                <i class="fas fa-hand-holding-usd mr-3"></i> Registrar Nuevo Préstamo
            </h2>
            <div id="checkout-step-1" class="mb-6">
                <label for="user_dui" class="block text-sm font-medium text-gray-700 mb-2">Paso 1: DUI del Usuario</label>
                <div class="flex items-center">
                    <input type="text" id="user_dui" name="user_dui" required class="form-input flex-grow" placeholder="Ej: 12345678-9">
                    <button type="button" class="scan-button" onclick="simulateScan('user_dui')">
                        <i class="fas fa-barcode"></i>
                    </button>
                </div>
            </div>
            <div id="checkout-step-2" class="hidden">
                <div id="user-info" class="p-4 bg-blue-200 rounded-lg mb-4">
                    <p class="text-blue-800 font-semibold"><i class="fas fa-user-circle mr-2"></i> Usuario: <span id="user-name"></span></p>
                </div>
                <label for="book_copy_code" class="block text-sm font-medium text-gray-700 mb-2">Paso 2: Código del Ejemplar</label>
                <form id="checkout-form">
                    <div class="flex items-center">
                        <input type="text" id="book_copy_code" name="book_copy_code" required class="form-input flex-grow" placeholder="Ej: LIB-001-C01">
                        <button type="button" class="scan-button" onclick="simulateScan('book_copy_code')">
                            <i class="fas fa-qrcode"></i>
                        </button>
                    </div>
                    <div class="mt-6 text-right">
                        <button type="submit" class="action-button">
                            <i class="fas fa-check-circle mr-2"></i> Confirmar Préstamo
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Sección de Devolución de Libro -->
        <div class="mb-12 p-8 border border-gray-200 rounded-2xl bg-orange-50">
            <h2 class="text-2xl font-bold text-orange-700 mb-6 flex items-center">
                <i class="fas fa-exchange-alt mr-3"></i> Registrar Devolución
            </h2>
            <form id="return-form">
                <label for="return_code" class="block text-sm font-medium text-gray-700 mb-2">Código del Ejemplar</label>
                <div class="flex items-center mb-6">
                    <input type="text" id="return_code" name="return_code" required class="form-input flex-grow" placeholder="Ej: LIB-001-C01">
                    <button type="button" class="scan-button" onclick="simulateScan('return_code')">
                        <i class="fas fa-barcode"></i>
                    </button>
                </div>
                <div class="text-right">
                    <button type="submit" class="action-button return-button">
                        <i class="fas fa-undo-alt mr-2"></i> Confirmar Devolución
                    </button>
                </div>
            </form>
        </div>

        <!-- Sección de Búsqueda -->
        <div class="mb-12 p-8 border border-gray-200 rounded-2xl bg-white">
            <h2 class="text-2xl font-bold text-gray-800 mb-6 flex items-center">
                <i class="fas fa-search mr-3"></i> Buscar Libros o Miembros
            </h2>
            <div class="flex flex-col md:flex-row gap-4">
                <input type="text" id="search-query" class="form-input" placeholder="Buscar por título, autor, o nombre del miembro...">
                <button type="button" class="action-button bg-gray-500 hover:bg-gray-700 md:w-auto" onclick="window.simulateSearch()">
                    <i class="fas fa-search mr-2"></i> Buscar
                </button>
            </div>
        </div>

        <!-- Sección de Préstamos Activos (Tabla dinámica) -->
        <div class="p-8 border border-gray-200 rounded-2xl bg-white">
            <h2 class="text-2xl font-bold text-gray-800 mb-6 flex items-center">
                <i class="fas fa-book-reader mr-3"></i> Préstamos Activos
            </h2>
            <div id="loans-table-container" class="overflow-x-auto table-container">
                <table class="min-w-full">
                    <thead>
                        <tr>
                            <th class="py-3 px-6">ID Préstamo</th>
                            <th class="py-3 px-6">Usuario</th>
                            <th class="py-3 px-6">DUI Usuario</th>
                            <th class="py-3 px-6">Libro</th>
                            <th class="py-3 px-6">Cód. Ejemplar</th>
                            <th class="py-3 px-6">Fecha Préstamo</th>
                            <th class="py-3 px-6">Fecha Esperada</th>
                        </tr>
                    </thead>
                    <tbody id="loans-table-body">
                        <!-- Las filas se llenarán con JavaScript -->
                    </tbody>
                </table>
            </div>
        </div>
    </main>

    <!-- Footer -->
    <footer class="w-full bg-gray-800 text-white text-center p-4 mt-8">
        <p>&copy; 2025 Biblioteca Automatizada. Todos los derechos reservados.</p>
    </footer>
</body>
</html>
