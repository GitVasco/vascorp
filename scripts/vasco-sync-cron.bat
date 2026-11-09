@echo off
REM Sync programada vascorp -> Vasco Online.
REM Uso desde el Programador de tareas / CaXMPP:
REM   vasco-sync-cron.bat            -> clientes + grupos + cuentas (sin purga)
REM   vasco-sync-cron.bat --finalize -> igual, y cierra cuentas con purga (1 vez al dia)
REM Ajusta PHP_EXE a la ruta de tu php.exe (XAMPP: C:\xampp\php\php.exe).
set PHP_EXE=C:\xampp\php\php.exe
"%PHP_EXE%" "%~dp0vasco-sync-cron.php" todo %*
exit /b %ERRORLEVEL%
