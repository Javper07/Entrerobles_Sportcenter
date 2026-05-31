<?php
session_start();
header('Content-Type: application/json');

// Solo admins
if (!isset($_SESSION['usuario_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'No autenticado']);
    exit;
}

require 'db.php';

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

    if ($seccion === 'resumen') {

        // Totales
        $totales = [];

        $stmt = $pdo->query("SELECT COUNT(*) FROM reservas WHERE estado != 'cancelada'");
        $totales['reservas_activas'] = (int)$stmt->fetchColumn();

        $stmt = $pdo->query("SELECT COUNT(*) FROM reservas WHERE fecha = CURRENT_DATE AND estado != 'cancelada'");
        $totales['reservas_hoy'] = (int)$stmt->fetchColumn();

        $stmt = $pdo->query("SELECT COUNT(*) FROM usuarios");
        $totales['usuarios'] = (int)$stmt->fetchColumn();

        // Mensajes sin leer (puede fallar si la tabla no existe aún)
        try {
            $stmt = $pdo->query("SELECT COUNT(*) FROM contacto WHERE leido = FALSE");
            $totales['mensajes_sin_leer'] = (int)$stmt->fetchColumn();
        } catch (Exception $e) {
            $totales['mensajes_sin_leer'] = 0;
        }

        // Reservas de hoy
        $stmt = $pdo->query("
            SELECT r.id, u.nombre as usuario, u.email, i.nombre as instalacion,
                   r.hora_inicio, r.hora_fin, r.participantes, r.estado
            FROM reservas r
            JOIN usuarios u ON r.usuario_id = u.id
            JOIN instalaciones i ON r.instalacion_id = i.id
            WHERE r.fecha = CURRENT_DATE
            ORDER BY r.hora_inicio ASC
        ");
        $totales['reservas_hoy_lista'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Próximas reservas (7 días)
        $stmt = $pdo->query("
            SELECT r.id, u.nombre as usuario, i.nombre as instalacion,
                   r.fecha, r.hora_inicio, r.hora_fin, r.estado
            FROM reservas r
            JOIN usuarios u ON r.usuario_id = u.id
            JOIN instalaciones i ON r.instalacion_id = i.id
            WHERE r.fecha > CURRENT_DATE AND r.fecha <= CURRENT_DATE + INTERVAL '7 days'
              AND r.estado != 'cancelada'
            ORDER BY r.fecha ASC, r.hora_inicio ASC
            LIMIT 20
        ");
        $totales['proximas_reservas'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode($totales);

    } elseif ($seccion === 'usuarios') {

        $stmt = $pdo->query("
            SELECT u.id, u.nombre, u.email, u.telefono, u.es_admin,
                   COUNT(r.id) as total_reservas
            FROM usuarios u
            LEFT JOIN reservas r ON r.usuario_id = u.id AND r.estado != 'cancelada'
            GROUP BY u.id, u.nombre, u.email, u.telefono, u.es_admin
            ORDER BY u.id DESC
        ");
        echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));

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

    } elseif ($seccion === 'reservas') {

        $stmt = $pdo->query("
            SELECT r.id, u.nombre as usuario, u.email, i.nombre as instalacion,
                   r.fecha, r.hora_inicio, r.hora_fin, r.participantes,
                   r.observaciones, r.estado, r.fecha_creacion
            FROM reservas r
            JOIN usuarios u ON r.usuario_id = u.id
            JOIN instalaciones i ON r.instalacion_id = i.id
            ORDER BY r.fecha DESC, r.hora_inicio DESC
            LIMIT 100
        ");
        echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
    }

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Error de base de datos: ' . $e->getMessage()]);
}
