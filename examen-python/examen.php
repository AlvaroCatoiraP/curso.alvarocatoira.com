<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/funciones.php';

exigirAutenticacion();

if (!isset($_SESSION['preguntas_examen']) || !is_array($_SESSION['preguntas_examen'])) {
    $_SESSION['preguntas_examen'] = obtenerPreguntasAleatorias($pdo, 10);
    $_SESSION['examen_iniciado_en'] = null;
}

$preguntas = $_SESSION['preguntas_examen'];
$alumno = obtenerNombreAlumno();
$duracionSegundos = 15 * 60;
$maxAdvertencias = 3;
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Examen en curso</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .pantalla-inicio {
            text-align: center;
            padding: 40px 20px;
        }

        .pantalla-inicio h2 {
            margin-bottom: 15px;
        }

        .pantalla-inicio p {
            margin-bottom: 12px;
            font-size: 16px;
        }

        #zonaExamen {
            display: none;
        }

        .lista-reglas {
            text-align: left;
            max-width: 700px;
            margin: 20px auto;
            background: #f9fafb;
            border: 1px solid #e5e7eb;
            border-radius: 12px;
            padding: 20px;
        }

        .lista-reglas li {
            margin-bottom: 10px;
        }

        .pantalla-inicio button {
            margin-top: 10px;
            font-size: 16px;
        }
    </style>
</head>
<body>
    <div class="contenedor">
        <div class="card">
            <div id="pantallaInicio" class="pantalla-inicio">
                <h1>Examen final de Python</h1>
                <p><strong>Alumno:</strong> <?= esc($alumno) ?></p>
                <p>Antes de empezar, debes entrar en pantalla completa.</p>

                <div class="lista-reglas">
                    <h3>Reglas del examen</h3>
                    <ul>
                        <li>Duración: <strong>15 minutos</strong>.</li>
                        <li>Temas: variables, funciones, listas, diccionarios, JSON y archivos.</li>
                        <li>Debes permanecer en la ventana del examen.</li>
                        <li>Si cambias de pestaña, pierdes foco o sales de pantalla completa, se registrará una advertencia.</li>
                        <li>Máximo permitido: <strong><?= $maxAdvertencias ?> advertencias</strong>.</li>
                        <li>Al superar el límite, el examen se entregará automáticamente.</li>
                    </ul>
                </div>

                <button type="button" id="btnIniciarExamen">Empezar examen</button>

                <p id="errorFullscreen" class="resultado-ko" style="display:none; margin-top:15px;">
                    No se pudo activar pantalla completa. Inténtalo otra vez.
                </p>
            </div>

            <div id="zonaExamen">
                <h1>Examen de Python</h1>

                <div class="fila-info">
                    <div class="badge">Alumno: <?= esc($alumno) ?></div>
                    <div class="badge">Tiempo restante: <span id="tiempo">15:00</span></div>
                    <div class="badge">Advertencias: <span id="contadorAdvertencias">0</span>/<?= $maxAdvertencias ?></div>
                </div>

                <div id="mensajeBloqueo" class="alerta"></div>

                <form id="formExamen" action="procesar_examen.php" method="post">
                    <?php foreach ($preguntas as $indice => $pregunta): ?>
                        <div class="pregunta">
                            <span class="tema"><?= esc($pregunta['tema']) ?></span>
                            <h3><?= ($indice + 1) ?>. <?= esc($pregunta['pregunta']) ?></h3>

                            <?php foreach (['a', 'b', 'c', 'd'] as $letra): ?>
                                <?php $campo = 'opcion_' . $letra; ?>
                                <label class="opcion">
                                    <input
                                        type="radio"
                                        name="respuesta[<?= (int) $pregunta['id'] ?>]"
                                        value="<?= $letra ?>"
                                        required
                                    >
                                    <?= strtoupper($letra) ?>) <?= esc((string) $pregunta[$campo]) ?>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    <?php endforeach; ?>

                    <div class="acciones">
                        <button type="submit">Entregar examen</button>
                        <a class="btn btn-secundario" href="logout_seguro.php">Cancelar</a>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        let tiempoRestante = <?= (int) $duracionSegundos ?>;
        let advertencias = 0;
        const maxAdvertencias = <?= (int) $maxAdvertencias ?>;

        let examenFinalizado = false;
        let examenIniciado = false;
        let temporizador = null;

        function actualizarTemporizador() {
            if (examenFinalizado || !examenIniciado) return;

            const minutos = Math.floor(tiempoRestante / 60);
            const segundos = tiempoRestante % 60;

            const tiempoElemento = document.getElementById("tiempo");
            if (tiempoElemento) {
                tiempoElemento.textContent =
                    String(minutos).padStart(2, "0") + ":" + String(segundos).padStart(2, "0");
            }

            if (tiempoRestante <= 0) {
                examenFinalizado = true;
                alert("Tiempo terminado. El examen será entregado automáticamente.");
                document.getElementById("formExamen").submit();
                return;
            }

            tiempoRestante--;
        }

        function mostrarAlerta(texto) {
            const caja = document.getElementById("mensajeBloqueo");
            if (!caja) return;
            caja.style.display = "block";
            caja.textContent = texto;
        }

        function registrarEvento(evento) {
            const data = new URLSearchParams();
            data.append("evento", evento);

            fetch("guardar_evento.php", {
                method: "POST",
                headers: {
                    "Content-Type": "application/x-www-form-urlencoded"
                },
                body: data.toString()
            }).catch(() => {});
        }

        function sumarAdvertencia(motivo) {
            if (examenFinalizado || !examenIniciado) return;

            advertencias++;

            const contador = document.getElementById("contadorAdvertencias");
            if (contador) {
                contador.textContent = advertencias;
            }

            mostrarAlerta("Advertencia " + advertencias + "/" + maxAdvertencias + ": " + motivo);
            registrarEvento(motivo);

            if (advertencias >= maxAdvertencias) {
                examenFinalizado = true;
                alert("Has superado el número máximo de advertencias. El examen será entregado.");
                document.getElementById("formExamen").submit();
            }
        }

        async function iniciarExamen() {
            const error = document.getElementById("errorFullscreen");
            if (error) {
                error.style.display = "none";
            }

            try {
                if (!document.fullscreenElement) {
                    await document.documentElement.requestFullscreen();
                }

                document.getElementById("pantallaInicio").style.display = "none";
                document.getElementById("zonaExamen").style.display = "block";

                examenIniciado = true;

                if (!temporizador) {
                    actualizarTemporizador();
                    temporizador = setInterval(actualizarTemporizador, 1000);
                }
            } catch (e) {
                console.error(e);
                if (error) {
                    error.style.display = "block";
                }
            }
        }

        document.addEventListener("DOMContentLoaded", function () {
            const boton = document.getElementById("btnIniciarExamen");
            if (boton) {
                boton.addEventListener("click", iniciarExamen);
            }

            document.addEventListener("visibilitychange", function () {
                if (document.hidden) {
                    sumarAdvertencia("Cambio de pestaña o ventana detectado");
                }
            });

            window.addEventListener("blur", function () {
                sumarAdvertencia("La ventana del examen perdió el foco");
            });

            document.addEventListener("fullscreenchange", function () {
                if (examenIniciado && !document.fullscreenElement && !examenFinalizado) {
                    sumarAdvertencia("Has salido del modo pantalla completa");
                }
            });

            document.addEventListener("contextmenu", function (e) {
                e.preventDefault();
            });

            document.addEventListener("keydown", function (e) {
                const tecla = e.key.toUpperCase();

                const intentoDevTools =
                    e.key === "F12" ||
                    (e.ctrlKey && e.shiftKey && ["I", "J", "C"].includes(tecla)) ||
                    (e.ctrlKey && e.key.toLowerCase() === "u");

                if (intentoDevTools) {
                    e.preventDefault();
                    sumarAdvertencia("Intento de abrir herramientas del navegador");
                }
            });
        });
    </script>
</body>
</html>