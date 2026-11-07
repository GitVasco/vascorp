# Órdenes de servicio de regularización

Una **orden de regularización** agrupa, por taller, los servicios enviados desde el
**01-01-2026 hasta el 31-10-2026** (antes de usar órdenes de servicio) para que las
liquidaciones de ese periodo muestren su OSP de origen en lugar de «SIN OSP».
Lo enviado desde el 01-11-2026 sigue el flujo normal (orden de servicio → guías → liquidación).

## Qué es y qué no es

| | Orden normal | Orden de regularización |
|---|---|---|
| Importes / precios | Sí (congela precios, neto, IGV, total) | **No** (no valoriza ni exige precios) |
| Guías de remisión | Sí (pendientes, botón «Guías») | **No** (los servicios ya salieron) |
| PDF | Detalle por servicio | Servicios incluidos + resumen por modelo y color |
| Etiqueta en el listado | — | `REGULARIZACIÓN` |
| Para qué sirve | Sustento del envío | Dar OSP de origen a lo ya enviado |

**Por qué sin importes:** los servicios de enero a octubre se pagaron (o se pagarán) con los
precios de su momento. Una orden emitida hoy con los precios vigentes (p. ej. T6 y T8 ya con +18 %
de IGV) mostraría importes que no corresponden. La valorización queda en la **liquidación** del taller.

## Requisitos antes de crear la orden de un taller

1. Ejecutar `docs/sql/ordenes-servicio-regularizacion.sql` (una sola vez, ya sirve para todos los talleres).
2. El taller debe tener **RUC y razón social** en Sectores (datos fiscales de la guía de remisión).
   Sin eso el sistema no deja emitir. *(Hoy solo T6 y T8 los tienen.)*
3. No hace falta que tenga precios de servicio cargados.

## Pasos (por cada taller)

1. Producción → Programación → **Órdenes de servicio** → **Nueva orden de servicio**.
2. Elegir el **taller**. Fechas: **desde 01/01/2026, hasta 31/10/2026**.
3. Marcar **Orden de regularización**.
4. **Previsualizar**: revisar servicios y unidades. Si aparece «ya pertenecen a otra orden», es normal
   (no se repiten servicios).
5. Observaciones: se completa sola («ORDEN DE REGULARIZACION: SERVICIOS ENVIADOS DEL … AL …»).
6. **Emitir orden e imprimir** y archivar el PDF.
7. Marcar el taller en la tabla de seguimiento de abajo.

> Tras emitirla, las liquidaciones que incluyan cierres de esos servicios muestran la OSP automáticamente.
> No hace falta tocar nada más. Los servicios posteriores al 31-10 **no** se incluyen: salen en órdenes normales.

## Si algo sale mal

- **Se creó con otro rango o taller:** en el listado usar **Anular** (conserva el correlativo) o **Eliminar**
  (libera los servicios y, si era la última, se reutiliza el número). Luego volver a emitir.
- **«Falta RUC / razón social»:** completar los datos fiscales del taller en Maestros → Sectores.
- **Servicios de 2025 o anteriores:** quedan como «SIN OSP» a propósito (solo se regulariza 2026).

## Seguimiento por taller

Servicios del 01-01-2026 al 31-10-2026 (cantidad al 07-11-2026, antes de emitir). Actualizar la columna **Estado** al emitir cada orden.

| Taller | Nombre | Servicios | Primer / último servicio | RUC cargado | Estado |
|---|---|---:|---|:---:|---|
| T6 | PABLO | 113 | 07-01 / 26-10 | Sí | ✅ **OSP-000001** · 07-11-2026 · 259,229 un. |
| T8 | MIGUEL | 93 | 07-01 / 29-10 | Sí | ✅ **OSP-000002** · 07-11-2026 · 173,060 un. |
| TA | ELVIRA | 158 | 07-01 / 31-10 | No | ⬜ En espera (cargar RUC) |
| TB | IREANA | 123 | 07-01 / 30-10 | No | ⬜ En espera (cargar RUC) |
| T4 | ADELA | 109 | 07-01 / 30-10 | No | ⬜ En espera (cargar RUC) |
| TE | BONDING | 61 | 07-01 / 28-10 | No | ⬜ En espera (cargar RUC) |
| T12 | LUIS | 58 | 02-02 / 29-10 | No | ⬜ En espera (cargar RUC) |
| TD | DISEÑO | 8 | 15-01 / 04-08 | No | ⬜ En espera (cargar RUC) |
| T11 | SEAMLESS BRASIER | 6 | 07-05 / 18-08 | No | ⬜ En espera (cargar RUC) |
| T7 | NELSON | 5 | 20-10 / 31-10 | No | ⬜ En espera (cargar RUC) |
| TC | OSCAR | 5 | 11-02 / 20-03 | No | ⬜ En espera (cargar RUC) |
| T2 | EDGAR | 5 | 15-06 / 07-10 | No | ⬜ En espera (cargar RUC) |
| T13 | EVARISTO | 5 | 23-04 / 09-06 | No | ⬜ En espera (cargar RUC) |

Leyenda: ✅ Emitida (anotar N° y fecha) · ⏳ Pendiente de emitir · ⬜ En espera.

## Notas sobre T6 (Pablo) y T8 (Miguel)

- **Emitidas el 07-11-2026** (OSP-000001 para T6 y OSP-000002 para T8), a nombre de Joel Medrano.
  Al liquidar sus cierres, las líneas ya muestran su OSP. Quedan líneas «SIN OSP» solo si el cierre
  no se puede rastrear hasta un servicio de 2026 (cierres antiguos o sin servicio de origen).

- Ya tienen el **+18 %** en `precio_serviciojf` y la marca **precios incluyen IGV** (respaldo en
  `precio_serviciojf_bk_20261107`).
- La mayoría de sus cierres de 2026 ya figuran **PAGADO** (342); solo quedan 6 **POR PAGAR**. Esos 6, al
  liquidarse, se valorizarán con los precios actuales (con IGV). Si corresponden a precios anteriores,
  liquidarlos **antes** de seguir con otros cambios de precio o decidir el criterio con contabilidad.
