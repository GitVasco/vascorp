# Scripts de Inventario - Documentación

## Flujo del Proceso

```
Orden de Corte → Almacén de Corte → Entaller (Taller/Servicio) → Cierre
```

## Estructura de Tablas Necesarias

### 1. Orden de Corte (ordencortejf)

-   **Campos principales:**
    -   `codigo` (INT) - Código único de la orden
    -   `usuario` (INT) - ID del usuario
    -   `total` (INT) - Total de artículos
    -   `saldo` (INT) - Saldo pendiente
    -   `configuracion` (VARCHAR) - Configuración
    -   `estado` (VARCHAR) - Estado (Pendiente, Parcial, Cerrado)
    -   `fecha` (TIMESTAMP) - Fecha de creación

### 2. Detalle Orden de Corte (detalles_ordencortejf)

-   **Campos principales:**
    -   `id` (AUTO_INCREMENT)
    -   `ordencorte` (INT) - FK a ordencortejf.codigo
    -   `articulo` (VARCHAR) - Código del artículo
    -   `cantidad` (INT) - Cantidad
    -   `saldo` (INT) - Saldo pendiente
    -   `estado` (VARCHAR) - Estado

### 3. Almacén de Corte (almacencortejf)

-   **Campos principales:**
    -   `codigo` (INT) - Código único del almacén
    -   `guia` (VARCHAR) - Número de guía
    -   `usuario` (INT) - ID del usuario
    -   `total` (INT) - Total de artículos
    -   `estado` (INT) - Estado (1=Procesado, 0=Sistemas)
    -   `fecha` (TIMESTAMP) - Fecha de creación

### 4. Detalle Almacén de Corte (almacencorte_detallejf)

-   **Campos principales:**
    -   `id` (AUTO_INCREMENT)
    -   `almacencorte` (INT) - FK a almacencortejf.codigo
    -   `ordcorte` (INT) - FK a ordencortejf.codigo
    -   `detordcorte` (INT) - FK a detalles_ordencortejf.id
    -   `articulo` (VARCHAR) - Código del artículo
    -   `cantidad` (INT) - Cantidad física
    -   `saldo_taller` (INT) - Saldo disponible para taller

## Formato CSV Requerido - Almacén de Corte

Para procesar el inventario de **Almacén de Corte**, necesito un CSV simple con las siguientes columnas:

### Columnas Requeridas:

1. **articulo** (VARCHAR) - Código del artículo
2. **cantidad** (INT) - Cantidad física encontrada

### Notas Importantes:

-   **Un solo almacén**: Todos los artículos del CSV pertenecen al mismo almacén de corte
-   **Agrupación automática**: Si un artículo aparece varias veces, las cantidades se suman automáticamente
-   **Códigos generados**: El script genera automáticamente:
    -   Código de almacén de corte (último + 1)
    -   Código de orden de corte (último + 1)
    -   Número de guía (formato: GUIA-YYYYMMDD-XXX)
-   **Configuración**: Se usa automáticamente "INV-YYYYMMDD" basado en la fecha actual
-   **Usuario**: Se usa el ID 6 por defecto
-   **Fecha**: Se usa la fecha actual del sistema

### Ejemplo de CSV:

```csv
articulo,cantidad
ART001,50
ART002,30
ART003,25
ART001,10
```

En este ejemplo, ART001 tendrá un total de 60 unidades (50 + 10).

## Lógica del Script

Cuando procesamos el CSV de **Almacén de Corte**:

1. **Leer CSV** - Leer todas las filas y agrupar artículos (sumar cantidades si se repiten)
2. **Generar códigos:**
    - Obtener último código de orden de corte → nuevo código = último + 1
    - Obtener último código de almacén de corte → nuevo código = último + 1
    - Generar guía automáticamente
3. **Crear Orden de Corte:**
    - Crear cabecera en `ordencortejf`
    - Crear detalles en `detalles_ordencortejf` (uno por artículo)
    - Actualizar `ord_corte` en `articulojf` (sumar)
4. **Crear Almacén de Corte:**
    - Crear cabecera en `almacencortejf`
    - Crear detalles en `almacencorte_detallejf` vinculados a la orden de corte
    - Actualizar stocks en `articulojf`:
        - Sumar a `alm_corte`
        - Restar de `ord_corte`
    - Actualizar saldos en `detalles_ordencortejf`
    - Actualizar estados de ordenes de corte (parcial/cerrado)

## Uso del Script

1. **Preparar el CSV:**

    - Crear archivo `almacen_corte.csv` en `/scripts/csv/`
    - Formato: `articulo,cantidad` (con encabezados)
    - Una fila por artículo

2. **Ejecutar el script:**

```bash
cd /Users/joel/Proyectos/vascorp
php scripts/procesar_almacen_corte.php
```

3. **El script:**
    - Valida el formato del CSV
    - Muestra resumen de artículos encontrados
    - Crea orden de corte automáticamente
    - Crea almacén de corte vinculado
    - Actualiza todos los stocks
    - Muestra resumen final con códigos generados

## Ejemplo de Ejecución

```bash
$ php scripts/procesar_almacen_corte.php

============================================
PROCESADOR DE INVENTARIO - ALMACÉN DE CORTE
============================================

Leyendo archivo CSV...
Artículos encontrados: 3
Total de unidades: 105

Obteniendo códigos disponibles...
Nueva Orden de Corte: 1001
Nuevo Almacén de Corte: 501
Guía generada: GUIA-20241215-042

Creando Orden de Corte...
✓ Orden de Corte creada exitosamente

Creando Almacén de Corte...
✓ Almacén de Corte creado exitosamente

============================================
PROCESO COMPLETADO EXITOSAMENTE
============================================

Resumen:
  - Orden de Corte: 1001
  - Almacén de Corte: 501
  - Guía: GUIA-20241215-042
  - Artículos procesados: 3
  - Total unidades: 105
```

---

## Script: Procesar Inventario de Taller

### Formato CSV Requerido - Taller

Para procesar el inventario de **Taller**, necesito un CSV con las siguientes columnas:

### Columnas Requeridas:

1. **articulo** (VARCHAR) - Código del artículo
2. **cantidad** (INT) - Cantidad física encontrada en taller

### Notas Importantes:

-   **Grupo independiente**: Este proceso crea SIEMPRE orden de corte y almacén de corte nuevos (no se relaciona con otros procesos)
-   **Un solo taller**: Solo existe un taller interno único de la empresa (TALLER01)
-   **Agrupación automática**: Si un artículo aparece varias veces, las cantidades se suman
-   **Códigos generados**: El script genera automáticamente:
    -   Código de orden de corte (último + 1)
    -   Código de almacén de corte (último + 1)
    -   Guía de almacén (formato: GUIA-TALLER-YYYYMMDD-XXX)
    -   Guía de taller (formato: GUIA-TALLER-YYYYMMDD-XXX)
-   **Configuración**: Se usa automáticamente "INV-TALLER-YYYYMMDD"
-   **Usuario**: Se usa el ID 6 por defecto
-   **Fecha**: Se usa la fecha actual del sistema
-   **Taller**: Siempre se usa TALLER01 (taller interno único)

### Ejemplo de CSV:

```csv
articulo,cantidad
ART001,50
ART002,30
ART003,25
ART001,10
```

En este ejemplo:

-   ART001 tendrá 60 unidades en total (50 + 10) para crear orden/almacén de corte
-   Todos los artículos se registrarán en el taller interno único (TALLER01)

### Lógica del Script

Cuando procesamos el CSV de **Taller**:

1. **Leer CSV** - Leer todas las filas y agrupar artículos únicos
2. **Generar códigos:**
    - Obtener último código de orden de corte → nuevo código = último + 1
    - Obtener último código de almacén de corte → nuevo código = último + 1
    - Generar guías automáticamente
3. **Crear Orden de Corte (OBLIGATORIO - Grupo Independiente):**
    - Crear cabecera en `ordencortejf`
    - Crear detalles en `detalles_ordencortejf` (agrupando artículos únicos)
    - Actualizar `ord_corte` en `articulojf` (sumar)
4. **Crear Almacén de Corte (OBLIGATORIO - Grupo Independiente):**
    - Crear cabecera en `almacencortejf`
    - Crear detalles en `almacencorte_detallejf` vinculados a la orden de corte
    - Actualizar stocks en `articulojf`:
        - Sumar a `alm_corte`
        - Restar de `ord_corte`
    - Actualizar saldos en `detalles_ordencortejf`
    - Actualizar estados de ordenes de corte (parcial/cerrado)
5. **Registrar en Taller (Taller Interno Único):**
    - Para cada artículo:
        - Buscar detalle de almacén de corte con saldo disponible
        - Registrar en `entaller_cabjf` vinculado a `almacencorte_detallejf` (taller: TALLER01)
        - Actualizar `saldo_taller` en `almacencorte_detallejf`
        - Actualizar stocks en `articulojf`:
            - Aumentar `taller`
            - Disminuir `alm_corte`

### Uso del Script

1. **Preparar el CSV:**

    - Crear archivo `taller.csv` en `/scripts/csv/`
    - Formato: `articulo,cantidad` (con encabezados)
    - Una fila por artículo

2. **Ejecutar el script:**

```bash
cd /Users/joel/Proyectos/vascorp
php scripts/procesar_taller.php
```

3. **El script:**
    - Valida el formato del CSV
    - Muestra resumen de artículos encontrados
    - Crea orden de corte automáticamente
    - Crea almacén de corte vinculado
    - Registra cada artículo en su taller correspondiente
    - Actualiza todos los stocks
    - Muestra resumen final con códigos generados

### Ejemplo de Ejecución

```bash
$ php scripts/procesar_taller.php

============================================
PROCESADOR DE INVENTARIO - TALLER
============================================

Leyendo archivo CSV...
Artículos encontrados: 4
Total de unidades: 115

Obteniendo códigos disponibles...
Nueva Orden de Corte: 1002
Nuevo Almacén de Corte: 502
Guía generada: GUIA-TALLER-20241215-123

Creando Orden de Corte...
✓ Orden de Corte creada exitosamente

Creando Almacén de Corte...
✓ Almacén de Corte creado exitosamente

Registrando artículos en Taller...
✓ Registrados 4 artículos en taller
  Guía de taller: GUIA-TALLER-20241215-456

============================================
PROCESO COMPLETADO EXITOSAMENTE
============================================

Resumen:
  - Orden de Corte: 1002
  - Almacén de Corte: 502
  - Guía Almacén: GUIA-TALLER-20241215-123
  - Guía Taller: GUIA-TALLER-20241215-456
  - Artículos procesados: 4
  - Total unidades: 115
  - Registros en taller: 4
```

## Notas Importantes

-   Los inventarios son **físicos** (lo que realmente existe)
-   La relación entre almacén de corte y orden de corte es **1:1**
-   El script manejará automáticamente la creación de órdenes de corte si no existen
-   Los códigos de almacén y orden de corte se generarán automáticamente si no se proporcionan
