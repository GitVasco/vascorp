# vascorp → Vasco: respuesta flag protestada en letras / CxC

**Fecha:** 2026-08-04  
**Responde a:** `vasco/docs/vascorp/por-hacer/VASCORP_IMPLEMENTAR_LETRAS_PROTESTADAS.md`  
**Estado:** implementado en exportador (2026-08-04). Contrato: `vasco/postman/VASCORP_SYNC_ACCOUNT.md` · migración Vasco `#0064`.

Sync actual: `modelos/vasco-sync.modelo.php` · `controladores/vasco-sync.controlador.php`  
UI local: grilla CxC pendientes — columna protesta (`SI` si `protesta = 1`)

---

## Checklist (§ handoff)

- [x] Confirmar columna `protesta` y valores
- [x] Incluir `protested` en `mapearDocumentoPendienteParaApi` (+ SELECT)
- [ ] Probar 1 letra protestada + 1 no protestada en un batch
- [x] Avisar a Vasco para ampliar receptor + filtro *(este doc)*

---

## Confirmaciones

| # | Pregunta | Respuesta vascorp |
|---|----------|-------------------|
| 1 | Columna / tabla del flag | **`cuenta_ctejf.protesta`** (`tinyint`), del cargo `tip_mov = '+'`. |
| 2 | Valores | **Protestada = `1`** (también llega como `"1"` en UI). No protestada = `0` o `NULL`. Checkbox al editar: `value="1"`. |
| 3 | ¿Solo letras `85`? | Uso real / operativo en **letras (`tipo_doc = 85`)**. La columna existe en todos los cargos; en otros tipos suele ir `0`/`NULL`. |
| 4 | ¿Protestada sigue con saldo y PENDIENTE? | **Sí.** Puede seguir `estado = PENDIENTE` y `saldo > 0`. Aparece en la grilla de pendientes con badge “SI”. **Debe seguir en el sync de CxC** (deuda real) con el flag. |

### Alternativa “excluir del export”

**No.** Coincide con lo preferido del handoff: no sacar protestadas del snapshot. El vendedor / estado de cuenta deben ver la deuda; Vasco filtra solo avisos WA / “Letras por vencer”.

---

## Decisión de mapeo

| Campo API | Origen ERP | Regla |
|-----------|------------|-------|
| `protested` | `cuenta_ctejf.protesta` del cargo `+` | boolean; `true` si `protesta = 1` (o `"1"`). Si `0` / `NULL` / omitido → `false` (compat). |

### JSON propuesto

Protestada:

```json
{
  "doc_type": "85",
  "doc_number": "F00117544-9",
  "unique_number": "73177392",
  "external_id": "1234567",
  "protested": true,
  "issue_date": "2025-11-25",
  "due_date": "2026-02-24",
  "amount": 2593.49,
  "balance": 2319.10
}
```

No protestada: `"protested": false` u omitir el campo.

---

## Qué no cambia

- Llave cliente SUNAT; llave doc `doc_type` + `doc_number`.
- `unique_number` / `external_id` (ya en exportador vascorp).
- WhatsApp / favoritos / cooldown / filtro de avisos: 100 % Vasco.

---

## Implementación vascorp

| Pieza | Cambio |
|-------|--------|
| `ModeloVascoSync::mdlCuentasDocsPendientesPorDocKey(s)` | SELECT `MAX(IFNULL(cc.protesta, 0)) AS protesta` |
| `ControladorVascoSync::mapearDocumentoPendienteParaApi` | Emite `protested: true` si `protesta = 1` / `"1"`; si no, `false` |

---

## Siguiente paso

1. ~~Vasco amplía contrato + migración `#0064` + filtro avisos~~ (hecho).
2. ~~vascorp amplía el exportador~~ (hecho).
3. Re-sync cuentas (confirmar `#0064` aplicada en el entorno) → prueba 1 protestada + 1 no protestada: solo la no protestada en lista de avisos WA.
