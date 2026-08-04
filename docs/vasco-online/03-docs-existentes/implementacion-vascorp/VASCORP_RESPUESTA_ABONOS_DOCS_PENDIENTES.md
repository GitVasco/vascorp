# vascorp → Vasco: respuesta abonos de docs pendientes

**Fecha:** 2026-08-04  
**Responde a:** `vasco/docs/vascorp/por-hacer/VASCORP_IMPLEMENTAR_ABONOS_DOCS_PENDIENTES.md`  
**Estado:** datos + mapeo listos. **Sin cambio de código de sync** hasta que Vasco acepte el contrato `payments[]`.

Sync actual (solo cargos): `modelos/vasco-sync.modelo.php` · `controladores/vasco-sync.controlador.php`  
Abonos ya listados en UI local: `ModeloEstadoCuenta::mdlCancelaciones`

---

## Checklist (§4 del handoff)

- [x] Completar tabla §2.1 (origen + vínculo doc↔abono)
- [x] Completar estimaciones §2.2
- [x] Adjuntar sample crudo §2.3 (mín. 1 cliente con abonos)
- [x] Proponer mapeo columnas → campos §3.2
- [x] Confirmar catálogo `cod_pago` → `method_label`
- [x] Confirmar si `external_id` del movimiento existe y es estable
- [x] Confirmar regla de consistencia `amount - balance` vs suma de abonos (con caveat regularizaciones)

---

## 2.1 Origen en el ERP

| Pregunta | Respuesta vascorp |
|----------|-------------------|
| ¿En qué tabla(s) / vista viven los movimientos de abono/cancelación parcial? | Misma tabla que los cargos: **`cuenta_ctejf`**. Cargos: `tip_mov = '+'`. Abonos: `tip_mov = '-'`. Catálogo de medio: **`tipo_pagosjf`** (`codigo` → `descripcion`). **`abonosjf` no es fuente** (import bancario / maestro aparte). |
| ¿Cómo se vincula un abono al documento abierto (`tipo_doc` + `num_cta` del cliente)? | El abono copia **`tipo_doc` + `num_cta`** del cargo. Join: `a.tip_mov='-' AND a.tipo_doc = cargo.tipo_doc AND a.num_cta = cargo.num_cta` (igual que `mdlCancelaciones`). El cliente del cargo es `cuenta_ctejf.cliente` → `clientesjf.codigo`. |
| ¿Los `cod_pago` (80, 10, …) son exactamente esos movimientos? ¿Hay catálogo? | Sí. En filas `tip_mov='-'`, `cod_pago` es el medio/tipo de cancelación. Catálogo en `tipo_pagosjf` (lista abajo). |
| ¿Un abono puede afectar varios documentos a la vez? | **No** a nivel fila. Cada cancelación inserta un `tip_mov='-'` ligado a un solo `tipo_doc`+`num_cta` y baja el `saldo` de ese cargo. |
| ¿Hay medio de pago legible? Columna | `tipo_pagosjf.descripcion` vía `cuenta_ctejf.cod_pago = tipo_pagosjf.codigo` → `method_label`. |
| ¿Hay nro. de referencia / voucher / banco / nota? | Sí: `notas` (texto libre / OP), `doc_origen` (doc asociado, p.ej. NC), `banco`, `num_unico`. Para UI: prioridad `notas` → `doc_origen` → `banco`. |

### Columnas crudas del abono

| Columna | Uso |
|--------|-----|
| `id` | `external_id` (PK AUTO_INCREMENT, estable) |
| `fecha` | `payment_date` |
| `monto` | `amount` |
| `cod_pago` | `payment_code` |
| `notas` / `doc_origen` / `banco` | `reference` |
| `tipo_doc` + `num_cta` | vínculo al `pending_documents[]` |
| `cliente` | código ERP (no va al JSON de abono) |

---

## 2.2 Volumen (query real 2026-08-04)

Filtro = cartera del sync actual: `tip_mov='+'`, `estado=PENDIENTE`, `saldo>0`, cliente con documento SUNAT válido.

| Métrica | Valor |
|---------|-------|
| Clientes con deuda (hoy) | **825** |
| Docs pendientes totales | **2 795** |
| Docs con `monto > saldo` (hubo abonos) | **423** |
| Abonos promedio por doc con `amount > balance` | **3.29** |
| Máx. abonos en un solo doc abierto | **49** |
| Percentil ~95 de abonos/doc (entre docs con parcial) | **11** |
| ¿Docs con cientos de micro-abonos? | **No.** 0 docs ≥100; 0 docs ≥200 |
| Docs con ≥2 abonos | **226** |

**Límites tentativos del handoff (200/doc, 2000/cliente):** holgados frente a la realidad. El peor caso visto es 49 abonos/doc.

Top densidades (referencia): `FR0100001697` (49), `F00100018180` (26), `F00100007628` (20).

---

## 2.3 Sample real

Cliente: **TIENDAS BOM BOM E.I.R.L.** — RUC `20612761486` (`doc_type=6`)

### Doc pendiente **sin** abonos (`amount == balance`)

```text
tipo_doc=85  num_cta=FR0101847-1
fecha=2025-12-17  fecha_ven=2026-01-31
monto=751.20  saldo=751.20
abonos: (ninguno)
```

### Doc pendiente **con** 2 abonos (`amount > balance`)

```text
tipo_doc=85  num_cta=F00117544-9
fecha=2025-11-25  fecha_ven=2026-02-24
monto=2593.49  saldo=2319.10
amount - balance = 274.39
sum(abonos)     = 274.39   ← cuadra exacto
```

Abonos ERP (crudo):

| id | tip_mov | tipo_doc | num_cta | fecha | monto | cod_pago | descripcion (tipo_pagosjf) | notas | doc_origen | banco | num_unico | saldo |
|----|---------|----------|---------|-------|-------|----------|----------------------------|-------|------------|-------|-----------|-------|
| 1144538 | - | 85 | F00117544-9 | 2026-01-15 | 129.67 | 97 | NOTA DE CREDITO PRONTO PAGO | DSCTO 5% | F00200005191 | | 73177392 | 0.00 |
| 1170101 | - | 85 | F00117544-9 | 2026-06-10 | 144.72 | 05 | DEP. CTACTE | OP-05299764 | F00117544-9 | | | 0.00 |

### JSON propuesto (`payments[]` dentro del pending doc)

```json
{
  "doc_type": "85",
  "doc_number": "F00117544-9",
  "issue_date": "2025-11-25",
  "due_date": "2026-02-24",
  "amount": 2593.49,
  "balance": 2319.10,
  "payments": [
    {
      "payment_date": "2026-01-15",
      "amount": 129.67,
      "payment_code": "97",
      "method_label": "NOTA DE CREDITO PRONTO PAGO",
      "reference": "DSCTO 5%",
      "external_id": "1144538"
    },
    {
      "payment_date": "2026-06-10",
      "amount": 144.72,
      "payment_code": "05",
      "method_label": "DEP. CTACTE",
      "reference": "OP-05299764",
      "external_id": "1170101"
    }
  ]
}
```

---

## 3.2 Mapeo columnas → campos API

| Campo API | Obligatorio | Columna ERP | Notas |
|-----------|-------------|-------------|-------|
| `payment_date` | Sí | `cuenta_ctejf.fecha` | Normalizar a `YYYY-MM-DD` (varchar hoy) |
| `amount` | Sí | `cuenta_ctejf.monto` | > 0; soles |
| `payment_code` | Recomendado | `cuenta_ctejf.cod_pago` | |
| `method_label` | Recomendado | `tipo_pagosjf.descripcion` | `TRIM`; no inventar aliases |
| `reference` | No | `notas` → si vacío `doc_origen` → si vacío `banco` | Corto para UI vendedor |
| `external_id` | Recomendado | `cuenta_ctejf.id` | Estable, único, idempotente |

Query base (solo docs que ya van en `pending_documents`):

```sql
SELECT c.id, c.fecha, c.monto, c.cod_pago, tp.descripcion,
       c.notas, c.doc_origen, c.banco
FROM cuenta_ctejf c
LEFT JOIN tipo_pagosjf tp ON tp.codigo = c.cod_pago
WHERE c.tip_mov = '-'
  AND TRIM(c.tipo_doc) = TRIM(?)
  AND TRIM(c.num_cta) = TRIM(?)
ORDER BY c.fecha ASC, c.id ASC
```

---

## Catálogo `cod_pago` → `method_label` (prod 2026-08-04)

Fuente: `tipo_pagosjf`.

| codigo | descripcion |
|--------|-------------|
| 00 | LETRAS BCP |
| 01 | FACTURA |
| 02 | RECIBOS POR HONORARIOS |
| 03 | BOLETAS DE VENTAS |
| 04 | CONTROL INTERNO-SALIDA ALMACEN |
| 05 | DEP. CTACTE |
| 06 | POS-BCP |
| 07 | NOTA DE CREDITO |
| 08 | NOTA DE DEBITO |
| 09 | PROFORMAS |
| 10 | DESCUENTO ADICIONAL PROFORMAS |
| 11 | PROMOCION VENTA DE BRASIERES |
| 12 | TICKET O CINTA EMITIDO POR MA |
| 13 | DEVOL. PROFORMAS |
| 14 | DEP. CULQI |
| 70 | ENTREGA DE EFECTIVO |
| 71 | PAGOS Y GASTOS |
| 72 | CAJA CHICA DIA ANTERIOR |
| 80 | EFECTIVO |
| 81 | TARJETA DE CREDITO |
| 82 | ABONO EN CTA. S/. |
| 83 | ABONO EN CTA. US$ |
| 84 | CHEQUE |
| 85 | LETRAS |
| 95 | MUESTRAS DE VITRINAS / PANELES |
| 96 | NOTA DE CREDITO DEVOLUCION |
| 97 | NOTA DE CREDITO PRONTO PAGO |
| 98 | AJUSTE DE CUENTAS NO EFECTIVO |
| 99 | PERDIDA POR ROBO |
| RF | REFINANCIACION |
| TR | TELECREDITO |

**Frecuentes en cobranza (para UI):** `80`, `82`, `05`, `06`, `14`, `10`, `96`, `97`, `00`, `TR`, `84`.  
**Nota:** código `15` (YAPE) aparece en algunos reportes internos pero **no está** en `tipo_pagosjf` de prod hoy; si llega un `cod_pago` sin match, enviar `method_label` = el propio código o vacío.

---

## Consistencia `sum(payments) ≈ amount - balance`

- Sobre **saldo oficial** ERP: en el sample cuadra exacto (`274.39 = 274.39`).
- El sync de vascorp puede proyectar **`balance` comercial** vía `AdaptadorSaldoComercialVasco` (regularizaciones que bajan el saldo **sin** crear filas `tip_mov='-'`).
- **Propuesta a Vasco:**
  1. Validar `sum(payments) ≈ amount - saldo_oficial` (±0.05), **o**
  2. Si el `balance` enviado ya es comercial: **no exigir** la regla cuando exista regularización aplicable en ese doc (la diferencia esperada = monto regularizado).
  3. No inventar abonos fantasma para “cuadrar” el saldo comercial.

NC / descuentos / ajustes (`97`, `96`, `10`, `98`, …) **sí** son filas `-` y van en `payments[]` con su `method_label`.

---

## Confirmaciones extra

| Tema | Confirmación |
|------|----------------|
| `external_id` estable | Sí: `cuenta_ctejf.id` (INT PK). |
| Orden envío | `payment_date ASC`, `id ASC`. |
| Snapshot | Al re-sincronizar, reemplazar abonos de esos docs (misma semántica que pendientes). |
| Docs cancelados / saldo 0 | Fuera de alcance; no enviar. |
| Compatibilidad | Hasta que Vasco publique el campo, seguir sync actual **sin** `payments`. |

---

## Siguiente paso

1. Vasco ajusta contrato / receptor `payments[]` si acepta este mapeo + caveat regularizaciones.
2. vascorp amplía el exportador de estados de cuenta para incluir `payments[]`.
3. Prueba 1 cliente (p.ej. RUC 20612761486) → corrida completa → UI visita.
