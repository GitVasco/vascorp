# Limpieza de programación semanal y cierre automático por envío a taller

Fecha: 2026-10-27 (ejecutado a distancia, sin acceso directo al servidor)

## Problema detectado

El envío a taller (`ModeloCortes::mdlMandarTallerCabV2`) solo escribe en `entaller_cabjf`,
`almacencorte_detallejf` y `articulojf`. No tocaba `programacion_taller_semanajf` ni
`programacion_taller_prioridadjf`. Consecuencias:

1. Lo enviado sin priorizar ni programar no deja ninguna fila en el módulo (vive solo en el Excel).
2. Las líneas programadas que sí se enviaron quedaban con `estado = 1` para siempre. El listado
   "no ejecutados" las ocultaba solo porque su OC ya no tenía saldo vivo.

Diagnóstico (consulta de envíos del año contra programación de la misma semana):
hasta la semana 32 el 100 % de las prendas enviadas no tenía programación en su semana;
desde la 33 (inicio de uso del módulo) sigue habiendo envíos sin programar
(p. ej. semana 43: 21 052 de 61 768 prendas).

## Cambios en base de datos

Respaldos creados (tablas completas, antes de modificar):

- `bk_pts_20261027`  = copia de `programacion_taller_semanajf`
- `bk_ptp_20261027` = copia de `programacion_taller_prioridadjf`

Tablas auxiliares (se pueden borrar): `tmp_pts_ids`, `tmp_ptp_ids`.

Criterio de limpieza: desactivar (`estado = 0`, `usumod = 'limpieza'`, `fecmod = NOW()`) las líneas
activas cuya OC ya no tiene saldo vivo:

- OC > 0: sin saldo en `detalles_ordencortejf` (saldo > 0, estado 0, OC no 'Cerrado') ni en
  `almacencorte_detallejf` (saldo_taller/cantidad > 0, año actual) para esa OC.
- OC NULL/0: el modelo/color no tiene `alm_corte` ni `ord_corte` en `articulojf`.

Resultado previo a aplicar: 361 de 784 líneas de semana y 10 de 10 de prioridad marcadas.
En semana solo se desactivan las de semanas cerradas (`fecha_fin < CURDATE()`).
Es un soft-delete (el mismo que `mdlEliminar`): no se borró ninguna fila.

### Cómo identificar lo que tocó la limpieza

```sql
SELECT * FROM programacion_taller_semanajf   WHERE usumod = 'limpieza' AND DATE(fecmod) = '2026-10-27';
SELECT * FROM programacion_taller_prioridadjf WHERE usumod = 'limpieza' AND DATE(fecmod) = '2026-10-27';
```

### Reversión

```sql
UPDATE programacion_taller_semanajf p JOIN bk_pts_20261027 b ON b.id = p.id
SET p.estado = b.estado, p.usumod = b.usumod, p.fecmod = b.fecmod
WHERE p.usumod = 'limpieza';

UPDATE programacion_taller_prioridadjf p JOIN bk_ptp_20261027 b ON b.id = p.id
SET p.estado = b.estado, p.usumod = b.usumod, p.fecmod = b.fecmod
WHERE p.usumod = 'limpieza';
```

## Cambios en código

- `modelos/programacion-taller-semana.modelo.php`: nuevo
  `mdlCerrarLineasSinSaldo($articulo, $usumod = "envio_taller")`. Para el modelo/color del artículo,
  desactiva líneas activas (semana y prioridad) cuya OC no tiene saldo en `mdlSaldosPorOc`.
  Las líneas sin OC siguen vivas mientras el modelo/color tenga saldo en alguna OC.
- `modelos/cortes.modelo.php`: `mdlMandarTallerCabV2` la invoca tras el `commit`, dentro de
  `try/catch (Throwable)` (solo registra en `error_log`; un fallo nunca rompe el envío).
  Cubre los tres puntos de envío: `ctrMandarTaller`, el envío total y
  `ctrMandarUnArticuloPts` (desde programación).

Las líneas cerradas automáticamente quedan con `usumod` = usuario que envió (o `envio_taller`).

Estado: sin commit ni push al momento de escribir esto. Sintaxis verificada con `php -l`;
no probado contra la base de datos.

### Si el cierre automático da problemas

Quitar el bloque `try { require_once ... mdlCerrarLineasSinSaldo ... }` de `mdlMandarTallerCabV2`.
El resto del sistema no depende de él.

## Pendiente (no resuelto)

Lo enviado sin programar sigue sin quedar registrado. Propuesta: tabla
`programacion_taller_ejecutadojf` (modelo, cod_color, ordencorte, cod_sector, cantidad, fecha,
anio, semana, programado S/N) alimentada desde `mdlMandarTallerCabV2`, para comparar
programado vs. ejecutado en el reporte semanal. Requiere SQL nuevo y pruebas; hacerlo con acceso
al servidor.
