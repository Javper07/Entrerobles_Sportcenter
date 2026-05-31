<?php
// Este archivo retorna un script que verifica la sesión y actualiza el header
session_start();

$isLoggedIn = isset($_SESSION['usuario_id']);
$usuarioNombre = $_SESSION['usuario_nombre'] ?? '';
$usuarioEmail = $_SESSION['usuario_email'] ?? '';

error_log('[check-session.php] isLoggedIn: ' . ($isLoggedIn ? 'true' : 'false'));
error_log('[check-session.php] usuario_id: ' . ($_SESSION['usuario_id'] ?? 'null'));

?>
<script>
// Script de verificación de sesión - se ejecuta INMEDIATAMENTE
console.log('[check-session.js] Script ejecutándose');

// Datos de sesión del servidor (inyectados en PHP)
const serverSessionData = {
    isLoggedIn: <?php echo $isLoggedIn ? 'true' : 'false'; ?>,
    usuarioNombre: "<?php echo addslashes($usuarioNombre); ?>",
    usuarioEmail: "<?php echo addslashes($usuarioEmail); ?>",
    usuarioId: <?php echo $_SESSION['usuario_id'] ?? 'null'; ?>
};

console.log('[check-session.js] Datos de sesión del servidor:', serverSessionData);

// Guardar en localStorage para acceso rápido
if (serverSessionData.isLoggedIn) {
    localStorage.setItem('usuario_logueado', 'true');
    localStorage.setItem('usuario_nombre', serverSessionData.usuarioNombre);
    localStorage.setItem('usuario_email', serverSessionData.usuarioEmail);
    console.log('[check-session.js] ✓ Usuario logueado:', serverSessionData.usuarioNombre);
} else {
    localStorage.removeItem('usuario_logueado');
    localStorage.removeItem('usuario_nombre');
    localStorage.removeItem('usuario_email');
    console.log('[check-session.js] ✗ Usuario no logueado');
}

// Actualizar header INMEDIATAMENTE basado en datos del servidor
function actualizarHeaderPorServidor() {
    const accountButtons = document.getElementById('accountButtons');
    
    if (!accountButtons) {
        console.warn('[check-session.js] Elemento accountButtons no encontrado aún, esperando DOM');
        setTimeout(actualizarHeaderPorServidor, 100);
        return;
    }
    
    if (serverSessionData.isLoggedIn) {
        console.log('[check-session.js] → Actualizando header como LOGUEADO');
        accountButtons.innerHTML = `
            <a href="account.html" class="AccountHeaderButton">MI CUENTA</a>
            <a href="logout.php" class="AccountHeaderButton">CERRAR SESIÓN</a>
        `;
        console.log('[check-session.js] ✓ Header actualizado para usuario logueado');
    } else {
        console.log('[check-session.js] → Actualizando header como NO LOGUEADO');
        accountButtons.innerHTML = `
            <a href="login.html" class="AccountHeaderButton">INICIAR SESIÓN</a>
            <a href="register.html" class="AccountHeaderButton">REGISTRARSE</a>
        `;
        console.log('[check-session.js] ✓ Header actualizado para usuario no logueado');
    }
}

// Ejecutar cuando DOM esté listo
if (document.readyState === 'loading') {
    console.log('[check-session.js] Esperando DOMContentLoaded');
    document.addEventListener('DOMContentLoaded', actualizarHeaderPorServidor);
} else {
    console.log('[check-session.js] DOM ya está cargado, actualizando header');
    actualizarHeaderPorServidor();
}
</script>
