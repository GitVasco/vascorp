# Arquitectura: categorías y subcategorías de modelos

**Estado:** v1 implementada (migración + pantalla de clasificación + ranking por subcategoría en ficha).  
**Alcance v1:** sin filtro “Por revisar”; sin admin de catálogo (solo semilla); sugerencia por `línea` orientativa (no autoasigna).  
**Alcance inicial:** clasificar únicamente modelos `ACTIVO` para que la Ficha gerencial compare modelos realmente equivalentes.  
**No confundir con:** `categorias-clientes`, que clasifica clientes y no productos.

## 1. Resultado esperado

Cada modelo activo tendrá, como máximo, una subcategoría vigente. Una subcategoría pertenece a una categoría; por tanto, elegir la subcategoría determina la categoría. La ficha mostrará un ranking de ventas contra los modelos activos de **la misma subcategoría**, no contra todo el grupo de marcas.

Ejemplo: un modelo asignado a `Brasieres > Deportivo` se compara solo con otros brasieres deportivos activos que tengan ventas válidas en el período. No se compara con fajas, trusas, ni con un brasier de lactancia.

Los modelos sin asignación no se deben incluir silenciosamente en otro ranking: la ficha debe mostrar `Sin subcategoría asignada` y enlazar a la pantalla de clasificación (si el usuario tiene permiso de edición).

## 2. Decisiones de arquitectura

1. **Diseño aditivo.** No agregar columnas ni cambiar flujos existentes de `modelojf`, `articulojf`, movimientos, costos o grupos de marcas. Se crean tablas nuevas y se integra el ranking mediante `LEFT/INNER JOIN` controlado.
2. **Una clasificación vigente por modelo.** La relación es modelo → subcategoría, no modelo → varias subcategorías. Es necesaria para que el universo comparativo sea inequívoco.
3. **Categoría derivada.** No guardar a la vez `id_categoria` e `id_subcategoria` en la asignación: se evita que una subcategoría de trusas termine vinculada a la categoría brasieres. La categoría se obtiene desde `subcategoria_modelojf.id_categoria`.
4. **Historial auditable.** La tabla vigente es rápida para la ficha; una tabla de historial deja evidencia de cada cambio. No se borra una asignación.
5. **Solo activos se editan.** La pantalla de mantenimiento solo lista `modelojf.estado = ACTIVO`. Si un modelo luego se inactiva, conserva su última clasificación e historial, pero no aparece para asignación ni participa en el ranking operativo.
6. **La categoría no reemplaza filtros existentes.** Marca, grupo comercial y período continúan disponibles como filtros informativos. El ranking principal de la ficha pasa a ser por subcategoría. Cualquier ranking por grupo de marcas debe conservar su etiqueta y endpoint propios para no cambiar su significado sin avisar.

## 3. Taxonomía inicial recomendada

La subcategoría debe significar la **forma o uso principal comparable**, no cada característica comercial. Por ejemplo, “con aro”, “encaje”, “algodón” o “sin costuras” pueden coexistir en varios productos; serán atributos futuros si se requieren, no subcategorías ahora.

| Categoría | Subcategorías iniciales | Regla de asignación |
|---|---|---|
| Trusas | Bikini / clásica; Cachetero; Hilo dental / tanga; Culotte / bóxer; Alta cintura / control suave; Menstrual / absorbente | Elegir por silueta principal. Si una prenda tiene control pero su silueta principal es cachetero, usar `Cachetero`; reservar `Alta cintura / control suave` para productos cuyo propósito principal es control leve. |
| Brasieres | Básico / copa suave; Copa preformada; Con aro; Push-up / realce; Deportivo; Lactancia; Strapless / multiuso; Postquirúrgico | Elegir por uso o construcción dominante. “Con aro” solo se usa si no corresponde mejor a push-up, strapless, lactancia, deportivo o postquirúrgico. |
| Fajas | Panty / alta cintura; Short / bermuda; Body entero; Chaleco / torso; Postparto; Postquirúrgica; Deportiva / modeladora activa | Elegir por cobertura y uso principal. `Postquirúrgica` prevalece sobre la forma; `Postparto` prevalece si fue diseñada específicamente para ese fin. |

### Reglas para evitar categorías pobres

- Cada modelo debe pertenecer a una sola subcategoría inicial. Si el producto realmente combina funciones, elegir el motivo de compra/uso principal y registrar la duda para una revisión posterior.
- No crear una subcategoría para un único modelo. Crear una solo cuando haya al menos tres modelos actuales o una decisión comercial explícita de medirla por separado.
- Mantener nombres entendibles para ventas y gerencia; usar un `codigo` técnico estable, por ejemplo `BRASIER_DEPORTIVO`, que no cambia aunque se ajuste el texto visible.
- La categoría “Otros” no se crea en la semilla inicial. Si aparece una necesidad real, dejar el modelo como pendiente hasta que se apruebe una clasificación; así no se contamina el ranking.
- Si posteriormente se necesitan ejes cruzados (por ejemplo, `con aro`, material, nivel de control, edad objetivo), implementar atributos de producto separados. No convertir una clasificación de un solo nivel en combinaciones como `Brasier deportivo con aro de encaje`.

## 4. Modelo de datos

```text
categoria_modelojf (catálogo raíz)
  1 ─── N subcategoria_modelojf (catálogo hoja)
                 1 ─── N modelo_subcategoria_historialjf
                 1 ─── N modelo_subcategoriajf (una fila vigente por modelo)

modelojf (catálogo existente; sin modificación)
  1 ─── 0..1 modelo_subcategoriajf
```

### 4.1 Tablas nuevas

| Tabla | Finalidad | Campos esenciales |
|---|---|---|
| `categoria_modelojf` | Catálogo de familias | `id`, `codigo`, `nombre`, `estado`, `orden`, fechas y usuarios de auditoría |
| `subcategoria_modelojf` | Catálogo de hojas de comparación | `id`, `id_categoria`, `codigo`, `nombre`, `estado`, `orden`, fechas y usuarios de auditoría |
| `modelo_subcategoriajf` | Asignación vigente, una fila por código de modelo | `modelo` (PK), `id_subcategoria`, `fecha_asignacion`, `usuario_asignacion`, `actualizado_en`, `usuario_actualizacion` |
| `modelo_subcategoria_historialjf` | Trazabilidad inmutable de altas/cambios | `id`, `modelo`, `id_subcategoria_anterior`, `id_subcategoria_nueva`, `accion`, `fecha`, `usuario_id`, `origen`, `observacion` |

`modelo` se guarda como `VARCHAR` y se usa `TRIM(modelo)` de forma consistente, porque el catálogo actual identifica los modelos por ese código. Antes de ejecutar la migración, Cursor debe confirmar la longitud y collation reales de `modelojf.modelo`; en el SQL de abajo se usa `VARCHAR(50)` como límite seguro inicial.

No añadir claves foráneas contra tablas legacy sin verificar primero tipos, motor y datos inconsistentes. Los índices y validaciones de aplicación son obligatorios; las FK pueden añadirse en una fase posterior si el diagnóstico confirma que no bloquearán la operación.

### 4.2 Migración SQL propuesta

Crear el archivo `docs/sql/categorias-subcategorias-modelos.sql`. Ejecutarlo primero en una copia de base de datos. **No alterar ni actualizar en masa `modelojf`.**

```sql
CREATE TABLE categoria_modelojf (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    codigo VARCHAR(50) NOT NULL,
    nombre VARCHAR(100) NOT NULL,
    estado TINYINT(1) NOT NULL DEFAULT 1,
    orden SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    creado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    creado_por INT NULL,
    actualizado_en DATETIME NULL DEFAULT NULL,
    actualizado_por INT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_categoria_modelojf_codigo (codigo),
    UNIQUE KEY uq_categoria_modelojf_nombre (nombre),
    KEY idx_categoria_modelojf_estado_orden (estado, orden, nombre)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE subcategoria_modelojf (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    id_categoria INT UNSIGNED NOT NULL,
    codigo VARCHAR(70) NOT NULL,
    nombre VARCHAR(100) NOT NULL,
    estado TINYINT(1) NOT NULL DEFAULT 1,
    orden SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    creado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    creado_por INT NULL,
    actualizado_en DATETIME NULL DEFAULT NULL,
    actualizado_por INT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_subcategoria_modelojf_codigo (codigo),
    UNIQUE KEY uq_subcategoria_modelojf_categoria_nombre (id_categoria, nombre),
    KEY idx_subcategoria_modelojf_categoria_estado_orden (id_categoria, estado, orden, nombre)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE modelo_subcategoriajf (
    modelo VARCHAR(50) NOT NULL,
    id_subcategoria INT UNSIGNED NOT NULL,
    fecha_asignacion DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    usuario_asignacion INT NULL,
    actualizado_en DATETIME NULL DEFAULT NULL,
    usuario_actualizacion INT NULL,
    PRIMARY KEY (modelo),
    KEY idx_modelo_subcategoriajf_subcategoria_modelo (id_subcategoria, modelo)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE modelo_subcategoria_historialjf (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    modelo VARCHAR(50) NOT NULL,
    id_subcategoria_anterior INT UNSIGNED NULL,
    id_subcategoria_nueva INT UNSIGNED NOT NULL,
    accion ENUM('ALTA', 'CAMBIO') NOT NULL,
    fecha DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    usuario_id INT NULL,
    origen VARCHAR(30) NOT NULL DEFAULT 'pantalla',
    observacion VARCHAR(250) NULL,
    PRIMARY KEY (id),
    KEY idx_modelo_subcategoria_historial_modelo_fecha (modelo, fecha),
    KEY idx_modelo_subcategoria_historial_nueva_fecha (id_subcategoria_nueva, fecha)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

La semilla debe ser idempotente usando `codigo` como identidad. Cursor puede usar `INSERT ... ON DUPLICATE KEY UPDATE nombre = VALUES(nombre), orden = VALUES(orden)` **sin cambiar `estado` automáticamente**, para no reactivar una categoría que un administrador desactivó.

Al final de la migración, insertar las tres categorías y las 21 subcategorías de la sección 3 con códigos en mayúscula y guion bajo. La semilla no asigna modelos: la asignación requiere revisión humana.

## 5. Pantalla: Clasificación de modelos

### Ruta y permisos

- Nueva ruta: `index.php?ruta=categorias-modelos`.
- Módulo de permisos: `gestion_comercial.categorias_modelos`.
- Acciones: `ver` para consultar y `editar` para asignar, administrar catálogos y ver historial.
- Solo se agrega al menú de Gestión comercial para usuarios con `ver`. Desde la ficha, el enlace de “Clasificar” solo se muestra a usuarios con `editar`.

### Experiencia de uso requerida

La pantalla debe servir para clasificar decenas o cientos de modelos sin abrir un modal por cada uno:

1. Cabecera fija: contador `Pendientes / Activos`, búsqueda por código/nombre, filtro de marca, filtro de categoría, filtro de subcategoría y selector `Pendientes | Todos clasificados | Por revisar`.
2. Cuerpo en tabla virtual/paginada por servidor (50 filas iniciales; búsqueda con debounce de 250–300 ms). Cada fila incluye imagen pequeña, código, nombre, marca y un único selector de subcategoría agrupado por categoría.
3. La primera vista predeterminada es `Pendientes`, ordenada por código. Esto permite terminar la migración sin perder modelos.
4. Al elegir una subcategoría, guardar de inmediato por AJAX y actualizar el contador. No usar un botón global “Guardar todo”: evita perder trabajo y permite auditoría por cambio.
5. Antes de cambiar una asignación existente, mostrar confirmación breve con el cambio exacto: `10273: Brasieres > Deportivo → Brasieres > Push-up`. Las asignaciones nuevas no requieren confirmación.
6. Mostrar badge de `asignado por / fecha` y botón de historial solo a quien puede editar. Si la llamada falla, devolver el selector a su valor anterior y mostrar un mensaje visible.
7. No devolver modelos inactivos en `listar`, `asignar` ni `pendientes`. El backend debe verificarlo; ocultarlo en JavaScript no basta.

La administración de categorías/subcategorías puede ser una pestaña secundaria de esta misma ruta. Al desactivar una subcategoría, impedirlo si tiene modelos activos asignados; ofrecer antes la reasignación masiva explícita. Nunca eliminar catálogos que tengan historial.

## 6. Contrato backend y archivos

Seguir el patrón del módulo de ficha: vista PHP liviana, JS por AJAX, controlador que valida y modelo PDO con sentencias preparadas.

| Archivo nuevo o modificado | Responsabilidad |
|---|---|
| `controladores/categorias-modelos.controlador.php` | Validación, permisos, respuestas JSON y transacciones de asignación. |
| `modelos/categorias-modelos.modelo.php` | Consultas de catálogo, listado paginado, asignación e historial. |
| `ajax/categorias-modelos.ajax.php` | Solo POST; mapa de acciones; `Content-Type: application/json`; permiso antes de ejecutar. |
| `vistas/modulos/categorias-modelos.php` | Contenedores, filtros y permiso de vista. |
| `vistas/js/categorias-modelos.js` | Carga incremental, debounce, selector y restauración ante error. |
| `vistas/css/categorias-modelos.css` | Filtros fijos, filas compactas y estado de guardado. |
| `index.php`, `vistas/plantilla.php`, `vistas/modulos/menu.php` | Registrar controlador/modelo, ruta, CSS/JS, título y acceso de menú. |
| `controladores/permisos-modulos.json` | Declarar `gestion_comercial.categorias_modelos.ver` y `.editar` con usuarios autorizados por negocio. |
| `modelos/ficha-gerencial-modelos.modelo.php` | Reemplazar el universo de `mdlRankingGeneral` por la subcategoría vigente. |

Acciones AJAX mínimas:

| Acción | Permiso | Entrada | Salida |
|---|---|---|---|
| `listar` | `ver` | `q`, `id_marca`, `id_categoria`, `id_subcategoria`, `estado_lista`, `pagina`, `limite` | filas activas, total y conteos de pendientes. |
| `catalogo` | `ver` | — | categorías y subcategorías activas. |
| `asignar` | `editar` | `modelo`, `id_subcategoria`, `observacion` opcional | clasificación vigente, actor y fecha. |
| `historial` | `editar` | `modelo` | cambios ordenados por fecha descendente. |
| `guardarCategoria` / `guardarSubcategoria` | `editar` | datos validados | catálogo actualizado. |

Para `asignar`, el controlador debe ejecutar una transacción:

1. Validar formato del modelo y que existe en `modelojf` con estado `ACTIVO`.
2. Bloquear/leer la asignación actual (`SELECT ... FOR UPDATE`).
3. Validar que la subcategoría existe y está activa, y obtener su categoría.
4. Si no cambia, responder éxito idempotente sin crear historial duplicado.
5. Insertar o actualizar `modelo_subcategoriajf`.
6. Insertar `ALTA` o `CAMBIO` en historial con `$_SESSION['id']`.
7. Confirmar transacción. Ante cualquier error, rollback y respuesta no exitosa.

Todos los límites, página y filtros se validan en el servidor. No concatenar búsqueda, ordenamiento ni IDs en SQL; utilizar una lista blanca para los únicos órdenes permitidos.

## 7. Integración del ranking de la Ficha gerencial

### Cambio funcional

El endpoint actual `accion=ranking` llama a `ModeloFichaGerencialModelos::mdlRankingGeneral()`, que hoy toma un grupo de marcas. Modificar **solo este ranking de ficha** para usar la subcategoría de la tabla vigente.

El algoritmo exacto es:

1. Obtener la subcategoría vigente del modelo seleccionado y su categoría.
2. Si no existe, responder `estado: "sin_clasificacion"`, posición nula y un mensaje accionable; no ejecutar la agregación pesada.
3. Buscar modelos de `modelojf` en estado `ACTIVO` enlazados a esa misma subcategoría.
4. Agregar ventas netas válidas del período usando las reglas ya centralizadas: `sqlTiposVenta('m')`, `sqlCabeceraValida('m')`, fechas y particiones seguras mediante `mdlSqlFuenteMovimientos()`.
5. El universo (`total_modelos_con_venta`) contiene solo modelos de esa subcategoría con ventas netas distintas de cero en el período. El modelo elegido sin ventas recibe posición nula, pero la respuesta conserva el total para explicarlo.
6. Ordenar por **venta neta monetaria descendente**; ante empate, unidades vendidas descendentes y código de modelo ascendente. Es la definición acordada para “ranking comparativo”. No usar precio de lista 9 para ranking, porque no representa necesariamente la venta efectiva.
7. Informar categoría, subcategoría, métrica, período y tamaño del universo en la respuesta.

Contrato sugerido:

```json
{
  "ok": true,
  "ranking_general": {
    "estado": "ok",
    "posicion": 2,
    "total_modelos_con_venta": 8,
    "categoria": {"id": 2, "codigo": "BRASIER", "nombre": "Brasieres"},
    "subcategoria": {"id": 10, "codigo": "BRASIER_DEPORTIVO", "nombre": "Deportivo"},
    "metrica": "venta_neta",
    "ventas_netas_modelo": 18450.35
  }
}
```

En estado `sin_clasificacion`, `categoria`, `subcategoria` y `posicion` son `null`, `total_modelos_con_venta` es `0`, y se devuelve un texto de interfaz, por ejemplo: `Este modelo aún no está clasificado; no se puede comparar de forma confiable.`

### Consulta de referencia

La implementación real debe mantener la construcción segura de la fuente de movimientos que ya existe. Conceptualmente, la consulta es:

```sql
SELECT TRIM(a.modelo) AS modelo,
       SUM(IFNULL(m.total, 0)) AS venta_neta,
       SUM(IFNULL(m.cantidad, 0)) AS unidades_vendidas
FROM movimientos_validos m
INNER JOIN articulojf a ON a.articulo = m.articulo
INNER JOIN modelojf mo ON TRIM(mo.modelo) = TRIM(a.modelo)
INNER JOIN modelo_subcategoriajf ms ON ms.modelo = TRIM(mo.modelo)
WHERE mo.estado = 'ACTIVO'
  AND ms.id_subcategoria = :id_subcategoria
  AND m.fecha >= :inicio AND m.fecha < :fin
  AND reglas_de_venta_validas
GROUP BY TRIM(a.modelo)
HAVING SUM(IFNULL(m.total, 0)) <> 0
ORDER BY venta_neta DESC, unidades_vendidas DESC, modelo ASC;
```

No incorporar el filtro de `articulojf.estado = ACTIVO` a la venta histórica: la ficha ya decidió correctamente mantener ventas de SKU inactivos para conciliar los KPI. La condición de activo aplica al **modelo que participa hoy en el ranking**, no a cada SKU histórico.

### UI de la ficha

- En la cabecera añadir `Categoría: Brasieres · Subcategoría: Deportivo`.
- Cambiar la leyenda bajo el KPI de `Grupo comercial · de N modelos` por `Brasieres › Deportivo · ventas netas · de N modelos`.
- Si no hay clasificación, el KPI muestra `—` y la leyenda explica que no se calcula hasta clasificar. No mostrar `Sin grupo o ventas`, pues sería incorrecto.
- Mantener la carga AJAX independiente para que un problema en clasificación no impida cargar tarjetas, variantes o evolución.

## 8. Datos históricos y cambios de clasificación

Para la primera entrega, el ranking se interpreta con la **clasificación vigente al momento de consultar**. Esto es deliberado: clasifica el catálogo actual y permite comparar el portafolio actual sin reescribir ventas antiguas.

El historial no se usa todavía para recalcular rankings pasados. Si negocio exige que un reporte de enero conserve exactamente la clasificación que existía en enero, se implementará una Fase 2 de vigencias (`vigente_desde`/`vigente_hasta`) y se evaluará la categoría correspondiente a la fecha del período. No anticipar esa complejidad antes de que se necesite; sí conservar el historial desde el día uno.

## 9. Plan de entrega seguro

1. **Diagnóstico:** respaldar base, verificar motor/collation/longitud de `modelojf.modelo`, revisar modelos activos duplicados tras `TRIM`, y contar activos por marca. No desplegar si hay códigos ambiguos.
2. **Migración:** crear tablas, índices y semilla en staging; validar que no modifica tablas existentes.
3. **Mantenimiento:** implementar ruta, permisos y AJAX; probar que un inactivo no se lista ni se puede asignar por llamada manual.
4. **Carga humana:** clasificar primero el 100 % de los modelos activos de las tres categorías. Revisar los pendientes con negocio; no usar inferencia automática por nombre en producción.
5. **Ranking:** integrar el contrato nuevo y mantener los grupos de marca para los paneles que aún los usen. Probar modelos de cada subcategoría, un modelo sin clasificación, un modelo sin ventas y empates.
6. **Activación:** mostrar el ranking por subcategoría solo cuando la clasificación esté completa o cuando la ficha explique claramente el estado pendiente. Medir tiempos con `EXPLAIN` y consultas reales.

## 10. Criterios de aceptación y pruebas

- Un usuario sin `categorias_modelos.ver` no puede abrir la ruta ni consumir `listar` o `catalogo`.
- Un usuario con `ver` pero sin `editar` puede consultar, pero no asignar ni ver acciones de cambio.
- Un modelo `INACTIVO` no aparece en ninguna búsqueda de asignación y `asignar` devuelve error incluso si se llama directo por AJAX.
- Cada modelo activo tiene a lo sumo una fila en `modelo_subcategoriajf`; una reasignación deja una única fila vigente y una fila adicional de historial.
- No es posible elegir una combinación inconsistente categoría/subcategoría porque el usuario selecciona solo subcategoría y el servidor deriva la categoría.
- Dos clics iguales no generan dos historiales.
- La ficha de un modelo clasificado reporta únicamente modelos activos de la misma subcategoría y ventas válidas del período.
- La ficha de un modelo sin clasificación no devuelve un ranking de grupo de marcas como sustituto.
- Ventas de un SKU inactivo cuentan para el modelo, según la regla actual de la ficha; un modelo inactivo no entra al universo comparativo.
- El orden de desempate es determinista: venta neta, unidades, código.
- La búsqueda y primera carga de clasificación no supera el tiempo acordado en staging; revisar `EXPLAIN` del índice `idx_modelo_subcategoriajf_subcategoria_modelo` y de las consultas de movimientos.

## 11. Fuera de alcance de esta entrega

- Clasificación de artículos/SKU por color, talla o variante.
- Clasificaciones múltiples por modelo y atributos cruzados.
- Reclasificación automática con IA o por coincidencia de nombres.
- Recalcular históricos con la categoría que existía en el pasado.
- Cambiar los rankings del resumen/comparación gerencial que hoy usan grupos de marcas. Esos cambios deben ser un requerimiento posterior, con definición propia de universo y métrica.
