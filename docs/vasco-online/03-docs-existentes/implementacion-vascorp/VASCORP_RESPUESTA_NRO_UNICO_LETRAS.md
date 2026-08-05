# vascorp → Vasco: respuesta número único en letras / CxC

**Fecha:** 2026-08-04  
**Responde a:** `vasco/docs/vascorp/por-hacer/VASCORP_IMPLEMENTAR_NRO_UNICO_LETRAS.md`  
**Estado:** implementado en exportador (2026-08-04). Contrato: `vasco/postman/VASCORP_SYNC_ACCOUNT.md`.

Sync actual: `modelos/vasco-sync.modelo.php` · `controladores/vasco-sync.controlador.php`  
UI local: grilla CxC / detalle cuenta (`Nro unico` ← `cuenta_ctejf.num_unico`)

---

## Checklist (§ handoff)

- [x] Confirmar si “número único” ≠ `num_cta`
- [x] Indicar tabla/columna y ejemplo real de 1–2 letras
- [x] Decidir: `unique_number` de negocio y / o `external_id` del cargo
- [x] Incluir el campo en el export del job de estados de cuenta
- [x] Avisar a Vasco para ampliar contrato + migración

---

## Confirmaciones

| # | Pregunta | Respuesta vascorp |
|---|----------|-------------------|
| 1 | Nombre exacto tabla/columna del “Nro unico” en UI | **`cuenta_ctejf.num_unico`** (`varchar(12)`). Label en pantalla: **“Nro unico”**. Distinto de `num_cta`. |
| 2 | ¿Es estable entre syncs? | **No del todo.** Se edita a mano, se importa masivo (“Actualizar número único”) y a menudo empieza vacío hasta que el banco lo asigna. También se usa el literal **`Cartera`** como marca de estado (no como ID). |
| 3 | ¿Único global o por cliente? | **Intención de negocio:** referencia bancaria (BCP) para localizar la letra. **En DB:** sin constraint `UNIQUE`; puede estar vacío, repetirse (`Cartera`) o cambiar. **No usarlo solo como llave técnica.** |
| 4 | ¿Solo letras `85` o todos los docs? | Columna existe en toda `cuenta_ctejf`, pero el **uso real es en letras (`tipo_doc = 85`)**. En facturas/otros suele ir vacío. Las notificaciones WA internas de vascorp ya muestran `num_unico` en vencimiento de letra. |

### Tabla de conceptos (actualizada)

| Concepto | Campo ERP | ¿Ya se synca? |
|----------|-----------|---------------|
| Número de documento / cuenta | `num_cta` → API `doc_number` | Sí |
| ID interno fila CxC (cargo) | `cuenta_ctejf.id` → API `external_id` | Sí (doc padre + abonos) |
| Número único de letra | `num_unico` → API `unique_number` | Sí (si no vacío) |

### Ejemplos (letras `85`)

Mismos docs del sample de abonos (cliente RUC `20612761486`):

| `num_cta` (`doc_number`) | `num_unico` (típico) | Notas |
|--------------------------|----------------------|-------|
| `FR0101847-1` | vacío / `Cartera` / código BCP según estado | Letra pendiente sin abonos en sample |
| `F00117544-9` | puede tener código bancario | En un abono asociado apareció `num_unico = 73177392` (referencia en movimiento `-`; el cargo `+` es la fuente a exportar) |

Formato esperado de `num_unico` cuando el banco lo asignó: string corto numérico (hasta 12 chars). Valores especiales frecuentes: vacío, `Cartera`.

---

## Decisión de mapeo

**Enviar ambos** en cada ítem de `pending_documents[]`:

| Campo API | Origen ERP | Regla |
|-----------|------------|-------|
| `unique_number` | `cuenta_ctejf.num_unico` del cargo (`tip_mov = '+'`) | string; **opcional** (puede venir vacío o `Cartera`). Uso: mensaje WA al cliente. |
| `external_id` | `cuenta_ctejf.id` del cargo (`tip_mov = '+'`) | string no vacío; **estable**. Uso: cooldown / contador / correlación de avisos. Mismo concepto de ID que ya usan abonos. |

### JSON propuesto

```json
{
  "doc_type": "85",
  "doc_number": "F00117544-9",
  "unique_number": "73177392",
  "external_id": "1234567",
  "issue_date": "2025-11-25",
  "due_date": "2026-02-24",
  "amount": 2593.49,
  "balance": 2319.10
}
```

Si `num_unico` está vacío:

```json
{
  "doc_type": "85",
  "doc_number": "FR0101847-1",
  "external_id": "1234568",
  "issue_date": "2025-12-17",
  "due_date": "2026-01-31",
  "amount": 751.20,
  "balance": 751.20
}
```

(`unique_number` omitido o `""` — a criterio del contrato Vasco.)

### Si Vasco solo pudiera elegir uno

| Necesidad | Campo |
|-----------|-------|
| Anti-spam / historial de avisos | **`external_id`** |
| Texto al cliente en WhatsApp | **`unique_number`** (sabiendo que a veces no hay) |

---

## Qué no cambia

- Llave del cliente: `doc_type` + `doc_number` (SUNAT).
- Llave comercial del doc: `tipo_doc` + `num_cta` → `doc_type` + `doc_number` del pending.
- WhatsApp, favoritos y cooldown siguen 100 % en Vasco.

---

## Implementación vascorp

| Pieza | Cambio |
|-------|--------|
| `ModeloVascoSync::mdlCuentasDocsPendientesPorDocKey(s)` | SELECT `MIN(cc.id)`, `MAX(NULLIF(TRIM(cc.num_unico),''))` |
| `ControladorVascoSync::mapearDocumentoPendienteParaApi` | Emite `external_id` siempre que haya `id`; `unique_number` solo si `num_unico` no vacío |

---

## Siguiente paso

1. ~~Vasco acepta contrato~~ (hecho en `VASCORP_SYNC_ACCOUNT.md`).
2. ~~vascorp amplía el exportador~~ (hecho).
3. Vasco migra persistencia / UI avisos letras + prueba con 1–2 letras reales tras re-sync.
