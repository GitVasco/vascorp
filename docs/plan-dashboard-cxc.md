# Plan de implementación: Dashboard de Cuentas por Cobrar

## Objetivo

Crear una vista independiente en `index.php?ruta=dashboard-cxc`, inspirada en
`docs/panel_cxc.png`, para controlar la cartera de cuentas por cobrar. Debe ser
más visual, filtrable y accionable que `inicio-gerencia`, sin modificar ni
reemplazar dicha vista.

Usar el stack existente: PHP, AdminLTE/Bootstrap 3, jQuery, Chart.js y
DataTables. No añadir React, Vite ni dependencias nuevas.

## Regla de negocio confirmada: documentos incobrables

La regla vigente se encuentra en
`vistas/reportes_excel/rpt_incobanno.php`. Un documento es **incobrable** por
pertenecer a un grupo fijo de vendedores; no por tener más de 180 días de
vencido.

```text
vendedores incobrables = ['00A', '01', '03', '05A', '07A', '14', '15']
```

Para calcular el monto de incobrables se deben considerar registros de
`cuenta_ctejf` que cumplan:

```sql
tip_mov = '+'
AND estado = 'PENDIENTE'
AND vendedor IN ('00A', '01', '03', '05A', '07A', '14', '15')
```

El monto es `SUM(saldo)`. Si el nuevo dashboard calcula el saldo a una fecha
de corte mediante movimientos de cargo y abono, usar el saldo pendiente a esa
fecha en vez de `cc.saldo`, pero conservar exactamente el filtro de vendedores.

### Distinción obligatoria

- **Incobrable:** pertenece al grupo de vendedores anterior y está pendiente.
- **Vencido +180 días:** está pendiente, vencido más de 180 días y **no**
  pertenece al grupo de vendedores incobrables. Esta exclusión está implementada
  en `ModeloMovimientos::mdlTotalVencidos180()`.

No sumar ni presentar ambas categorías como equivalentes ni dejar que un
documento figure simultáneamente en ambas KPI sin una etiqueta explícita.

### Implementación de la configuración

Crear una única fuente de verdad, por ejemplo:

`controladores/dashboard-cxc.config.php`

```php
<?php
const DASHBOARD_CXC_VENDEDORES_INCOBRABLES = [
    '00A', '01', '03', '05A', '07A', '14', '15',
];
```

El controlador, modelo y endpoint deben usar esta constante. No repetir la
lista literal en SQL, vistas o JavaScript. Al usar placeholders SQL, generar el
número de placeholders a partir de la constante y enlazar cada valor.

Antes de cerrar la tarea, comparar el KPI de incobrables del dashboard con el
reporte `rpt_incobanno.php` para el mismo corte. La caja actual de
`inicio-gerencia` usa el snapshot `totalesjf.incobrable_cuentas`; sirve como
referencia, pero no debe ser la fuente principal si el nuevo dashboard permite
filtros y fechas dinámicas.

## Archivos a crear

- `vistas/modulos/dashboard-cxc.php`: contenedor de la página, filtros e
  inclusión ordenada de bloques.
- `vistas/modulos/dashboard-cxc/`: un archivo PHP por bloque visual.
- `vistas/js/dashboard-cxc.js`: filtros, solicitudes AJAX, gráficos y tablas.
- `vistas/css/dashboard-cxc.css`: estilos encapsulados bajo `.cxc-dashboard`.
- `controladores/dashboard-cxc.config.php`: configuración centralizada de
  vendedores incobrables.
- `controladores/dashboard-cxc.controlador.php`: reglas de negocio y armado
  de métricas.
- `modelos/dashboard-cxc.modelo.php`: consultas SQL parametrizadas.
- `ajax/dashboard-cxc.ajax.php`: respuestas JSON para datos dinámicos.

## Archivos existentes a ajustar

- `index.php`: cargar el controlador, configuración y modelo nuevos.
- `vistas/plantilla.php`: registrar título, CSS, JS y la ruta.
- `vistas/modulos/menu.php`: agregar la entrada bajo “Crédito y cobranzas”,
  aplicando el mismo esquema de permisos del módulo.

Tomar como referencia arquitectónica el módulo existente `dashboard-cobranzas`
(`vistas/modulos/dashboard-cobranzas.php`, su controlador, modelo, AJAX y JS).
Revisar también las fuentes actuales de CxC en `inicio-gerencia`, especialmente
`ajax/movimientos/tabla-ctasgerenciavdor.ajax.php` y
`ModeloMovimientos::mdlTotalVencidos180()`.

## Orden de implementación por bloques

No construir el siguiente bloque hasta validar el anterior con datos reales.

### Bloque 0 — Datos y contrato del endpoint

1. Identificar las tablas y campos de documentos, vencimiento, cliente,
   vendedor, abonos y estado.
2. Definir saldo pendiente, fecha de corte y rangos de antigüedad en un solo
   método de modelo reutilizable.
3. Implementar el endpoint con parámetros validados: `anio`, `mes`,
   `vendedor`, `zona`, `rango`, `cliente` y paginación cuando corresponda.
4. Confirmar que la consulta base coincide con los totales de las tablas
   actuales de CxC.

Criterio de aceptación: no hay SQL concatenado con parámetros de URL y los
documentos anulados/cancelados no se incluyen.

### Bloque 1 — Ruta, permisos y filtros

Crear `dashboard-cxc.php` con título “Centro de Control de Cuentas por Cobrar”.

Filtros:

- año;
- mes o fecha de corte;
- vendedor, con opción “Todos”;
- zona, únicamente si existe una relación confiable en los datos.

Guardar los filtros en la URL y aplicar validación de permisos en la vista y en
el AJAX.

### Bloque 2 — Cajas superiores

Crear `vistas/modulos/dashboard-cxc/cajas-superiores.php`.

Mostrar, como mínimo:

1. Total por cobrar.
2. Monto vencido, excluyendo incobrables si se quiere conservar la semántica de
   `inicio-gerencia`.
3. Monto vencido +180 días, excluyendo el grupo de incobrables.
4. Monto incobrable, usando la regla documentada arriba.
5. Clientes con deuda.
6. Clientes del grupo incobrable con saldo pendiente.

Cada caja puede filtrar el detalle solo cuando dicho filtro exista realmente.
Los aumentos de vencido, +180 e incobrable deben mostrarse como alertas, no como
una mejora.

### Bloque 3 — Resumen y gráfico de antigüedad

Crear dos archivos independientes:

- `resumen-antiguedad.php`;
- `grafico-antiguedad.php`.

Rangos: `0–30`, `31–60`, `61–90`, `91–180`, `+180` e `Incobrables` como
categoría separada. El donut debe mostrar monto y porcentaje. La suma debe ser
coherente: si Incobrables se muestra separado, indicar explícitamente si se
excluye de los demás rangos para evitar doble conteo.

### Bloque 4 — Tabla por vendedor

Crear `tabla-cxc-vendedor.php` con:

- vendedor;
- clientes;
- crédito vigente;
- vencido;
- +180 días;
- incobrable;
- total;
- porcentaje vencido.

Orden inicial por mayor saldo vencido. Un clic debe aplicar el vendedor a los
filtros del detalle.

### Bloque 5 — Tabla por rango de vencimiento

Crear `tabla-vencimiento-rangos.php` con rango, monto, porcentaje, clientes y
documentos. Al seleccionar una fila, filtrar la tabla de detalle.

### Bloque 6 — Top clientes por deuda

Crear `tabla-top-clientes.php` con cliente, saldo, vencido, antigüedad máxima,
vendedor y etiqueta de riesgo. Ordenar por vencido y luego por antigüedad.

### Bloque 7 — Detalle operativo de documentos

Crear `tabla-detalle-documentos.php` con búsqueda, paginación, orden y totales
del resultado filtrado.

Columnas mínimas:

- cliente;
- vendedor;
- documento;
- fecha de emisión;
- fecha de vencimiento;
- saldo pendiente;
- días de antigüedad;
- rango;
- clasificación: regular, vencido, +180 o incobrable.

La tabla debe mostrar visualmente los filtros aplicados y permitir limpiarlos.

### Bloque 8 — Tendencia y alertas

Solo tras validar los bloques anteriores:

- gráfico de evolución mensual de CxC total, vencido, +180 e incobrable;
- alertas de clientes vencidos, documentos próximos a vencer y concentración de
cartera, cada una respaldada por una regla de negocio explícita.

No implementar proyecciones de cobranza hasta acordar su fórmula.

### Bloque 9 — Calidad y rendimiento

- Escapar toda salida HTML.
- Mantener CSS aislado bajo `.cxc-dashboard`; evitar nuevos estilos inline.
- Usar consultas agregadas para KPI y gráficos.
- Usar DataTables server-side o paginación AJAX si el detalle es grande.
- Probar: cero resultados, enero/cambio de año, vendedor sin cartera, cartera
  sin vencidos, datos altos y usuarios sin permiso.
- Validar los montos de incobrable contra el reporte histórico y los de +180
  contra la lógica existente antes de dar la tarea por terminada.
