# Paginación Servidor - Cuentas Corrientes

## Descripción
Se ha implementado una versión optimizada de la tabla de cuentas con paginación del lado del servidor. Esta versión mejora significativamente el rendimiento al cargar solo los registros necesarios en lugar de traer todos los datos del año de una vez.

## Configuración

### Elegir Tipo de Paginación

En el archivo `controladores/config.php` puedes elegir qué versión(es) mostrar:

```php
// Elegir qué versión(es) de paginación mostrar para la vista de cuentas
// "cliente" = Solo paginación del lado del cliente (versión original)
// "servidor" = Solo paginación del lado del servidor (versión optimizada)
// "ambos" = Mostrar ambas versiones para comparar (útil para demostrar mejoras)
define("TIPO_PAGINACION_CUENTAS", "ambos");
```

**Valores:**
- `"cliente"` - Solo muestra la versión original con paginación del lado del cliente
- `"servidor"` - Solo muestra la versión optimizada con paginación del lado del servidor
- `"ambos"` - Muestra ambas opciones en el menú: "Generales (Cliente)" y "Generales (Servidor)" para poder comparar

**Casos de uso:**
- `"cliente"` - Producción con versión original
- `"servidor"` - Producción con versión optimizada
- `"ambos"` - Demostración o pruebas para comparar rendimiento y funcionalidad

## Archivos Creados

1. **Modelo**: `modelos/cuentas.modelo.php`
   - Método: `mdlRangoFechasCuentasPaginado()`

2. **Controlador**: `controladores/cuentas.controlador.php`
   - Método: `ctrRangoFechasCuentasPaginado()`

3. **AJAX**: `ajax/cuentas-corrientes/tabla-cuentas-paginado.ajax.php`
   - Nueva clase: `TablaCuentasPaginado`

4. **JavaScript**: `vistas/js/cuentas.js`
   - Nueva función: `cargarTablaCuentasPaginado()`

## Cómo Usar

### Opción 1: Cambiar la tabla existente (recomendado para pruebas)

En el archivo PHP donde se muestra la tabla, cambiar la clase de la tabla:

```php
<!-- Cambiar de: -->
<table class="table table-bordered table-striped dt-responsive tablaCuentas" width="100%">

<!-- A: -->
<table class="table table-bordered table-striped dt-responsive tablaCuentasPaginado" width="100%">
```

Y en el JavaScript, cambiar la llamada:

```javascript
// Cambiar de:
cargarTablaCuentas(ano);

// A:
cargarTablaCuentasPaginado(ano);
```

### Opción 2: Crear una nueva vista (recomendado para producción)

Crear una copia de `vistas/modulos/cuentas-corrientes/cuentas.php` y modificar:
- Cambiar la clase de la tabla a `tablaCuentasPaginado`
- Cambiar la función JavaScript a `cargarTablaCuentasPaginado()`

## Ventajas

1. **Rendimiento**: Solo carga 20-100 registros por vez en lugar de todos los del año
2. **Búsqueda optimizada**: La búsqueda se realiza en el servidor, reduciendo la carga de datos
3. **Ordenamiento eficiente**: El ordenamiento se hace en la base de datos
4. **Escalabilidad**: Funciona bien incluso con millones de registros

## Notas Importantes

- Los archivos originales NO han sido modificados, por lo que el sistema actual sigue funcionando
- La nueva versión requiere que los índices estén creados en la base de datos para máximo rendimiento
- Los botones y funcionalidades se mantienen iguales a la versión original

## Próximos Pasos Recomendados

1. Probar la nueva versión en un entorno de desarrollo
2. Crear índices en la base de datos cuando no haya usuarios conectados:
   ```sql
   CREATE INDEX idx_fecha_tipmov ON cuenta_ctejf(fecha, tip_mov);
   CREATE INDEX idx_cliente ON cuenta_ctejf(cliente);
   CREATE INDEX idx_num_cta ON cuenta_ctejf(num_cta);
   CREATE INDEX idx_cliente_codigo ON clientesjf(codigo);
   ```
3. Una vez probado, migrar gradualmente a la nueva versión

