# Catálogo completo — Reportes generales v1 (legacy)

Inventario **exhaustivo** de la pantalla `/reportes-generales` para no olvidar ningún reporte al migrar a v2.

Relacionado: [auditoría](REPORTES_GENERALES_V1_AUDIT.md) · [plan v2](PLAN_REPORTES_GENERALES_V2.md)

---

## Controles globales de la UI v1

| Control | IDs / valores | Cuándo aplica |
|---------|---------------|---------------|
| Tipo consulta | 16 radios (`optradio`) | Siempre |
| Orden primario | `tipo`, `cliente`, `vendedor`, `fecha_ven`, `fecha_pag` | Siempre visible (aunque no todos aplican) |
| Orden secundario | `ordNumCuenta`, `ordVencimiento`, `ordCliente` | Siempre visible |
| Tipo documento | `#tipoDocumentoReporte` | Oculto solo en **Pagos** |
| Tipo cancelación | `#tipoCancelacionReporte` | Solo **Pagos** |
| Cliente | `#tipoClienteReporte` | Pagos; Estado de cuenta; orden primario Cliente |
| Vendedor | `#tipoVendedorReporte` | Estado de cuenta; orden primario Vendedor; Pagos + orden Vendedor |
| Banco | `#tipoBancoReporte` | Siempre visible en UI |
| Fecha inicio / fin | `#fechaCuentaInicio`, `#fechaCuentaFin` | Deshabilitadas en cobranza/letras 1–10; habilitadas en 11–15 |
| Salida | `pantalla` / `excel` | Siempre |
| Botón | `.btnGenerarReporteCuenta` | Atributos: `consulta`, `orden1`, `orden2`, `tip_doc`, `cli`, `vend`, `banco`, `canc`, `inicio`, `fin`, `impresion` |

**Defectos UI v1:** título “Administrar agencias”; muchos filtros visibles aunque el reporte no los use; sin mensaje si Aceptar no hace nada.

---

## Inventario de los 16 tipos (checklist migración)

| # | v1 `value` | Etiqueta | id v2 | Fase v2 | v1 pantalla | v1 Excel | Backend v1 | Notas negocio |
|---|------------|----------|-------|---------|-------------|----------|------------|---------------|
| 1 | `pendiente` | Doc. por cobrar | `doc_por_cobrar` | 1 | PDF parcial | Excel fijo sin filtros | `mdlMostrarReporteCobrar` | Docs `+` pendientes |
| 2 | `pendienteVencidoMenor` | Doc. vencidos | `doc_vencidos` | 1 | PDF parcial | No | `mdlMostrarReporteVencidos` | `fecha_ven` &lt; hoy |
| 3 | `pendienteVencidoMayor` | Doc. no vencidos | `doc_no_vencidos` | 1 | PDF parcial | No | `mdlMostrarReporteNoVencidos` | `fecha_ven` ≥ hoy |
| 4 | `protestado` | Doc. protestados | `doc_protestados` | 1 | PDF parcial | No | `mdlMostrarReporteProtestados` | `protesta = 1` |
| 5 | `option5` | Letras por imprimir | `letras_por_imprimir` | 2 | No | No | — | Impresión unitaria en `/ver-cuentas` |
| 6 | `estadoEnvioVacio` | Letras por aceptar | `letras_por_aceptar` | 2 | No | No | `mdlLetrasAceptar` | `letras_aceptar.php` (otro menú) |
| 7 | `unicoCartera` | Letras en cartera | `letras_en_cartera` | 2 | No | No | gerencia CARTERA | `num_unico` tipo cartera |
| 8 | `option8` | Doc. por banco/estado | `doc_por_banco_estado` | 3 | No | No | — | Agrupar banco → estado |
| 9 | `option9` | Doc. por estado/banco | `doc_por_estado_banco` | 3 | No | No | — | Agrupar estado → banco |
| 10 | `cancelado` | Doc. Cancelados | `doc_cancelados` | 2 | No | No | stub PDF | Estado cancelado |
| 11 | `option11` | Movimientos en Ctas.ctes. | `movimientos_ctacte` | 3 | No | No | — | +/- en rango fechas |
| 12 | `fechaSaldo` | Saldos a una fecha | `saldos_fecha` | 1 | PDF | Excel | `mdlSaldoFecha` | Corte a `fin` |
| 13 | `pagos` | Pagos | `pagos` | 1 | PDF parcial | Excel parcial | `mdlMostrarReportePagos` | Movimientos `-` |
| 14 | `fechaActualSaldo` | Estado de cuenta | `estado_cuenta` | 1 | Ticket HTML | Excel completo | `ctrEstadoCuenta` / `rpt_estado_cuenta` | Paridad rota en v1 |
| 15 | `option15` | Rsm saldos a una fecha (S/) | `resumen_saldos_fecha` | 3 | No | No | — | Resumen soles por cliente |
| 16 | `option16` | Pagos-comisiones | `pagos_comisiones` | — | disabled | disabled | — | Fuera alcance v2 inicial |

**Total activos a migrar:** 15 (excluye #16 disabled).

---

## Detalle por reporte v1

### 1 — Doc. por cobrar (`pendiente`)

| Aspecto | Detalle |
|---------|---------|
| PDF | `reporte_general_cuentas.php` (tipo/fecha_ven); `reporte_cliente_cuentas.php` (cliente); `reporte_vendedor_cuentas.php` (vendedor con código) |
| Excel | `rpt_ctas_ctes.php` — **sin GET**; todos los pendientes |
| Excel huérfano | `rpt_cliente_cuenta.php` — no cableado |
| Filtros útiles | orden1/2, tip_doc, cli, vend, banco, fin (si fecha_ven) |
| Equivalente Aida | `cxc-portfolio` |

### 2 — Doc. vencidos (`pendienteVencidoMenor`)

| Aspecto | Detalle |
|---------|---------|
| PDF | Mismos 3 PDF que #1 con `consulta=pendienteVencidoMenor` |
| Excel | **No** |
| Modelo | `mdlMostrarReporteVencidos` |
| Equivalente Aida | `cxc-overdue` |

### 3 — Doc. no vencidos (`pendienteVencidoMayor`)

| Aspecto | Detalle |
|---------|---------|
| PDF | Mismos 3 PDF con `consulta=pendienteVencidoMayor` |
| Excel | **No** |
| Modelo | `mdlMostrarReporteNoVencidos` |
| Equivalente Aida | `cxc-upcoming` (criterio puede diferir) |

### 4 — Doc. protestados (`protestado`)

| Aspecto | Detalle |
|---------|---------|
| PDF | Mismos 3 PDF con `consulta=protestado` |
| Excel | **No** |
| Modelo | `mdlMostrarReporteProtestados` |
| Equivalente Aida | — (parcial en cartera/vencidos) |

### 5 — Letras por imprimir (`option5`)

| Aspecto | Detalle |
|---------|---------|
| v1 | **Sin enrutamiento** |
| Relacionado | `imprimir_letra.php`, `letra_full.php`, `mdlMostrarCuentasNroUnico` |
| Criterio v2 (pendiente negocio) | Letras 85 sin nro. único / estado 01 / banco 02 |

### 6 — Letras por aceptar (`estadoEnvioVacio`)

| Aspecto | Detalle |
|---------|---------|
| v1 | **Sin enrutamiento**; stub vacío en PDF |
| Relacionado | `vistas/reportes_ticket/letras_aceptar.php`, `mdlLetrasAceptar`, botón `.btnPorAceptar` |
| Filtros típicos | vendedor, mes (hoy vía prompt) |

### 7 — Letras en cartera (`unicoCartera`)

| Aspecto | Detalle |
|---------|---------|
| v1 | **Sin enrutamiento**; stub vacío |
| Relacionado | `mdlEstadoLetrasPorCobrarGerencia`, `num_unico` contiene CARTERA |
| Equivalente Aida | parte de `cxc-letters` |

### 8 — Doc. por banco/estado (`option8`)

| Aspecto | Detalle |
|---------|---------|
| v1 | **Sin implementación** |
| v2 | Agrupación + subtotales (`preview_informe`) |

### 9 — Doc. por estado/banco (`option9`)

| Aspecto | Detalle |
|---------|---------|
| v1 | **Sin implementación** |
| v2 | Agrupación inversa a #8 |

### 10 — Doc. Cancelados (`cancelado`)

| Aspecto | Detalle |
|---------|---------|
| v1 | **Sin enrutamiento**; stub vacío en PDF |
| v2 | Listado docs cancelados |

### 11 — Movimientos en Ctas.ctes. (`option11`)

| Aspecto | Detalle |
|---------|---------|
| v1 | **Sin implementación** |
| Fechas v1 | Habilitadas al elegir este radio |
| v2 | `+` y `-` en rango; preview paginada |

### 12 — Saldos a una fecha (`fechaSaldo`)

| Aspecto | Detalle |
|---------|---------|
| PDF | `reporte_saldo_fecha.php` |
| Excel | `rpt_saldo_fecha.php` |
| Modelo | `mdlSaldoFecha` / `ctrSaldoFecha` |
| Filtros v1 usados | principalmente `fin` (inicio opcional) |

### 13 — Pagos (`pagos`)

| Aspecto | Detalle |
|---------|---------|
| PDF | `reporte_pago_cuentas.php` |
| Excel | `rpt_pagos_cta_cte.php` |
| Modelo | `mdlMostrarReportePagos`, `mdlMostrarReporteTotalPagos` |
| Filtros v1 | fechas, canc, vend, cli (reciente), orden limitado |
| Equivalente Aida | `cxc-collections` |

### 14 — Estado de cuenta (`fechaActualSaldo`)

| Aspecto | Detalle |
|---------|---------|
| Pantalla | `reporte_estado_cuentas.php` — solo `inicio` |
| Excel | `rpt_estado_cuenta.php` — cliente, vend, rango, max 6 años |
| Equivalente Aida | `cxc-statement` |

### 15 — Rsm saldos a una fecha S/ (`option15`)

| Aspecto | Detalle |
|---------|---------|
| v1 | **Sin implementación** |
| Fechas v1 | Habilitadas |
| v2 | Resumen por cliente en soles a fecha corte |

### 16 — Pagos-comisiones (`option16`)

| Aspecto | Detalle |
|---------|---------|
| v1 | Radio **disabled** |
| v2 | Fuera de alcance hasta definición |

---

## Reportes relacionados FUERA de `/reportes-generales`

No olvidar al diseñar v2; algunos usuarios los usan hoy en lugar del menú legacy:

| Pantalla / botón | Archivo | Uso |
|------------------|---------|-----|
| Cuentas → Excel pendientes | `rpt_cuentas_pendientes.php` | Por año |
| Cuentas → Excel aprobadas | `rpt_cuentas_aprobadas.php` | Por año |
| Cuentas → Letras urgentes | `rpt_letras_urgentes_protesto.php` | — |
| Cuentas → Por aceptar | `letras_aceptar.php` | Prompt vendedor/mes |
| Cuentas → Deuda clientes | `rpt_cuentas_deuda_clientes.php` | Modal export |
| Cuentas → EC gerencia | `rpt_estado_cuenta_gerencia.php` | Modal |
| Cuentas → Morosidad | `rpt_clasificacion_morosidad.php` | Modal |
| Clientes → EC ticket | `estado_cuenta.php` (ticket) | Por cliente + línea |
| Línea de crédito | `/linea-credito` | Export Excel cartera |
| Informe semanal vendedor | `/informe-semanal-vendedor` | Patrón preview (referencia) |

Estos **no** se reemplazan en Fase 1; v2 se enfoca en el catálogo de los 15 radios activos de Reportes generales.

---

## Matriz orden primario × reporte (v1 pantalla)

| orden1 | Cobranza 1–4 | Pagos | Saldos | EC |
|--------|--------------|-------|--------|-----|
| `tipo` | PDF general | No enrutado / falla | — | — |
| `cliente` | PDF cliente | — | — | — |
| `vendedor` | PDF vendedor o general | PDF vendedor | — | — |
| `fecha_ven` | PDF general + fin | — | — | — |
| `fecha_pag` | **Silencio** | PDF (ramas modelo) | — | — |

---

## Archivos legacy (no modificar en v2)

Ver lista completa en [REPORTES_GENERALES_V1_AUDIT.md](REPORTES_GENERALES_V1_AUDIT.md#mapa-de-archivos-v1-referencia).

---

## Checklist “¿olvidamos algún reporte?”

- [x] 16 radios documentados (#1–#16)
- [x] Mapeo id v2 por cada uno
- [x] Fase v2 asignada
- [x] Estado pantalla/Excel v1
- [x] Backend / stubs
- [x] Equivalente Aida (si existe)
- [x] Reportes externos relacionados
- [x] Controles globales UI (orden, filtros, fechas)
- [x] Matriz orden × reporte

Actualizar este documento si se descubre un reporte adicional en producción.
