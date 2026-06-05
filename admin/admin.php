<?php
session_start();

// ── 1. Protección de sesión ──────────────────────────────
if (!isset($_SESSION['usuario_id'])) {
    header('Location: ../login.html');
    exit;
}

require '../comun/db.php';
try {
    $pdo    = getDbConnection();
    $stmt   = $pdo->prepare("SELECT es_admin, nombre FROM usuarios WHERE id = :id");
    $stmt->execute([':id' => $_SESSION['usuario_id']]);
    $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$usuario || !$usuario['es_admin']) {
        header('Location: ../index.html');
        exit;
    }
    $adminNombre = $usuario['nombre'];
} catch (Exception $e) {
    header('Location: ../index.html');
    exit;
}

// ── 2. ¿Qué sección pide el usuario? ─────────────────────
// En lugar de JS, usamos ?sec=reservas en la URL
$seccion = $_GET['sec'] ?? 'resumen';
// Permitir solo valores válidos (seguridad)
$secciones_validas = ['resumen', 'reservas', 'usuarios'];
if (!in_array($seccion, $secciones_validas)) {
    $seccion = 'resumen';
}

// ── 3. Acciones POST (eliminar, editar) ───────────────────
$mensaje_ok    = '';
$mensaje_error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Eliminar reserva
    if (isset($_POST['eliminar_reserva_id'])) {
        $id = (int) $_POST['eliminar_reserva_id'];
        try {
            $stmt = $pdo->prepare("DELETE FROM reservas WHERE id = :id");
            $stmt->execute([':id' => $id]);
            $mensaje_ok = "Reserva eliminada correctamente.";
        } catch (Exception $e) {
            $mensaje_error = "Error al eliminar la reserva.";
        }
    }

    // Editar reserva
    if (isset($_POST['guardar_reserva_id'])) {
        $id           = (int)   $_POST['guardar_reserva_id'];
        $fecha        = trim(   $_POST['fecha']        ?? '');
        $hora_inicio  = trim(   $_POST['hora_inicio']  ?? '');
        $hora_fin     = trim(   $_POST['hora_fin']     ?? '');
        $participantes= (int)   $_POST['participantes'] ?? 1;
        $estado       = trim(   $_POST['estado']       ?? 'activa');
        $observaciones= trim(   $_POST['observaciones']?? '');

        $estados_validos = ['activa', 'cancelada', 'completada'];
        if (!in_array($estado, $estados_validos)) $estado = 'activa';

        try {
            $stmt = $pdo->prepare("
                UPDATE reservas
                SET fecha = :fecha, hora_inicio = :hi, hora_fin = :hf,
                    participantes = :part, estado = :est, observaciones = :obs
                WHERE id = :id
            ");
            $stmt->execute([
                ':fecha' => $fecha,
                ':hi'    => $hora_inicio,
                ':hf'    => $hora_fin,
                ':part'  => $participantes,
                ':est'   => $estado,
                ':obs'   => $observaciones,
                ':id'    => $id,
            ]);
            $mensaje_ok = "Reserva actualizada correctamente.";
        } catch (Exception $e) {
            $mensaje_error = "Error al actualizar la reserva.";
        }
    }

    // Eliminar usuario
    if (isset($_POST['eliminar_usuario_id'])) {
        $id = (int) $_POST['eliminar_usuario_id'];
        if ($id === (int)$_SESSION['usuario_id']) {
            $mensaje_error = "No puedes eliminarte a ti mismo.";
        } else {
            try {
                // Las reservas se eliminan en cascada si tienes ON DELETE CASCADE
                // Si no, elimínalas antes:
                $pdo->prepare("DELETE FROM reservas WHERE usuario_id = :id")->execute([':id' => $id]);
                $pdo->prepare("DELETE FROM usuarios WHERE id = :id")->execute([':id' => $id]);
                $mensaje_ok = "Usuario eliminado.";
            } catch (Exception $e) {
                $mensaje_error = "Error al eliminar el usuario.";
            }
        }
    }
}

// ── 4. Consultas según la sección activa ──────────────────
$datos = [];

if ($seccion === 'resumen') {
    $datos['reservas_activas'] = $pdo->query("SELECT COUNT(*) FROM reservas WHERE estado = 'activa'")->fetchColumn();
    $datos['reservas_hoy']     = $pdo->query("SELECT COUNT(*) FROM reservas WHERE fecha = CURRENT_DATE")->fetchColumn();
    $datos['usuarios']         = $pdo->query("SELECT COUNT(*) FROM usuarios")->fetchColumn();

    $stmt = $pdo->query("
        SELECT r.*, u.nombre AS usuario, u.email
        FROM reservas r JOIN usuarios u ON r.usuario_id = u.id
        WHERE r.fecha = CURRENT_DATE ORDER BY r.hora_inicio
    ");
    $datos['reservas_hoy_lista'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

if ($seccion === 'reservas') {
    $stmt = $pdo->query("
        SELECT r.*, u.nombre AS usuario, u.email,
               i.nombre AS instalacion
        FROM reservas r
        JOIN usuarios u ON r.usuario_id = u.id
        JOIN instalaciones i ON r.instalacion_id = i.id
        ORDER BY r.fecha DESC, r.hora_inicio
    ");
    $datos['lista'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

if ($seccion === 'usuarios') {
    $stmt = $pdo->query("
        SELECT u.*, COUNT(r.id) AS total_reservas
        FROM usuarios u
        LEFT JOIN reservas r ON r.usuario_id = u.id
        GROUP BY u.id ORDER BY u.nombre
    ");
    $datos['lista'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="../styles/adminStyles.css">
    <title>Panel de Administración — Polideportivo Entrerobles</title>
</head>
<body>

<!-- Sidebar -->
<aside class="sidebar">
    <div class="sidebar-logo">
        <img src="../images/logoERwhite.png" alt="Logo">
        <span>Panel de Administración</span>
    </div>
    <nav class="sidebar-nav">
        <!-- Los enlaces ahora llevan ?sec=... en lugar de usar JS -->
        <a href="admin.php?sec=resumen"  class="nav-item <?= $seccion === 'resumen'  ? 'active' : '' ?>">
            <i class="fas fa-chart-pie"></i> Resumen
        </a>
        <a href="admin.php?sec=reservas" class="nav-item <?= $seccion === 'reservas' ? 'active' : '' ?>">
            <i class="fas fa-calendar-check"></i> Reservas
        </a>
        <a href="admin.php?sec=usuarios" class="nav-item <?= $seccion === 'usuarios' ? 'active' : '' ?>">
            <i class="fas fa-users"></i> Usuarios
        </a>
    </nav>
    <div class="sidebar-footer">
        <span class="admin-name">
            <i class="fas fa-user-shield"></i>
            <?= htmlspecialchars($adminNombre) ?>
        </span>
        <a href="../index.html" class="btn-volver"><i class="fas fa-arrow-left"></i> Volver al sitio</a>
        <a href="../usuarios/logout.php" class="btn-logout"><i class="fas fa-sign-out-alt"></i> Cerrar sesión</a>
    </div>
</aside>

<!-- Main -->
<main class="admin-main">

    <header class="admin-topbar">
        <h1><?= htmlspecialchars(ucfirst($seccion)) ?></h1>
        <!-- La fecha ahora la calcula PHP, no JS -->
        <span class="fecha-hoy">
            <?= date_create()->format('l, d \d\e F \d\e Y') ?>
            <!-- O en español con IntlDateFormatter si lo tienes disponible -->
        </span>
    </header>

    <div class="admin-contenido">

        <!-- Mensajes de éxito / error -->
        <?php if ($mensaje_ok):    ?><div class="alert alert-ok">   <?= htmlspecialchars($mensaje_ok)    ?></div><?php endif; ?>
        <?php if ($mensaje_error): ?><div class="alert alert-error"><?= htmlspecialchars($mensaje_error) ?></div><?php endif; ?>

        <!-- ── RESUMEN ──────────────────────────────────── -->
        <?php if ($seccion === 'resumen'): ?>

        <div class="cards-grid">
            <div class="cards-card cards-blue">
                <i class="fas fa-calendar-check cards-icon"></i>
                <div class="cards-val"><?= $datos['reservas_activas'] ?></div>
                <div class="cards-label">Reservas activas</div>
            </div>
            <div class="cards-card cards-green">
                <i class="fas fa-calendar-day cards-icon"></i>
                <div class="cards-val"><?= $datos['reservas_hoy'] ?></div>
                <div class="cards-label">Reservas hoy</div>
            </div>
            <div class="cards-card cards-purple">
                <i class="fas fa-users cards-icon"></i>
                <div class="cards-val"><?= $datos['usuarios'] ?></div>
                <div class="cards-label">Usuarios registrados</div>
            </div>
        </div>

        <div class="section-card">
            <h2><i class="fas fa-sun"></i> Reservas de hoy</h2>
            <?php if (empty($datos['reservas_hoy_lista'])): ?>
                <p class="vacio">No hay reservas para hoy.</p>
            <?php else: ?>
                <div class="tabla-wrap">
                    <table class="tabla">
                        <thead><tr>
                            <th>Usuario</th>
                            <th>Instalación</th>
                            <th>Horario</th>
                            <th>Participantes</th>
                            <th>Estado</th>
                        </tr></thead>
                        <tbody>
                        <?php foreach ($datos['reservas_hoy_lista'] as $r): ?>
                            <tr>
                                <td><strong><?= htmlspecialchars($r['usuario']) ?></strong></td>
                                <td><?= htmlspecialchars($r['instalacion']) ?></td>
                                <td><?= substr($r['hora_inicio'],0,5) ?> – <?= substr($r['hora_fin'],0,5) ?></td>
                                <td><?= (int)$r['participantes'] ?></td>
                                <td>
                                    <span class="badge-estado <?= $r['estado'] === 'cancelada' ? 'badge-cancelada' : 'badge-activa' ?>">
                                        <?= htmlspecialchars($r['estado']) ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>

        <!-- ── RESERVAS ─────────────────────────────────── -->
        <?php elseif ($seccion === 'reservas'): ?>

        <div class="section-card">
            <h2><i class="fas fa-list"></i> Todas las reservas
                <span class="count"><?= count($datos['lista']) ?></span>
            </h2>
            <div class="tabla-wrap">
                <table class="tabla">
                    <thead><tr>
                        <th>Fecha</th>
                        <th>Usuario</th>
                        <th>Instalación</th>
                        <th>Horario</th>
                        <th>Participantes</th>
                        <th>Estado</th>
                        <th>Acciones</th>
                    </tr></thead>
                    <tbody>
                    <?php foreach ($datos['lista'] as $r): ?>
                        <tr>
                            <td><?= htmlspecialchars($r['fecha']) ?></td>
                            <td>
                                <strong><?= htmlspecialchars($r['usuario']) ?></strong><br>
                                <small><?= htmlspecialchars($r['email']) ?></small>
                            </td>
                            <td><?= htmlspecialchars($r['instalacion']) ?></td>
                            <td><?= substr($r['hora_inicio'],0,5) ?> – <?= substr($r['hora_fin'],0,5) ?></td>
                            <td><?= (int)$r['participantes'] ?></td>
                            <td>
                                <span class="badge-estado <?= $r['estado'] === 'cancelada' ? 'badge-cancelada' : 'badge-activa' ?>">
                                    <?= htmlspecialchars($r['estado']) ?>
                                </span>
                            </td>
                            <td class="td-acciones">
                                <!-- Botón editar: abre el formulario de edición -->
                                <a href="admin.php?sec=reservas&editar=<?= $r['id'] ?>"
                                   class="btn-accion btn-editar" title="Editar">
                                    <i class="fas fa-pen"></i>
                                </a>
                                <!-- Botón eliminar: formulario POST -->
                                <form method="POST" action="admin.php?sec=reservas"
                                      style="display:inline"
                                      onsubmit="return confirm('¿Eliminar esta reserva?')">
                                    <input type="hidden" name="eliminar_reserva_id" value="<?= $r['id'] ?>">
                                    <button type="submit" class="btn-accion btn-eliminar" title="Eliminar">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>

                        <!-- Fila inline de edición (se muestra si ?editar=ID) -->
                        <?php if (isset($_GET['editar']) && (int)$_GET['editar'] === (int)$r['id']): ?>
                        <tr class="fila-editar">
                            <td colspan="7">
                                <form method="POST" action="admin.php?sec=reservas" class="form-editar-inline">
                                    <input type="hidden" name="guardar_reserva_id" value="<?= $r['id'] ?>">
                                    <div class="form-editar-grid">
                                        <label>Fecha
                                            <input type="date" name="fecha" value="<?= htmlspecialchars($r['fecha']) ?>" required>
                                        </label>
                                        <label>Hora inicio
                                            <input type="time" name="hora_inicio" value="<?= substr($r['hora_inicio'],0,5) ?>" required>
                                        </label>
                                        <label>Hora fin
                                            <input type="time" name="hora_fin" value="<?= substr($r['hora_fin'],0,5) ?>" required>
                                        </label>
                                        <label>Participantes
                                            <input type="number" name="participantes" value="<?= (int)$r['participantes'] ?>" min="1" max="50" required>
                                        </label>
                                        <label>Estado
                                            <select name="estado">
                                                <option value="activa"     <?= $r['estado']==='activa'     ? 'selected':'' ?>>Activa</option>
                                                <option value="cancelada"  <?= $r['estado']==='cancelada'  ? 'selected':'' ?>>Cancelada</option>
                                                <option value="completada" <?= $r['estado']==='completada' ? 'selected':'' ?>>Completada</option>
                                            </select>
                                        </label>
                                        <label style="grid-column:1/-1">Observaciones
                                            <textarea name="observaciones"><?= htmlspecialchars($r['observaciones'] ?? '') ?></textarea>
                                        </label>
                                    </div>
                                    <div class="form-editar-btns">
                                        <a href="admin.php?sec=reservas" class="btn-cancelar">Cancelar</a>
                                        <button type="submit" class="btn-guardar">
                                            <i class="fas fa-save"></i> Guardar
                                        </button>
                                    </div>
                                </form>
                            </td>
                        </tr>
                        <?php endif; ?>

                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- ── USUARIOS ──────────────────────────────────── -->
        <?php elseif ($seccion === 'usuarios'): ?>

        <div class="section-card">
            <h2><i class="fas fa-users"></i> Usuarios registrados
                <span class="count"><?= count($datos['lista']) ?></span>
            </h2>
            <div class="tabla-wrap">
                <table class="tabla">
                    <thead><tr>
                        <th>ID</th><th>Nombre</th><th>Email</th>
                        <th>Teléfono</th><th>Reservas</th><th>Rol</th><th>Acciones</th>
                    </tr></thead>
                    <tbody>
                    <?php foreach ($datos['lista'] as $u): ?>
                        <tr>
                            <td><?= (int)$u['id'] ?></td>
                            <td><strong><?= htmlspecialchars($u['nombre']) ?></strong></td>
                            <td><?= htmlspecialchars($u['email']) ?></td>
                            <td><?= htmlspecialchars($u['telefono'] ?? '—') ?></td>
                            <td><?= (int)$u['total_reservas'] ?></td>
                            <td>
                                <span class="badge-estado <?= $u['es_admin'] ? 'badge-admin' : 'badge-user' ?>">
                                    <?= $u['es_admin'] ? '🛡 Admin' : 'Usuario' ?>
                                </span>
                            </td>
                            <td class="td-acciones">
                                <?php if ((int)$u['id'] !== (int)$_SESSION['usuario_id']): ?>
                                    <form method="POST" action="admin.php?sec=usuarios"
                                          style="display:inline"
                                          onsubmit="return confirm('¿Eliminar al usuario <?= htmlspecialchars($u['nombre'], ENT_QUOTES) ?>?')">
                                        <input type="hidden" name="eliminar_usuario_id" value="<?= $u['id'] ?>">
                                        <button type="submit" class="btn-accion btn-eliminar" title="Eliminar">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </form>
                                <?php else: ?>
                                    <span style="font-size:.75rem;color:#aaa">yo</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <?php endif; ?>

    </div><!-- /admin-contenido -->
</main>

</body>
</html>