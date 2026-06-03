<?php
session_start();
?><!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://fonts.googleapis.com/css2?family=Bungee+Spice&family=Roboto:ital,wght@0,100..900;1,100..900&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Bungee+Spice&family=Quantico:ital,wght@0,400;0,700;1,400;1,700&family=Roboto:ital,wght@0,100..900;1,100..900&display=swap" rel="stylesheet">
    <style>@import url('https://fonts.googleapis.com/css2?family=Merriweather:wght@300;400;700&display=swap');</style>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="../Styles/indexStyles.css">
    <link rel="stylesheet" href="../Styles/contactoStyles.css">
    <title>Contacto — Polideportivo Entrerobles</title>
    <script src="../comun/header.js"></script>
</head>
<body>

    <!-- Cabecera -->
    <div id="header">
        <img src="../images/logoERwhite.png" alt="Logo Escudo" id="logo_escudo">
        <h1>POLIDEPORTIVO ENTREROBLES</h1>
        <div class="headerButtons">
            <div class="HeaderButtonsGroup1">
                <a href="../index.html" class="HeaderButton">INICIO</a>
                <a href="../index.html#instalacionesWidgetsTitle" class="HeaderButton">INSTALACIONES</a>
            </div>
            <div class="HeaderButtonsGroup2">
                <a href="../index.html#horariosTitle" class="HeaderButton">HORARIOS</a>
            </div>
            <div class="AccountButtons" id="accountButtons"></div>
        </div>
    </div>

    <main class="contacto-main">

        <!-- Hero de la sección -->
        <div class="contacto-hero">
            <h2>Contacto e Incidencias</h2>
            <p>¿Tienes algún problema, sugerencia o queja? Escríbenos y te responderemos lo antes posible.</p>
        </div>

        <div class="contacto-layout">

            <!-- Formulario -->
            <div class="contacto-form-card">

                <!-- Toast -->
                <div id="toast" class="toast" style="display:none;"></div>

                <form id="formContacto" novalidate>

                    <div class="form-row-2">
                        <div class="form-group">
                            <label for="nombre">Nombre completo <span class="req">*</span></label>
                            <input type="text" id="nombre" name="nombre" placeholder="Tu nombre" required>
                        </div>
                        <div class="form-group">
                            <label for="email">Email <span class="req">*</span></label>
                            <input type="email" id="email" name="email" placeholder="tu@email.com" required>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="tipo">Tipo de consulta <span class="req">*</span></label>
                        <div class="tipo-grid">
                            <label class="tipo-opcion">
                                <input type="radio" name="tipo" value="queja" required>
                                <span><i class="fas fa-exclamation-circle"></i> Queja</span>
                            </label>
                            <label class="tipo-opcion">
                                <input type="radio" name="tipo" value="incidencia">
                                <span><i class="fas fa-tools"></i> Incidencia</span>
                            </label>
                            <label class="tipo-opcion">
                                <input type="radio" name="tipo" value="sugerencia">
                                <span><i class="fas fa-lightbulb"></i> Sugerencia</span>
                            </label>
                            <label class="tipo-opcion">
                                <input type="radio" name="tipo" value="consulta" checked>
                                <span><i class="fas fa-question-circle"></i> Consulta general</span>
                            </label>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="asunto">Asunto <span class="req">*</span></label>
                        <input type="text" id="asunto" name="asunto" placeholder="Describe brevemente el motivo" required>
                    </div>

                    <div class="form-group">
                        <label for="mensaje">Mensaje <span class="req">*</span></label>
                        <textarea id="mensaje" name="mensaje" rows="5" placeholder="Explícanos con detalle tu consulta, queja o incidencia..." required></textarea>
                    </div>

                    <button type="submit" class="btn-enviar" id="btnEnviar">
                        <i class="fas fa-paper-plane"></i> Enviar mensaje
                    </button>
                </form>
            </div>

            <!-- Panel lateral de info -->
            <aside class="contacto-info">

                <div class="info-card">
                    <h3><i class="fas fa-clock"></i> Horario de atención</h3>
                    <p>Lunes a Viernes: 09:00 – 14:00</p>
                    <p>Tardes: 17:00 – 20:00</p>
                    <p>Sábados: 09:00 – 13:00</p>
                </div>

                <div class="info-card">
                    <h3><i class="fas fa-phone"></i> Teléfono</h3>
                    <p><a href="tel:+34000000000">+34 000 000 000</a></p>
                </div>

                <div class="info-card">
                    <h3><i class="fas fa-envelope"></i> Email</h3>
                    <p><a href="mailto:info@aytoentrerobles.com">info@aytoentrerobles.com</a></p>
                </div>

                <div class="info-card">
                    <h3><i class="fas fa-map-marker-alt"></i> Dirección</h3>
                    <p>Calle Principal, 123<br>Entrerobles</p>
                </div>

                <div class="info-card info-card--aviso">
                    <h3><i class="fas fa-info-circle"></i> Tiempo de respuesta</h3>
                    <p>Intentamos responder en un plazo máximo de <strong>48 horas laborables</strong>.</p>
                </div>

            </aside>
        </div>
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
                        <li><a href="../contacto/contacto.php">Contacto e Incidencias</a></li>
                        <li><a href="#">Preguntas Frecuentes</a></li>
                    </ul>
                </div>
                <div class="footer-links">
                    <h4>SÍGUENOS</h4>
                    <ul class="social-icons">
                        <li><a href="#"><i class="fab fa-facebook-f"></i></a></li>
                        <li><a href="#"><i class="fab fa-twitter"></i></a></li>
                        <li><a href="#"><i class="fab fa-instagram"></i></a></li>
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

    <script>
        // Pre-rellenar si hay sesión
        fetch('../usuarios/get_user_info.php', { credentials: 'include' })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    document.getElementById('nombre').value = data.nombre || '';
                    document.getElementById('email').value  = data.email  || '';
                    document.getElementById('nombre').readOnly = true;
                    document.getElementById('email').readOnly  = true;
                }
            }).catch(() => {});

        // Toast
        function toast(msg, tipo) {
            const el = document.getElementById('toast');
            el.textContent = (tipo === 'ok' ? '✅ ' : '❌ ') + msg;
            el.className = 'toast toast--' + tipo;
            el.style.display = 'block';
            setTimeout(() => el.style.display = 'none', 4000);
        }

        // Envío
        document.getElementById('formContacto').addEventListener('submit', function(e) {
            e.preventDefault();

            const nombre  = document.getElementById('nombre').value.trim();
            const email   = document.getElementById('email').value.trim();
            const asunto  = document.getElementById('asunto').value.trim();
            const mensaje = document.getElementById('mensaje').value.trim();
            const tipo    = document.querySelector('input[name="tipo"]:checked')?.value || 'consulta';

            if (!nombre || !email || !asunto || !mensaje) {
                toast('Por favor rellena todos los campos.', 'error');
                return;
            }

            const btn = document.getElementById('btnEnviar');
            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Enviando...';

            fetch('procesar_contacto.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                credentials: 'include',
                body: JSON.stringify({ nombre, email, tipo, asunto, mensaje })
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    toast('Mensaje enviado. Te responderemos en menos de 48h.', 'ok');
                    document.getElementById('asunto').value  = '';
                    document.getElementById('mensaje').value = '';
                } else {
                    toast(data.error || 'Error al enviar el mensaje.', 'error');
                }
            })
            .catch(() => toast('Error de conexión. Inténtalo de nuevo.', 'error'))
            .finally(() => {
                btn.disabled = false;
                btn.innerHTML = '<i class="fas fa-paper-plane"></i> Enviar mensaje';
            });
        });
    </script>
</body>
</html>
