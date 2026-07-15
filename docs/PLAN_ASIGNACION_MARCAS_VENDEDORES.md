# Plan de asignación de marcas a vendedores

**Estado:** definición funcional confirmada; pendiente de implementación.  
**Módulo:** Gestión comercial.  
**Alcance inicial:** metas, retos y reportes de desempeño de vendedores.

## 1. Problema y objetivo

Actualmente el avance de venta de una meta se calcula con el total de `ventajf.neto` agrupado por vendedor. Esa consulta no identifica los artículos ni su marca. Por tanto, la venta de cualquier marca suma al cumplimiento de cualquier vendedor, aunque esa marca no esté dentro de su responsabilidad.

El objetivo es administrar qué **grupos de marcas** puede atender cada vendedor y usar esa asignación para obtener indicadores comerciales justos y auditables. En particular, una meta, modelo, cliente nuevo o reto solo deberá considerar operaciones de marcas contenidas en grupos habilitados para ese vendedor en la fecha de la operación.

No se debe reescribir ni alterar la historia de ventas: la asignación debe tener vigencia para conservar el criterio que aplicaba en cada período, incluso cuando haya rotación de vendedores.

## 2. Decisiones de diseño recomendadas

| Tema | Decisión recomendada |
|---|---|
| Relación | Muchos a muchos: un vendedor puede atender varios grupos/marcas y una marca puede estar disponible para varios vendedores simultáneamente. |
| Agrupación | La administración se hace por grupos de marcas. Ejemplo: código `X` = Grupo Jackyform (Jackyform + Guapitas); código `Y` = RosaFlor (Rosalinda + Rositas). |
| Identificadores | Usar `maestrajf.codigo` (`TVEND`) para vendedor, `marcasjf.id` para marca y claves internas numéricas para grupos; no usar textos como claves. |
| Vigencia | Cada asignación vendedor–grupo tiene `fecha_inicio` obligatoria y `fecha_fin` opcional. Aplica desde el mes actual y puede cambiar a futuro sin alterar períodos anteriores. |
| Histórico | No editar una asignación antigua para cambiar el pasado: se cierra con `fecha_fin` y se crea otra nueva. |
| Cálculo de meta | La meta se mide por **modelo**. Cada modelo pertenece obligatoriamente a una sola marca; por ello un modelo cuenta si su marca pertenece a un grupo vigente asignado al vendedor. |
| Documentos mixtos | Cada línea se evalúa por el modelo y la marca de su artículo; solo cuentan los modelos de grupos permitidos. |
| Venta no asignada | Se conserva en un indicador separado: **fuera de cobertura**. No suma a metas ni retos. |
| Cobranza | No se atribuye por marcas ni se altera en esta iniciativa. |
| Control de venta | Una venta/modelo no autorizado no se toma en cuenta para metas o retos. El registro de venta no se bloqueará en el MVP. |
| Datos maestros | Todo artículo tiene marca y modelo; el modelo es obligatorio y pertenece a una única marca. Esto debe validarse en consultas de control. |
| Edición | Solo el propietario/administrador autorizado por el negocio podrá editar grupos y asignaciones. |

### Regla de pertenencia

Una línea de venta pertenece al avance de un vendedor si se cumplen todas estas condiciones:

1. El documento es una venta válida para el KPI (`S02`, `S03`, `S70`, `E05`, `S05`), no está anulado y cae dentro del período.
2. La cabecera o movimiento corresponde al vendedor evaluado.
3. El artículo de la línea tiene modelo e `id_marca` válidos en `articulojf`, y el modelo pertenece a esa única marca.
4. La marca pertenece a un grupo de marcas activo.
5. Existe una fila activa en la asignación vendedor–grupo cuya vigencia incluya la fecha del documento.

Para importes, la fuente de detalle deberá confirmarse durante el relevamiento: el repositorio ya muestra que los indicadores de modelos usan `movimientosjf_AAAA` + `articulojf`; ese es el candidato preferido si contiene el importe neto por línea y se puede relacionar inequívocamente con el documento. Si no lo contiene, usar el detalle transaccional equivalente que sí tenga cantidad, precio, descuento e importe neto.

## 3. Modelo de datos propuesto

Crear una migración nueva, por ejemplo `docs/sql/asignacion-grupos-marcas-vendedores.sql`:

```sql
CREATE TABLE grupos_marcas_comercialjf (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    codigo          VARCHAR(30) NOT NULL,
    nombre          VARCHAR(100) NOT NULL,
    descripcion     VARCHAR(255) NULL,
    estado          TINYINT(1) NOT NULL DEFAULT 1,
    usureg          VARCHAR(50) NULL,
    fecreg          DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    usumod          VARCHAR(50) NULL,
    fecmod          DATETIME NULL,
    UNIQUE KEY uk_gmc_codigo (codigo),
    KEY idx_gmc_estado (estado)
) ENGINE=InnoDB DEFAULT CHARSET=utf8
  COMMENT='Grupos comerciales de marcas';

CREATE TABLE grupos_marcas_detallejf (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    id_grupo_marca  INT NOT NULL COMMENT 'grupos_marcas_comercialjf.id',
    id_marca        INT NOT NULL COMMENT 'marcasjf.id',
    usureg          VARCHAR(50) NULL,
    fecreg          DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uk_gmd_grupo_marca (id_grupo_marca, id_marca),
    KEY idx_gmd_marca (id_marca)
) ENGINE=InnoDB DEFAULT CHARSET=utf8
  COMMENT='Marcas contenidas en cada grupo comercial';

CREATE TABLE vendedor_grupos_marcasjf (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    cod_vendedor    VARCHAR(20) NOT NULL COMMENT 'maestrajf.codigo con tipo_dato=TVEND',
    id_grupo_marca  INT NOT NULL COMMENT 'grupos_marcas_comercialjf.id',
    fecha_inicio    DATE NOT NULL,
    fecha_fin       DATE NULL,
    estado          TINYINT(1) NOT NULL DEFAULT 1,
    observacion     VARCHAR(255) NULL,
    usureg          VARCHAR(50) NULL,
    fecreg          DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    usumod          VARCHAR(50) NULL,
    fecmod          DATETIME NULL,
    CONSTRAINT chk_vgm_fechas CHECK (fecha_fin IS NULL OR fecha_fin >= fecha_inicio),
    UNIQUE KEY uk_vgm_inicio (cod_vendedor, id_grupo_marca, fecha_inicio),
    KEY idx_vgm_vendedor_vigencia (cod_vendedor, estado, fecha_inicio, fecha_fin),
    KEY idx_vgm_grupo_vigencia (id_grupo_marca, estado, fecha_inicio, fecha_fin)
) ENGINE=InnoDB DEFAULT CHARSET=utf8
  COMMENT='Grupos de marcas autorizados por vendedor con vigencia';
```

Notas de implementación:

- Validar en PHP que `cod_vendedor` exista y sea `TVEND`, que el grupo esté activo y que sus marcas existan/estén activas. Esto es necesario porque las tablas actuales no usan llaves foráneas de forma consistente.
- Evitar rangos superpuestos para la misma pareja vendedor–grupo. MySQL no lo resuelve con un índice; validarlo en el servicio/controlador dentro de una transacción.
- No usar `estado=0` para “terminar” una asignación histórica; usar `fecha_fin`. El estado sirve para desactivar registros erróneos antes de que entren en vigencia.
- Registrar usuario y fechas de creación/modificación para auditoría.
- Antes de eliminar una marca de un grupo, validar su impacto histórico. Para el MVP, los grupos son catálogos estables: si su composición cambia, crear un grupo nuevo o habilitar una vigencia de detalle en una fase posterior.

## 4. Experiencia de usuario propuesta

Agregar en **Gestión comercial → Configuración comercial** dos pantallas: **Grupos de marcas** y **Asignación de grupos**.

Componentes mínimos:

1. **Grupos de marcas:** tabla y formulario para crear un grupo y agregarle marcas. Ejemplos iniciales: `X — Grupo Jackyform` y `Y — RosaFlor`.
2. **Asignación de grupos:** filtros por vendedor, grupo y estado al día seleccionado (por defecto, hoy).
3. Tabla: vendedor, grupo, marcas incluidas, inicio, fin, estado, observación, usuario y acciones.
4. Modal de alta: vendedor, selección múltiple de grupos, fecha de inicio y observación. Crear una fila por grupo.
4. Acción **Cerrar asignación**: solicita fecha de fin; nunca borra historia.
5. Vista por vendedor en la ficha/listado de metas: chips con sus grupos y marcas vigentes, con enlace a la asignación.
6. En metas y retos, mostrar:
   - `Venta permitida` (suma a meta);
   - `Venta fuera de cobertura` (no suma);
   - detalle por marca para explicar el avance.
7. En documentos de venta, solo como alerta inicial: advertir si el modelo/marca no pertenece a un grupo vigente para el vendedor; no bloquear en el MVP.

## 5. Cambios técnicos por componente

### Permisos y rutas

- Añadir `gestion_comercial.grupos_marcas` y `gestion_comercial.asignacion_grupos_marcas`, con `ver` y `editar`, en `controladores/permisos-modulos.json`. Configurar `editar` solo para tu usuario/rol autorizado.
- Registrar la ruta en `vistas/plantilla.php`, script JS y menú de Gestión comercial en `vistas/modulos/menu.php`.
- Aplicar `usuarioPuedeVerModulo` para la pantalla y `usuarioPuedeModulo(..., 'editar')` para altas, cierres y cambios.

### Backend de asignaciones

- Crear controladores/modelos para grupos de marcas y asignación vendedor–grupo.
- Crear AJAX y DataTable siguiendo el patrón de zonas comerciales.
- Operaciones necesarias: administrar grupos, listar vigentes/históricas, crear asignaciones en lote, cerrar, y consultar marcas vigentes por vendedor/fecha a través de sus grupos.
- Centralizar la condición SQL de vigencia en una función reutilizable; no duplicar la lógica en cada KPI.

### Servicio de métricas por marca

- Crear una capa o métodos comunes, por ejemplo `ModeloMetricasComerciales::mdlVentasPermitidasPorVendedorPeriodo()`.
- Retornar por vendedor: `venta_permitida`, `venta_fuera_cobertura`, detalle por `id_marca`/nombre y, si corresponde, cantidades/modelos/clientes.
- Aplicar la misma regla en estos puntos ya existentes:
  - `ModeloMetasVendedor::mdlListarMetasPeriodo()`;
  - `ModeloMetasVendedor::mdlAvanceVentasDashboard()`;
  - `ModeloMetasRetos::mdlVentaRealPorVendedor()`;
  - `ModeloMetasRetos::mdlModelosActivosPorVendedor()`;
  - `ModeloMetasRetos::mdlClientesNuevosPorVendedor()` (definir si “cliente nuevo” exige que su primera compra sea permitida o que tenga alguna compra permitida en el período).
- Revisar los widgets del Centro de Decisiones que consumen `ctrAvanceVentas()` para que muestren el mismo avance y no queden con un total distinto.

### Consultas y rendimiento

- Relacionar la línea vendida con `articulojf.modelo` e `articulojf.id_marca`; luego enlazar marca → detalle de grupo → grupo vigente del vendedor, usando vendedor y fecha del movimiento/documento.
- Usar fechas semiabiertas (`>= inicio` y `< primer_día_mes_siguiente`) como ya hace el módulo.
- Ejecutar `EXPLAIN` con un período real antes y después. Crear índices sobre las columnas de unión de la tabla de detalle/movimientos solo si faltan; no añadir índices a ciegas.
- Asegurar que una asignación no duplique una línea al hacer `JOIN`; si hubiera riesgo por traslape, usar `EXISTS` mientras se corrigen datos.

## 6. Fases de entrega

### Fase 0 — Relevamiento y regla aprobada

- Confirmar cuál es la tabla de detalle fuente y qué columna representa el importe neto real por línea.
- Preparar tres documentos de prueba: una venta de una marca permitida, una no permitida y una venta con dos marcas.
- Confirmar la lista inicial de grupos, marcas y vendedores de la sección 10 antes de cargar datos.

### Fase 1 — Catálogo de grupos y administración de cobertura

- Implementar migración, permisos, menú y pantallas de grupos/asignaciones con vigencia.
- Crear los grupos iniciales y cargar sus marcas.
- Cargar las asignaciones iniciales desde el mes actual.
- Validar que no existan rangos duplicados o superpuestos.

### Fase 2 — Medición paralela

- Construir la consulta de detalle por marca y compararla contra el total actual, sin cambiar aún el valor oficial de metas.
- Mostrar para cada vendedor: total actual, permitido, fuera de cobertura y diferencia, con desglose por marca.
- Corregir datos maestros: artículos sin `id_marca`, documentos sin vendedor, marcas inactivas o asignaciones faltantes.

### Fase 3 — Metas y retos oficiales

- Sustituir el total de venta de metas/retos por `venta_permitida` (**hecho**).
- Filtrar modelos y retos por marcas vigentes (**hecho**).
- Clientes nuevos: 1ª compra del periodo con documento 100% permitido (**hecho**).
- **E05 devolución** (con unidades/líneas): imputa por marca.
- **E05 descuento** (sin unidades): resta al vendedor sin marca (`nc_descuento`).
- Conservar el indicador “fuera de cobertura” en la conciliación, sin sumarlo a la meta.
- Etiquetar claramente el período y las marcas consideradas.

### Fase 4 — Prevención y evolución

- Activar alerta en el registro/edición de pedidos (crear pedido CV + escaneo barcode) si la marca no está autorizada; **no bloquea**.
- Evaluar bloqueo solo luego de un período de operación sin incidencias.
- Agregar reporte de cobertura: ventas permitidas, fuera de cobertura, sin marca y grupos/marcas sin vendedor.
- Considerar asignación de metas por marca si una misma meta global deja de ser suficiente.
- En Grupos de marcas se muestra el conteo de modelos (`modelojf`) por grupo.

## 7. Checklist para Cursor / implementación

### Antes de programar

- [x] Confirmar la tabla y columnas de detalle que vinculan venta, vendedor, artículo, fecha e importe neto (`movimientosjf_AAAA.total` + `articulojf.id_marca`).
- [ ] Confirmar que `articulojf.id_marca` es la fuente confiable para todos los artículos vendibles.
- [x] Definir la fecha desde la cual aplicará el nuevo criterio: mes actual, con vigencias que podrán cambiar posteriormente.
- [x] Paralelo Fase 2: total oficial = cabecera `ventajf.neto`; permitido = suma líneas `movimientosjf`.
- [x] Cliente nuevo (Fase 3): cuenta solo si la 1ª compra del periodo es documento 100% permitido por marcas.
- [ ] Obtener la matriz inicial vendedor × grupo de marcas y validar los grupos con el propietario.
- [ ] Registrar los grupos iniciales y todas sus marcas.

### Base de datos

- [ ] Crear `docs/sql/asignacion-grupos-marcas-vendedores.sql` con las tres tablas, índices y comentarios propuestos.
- [ ] Implementar validación de fechas y prevención de solapamientos para vendedor–grupo dentro de transacción.
- [ ] Preparar un SQL de carga inicial de grupos, marcas por grupo y asignaciones, revisable antes de ejecutarlo.
- [ ] Hacer respaldo o export de la matriz inicial y dejar responsable/fecha de carga documentados.
- [ ] Verificar con consultas que no haya artículos vendidos sin marca.

### Módulo de administración

- [ ] Añadir permisos `grupos_marcas` y `asignacion_grupos_marcas`; entregar edición solo al propietario autorizado.
- [ ] Crear modelo, controlador, AJAX, vista, JS y ruta para grupos y asignaciones.
- [ ] Incluir filtros por vendedor, grupo, marca incluida, fecha y estado.
- [ ] Permitir alta múltiple de grupos para un vendedor.
- [ ] Implementar cierre por fecha, sin borrado físico de historia.
- [ ] Mostrar historial, observación y usuario que realizó el cambio.
- [ ] Validar permisos también en AJAX, no solo en la vista.

### Métricas y metas

- [ ] Implementar un único método reutilizable para ventas permitidas por vendedor/período/grupo/marca/modelo.
- [ ] Implementar cálculo de importes por línea para documentos con marcas mixtas.
- [ ] Añadir el diagnóstico de ventas fuera de cobertura y ventas sin marca.
- [ ] Usar el método en `mdlListarMetasPeriodo`.
- [ ] Usar el método en `mdlAvanceVentasDashboard`.
- [ ] Usar el método en `mdlVentaRealPorVendedor` de metas/retos.
- [ ] Ajustar el KPI de modelos para filtrar por grupo/marca permitida.
- [ ] Aplicar la misma regla a clientes nuevos y modelo especial.
- [ ] Actualizar etiquetas y ayudas de las pantallas para indicar “marcas permitidas”.

### Pruebas de aceptación

- [ ] Vendedor A con grupo X vigente: la venta de un modelo de una marca de X suma a su meta.
- [ ] Vendedor A sin grupo Y: la venta de un modelo de una marca de Y no suma y aparece como fuera de cobertura.
- [ ] Documento con X e Y: solo el importe de X suma; Y queda fuera de cobertura.
- [ ] Al cerrar un grupo el día 15: ventas hasta el 15 cumplen; desde el 16 no cumplen.
- [ ] Cambio de vendedor: cada venta se evalúa con el vendedor registrado en el documento y con el grupo vigente en esa fecha, no con el vendedor actual del cliente.
- [ ] Un período anterior mantiene su resultado con las asignaciones que estaban vigentes entonces.
- [ ] No hay doble conteo con dos asignaciones consecutivas ni con un intento de solapamiento.
- [ ] Total de líneas permitidas + fuera de cobertura + sin marca = total de líneas analizadas.
- [ ] Las metas, retos y el dashboard muestran el mismo monto permitido para la misma persona y período.
- [ ] Cobranza no cambia en el MVP y se muestra con una etiqueta que no la atribuye por marca.
- [ ] Se valida rendimiento con un mes completo y `EXPLAIN` de las consultas nuevas.

### Despliegue

- [ ] Ejecutar migración primero en una copia de base de datos.
- [ ] Cargar y revisar la matriz inicial antes de activar el cálculo oficial.
- [ ] Operar al menos un cierre mensual en modo paralelo y conciliar diferencias.
- [ ] Autorizar por escrito el cambio de cálculo oficial.
- [ ] Comunicar a vendedores y supervisores qué grupos/marcas cuentan desde qué fecha.
- [ ] Monitorear semanalmente ventas fuera de cobertura y corregir asignaciones/datos maestros.

## 8. Criterio de cierre del MVP

El MVP se considera terminado cuando Gestión comercial permite mantener grupos de marcas y asignaciones vigentes e históricas, y el avance de metas y retos por modelo suma únicamente las líneas cuyas marcas pertenecen a grupos autorizados en la fecha de venta. Cada diferencia queda explicada por grupo/marca como permitida o fuera de cobertura; no hay doble conteo ni alteración silenciosa de períodos históricos.

## 9. Definiciones confirmadas

1. Una marca puede estar disponible para varios vendedores y un vendedor puede atender varios grupos/marcas.
2. El criterio inicia en el mes actual. Las asignaciones podrán cambiar mediante vigencias durante el tiempo.
3. La meta es por modelo y cada modelo pertenece a una sola marca.
4. Una venta no autorizada no se toma en cuenta para metas ni retos.
5. Modelos, clientes nuevos y modelo especial se calculan solo para marcas permitidas.
6. La cobranza no se asigna ni se calcula por marcas.
7. La rotación se resuelve cerrando la vigencia del vendedor anterior y abriendo la del nuevo.
8. Marca y modelo son obligatorios para todo artículo.
9. Solo el propietario autorizado puede editar grupos y asignaciones.
10. **Fase 3 ventas:** KPI = líneas permitidas (`movimientosjf`) + NC descuento E05 sin unidades (cabecera, sin marca).
11. **Cliente nuevo:** cuenta solo si su primera compra en el periodo es un documento íntegramente permitido (todas las líneas con marca autorizada).
12. **E05:** con cantidad/líneas = devolución por marca; sin unidades = descuento al vendedor.

## 10. Matriz inicial de grupos y vendedores

Vigencia inicial: desde el primer día del mes actual. El código debe conservarse como texto, incluidos los valores alfanuméricos `24j` y `18a`.

| Grupo | Nombre | Marcas incluidas | Vendedores asignados |
|---|---|---|---|
| X | Grupo Jackyform | JACKYFORM, GUAPITAS | `04`, `05`, `19`, `24j`, `27`, `31` |
| Y | RosaFlor | ROSALINDA, ROSITAS | `32`, `30`, `18a` |

Antes de ejecutar la carga, validar que cada código exista en `maestrajf` con `tipo_dato = 'TVEND'`. La carga debe usar los códigos, no los nombres de vendedor.

Con esta matriz, Cursor puede preparar el SQL de carga inicial de grupos, sus marcas y las asignaciones de vendedor–grupo sin interpretar combinaciones ni nombres.
