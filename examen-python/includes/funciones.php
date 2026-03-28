<?php

function limpiarTexto(?string $valor): string
{
    return trim((string) $valor);
}

function esc(string $valor): string
{
    return htmlspecialchars($valor, ENT_QUOTES, 'UTF-8');
}

function obtenerPreguntasAleatorias(PDO $pdo, int $limite = 10): array
{
    $sql = "SELECT * FROM preguntas ORDER BY RAND() LIMIT :limite";
    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(':limite', $limite, PDO::PARAM_INT);
    $stmt->execute();

    return $stmt->fetchAll();
}

function registrarEvento(PDO $pdo, string $alumno, string $evento): void
{
    $stmt = $pdo->prepare(
        "INSERT INTO eventos_examen (alumno_nombre, evento) VALUES (:alumno, :evento)"
    );
    $stmt->execute([
        ':alumno' => $alumno,
        ':evento' => $evento,
    ]);
}

function contarAdvertencias(PDO $pdo, string $alumno): int
{
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM eventos_examen WHERE alumno_nombre = :alumno");
    $stmt->execute([':alumno' => $alumno]);

    return (int) $stmt->fetchColumn();
}

function calcularNota(array $preguntas, array $respuestasUsuario): array
{
    $correctas = 0;
    $detalle = [];

    foreach ($preguntas as $pregunta) {
        $id = (int) $pregunta['id'];
        $respuestaCorrecta = strtolower(trim((string) $pregunta['respuesta_correcta']));
        $respuestaAlumno = strtolower(trim((string) ($respuestasUsuario[$id] ?? '')));

        $esCorrecta = $respuestaAlumno === $respuestaCorrecta;
        if ($esCorrecta) {
            $correctas++;
        }

        $detalle[] = [
            'id' => $id,
            'pregunta' => $pregunta['pregunta'],
            'tema' => $pregunta['tema'],
            'respuesta_alumno' => $respuestaAlumno,
            'respuesta_correcta' => $respuestaCorrecta,
            'es_correcta' => $esCorrecta,
        ];
    }

    $total = count($preguntas);
    $nota = $total > 0 ? ($correctas / $total) * 20 : 0;

    return [
        'correctas' => $correctas,
        'total' => $total,
        'nota' => round($nota, 2),
        'detalle' => $detalle,
    ];
}
