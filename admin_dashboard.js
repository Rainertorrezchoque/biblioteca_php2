// Este script se encarga de realizar peticiones al servidor para
// obtener los datos del dashboard y actualizar la interfaz de usuario.

document.addEventListener('DOMContentLoaded', () => {

    const totalUsersElement = document.getElementById('total-users');
    const totalBooksElement = document.getElementById('total-books');
    const activeLoansElement = document.getElementById('active-loans');
    const pendingReservationsElement = document.getElementById('pending-reservations');
    const recentActivityList = document.getElementById('recent-activity');
    const importantAlertsList = document.getElementById('important-alerts');

    // Función para obtener y actualizar los datos del dashboard
    async function fetchDashboardData() {
        try {
            // Realizar una petición al archivo PHP que devuelve los datos
            const response = await fetch('get_admin_dashboard_data.php');
            const data = await response.json();

            // Comprobar si la petición fue exitosa
            if (data.success) {
                const dashboardData = data.data;
                
                // Actualizar los contadores
                totalUsersElement.textContent = dashboardData.totalUsers;
                totalBooksElement.textContent = dashboardData.totalBooks;
                activeLoansElement.textContent = dashboardData.activeLoans;
                pendingReservationsElement.textContent = dashboardData.pendingReservations;

                // Limpiar y actualizar la lista de actividad reciente
                recentActivityList.innerHTML = '';
                dashboardData.recentActivity.forEach(activity => {
                    const li = document.createElement('li');
                    li.textContent = `[${activity.date}] ${activity.text}`;
                    recentActivityList.appendChild(li);
                });

                // Limpiar y actualizar la lista de alertas importantes
                importantAlertsList.innerHTML = '';
                dashboardData.importantAlerts.forEach(alert => {
                    const li = document.createElement('li');
                    li.textContent = alert;
                    importantAlertsList.appendChild(li);
                });

            } else {
                console.error('Error al obtener los datos del dashboard:', data.errorMessage);
                // Mostrar un mensaje de error en la UI si la petición falla
                recentActivityList.innerHTML = `<li>Error: ${data.errorMessage}</li>`;
                importantAlertsList.innerHTML = `<li>Error: ${data.errorMessage}</li>`;
            }

        } catch (error) {
            console.error('Error en la petición fetch:', error);
            // Mostrar un mensaje de error si la petición falla por completo
            recentActivityList.innerHTML = `<li>Error de conexión al servidor.</li>`;
            importantAlertsList.innerHTML = `<li>Error de conexión al servidor.</li>`;
        }
    }

    // Llamar a la función al cargar la página por primera vez
    fetchDashboardData();

    // Actualizar los datos automáticamente cada 30 segundos
    // Puedes ajustar el tiempo según sea necesario (30000 ms = 30 segundos)
    setInterval(fetchDashboardData, 30000);
});
