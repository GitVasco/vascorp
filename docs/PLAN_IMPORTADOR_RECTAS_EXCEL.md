# Plan de implementación: importador de rectas desde Excel

**Estado:** listo para desarrollar, con una decisión funcional pendiente.  
**Objetivo:** que un usuario pueda cargar muchas rectas desde una plantilla Excel en tres pasos: descargar plantilla, arrastrar archivo y confirmar registros validados.

> **Decisión funcional necesaria antes de empezar la Fase 2:** el repositorio no contiene hoy un módulo, tabla ni definición de negocio llamada `rectas`. El responsable funcional debe confirmar qué representa una recta y aprobar la tabla de columnas de la sección 2. Este plan usa `recta` como la entidad destino; no se debe adivinar ni reutilizar una tabla de producción existente sin esa confirmación.

## 1. Principios de la solución

- **El Excel no escribe directamente en producción.** Primero se analiza y se presenta una previsualización; solo el botón `Importar N rectas válidas` ejecuta la transacción.
- **Sin mapeo manual de columnas.** La plantilla descargable fija los encabezados. El importador acepta diferencias inocuas de mayúsculas, tildes y espacios, pero rechaza columnas obligatorias ausentes.
- **Corrección accionable.** Cada fila inválida muestra número de fila, columna, valor recibido y cómo corregirlo. También se ofrece descargar el reporte de errores en CSV.
- **Reintentos seguros.** Cada importación tiene un identificador, hash del archivo y auditoría. Las filas duplicadas se detectan antes de guardar y no generan inserciones repetidas.
- **Primero el camino fácil.** Una sola hoja (`Rectas`), encabezados en la fila 1, hasta 5 MB y 5 000 filas por carga inicial. No se solicitan códigos internos que el usuario no conoce si se pueden resolver desde el catálogo.
- **Compatibilidad con el proyecto.** Usar la librería PHPExcel ya disponible en `vistas/reportes_excel/Classes/PHPExcel.php`; aceptar `.xlsx` y `.xls`. CSV se puede aceptar como alternativa, pero la experiencia principal es Excel.

## 2. Contrato de la plantilla (debe ser aprobado)

Cursor debe crear una plantilla `.xlsx` con una hoja llamada `Rectas`, instrucciones breves en una segunda hoja protegida llamada `Ayuda`, validación visual de columnas obligatorias y una fila de ejemplo que el usuario debe reemplazar.

Antes de desarrollar la persistencia, completar y aprobar esta tabla. Los nombres de encabezado definidos aquí son el contrato estable entre Excel y la aplicación.

| Encabezado Excel | Obligatorio | Tipo/regla | Campo destino | Ejemplo |
|---|---:|---|---|---|
| `codigo` | Sí | Texto único que identifica la recta | `codigo` | `REC-000123` |
| `descripcion` | Sí | Texto, máximo 255 caracteres | `descripcion` | `Recta 25 mm negra` |
| `categoria` | Por decidir | Catálogo existente o texto controlado | `categoria_id` / `categoria` | `Producción` |
| `unidad_medida` | Por decidir | Catálogo existente | `unidad_medida_id` | `UND` |
| `cantidad` | Por decidir | Decimal positivo, máximo 4 decimales | `cantidad` | `25.5000` |
| `observacion` | No | Texto, máximo 500 caracteres | `observacion` | `Lote julio` |

Reglas adicionales que se deben decidir y codificar en constantes del módulo, no dispersas en JavaScript:

1. Definir la clave única real: ¿`codigo` global, `codigo + empresa`, o una combinación de columnas?
2. Elegir el comportamiento de un código ya existente: `rechazar`, `actualizar solo campos permitidos` o `omitir y avisar`. Para el MVP se recomienda **rechazar**, por ser el comportamiento más seguro.
3. Definir qué catálogos se crean automáticamente. Recomendación: no crear catálogos; informar el valor inexistente y ofrecer descargar la lista válida.
4. Confirmar si las rectas son solo un maestro o si generan movimientos/stock. El MVP debe limitarse al maestro; movimientos requieren un flujo y aprobación independientes.

## 3. Experiencia del usuario

La nueva acción `Importar rectas` se ubica en la pantalla donde hoy se administran las rectas, visible solo con permiso de edición.

1. El usuario pulsa **Descargar plantilla**, llena la hoja `Rectas` y guarda el archivo.
2. Arrastra el `.xlsx` al área de carga o usa **Seleccionar archivo**. La interfaz indica de inmediato archivo, tamaño y cantidad de filas leídas.
3. El sistema muestra una previsualización paginada con estados: `Lista para importar`, `Con error` y `Duplicada`.
4. Si hay errores, el usuario descarga el reporte, corrige el mismo archivo y lo vuelve a cargar. No se permite confirmar mientras existan filas inválidas, evitando importaciones parciales involuntarias.
5. Si todo está válido, ve un resumen: “120 nuevas, 0 actualizaciones, 0 errores”. Confirma con **Importar 120 rectas**.
6. Se muestra el resultado, el número de lote y enlaces a la lista de rectas y al reporte de importación.

Para cargas grandes, el botón queda deshabilitado mientras procesa; una operación repetida por doble clic debe ser idempotente mediante `token_importacion` de un solo uso.

## 4. Arquitectura y archivos a crear

Los nombres se ajustan al módulo real cuando se confirme su ubicación, manteniendo la separación actual `vista → JS → AJAX → controlador → modelo`.

| Capa | Archivo propuesto | Responsabilidad |
|---|---|---|
| SQL | `docs/sql/rectas-importador.sql` | Tablas de lote, detalle de errores y, solo si no existe, maestro de rectas. |
| Vista | `vistas/rectas.php` y modal de importación | Botón, carga de archivo, resumen, grilla y confirmación. |
| Cliente | `vistas/js/rectas.js` | `FormData`, render de previsualización, descarga de plantilla/errores y bloqueo contra doble envío. |
| Endpoint | `ajax/rectas.ajax.php` | Enrutamiento JSON y comprobación inicial de permiso. |
| Servicio | `controladores/rectas-importador.controlador.php` | Validación de entrada, orquestación de análisis/confirmación y contratos de respuesta. |
| Persistencia | `modelos/rectas-importador.modelo.php` | Consultas parametrizadas, catálogos y transacciones. |
| Lector | `controladores/rectas-excel-lector.php` | Adaptador aislado de PHPExcel, normalización de encabezados y conversión de celdas. |
| Permisos | `controladores/permisos-modulos.json` | Añadir `ver` y `editar` al módulo que contenga rectas. |

No incrustar la lectura de Excel en el AJAX ni SQL en la vista. El lector debe devolver un arreglo normalizado, sin conocer la base de datos; el controlador aplica las reglas de negocio y el modelo persiste.

## 5. Modelo de datos de importación

Si el maestro de rectas ya existe, **no duplicarlo**: adaptar las columnas de negocio y conservar solo las tablas de auditoría. Si no existe, crear primero el maestro validado por negocio.

```sql
CREATE TABLE rectas_importacionjf (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    token CHAR(36) NOT NULL,
    nombre_archivo VARCHAR(255) NOT NULL,
    hash_archivo CHAR(64) NOT NULL,
    total_filas INT NOT NULL DEFAULT 0,
    filas_validas INT NOT NULL DEFAULT 0,
    filas_error INT NOT NULL DEFAULT 0,
    estado ENUM('analizado','confirmado','fallido','expirado') NOT NULL DEFAULT 'analizado',
    usuario_id INT NOT NULL,
    creado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    confirmado_en DATETIME NULL,
    UNIQUE KEY uk_rectas_importacion_token (token),
    KEY idx_rectas_importacion_hash_usuario (hash_archivo, usuario_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE rectas_importacion_errorjf (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    importacion_id BIGINT NOT NULL,
    fila_excel INT NOT NULL,
    columna VARCHAR(80) NULL,
    valor VARCHAR(1000) NULL,
    codigo_error VARCHAR(80) NOT NULL,
    mensaje VARCHAR(500) NOT NULL,
    KEY idx_rectas_error_importacion (importacion_id),
    CONSTRAINT fk_rectas_error_importacion
      FOREIGN KEY (importacion_id) REFERENCES rectas_importacionjf(id)
      ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
```

Toda fila creada o actualizada debe guardar `usuario_registro`/`fecha_registro` y, si ya existe la auditoría del maestro, una entrada que refiera `importacion_id`. No almacenar el Excel permanentemente en el MVP; conservar hash, nombre y errores es suficiente. El archivo temporal se elimina al terminar el análisis.

## 6. Contratos AJAX

El endpoint debe responder siempre JSON UTF-8 con `ok`, `mensaje` y datos solo cuando corresponda. Acciones:

| Acción | Entrada | Respuesta exitosa |
|---|---|---|
| `descargarPlantilla` | — | Descarga `.xlsx`; validación de permiso `ver`. |
| `analizarImportacion` | `archivo` multipart | `token`, conteos, columnas encontradas, primeras 100 filas y errores. |
| `detalleImportacion` | `token`, `pagina` | Filas y errores paginados; verifica que el lote pertenece al usuario. |
| `confirmarImportacion` | `token` | Conteos definitivos e IDs/estado del lote. |
| `reporteErrores` | `token` | Descarga CSV con el detalle de errores. |

`analizarImportacion` requiere `editar`; `confirmarImportacion` también. El token expira en 24 horas y solo puede confirmarse una vez. No aceptar IDs de rectas enviados por el navegador como fuente de verdad.

## 7. Algoritmo de validación e importación

### Análisis

1. Verificar sesión, permiso, `UPLOAD_ERR_OK`, extensión/MIME permitido, tamaño máximo y límite de filas.
2. Abrir exclusivamente la hoja `Rectas`. Rechazar libro protegido, corrupto o sin datos.
3. Leer encabezados de la fila 1, normalizarlos (trim, minúsculas, quitar tildes) y compararlos contra el contrato. Rechazar encabezados obligatorios ausentes o repetidos.
4. Desde fila 2, ignorar solo filas completamente vacías. Conservar siempre el número de fila original de Excel.
5. Convertir texto, fechas y números de forma explícita; nunca convertir importes con `float` antes de persistir. Normalizar decimales `,`/`.` con una función central y devolver cadenas decimales de precisión fija.
6. Validar los campos de cada fila, sus referencias de catálogo y duplicados dentro del archivo.
7. Consultar las claves existentes en lotes, no una consulta por fila. Aplicar la política de duplicados aprobada.
8. Crear lote `analizado`, guardar errores y devolver la previsualización. No insertar ni modificar rectas en esta etapa.

### Confirmación

1. Obtener el lote por `token`, usuario y estado `analizado`; bloquearlo para evitar dos confirmaciones simultáneas.
2. Revalidar que no tenga errores y que no haya expirado.
3. Abrir transacción. Volver a comprobar claves únicas por si otro usuario creó una recta entre análisis y confirmación.
4. Insertar/actualizar en bloques con sentencias preparadas y registrar auditoría con el `importacion_id`.
5. Marcar el lote `confirmado` y guardar la fecha dentro de la misma transacción. Si falla una fila o consulta, hacer `ROLLBACK`, marcar `fallido` fuera de la transacción y no dejar datos a medio importar.
6. Devolver conteos reales. La interfaz refresca la lista de rectas solo después de `ok: true`.

## 8. Seguridad, rendimiento y observabilidad

- Comprobar permisos tanto en la vista como en cada acción AJAX. No confiar en que el botón esté oculto.
- Guardar archivos solo en una ruta temporal no pública, con nombre aleatorio; no usar el nombre recibido como ruta.
- Limitar a 5 MB / 5 000 filas en MVP y devolver un mensaje que invite a dividir el archivo. Si el negocio necesita más, implementar procesamiento asíncrono en una fase posterior.
- Usar parámetros PDO en todas las consultas y escapar las salidas al renderizar la previsualización.
- Registrar excepciones técnicas en el log del servidor con `token` e `importacion_id`; mostrar al usuario un mensaje seguro sin trazas SQL.
- El hash SHA-256 más usuario permite avisar si exactamente el mismo archivo ya fue confirmado, sin bloquear necesariamente una corrección legítima.

## 9. Fases de desarrollo para Cursor

### Fase R0 — Descubrimiento y contrato (obligatoria)

- [ ] Identificar la pantalla, ruta, tabla y permisos reales de las rectas.
- [ ] Completar la tabla de la sección 2 y aprobar clave única/política de duplicados.
- [ ] Verificar con un archivo de ejemplo real que PHPExcel instalado lee el formato requerido; si no lee `.xlsx`, documentar el bloqueo y proponer la dependencia compatible antes de seguir.

**Salida:** decisión funcional escrita y plantilla final aprobada. No construir importación antes de esta salida.

### Fase R1 — Base segura y plantilla

- [ ] Crear migración de auditoría y adaptar/crear el maestro aprobado.
- [ ] Implementar permisos, ruta, modal y descarga de plantilla.
- [ ] Implementar lector aislado de `.xlsx`/`.xls` y pruebas con plantilla vacía, válida y corrupta.

### Fase R2 — Análisis y corrección

- [ ] Implementar `analizarImportacion`, validaciones, detección de duplicados y persistencia de lote/errores.
- [ ] Mostrar resumen, grilla paginada y descarga CSV de errores.
- [ ] Asegurar que analizar un archivo no cambia ninguna recta.

### Fase R3 — Confirmación transaccional

- [ ] Implementar confirmación de un token, revalidación y transacción total.
- [ ] Aplicar la política aprobada para duplicados y registrar auditoría.
- [ ] Agregar estado final, mensajería clara e idempotencia de doble clic/reintento.

### Fase R4 — Calidad y salida

- [ ] Pruebas manuales y automatizadas de los casos de aceptación.
- [ ] Crear guía corta para usuario con capturas cuando la vista exista.
- [ ] Revisar permisos con un usuario sin acceso y otro con solo lectura.

## 10. Criterios de aceptación

- [ ] Un usuario autorizado puede descargar una plantilla y cargar 100 rectas válidas en un único flujo de tres pasos.
- [ ] El sistema acepta `.xlsx` y `.xls` válidos, rechaza archivos corruptos, hoja errónea, tamaño excedido y encabezados incompletos con mensajes claros.
- [ ] Una fila con dato inválido identifica exactamente la fila y la columna, y puede descargarse en CSV.
- [ ] Una carga con al menos un error no modifica ninguna recta.
- [ ] Los duplicados dentro del archivo y contra la base siguen exactamente la política aprobada y son visibles en la previsualización.
- [ ] Confirmar importa todo o nada; una falla no deja filas parciales.
- [ ] Reenviar el mismo token o doble clic no duplica registros.
- [ ] Cada importación confirmada puede rastrearse por usuario, fecha, archivo (hash), conteos y lote.
- [ ] Un usuario sin permiso no puede analizar, confirmar ni descargar información del lote por AJAX.

## 11. Prompt de ejecución para Cursor

```text
Implementa el módulo “Importador de rectas desde Excel” siguiendo estrictamente
docs/PLAN_IMPORTADOR_RECTAS_EXCEL.md. Antes de escribir código, completa la Fase R0:
localiza la entidad real “rectas”, confirma sus columnas y clave única con el responsable
funcional, y no supongas una tabla destino. Mantén la arquitectura actual del proyecto
(vista → JS → AJAX → controlador → modelo), usa PHPExcel ya incluido y no agregues
dependencias sin justificarlo. La subida solo debe analizar y previsualizar; la escritura
ocurre únicamente al confirmar un token, dentro de una transacción total. Implementa los
contratos, permisos, auditoría, validaciones y criterios de aceptación del documento.
Entrega migración SQL, archivos modificados/creados, pruebas ejecutadas y cualquier
decisión funcional pendiente.
```
