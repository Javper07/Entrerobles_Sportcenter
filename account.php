<?php
session_start();
if (!isset($_SESSION['usuario_id'])) {
    header('Location: login.html');
    exit;
}
ob_start();
?><!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://fonts.googleapis.com/css2?family=Bungee+Spice&family=Roboto:ital,wght@0,100..900;1,100..900&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Bungee+Spice&family=Quantico:ital,wght@0,400;0,700;1,400;1,700&family=Roboto:ital,wght@0,100..900;1,100..900&display=swap" rel="stylesheet">
    <style>@import url('https://fonts.googleapis.com/css2?family=Merriweather:wght@300;400;700&display=swap');</style>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="Styles/indexStyles.css">
    <link rel="stylesheet" href="Styles/accountStyles.css">
    <title>Mi Cuenta — Polideportivo Entrerobles</title>
    <?php include 'check_session.php'; ?>
</head>
<body>

    <!-- Cabecera -->
    <div id="header">
        <img src="images/logoERwhite.png" alt="Logo Escudo" id="logo_escudo">
        <h1>POLIDEPORTIVO ENTREROBLES</h1>
        <div class="headerButtons">
            <div class="HeaderButtonsGroup1">
                <a href="index.html" class="HeaderButton">INICIO</a>
                <a href="index.html#instalacionesWidgetsTitle" class="HeaderButton">INSTALACIONES</a>
            </div>
            <div class="HeaderButtonsGroup2">
                <a href="index.html#horariosTitle" class="HeaderButton">HORARIOS</a>
            </div>
            <div class="AccountButtons" id="accountButtons">
                <!-- Se rellena dinámicamente -->
            </div>
        </div>
    </div>

    <main class="account-container">
        <div class="account-header">
            <h1>Mi Cuenta</h1>
            <p id="userEmail"></p>
        </div>

        <!-- Toast de notificación -->
        <div id="toast" class="toast" style="display:none;"></div>

        <div class="account-content">
            <section class="reservas-section">
                <div class="reservas-section-header">
                    <h2>Mis Reservas</h2>
                    <a href="reservations.php" class="btn-primary">+ Nueva reserva</a>
                </div>

                <div id="reservasContainer" class="reservas-grid"></div>

                <div id="noReservas" class="no-reservas" style="display:none;">
                    <p>No tienes ninguna reserva registrada.</p>
                    <a href="reservations.php" class="btn-primary">Crear nueva reserva</a>
                </div>
            </section>
        </div>
    </main>

    <!-- ── Modal editar reserva ─────────────────────────────── -->
    <div id="editModal" class="modal" style="display:none;">
        <div class="modal-content">
            <span class="close-btn" id="closeEdit">&times;</span>
            <h2>Editar Reserva</h2>
            <form id="editForm">
                <input type="hidden" id="editReservaId">
                <div class="form-group">
                    <label>Instalación</label>
                    <input type="text" id="editInstalacion" disabled>
                </div>
                <div class="form-group">
                    <label>Fecha</label>
                    <input type="date" id="editFecha" required>
                </div>
                <div class="form-group">
                    <label>Hora Inicio</label>
                    <input type="time" id="editHoraInicio" required>
                </div>
                <div class="form-group">
                    <label>Hora Fin</label>
                    <input type="time" id="editHoraFin" required>
                </div>
                <div class="form-group">
                    <label>Participantes</label>
                    <input type="number" id="editParticipantes" min="1" required>
                </div>
                <div class="form-group">
                    <label>Observaciones</label>
                    <textarea id="editObservaciones"></textarea>
                </div>
                <div class="modal-buttons">
                    <button type="submit" class="btn-primary">Guardar cambios</button>
                    <button type="button" class="btn-secondary" id="cancelEdit">Cancelar</button>
                </div>
            </form>
        </div>
    </div>

    <!-- ── Modal de confirmación (cancelar / eliminar) ──────── -->
    <div id="confirmModal" class="modal" style="display:none;">
        <div class="modal-content modal-confirm">
            <p id="confirmMsg" class="confirm-msg"></p>
            <div class="modal-buttons">
                <button id="confirmOk"  class="btn-danger">Confirmar</button>
                <button id="confirmNo"  class="btn-secondary">Volver</button>
            </div>
        </div>
    </div>

    <script>
        let reservas = [];

        // ── Arranque ──────────────────────────────────────────
        document.addEventListener('DOMContentLoaded', () => {
            cargarReservas();
        });

        // ── Toast ─────────────────────────────────────────────
        function toast(msg, tipo = 'ok') {
            const el = document.getElementById('toast');
            el.textContent = (tipo === 'ok' ? '✅ ' : '❌ ') + msg;
            el.className = 'toast toast--' + tipo;
            el.style.display = 'block';
            setTimeout(() => { el.style.display = 'none'; }, 3500);
        }

        // ── Cargar datos ──────────────────────────────────────
        function cargarReservas() {
            fetch('get_reservas.php', { credentials: 'include' })
                .then(r => r.json())
                .then(data => {
                    if (data.error) { window.location.href = 'login.html'; return; }
                    reservas = data;
                    obtenerInfoUsuario();
                })
                .catch(() => { window.location.href = 'login.html'; });
        }

        function obtenerInfoUsuario() {
            fetch('get_user_info.php', { credentials: 'include' })
                .then(r => r.json())
                .then(data => {
                    if (data.success) {
                        document.getElementById('userEmail').textContent = '👤 ' + data.nombre + ' — ' + data.email;
                    }
                    mostrarReservas();
                });
        }

        // ── Renderizar tarjetas ───────────────────────────────
        function mostrarReservas() {
            const container  = document.getElementById('reservasContainer');
            const noReservas = document.getElementById('noReservas');

            if (reservas.length === 0) {
                container.style.display = 'none';
                noReservas.style.display = 'block';
                return;
            }

            container.style.display = '';
            noReservas.style.display = 'none';
            container.innerHTML = '';
            reservas.forEach(r => container.appendChild(crearTarjeta(r)));
        }

        function crearTarjeta(reserva) {
            const div  = document.createElement('div');
            const cancelada = reserva.estado === 'cancelada';
            div.className = 'reserva-card' + (cancelada ? ' reserva-card--cancelada' : '');

            const fecha = new Date(reserva.fecha + 'T00:00:00')
                .toLocaleDateString('es-ES', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' });

            div.innerHTML = `
                <div class="reserva-header">
                    <h3>${reserva.instalacion_nombre}</h3>
                    <span class="estado ${cancelada ? 'estado-cancelada' : 'estado-activa'}">
                        ${cancelada ? 'Cancelada' : 'Activa'}
                    </span>
                </div>
                <div class="reserva-details">
                    <p><strong>Fecha:</strong> ${fecha}</p>
                    <p><strong>Horario:</strong> ${reserva.hora_inicio.slice(0,5)} – ${reserva.hora_fin.slice(0,5)}</p>
                    <p><strong>Participantes:</strong> ${reserva.participantes}</p>
                    ${reserva.observaciones ? `<p><strong>Observaciones:</strong> ${reserva.observaciones}</p>` : ''}
                </div>
                ${!cancelada ? `
                <div class="reserva-actions">
                    <button class="btn-edit" onclick="abrirModalEditar(${reserva.id})">
                        <i class="fas fa-edit"></i> Modificar
                    </button>
                    <button class="btn-cancel" onclick="pedirCancelar(${reserva.id})">
                        <i class="fas fa-ban"></i> Cancelar reserva
                    </button>
                    <button class="btn-delete" onclick="pedirEliminar(${reserva.id})">
                        <i class="fas fa-trash"></i> Eliminar
                    </button>
                </div>` : ''}
            `;
            return div;
        }

        // ── Modal de confirmación genérico ────────────────────
        function abrirConfirm(mensaje, onOk) {
            document.getElementById('confirmMsg').textContent = mensaje;
            document.getElementById('confirmModal').style.display = 'flex';
            const okBtn = document.getElementById('confirmOk');
            const noBtn = document.getElementById('confirmNo');
            const cerrar = () => { document.getElementById('confirmModal').style.display = 'none'; };
            okBtn.onclick = () => { cerrar(); onOk(); };
            noBtn.onclick = cerrar;
        }

        function pedirCancelar(id) {
            abrirConfirm('¿Cancelar esta reserva? Quedará registrada como cancelada en tu historial.', () => cancelarReserva(id));
        }
        function pedirEliminar(id) {
            abrirConfirm('¿Eliminar esta reserva? Esta acción no se puede deshacer.', () => eliminarReserva(id));
        }

        // ── Cancelar (estado → cancelada) ─────────────────────
        function cancelarReserva(id) {
            fetch('cancelar_reserva.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                credentials: 'include',
                body: JSON.stringify({ reserva_id: id })
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    toast('Reserva cancelada correctamente.', 'ok');
                    cargarReservas();
                } else {
                    toast(data.error || 'Error al cancelar.', 'error');
                }
            })
            .catch(() => toast('Error de conexión.', 'error'));
        }

        // ── Eliminar (borrado físico) ──────────────────────────
        function eliminarReserva(id) {
            fetch('eliminar_reserva.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                credentials: 'include',
                body: JSON.stringify({ reserva_id: id })
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    toast('Reserva eliminada.', 'ok');
                    cargarReservas();
                } else {
                    toast(data.error || 'Error al eliminar.', 'error');
                }
            })
            .catch(() => toast('Error de conexión.', 'error'));
        }

        // ── Editar reserva ────────────────────────────────────
        function abrirModalEditar(id) {
            const r = reservas.find(x => x.id === id);
            if (!r) return;
            document.getElementById('editReservaId').value    = id;
            document.getElementById('editInstalacion').value  = r.instalacion_nombre;
            document.getElementById('editFecha').value        = r.fecha;
            document.getElementById('editHoraInicio').value   = r.hora_inicio.slice(0,5);
            document.getElementById('editHoraFin').value      = r.hora_fin.slice(0,5);
            document.getElementById('editParticipantes').value = r.participantes;
            document.getElementById('editObservaciones').value = r.observaciones || '';
            document.getElementById('editModal').style.display = 'flex';
        }

        document.getElementById('closeEdit').onclick  = () => document.getElementById('editModal').style.display = 'none';
        document.getElementById('cancelEdit').onclick  = () => document.getElementById('editModal').style.display = 'none';

        document.getElementById('editForm').addEventListener('submit', function(e) {
            e.preventDefault();
            const datos = {
                reserva_id:    document.getElementById('editReservaId').value,
                fecha:         document.getElementById('editFecha').value,
                hora_inicio:   document.getElementById('editHoraInicio').value,
                hora_fin:      document.getElementById('editHoraFin').value,
                participantes: document.getElementById('editParticipantes').value,
                observaciones: document.getElementById('editObservaciones').value
            };
            fetch('actualizar_reserva.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                credentials: 'include',
                body: JSON.stringify(datos)
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    document.getElementById('editModal').style.display = 'none';
                    toast('Reserva actualizada correctamente.', 'ok');
                    cargarReservas();
                } else {
                    toast(data.error || 'Error al actualizar.', 'error');
                }
            })
            .catch(() => toast('Error de conexión.', 'error'));
        });

        // Cerrar modales al pulsar fuera
        window.addEventListener('click', e => {
            if (e.target.id === 'editModal')    document.getElementById('editModal').style.display    = 'none';
            if (e.target.id === 'confirmModal') document.getElementById('confirmModal').style.display = 'none';
        });
    </script>
</body>
</html>
<?php ob_end_flush(); ?>
