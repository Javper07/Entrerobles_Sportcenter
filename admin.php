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
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&family=Quantico:wght@400;700&display=swap" rel="stylesheet">
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
            ${tablaReservas(data, true)}
        </div>`;
        document.getElementById('contenido').innerHTML = html;
    }

    function tablaReservas(lista, conFecha) {
        if (lista.length === 0) return '<p class="vacio">Sin datos.</p>';
        let html = `<div class="tabla-wrap"><table class="tabla">
            <thead><tr>
                ${conFecha ? '<th>Fecha</th>' : ''}
                <th>Usuario</th>
                <th>Instalación</th>
                <th>Horario</th>
                <th>Participantes</th>
                <th>Estado</th>
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
            </tr>`;
        });
        html += '</tbody></table></div>';
        return html;
    }

    // ── USUARIOS ──────────────────────────────────────────
    function renderUsuarios(data) {
        let html = `<div class="section-card">
            <h2><i class="fas fa-users"></i> Usuarios registrados <span class="count">${data.length}</span></h2>
            <div class="tabla-wrap"><table class="tabla">
                <thead><tr>
                    <th>ID</th><th>Nombre</th><th>Email</th><th>Teléfono</th>
                    <th>Reservas</th><th>Rol</th>
                </tr></thead><tbody>`;
        data.forEach(u => {
            html += `<tr>
                <td>${u.id}</td>
                <td><strong>${esc(u.nombre)}</strong></td>
                <td>${esc(u.email)}</td>
                <td>${esc(u.telefono||'—')}</td>
                <td>${u.total_reservas}</td>
                <td><span class="badge-estado ${u.es_admin ? 'badge-admin' : 'badge-user'}">
                    ${u.es_admin ? '🛡 Admin' : 'Usuario'}
                </span></td>
            </tr>`;
        });
        html += '</tbody></table></div></div>';
        document.getElementById('contenido').innerHTML = html;
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
</body>
</html>
