# Sync programada Vasco Online en servidor Windows

Reemplaza el proceso manual de la pantalla **Vasco Online** (clientes, grupos, cuentas).
La tarea de Windows ejecuta directamente **php.exe + el script**, igual que las otras tareas:

```
php.exe  C:\xampp\htdocs\vascorp\scripts\vasco-sync-cron.php  todo
```

(ajusta las rutas a las reales del servidor)

---

## 1. Preparar el servidor (PHP 5.6)

1. Tener el proyecto actualizado en `C:\xampp\htdocs\vascorp`.
2. Revisar `controladores\config.php` (no está en git):

```php
define("VASCO_ONLINE_ENTORNO", "produccion");
define("VASCO_ONLINE_API_KEY", "la-clave-real");
```

3. Confirmar la versión y las extensiones del **PHP de consola** (puede no ser el mismo que usa Apache):

```bat
C:\xampp\php\php.exe -v
C:\xampp\php\php.exe -m
```

   Deben aparecer `curl` y `pdo_mysql`. Si faltan, en `C:\xampp\php\php.ini` quitar el `;` de
   `extension=php_curl.dll` y `extension=php_pdo_mysql.dll`. Para saber qué `php.ini` usa la consola:
   `C:\xampp\php\php.exe --ini`.
4. El servidor debe llegar a la BD `192.168.1.64` (puerto 3306) y a `https://api.jackyform.com.pe`.

> Si `php.exe` no está en `C:\xampp\php`, búscalo con: `where /R C:\ php.exe`.

## 2. Probar en la terminal

1. Abrir **CMD** (Inicio → escribir `cmd` → Enter).
2. Ir a la carpeta del proyecto:

```bat
cd C:\xampp\htdocs\vascorp
```

3. Ejecutar solo clientes (prueba más corta):

```bat
C:\xampp\php\php.exe scripts\vasco-sync-cron.php clientes
```

4. Ver el código de salida (0 = OK):

```bat
echo %ERRORLEVEL%
```

5. Ver el log: abrir `scripts\logs\vasco-sync-AAAAMMDD.log` (con el Bloc de notas) o en la terminal:
   `type scripts\logs\vasco-sync-%date:~-4%%date:~3,2%%date:~0,2%.log` (si el formato de fecha de tu
   Windows es dd/mm/aaaa; si no, abre el archivo a mano).

Esperado: `Conexión al API OK`, líneas `clientes · Lote N enviado correctamente` y
`código de salida 0`.

Orden recomendado de pruebas (cada una con el mismo formato de comando):

| # | Comando (después de `C:\xampp\php\php.exe scripts\vasco-sync-cron.php`) | Qué prueba |
|---|---|---|
| 1 | `clientes` | maestro de clientes |
| 2 | `grupos` | grupos y asignaciones |
| 3 | `cuentas` | estados de cuenta (sin purga) |
| 4 | `todo` | lo que hará la tarea horaria |
| 5 | `todo --finalize` | igual + purga de cuentas (hacerla una sola vez, al final, y con 1–4 sin errores) |

Prueba del lock: abre dos CMD y lanza `todo` casi a la vez; la segunda debe decir
`Ya hay una sincronización en curso` y terminar con código 3.

## 3. Programar la tarea (interfaz gráfica)

1. **Inicio** → escribir **Programador de tareas** → abrir.
2. Panel derecho → **Crear tarea…** (no "Crear tarea básica").
3. Pestaña **General**:
   - Nombre: `Vasco Sync Horario`
   - Marcar **Ejecutar tanto si el usuario inició sesión como si no**
   - Marcar **Ejecutar con los privilegios más altos**
   - "Configurar para": tu versión de Windows/Server.
4. Pestaña **Desencadenadores** → **Nuevo…**:
   - Iniciar la tarea: **Según una programación** → **Diariamente**
   - Hora de inicio: `06:00:00`
   - Marcar **Repetir la tarea cada:** `1 hora` **durante:** `Indefinidamente`
     (si no sale `Indefinidamente`, escribe `1 día`)
   - Marcar **Habilitado** → Aceptar.
5. Pestaña **Acciones** → **Nueva…**:
   - Acción: **Iniciar un programa**
   - **Programa o script:** `C:\xampp\php\php.exe`
   - **Agregar argumentos (opcional):** `C:\xampp\htdocs\vascorp\scripts\vasco-sync-cron.php todo`
   - **Iniciar en (opcional):** `C:\xampp\htdocs\vascorp`
   - Aceptar.
6. Pestaña **Configuración**:
   - Marcar **Permitir ejecutar la tarea a petición**
   - Desmarcar "Detener la tarea si se ejecuta durante más de…" (o poner 2 horas)
   - "Si la tarea ya se está ejecutando": **No iniciar una nueva instancia**
7. **Aceptar**. Pide usuario y contraseña de Windows (cuenta con permiso para ejecutar tareas).
8. Probar: clic derecho sobre la tarea → **Ejecutar**. Revisar el log y, en la lista, la columna
   **Resultado de la última ejecución** = `0x0` (correcto).

**Tarea 2 – diaria con purga:** repetir los pasos 2–8 con estas diferencias:
- Nombre: `Vasco Sync Diario Finalize`
- Desencadenador: Diariamente a las `23:30:00`, **sin** repetir cada hora
- Argumentos: `C:\xampp\htdocs\vascorp\scripts\vasco-sync-cron.php todo --finalize`

Pausar una tarea: clic derecho → **Deshabilitar**.

## 4. Códigos de salida

| Código | Significado |
|--------|-------------|
| 0 | Todo OK |
| 1 | Error (red, API, BD, excepción) |
| 2 | Terminó, pero Vasco rechazó filas (ver `↳ rechazado` en el log) o se omitió el finalize |
| 3 | Ya había otra corrida en curso |

## 5. Notas

- **Solapamiento:** hay un lock; si la diaria cae mientras corre la horaria, sale con código 3 y ese día
  no hace la purga. Por eso va a las 23:30. Mide cuánto tarda una corrida y ajusta.
- **`--finalize`** purga en Vasco los clientes que ya no deben, y solo se envía si toda la corrida de
  cuentas fue limpia (sin lotes fallidos ni parciales). Si no, el log dice `FINALIZE OMITIDO`.
- Buscar problemas en los logs: `findstr /I "ERROR ABORTADA rechazado EXCEPCIÓN OMITIDO" C:\xampp\htdocs\vascorp\scripts\logs\*.log`
- Pausar: `schtasks /Change /TN "Vasco Sync Horario" /DISABLE`

## 6. Problemas frecuentes

| Síntoma | Causa / solución |
|---------|------------------|
| `could not find driver` | Falta `extension=php_pdo_mysql.dll` en el `php.ini` que usa la consola |
| `cURL no está disponible` | Falta `extension=php_curl.dll` |
| `SQLSTATE[HY000] [2002]` | No llega a `192.168.1.64` |
| `API key no configurada` | Falta `VASCO_ONLINE_API_KEY` en `config.php` |
| `el API no responde` | Sin internet o firewall hacia `api.jackyform.com.pe` |
| La tarea no hace nada | Revisar "Iniciar en" y que la ruta de `php.exe` sea correcta; mirar el log |
