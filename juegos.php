<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Python Adventure</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/pyodide/v0.25.1/full/pyodide.js"></script>
    <style>
        canvas {
            background: linear-gradient(to bottom, #0f172a, #111827);
            border: 1px solid #1e293b;
            border-radius: 18px;
        }
    </style>
</head>
<body class="bg-slate-950 text-slate-100">
    <div class="min-h-screen flex flex-col">
        <header class="border-b border-slate-800 bg-slate-950/95 backdrop-blur">
            <div class="max-w-7xl mx-auto px-6 py-5 flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
                <div>
                    <p class="text-sky-400 text-sm font-semibold uppercase tracking-widest">Python Adventure</p>
                    <h1 class="text-3xl font-bold mt-2">Programa el personaje con Python</h1>
                    <p class="text-slate-400 mt-2 max-w-3xl">
                        Escribe funciones reales manipulando atributos del personaje como
                        <code class="text-sky-300">personaje.x</code>,
                        <code class="text-sky-300">personaje.y</code>,
                        <code class="text-sky-300">personaje.color</code> y
                        <code class="text-sky-300">personaje.expresion</code>.
                    </p>
                </div>

                <div class="flex flex-wrap gap-3">
                    <button id="runBtn" class="bg-sky-500 hover:bg-sky-600 px-4 py-2 rounded-xl font-semibold transition">
                        Ejecutar
                    </button>
                    <button id="resetBtn" class="bg-slate-800 hover:bg-slate-700 px-4 py-2 rounded-xl font-semibold transition">
                        Reiniciar nivel
                    </button>
                    <button id="exampleBtn" class="bg-violet-600 hover:bg-violet-700 px-4 py-2 rounded-xl font-semibold transition">
                        Cargar ejemplo
                    </button>
                </div>
            </div>
        </header>

        <main class="flex-1 max-w-7xl mx-auto w-full px-6 py-6">
            <div class="grid lg:grid-cols-2 gap-6">
                <!-- IZQUIERDA -->
                <section class="bg-slate-900 border border-slate-800 rounded-3xl p-5 flex flex-col min-h-[760px]">
                    <div class="flex items-start justify-between gap-4 mb-4">
                        <div>
                            <p class="text-sky-400 text-sm font-semibold uppercase tracking-widest">Editor Python</p>
                            <h2 class="text-2xl font-bold mt-2" id="levelTitle">Nivel</h2>
                            <p class="text-slate-400 mt-2 leading-7" id="levelGoal"></p>
                        </div>
                        <div class="px-3 py-2 rounded-2xl bg-slate-950 border border-slate-800 text-sm">
                            Nivel <span id="levelIndex">1</span> / <span id="levelTotal">3</span>
                        </div>
                    </div>

                    <div class="bg-slate-950 border border-slate-800 rounded-2xl p-4 mb-4">
                        <h3 class="text-lg font-semibold text-sky-300 mb-3">Propiedades disponibles del personaje</h3>
                        <div class="grid sm:grid-cols-2 gap-3 text-sm text-slate-300">
                            <div class="bg-slate-900 rounded-xl p-3 border border-slate-800">
                                <p><code class="text-emerald-300">personaje.x</code> → posición horizontal</p>
                            </div>
                            <div class="bg-slate-900 rounded-xl p-3 border border-slate-800">
                                <p><code class="text-emerald-300">personaje.y</code> → posición vertical</p>
                            </div>
                            <div class="bg-slate-900 rounded-xl p-3 border border-slate-800">
                                <p><code class="text-emerald-300">personaje.color</code> → color del cuerpo</p>
                            </div>
                            <div class="bg-slate-900 rounded-xl p-3 border border-slate-800">
                                <p><code class="text-emerald-300">personaje.expresion</code> → texto sobre el personaje</p>
                            </div>
                        </div>
                    </div>

                    <div class="bg-slate-950 border border-slate-800 rounded-2xl p-4 mb-4">
                        <h3 class="text-lg font-semibold text-sky-300 mb-3">Reglas del nivel</h3>
                        <ul id="requirements" class="list-disc list-inside text-slate-300 leading-8 space-y-1"></ul>
                    </div>

                    <label class="text-sm text-slate-400 mb-2 font-semibold">Tu código Python</label>
                    <textarea
                        id="code"
                        class="flex-1 min-h-[320px] bg-[#050816] border border-slate-800 rounded-2xl p-4 font-mono text-sm text-emerald-300 resize-none outline-none leading-7"
                        spellcheck="false"
                    ></textarea>

                    <div class="mt-4 grid md:grid-cols-2 gap-4">
                        <div class="bg-black/70 border border-slate-800 rounded-2xl p-4">
                            <h3 class="text-sm font-semibold text-slate-300 mb-2">Consola</h3>
                            <pre id="output" class="text-sm text-slate-300 whitespace-pre-wrap"></pre>
                        </div>
                        <div class="bg-slate-950 border border-slate-800 rounded-2xl p-4">
                            <h3 class="text-sm font-semibold text-slate-300 mb-2">Feedback</h3>
                            <div id="feedback" class="text-sm text-slate-300 leading-7">
                                Escribe tu código y pulsa <strong>Ejecutar</strong>.
                            </div>
                        </div>
                    </div>
                </section>

                <!-- DERECHA -->
                <section class="bg-slate-900 border border-slate-800 rounded-3xl p-5 flex flex-col min-h-[760px]">
                    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-5">
                        <div>
                            <p class="text-sky-400 text-sm font-semibold uppercase tracking-widest">Mapa</p>
                            <h2 class="text-2xl font-bold mt-2">Escenario del nivel</h2>
                        </div>

                        <div class="flex gap-2 flex-wrap">
                            <button id="prevBtn" class="bg-slate-800 hover:bg-slate-700 px-4 py-2 rounded-xl font-semibold transition">
                                ← Nivel anterior
                            </button>
                            <button id="nextBtn" class="bg-slate-800 hover:bg-slate-700 px-4 py-2 rounded-xl font-semibold transition">
                                Nivel siguiente →
                            </button>
                        </div>
                    </div>

                    <div class="bg-slate-950 border border-slate-800 rounded-3xl p-4 flex-1 flex items-center justify-center">
                        <canvas id="game" width="500" height="420"></canvas>
                    </div>

                    <div class="grid md:grid-cols-3 gap-4 mt-5">
                        <div class="bg-slate-950 border border-slate-800 rounded-2xl p-4">
                            <p class="text-sm text-slate-400">Coordenadas finales</p>
                            <p class="text-lg font-semibold mt-2" id="coordsInfo">x: 0, y: 0</p>
                        </div>
                        <div class="bg-slate-950 border border-slate-800 rounded-2xl p-4">
                            <p class="text-sm text-slate-400">Expresión final</p>
                            <p class="text-lg font-semibold mt-2" id="exprInfo">-</p>
                        </div>
                        <div class="bg-slate-950 border border-slate-800 rounded-2xl p-4">
                            <p class="text-sm text-slate-400">Estado del nivel</p>
                            <p class="text-lg font-semibold mt-2" id="statusInfo">Pendiente</p>
                        </div>
                    </div>
                </section>
            </div>
        </main>
    </div>

<script>
let pyodide = null;
let ready = false;

const canvas = document.getElementById("game");
const ctx = canvas.getContext("2d");

const output = document.getElementById("output");
const feedback = document.getElementById("feedback");
const codeArea = document.getElementById("code");
const coordsInfo = document.getElementById("coordsInfo");
const exprInfo = document.getElementById("exprInfo");
const statusInfo = document.getElementById("statusInfo");
const levelTitle = document.getElementById("levelTitle");
const levelGoal = document.getElementById("levelGoal");
const levelIndex = document.getElementById("levelIndex");
const levelTotal = document.getElementById("levelTotal");
const requirementsList = document.getElementById("requirements");

const levels = [
    {
        title: "Nivel 1 · Crear avanzar(personaje)",
        goalText: "Crea una función avanzar(personaje) que use personaje.x += 20. Después, crea resolver_nivel(personaje) y llama varias veces a avanzar(personaje) para llegar a la estrella.",
        requirements: [
            "Debes crear la función avanzar(personaje).",
            "Dentro de avanzar, debes modificar personaje.x con += 20.",
            "También cambia personaje.expresion a \"feliz\" o similar.",
            "Crea resolver_nivel(personaje) y úsala para llegar a la estrella."
        ],
        start: { x: 40, y: 180, color: "#38bdf8", expresion: "🙂" },
        goal: { x: 350, y: 180 },
        obstacles: [],
        starterCode: `# NIVEL 1
# Objetivo:
# - Define avanzar(personaje)
# - Dentro debes usar personaje.x += 20
# - Cambia también personaje.expresion
# - Luego crea resolver_nivel(personaje) para llegar a la estrella

def avanzar(personaje):
    pass

def resolver_nivel(personaje):
    pass
`,
        exampleCode: `def avanzar(personaje):
    personaje.x += 20
    personaje.expresion = "feliz"

def resolver_nivel(personaje):
    for _ in range(23):
        avanzar(personaje)
`,
        validateSource(code) {
            const okAvanzar = /def\s+avanzar\s*\(\s*personaje\s*\)\s*:/m.test(code);
            const okX = /personaje\.x\s*\+=\s*20/m.test(code);
            const okResolver = /def\s+resolver_nivel\s*\(\s*personaje\s*\)\s*:/m.test(code);

            if (!okAvanzar) return "Falta la función avanzar(personaje).";
            if (!okX) return "Dentro de avanzar debes usar exactamente personaje.x += 20.";
            if (!okResolver) return "Falta la función resolver_nivel(personaje).";
            return null;
        },
        success(state) {
            return state.x >= 500;
        },
        successMessage: "¡Muy bien! Tu función avanzar(personaje) modifica el atributo x correctamente."
    },
    {
        title: "Nivel 2 · Saltar el obstáculo",
        goalText: "Ahora hay un bloque en el camino. Debes crear avanzar(personaje) y saltar(personaje). Usa personaje.y para levantar al personaje, pasa el obstáculo y vuelve al suelo.",
        requirements: [
            "Debes definir avanzar(personaje) con personaje.x += 20.",
            "Debes definir saltar(personaje).",
            "Debes cambiar personaje.y para esquivar el obstáculo.",
            "Debes definir resolver_nivel(personaje) y llegar a la estrella sin tocar el bloque."
        ],
        start: { x: 60, y: 320, color: "#22c55e", expresion: "😐" },
        goal: { x: 540, y: 320 },
        obstacles: [
            { x: 240, y: 280, w: 70, h: 60 }
        ],
        starterCode: `# NIVEL 2
# Hay un obstáculo en el camino.
# Debes crear:
# - avanzar(personaje)
# - saltar(personaje)
# - resolver_nivel(personaje)

def avanzar(personaje):
    pass

def saltar(personaje):
    pass

def resolver_nivel(personaje):
    pass
`,
        exampleCode: `def avanzar(personaje):
    personaje.x += 20
    personaje.expresion = "concentrado"

def saltar(personaje):
    personaje.y -= 80
    personaje.expresion = "wow"

def resolver_nivel(personaje):
    for _ in range(8):
        avanzar(personaje)

    saltar(personaje)

    for _ in range(4):
        avanzar(personaje)

    personaje.y = 320
    personaje.expresion = "bien"

    for _ in range(7):
        avanzar(personaje)
`,
        validateSource(code) {
            const okAvanzar = /def\s+avanzar\s*\(\s*personaje\s*\)\s*:/m.test(code);
            const okX = /personaje\.x\s*\+=\s*20/m.test(code);
            const okSaltar = /def\s+saltar\s*\(\s*personaje\s*\)\s*:/m.test(code);
            const okResolver = /def\s+resolver_nivel\s*\(\s*personaje\s*\)\s*:/m.test(code);

            if (!okAvanzar) return "Falta la función avanzar(personaje).";
            if (!okX) return "Debes usar personaje.x += 20.";
            if (!okSaltar) return "Falta la función saltar(personaje).";
            if (!okResolver) return "Falta la función resolver_nivel(personaje).";
            return null;
        },
        success(state, collision) {
            return state.x >= 520 && !collision;
        },
        successMessage: "¡Perfecto! Has usado coordenadas x e y para esquivar el obstáculo."
    },
    {
        title: "Nivel 3 · Personalizar y llegar a la meta",
        goalText: "Llega a la estrella y además personaliza el personaje. Debes cambiar color y expresión durante el recorrido.",
        requirements: [
            "Debes definir avanzar(personaje) usando personaje.x += 20.",
            "Debes cambiar personaje.color en alguna parte del código.",
            "Debes cambiar personaje.expresion en alguna parte del código.",
            "Debes definir resolver_nivel(personaje) y llegar a la estrella."
        ],
        start: { x: 60, y: 320, color: "#f59e0b", expresion: "😴" },
        goal: { x: 540, y: 320 },
        obstacles: [
            { x: 200, y: 300, w: 50, h: 40 },
            { x: 340, y: 260, w: 70, h: 80 }
        ],
        starterCode: `# NIVEL 3
# Personaliza al personaje y llega a la estrella.
# Debes:
# - usar personaje.x += 20
# - cambiar personaje.color
# - cambiar personaje.expresion
# - crear resolver_nivel(personaje)

def avanzar(personaje):
    pass

def resolver_nivel(personaje):
    pass
`,
        exampleCode: `def avanzar(personaje):
    personaje.x += 20
    personaje.expresion = "vamos"

def resolver_nivel(personaje):
    personaje.color = "deepskyblue"

    for _ in range(7):
        avanzar(personaje)

    personaje.y -= 70
    personaje.expresion = "saltando"

    for _ in range(5):
        avanzar(personaje)

    personaje.y = 220
    personaje.color = "violet"

    for _ in range(4):
        avanzar(personaje)

    personaje.y = 320
    personaje.expresion = "gané"

    for _ in range(8):
        avanzar(personaje)
`,
        validateSource(code) {
            const okAvanzar = /def\s+avanzar\s*\(\s*personaje\s*\)\s*:/m.test(code);
            const okX = /personaje\.x\s*\+=\s*20/m.test(code);
            const okResolver = /def\s+resolver_nivel\s*\(\s*personaje\s*\)\s*:/m.test(code);
            const okColor = /personaje\.color\s*=/m.test(code);
            const okExp = /personaje\.expresion\s*=/m.test(code);

            if (!okAvanzar) return "Falta la función avanzar(personaje).";
            if (!okX) return "Debes usar personaje.x += 20.";
            if (!okResolver) return "Falta la función resolver_nivel(personaje).";
            if (!okColor) return "Debes cambiar personaje.color al menos una vez.";
            if (!okExp) return "Debes cambiar personaje.expresion al menos una vez.";
            return null;
        },
        success(state, collision) {
            return state.x >= 520 && !collision && !!state.color && !!state.expresion;
        },
        successMessage: "¡Excelente! Has usado atributos del objeto personaje como en programación real."
    }
];

let currentLevel = 0;
let animationState = null;

function getLevel() {
    return levels[currentLevel];
}

function setStatus(text, colorClass = "text-slate-100") {
    statusInfo.className = `text-lg font-semibold mt-2 ${colorClass}`;
    statusInfo.textContent = text;
}

function loadLevel(index) {
    currentLevel = Math.max(0, Math.min(levels.length - 1, index));
    const level = getLevel();

    levelIndex.textContent = String(currentLevel + 1);
    levelTotal.textContent = String(levels.length);
    levelTitle.textContent = level.title;
    levelGoal.textContent = level.goalText;
    requirementsList.innerHTML = "";

    for (const req of level.requirements) {
        const li = document.createElement("li");
        li.textContent = req;
        requirementsList.appendChild(li);
    }

    codeArea.value = level.starterCode;
    output.textContent = "";
    feedback.innerHTML = "Escribe tu solución y pulsa <strong>Ejecutar</strong>.";
    coordsInfo.textContent = `x: ${level.start.x}, y: ${level.start.y}`;
    exprInfo.textContent = level.start.expresion || "-";
    setStatus("Pendiente", "text-slate-100");
    animationState = buildInitialVisualState(level.start);
    drawScene();
}

function buildInitialVisualState(start) {
    return {
        x: start.x,
        y: start.y,
        color: start.color,
        expresion: start.expresion
    };
}

function drawGround() {
    ctx.fillStyle = "#1e293b";
    ctx.fillRect(0, 200, canvas.width, 60);

    ctx.strokeStyle = "#334155";
    ctx.lineWidth = 2;
    for (let x = 0; x < canvas.width; x += 32) {
        ctx.beginPath();
        ctx.moveTo(x, 200);
        ctx.lineTo(x + 20, 200);
        ctx.stroke();
    }
}

function drawGoal(goal) {
    ctx.font = "28px sans-serif";
    ctx.fillText("⭐", goal.x, goal.y - 10);
}

function drawObstacles(obstacles) {
    for (const obs of obstacles) {
        ctx.fillStyle = "#7c2d12";
        ctx.fillRect(obs.x, obs.y, obs.w, obs.h);

        ctx.strokeStyle = "#fb923c";
        ctx.lineWidth = 2;
        ctx.strokeRect(obs.x, obs.y, obs.w, obs.h);
    }
}

function drawCharacter(state) {
    // sombra
    ctx.fillStyle = "rgba(0,0,0,0.35)";
    ctx.beginPath();
    ctx.ellipse(state.x + 18, 198, 18, 6, 0, 0, Math.PI * 2);
    ctx.fill();

    // cuerpo
    ctx.fillStyle = state.color || "#38bdf8";
    ctx.fillRect(state.x, state.y, 36, 36);

    // borde
    ctx.strokeStyle = "#e2e8f0";
    ctx.lineWidth = 2;
    ctx.strokeRect(state.x, state.y, 36, 36);

    // ojos
    ctx.fillStyle = "#0f172a";
    ctx.fillRect(state.x + 8, state.y + 10, 4, 4);
    ctx.fillRect(state.x + 24, state.y + 10, 4, 4);

    // boca simple
    ctx.fillRect(state.x + 12, state.y + 24, 12, 3);

    // expresión
    ctx.font = "16px sans-serif";
    ctx.fillStyle = "#f8fafc";
    ctx.fillText(String(state.expresion || ""), state.x - 6, state.y - 10);
}

function drawScene() {
    const level = getLevel();
    const state = animationState || buildInitialVisualState(level.start);

    ctx.clearRect(0, 0, canvas.width, canvas.height);

    drawGround();
    drawGoal(level.goal);
    drawObstacles(level.obstacles);
    drawCharacter(state);
}

function intersects(a, b) {
    return (
        a.x < b.x + b.w &&
        a.x + a.w > b.x &&
        a.y < b.y + b.h &&
        a.y + a.h > b.y
    );
}

function detectCollisionWithObstacles(state, obstacles) {
    const box = { x: state.x, y: state.y, w: 36, h: 36 };
    return obstacles.some(obs => intersects(box, obs));
}

async function animateHistory(history, level) {
    if (!history || history.length === 0) {
        animationState = buildInitialVisualState(level.start);
        drawScene();
        return false;
    }

    let collisionDetected = false;

    for (const step of history) {
        animationState = {
            x: Number(step.x),
            y: Number(step.y),
            color: step.color || "#38bdf8",
            expresion: step.expresion || ""
        };

        if (detectCollisionWithObstacles(animationState, level.obstacles)) {
            collisionDetected = true;
        }

        coordsInfo.textContent = `x: ${animationState.x}, y: ${animationState.y}`;
        exprInfo.textContent = animationState.expresion || "-";
        drawScene();
        await new Promise(resolve => setTimeout(resolve, 180));
    }

    return collisionDetected;
}

async function initPyodideOnce() {
    if (ready) return;

    output.textContent = "Cargando motor Python...\n";
    pyodide = await loadPyodide();

    const prelude = `
class Personaje:
    def __init__(self, x=0, y=0, color="skyblue", expresion="🙂"):
        object.__setattr__(self, "_history", [])
        object.__setattr__(self, "_recording", False)
        object.__setattr__(self, "x", x)
        object.__setattr__(self, "y", y)
        object.__setattr__(self, "color", color)
        object.__setattr__(self, "expresion", expresion)
        object.__setattr__(self, "_recording", True)
        self._snapshot()

    def _snapshot(self):
        self._history.append({
            "x": self.x,
            "y": self.y,
            "color": self.color,
            "expresion": self.expresion
        })

    def __setattr__(self, name, value):
        object.__setattr__(self, name, value)
        if getattr(self, "_recording", False) and name in ("x", "y", "color", "expresion"):
            self._snapshot()

    def exportar(self):
        return {
            "x": self.x,
            "y": self.y,
            "color": self.color,
            "expresion": self.expresion,
            "history": self._history
        }
`;
    await pyodide.runPythonAsync(prelude);
    ready = true;
    output.textContent = "Motor Python listo.\n";
}

async function runCode() {
    const level = getLevel();
    const code = codeArea.value;

    output.textContent = "";
    feedback.textContent = "Ejecutando tu código...";
    setStatus("Ejecutando...", "text-sky-300");

    const sourceError = level.validateSource(code);
    if (sourceError) {
        feedback.textContent = sourceError;
        setStatus("Código incompleto", "text-red-300");
        return;
    }

    try {
        await initPyodideOnce();

        const escapedColor = JSON.stringify(level.start.color);
        const escapedExpression = JSON.stringify(level.start.expresion);

        const fullProgram = `
personaje = Personaje(
    x=${level.start.x},
    y=${level.start.y},
    color=${escapedColor},
    expresion=${escapedExpression}
)

${code}

resolver_nivel(personaje)

import json
resultado_json = json.dumps(personaje.exportar())
`;

        await pyodide.runPythonAsync(fullProgram);

        const resultJson = pyodide.globals.get("resultado_json");
        const result = JSON.parse(resultJson);

        const collision = await animateHistory(result.history, level);
        const success = level.success(result, collision);

        if (collision) {
            feedback.innerHTML = "Tu personaje ha tocado un obstáculo. Revisa los cambios de <code>personaje.y</code> y el orden de tus acciones.";
            setStatus("Colisión", "text-red-300");
        } else if (success) {
            feedback.textContent = level.successMessage;
            setStatus("Superado", "text-emerald-300");
        } else {
            feedback.innerHTML = "Tu código se ejecutó, pero el personaje todavía no llegó correctamente a la estrella. Mira las coordenadas finales y ajusta tu función.";
            setStatus("No completado", "text-yellow-300");
        }

        coordsInfo.textContent = `x: ${result.x}, y: ${result.y}`;
        exprInfo.textContent = result.expresion || "-";
        output.textContent = "Código ejecutado correctamente.";

    } catch (err) {
        output.textContent = String(err);
        feedback.textContent = "Hay un error en tu código Python. Revisa la consola.";
        setStatus("Error", "text-red-300");
    }
}

document.getElementById("runBtn").addEventListener("click", runCode);
document.getElementById("resetBtn").addEventListener("click", () => loadLevel(currentLevel));
document.getElementById("exampleBtn").addEventListener("click", () => {
    codeArea.value = getLevel().exampleCode;
});
document.getElementById("prevBtn").addEventListener("click", () => loadLevel(currentLevel - 1));
document.getElementById("nextBtn").addEventListener("click", () => loadLevel(currentLevel + 1));

loadLevel(0);
drawScene();
</script>
</body>
</html>