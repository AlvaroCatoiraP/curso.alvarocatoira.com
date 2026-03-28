CREATE DATABASE IF NOT EXISTS examen_python CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE examen_python;

DROP TABLE IF EXISTS eventos_examen;
DROP TABLE IF EXISTS resultados;
DROP TABLE IF EXISTS preguntas;

CREATE TABLE preguntas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tema VARCHAR(50) NOT NULL,
    tipo VARCHAR(20) NOT NULL DEFAULT 'qcm',
    pregunta TEXT NOT NULL,
    opcion_a TEXT NOT NULL,
    opcion_b TEXT NOT NULL,
    opcion_c TEXT NOT NULL,
    opcion_d TEXT NOT NULL,
    respuesta_correcta CHAR(1) NOT NULL
);

CREATE TABLE resultados (
    id INT AUTO_INCREMENT PRIMARY KEY,
    alumno_nombre VARCHAR(120) NOT NULL,
    nota DECIMAL(5,2) NOT NULL,
    correctas INT NOT NULL,
    total INT NOT NULL,
    advertencias INT NOT NULL DEFAULT 0,
    fecha DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE eventos_examen (
    id INT AUTO_INCREMENT PRIMARY KEY,
    alumno_nombre VARCHAR(120) NOT NULL,
    evento VARCHAR(255) NOT NULL,
    fecha DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
);

INSERT INTO preguntas (tema, tipo, pregunta, opcion_a, opcion_b, opcion_c, opcion_d, respuesta_correcta) VALUES
('Variables', 'qcm', '¿Cuál es la forma correcta de crear una variable en Python?', 'int x = 5', 'x = 5', 'var x = 5', 'x := int(5)', 'b'),
('Variables', 'qcm', '¿Qué tipo de dato es "Hola"?', 'int', 'float', 'str', 'bool', 'c'),

('Funciones', 'qcm', '¿Qué palabra clave se usa para definir una función en Python?', 'function', 'func', 'def', 'define', 'c'),
('Funciones', 'qcm', '¿Qué instrucción se usa para devolver un valor desde una función?', 'print', 'return', 'break', 'yield always', 'b'),

('Listas', 'qcm', '¿Cuál de estas opciones crea una lista en Python?', '{1, 2, 3}', '(1, 2, 3)', '[1, 2, 3]', '<1, 2, 3>', 'c'),
('Listas', 'qcm', '¿Qué método añade un elemento al final de una lista?', 'add()', 'append()', 'push()', 'insertEnd()', 'b'),

('Diccionarios', 'qcm', '¿Cómo se accede al valor asociado a la clave "nombre" en un diccionario persona?', 'persona.nombre', 'persona["nombre"]', 'persona(nombre)', 'persona->nombre', 'b'),
('Diccionarios', 'qcm', '¿Qué símbolo se usa para definir un diccionario en Python?', '[]', '()', '{}', '<>', 'c'),

('JSON', 'qcm', '¿Qué módulo de Python se usa normalmente para trabajar con JSON?', 'file', 'json', 'dict', 'data', 'b'),
('JSON', 'qcm', '¿Qué función convierte un diccionario Python a texto JSON?', 'json.loads()', 'json.reads()', 'json.dumps()', 'json.opens()', 'c'),

('Archivos', 'qcm', '¿Qué instrucción abre un archivo en modo lectura?', 'open("archivo.txt", "r")', 'file("archivo.txt", "read")', 'read("archivo.txt")', 'open.read("archivo.txt")', 'a'),
('Archivos', 'qcm', '¿Qué modo se usa para escribir en un archivo sobrescribiendo su contenido?', 'r', 'a', 'x', 'w', 'd');
