# Rollback: eliminar cancelación / renovación en `/ver-cuentas`

Fecha: 18 ago 2026.

Corrección al borrar un abono o renovación: el saldo no volvía y el estado no regresaba a `PENDIENTE`.

No hay cambio de tablas ni de datos. El rollback es **solo de archivos**.

## Archivos tocados

| Archivo | Qué cambió |
|---------|------------|
| `controladores/cuentas.controlador.php` | Función `ctrEliminarCancelacion` |
| `vistas/js/cuentas.js` | Click de `.btnEliminarCancelacion` |
| `vistas/modulos/cuentas-corrientes/ver-cuentas.php` | Tres inputs hidden (`idCuentaOrigen`, `verCuentasNumCta`, `verCuentasCodCuenta`) |

No se tocó crear abono, Dividir letra, ni el modelo.

## Comportamiento nuevo (por si hay que comparar)

1. El borrado manda el `id` del cargo que se está viendo (`idOrigen`), más número y tipo de documento.
2. Si ese `id` no coincide con el movimiento (`num_cta` + `tipo_doc`), **no borra** y avisa.
3. Si no hay cargo original, **no borra**.
4. Saldo: igual que antes, `saldo actual + monto del movimiento`.
5. Estado: misma regla que al registrar el abono (`PENDIENTE` si queda saldo; `CANCELADO` si queda ~0). Antes solo ponía `PENDIENTE` si el saldo era `> 0`, y no tocaba el estado en el resto de casos.
6. Se quitó el `var_dump` que salía al borrar.

## Cómo volver atrás

Desde la raíz del repo, restaurar los tres archivos al último commit:

```bash
git checkout -- controladores/cuentas.controlador.php vistas/js/cuentas.js vistas/modulos/cuentas-corrientes/ver-cuentas.php
```

Si el cambio ya está commiteado, revertir ese commit o restaurar esos tres archivos desde el commit anterior.

Después de publicar el rollback:

- Recargar fuerte el navegador (`Cmd+Shift+R` / `Ctrl+F5`) para que no quede el JS nuevo en caché.
- No hace falta SQL.
- Los movimientos ya borrados con la corrección no se “deshacen”: el rollback solo cambia el código de ahora en adelante.

## Comportamiento viejo (referencia)

Al borrar:

1. Buscaba el cargo `+` con `ctrMostrarCuentasV2(num_cta, tipo_doc)` (un `fetch` sin `ORDER BY`; si había duplicados podía tomar otro registro).
2. `saldoNuevo = monto + saldo`.
3. Si `saldoNuevo > 0`, ponía `PENDIENTE`. Si no, **no cambiaba el estado**.
4. Actualizaba saldo y **siempre** borraba el movimiento `-`, aunque el cargo original no existiera.
5. Hacía `var_dump` del update.

La URL de borrado era:

`index.php?ruta=ver-cuentas&idCancelacion=...&rutas=...`
