# Módulo de examen anti-triche para curso de Python

Este paquete está pensado para integrarse dentro de una web PHP existente.

## Estructura recomendada

```text
tu_sitio/
├── config.php                  <- tu conexión global (opcional)
├── login.php
├── curso-python.php
└── examen-python/
    ├── index.php
    ├── examen.php
    ├── procesar_examen.php
    ├── resultado.php
    ├── guardar_evento.php
    ├── logout_seguro.php
    ├── includes/
    │   ├── config.php
    │   ├── auth.php
    │   └── funciones.php
    ├── assets/
    │   ├── css/style.css
    │   └── js/examen.js
    └── sql/
        └── schema.sql
```

## Integración rápida

1. Copia la carpeta `examen-python/` dentro de la raíz de tu proyecto.
2. Importa `sql/schema.sql` en tu base de datos.
3. Edita `includes/config.php` con tus credenciales.
   - Si ya tienes un `config.php` global, puedes reemplazar el contenido por un `require_once`.
4. Si tu sitio ya usa login, adapta `includes/auth.php` para usar tu sesión actual.
5. Enlaza el examen desde tu curso:
   ```php
   <a href="/examen-python/index.php">Hacer examen final</a>
   ```

## Qué hace este módulo

- temporizador del examen
- pantalla completa
- detecta pérdida de foco
- detecta cambio de pestaña
- detecta salida de fullscreen
- registra incidentes
- autoentrega al superar advertencias o al terminar el tiempo
- preguntas por temas:
  - variables
  - funciones
  - listas
  - diccionarios
  - JSON
  - archivos

## Limitación real

No puede bloquear 100% Alt+Tab o el uso de otro dispositivo.
Sí puede detectar comportamientos sospechosos y sancionarlos.
