<?php
// check_sesion.php — inyecta datos de sesión PHP directamente en scripts JS.
// Se usa en páginas PHP para actualizar el header antes de cualquier otra lógica.
if (session_status() === PHP_SESSION_NONE) { session_start(); }

$isLoggedIn    = isset($_SESSION['usuario_id']);
$usuarioNombre = htmlspecialchars($_SESSION['usuario_nombre'] ?? '', ENT_QUOTES);
$esAdmin       = !empty($_SESSION['es_admin']);
?>
<script>
(function () {
    // Datos de sesión inyectados directamente desde PHP (sin localStorage)
    var session = {
        loggedIn: <?= $isLoggedIn ? 'true' : 'false' ?>,
        nombre:   "<?= $usuarioNombre ?>",
        esAdmin:  <?= $esAdmin ? 'true' : 'false' ?>
    };

    function actualizarHeader() {
        var el = document.getElementById('accountButtons');
        if (!el) { setTimeout(actualizarHeader, 50); return; }

        if (session.loggedIn) {
            el.innerHTML =
                '<a href="../usuarios/account.php" class="AccountHeaderButton">MI CUENTA</a>' +
                (session.esAdmin ? '<a href="../admin/admin.php" class="AccountHeaderButton">ADMIN</a>' : '') +
                '<a href="../usuarios/logout.php" class="AccountHeaderButton">CERRAR SESIÓN</a>';
        } else {
            el.innerHTML =
                '<a href="../login.html" class="AccountHeaderButton">INICIAR SESIÓN</a>' +
                '<a href="../register.html" class="AccountHeaderButton">REGISTRARSE</a>';
        }
    }

    if (document.readyState === 'loading') {
        // Si el DOM aún no está listo, esperamos a que se cargue.
        document.addEventListener('DOMContentLoaded', actualizarHeader);
    } else {
        actualizarHeader();
    }
})();
</script>
