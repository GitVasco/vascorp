# Plan técnico — Regularización comercial excepcional

## Estado y alcance

**Estado:** plan aprobado. Tareas 0–5 hechas. Pendiente piloto/despliegue (Tareas 6–7).  
**Finalidad:** reflejar en VascoPro pagos efectivamente realizados por clientes y no ingresados a Vascorp por el fraude de junio, sin registrar movimientos contables ni modificar la cartera oficial.

Este módulo es excepcional y acotado. No sustituye cobranza, caja, cuentas corrientes ni conciliación contable.

### Decisiones cerradas (Tarea 0)

| Tema | Decisión |
|---|---|
| Enfoque | Adaptador comercial exclusivo para sync a VascoPro. No tocar contabilidad, CxC oficial ni flujos actuales. |
| Usuario Sistemas | ID `6` (provisional; único con permisos iniciales). |
| Evidencia al registrar | Suficiente: código de pago / OP / nro. de recibo en texto (`sustento_referencia` + motivo). Sin adjuntos. El caso se resolverá por vía legal a largo plazo. |
| Carga de casos | Manual por pantalla (caso a caso). El Excel de negocio es fuente humana, no import automático. |
| Cuando entre el pago oficial | Se registra **solo** en `cuenta_ctejf` por el flujo normal del ERP. La regularización deja de aplicar al saldo comercial (pasa a `RESUELTA_AUTOMATICA` o se reduce parcialmente). **No se borra la fila** de la tabla alterna: queda como evidencia/auditoría; deja de afectar VascoPro. |
| Contabilidad / sistema actual | Intocable. Cero `INSERT`/`UPDATE`/`DELETE` sobre tablas oficiales desde este módulo. |

## 1. Hallazgos del relevamiento

### Arquitectura relevante

Vascorp es una aplicación PHP con controladores, modelos PDO, AJAX y vistas PHP. La cuenta corriente oficial es `cuenta_ctejf`:

- los cargos/documentos usan `tip_mov = '+'`;
- los pagos/cancelaciones oficiales usan `tip_mov = '-'`;
- el cargo mantiene su saldo oficial en `cuenta_ctejf.saldo`, estado y `ult_pago`;
- un abono oficial se vincula al documento por `tipo_doc`, `num_cta` y `doc_origen`.

La lógica actual actualiza el saldo y el estado oficial con `ModeloCuentas::mdlActualizarCuenta()`. Esta lógica no debe ser invocada por el nuevo módulo.

### Cuentas por cobrar y saldos actuales

Los listados y consultas internas consumen directamente `cuenta_ctejf.saldo`; por ejemplo, `ModeloCuentas::mdlMostrarCuentaDeuda()`, `mdlMostrarCuentaDeudaVencida()`, `mdlRangoFechasCuentas*()` y las tablas AJAX de cuentas. Esos son saldos **oficiales** y deben seguir siéndolo.

Para la integración VascoPro, el punto de extracción está concentrado en `ModeloVascoSync`:

- `sqlFiltroCuentaPendiente()` define cartera exportable: `tip_mov='+'`, `estado='PENDIENTE'`, `saldo > 0`.
- `sqlSubqueryCuentasPorDocKey()` calcula `deuda_total` y `vencido_total` por documento de identidad del cliente.
- `mdlCuentasDocsPendientesPorDocKeys()` obtiene los documentos y sus saldos para el payload.
- `ControladorVascoSync::ctrSincronizarLoteCuentas()` arma y publica `POST /v2/sync/account-statements-bulk`.

La sincronización es un *snapshot*: el `finalize` purga en VascoPro las cuentas no presentes. Por ello, un documento comercialmente cancelado no debe enviarse con `balance: 0`; debe excluirse de `pending_documents`, y el total comercial debe recalcularse antes de publicar el snapshot.

### Permisos especiales

`controladores/permisos-modulos.config.php` lee `controladores/permisos-modulos.json` y expone `usuarioPuedeVerModulo(sector, modulo)` y `usuarioPuedeModulo(sector, modulo, accion)`. La sincronización actual ya lo usa en la vista y en el endpoint. El módulo debe añadir su propio nodo a este JSON; no debe usar flags generales de sesión ni el sistema general de permisos.

### Riesgos encontrados

1. Existen varias consultas internas directas sobre `cuenta_ctejf.saldo`; cambiarlas propagaría una realidad comercial a reportes internos y crédito. Quedan expresamente fuera.
2. VascoPro consolida por identidad de cliente y agrupa documentos por `tipo_doc` + `num_cta`; una regularización debe apuntar a un cargo oficial identificable, no solo a un cliente.
3. El saldo oficial puede cambiar por pagos, notas u operaciones posteriores. La resolución automática debe basarse en abonos oficiales vinculados al documento, no inferirse solamente por la diferencia de saldo.
4. El exportador existente agrupa algunos documentos por número para VascoPro. Se debe validar antes de producción que `(tipo_doc, num_cta, cliente)` identifica unívocamente el cargo de los casos de fraude; si no, se requerirá una regla explícita de agregación aprobada.
5. Sin una auditoría inmutable, una modificación posterior de una regularización podría ocultar el caso. Se requiere historial de eventos separado.

## 2. Alternativas evaluadas

| Alternativa | Ventaja | Riesgo / descarte |
|---|---|---|
| Registrar el pago como abono oficial en `cuenta_ctejf` | Reutiliza el flujo actual | Altera contabilidad, cobranza, saldo y auditoría oficial. Incompatible con el objetivo. |
| Modificar todas las consultas de CxC para devolver saldo comercial | Un único saldo visible dentro del ERP | Impacta reportes, crédito, pantallas y procesos no relacionados; contradice las restricciones. |
| Crear una vista SQL que sustituya la tabla oficial | Puede centralizar el cálculo | Exige cambiar consultas existentes o nombres de tabla; acopla el módulo a toda la aplicación y dificulta reversión. |
| **Adaptador comercial exclusivo para la exportación a VascoPro** | Aísla la excepción, deja intacta la realidad oficial y concentra el cambio en el único consumidor que debe verla | Requiere un contrato interno de datos y pruebas de snapshot. **Recomendada.** |

## 3. Arquitectura recomendada

### Principio

Crear un *bounded module* `regularizaciones-comerciales`, propietario de sus datos y reglas. La única integración será un adaptador de lectura usado por el exportador de estados de cuenta. Si no existen regularizaciones activas, dicho adaptador devuelve exactamente los mismos importes y documentos que hoy; por tanto, el comportamiento preexistente se conserva.

Flujo:

```text
Pantalla restringida -> AJAX propio -> Servicio de regularización -> tablas nuevas
                                                        |
cuenta_ctejf (solo lectura) -> Adaptador de saldo comercial -> ModeloVascoSync -> VascoPro
                                                        |
                                           Auditoría de resolución automática
```

### Componentes nuevos

| Componente | Responsabilidad |
|---|---|
| `ModeloRegularizacionesComerciales` | Persistencia solo de tablas del módulo y lecturas de referencia a `cuenta_ctejf`. |
| `ServicioRegularizacionesComerciales` | Validación, alta, anulación lógica, cálculo de monto aplicable y conciliación automática. Sin HTTP ni HTML. |
| `AdaptadorSaldoComercialVasco` | Recibe filas oficiales ya extraídas para el snapshot y devuelve sus equivalentes comerciales; no cambia SQL ni semántica de otros módulos. |
| `ControladorRegularizacionesComerciales` | Casos de uso y contexto de sesión/auditoría. |
| `ajax/regularizaciones-comerciales.ajax.php` | API JSON propia, autenticada y autorizada. |
| Vista/JS/CSS propios | Consulta de cargos, alta, historial y estados. No editar las vistas existentes de CxC. |
| Integración mínima de sync | Llama al adaptador inmediatamente antes de `mapearCuentaParaApi()`. No altera el endpoint, contrato HTTP ni tablas oficiales. |

### Cálculo comercial

Para cada cargo oficial exportable:

`saldo_comercial = max(0, saldo_oficial - suma(montos_aplicables_de_regularizaciones_activas))`.

El adaptador debe:

1. preservar el saldo oficial como dato de referencia;
2. no aplicar más regularización que el saldo oficial actual;
3. excluir del detalle VascoPro los documentos cuyo saldo comercial sea cero;
4. recalcular `deuda_total` y `vencido_total` desde esos documentos comerciales;
5. no exponer campos, nombres, eventos ni marcas de regularización a VascoPro.

La consulta de auditoría del sync debe usar el mismo adaptador, para que sus totales coincidan exactamente con lo publicado.

### Resolución automática basada en evidencia oficial

Al calcular saldo comercial (y adicionalmente al registrar/listar), el servicio buscará abonos oficiales nuevos en `cuenta_ctejf` con `tip_mov='-'`, vinculados al documento origen. Solo se consideran posteriores al corte guardado al crear la regularización y que no hayan sido ya consumidos por otra resolución.

La asignación será FIFO determinista: por fecha de pago oficial, `id` de movimiento oficial y `id` de regularización. Si un abono cubre totalmente el monto pendiente de una regularización, su estado cambia a `RESUELTA_AUTOMATICA`; si cubre una parte, solo se reduce su `monto_aplicable` y se registra el evento parcial. Nunca se elimina la fila.

Esto exige confirmar durante la implementación la regla de vínculo exacta del abono en los casos reales (`doc_origen`, `tipo_doc`, `num_cta`). Si el ERP permite abonos sin vínculo inequívoco al cargo, no se debe inventar una asociación: el caso queda como `REQUIERE_REVISION` y Sistemas decide explícitamente.

## 4. Modelo de datos nuevo

Los nombres son propuestos y no crean llaves foráneas físicas a tablas legacy: se conserva la referencia lógica y un snapshot, para no introducir acoplamiento DDL ni perder auditoría si cambia el histórico oficial.

### `regularizacion_comercialjf`

| Campo | Propósito |
|---|---|
| `id` BIGINT PK | Identificador técnico. |
| `cuenta_cte_id` INT | Referencia lógica al cargo oficial. |
| `tipo_doc`, `num_cta`, `cliente_codigo` | Snapshot de identidad comercial del cargo. |
| `monto_original` DECIMAL(14,2) | Pago comercial comprobado inicialmente. Inmutable. |
| `monto_aplicable` DECIMAL(14,2) | Porción aún aplicada al saldo comercial. |
| `fecha_pago_cliente` DATE | Fecha en que el cliente pagó. |
| `fecha_registro` DATETIME | Registro en el módulo. |
| `saldo_oficial_al_registrar` DECIMAL(14,2) | Evidencia del contexto de creación. |
| `corte_movimiento_oficial_id` BIGINT NULL | Último abono oficial observado al crear; delimita conciliación posterior. |
| `estado` VARCHAR(30) | `ACTIVA`, `RESUELTA_AUTOMATICA`, `ANULADA`, `REQUIERE_REVISION`. |
| `motivo`, `sustento_referencia`, `observacion` | Contexto obligatorio y referencia a evidencia; sin almacenar binarios. |
| `usuario_registro_id`, `usuario_anulacion_id` | Trazabilidad. |
| `fecha_anulacion`, `motivo_anulacion` | Baja lógica. |
| `version` INT | Control optimista contra doble edición/resolución. |

Índices: `(cuenta_cte_id, estado)`, `(tipo_doc, num_cta, cliente_codigo, estado)`, `(estado, fecha_registro)`. Restricción de chequeo en aplicación para montos positivos y `monto_aplicable <= monto_original` (MariaDB legacy puede no hacer cumplir `CHECK`).

### `regularizacion_comercial_eventojf`

Historial append-only: `id`, `regularizacion_id`, `tipo_evento`, `estado_anterior`, `estado_nuevo`, `monto_delta`, `monto_aplicable_resultante`, `movimiento_oficial_id`, `detalle_json`, `usuario_id`, `fecha`, `origen` (`USUARIO`/`AUTO_SYNC`).

Cada alta, anulación, resolución parcial/completa y detección de ambigüedad genera un evento en la misma transacción de su cambio de estado.

No se reutiliza `auditoriajf`: su estructura es insuficiente para reconstruir el cálculo y no es propiedad del nuevo módulo.

## 5. Interfaz y endpoints propuestos

Ruta: `regularizaciones-comerciales` bajo el menú de Cuentas Corrientes, visible solo a Sistemas.

La vista debe mostrar explícitamente: “Saldo oficial (no modificado)”, “Regularizaciones activas” y “Saldo comercial enviado a VascoPro”; no debe presentarse como asiento, caja o cobranza.

Endpoints JSON propios, todos con sesión y permiso especial:

| Acción | Permiso | Resultado |
|---|---|---|
| `buscar-cargos` | `ver` | Cargos oficiales elegibles, solo lectura. |
| `listar` / `ver` | `ver` | Regularizaciones e historial. |
| `crear` | `registrar` | Alta transaccional y evento. |
| `anular` | `anular` | Anulación lógica con motivo y evento. |
| `reconciliar` | `resolver` | Revisión manual segura de casos ambiguos; no modifica contabilidad. |

No se exponen endpoints a VascoPro y no se agregan campos al payload actual.

## 6. Permisos

Añadir `vasco_online.regularizaciones_comerciales` al JSON de permisos con acciones `ver`, `registrar`, `anular`, `resolver`; inicialmente, los cuatro arreglos deben contener exclusivamente el ID de usuario de Sistemas proporcionado por el responsable. No se debe presumir que el ID `6` representa Sistemas: el JSON actual no documenta ese mapeo.

El mismo control se aplica en ruta, vista y cada endpoint AJAX. La ausencia de una acción deniega por defecto.

## 7. Plan de implementación para aprobación

### Tarea 0 — Cerrar supuestos de negocio

- **Objetivo:** validar la población exacta de fraude y la regla de identificación de pago oficial posterior.
- **Archivos:** ninguno; consultas de solo lectura sobre `cuenta_ctejf`.
- **Cambio:** documentar IDs de cargos, evidencia, usuario Sistemas y regla de abono vinculante.
- **Dependencias:** acceso a datos históricos y Contabilidad.
- **Aceptación:** muestra de cada caso con cargo, regularización esperada y eventual abono oficial identificado sin ambigüedad.
- **Riesgo:** aplicar una regularización a documento/cliente equivocado.

### Tarea 1 — Migración aislada

- **Objetivo:** crear las dos tablas e índices nuevos.
- **Archivos:** `docs/sql/regularizaciones-comerciales.sql`.
- **Cambio:** DDL idempotente, sin `ALTER` sobre tablas existentes y sin FKs legacy físicas.
- **Dependencias:** Tarea 0.
- **Aceptación:** puede ejecutarse dos veces; no cambia el esquema de ninguna tabla actual.
- **Riesgo:** compatibilidad MariaDB; probar en una copia con la versión objetivo.

### Tarea 2 — Persistencia, servicio y auditoría

- **Objetivo:** encapsular el ciclo de vida y el cálculo, con transacciones.
- **Archivos:** nuevos modelo, servicio y controlador del módulo.
- **Cambio:** alta, consulta, anulación lógica, eventos inmutables, validación de saldo y conciliación FIFO.
- **Dependencias:** Tarea 1.
- **Aceptación:** ninguna operación ejecuta `INSERT`, `UPDATE` o `DELETE` sobre `cuenta_ctejf`.
- **Riesgo:** concurrencia entre dos altas o entre alta y sync; usar transacción y versión/bloqueo de fila nueva.

### Tarea 3 — Adaptador comercial y pruebas unitarias de cálculo

- **Objetivo:** transformar únicamente filas oficiales destinadas a VascoPro.
- **Archivos:** nuevo adaptador y tests/fixture de datos.
- **Cambio:** cálculo por documento, exclusión de saldos comerciales cero y totales recalculados.
- **Dependencias:** Tarea 2.
- **Aceptación:** sin filas activas, entrada y salida son idénticas; con una regularización, el saldo oficial se conserva y solo la salida comercial disminuye.
- **Riesgo:** diferencias entre el resumen y el detalle enviado.

### Tarea 4 — Integración acotada con sync

- **Objetivo:** consumir el adaptador en auditoría y en `ctrSincronizarLoteCuentas()`.
- **Archivos:** `modelos/vasco-sync.modelo.php`, `controladores/vasco-sync.controlador.php` y los `require` mínimos.
- **Cambio:** sustituir solo el conjunto de datos de exportación por su proyección comercial; no cambiar URL, endpoint, lotes ni contrato.
- **Dependencias:** Tarea 3.
- **Aceptación:** snapshot sin regularizaciones byte-equivalente salvo orden no relevante; documento comercialmente cerrado desaparece del payload y `finalize` lo elimina de VascoPro.
- **Riesgo:** modificar la paginación si se filtran documentos después de paginar; paginar clientes sobre el conjunto comercial, manteniendo identificadores estables.

### Tarea 5 — Pantalla y AJAX restringidos

- **Objetivo:** permitir administrar el caso sin tocar vistas de CxC.
- **Archivos:** nueva vista, JS, CSS, AJAX; altas de ruta/carga de assets/menú mínimas.
- **Cambio:** búsqueda, formulario con confirmación, historial y anulación con motivo.
- **Dependencias:** Tarea 2 y usuario Sistemas confirmado.
- **Aceptación:** un usuario fuera del JSON recibe 403/no ve menú; un usuario autorizado no puede editar el saldo oficial.
- **Riesgo:** controles solo del lado cliente; validar también siempre en servidor.

### Tarea 6 — Pruebas de integración y piloto

- **Objetivo:** validar snapshot, resolución automática y reversión operativa.
- **Archivos:** casos de prueba/documento de despliegue.
- **Cambio:** pruebas en copia de base/API de prueba y respaldo de payload antes/después.
- **Dependencias:** Tareas 1–5.
- **Aceptación:** casos: sin regularización, una parcial, múltiples, pago oficial posterior, abono ambiguo, anulación, cliente consolidado y `finalize`.
- **Riesgo:** publicar una cartera incorrecta; habilitar primero con un único caso y comparar payload con negocio.

### Tarea 7 — Despliegue reversible y monitoreo

- **Objetivo:** activar con riesgo bajo.
- **Archivos:** runbook y configuración JSON de permisos.
- **Cambio:** despliegue de tablas/código, carga controlada de casos, ejecución de sync supervisada.
- **Dependencias:** aprobación de resultados de Tarea 6.
- **Aceptación:** deshabilitar permisos o no registrar filas devuelve inmediatamente la exportación oficial; no se requiere rollback contable.
- **Riesgo:** reversión de código con tablas persistentes; las tablas nuevas son aditivas y pueden conservarse como evidencia.

## 8. Instrucciones para Cursor

Plan aprobado. Decisiones de Tarea 0 documentadas arriba (usuario `6`, evidencia = OP/recibo en texto, carga manual, sin tocar tablas oficiales).

Pendiente solo de validar en piloto (no bloquea arranque de Tareas 1–5): regla exacta de vínculo abono↔cargo en casos reales. Mientras tanto: vínculo inequívoco → resolución automática; ambigüedad → `REQUIERE_REVISION`.

Al implementar: seguir Tareas 1→7 en orden; no modificar contabilidad ni vistas de CxC existentes.
