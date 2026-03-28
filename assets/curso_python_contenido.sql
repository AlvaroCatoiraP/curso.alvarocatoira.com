UPDATE chapitres_contenu
SET
    teoria_larga = '# Introducción a Python

Python es un lenguaje de programación **interpretado**, **de alto nivel** y muy conocido por su sintaxis clara. Esto significa que está diseñado para ser más cercano al lenguaje humano que otros lenguajes más rígidos o complejos.

## ¿Qué es Python?

Python fue creado por **Guido van Rossum** y publicado por primera vez en **1991**. Hoy en día es uno de los lenguajes más utilizados del mundo.

Se utiliza en muchos ámbitos:

- desarrollo web
- automatización de tareas
- análisis de datos
- inteligencia artificial
- programación científica

## Primer ejemplo

```python
print("Hola Python")
```

La función `print()` permite mostrar información en pantalla.

## Ejemplo con varios mensajes

```python
print("Hola")
print("Bienvenido al curso")
print("Vamos a aprender Python")
```

Python ejecuta las instrucciones **de arriba hacia abajo**.

## Diferencia entre texto y número

```python
print("5")
print(5)
```

Aunque parezcan iguales en pantalla:

- `"5"` es **texto**
- `5` es **número**

## Resumen

- Python es un lenguaje claro y fácil de aprender.
- Permite escribir programas rápidamente.
- Se usa en muchos campos profesionales.
- `print()` es una de las primeras funciones que se aprende.',
    
    ejercicios_guiados = 'Escribe un programa que muestre **Hola Python**.

Escribe un programa que muestre tu nombre.

Escribe tres instrucciones `print()` diferentes.

Muestra un número y un texto en pantalla.',

    mini_quiz = '¿Quién creó Python?
¿En qué año apareció Python?
¿Qué función se usa para mostrar texto?
¿Cuál es la diferencia entre `print("5")` y `print(5)`?'

WHERE chapitre_id = 1 AND langue = "es";
