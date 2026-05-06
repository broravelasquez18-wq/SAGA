<?php
session_start();
require_once "../config/csrf.php";

// ⭐ CAPTURAR MENSAJES
$error = $_GET['error'] ?? '';
$msg = $_GET['msg'] ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../assets/css/home.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <title>SAGA - Sistema de Gestión de Ambientes</title>
    <style>
        .alert-error {
            background: #fee;
            color: #c33;
            border: 1px solid #fcc;
            padding: 12px 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 14px;
            text-align: center;
        }
        .alert-success {
            background: #d1fae5;
            color: #065f46;
            border: 1px solid #3eb489;
            padding: 12px 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 14px;
            text-align: center;
            font-weight: 600;
        }
    </style>
</head>
<body>
    <div class="header">
        <img src="../assets/img/ChatGPT_Image_6_mar_2026__12_14_37_p.m.-removebg-preview.png" alt="logo SAGA">
        <div class="linea"> </div>
        <h2>SAGA</h2>

        <button class="btn-login" id="abrirModal">
            Iniciar Sesión
        </button>
    </div>
    <div class="contenedor">
        <img class="hero" src="../assets/img/Gemini_Generated_Image_wgbsi1wgbsi1wgbs.png" alt="Ambiente">
        <div class="contenido">
            <h1 class="animacion a1">Bienvenido a SAGA</h1>
            <p class="animacion a2">Sistema de Administración y Gestión de Ambientes</p>
            <h2 class="titulo-saga animacion a3">¿Qué puedes hacer en SAGA?</h2>

            <div class="cards animacion a4" id="carrusel">
                <div class="card">
                    <i class="bi bi-house icono"></i>
                    <h3>Gestión de Ambientes</h3>
                    <p>24/7</p>
                </div>

                <div class="card">
                    <i class="bi bi-calendar3 icono"></i>
                    <h3>Reserva y evita conflictos</h3>
                    <p>Control total</p>
                </div>

                <div class="card">
                    <i class="bi bi-bar-chart-fill icono"></i>
                    <h3>Consulta de uso y disponibilidad</h3>
                    <p>Reportes en tiempo real</p>
                </div>
            </div>
            <div class="carrusel-dots" id="carruselDots">
                <span class="dot activo"></span>
                <span class="dot"></span>
                <span class="dot"></span>
            </div>

            <button class="btn-login btn-login-mobile animacion a5" id="abrirModal2">
                Iniciar Sesión
            </button>
        </div>
    </div>

    <!-- MODAL: LOGIN -->
    <div class="modal" id="modalLogin">
        <div class="modal-content">
            <span class="cerrar" id="cerrarModal"><i class="bi bi-x-lg"></i></span>
            <div class="formulario">
                <div class="logo">
                    <img src="../assets/img/ChatGPT_Image_6_mar_2026__12_14_37_p.m.-removebg-preview.png" alt="">
                </div>
                <h1>SAGA</h1>
                <p>Ingrese sus credenciales</p>

                <!-- Mensajes -->
                <?php if($msg == 'sesion_cerrada'): ?>
                    <div class="alert-success">
                        <i class="bi bi-check-circle-fill"></i> Sesión cerrada correctamente.
                    </div>
                <?php endif; ?>

                <?php if($error): ?>
                    <div class="alert-error">
                        <?php
                        if($error == 'credenciales') echo '<i class="bi bi-x-circle-fill"></i> Cédula o contraseña incorrectos';
                        if($error == 'inactivo') echo '<i class="bi bi-exclamation-triangle-fill"></i> Usuario inactivo. Contacte al administrador';
                        ?>
                    </div>
                <?php endif; ?>

                <form action="../controllers/loginController.php" method="POST" autocomplete="off">
                    <?php echo csrf_field(); ?>
                    <label>CÉDULA</label>
                    <input type="text" name="cedula" placeholder="Ingrese su número de cédula" autocomplete="off" required>

                    <label>CONTRASEÑA</label>
                    <div class="input-pass">
                        <input type="password" name="password" placeholder="Ingrese su contraseña" autocomplete="new-password" required>
                    </div>

                    <button type="submit" class="btn-login2">INGRESAR</button>
                </form>
            </div>
        </div>
    </div>

<script>
    /* ── Carrusel con auto-rotación ── */
    const carrusel = document.getElementById('carrusel');
    const dots     = document.querySelectorAll('.carrusel-dots .dot');

    if (carrusel && dots.length) {
        const cards      = carrusel.children;
        const totalCards = cards.length;
        let   current    = 0;
        let   autoTimer  = null;

        function cardWidth() {
            return cards[0].offsetWidth + 16; // ancho + gap
        }

        function irA(index) {
            current = (index + totalCards) % totalCards;
            carrusel.scrollTo({ left: cardWidth() * current, behavior: 'smooth' });
            dots.forEach((d, i) => d.classList.toggle('activo', i === current));
        }

        function siguiente() { irA(current + 1); }

        function iniciarAuto() {
            clearInterval(autoTimer);
            autoTimer = setInterval(siguiente, 3000);
        }

        function pausarAuto() { clearInterval(autoTimer); }

        /* Sincronizar dots al hacer scroll manual */
        carrusel.addEventListener('scroll', () => {
            const idx = Math.round(carrusel.scrollLeft / cardWidth());
            if (idx !== current) {
                current = idx;
                dots.forEach((d, i) => d.classList.toggle('activo', i === current));
            }
        }, { passive: true });

        /* Pausar al tocar, reanudar al soltar */
        carrusel.addEventListener('touchstart', pausarAuto,  { passive: true });
        carrusel.addEventListener('touchend',   iniciarAuto, { passive: true });

        /* Click en dots */
        dots.forEach((dot, i) => {
            dot.addEventListener('click', () => { pausarAuto(); irA(i); iniciarAuto(); });
        });

        /* Arrancar */
        iniciarAuto();
    }

    const modal = document.getElementById("modalLogin");
    const abrir = document.getElementById("abrirModal");
    const abrir2 = document.getElementById("abrirModal2");
    const cerrar = document.getElementById("cerrarModal");

    function abrirModal() {
        modal.style.display = "flex";
        abrir.style.display = "none";
    }

    function cerrarModal() {
        modal.style.display = "none";
        abrir.style.display = ""; // deja que el CSS decida según el breakpoint
    }

    abrir.onclick = abrirModal;

    if(abrir2) abrir2.onclick = abrirModal;

    cerrar.onclick = cerrarModal;

    window.onclick = function(event){
        if(event.target == modal){
            cerrarModal();
        }
    }

    // Auto-abrir modal si hay error o mensaje
    <?php if($error || $msg): ?>
        abrirModal();
    <?php endif; ?>

</script>

</body>
</html>