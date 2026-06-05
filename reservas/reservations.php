<?php
// Este archivo es un wrapper de reservations.html que inyecta la verificación de sesión
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
    <link rel="stylesheet" href="../Styles/reservationsStyles.css">
    <link rel="stylesheet" href="../Styles/indexStyles.css">
    <title>Reservas de pistas — Polideportivo Entrerobles</title>
    
    <!-- ⭐ INYECCIÓN DE SESIÓN - SE EJECUTA PRIMERO -->
    <?php include '../comun/check_sesion.php'; ?>
    <script src="../comun/header.js"></script>
</head>
<body>
    <!-- Cabecera de la web: muestra navegación y estado de sesión. -->
    <div id="header"> 
        <img src="../images/logoERwhite.png" alt="Logo Escudo" id="logo_escudo">
        <h1>POLIDEPORTIVO ENTREROBLES</h1>
        <div class="headerButtons">

            <div class="HeaderButtonsGroup1">
                <a href="../index.html"  class="HeaderButton">INICIO</a>
                <a href="../index.html#instalacionesWidgetsTitle"  class="HeaderButton">INSTALACIONES</a>
            </div>

            <div class="HeaderButtonsGroup2">    
                <a href="../index.html#horariosTitle"  class="HeaderButton">HORARIOS</a>
                
            </div>
            
            <div class="AccountButtons" id="accountButtons">
                <!-- Se rellena dinámicamente -->
            </div>
        </div>
    </div>

    <!-- Contenido de reservas: formulario para crear reserva y panel lateral. -->
    <main class="main-content">
      <div class="container">

        <!-- Mensaje de éxito o error -->
        <div id="msg-reserva-ok" class="alert alert-success" style="display:none;">
          Reserva enviada correctamente
        </div>
        <div id="msg-reserva-error" class="alert alert-error" style="display:none;">
          Ha ocurrido un error al enviar la reserva. Inténtalo de nuevo.
        </div>

        <div class="grid">
          <!-- Formulario de Reserva -->
          <div class="form-section">
            <div class="card">
              <h2 class="section-title">Nueva Reserva</h2>
              
              <form class="form" action="procesar_reserva.php" method="POST" id="formReserva">

                <!-- Selección de Instalación -->
                <div class="form-group">
                  <label class="form-label">Seleccione la instalacion a reservar:</label>
                  <div class="instalations-grid">

                    <label class="instalation-card">
                      <input type="radio" name="instalacion" value="piscina" class="instalation-input" required>
                      <div class="instalation-content">
                        <p class="instalation-name">Piscina Cubierta</p>
                      </div>
                    </label>

                    <label class="instalation-card">
                      <input type="radio" name="instalacion" value="gimnasio" class="instalation-input">
                      <div class="instalation-content">
                        <p class="instalation-name">Gimnasio</p>
                      </div>
                    </label>

                    <label class="instalation-card">
                      <input type="radio" name="instalacion" value="pabellon" class="instalation-input">
                      <div class="instalation-content">
                        <p class="instalation-name">Pabellón Polideportivo</p>
                      </div>
                    </label>

                    <label class="instalation-card">
                      <input type="radio" name="instalacion" value="tenis" class="instalation-input">
                      <div class="instalation-content">
                        <p class="instalation-name">Pistas de Tenis</p>
                      </div>
                    </label>

                    <label class="instalation-card">
                      <input type="radio" name="instalacion" value="futbol" class="instalation-input">
                      <div class="instalation-content">
                        <p class="instalation-name">Campo de Fútbol</p>
                      </div>
                    </label>

                    <label class="instalation-card">
                      <input type="radio" name="instalacion" value="padel" class="instalation-input">
                      <div class="instalation-content">
                        <p class="instalation-name">Pistas de Pádel</p>
                      </div>
                    </label>

                  </div>
                </div>

                <!-- Fecha -->
                <div class="form-group">
                  <label for="fecha" class="form-label">
                    <svg class="icon" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                      <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                      <line x1="16" y1="2" x2="16" y2="6"></line>
                      <line x1="8" y1="2" x2="8" y2="6"></line>
                      <line x1="3" y1="10" x2="21" y2="10"></line>
                    </svg>
                    Fecha de la reserva
                  </label>
                  <input type="date" id="fecha" name="fecha" class="form-input" required>
                </div>

                <!-- Selección de franja horaria -->
                <div class="form-group" id="horario-section" style="display:none;">
                  <label class="form-label">
                    <svg class="icon" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                      <circle cx="12" cy="12" r="10"></circle>
                      <polyline points="12 6 12 12 16 14"></polyline>
                    </svg>
                    Selecciona tu franja horaria
                  </label>

                  <!-- Selector de duración -->
                  <div class="duracion-selector">
                    <span class="duracion-label">Duración:</span>
                    <label class="duracion-opcion">
                      <input type="radio" name="duracion" value="1" checked> 1 hora
                    </label>
                    <label class="duracion-opcion">
                      <input type="radio" name="duracion" value="1.5"> 1,5 horas
                    </label>
                  </div>

                  <!-- Turno mañana -->
                  <div class="turno-bloque">
                    <p class="turno-titulo"> Mañana (09:00 – 13:00)</p>
                    <div class="horas-grid" id="horas-manana"></div>
                  </div>

                  <!-- Turno tarde -->
                  <div class="turno-bloque">
                    <p class="turno-titulo"> Tarde (17:00 – 21:00)</p>
                    <div class="horas-grid" id="horas-tarde"></div>
                  </div>

                  <p id="hora-seleccionada-label" class="hora-sel-label" style="display:none;"></p>
                  <p id="horario-aviso" class="horario-aviso" style="display:none;"> Seleccione una instalación y fecha primero.</p>

                  <!-- Hidden inputs que se envían al formulario -->
                  <input type="hidden" id="hora_inicio" name="hora_inicio" required>
                  <input type="hidden" id="hora_fin"    name="hora_fin"    required>
                </div>

                <!-- Datos Personales -->
                <div class="personal-data">
                  <h3 class="subsection-title">Datos del solicitante</h3>
                  
                  <div class="form-row">
                    <div class="form-group">
                      <label for="nombre" class="form-label">Nombre completo</label>
                      <input type="text" id="nombre" name="nombre" class="form-input" placeholder="Tu nombre" required>
                    </div>

                    <div class="form-group">
                      <label for="telefono" class="form-label">Teléfono</label>
                      <input type="tel" id="telefono" name="telefono" class="form-input" placeholder="+34 000 000 000">
                    </div>

                    <div class="form-group">
                      <label for="email" class="form-label">Email</label>
                      <input type="email" id="email" name="email" class="form-input" placeholder="tu@email.com" required>
                    </div>

                    <div class="form-group">
                      <label for="participantes" class="form-label">
                        Nº Participantes
                      </label>
                      <input type="number" id="participantes" name="participantes" min="1" max="50" class="form-input" placeholder="1" required>
                    </div>
                  </div>
                </div>

                <!-- Observaciones -->
                <div class="form-group">
                  <label for="observaciones" class="form-label">Observaciones (opcional)</label>
                  <textarea id="observaciones" name="observaciones" rows="3" class="form-textarea" placeholder="Información adicional sobre tu reserva..."></textarea>
                </div>

                <!-- Botón de envío -->
                <button type="submit" class="submit-button">Solicitar Reserva</button>
              </form>
            </div>
          </div>

          <!-- Panel lateral -->
          <div class="sidebar">
            <!-- Tarifas -->
            <div class="card">
              <h3 class="card-title">Tarifas</h3>
              <div class="price-list">
                <div class="price-item">
                  <span class="price-label">Piscina</span>
                  <span class="price-value">15€/h</span>
                </div>
                <div class="price-item">
                  <span class="price-label">Pistas deportivas</span>
                  <span class="price-value">10€/h</span>
                </div>
                <div class="price-item">
                  <span class="price-label">Gimnasio</span>
                  <span class="price-value">35€/h</span>
                </div>
                <div class="price-item">
                  <span class="price-label">Pabellón completo</span>
                  <span class="price-value">25€/h</span>
                </div>
              </div>
            </div>

            <!-- Información importante -->
            <div class="card info-card">
              <h3 class="card-title-white">Información importante</h3>
              <ul class="info-list">
                <li>Las reservas deben realizarse con al menos 24h de antelación</li>
                <li>Cancelaciones gratuitas hasta 12h antes</li>
                <li>Recibirás un email de confirmación</li>
                <li>El pago se realiza en recepción</li>
              </ul>
            </div>

            <!-- Contacto -->
            <div class="card">
              <h3 class="card-title">¿Necesitas ayuda?</h3>
              <a class="contact-link">
                <div class="contact-icon">
                  <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path>
                    <circle cx="12" cy="10" r="3"></circle>
                  </svg>
                  <span class="contact-text"><strong>Dirección</strong><br/>Calle Principal, 123<br/>Entrerobles</span>
                </div>
              </a>
              <a class="contact-link" href="tel:+34000000000">
                <div class="contact-icon">
                  
                  <span class="contact-text"><strong>Teléfono</strong><br/>+34 000 000 000</span>
                </div>
              </a>
              <a class="contact-link" href="../contacto/contacto.php">
                <div class="contact-icon">
                  <span class="contact-text"><strong>Contacto e Incidencias</strong><br/>Quejas, sugerencias...</span>
                </div>
              </a>
              <a class="contact-link" href="mailto:info@polideportivo.com">
                <div class="contact-icon">
                 
                  <span class="contact-text"><strong>Email</strong><br/>info@polideportivo.com</span>
                </div>
              </a>
            </div>
          </div>
        </div>
      </div>
    </main>

    <!-- Pie de página simple para cerrar la página de reserva. -->
    <footer>
        <div class="footer-content">
            <p>© 2024 Polideportivo Entrerobles. Todos los derechos reservados.</p>
        </div>
        <div class="footer-divider"></div>
        <p class="bottom-text">Por un deporte más saludable y accesible para todos.</p>
    </footer>

    <script>
      // JavaScript de reservas: controla mensajes, selección de horario y envío.
      // ── Mensajes de éxito/error ──────────────────────────────────────
      const params = new URLSearchParams(window.location.search);
      if (params.get('reserva') === 'ok') {
        document.getElementById('msg-reserva-ok').style.display = 'block';
        window.scrollTo({ top: 0, behavior: 'smooth' });
      }
      if (params.get('reserva') === 'error') {
        document.getElementById('msg-reserva-error').style.display = 'block';
        window.scrollTo({ top: 0, behavior: 'smooth' });
      }
      if (params.get('reserva') === 'ocupado') {
        const el = document.getElementById('msg-reserva-error');
        el.textContent = '⚠️ Esa franja ya está reservada. Elige otro horario.';
        el.style.display = 'block';
        window.scrollTo({ top: 0, behavior: 'smooth' });
      }

      // ── Datos del usuario logueado ────────────────────────────────────
      fetch('../usuarios/get_user_info.php', { credentials: 'include' })
        .then(r => r.json())
        .then(data => {
          if (data.success) {
            document.getElementById('nombre').value = data.nombre || '';
            document.getElementById('email').value  = data.email  || '';
            document.getElementById('nombre').readOnly = true;
            document.getElementById('email').readOnly  = true;
          }
        })
        .catch(() => {});

      // ── Lógica de la cuadrícula de horas ─────────────────────────────

      // Franjas horarias disponibles (en minutos desde medianoche)
      const TURNOS = {
        manana: { inicio: 9*60,  fin: 13*60 },
        tarde:  { inicio: 17*60, fin: 21*60 }
      };

      // Genera array de horas de inicio posibles para un turno dada duración (en horas)
      function generarFranjas(turno, duracionH) {
        const franjas = [];
        const paso = duracionH === 1.5 ? 90 : 60;
        for (let t = turno.inicio; t + paso <= turno.fin; t += paso) {
          franjas.push(t);
        }
        return franjas;
      }

      function minToHHMM(min) {
        const h = String(Math.floor(min / 60)).padStart(2, '0');
        const m = String(min % 60).padStart(2, '0');
        return h + ':' + m;
      }

      // Comprueba si una franja (inicio, fin en minutos) se solapa con reservas ocupadas
      function estaOcupada(inicioMin, finMin, ocupadas) {
        return ocupadas.some(r => {
          const rIni = timeToMin(r.hora_inicio);
          const rFin = timeToMin(r.hora_fin);
          return inicioMin < rFin && finMin > rIni;
        });
      }

      function timeToMin(hhmm) {
        const [h, m] = hhmm.split(':').map(Number);
        return h * 60 + m;
      }

      let horaInicioSel = null; // minutos
      let horaFinSel    = null;

      function renderCuadricula(ocupadas) {
        const duracion = parseFloat(document.querySelector('input[name="duracion"]:checked').value);
        const paso = duracion === 1.5 ? 90 : 60;

        ['manana', 'tarde'].forEach(turnoKey => {
          const container = document.getElementById('horas-' + turnoKey);
          container.innerHTML = '';
          const franjas = generarFranjas(TURNOS[turnoKey], duracion);

          franjas.forEach(inicioMin => {
            const finMin  = inicioMin + paso;
            const ocupada = estaOcupada(inicioMin, finMin, ocupadas);
            const esSel   = (inicioMin === horaInicioSel);

            const btn = document.createElement('button');
            btn.type = 'button';
            btn.textContent = minToHHMM(inicioMin) + ' – ' + minToHHMM(finMin);
            btn.className = 'hora-btn' +
              (ocupada  ? ' hora-btn--ocupada'      : '') +
              (esSel    ? ' hora-btn--seleccionada' : '');
            btn.disabled = ocupada;

            if (!ocupada) {
              btn.addEventListener('click', () => {
                horaInicioSel = inicioMin;
                horaFinSel    = finMin;
                document.getElementById('hora_inicio').value = minToHHMM(inicioMin);
                document.getElementById('hora_fin').value    = minToHHMM(finMin);

                const label = document.getElementById('hora-seleccionada-label');
                label.textContent = 'Seleccionado: ' + minToHHMM(inicioMin) + ' – ' + minToHHMM(finMin);
                label.style.display = 'block';

                // Re-render para reflejar selección visualmente
                renderCuadricula(window._ocupadasCache || []);
              });
            }

            container.appendChild(btn);
          });
        });
      }

      function cargarHoras() {
        // Si la instalación o la fecha no están seleccionadas, no se muestran horarios.
        const instalacion = document.querySelector('input[name="instalacion"]:checked');
        const fecha = document.getElementById('fecha').value;
        const seccion = document.getElementById('horario-section');
        const aviso   = document.getElementById('horario-aviso');

        if (!instalacion || !fecha) {
          seccion.style.display = 'none';
          return;
        }

        seccion.style.display = 'block';
        aviso.style.display   = 'none';

        // Reset selección si cambia instalación o fecha
        horaInicioSel = null;
        horaFinSel    = null;
        document.getElementById('hora_inicio').value = '';
        document.getElementById('hora_fin').value    = '';
        const label = document.getElementById('hora-seleccionada-label');
        label.style.display = 'none';

        fetch('get_horas_ocupadas.php?instalacion=' + encodeURIComponent(instalacion.value) + '&fecha=' + encodeURIComponent(fecha), {
          credentials: 'include'
        })
          .then(r => r.json())
          .then(data => {
            window._ocupadasCache = data.ocupadas || [];
            renderCuadricula(window._ocupadasCache);
          })
          .catch(() => {
            window._ocupadasCache = [];
            renderCuadricula([]);
          });
      }

      // Eventos
      document.querySelectorAll('input[name="instalacion"]').forEach(r =>
        r.addEventListener('change', cargarHoras)
      );
      document.getElementById('fecha').addEventListener('change', cargarHoras);
      document.querySelectorAll('input[name="duracion"]').forEach(r =>
        r.addEventListener('change', () => {
          horaInicioSel = null;
          horaFinSel    = null;
          document.getElementById('hora_inicio').value = '';
          document.getElementById('hora_fin').value    = '';
          document.getElementById('hora-seleccionada-label').style.display = 'none';
          renderCuadricula(window._ocupadasCache || []);
        })
      );

      // Validación antes de enviar
      document.getElementById('formReserva').addEventListener('submit', function(e) {
        if (!document.getElementById('hora_inicio').value) {
          e.preventDefault();
          alert('Por favor selecciona una franja horaria.');
        }
      });
    </script>

</body>
</html>
