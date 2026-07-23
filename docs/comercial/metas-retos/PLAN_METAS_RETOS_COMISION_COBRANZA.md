# Plan: comisión de metas/retos por cobranza

**Estado:** implementado en código (pendiente ejecutar SQL en BD y checklist de despliegue).  
**Módulo:** Gestión comercial → Metas / retos por vendedor (`metas-retos`).  
**Decisión de Gerencia General:** por ahora, la comisión general del módulo se calcula por **cobranza efectiva**, no por ventas. La estructura y medición de ventas se conserva para una futura reactivación, pero no debe generar comisión ni formar parte del total estimado actual. Los incentivos específicos por modelo, color o artículo **sí continúan** calculando comisión por venta.

## 1. Alcance y resultado esperado

Cada vendedor tendrá una meta mensual de cobranza y una regla de comisión asociada. El panel mostrará meta, cobranza real neta sin IGV, porcentaje de avance y aporte estimado. El total estimado a pagar sumará ese aporte junto con las comisiones específicas de producto que siguen vigentes.

Ejemplo:

```text
Meta de cobranza:     S/ 50,000
Cobranza efectiva:    S/ 46,000
Regla:                prorrata, 1.5 %
Comisión estimada:    S/ 690.00
```

La venta seguirá pudiendo verse como indicador operativo, y sus campos se conservarán en la base de datos. Sin embargo, su aporte de comisión será `S/ 0.00` mientras esta política esté activa.

## 2. Fuente de datos de cobranza

La referencia de monto es la columna **Cobranza** de la tabla **Resumen de gestión** de `/inicio-gerencia`, cuya consulta actual vive en `ModeloMovimientos::mdlMostrarRangosGerencia()`. Su lista antigua debe normalizarse según la lista comercial definitiva aprobada.

- Tabla: `cuenta_ctejf`.
- Período: `cc.fecha >= inicio_mes` y `cc.fecha < inicio_mes_siguiente`.
- Vendedor: `TRIM(cc.vendedor)`.
- Cobranza: `cc.tip_mov = '-'`.
- Códigos que hoy usa Resumen de gestión: `00`, `05`, `06`, `14`, `80`, `82`, `TR`.
- Códigos adicionales que sí reconocen `dashboard-cobranzas` y `metas-vendedor`: `15`, `16`, `17`, `18`.
- Lista comercial definitiva: `00`, `TR`, `05`, `06`, `14`, `15`, `16`, `17`, `18`, `80`, `82`.
- El importe de la tabla de gerencia está con IGV. Para metas y comisión se usará su valor neto sin IGV: `cc.monto / 1.18`.
- No sumar otros códigos, devoluciones, descuentos ni otros medios que no figuran en el Resumen de gestión.

La nueva función debe centralizar esta regla, por ejemplo `ModeloMetricasComerciales::mdlCobranzaNetaGerenciaPorVendedor()`. No se deben mantener dos versiones diferentes de la clasificación de códigos en `inicio-gerencia`, `metas-vendedor` y `metas-retos`. En la misma entrega, Cursor debe actualizar `/inicio-gerencia` para que use exactamente la lista definitiva y no siga mostrando un total distinto.

La fórmula debe redondear al final de la suma, no cada movimiento:

```sql
ROUND(SUM(IFNULL(cc.monto, 0) / 1.18), 2) AS cobranza_neta
```

El factor `1.18` debe quedar en una constante con nombre explícito (`IGV_FACTOR = 1.18`) y documentado. No usar `monto * 0.82`, pues no elimina correctamente un IGV incluido.

La cobranza no se asigna por grupo de marcas ni por producto. La comisión corresponde al vendedor registrado en `cuenta_ctejf` y al período de cobro.

## 3. Modelo de datos propuesto

Ampliar `metas_retos_vendedorjf`, que ya tiene una fila única por vendedor/año/mes:

```sql
ALTER TABLE metas_retos_vendedorjf
    ADD COLUMN meta_cobranza DECIMAL(14,2) NULL COMMENT 'Meta mensual de cobranza efectiva',
    ADD COLUMN comision_cobranza_pct DECIMAL(8,2) NULL COMMENT '% sobre cobranza efectiva',
    ADD COLUMN comision_cobranza_fijo DECIMAL(14,2) NULL COMMENT 'Monto fijo al cumplir meta',
    ADD COLUMN cumplimiento_cobranza VARCHAR(20) NOT NULL DEFAULT 'todo_nada'
        COMMENT 'todo_nada | prorrata';
```

Reglas:

| Campo | Regla |
|---|---|
| `meta_cobranza` | Monto objetivo mensual, mayor o igual a cero. |
| `cumplimiento_cobranza = prorrata` | La comisión es el porcentaje de toda la cobranza neta efectiva, incluso si supera la meta. |
| `cumplimiento_cobranza = todo_nada` | Al llegar a la meta, se paga `comision_cobranza_fijo`; antes de ello, cero. |
| comisión porcentual | Usar `comision_cobranza_pct`; no combinarla con fijo en la misma regla. |
| comisión fija | Usar `comision_cobranza_fijo`; no combinarla con porcentaje en la misma regla. |

No eliminar ni renombrar estos campos ya existentes: `meta_monto`, `comision_monto_pct`, `comision_monto_fijo` y `cumplimiento_monto`. Permanecen para una futura reactivación de comisión por ventas.

### Fuente de verdad y compatibilidad

`metas_retos_vendedorjf` será la fuente de verdad de la nueva meta y comisión de cobranza, pues allí viven todas las reglas de comisión. La tabla histórica `metas_vendedorjf` ya tiene `meta_cobranza`, pero no posee reglas de comisión.

Para no mostrar metas distintas en las dos pantallas, al guardar en `metas-retos` se recomienda sincronizar únicamente `meta_cobranza` hacia la fila equivalente de `metas_vendedorjf`, con un método paralelo al actual `mdlSyncMetaVentaLegacy()`. La configuración de comisión queda solamente en `metas_retos_vendedorjf`.

Antes de activar esto, revisar si `metas-vendedor` ya contiene metas de cobranza del mes actual y decidir cuál será el valor inicial. La migración nunca debe sobrescribir una meta existente sin registro de la decisión.

## 4. Cálculo de comisión

Crear una función común de cálculo, reutilizando `ctrFactorCumplimiento()`:

```text
si modo = prorrata:
  comisión = cobranza_neta × (comision_cobranza_pct / 100)

si modo = todo_nada:
  comisión = comision_cobranza_fijo solo si cobranza_neta >= meta_cobranza
```

En prorrata, el porcentaje se paga sobre toda la cobranza neta lograda, incluso si supera la meta. La meta determina desempeño, pero no limita una cobranza adicional real.

El total estimado del período debe componerse así durante esta política:

```text
total_estimado = comisión_cobranza_neta
                + comisión_clientes (si está configurada)
                + comisión_modelos_generales (si está configurada)
                + incentivos_por_producto_por_venta

comisión_venta_general = 0.00
```

La estructura de venta se conserva y su avance se puede mostrar como referencia, pero el método `ctrCalcularComisionEstimada()` debe omitir el aporte `monto` mientras la política esté activa. Implementar esto como una configuración clara (por ejemplo `comision_ventas_habilitada = false` en configuración comercial), no comentando código ni eliminando columnas. Al cambiar la política en el futuro, se habilita el flag y se vuelve a usar la lógica existente.

## 5. Interfaz propuesta

En el modal de **Metas / retos por vendedor**:

1. Agregar el bloque prioritario **“1) Cobranza efectiva”** con:
   - Meta S/;
   - modalidad: Prorrata / Todo o nada;
   - porcentaje o comisión fija según modalidad;
   - ayuda que indique “misma fuente que Resumen de gestión, sin IGV” y enumere los códigos incluidos.
2. Renombrar el bloque actual de ventas a **“Ventas (referencia / futura comisión)”**.
3. Mostrar sus campos en modo lectura o esconderlos de la edición mientras `comision_ventas_habilitada = false`; conservar el avance de ventas como dato informativo.
4. En la tabla principal, colocar la columna **Cobranza S/ (meta/real)** antes de ventas y mostrar barra de avance y aporte estimado.
5. En el total y tooltip de pago, desglosar claramente: Cobranza, Clientes, Modelos, Incentivos de producto y Ventas (desactivada: S/ 0.00).
6. Etiquetar el estimado como “sujeto a validación/liquidación” si existe proceso posterior de auditoría.

## 6. Cambios técnicos para Cursor

### Base y modelos

- [x] Crear `docs/sql/metas-retos-comision-cobranza.sql` con columnas, migración de datos y consultas de verificación idempotentes.
- [x] Crear una configuración comercial explícita para activar/desactivar comisión de ventas. Valor inicial: desactivada.
- [x] Confirmar y centralizar la clasificación de cobranza: lista recomendada `00`, `TR`, `05`, `06`, `14`, `15`, `16`, `17`, `18`, `80`, `82`.
- [x] Actualizar la subconsulta de cobranza de `ModeloMovimientos::mdlMostrarRangosGerencia()` para consumir la misma regla centralizada; eliminar su lista reducida de siete códigos.
- [x] Implementar `mdlCobranzaNetaGerenciaPorVendedor($anio, $mes)` con consultas preparadas, rangos semiabiertos y `SUM(monto / 1.18)`.
- [x] Incluir `cobranza_neta_real` en `ModeloMetasRetos::mdlListarAvancePeriodo()`.
- [x] Persistir campos de cobranza al crear/editar el reto y validar valores/método de cumplimiento.
- [x] Sincronizar solo `meta_cobranza` con `metas_vendedorjf`, previa regla de migración aprobada.

### Controlador y cálculo

- [x] Aceptar y normalizar `meta_cobranza`, porcentaje, fijo y modo en `ControladorMetasRetos`.
- [x] Añadir `cobranza` al detalle devuelto por `ctrCalcularComisionEstimada()`.
- [x] Retornar cero para `monto` cuando la comisión de ventas esté desactivada, sin borrar su cálculo ni sus datos.
- [x] Asegurar que no se pague simultáneamente porcentaje y monto fijo en la misma modalidad.
- [x] Redondear solo el aporte final de cada regla a dos decimales.

### Pantallas y AJAX

- [x] Agregar campos de cobranza al modal y lógica JS para alternar porcentaje/fijo por modalidad.
- [x] Cargar y guardar los campos por AJAX con permisos de `metas_vendedor.editar`.
- [x] Añadir columna de cobranza y aporte en `ajax/tabla-metas-retos.ajax.php`.
- [x] Actualizar encabezados, texto de ayuda, tooltip y total estimado.
- [x] Mantener visible una indicación inequívoca: “Comisión por ventas: desactivada”.

## 7. Checklist de pruebas

- [ ] Un pago con código `00` y `tip_mov = '-'` suma a la cobranza neta del vendedor correcto.
- [ ] Un pago de cada código permitido suma una sola vez.
- [ ] Cada código confirmado (`00`, `TR`, `05`, `06`, `14`, `15`, `16`, `17`, `18`, `80`, `82`) suma una sola vez.
- [ ] Cualquier código fuera de la lista definitiva no suma a comisión de cobranza.
- [ ] Movimientos de otro vendedor o fuera del mes no suman.
- [ ] Una cobranza bruta de S/ 11,800 se muestra como S/ 10,000.00 netos (sin IGV).
- [ ] Una meta de S/ 10,000 con cobranza neta de S/ 8,000 y prorrata 2 % muestra S/ 160.00.
- [ ] Una meta de S/ 10,000 con cobranza neta de S/ 8,000 y todo o nada/fijo muestra S/ 0.00.
- [ ] La misma meta, con cobranza neta de S/ 10,000, muestra el fijo configurado.
- [ ] Una cobranza neta superior a la meta sigue pagando el porcentaje sobre el total cobrado.
- [ ] La comisión de ventas es S/ 0.00 aunque exista venta y campos de venta configurados.
- [ ] Clientes y modelos generales mantienen exactamente su comportamiento aprobado; los incentivos por producto continúan calculando comisión por venta.
- [ ] `metas-retos` y `metas-vendedor` muestran la misma meta de cobranza tras la sincronización.
- [ ] La consulta mensual se revisa con `EXPLAIN` sobre un período real.
- [ ] Un usuario sin permiso de edición no puede guardar ni modificar campos mediante AJAX.

## 8. Despliegue

- [ ] Respaldar `metas_retos_vendedorjf` y `metas_vendedorjf`.
- [ ] Ejecutar migración en copia de base de datos.
- [ ] Conciliar por vendedor el bruto de la nueva consulta contra la columna Cobranza de Resumen de gestión y el neto contra `bruto / 1.18` de un mes cerrado.
- [ ] Cargar las metas de cobranza del mes actual y definir la fuente inicial si existen valores en ambas pantallas.
- [ ] Mantener durante el primer cierre un reporte de diferencias por código de pago y vendedor.
- [ ] Autorizar el paso a cálculo oficial de comisión de cobranza.

## 9. Decisiones confirmadas

1. En prorrata, el porcentaje se paga sobre toda la cobranza neta lograda, incluso si supera la meta.
2. Los incentivos por producto/modelo/color continúan generando comisión por venta.
3. La cobranza para metas/retos toma el mismo criterio de Resumen de gestión de `/inicio-gerencia`; su lista reducida se corrige a `00`, `TR`, `05`, `06`, `14`, `15`, `16`, `17`, `18`, `80`, `82`, y el IGV se retira mediante división entre `1.18`.

## 10. Normalización obligatoria de códigos

La implementación debe resolver la inconsistencia actual:

| Origen | Códigos incluidos |
|---|---|
| Resumen de gestión (`inicio-gerencia`) | `00`, `05`, `06`, `14`, `80`, `82`, `TR` |
| Dashboard de cobranzas y metas de vendedor | `00`, `TR`, `05`, `06`, `14`, `15`, `16`, `17`, `18`, `80`, `82` |

Para metas y comisiones se usará la segunda lista, que incorpora `15`, `16`, `17` y `18`. Cursor debe actualizar el Resumen de gestión para que todos los paneles coincidan antes de activar la liquidación oficial.
