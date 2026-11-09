# Sync programada Vasco Online en servidor Windows

Reemplaza el proceso manual de la pantalla **Vasco Online** (clientes, grupos, cuentas) por una
tarea del **Programador de tareas de Windows** que ejecuta `scripts/vasco-sync-cron.bat`.

Rutas de ejemplo: el proyecto está en `C:\xampp\htdocs\vascorp`. Ajusta si es otra.

---

## 1. Requisitos en el servidor

| # | Qué | Cómo verificar |
|---|-----|----------------|
| 1 | PHP CLI (viene con XAMPP) | `C:\xampp\php\php.exe -v` |
| 2 | Extensión `curl` | `C:\xampp\php\php.exe -m` → debe listar `curl` |
| 3 | Extensión `pdo_mysql` | igual → debe listar `pdo_mysql` |
| 4 | Llega a la BD `192.168.1.64:3306` | `Test-NetConnection 192.168.1.64 -Port 3306` (PowerShell) |
| 5 | Llega al API por internet | `curl https://api.jackyform.com.pe/health` → 200 |

Si faltan `curl` o `pdo_mysql`: en `C:\xampp\php\php.ini` quita el `;` de `extension=curl` y
`extension=pdo_mysql`.

## 2. Llevar el código

1. Copiar/actualizar el proyecto en `C:\xampp\htdocs\vascorp` (git pull de la rama).
2. Confirmar que existen `scripts\vasco-sync-cron.php` y `scripts\vasco-sync-cron.bat`.
3. Crear/revisar `controladores\config.php` (**no está en git**). Debe tener:

```php
define("VASCO_ONLINE_ENTORNO", "produccion");   // "pruebas" para la primera prueba
define("VASCO_ONLINE_API_KEY", "la-clave-real");
```

4. Abrir `scripts\vasco-sync-cron.bat` y poner en `PHP_EXE` la ruta real de `php.exe`.

## 3. Pruebas manuales (antes de programar nada)

Abrir **CMD** y entrar al proyecto:

```bat
cd C:\xampp\htdocs\vascorp
```

**Prueba A – solo clientes**

```bat
C:\xampp\php\php.exe scripts\vasco-sync-cron.php clientes
echo %ERRORLEVEL%
```

Esperado: `Conexión al API OK`, líneas `clientes · Lote N enviado correctamente` y
`código de salida 0`. Revisar el log `scripts\logs\vasco-sync-AAAAMMDD.log`.

**Prueba B – grupos y cuentas**

```bat
C:\xampp\php\php.exe scripts\vasco-sync-cron.php grupos
C:\xampp\php\php.exe scripts\vasco-sync-cron.php cuentas
```

**Prueba C – corrida completa** (lo que hará la tarea horaria)

```bat
scripts\vasco-sync-cron.bat
```

**Prueba D – con cierre/purga** (solo cuando A–C estén bien)

```bat
scripts\vasco-sync-cron.bat --finalize
```

Verificar en Vasco (pantalla o API) que clientes/cuentas coinciden con vascorp.

**Prueba E – lock:** abrir dos CMD y lanzar `scripts\vasco-sync-cron.bat` en ambas casi a la vez.
La segunda debe decir `Ya hay una sincronización en curso` y salir con código 3.

### Códigos de salida

| Código | Significado |
|--------|-------------|
| 0 | Todo OK |
| 1 | Error (red, API, BD, excepción) |
| 2 | Terminó pero Vasco rechazó filas (ver `↳ rechazado` en el log) o se omitió el finalize |
| 3 | Ya había otra corrida |

## 4. Programar las tareas (Programador de tareas)

Abrir **CMD como Administrador**. Dos tareas:

**Tarea 1 – cada hora (sin purga)**

```bat
schtasks /Create /TN "Vasco Sync Horario" /SC HOURLY /ST 06:00 /RU SYSTEM /RL HIGHEST ^
  /TR "C:\xampp\htdocs\vascorp\scripts\vasco-sync-cron.bat"
```

**Tarea 2 – una vez al día, con purga de cuentas**

```bat
schtasks /Create /TN "Vasco Sync Diario Finalize" /SC DAILY /ST 23:30 /RU SYSTEM /RL HIGHEST ^
  /TR "\"C:\xampp\htdocs\vascorp\scripts\vasco-sync-cron.bat\" --finalize"
```

> Si prefieres la interfaz: **Programador de tareas → Crear tarea** → General: "Ejecutar tanto
> si el usuario inició sesión como si no" + "Ejecutar con los privilegios más altos" →
> Desencadenadores: repetir cada 1 hora → Acciones: *Iniciar un programa* →
> `C:\xampp\htdocs\vascorp\scripts\vasco-sync-cron.bat` (argumentos: `--finalize` en la diaria) →
> Iniciar en: `C:\xampp\htdocs\vascorp`.

Probar sin esperar:

```bat
schtasks /Run /TN "Vasco Sync Horario"
schtasks /Query /TN "Vasco Sync Horario" /V /FO LIST
```

Ver "Último resultado" = 0 en la consulta, y el log del día.

### Solapamiento
Un lock impide dos corridas a la vez. Si la tarea diaria cae mientras corre la horaria, la
diaria sale con código 3 y **no hace el finalize ese día**. Por eso se programa a las 23:30
(lejos de la hora en punto) y conviene que una corrida normal dure bastante menos de 30 min.
Revisa en el log cuánto tarda y ajusta.

## 5. Operación diaria

- Logs: `scripts\logs\vasco-sync-AAAAMMDD.log` (borrar los viejos de vez en cuando).
- Buscar problemas: `findstr /I "ERROR ABORTADA rechazado EXCEPCIÓN OMITIDO" scripts\logs\*.log`
- Pausar la sync: `schtasks /Change /TN "Vasco Sync Horario" /DISABLE` (y `/ENABLE` para reactivar).
- Eliminar: `schtasks /Delete /TN "Vasco Sync Horario" /F`

## 6. Problemas frecuentes

| Síntoma | Causa / solución |
|---------|------------------|
| `'php.exe' no se reconoce` | `PHP_EXE` mal en el `.bat` |
| `could not find driver` | Falta `pdo_mysql` en `php.ini` |
| `cURL no está disponible` | Falta `extension=curl` |
| `SQLSTATE[HY000] [2002]` | El servidor no llega a `192.168.1.64` |
| `API key no configurada` | Falta `VASCO_ONLINE_API_KEY` en `config.php` |
| `el API no responde` | Sin internet/firewall hacia `api.jackyform.com.pe` |
| Tarea "Último resultado" 0x1 | Error; leer el log. Con `/RU SYSTEM` no hay consola, solo el log |
| Código 2 constante | Filas rechazadas: corregir el dato en vascorp (documento, tipo, etc.) |
| `FINALIZE OMITIDO` | La corrida de cuentas tuvo lotes parciales/fallidos; es la protección contra purgar de más |

## 7. Qué hace el script (resumen)

`todo` = clientes → grupos → miembros → cuentas. Cada lote se reintenta 3 veces. Si clientes
falla, no sigue. `--finalize` solo purga si toda la corrida de cuentas fue limpia.
Detalle técnico en `VASCO_ONLINE_SYNC.md` y en la cabecera de `scripts/vasco-sync-cron.php`.
