// header.js - Manejo dinámico del header según sesión del usuario

console.log('[Header.js] Script cargado');

document.addEventListener('DOMContentLoaded', function() {
    console.log('[Header.js] DOM cargado, verificando sesión...');
    verificarSesionYActualizarHeader();
});

// También intentar verificar después de un pequeño delay
setTimeout(function() {
    console.log('[Header.js] Verificación de sesión por timeout (500ms)');
    verificarSesionYActualizarHeader();
}, 500);

function verificarSesionYActualizarHeader() {
    console.log('[Header.js] Iniciando fetch a get_user_info.php');
    
    fetch('../usuarios/get_user_info.php', {
        method: 'GET',
        credentials: 'include',
        cache: 'no-store'
    })
        .then(response => {
            console.log('[Header.js] Respuesta HTTP recibida, status:', response.status);
            
            if (!response.ok) {
                throw new Error(`HTTP ${response.status}: ${response.statusText}`);
            }
            
            return response.json();
        })
        .then(data => {
            console.log('[Header.js] JSON parseado:', data);
            
            if (data.success) {
                console.log('[Header.js] ✓ Usuario logueado:', data.nombre, '(' + data.email + ')');
                actualizarHeaderLogueado(data.nombre, data.email);
            } else {
                console.log('[Header.js] ✗ Usuario no logueado. Razón:', data.error);
                actualizarHeaderNoLogueado();
            }
        })
        .catch(error => {
            console.error('[Header.js] ✗ Error en fetch:', error.message);
            console.error('[Header.js] Stack trace:', error.stack);
            console.log('[Header.js] Asumiendo usuario no logueado');
            actualizarHeaderNoLogueado();
        });
}

function actualizarHeaderLogueado(nombre, email) {
    console.log('[Header.js] → Actualizando header como LOGUEADO');
    const accountButtons = document.getElementById('accountButtons');
    
    if (!accountButtons) {
        console.error('[Header.js] ✗ ERROR: No se encontró el elemento #accountButtons');
        return;
    }
    
    console.log('[Header.js] → Encontrado elemento accountButtons, inyectando HTML...');
    accountButtons.innerHTML = `
        <span class="AccountHeaderButton AccountHeaderButton--name">👤 ${nombre}</span>
        <a href="../usuarios/account.php" class="AccountHeaderButton">MI CUENTA</a>
        <a href="../usuarios/logout.php" class="AccountHeaderButton">CERRAR SESIÓN</a>
    `;
    console.log('[Header.js] ✓ Header actualizado correctamente');
}

function actualizarHeaderNoLogueado() {
    console.log('[Header.js] → Actualizando header como NO LOGUEADO');
    const accountButtons = document.getElementById('accountButtons');
    
    if (!accountButtons) {
        console.error('[Header.js] ✗ ERROR: No se encontró el elemento #accountButtons');
        return;
    }
    
    console.log('[Header.js] → Encontrado elemento accountButtons, inyectando HTML...');
    accountButtons.innerHTML = `
        <a href="../login.html" class="AccountHeaderButton">INICIAR SESIÓN</a>
        <a href="../register.html" class="AccountHeaderButton">REGISTRARSE</a>
    `;
    console.log('[Header.js] ✓ Header actualizado correctamente');
}
