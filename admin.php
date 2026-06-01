<?php
session_start();

// Verificar sesión
if (!isset($_SESSION['usuario_id'])) {
    header('Location: login.html');
    exit;
}

// Verificar rol admin
require 'db.php';
try {
    $pdo = getDbConnection();
    $stmt = $pdo->prepare("SELECT es_admin, nombre FROM usuarios WHERE id = :id");
    $stmt->execute([':id' => $_SESSION['usuario_id']]);
    $u = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$u || !$u['es_admin']) {
        header('Location: index.html');
        exit;
    }
    $adminNombre = $u['nombre'];
} catch (Exception $e) {
    header('Location: index.html');
    exit;
}
?><!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600;700&family=Nunito:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="Styles/adminStyles.css">
    <title>Panel de Administración — Polideportivo Entrerobles</title>
</head>
<body>

    <!-- Sidebar -->
    <aside class="sidebar">
        <div class="sidebar-logo">
            <img src="images/logoERwhite.png" alt="Logo">
            <span>Admin</span>
        </div>
        <nav class="sidebar-nav">
            <a href="#" class="nav-item active" data-seccion="resumen">
                <i class="fas fa-chart-pie"></i> Resumen
            </a>
            <a href="#" class="nav-item" data-seccion="reservas">
                <i class="fas fa-calendar-check"></i> Reservas
            </a>
            <a href="#" class="nav-item" data-seccion="usuarios">
                <i class="fas fa-users"></i> Usuarios
            </a>
            <a href="#" class="nav-item" data-seccion="mensajes">
                <i class="fas fa-envelope"></i> Mensajes
                <span id="badge-mensajes" class="badge" style="display:none;"></span>
            </a>
        </nav>
        <div class="sidebar-footer">
            <span class="admin-name"><i class="fas fa-user-shield"></i> <?= htmlspecialchars($adminNombre) ?></span>
            <a href="index.html" class="btn-volver"><i class="fas fa-arrow-left"></i> Volver al sitio</a>
            <a href="logout.php" class="btn-logout"><i class="fas fa-sign-out-alt"></i> Cerrar sesión</a>
        </div>
    </aside>

    <!-- Main -->
    <main class="admin-main">

        <header class="admin-topbar">
            <h1 id="page-title">Resumen</h1>
            <span id="fecha-hoy" class="fecha-hoy"></span>
        </header>

        <div id="contenido" class="admin-contenido">
            <div class="loading"><i class="fas fa-spinner fa-spin"></i> Cargando...</div>
        </div>

    </main>

    <script>
    // ── Fecha de hoy ──────────────────────────────────────
    document.getElementById('fecha-hoy').textContent =
        new Date().toLocaleDateString('es-ES', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' });

    // ── Navegación ────────────────────────────────────────
    let seccionActual = 'resumen';

    document.querySelectorAll('.nav-item').forEach(el => {
        el.addEventListener('click', e => {
            e.preventDefault();
            const sec = el.dataset.seccion;
            document.querySelectorAll('.nav-item').forEach(x => x.classList.remove('active'));
            el.classList.add('active');
            document.getElementById('page-title').textContent = el.textContent.trim();
            seccionActual = sec;
            cargar(sec);
        });
    });

    // ── Carga inicial ─────────────────────────────────────
    cargar('resumen');

    function cargar(seccion) {
        const contenido = document.getElementById('contenido');
        contenido.innerHTML = '<div class="loading"><i class="fas fa-spinner fa-spin"></i> Cargando...</div>';

        fetch('admin_data.php?seccion=' + seccion, { credentials: 'include' })
            .then(r => r.json())
            .then(data => {
                if (data.error) { contenido.innerHTML = '<p class="error-msg">Error: ' + data.error + '</p>'; return; }
                if (seccion === 'resumen')   renderResumen(data);
                if (seccion === 'reservas')  renderReservas(data);
                if (seccion === 'usuarios')  renderUsuarios(data);
                if (seccion === 'mensajes')  renderMensajes(data);
            })
            .catch(() => contenido.innerHTML = '<p class="error-msg">Error de conexión.</p>');
    }

    // ── RESUMEN ───────────────────────────────────────────
    function renderResumen(d) {
        const badge = document.getElementById('badge-mensajes');
        if (d.mensajes_sin_leer > 0) {
            badge.textContent = d.mensajes_sin_leer;
            badge.style.display = 'inline-block';
        }

        let html = `
        <div class="kpi-grid">
            <div class="kpi-card kpi-blue">
                <i class="fas fa-calendar-check kpi-icon"></i>
                <div class="kpi-val">${d.reservas_activas}</div>
                <div class="kpi-label">Reservas activas</div>
            </div>
            <div class="kpi-card kpi-green">
                <i class="fas fa-calendar-day kpi-icon"></i>
                <div class="kpi-val">${d.reservas_hoy}</div>
                <div class="kpi-label">Reservas hoy</div>
            </div>
            <div class="kpi-card kpi-purple">
                <i class="fas fa-users kpi-icon"></i>
                <div class="kpi-val">${d.usuarios}</div>
                <div class="kpi-label">Usuarios registrados</div>
            </div>
            <div class="kpi-card kpi-orange">
                <i class="fas fa-envelope kpi-icon"></i>
                <div class="kpi-val">${d.mensajes_sin_leer}</div>
                <div class="kpi-label">Mensajes sin leer</div>
            </div>
        </div>`;

        // Reservas de hoy
        html += `<div class="section-card">
            <h2><i class="fas fa-sun"></i> Reservas de hoy</h2>`;
        if (d.reservas_hoy_lista.length === 0) {
            html += '<p class="vacio">No hay reservas para hoy.</p>';
        } else {
            html += tablaReservas(d.reservas_hoy_lista, false);
        }
        html += '</div>';

        // Próximas 7 días
        html += `<div class="section-card">
            <h2><i class="fas fa-calendar-alt"></i> Próximas reservas (7 días)</h2>`;
        if (d.proximas_reservas.length === 0) {
            html += '<p class="vacio">No hay reservas en los próximos 7 días.</p>';
        } else {
            html += tablaReservas(d.proximas_reservas, true);
        }
        html += '</div>';

        document.getElementById('contenido').innerHTML = html;
    }

    // ── RESERVAS ──────────────────────────────────────────
    function renderReservas(data) {
        let html = `<div class="section-card">
            <h2><i class="fas fa-list"></i> Todas las reservas <span class="count">${data.length}</span></h2>
            ${tablaReservas(data, true, true)}
        </div>`;
        document.getElementById('contenido').innerHTML = html;
    }

    function tablaReservas(lista, conFecha, conAcciones = false) {
        if (lista.length === 0) return '<p class="vacio">Sin datos.</p>';
        let html = `<div class="tabla-wrap"><table class="tabla">
            <thead><tr>
                ${conFecha ? '<th>Fecha</th>' : ''}
                <th>Usuario</th>
                <th>Instalación</th>
                <th>Horario</th>
                <th>Participantes</th>
                <th>Estado</th>
                ${conAcciones ? '<th>Acciones</th>' : ''}
            </tr></thead><tbody>`;
        lista.forEach(r => {
            const estadoClass = r.estado === 'cancelada' ? 'badge-cancelada' : 'badge-activa';
            html += `<tr>
                ${conFecha ? `<td>${formatFecha(r.fecha)}</td>` : ''}
                <td><strong>${esc(r.usuario)}</strong><br><small>${esc(r.email||'')}</small></td>
                <td>${esc(r.instalacion)}</td>
                <td>${r.hora_inicio.slice(0,5)} – ${r.hora_fin.slice(0,5)}</td>
                <td>${r.participantes}</td>
                <td><span class="badge-estado ${estadoClass}">${r.estado}</span></td>
                ${conAcciones ? `<td class="td-acciones">
                    <button class="btn-accion btn-editar" onclick='abrirModalEditar(${JSON.stringify(r)})' title="Editar reserva">
                        <i class="fas fa-pen"></i>
                    </button>
                    <button class="btn-accion btn-eliminar" onclick="confirmarEliminar(${r.id})" title="Eliminar reserva">
                        <i class="fas fa-trash"></i>
                    </button>
                </td>` : ''}
            </tr>`;
        });
        html += '</tbody></table></div>';
        return html;
    }

    // ── MODAL EDITAR RESERVA ──────────────────────────────
    function abrirModalEditar(r) {
        document.getElementById('modal-reserva-id').value    = r.id;
        document.getElementById('modal-fecha').value         = r.fecha;
        document.getElementById('modal-hora-inicio').value   = r.hora_inicio.slice(0,5);
        document.getElementById('modal-hora-fin').value      = r.hora_fin.slice(0,5);
        document.getElementById('modal-participantes').value = r.participantes;
        document.getElementById('modal-observaciones').value = r.observaciones || '';
        document.getElementById('modal-estado').value        = r.estado;
        document.getElementById('modal-usuario').textContent = r.usuario + (r.email ? ' — ' + r.email : '');
        document.getElementById('modal-instalacion').textContent = r.instalacion;
        document.getElementById('modal-error').textContent  = '';
        document.getElementById('modal-overlay').style.display = 'flex';
    }

    function cerrarModal() {
        document.getElementById('modal-overlay').style.display = 'none';
    }

    document.addEventListener('DOMContentLoaded', () => {
        document.getElementById('modal-overlay').addEventListener('click', e => {
            if (e.target === document.getElementById('modal-overlay')) cerrarModal();
        });

        document.getElementById('form-editar-reserva').addEventListener('submit', async e => {
            e.preventDefault();
            const btn = document.getElementById('btn-guardar-reserva');
            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Guardando...';

            const body = {
                reserva_id:    parseInt(document.getElementById('modal-reserva-id').value),
                fecha:         document.getElementById('modal-fecha').value,
                hora_inicio:   document.getElementById('modal-hora-inicio').value,
                hora_fin:      document.getElementById('modal-hora-fin').value,
                participantes: parseInt(document.getElementById('modal-participantes').value),
                observaciones: document.getElementById('modal-observaciones').value,
                estado:        document.getElementById('modal-estado').value,
            };

            try {
                const res  = await fetch('admin_actualizar_reserva.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    credentials: 'include',
                    body: JSON.stringify(body)
                });
                const data = await res.json();
                if (data.success) {
                    cerrarModal();
                    cargar('reservas');
                } else {
                    document.getElementById('modal-error').textContent = data.error || 'Error desconocido';
                }
            } catch {
                document.getElementById('modal-error').textContent = 'Error de conexión';
            } finally {
                btn.disabled = false;
                btn.innerHTML = '<i class="fas fa-save"></i> Guardar cambios';
            }
        });
    });

    async function confirmarEliminar(id) {
        if (!confirm('¿Seguro que quieres eliminar esta reserva? Esta acción no se puede deshacer.')) return;
        try {
            const res  = await fetch('admin_eliminar_reserva.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                credentials: 'include',
                body: JSON.stringify({ reserva_id: id })
            });
            const data = await res.json();
            if (data.success) {
                cargar('reservas');
            } else {
                alert('Error: ' + (data.error || 'No se pudo eliminar'));
            }
        } catch {
            alert('Error de conexión');
        }
    }

    // ── USUARIOS ──────────────────────────────────────────
    function renderUsuarios(data) {
        let html = `<div class="section-card">
            <h2><i class="fas fa-users"></i> Usuarios registrados <span class="count">${data.length}</span></h2>
            <div class="tabla-wrap"><table class="tabla">
                <thead><tr>
                    <th>ID</th><th>Nombre</th><th>Email</th><th>Teléfono</th>
                    <th>Reservas</th><th>Rol</th><th>Acciones</th>
                </tr></thead><tbody>`;
        const miId = <?= (int)$_SESSION['usuario_id'] ?>;
        data.forEach(u => {
            const esSelf = u.id == miId;
            html += `<tr>
                <td>${u.id}</td>
                <td><strong>${esc(u.nombre)}</strong></td>
                <td>${esc(u.email)}</td>
                <td>${esc(u.telefono||'—')}</td>
                <td>${u.total_reservas}</td>
                <td><span class="badge-estado ${u.es_admin ? 'badge-admin' : 'badge-user'}">
                    ${u.es_admin ? '🛡 Admin' : 'Usuario'}
                </span></td>
                <td class="td-acciones">
                    <button class="btn-accion btn-editar" onclick='abrirModalEditarUsuario(${JSON.stringify(u)})' title="Editar usuario">
                        <i class="fas fa-pen"></i>
                    </button>
                    ${!esSelf ? `<button class="btn-accion btn-eliminar" onclick="confirmarEliminarUsuario(${u.id}, '${esc(u.nombre)}')" title="Eliminar usuario">
                        <i class="fas fa-trash"></i>
                    </button>` : '<span style="font-size:.75rem;color:#aaa;padding:0 4px;">yo</span>'}
                </td>
            </tr>`;
        });
        html += '</tbody></table></div></div>';
        document.getElementById('contenido').innerHTML = html;
    }

    // ── MODAL EDITAR USUARIO ──────────────────────────────
    function abrirModalEditarUsuario(u) {
        document.getElementById('modal-u-id').value       = u.id;
        document.getElementById('modal-u-nombre').value   = u.nombre;
        document.getElementById('modal-u-email').value    = u.email;
        document.getElementById('modal-u-telefono').value = u.telefono || '';
        document.getElementById('modal-u-admin').checked  = u.es_admin == true || u.es_admin == 1;
        document.getElementById('modal-u-error').textContent = '';
        document.getElementById('modal-u-overlay').style.display = 'flex';
    }

    function cerrarModalUsuario() {
        document.getElementById('modal-u-overlay').style.display = 'none';
    }

    document.addEventListener('DOMContentLoaded', () => {
        document.getElementById('modal-u-overlay').addEventListener('click', e => {
            if (e.target === document.getElementById('modal-u-overlay')) cerrarModalUsuario();
        });

        document.getElementById('form-editar-usuario').addEventListener('submit', async e => {
            e.preventDefault();
            const btn = document.getElementById('btn-guardar-usuario');
            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Guardando...';

            const body = {
                usuario_id: parseInt(document.getElementById('modal-u-id').value),
                nombre:     document.getElementById('modal-u-nombre').value.trim(),
                email:      document.getElementById('modal-u-email').value.trim(),
                telefono:   document.getElementById('modal-u-telefono').value.trim(),
                es_admin:   document.getElementById('modal-u-admin').checked,
            };

            try {
                const res  = await fetch('admin_actualizar_usuario.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    credentials: 'include',
                    body: JSON.stringify(body)
                });
                const data = await res.json();
                if (data.success) {
                    cerrarModalUsuario();
                    cargar('usuarios');
                } else {
                    document.getElementById('modal-u-error').textContent = data.error || 'Error desconocido';
                }
            } catch {
                document.getElementById('modal-u-error').textContent = 'Error de conexión';
            } finally {
                btn.disabled = false;
                btn.innerHTML = '<i class="fas fa-save"></i> Guardar cambios';
            }
        });
    });

    async function confirmarEliminarUsuario(id, nombre) {
        if (!confirm(`¿Seguro que quieres eliminar al usuario "${nombre}"?\nSe eliminarán también todas sus reservas. Esta acción no se puede deshacer.`)) return;
        try {
            const res  = await fetch('admin_eliminar_usuario.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                credentials: 'include',
                body: JSON.stringify({ usuario_id: id })
            });
            const data = await res.json();
            if (data.success) {
                cargar('usuarios');
            } else {
                alert('Error: ' + (data.error || 'No se pudo eliminar'));
            }
        } catch {
            alert('Error de conexión');
        }
    }

    // ── MENSAJES ──────────────────────────────────────────
    function renderMensajes(data) {
        document.getElementById('badge-mensajes').style.display = 'none';

        let html = `<div class="section-card">
            <h2><i class="fas fa-envelope-open"></i> Mensajes de contacto <span class="count">${data.length}</span></h2>`;

        if (data.length === 0) {
            html += '<p class="vacio">No hay mensajes.</p>';
        } else {
            data.forEach(m => {
                const tipoClass = { queja:'tipo-queja', incidencia:'tipo-incidencia', sugerencia:'tipo-sugerencia', consulta:'tipo-consulta' }[m.tipo] || 'tipo-consulta';
                html += `
                <div class="mensaje-card ${m.leido ? '' : 'mensaje-nuevo'}">
                    <div class="mensaje-head">
                        <div>
                            <strong>${esc(m.nombre)}</strong>
                            <span class="msg-email">${esc(m.email)}</span>
                        </div>
                        <div class="mensaje-meta">
                            <span class="badge-tipo ${tipoClass}">${m.tipo}</span>
                            <span class="msg-fecha">${formatFechaHora(m.fecha_envio)}</span>
                        </div>
                    </div>
                    <p class="mensaje-asunto"><i class="fas fa-tag"></i> ${esc(m.asunto)}</p>
                    <p class="mensaje-texto">${esc(m.mensaje)}</p>
                    <a href="mailto:${esc(m.email)}?subject=Re: ${esc(m.asunto)}" class="btn-responder">
                        <i class="fas fa-reply"></i> Responder por email
                    </a>
                </div>`;
            });
        }
        html += '</div>';
        document.getElementById('contenido').innerHTML = html;
    }

    // ── Helpers ───────────────────────────────────────────

    function esc(s) {
        if (!s) return '';
        return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
    }
    function formatFecha(f) {
        if (!f) return '—';
        return new Date(f + 'T00:00:00').toLocaleDateString('es-ES', { day:'2-digit', month:'short', year:'numeric' });
    }
    function formatFechaHora(f) {
        if (!f) return '—';
        return new Date(f).toLocaleString('es-ES', { day:'2-digit', month:'short', hour:'2-digit', minute:'2-digit' });
    }
    </script>
    <!-- ── MODAL EDITAR USUARIO ─────────────────────────── -->

    <div id="modal-u-overlay" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,.55); z-index:1000; align-items:center; justify-content:center;">
        <div style="background:#fff; border-radius:12px; padding:2rem; width:min(460px,95vw); box-shadow:0 8px 32px rgba(0,0,0,.25); position:relative;">
            <button onclick="cerrarModalUsuario()" style="position:absolute;top:1rem;right:1rem;border:none;background:none;font-size:1.3rem;cursor:pointer;color:#666;">&times;</button>
            <h2 style="margin:0 0 1.2rem; font-size:1.2rem;"><i class="fas fa-user-pen" style="color:#4e73df;"></i> Editar usuario</h2>
            <form id="form-editar-usuario">
                <input type="hidden" id="modal-u-id">
                <div style="display:flex;flex-direction:column;gap:.75rem;">
                    <label>
                        <span style="font-size:.8rem;font-weight:600;color:#444;">Nombre</span>
                        <input type="text" id="modal-u-nombre" required maxlength="100"
                            style="width:100%;margin-top:.25rem;padding:.45rem .6rem;border:1px solid #ccc;border-radius:6px;font-size:.95rem;">
                    </label>
                    <label>
                        <span style="font-size:.8rem;font-weight:600;color:#444;">Email</span>
                        <input type="email" id="modal-u-email" required maxlength="150"
                            style="width:100%;margin-top:.25rem;padding:.45rem .6rem;border:1px solid #ccc;border-radius:6px;font-size:.95rem;">
                    </label>
                    <label>
                        <span style="font-size:.8rem;font-weight:600;color:#444;">Teléfono</span>
                        <input type="tel" id="modal-u-telefono" maxlength="20"
                            style="width:100%;margin-top:.25rem;padding:.45rem .6rem;border:1px solid #ccc;border-radius:6px;font-size:.95rem;">
                    </label>
                    <label style="display:flex;align-items:center;gap:.6rem;cursor:pointer;margin-top:.25rem;">
                        <input type="checkbox" id="modal-u-admin" style="width:1rem;height:1rem;cursor:pointer;">
                        <span style="font-size:.9rem;font-weight:600;color:#444;">🛡 Administrador</span>
                    </label>
                </div>
                <p id="modal-u-error" style="color:#e74c3c;font-size:.85rem;margin:.5rem 0 0;min-height:1.2em;"></p>
                <div style="display:flex;gap:.75rem;justify-content:flex-end;margin-top:1rem;">
                    <button type="button" onclick="cerrarModalUsuario()"
                        style="padding:.55rem 1.2rem;border:1px solid #ccc;border-radius:6px;background:#fff;cursor:pointer;font-size:.9rem;">
                        Cancelar
                    </button>
                    <button type="submit" id="btn-guardar-usuario"
                        style="padding:.55rem 1.4rem;border:none;border-radius:6px;background:#4e73df;color:#fff;cursor:pointer;font-size:.9rem;font-weight:600;">
                        <i class="fas fa-save"></i> Guardar cambios
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- ── MODAL EDITAR RESERVA ────────────────────────── -->

    <div id="modal-overlay" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,.55); z-index:1000; align-items:center; justify-content:center;">
        <div style="background:#fff; border-radius:12px; padding:2rem; width:min(520px,95vw); box-shadow:0 8px 32px rgba(0,0,0,.25); position:relative;">
            <button onclick="cerrarModal()" style="position:absolute;top:1rem;right:1rem;border:none;background:none;font-size:1.3rem;cursor:pointer;color:#666;">&times;</button>
            <h2 style="margin:0 0 .25rem; font-size:1.2rem;"><i class="fas fa-pen" style="color:#4e73df;"></i> Editar reserva</h2>
            <p style="margin:0 0 1.2rem;font-size:.85rem;color:#666;">
                <strong id="modal-usuario"></strong><br>
                <span id="modal-instalacion" style="font-style:italic;"></span>
            </p>
            <form id="form-editar-reserva">
                <input type="hidden" id="modal-reserva-id">
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:.75rem;">
                    <label style="grid-column:1/-1;">
                        <span style="font-size:.8rem;font-weight:600;color:#444;">Fecha</span>
                        <input type="date" id="modal-fecha" required
                            style="width:100%;margin-top:.25rem;padding:.45rem .6rem;border:1px solid #ccc;border-radius:6px;font-size:.95rem;">
                    </label>
                    <label>
                        <span style="font-size:.8rem;font-weight:600;color:#444;">Hora inicio</span>
                        <input type="time" id="modal-hora-inicio" required
                            style="width:100%;margin-top:.25rem;padding:.45rem .6rem;border:1px solid #ccc;border-radius:6px;font-size:.95rem;">
                    </label>
                    <label>
                        <span style="font-size:.8rem;font-weight:600;color:#444;">Hora fin</span>
                        <input type="time" id="modal-hora-fin" required
                            style="width:100%;margin-top:.25rem;padding:.45rem .6rem;border:1px solid #ccc;border-radius:6px;font-size:.95rem;">
                    </label>
                    <label>
                        <span style="font-size:.8rem;font-weight:600;color:#444;">Participantes</span>
                        <input type="number" id="modal-participantes" min="1" max="50" required
                            style="width:100%;margin-top:.25rem;padding:.45rem .6rem;border:1px solid #ccc;border-radius:6px;font-size:.95rem;">
                    </label>
                    <label>
                        <span style="font-size:.8rem;font-weight:600;color:#444;">Estado</span>
                        <select id="modal-estado" style="width:100%;margin-top:.25rem;padding:.45rem .6rem;border:1px solid #ccc;border-radius:6px;font-size:.95rem;">
                            <option value="activa">Activa</option>
                            <option value="cancelada">Cancelada</option>
                            <option value="completada">Completada</option>
                        </select>
                    </label>
                    <label style="grid-column:1/-1;">
                        <span style="font-size:.8rem;font-weight:600;color:#444;">Observaciones</span>
                        <textarea id="modal-observaciones" rows="2"
                            style="width:100%;margin-top:.25rem;padding:.45rem .6rem;border:1px solid #ccc;border-radius:6px;font-size:.95rem;resize:vertical;"></textarea>
                    </label>
                </div>
                <p id="modal-error" style="color:#e74c3c;font-size:.85rem;margin:.5rem 0 0;min-height:1.2em;"></p>
                <div style="display:flex;gap:.75rem;justify-content:flex-end;margin-top:1rem;">
                    <button type="button" onclick="cerrarModal()"
                        style="padding:.55rem 1.2rem;border:1px solid #ccc;border-radius:6px;background:#fff;cursor:pointer;font-size:.9rem;">
                        Cancelar
                    </button>
                    <button type="submit" id="btn-guardar-reserva"
                        style="padding:.55rem 1.4rem;border:none;border-radius:6px;background:#4e73df;color:#fff;cursor:pointer;font-size:.9rem;font-weight:600;">
                        <i class="fas fa-save"></i> Guardar cambios
                    </button>
                </div>
            </form>
        </div>
    </div>

    <style>
    .td-acciones { white-space:nowrap; }
    .btn-accion {
        border: none; border-radius: 6px; padding: .3rem .55rem;
        cursor: pointer; font-size: .8rem; margin: 0 2px;
        transition: opacity .15s;
    }
    .btn-accion:hover { opacity: .8; }
    .btn-editar  { background: #4e73df; color: #fff; }
    .btn-eliminar { background: #e74a3b; color: #fff; }
    </style>
</body>
</html>
