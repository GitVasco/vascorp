# vascorp → Vasco: respuesta marca en documentos de cuenta (`brand_code`)

**Fecha:** 2026-08-05  
**Responde a:** `vasco/docs/vascorp/por-hacer/VASCORP_IMPLEMENTAR_MARCA_CUENTAS.md`  
**Estado:** implementado en exportador (2026-08-05). Receptor Vasco publicado. Serie: 4 chars (proformas `09` = 3). Default `JF`.

Sync: `modelos/vasco-sync.modelo.php` · `controladores/vasco-sync.controlador.php`  
Amarre serie↔marca: `serie_documento_marcajf` + `talonariosjf` (UI Series de documentos)  
Grupos comerciales: `X` = Jackyform → `JF` · `Y` = RosaFlor → `RF`

---

## Qué decirle a Vasco (mensaje corto)

> Confirmamos `brand_code` por documento en `pending_documents[]` con valores `JF` | `RF`.
>
> **Fuente vascorp:** serie fiscal del documento (`serie_documento_marcajf` → grupo comercial X/Y). En letras (`85`) resolvemos subiendo por `doc_origen` hasta el documento fiscal origen (cubre renovaciones).
>
> **Fallback:** si no se puede clasificar → enviamos `JF` (no omitimos el campo).
>
> No mandaremos `jackyform_total` / `rosaflor_total`; Vasco sigue calculando subtotales desde `balance`.
>
> Cuando tengan el receptor listo, avisamos y desplegamos el campo en `account-statements-bulk`. Hasta entonces no lo enviamos a prod.

---

## Checklist

- [x] Confirmar nivel: marca por documento, no por cliente
- [x] Confirmar fuente: serie fiscal + cadena `doc_origen` en letras
- [x] Confirmar fallback: `JF` si no hay serie/clasificación
- [x] Auditar amarre serie↔marcas (negocio: ya amarrado)
- [x] Vasco confirma receptor `brand_code` desplegado
- [x] Ampliar SELECT sync (`doc_origen`)
- [x] Implementar `mapBrandCode()` + emitir en `mapearDocumentoPendienteParaApi`
- [ ] Probar cliente solo JF, solo RF y mixto
- [ ] Corrida `finalize: true` tras despliegue

---

## Confirmaciones

| # | Pregunta | Respuesta vascorp |
|---|----------|-------------------|
| 1 | ¿Marca en cliente o documento? | **Documento** (`pending_documents[]`). |
| 2 | Valores | Exactamente `JF` o `RF` (mayúsculas). |
| 3 | Fuente formal | Serie del cargo fiscal vía `talonariosjf` + `serie_documento_marcajf` → marcas → grupo `X`/`Y` → `JF`/`RF`. |
| 4 | ¿Letras `85`? | Seguir `doc_origen` hasta doc fiscal clasificable por serie. Renovación: cadena letra→letra→…→factura/boleta. |
| 5 | ¿Sin serie / cadena rota / serie mixta? | **Fallback `JF`**. Registrar en log/reporte para limpieza, pero **sí enviar** el campo. |
| 6 | ¿Subtotales por marca en account? | **No.** Vasco suma `balance` por `brand_code`. |
| 7 | ¿Abonos (`payments[]`)? | Sin marca; heredan del documento. |

### Mapeo grupo → código API

| Grupo comercial | Código | `brand_code` |
|-----------------|--------|--------------|
| `X` Grupo Jackyform (JACKYFORM, GUAPITAS) | X | `JF` |
| `Y` RosaFlor (ROSALINDA, ROSITAS) | Y | `RF` |

**Prerrequisito:** cada serie fiscal debe quedar amarrada a marcas de **un solo** grupo. Si una serie tiene marcas de X y Y, tratarla como no clasificable → fallback `JF` y corregir en UI Series.

---

## Cómo resolver `brand_code` (para no olvidar)

### 1. Documentos fiscales (`01`, `03`, `07`, `08`, `09`, …)

1. Tomar `num_cta` del cargo `tip_mov = '+'`.
2. Extraer **serie**: **3** chars si `tipo_doc = 09` (proforma); **4** chars en el resto (`F001`, `FR01`, `B001`…). Si hay guion, usar la parte izquierda.
3. Resolver vía `mdlGruposPorSerieDocumento()` / `brandCodePorTipoSerie()`.
4. Si unívoco grupo `X`/`Y` → `JF`/`RF`. Si vacío o mixto → **`JF`**.

### 2. Letras (`85`) y renovaciones

```
letra pendiente
  └─ doc_origen → ¿es fiscal con serie? → mapear (paso 1)
                 └─ ¿es otra letra 85? → repetir (límite profundidad, ej. 10)
                 └─ sin origen / no clasifica → JF
```

Notas de negocio ya vistas en código:

- Al **pasar a letras** se elimina el cargo fiscal; la pista queda en `doc_origen` (string del nro. factura/boleta).
- Al **renovar/dividir** (`ctrDividirLetra`): nuevo cargo `renovacion = 1`, `doc_origen` = letra padre (no la factura). Por eso hay que caminar la cadena.
- **Refinanciamiento** (`cod_pago = RF` en abonos) **no** define marca del pending.

### 3. Export sync (hecho)

- `mdlCuentasDocsPendientesPorDocKey(s)` trae `doc_origen`.
- `ctrSincronizarLoteCuentas` → `prepararBrandCodeParaLote()` (cache cadena letras).
- `mapearDocumentoPendienteParaApi` emite siempre `brand_code` (`mapBrandCode()`).

### 4. Validación previa

- Conteo de pendientes por `brand_code`.
- Listado de los que cayeron en **fallback JF** (para corregir series / `doc_origen`).
- Muestra: cliente solo JF, solo RF, mixto.

---

## Ejemplo (cuenta mixta)

```json
{
  "doc_type": "6",
  "doc_number": "20100070970",
  "deuda_total": 12519.60,
  "vencido_total": 2319.10,
  "pending_documents": [
    {
      "doc_type": "09",
      "doc_number": "0030019723",
      "external_id": "1002001",
      "brand_code": "JF",
      "amount": 10200.50,
      "balance": 10200.50
    },
    {
      "doc_type": "85",
      "doc_number": "F00117544-9",
      "external_id": "1002002",
      "brand_code": "RF",
      "amount": 2593.49,
      "balance": 2319.10
    }
  ]
}
```

Letra sin cadena resoluble:

```json
{
  "doc_type": "85",
  "doc_number": "XXXX12345-1",
  "external_id": "1002099",
  "brand_code": "JF",
  "amount": 500.00,
  "balance": 500.00
}
```

---

## Secuencia

1. Completar/auditar amarre series → un grupo por serie.
2. Vasco despliega receptor `brand_code`.
3. Vascorp implementa resolución + fallback `JF` en el job de cuentas.
4. Sync completo con `finalize: true`.
5. Conciliar totales JF+RF vs ERP en muestra acordada.

---

## Fuera de alcance

- No marcar a nivel `accounts[]` ni maestro de clientes.
- No persistir aún columna de marca en `cuenta_ctejf` (opcional fase 2: heredar al renovar).
- No usar vendedor hardcodeado ni prefijo “a ojo” como fuente primaria (solo serie + cadena; fallback JF).
