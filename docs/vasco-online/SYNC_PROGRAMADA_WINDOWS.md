# Sync programada Vasco Online en servidor Windows
Reemplaza el proceso manual de la pantalla **Vasco Online** (clientes, grupos, cuentas).
En la terminal se ejecuta con `php` (como las otras pruebas): `php scripts\vasco-sync-cron.php todo`.
La tarea de Windows hace lo mismo, pero con la ruta completa de `php.exe` (se obtiene con `where php`).
Las rutas `C:\xampp\...` de este documento son de ejemplo: ajústalas a las reales del servidor.

## 1. Preparar el servidor (PHP 5.6)
1. Tener el proyecto actualizado en `C:\xampp\htdocs\vascorp`.
2. Revisar `controladores\config.php` (no está en git) y que tenga:
   - `define("VASCO_ONLINE_ENTORNO", "produccion");`
   - `define("VASCO_ONLINE_API_KEY", "la-clave-real");`
3. Abrir **CMD** y confirmar versión y extensiones del PHP de consola (puede no ser el mismo que usa Apache):
   - `php -v` → versión (debe decir 5.6.x)
   - `where php` → ruta del `php.exe` que se usa (anótala, se necesita en la tarea)
   - `php -m` → deben aparecer `curl` y `pdo_mysql`
   - Si faltan: en el `php.ini` quitar el `;` de `extension=php_curl.dll` y `extension=php_pdo_mysql.dll`
   - Para saber qué `php.ini` usa: `php --ini`
4. El servidor debe llegar a la BD `192.168.1.64` (puerto 3306) y a `https://api.jackyform.com.pe`.
5. Si `php -v` dice que no se reconoce el comando, `php` no está en el PATH: busca el archivo con `where /R C:\ php.exe` y usa su ruta completa.

## 2. Probar en la terminal
1. Abrir **CMD** (Inicio → escribir `cmd` → Enter).
2. Ir al proyecto: `cd C:\xampp\htdocs\vascorp`
3. Ejecutar solo clientes (la prueba más corta):
   `php scripts\vasco-sync-cron.php clientes`
4. Ver el código de salida (0 = OK): `echo %ERRORLEVEL%`
5. Ver el log: abrir con el Bloc de notas `scripts\logs\vasco-sync-AAAAMMDD.log`.

Esperado: `Conexión al API OK`, líneas `clientes · Lote N enviado correctamente` y `código de salida 0`.

Orden recomendado de pruebas (el comando completo es `php scripts\vasco-sync-cron.php` + lo de la tabla):

| # | Argumento | Qué prueba |
|---|-----------|------------|
| 1 | `clientes` | maestro de clientes |
| 2 | `grupos` | grupos y asignaciones |
| 3 | `cuentas` | estados de cuenta (sin purga) |
| 4 | `todo` | lo que hará la tarea horaria |
| 5 | `todo --finalize` | igual + purga de cuentas (una sola vez, al final, con 1–4 sin errores) |

Prueba del lock: abre dos CMD y lanza `todo` casi a la vez; la segunda debe decir `Ya hay una sincronización en curso` y terminar con código 3.

## 3. Programar la tarea (interfaz gráfica)
1. **Inicio** → escribir **Programador de tareas** → abrir.
2. Panel derecho → **Crear tarea…** (no "Crear tarea básica").
3. Pestaña **General**:
   - Nombre: `Vasco Sync Horario`
   - Marcar **Ejecutar tanto si el usuario inició sesión como si no**
   - Marcar **Ejecutar con los privilegios más altos**
4. Pestaña **Desencadenadores** → **Nuevo…**:
   - Iniciar la tarea: **Según una programación** → **Diariamente**
   - Hora de inicio: `06:00:00`
   - Marcar **Repetir la tarea cada:** `1 hora` **durante:** `Indefinidamente` (si no sale, escribe `1 día`)
   - Marcar **Habilitado** → Aceptar
5. Pestaña **Acciones** → **Nueva…**:
   - Acción: **Iniciar un programa**
   - **Programa o script:** la ruta completa que dio `where php` (ej. `C:\xampp\php\php.exe`). Usa la ruta completa porque la tarea puede no tener el PATH de tu usuario
   - **Agregar argumentos:** `C:\xampp\htdocs\vascorp\scripts\vasco-sync-cron.php todo`
   - **Iniciar en:** `C:\xampp\htdocs\vascorp`
6. Pestaña **Configuración**:
   - Marcar **Permitir ejecutar la tarea a petición**
   - Desmarcar "Detener la tarea si se ejecuta durante más de…" (o poner 2 horas)
   - "Si la tarea ya se está ejecutando": **No iniciar una nueva instancia**
7. **Aceptar**. Pide usuario y contraseña de Windows (una cuenta con permiso para ejecutar tareas).
8. Probar: clic derecho sobre la tarea → **Ejecutar**. Revisar el log y que **Resultado de la última ejecución** sea `0x0`.

**Tarea 2 – diaria con purga:** repetir los pasos 2–8 cambiando:
- Nombre: `Vasco Sync Diario Finalize`
- Desencadenador: Diariamente a las `23:30:00`, **sin** repetir cada hora
- Argumentos: `C:\xampp\htdocs\vascorp\scripts\vasco-sync-cron.php todo --finalize`
- Para pausar cualquiera de las dos tareas: clic derecho → **Deshabilitar**

## 4. Códigos de salida
| Código | Significado |
|--------|-------------|
| 0 | Todo OK |
| 1 | Error (red, API, BD, excepción) |
| 2 | Terminó, pero Vasco rechazó filas (ver `↳ rechazado` en el log) o se omitió el finalize |
| 3 | Ya había otra corrida en curso |

## 5. Notas
- **Solapamiento:** hay un lock; si la diaria cae mientras corre la horaria, sale con código 3 y ese día no hace la purga. Por eso va a las 23:30. Mide cuánto tarda una corrida y ajusta.
- **`--finalize`** purga en Vasco los clientes que ya no deben, y solo se envía si toda la corrida de cuentas fue limpia (sin lotes fallidos ni parciales). Si no, el log dice `FINALIZE OMITIDO`.
- Buscar problemas en los logs: `findstr /I "ERROR ABORTADA rechazado EXCEPCIÓN OMITIDO" C:\xampp\htdocs\vascorp\scripts\logs\*.log`

## 6. Problemas frecuentes
| Síntoma | Causa / solución |
|---------|------------------|
| `could not find driver` | Falta `extension=php_pdo_mysql.dll` en el `php.ini` que usa la consola |
| `cURL no está disponible` | Falta `extension=php_curl.dll` |
| `SQLSTATE[HY000] [2002]` | No llega a `192.168.1.64` |
| `API key no configurada` | Falta `VASCO_ONLINE_API_KEY` en `config.php` |
| `el API no responde` | Sin internet o firewall hacia `api.jackyform.com.pe` |
| La tarea no hace nada | Revisar "Iniciar en" y la ruta de `php.exe`; mirar el log |
