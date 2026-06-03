<?php
session_start();
if (!isset($_SESSION['usuario_id'])) {
    header('Location: ../login.html');
    exit;
}
?><!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600;700&family=Nunito:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="../Styles/indexStyles.css">
    <link rel="stylesheet" href="../Styles/accountStyles.css">
    <title>Mi Cuenta — Polideportivo Entrerobles</title>
</head>
<body>

    <div id="header">
        <img src="../images/logoERwhite.png" alt="Logo" id="logo_escudo">
        <h1>POLIDEPORTIVO ENTREROBLES</h1>
        <div class="headerButtons">
            <div class="HeaderButtonsGroup1">
                <a href="../index.html" class="HeaderButton">INICIO</a>
                <a href="../index.html#instalacionesWidgetsTitle" class="HeaderButton">INSTALACIONES</a>
            </div>
            <div class="HeaderButtonsGroup2">
                <a href="../index.html#horariosTitle" class="HeaderButton">HORARIOS</a>
            </div>
            <div class="AccountButtons" id="accountButtons">
                <span class="AccountHeaderButton">👤 <?php echo htmlspecialchars($_SESSION['usuario_nombre'] ?? ''); ?></span>
                <a href="logout.php" class="AccountHeaderButton">CERRAR SESIÓN</a>
            </div>
        </div>
    </div>

    <main class="account-container">
        <div class="account-header">
            <h1>Mi Cuenta</h1>
            <p><?php echo htmlspecialchars($_SESSION['usuario_email'] ?? ''); ?></p>
        </div>

        <div id="toast" class="toast" style="display:none;"></div>

        <div class="account-content">
            <section class="reservas-section">
                <div class="reservas-section-header">
                    <h2>Mis Reservas</h2>
                    <a href="../reservas/reservations.php" class="btn-primary">+ Nueva reserva</a>
                </div>
                <div id="reservasContainer" class="reservas-grid"></div>
                <div id="noReservas" class="no-reservas" style="display:none;">
                    <p>No tienes ninguna reserva registrada.</p>
                    <a href="../reservas/reservations.php" class="btn-primary">Crear nueva reserva</a>
                </div>
            </section>
        </div>
    </main>

    <!-- Modal editar -->
    <div id="editModal" class="modal" style="display:none;">
        <div class="modal-content">
            <span class="close-btn" id="closeEdit">&times;</span>
            <h2>Editar Reserva</h2>
            <form id="editForm">
                <input type="hidden" id="editReservaId">
                <div class="form-group"><label>Instalación</label><input type="text" id="editInstalacion" disabled></div>
                <div class="form-group"><label>Fecha</label><input type="date" id="editFecha" required></div>
                <div class="form-group"><label>Hora Inicio</label><input type="time" id="editHoraInicio" required></div>
                <div class="form-group"><label>Hora Fin</label><input type="time" id="editHoraFin" required></div>
                <div class="form-group"><label>Participantes</label><input type="number" id="editParticipantes" min="1" required></div>
                <div class="form-group"><label>Observaciones</label><textarea id="editObservaciones"></textarea></div>
                <div class="modal-buttons">
                    <button type="submit" class="btn-primary">Guardar</button>
                    <button type="button" class="btn-secondary" id="cancelEdit">Cancelar</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal confirmar -->
    <div id="confirmModal" class="modal" style="display:none;">
        <div class="modal-content modal-confirm">
            <p id="confirmMsg" class="confirm-msg"></p>
            <div class="modal-buttons">
                <button id="confirmOk" class="btn-danger">Confirmar</button>
                <button id="confirmNo" class="btn-secondary">Volver</button>
            </div>
        </div>
    </div>

    <script>
        let reservas = [];

        document.addEventListener('DOMContentLoaded', cargarReservas);

        function toast(msg, tipo) {
            const el = document.getElementById('toast');
            el.textContent = (tipo === 'ok' ? '✅ ' : '❌ ') + msg;
            el.className = 'toast toast--' + tipo;
            el.style.display = 'block';
            setTimeout(() => el.style.display = 'none', 3500);
        }

        function cargarReservas() {
            fetch('../reservas/get_reservas.php', { credentials: 'include' })
                .then(r => r.json())
                .then(data => { reservas = data; mostrarReservas(); })
                .catch(() => toast('Error al cargar reservas', 'error'));
        }

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

        function crearTarjeta(r) {
            const div = document.createElement('div');
            const cancelada = r.estado === 'cancelada';
            div.className = 'reserva-card' + (cancelada ? ' reserva-card--cancelada' : '');
            const fecha = new Date(r.fecha + 'T00:00:00').toLocaleDateString('es-ES', { weekday:'long', year:'numeric', month:'long', day:'numeric' });
            div.innerHTML = `
                <div class="reserva-header">
                    <h3>${r.instalacion_nombre}</h3>
                    <span class="estado ${cancelada ? 'estado-cancelada' : 'estado-activa'}">${cancelada ? 'Cancelada' : 'Activa'}</span>
                </div>
                <div class="reserva-details">
                    <p><strong>Fecha:</strong> ${fecha}</p>
                    <p><strong>Horario:</strong> ${r.hora_inicio.slice(0,5)} – ${r.hora_fin.slice(0,5)}</p>
                    <p><strong>Participantes:</strong> ${r.participantes}</p>
                    ${r.observaciones ? `<p><strong>Observaciones:</strong> ${r.observaciones}</p>` : ''}
                </div>
                ${!cancelada ? `
                <div class="reserva-actions">
                    <button class="btn-edit" onclick="abrirModalEditar(${r.id})"><i class="fas fa-edit"></i> Modificar</button>
                    <button class="btn-cancel" onclick="pedirCancelar(${r.id})"><i class="fas fa-ban"></i> Cancelar</button>
                    <button class="btn-delete" onclick="pedirEliminar(${r.id})"><i class="fas fa-trash"></i> Eliminar</button>
                </div>` : ''}
            `;
            return div;
        }

        function abrirConfirm(msg, onOk) {
            document.getElementById('confirmMsg').textContent = msg;
            document.getElementById('confirmModal').style.display = 'flex';
            document.getElementById('confirmOk').onclick = () => { document.getElementById('confirmModal').style.display = 'none'; onOk(); };
            document.getElementById('confirmNo').onclick  = () => { document.getElementById('confirmModal').style.display = 'none'; };
        }

        function pedirCancelar(id) { abrirConfirm('¿Cancelar esta reserva?', () => cancelarReserva(id)); }
        function pedirEliminar(id) { abrirConfirm('¿Eliminar esta reserva? No se puede deshacer.', () => eliminarReserva(id)); }

        function cancelarReserva(id) {
            fetch('../reservas/cancelar_reserva.php', { method:'POST', headers:{'Content-Type':'application/json'}, credentials:'include', body: JSON.stringify({reserva_id:id}) })
            .then(r => r.json()).then(d => { toast(d.success ? 'Reserva cancelada.' : d.error, d.success ? 'ok' : 'error'); if(d.success) cargarReservas(); });
        }

        function eliminarReserva(id) {
            fetch('../reservas/eliminar_reserva.php', { method:'POST', headers:{'Content-Type':'application/json'}, credentials:'include', body: JSON.stringify({reserva_id:id}) })
            .then(r => r.json()).then(d => { toast(d.success ? 'Reserva eliminada.' : d.error, d.success ? 'ok' : 'error'); if(d.success) cargarReservas(); });
        }

        function abrirModalEditar(id) {
            const r = reservas.find(x => x.id === id);
            if (!r) return;
            document.getElementById('editReservaId').value     = id;
            document.getElementById('editInstalacion').value   = r.instalacion_nombre;
            document.getElementById('editFecha').value         = r.fecha;
            document.getElementById('editHoraInicio').value    = r.hora_inicio.slice(0,5);
            document.getElementById('editHoraFin').value       = r.hora_fin.slice(0,5);
            document.getElementById('editParticipantes').value = r.participantes;
            document.getElementById('editObservaciones').value = r.observaciones || '';
            document.getElementById('editModal').style.display = 'flex';
        }

        document.getElementById('closeEdit').onclick  = () => document.getElementById('editModal').style.display = 'none';
        document.getElementById('cancelEdit').onclick = () => document.getElementById('editModal').style.display = 'none';

        document.getElementById('editForm').addEventListener('submit', function(e) {
            e.preventDefault();
            fetch('../reservas/actualizar_reserva.php', {
                method:'POST', headers:{'Content-Type':'application/json'}, credentials:'include',
                body: JSON.stringify({
                    reserva_id: document.getElementById('editReservaId').value,
                    fecha: document.getElementById('editFecha').value,
                    hora_inicio: document.getElementById('editHoraInicio').value,
                    hora_fin: document.getElementById('editHoraFin').value,
                    participantes: document.getElementById('editParticipantes').value,
                    observaciones: document.getElementById('editObservaciones').value
                })
            }).then(r => r.json()).then(d => {
                if (d.success) { document.getElementById('editModal').style.display = 'none'; toast('Reserva actualizada.', 'ok'); cargarReservas(); }
                else toast(d.error || 'Error', 'error');
            });
        });

        window.addEventListener('click', e => {
            if (e.target.id === 'editModal')    document.getElementById('editModal').style.display    = 'none';
            if (e.target.id === 'confirmModal') document.getElementById('confirmModal').style.display = 'none';
        });
    </script>
</body>
</html>
