# Plan: Módulo de costos mensuales por modelo

**Estado:** propuesto para implementación independiente.  
**Dependencia:** será la fuente oficial de margen y utilidad de la Fase 4 de `PLAN_FICHA_GERENCIAL_MODELOS.md`.  
**Objetivo:** cargar, revisar y aprobar el costo de cada modelo por mes para calcular rentabilidad con historial, sin depender de Excel/JSON de 2022.

## 1. Alcance inicial

El módulo permitirá al propietario autorizado cargar costos unitarios directos de modelos activos por mes, por ingreso manual y carga masiva CSV/Excel. Cada registro quedará auditado y aprobado antes de ser usado por la ficha gerencial.

La primera versión registra **costo unitario mensual directo por modelo**. No intenta aún repartir el costo hasta color, talla o artículo; esas variantes heredan el costo mensual de su modelo. Esa precisión adicional se deja para una fase posterior si el negocio la requiere.

## 2. Datos a registrar

Para cada `modelo + año + mes`:

| Campo | Uso |
|---|---|
| Modelo, marca y nombre | Identificación, obtenida desde `modelojf`; no editable en la carga. |
| Costo unitario | Único valor requerido: costo directo unitario aprobado para el modelo y mes. |
| Fuente / observación | Archivo, criterio o comentario que explica el cálculo. |
| Estado | `borrador`, `aprobado` o `anulado`. Solo aprobado llega a la ficha. |
| Auditoría | Usuario, fecha de carga, aprobación y modificación. |

Todos los importes se guardan **sin IGV**, en soles, y con cuatro decimales internos. La ficha redondea la visualización a dos decimales.

## 3. Modelo de datos propuesto

Archivo: `docs/sql/costos-mensuales-modelo.sql`.

```sql
CREATE TABLE costos_modelo_mensualjf (
    id                  INT AUTO_INCREMENT PRIMARY KEY,
    modelo              VARCHAR(50) NOT NULL COMMENT 'modelojf.modelo',
    anio                SMALLINT NOT NULL,
    mes                 TINYINT NOT NULL,
    costo_unitario      DECIMAL(14,4) NOT NULL DEFAULT 0 COMMENT 'Costo unitario directo aprobado',
    fuente              VARCHAR(100) NULL,
    observacion         VARCHAR(500) NULL,
    estado              VARCHAR(20) NOT NULL DEFAULT 'borrador',
    usuario_registro    INT NULL,
    fecha_registro      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    usuario_aprobacion  INT NULL,
    fecha_aprobacion    DATETIME NULL,
    usuario_modificacion INT NULL,
    fecha_modificacion  DATETIME NULL,
    UNIQUE KEY uk_cmm_modelo_periodo (modelo, anio, mes),
    KEY idx_cmm_periodo_estado (anio, mes, estado),
    KEY idx_cmm_modelo_estado (modelo, estado)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE costos_modelo_mensual_historialjf (
    id                  BIGINT AUTO_INCREMENT PRIMARY KEY,
    costo_modelo_id     INT NOT NULL,
    modelo              VARCHAR(50) NOT NULL,
    anio                SMALLINT NOT NULL,
    mes                 TINYINT NOT NULL,
    costo_unitario      DECIMAL(14,4) NOT NULL,
    fuente              VARCHAR(100) NULL,
    observacion         VARCHAR(500) NULL,
    estado              VARCHAR(20) NOT NULL,
    accion              VARCHAR(30) NOT NULL COMMENT 'creado, modificado, aprobado, anulado o reabierto',
    motivo              VARCHAR(500) NULL,
    usuario              INT NULL,
    fecha                DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_cmmh_registro_fecha (costo_modelo_id, fecha),
    KEY idx_cmmh_modelo_periodo (modelo, anio, mes)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
```

Reglas de integridad a implementar en PHP/transacción:

- El modelo existe en `modelojf`; la marca/nombre se leen desde catálogo.
- Mes entre 1 y 12 y costo unitario no negativo.
- El único importe requerido es `costo_unitario`; el backend lo normaliza y persiste con cuatro decimales.
- La descomposición posterior por materia prima, mano de obra, CIF y costos fijos no se implementa en esta fase.
- Solo puede existir un registro por modelo/período. Para corregir un aprobado, registrar auditoría y exigir re-aprobación; no modificar silenciosamente una cifra usada por gerencia.
- Cada creación, modificación, aprobación, anulación y reapertura genera una fila inmutable en `costos_modelo_mensual_historialjf`, conservando el valor y estado anteriores.
- La ficha de modelo lee exclusivamente filas `estado = 'aprobado'`.

## 4. Flujo de usuario

1. Seleccionar año y mes.
2. Elegir carga manual para un modelo o importar CSV/Excel.
3. Ver una grilla con modelos activos y costo unitario directo.
4. Corregir errores de la carga; los errores se muestran por fila y no se guarda parcialmente sin confirmación.
5. Guardar como **borrador**.
6. Revisar totales por marca/modelo y aprobar el período o filas seleccionadas.
7. La Ficha gerencial empieza a mostrar margen y utilidad solo desde que el costo está aprobado.

Pantallas propuestas:

- **Costos mensuales:** filtros período, marca, estado y grilla de carga/revisión.
- **Importar costos:** carga de archivo, mapeo de columnas, previsualización y reporte de errores.
- **Historial de costo:** por modelo, con períodos, costo unitario, fuente, cambios y aprobaciones.

## 5. Carga masiva

Plantilla mínima:

```text
modelo,costo_unitario,fuente,observacion
10026,22.5000,Costeo julio 2026,Revisión producción
```

Reglas de importación:

- Año y mes se eligen en pantalla, no se repiten por fila inicialmente.
- Identificar modelo por código, nunca por nombre.
- Rechazar modelos inexistentes, duplicados dentro del archivo, números inválidos y componentes negativos.
- Mostrar importadas, rechazadas y motivo por fila antes de persistir.
- Guardar nombre del archivo, hash/fecha de importación y usuario como auditoría complementaria si el repositorio ya dispone de almacenamiento de adjuntos; de no ser así, guardar fuente/observación como MVP.

## 6. Integración posterior con la ficha gerencial

Para una venta de un modelo en un período:

```text
costo_venta = unidades_vendidas × costo_unitario_aprobado_del_mismo_modelo_y_mes
utilidad     = venta_neta − costo_venta
margen       = utilidad ÷ venta_neta
```

Reglas de visualización:

- Si no existe costo aprobado para el período, mostrar “Costo pendiente de aprobación”; no mostrar margen ni utilidad como cero.
- Si se consulta un período histórico, usar el costo de ese período, no el último costo disponible.
- No mezclar ventas con IGV y costos sin IGV: la venta usada para margen debe ser neta sin IGV.
- El margen por color/talla usa el costo del modelo hasta que exista costo por variante.

Contrato backend disponible para la futura ficha:

- `accion=costoAprobado`: recibe `modelo`, `anio` y `mes`; devuelve el costo aprobado o `costo_pendiente_aprobacion` con importe nulo.
- `accion=calcularRentabilidad`: además recibe `venta_neta` y `unidades_vendidas`; devuelve costo de venta, utilidad y margen calculados con decimales en MySQL.
- `ModeloCostosModeloMensual::mdlCostosAprobadosPeriodo()` permite resolver varios modelos en una sola consulta para reportes agregados.

## 7. Fases de implementación

### Fase C1 — Catálogo y carga manual

- [ ] Crear tabla/migración, modelo, controlador, AJAX, vista, JS, ruta y permisos.
- [ ] Carga manual de un modelo y grilla por período.
- [ ] Validación y normalización central del costo unitario directo.
- [ ] Guardar como borrador e historial de auditoría.

### Fase C2 — Importación y aprobación

- [x] Plantilla CSV descargable. Excel queda pendiente de una librería compatible con la versión actual de PHP.
- [x] Previsualización, validación y carga masiva CSV transaccional.
- [x] Flujo de aprobación, anulación, reapertura y bloqueo contra cambios silenciosos.
- [x] Reporte CSV de modelos sin costo aprobado en el período.

### Fase C3 — Integración de rentabilidad

- [x] Exponer consulta de costo aprobado por modelo/período, individual y por lote.
- [x] Implementar cálculo decimal reutilizable de costo de venta, utilidad, margen y alerta por utilidad negativa.
- [x] Devolver estado pendiente y valores nulos cuando no existe costo aprobado.
- [ ] Consumir el contrato desde la futura Ficha gerencial de modelos.
- [ ] Conciliar una muestra con costos reales aprobados de finanzas/producción.

## 8. Permisos y seguridad

Agregar `gestion_comercial.costos_modelo` en `controladores/permisos-modulos.json`, fuente única de permisos del módulo:

- `ver`: consulta e historial.
- `editar`: carga manual/importación/borrador.
- `aprobar`: aprobar, anular y reabrir con motivo.

Para el alcance actual, el propietario autorizado tendrá los tres permisos. Si se delega después, separar a quien carga de quien aprueba.

La vista, los endpoints AJAX y el controlador deben validar estas acciones desde el JSON. No se mantendrán listas de IDs ni permisos paralelos en PHP o JavaScript.

## 9. Checklist de aceptación

- [ ] Un costo manual de un modelo guarda correctamente el costo unitario directo.
- [ ] No se aceptan importes negativos, mes inválido o modelo inexistente.
- [ ] Una carga con fila errónea informa el error sin crear costos parciales inesperados.
- [ ] Solo el costo aprobado es visible para margen en la ficha.
- [ ] Una corrección de costo aprobado conserva quién cambió qué y exige nueva aprobación.
- [ ] Un modelo sin costo del período muestra estado pendiente, no margen ficticio.
- [ ] Una consulta de un mes anterior usa su costo histórico aprobado.
- [ ] Venta neta, costo de venta, utilidad y margen concilian para los modelos de prueba `10026`, `10273`, `10353` y `10054`.

## 10. Decisiones confirmadas y pendiente

1. **Confirmado:** se cargará costo unitario directo por modelo. No se implementa por ahora costo total mensual + unidades base.
2. **Confirmado:** en esta etapa solo el propietario autorizado aprueba, anula o reabre costos.
3. **Confirmado:** todo cambio conservará historial inmutable con valor, estado, acción, motivo, usuario y fecha.
4. **Confirmado:** los permisos se administrarán exclusivamente desde `controladores/permisos-modulos.json`.
5. ¿La carga inicial será CSV, Excel o ambas? Recomiendo CSV primero por validación más segura y Excel como exportación/plantilla posterior.
