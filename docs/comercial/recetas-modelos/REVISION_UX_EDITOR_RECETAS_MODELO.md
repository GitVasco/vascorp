# Ajuste UX: contexto al editar recetas por modelo

**Alcance acordado:** el consumo es único por sublínea y ese comportamiento se conserva. Esta mejora trata exclusivamente la confusión al agregar o seleccionar una sublínea: el usuario puede creer que sigue configurando la anterior cuando ya está editando otra.

## Problema confirmado

La acción actual **Agregar** crea la sublínea, la deja seleccionada automáticamente y muestra su consumo `1` en el mismo panel. El cambio de contexto ocurre sin una señal suficientemente fuerte.

```text
Usuario agrega “Encaje”
        ↓
El editor selecciona “Encaje” automáticamente
        ↓
El campo Consumo ahora edita “Encaje”, pero el usuario puede pensar que edita “Tela”
```

El dato no se mezcla técnicamente: cada sublínea mantiene su propio `consumo_base`. El riesgo es puramente de UX: el usuario modifica la sublínea equivocada porque no percibe cuál está activa.

## Diseño propuesto

### 1. Mostrar una única sublínea activa de forma inequívoca

El área de edición debe iniciar con un encabezado grande y persistente:

```text
Editando ahora
ENCaje · 120301
Consumo único para esta sublínea · se aplica a 24 combinaciones color × talla
```

- El chip activo conserva borde/color destacado y además muestra la etiqueta `EDITANDO`.
- Los demás chips se ven secundarios y deben indicar su consumo resumido.
- El campo `Consumo` se renombra a **Consumo de: Encaje · 120301** y la unidad se muestra junto al valor.
- Antes de la matriz debe repetirse el nombre/código de la sublínea activa; no depender solo del título de sección.

### 2. Después de agregar, no ocultar el cambio de contexto

Al pulsar **Agregar sublínea**:

1. Crear la nueva sublínea y seleccionarla, como ahora.
2. Mostrar una franja temporal de éxito: `Ahora estás configurando: Encaje · 120301`.
3. Desplazar/enfocar el panel de edición de esa sublínea y enfocar el campo de consumo.
4. Presentar una acción visible: **Volver a “Tela principal”** (o a la línea antes activa).

La franja desaparece después de unos segundos, pero el encabezado de “Editando ahora” siempre permanece.

### 3. Separar “Agregar” de “editar la seleccionada”

En la cabecera:

```text
[ Buscar y agregar otra sublínea ]

Sublínea seleccionada: Encaje · 120301
```

El control superior solo agrega. El panel inferior edita exclusivamente la seleccionada. No debe haber una etiqueta genérica como “Asignar MP” o un input `Consumo` sin el nombre de la sublínea activa.

### 4. Cambio de chip seguro

Al hacer clic en otro chip:

- Actualizar primero el encabezado `Editando ahora`, luego consumo, matriz y catálogo.
- Mostrar una transición breve: `Ahora editas: [nombre de sublínea]`.
- Si hay cambios locales pendientes en la línea anterior, mantenerlos en memoria —como hoy— y mostrar `Cambios sin guardar en esta receta`; no bloquear el cambio de chip.
- No copiar consumo ni MP entre sublíneas. Cada una recupera exactamente su propio estado.

## Cambios técnicos mínimos

Archivo principal: `vistas/js/recetas-modelo.js` y vista `vistas/modulos/recetas-modelo/editar-receta-modelo.php`.

- [ ] Añadir un bloque `#rmLineaActivaContexto` encima del consumo y la matriz.
- [ ] Crear `rmActualizarContextoLineaActiva(mensajeTemporal)` para renderizar nombre, código, unidad y cantidad de artículos afectados.
- [ ] Llamarla después de cargar la receta, cambiar de chip y agregar una sublínea.
- [ ] Al agregar, guardar `lineaAnteriorIdx`, seleccionar la nueva, mostrar la franja temporal y poner foco en `#rmConsumoLinea`.
- [ ] Añadir botón contextual **Volver a la anterior** durante el aviso de alta; debe seleccionar `lineaAnteriorIdx` sin modificar datos.
- [ ] En los chips, añadir `EDITANDO` solo al activo y conservar `N/N` para cobertura.
- [ ] Cambiar la etiqueta de consumo dinámicamente: `Consumo de: {nombre} · {código}`.
- [ ] No cambiar `rmSincronizarConsumoEnVariantes`: el consumo único por sublínea se mantiene.

## Criterios de aceptación

- [ ] Al agregar una sublínea, es evidente cuál se está editando ahora, sin revisar chips pequeños ni inferirlo por la matriz.
- [ ] El nombre/código de la sublínea activa se ve junto al consumo y junto a la matriz.
- [ ] Al cambiar de chip, el consumo mostrado pertenece siempre a esa sublínea y el usuario recibe feedback visible del cambio.
- [ ] El usuario puede volver a la sublínea anterior con una acción clara tras agregar una nueva.
- [ ] Cambiar de sublínea no copia ni modifica consumo, MP ni asignaciones de otra sublínea.
- [ ] El flujo actual de consumo único por sublínea conserva su comportamiento.
