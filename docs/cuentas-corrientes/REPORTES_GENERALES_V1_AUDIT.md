# Auditoría — Reportes generales v1 (legacy)

Pantalla: `/reportes-generales`  
Fecha auditoría: 2026-09-01  
Alcance: formulario → `vistas/js/cuentas.js` → salida pantalla/Excel → modelos.

---

## Resumen

De **16 tipos** en el menú:

| Estado | Cantidad | Tipos |
|--------|----------|-------|
| Pantalla parcial | 4 | Doc. por cobrar, vencidos, no vencidos, protestados |
| Pantalla + Excel (incompletos) | 3 | Pagos, Estado de cuenta, Saldos a una fecha |
| Sin acción al Aceptar | 8 | Letras (3), banco/estado (2), cancelados, movimientos, resumen saldos S/ |
| Deshabilitado | 1 | Pagos-comisiones |

**Problema central:** un solo handler en `cuentas.js` sin fallback; muchos radios no abren nada. Pantalla y Excel no comparten los mismos filtros. Tres familias de salida mezcladas (TCPDF, ticket HTML, PHPExcel + `mysql_*`).

---

## Catálogo v1 (radio → comportamiento real)

| # | `value` | Etiqueta UI | Pantalla | Excel | Notas |
|---|---------|-------------|----------|-------|-------|
| 1 | `pendiente` | Doc. por cobrar | Parcial | Parcial | Excel solo este tipo; query fija sin filtros |
| 2 | `pendienteVencidoMenor` | Doc. vencidos | Parcial | **No** | — |
| 3 | `pendienteVencidoMayor` | Doc. no vencidos | Parcial | **No** | — |
| 4 | `protestado` | Doc. protestados | Parcial | **No** | — |
| 5 | `option5` | Letras por imprimir | **No** | **No** | Sin backend de listado |
| 6 | `estadoEnvioVacio` | Letras por aceptar | **No** | **No** | Lógica en `letras_aceptar.php` (otro módulo) |
| 7 | `unicoCartera` | Letras en cartera | **No** | **No** | Clasificación CARTERA solo en gerencia |
| 8 | `option8` | Doc. por banco/estado | **No** | **No** | — |
| 9 | `option9` | Doc. por estado/banco | **No** | **No** | — |
| 10 | `cancelado` | Doc. Cancelados | **No** | **No** | Stubs vacíos en PDF |
| 11 | `option11` | Movimientos en Ctas.ctes. | **No** | **No** | — |
| 12 | `fechaSaldo` | Saldos a una fecha | Sí | Sí | Ignora orden, cliente, vendedor, banco |
| 13 | `pagos` | Pagos | Parcial | Parcial | PDF: pocos órdenes; Excel: fechas + cliente |
| 14 | `fechaActualSaldo` | Estado de cuenta | Parcial | Sí | Pantalla solo fecha inicio; Excel completo |
| 15 | `option15` | Rsm saldos a una fecha (S/) | **No** | **No** | — |
| 16 | `option16` | Pagos-comisiones | — | — | `disabled` en UI |

---

## Flujo al pulsar Aceptar (v1)

```
PANTALLA
├── pendiente | vencidos | no vencidos | protestados
│   └── PDF según orden1 → general | cliente | vendedor | fecha_ven
├── pagos → reporte_pago_cuentas.php
├── fechaActualSaldo → reporte_estado_cuentas.php (ticket HTML)
├── fechaSaldo → reporte_saldo_fecha.php
└── resto → (silencio, no abre nada)

EXCEL
├── pagos → rpt_pagos_cta_cte.php
├── fechaActualSaldo → rpt_estado_cuenta.php (+ validaciones)
├── fechaSaldo → rpt_saldo_fecha.php
├── pendiente (solo) → rpt_ctas_ctes.php (sin parámetros GET)
└── resto → (silencio)
```

---

## Cobranza básica (1–4) — detalle pantalla

Orden primario vs archivo PDF:

| `orden1` | Archivo | Observaciones |
|----------|---------|---------------|
| `tipo` | `extensiones/tcpdf/pdf/reporte_general_cuentas.php` | OK |
| `cliente` | `extensiones/tcpdf/pdf/reporte_cliente_cuentas.php` | Sin cliente: lista agrupada por todos |
| `vendedor` | `reporte_vendedor_cuentas.php` si hay vend; si no, general | — |
| `fecha_ven` | `reporte_general_cuentas.php` | Usa `tip_doc`, `banco`, `fin` |
| `fecha_pag` | — | **No enrutado** (no aplica a cobranza) |

Excel: únicamente `pendiente` → `vistas/reportes_excel/rpt_ctas_ctes.php` con SQL fijo:

```sql
WHERE cc.tip_mov = '+' AND cc.estado = 'PENDIENTE'
```

**Huérfano:** `vistas/reportes_excel/rpt_cliente_cuenta.php` acepta `consulta`, `orden1`, `cli`, etc., pero **no está cableado** desde Reportes generales.

---

## Letras y cartera (5–7)

| Reporte | En v1 | Lógica existente fuera de Reportes generales |
|---------|-------|-----------------------------------------------|
| Letras por imprimir | Nada | Impresión unitaria desde `/ver-cuentas` |
| Letras por aceptar | Nada | `vistas/reportes_ticket/letras_aceptar.php`, `mdlLetrasAceptar` (botón `.btnPorAceptar` + prompt) |
| Letras en cartera | Nada | `mdlEstadoLetrasPorCobrarGerencia` (dashboard gerencial) |

En PDF legacy hay bloques vacíos:

```php
} else if ($consulta == 'estadoEnvioVacio') {
} else if ($consulta == 'unicoCartera') {
} else if ($consulta == 'cancelado') {
```

(En `reporte_general_cuentas.php`, `reporte_cliente_cuentas.php`, `reporte_vendedor_cuentas.php`.)

---

## Pagos (13)

| Salida | Archivo | Filtros efectivos |
|--------|---------|-------------------|
| PDF | `extensiones/tcpdf/pdf/reporte_pago_cuentas.php` | Fechas, cancelación, vendedor, cliente (reciente); órdenes limitados en modelo |
| Excel | `vistas/reportes_excel/rpt_pagos_cta_cte.php` | Fechas, cliente; **ignora** orden, cancelación, vendedor |

Modelo `mdlMostrarReportePagos`: ramas principalmente para `fecha_pag` / `cliente` + `ordNumCuenta`, y `vendedor` + `ordNumCuenta`. Otros órdenes pueden dejar `$stmt` sin definir.

---

## Estado de cuenta (14)

| Salida | Archivo | Filtros |
|--------|---------|---------|
| Pantalla | `vistas/reportes_ticket/reporte_estado_cuentas.php` | Solo `inicio` → `ctrEstadoCuenta($inicio)` |
| Excel | `vistas/reportes_excel/rpt_estado_cuenta.php` | Cliente, vendedor, rango, validación máx. 6 años |

**No hay paridad** entre pantalla y Excel.

---

## Saldos a una fecha (12)

| Salida | Archivo | Datos |
|--------|---------|-------|
| PDF | `extensiones/tcpdf/pdf/reporte_saldo_fecha.php` | `ctrSaldoFecha($inicio, $fin)` |
| Excel | `vistas/reportes_excel/rpt_saldo_fecha.php` | Igual |

Filtros de UI (orden, cliente, banco, tipo doc) no se usan.

---

## Deuda técnica v1 (por qué no refactorizar in situ)

1. Handler monolítico en `cuentas.js` (~250 líneas mezcladas con otras pantallas de cuentas).
2. Estado en atributos HTML del botón (`consulta`, `cli`, `vend`…).
3. Tres stacks: TCPDF, tickets HTML, PHPExcel + **`mysql_*` deprecado**.
4. IDs opacos (`option5`, `option8`…).
5. UI incoherente: título “Administrar agencias”; bancos siempre visible; orden “Por Fch. Pago” en reportes donde no aplica.
6. Reportes útiles vivos en otros módulos sin enlace desde aquí.

---

## Mapa de archivos v1 (referencia)

| Rol | Ruta |
|-----|------|
| Vista | `vistas/modulos/cuentas-corrientes/reportes-generales.php` |
| JS | `vistas/js/cuentas.js` (`.btnGenerarReporteCuenta`) |
| PDF general | `extensiones/tcpdf/pdf/reporte_general_cuentas.php` |
| PDF cliente | `extensiones/tcpdf/pdf/reporte_cliente_cuentas.php` |
| PDF vendedor | `extensiones/tcpdf/pdf/reporte_vendedor_cuentas.php` |
| PDF pagos | `extensiones/tcpdf/pdf/reporte_pago_cuentas.php` |
| PDF saldos | `extensiones/tcpdf/pdf/reporte_saldo_fecha.php` |
| Ticket EC | `vistas/reportes_ticket/reporte_estado_cuentas.php` |
| Excel cobranza (conectado) | `vistas/reportes_excel/rpt_ctas_ctes.php` |
| Excel cobranza (huérfano) | `vistas/reportes_excel/rpt_cliente_cuenta.php` |
| Excel pagos | `vistas/reportes_excel/rpt_pagos_cta_cte.php` |
| Excel EC | `vistas/reportes_excel/rpt_estado_cuenta.php` |
| Excel saldos | `vistas/reportes_excel/rpt_saldo_fecha.php` |
| Modelo | `modelos/cuentas.modelo.php` (`mdlMostrarReporte*`, `mdlSaldoFecha`, `mdlLetrasAceptar`) |

---

## Criterio de corte a v2

No reemplazar el ítem de menú “Reportes Generales” hasta que v2 tenga:

1. Paridad funcional acordada con negocio para cada tipo de reporte **activo** (los `disabled` pueden quedar fuera de alcance).
2. Mismos filtros en pantalla y Excel por reporte (salvo decisión explícita de “solo Excel”).
3. Mensaje claro si un reporte no está listo (nunca silencio al Aceptar).
4. Prueba manual documentada por tipo × orden × filtros.
