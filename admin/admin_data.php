<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['usuario_id'])) {
    http_response_code(401); 
    echo json_encode(['error' => 'No autenticado']);
    exit;
}

require '../comun/db.php';

try {
    $pdo = getDbConnection();

    // Verificar que es admin
    $stmt = $pdo->prepare("SELECT es_admin FROM usuarios WHERE id = :id");
    $stmt->execute([':id' => $_SESSION['usuario_id']]);
    $u = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$u || !$u['es_admin']) {
        http_response_code(403);
        echo json_encode(['error' => 'Acceso denegado']);
        exit;
    }

    $seccion = $_GET['seccion'] ?? 'resumen';

    // ── RESUMEN ───────────────────────────────────────────────────────────
    if ($seccion === 'resumen') {

        // Una sola query con CTEs para las métricas y reservas de hoy
        $stmt = $pdo->query("
            WITH
            reservasActivas AS (
                SELECT
                    COUNT(*) FILTER (WHERE estado != 'cancelada') AS reservas_activas,
                    COUNT(*) FILTER (WHERE fecha = CURRENT_DATE AND estado != 'cancelada') AS reservas_hoy,
                    (SELECT COUNT(*) FROM usuarios) AS usuarios,
                    (SELECT COUNT(*) FROM contacto WHERE leido = FALSE) AS mensajes_sin_leer
                FROM reservas
            ),
            hoy AS (
                SELECT r.id, u.nombre AS usuario, u.email,
                       i.nombre AS instalacion,
                       r.hora_inicio, r.hora_fin, r.participantes, r.estado
                FROM reservas r
                JOIN usuarios u      ON r.usuario_id     = u.id
                JOIN instalaciones i ON r.instalacion_id = i.id
                WHERE r.fecha = CURRENT_DATE
                ORDER BY r.hora_inicio ASC
            )
            SELECT
                (SELECT row_to_json(k) FROM reservasActivas k)          AS kpi,
                (SELECT json_agg(h) FROM hoy h)                         AS reservas_hoy_lista
        ");

        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        $kpi            = json_decode($row['kpi'],              true);
        $hoy_lista      = json_decode($row['reservas_hoy_lista'], true) ?? []; 

        // Marcar mensajes como leídos tras leerlos
        try {
            $pdo->exec("UPDATE contacto SET leido = TRUE WHERE leido = FALSE");
        } catch (Exception $e) { /* tabla puede no existir */ }

        echo json_encode([
            'reservas_activas'   => (int)($kpi['reservas_activas']   ?? 0),
            'reservas_hoy'       => (int)($kpi['reservas_hoy']       ?? 0),
            'usuarios'           => (int)($kpi['usuarios']           ?? 0),
            'mensajes_sin_leer'  => (int)($kpi['mensajes_sin_leer']  ?? 0),
            'reservas_hoy_lista' => $hoy_lista,
        ]);

    // ── RESERVAS ──────────────────────────────────────────────────────────
    } elseif ($seccion === 'reservas') {

        $stmt = $pdo->query("
            SELECT r.id,
                   u.nombre AS usuario,
                   u.email,
                   i.nombre AS instalacion,
                   r.fecha,
                   r.hora_inicio,
                   r.hora_fin,
                   r.participantes,
                   r.observaciones,
                   r.estado,
                   r.creado_en AS fecha_creacion
            FROM reservas r
            JOIN usuarios    u ON r.usuario_id     = u.id
            JOIN instalaciones i ON r.instalacion_id = i.id
            ORDER BY r.fecha DESC, r.hora_inicio DESC
            LIMIT 100
        ");
        echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));

    // ── USUARIOS ──────────────────────────────────────────────────────────
    } elseif ($seccion === 'usuarios') {

        $stmt = $pdo->query("
            SELECT u.id,
                   u.nombre,
                   u.email,
                   u.telefono,
                   u.es_admin,
                   COUNT(r.id) AS total_reservas
            FROM usuarios u
            LEFT JOIN reservas r ON r.usuario_id = u.id AND r.estado != 'cancelada'
            GROUP BY u.id, u.nombre, u.email, u.telefono, u.es_admin
            ORDER BY u.id DESC
        ");
        echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));

    // ── MENSAJES ──────────────────────────────────────────────────────────
    } elseif ($seccion === 'mensajes') {

        try {
            $stmt = $pdo->query("
                SELECT id, nombre, email, tipo, asunto, mensaje,
                       fecha_envio, leido
                FROM contacto
                ORDER BY fecha_envio DESC
                LIMIT 50
            ");
            $mensajes = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Marcar todos como leídos
            $pdo->exec("UPDATE contacto SET leido = TRUE WHERE leido = FALSE");

            echo json_encode($mensajes);
        } catch (Exception $e) {
            echo json_encode([]);
        }
    }

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Error de base de datos: ' . $e->getMessage()]);
}
