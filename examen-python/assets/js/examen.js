let tiempoRestante = window.EXAMEN_DURACION || 900;
let advertencias = 0;
const maxAdvertencias = window.EXAMEN_MAX_ADVERTENCIAS || 3;

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
        if (error) {
            error.style.display = "block";
        }
    }
}

document.addEventListener("DOMContentLoaded", () => {
    document.addEventListener("visibilitychange", () => {
        if (document.hidden) {
            sumarAdvertencia("Cambio de pestaña o ventana detectado");
        }
    });

    window.addEventListener("blur", () => {
        sumarAdvertencia("La ventana del examen perdió el foco");
    });

    document.addEventListener("fullscreenchange", () => {
        if (examenIniciado && !document.fullscreenElement && !examenFinalizado) {
            sumarAdvertencia("Has salido del modo pantalla completa");
        }
    });

    document.addEventListener("contextmenu", (e) => {
        e.preventDefault();
    });

    document.addEventListener("keydown", (e) => {
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