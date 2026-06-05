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
            <h1><?= $usuarioNombre ?> <?= $usuarioApellidos ?></h1>
            <p><?= $usuarioEmail ?></p>
        </div>

        <?php if ($flash): ?>
            <div class="message message-<?= $flash['type'] ?>">
                <?= htmlspecialchars($flash['text'], ENT_QUOTES, 'UTF-8') ?>
            </div>
        <?php endif; ?>

        <?php if ($mostrarFormularioEdicion && $editReserva): ?>
            <section class="reservas-section">
                <div class="reservas-section-header">
                    <h2>Editar reserva</h2>
                    <a href="account.php" class="btn-secondary">Volver a mis reservas</a>
                </div>

                <?php if (!empty($errores)): ?>
                    <div class="message message-error">
                        <ul>
                            <?php foreach ($errores as $error): ?>
                                <li><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>

                <form method="post" action="account.php" class="edit-reserva-form">
                    <input type="hidden" name="action" value="guardar">
                    <input type="hidden" name="reserva_id" value="<?= $editReserva['id'] ?>">

                    <div class="form-group">
                        <label>Instalación</label>
                        <input type="text" value="<?= htmlspecialchars($editReserva['instalacion_nombre'], ENT_QUOTES, 'UTF-8') ?>" disabled>
                    </div>
                    <div class="form-group">
                        <label>Fecha</label>
                        <input type="date" name="fecha" value="<?= $editReserva['fecha'] ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Hora de inicio</label>
                        <input type="time" name="hora_inicio" value="<?= $editReserva['hora_inicio'] ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Hora de fin</label>
                        <input type="time" name="hora_fin" value="<?= $editReserva['hora_fin'] ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Participantes</label>
                        <input type="number" name="participantes" min="1" value="<?= $editReserva['participantes'] ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Observaciones</label>
                        <textarea name="observaciones"><?= htmlspecialchars($editReserva['observaciones'], ENT_QUOTES, 'UTF-8') ?></textarea>
                    </div>
                    <div class="modal-buttons">
                        <button type="submit" class="btn-primary">Guardar cambios</button>
                        <a href="account.php" class="btn-secondary">Cancelar</a>
                    </div>
                </form>
            </section>
        <?php else: ?>
            <div class="account-content">
                <section class="reservas-section">
                    <div class="reservas-section-header">
                        <h2>Mis Reservas</h2>
                        <a href="../reservas/reservations.php" class="btn-primary">+ Nueva reserva</a>
                    </div>

                    <?php if (count($reservas) === 0): ?>
                        <div class="no-reservas">
                            <p>No tienes ninguna reserva registrada.</p>
                            <a href="../reservas/reservations.php" class="btn-primary">Crear nueva reserva</a>
                        </div>
                    <?php else: ?>
                        <div class="reservas-grid">
                            <?php foreach ($reservas as $reserva): ?>
                                <?php $cancelada = $reserva['estado'] === 'cancelada'; ?>
                                <div class="reserva-card<?= $cancelada ? ' reserva-card--cancelada' : '' ?>">
                                    <div class="reserva-header">
                                        <h3><?= htmlspecialchars($reserva['instalacion_nombre'], ENT_QUOTES, 'UTF-8') ?></h3>
                                        <span class="estado <?= $cancelada ? 'estado-cancelada' : 'estado-activa' ?>">
                                            <?= $cancelada ? 'Cancelada' : 'Activa' ?>
                                        </span>
                                    </div>
                                    <div class="reserva-details">
                                        <p><strong>Fecha:</strong> <?= htmlspecialchars(date('d/m/Y', strtotime($reserva['fecha'])), ENT_QUOTES, 'UTF-8') ?></p>
                                        <p><strong>Horario:</strong> <?= htmlspecialchars(substr($reserva['hora_inicio'], 0, 5), ENT_QUOTES, 'UTF-8') ?> – <?= htmlspecialchars(substr($reserva['hora_fin'], 0, 5), ENT_QUOTES, 'UTF-8') ?></p>
                                        <p><strong>Participantes:</strong> <?= htmlspecialchars($reserva['participantes'], ENT_QUOTES, 'UTF-8') ?></p>
                                        <?php if (trim($reserva['observaciones']) !== ''): ?>
                                            <p><strong>Observaciones:</strong> <?= nl2br(htmlspecialchars($reserva['observaciones'], ENT_QUOTES, 'UTF-8')) ?></p>
                                        <?php endif; ?>
                                    </div>
                                    <?php if (!$cancelada): ?>
                                        <div class="reserva-actions">
                                            <a href="account.php?editar=<?= $reserva['id'] ?>" class="btn-edit"><i class="fas fa-edit"></i> Modificar</a>
                                            <form method="post" action="account.php">
                                                <input type="hidden" name="action" value="cancelar">
                                                <input type="hidden" name="reserva_id" value="<?= $reserva['id'] ?>">
                                                <button type="submit" class="btn-cancel">Cancelar</button>
                                            </form>
                                            <form method="post" action="account.php">
                                                <input type="hidden" name="action" value="eliminar">
                                                <input type="hidden" name="reserva_id" value="<?= $reserva['id'] ?>">
                                                <button type="submit" class="btn-delete">Eliminar</button>
                                            </form>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </section>
            </div>
        <?php endif; ?>
    </main>

    <footer class="footer">
        <div class="footer-container">
            <div class="footer-row">
                <div class="footer-links">
                    <h4>CONTÁCTANOS</h4>
                    <ul>
                        <li><a href="#">123-456-7890</a></li>
                        <li><a href="#">info@aytoentrerobles.com</a></li>
                        <li><a href="#">Calle Principal, 123, Entrerobles</a></li>
                    </ul>
                </div>
                <div class="footer-links">
                    <h4>AYUDA</h4>
                    <ul>
                        <li><a href="#">Preguntas Frecuentes</a></li>
                        <li><a href="#">Compras</a></li>
                        <li><a href="#">Envios</a></li>
                        <li><a href="#">Sugerencias</a></li>
                    </ul>
                </div>
                <div class="footer-links">
                    <h4>SÍGUENOS</h4>
                    <ul class="social-icons">
                        <li><a href="#"><i class="fab fa-facebook-f"></i></a></li>
                        <li><a href="#"><i class="fab fa-twitter"></i></a></li>
                        <li><a href="#"><i class="fab fa-instagram"></i></a></li>
                        <li><a href="#"><i class="fab fa-linkedin"></i></a></li>
                    </ul>
                </div>
                <div class="footer-logo">
                    <img src="../images/logoERwhite.png" alt="Logo del Ayuntamiento de Entrerobles">
                </div>
            </div>
        </div>
        <div class="footer-divider"></div>
        <p class="bottom-text">Por un deporte más saludable y accesible para todos.</p>
    </footer>
</body>
</html>
