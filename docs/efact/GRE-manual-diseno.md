# GRE Remitente manual (traslados y otros motivos) — Diseño

Fuente: `Guía de remisión remitente CSV 2.1 (1).pdf` (eFact). Estado: **implementado, pendiente de probar con BD y EFACT**.

## 1. Objetivo
Pantalla nueva para emitir GRE remitente **sin pasar por pedidos/facturación**: traslados entre locales, compras, otros. **No toca** el flujo actual (`ctrGenerarGuia`, `ventajf` tipo `S01`, `procesar-ce`).

## 2. Aislamiento del flujo actual
| | Flujo actual | Flujo nuevo |
|---|---|---|
| Origen | Pedido → `ventajf` S01 | Formulario manual |
| Tablas | `ventajf` / `movimientosjf` | Tablas nuevas `gre_manual` y `gre_manual_det` (propuesto) |
| Serie | `serie_guias` en talonarios | Serie/correlativo propio (propuesto) |
| Generador CSV | `ctrGenerarGuia` | Función nueva, no modifica la anterior |
| Carpeta salida | `DESTINO_GUIA_REMISION` | La misma (EFACT ya la vigila) |
| Stock | Descuenta | **No** descuenta (a confirmar) |

## 3. Formulario (secciones)
1. **Datos**: fecha emisión, fecha inicio traslado, motivo, descripción del motivo, observaciones (≤250).
2. **Destinatario**: cliente/proveedor del sistema o escrito a mano. Motivos 02, 04 y 18: se copia el remitente.
3. **Puntos de partida y llegada**: ubigeo, dirección, dpto/prov/distrito.
4. **Transporte**: modalidad 01 público (RUC y razón social del transportista obligatorios) o 02 privado (conductor y placa obligatorios). Peso bruto total KGM, bultos.
5. **Ítems** (pestañas): Modelo / Artículo / Materia prima / Manual. Cada ítem: unidad, cantidad, descripción (≤250), código (≤16).
6. **Documentos relacionados** (opcional): tipo 01–06 + número.

## 4. Reglas del manual (resumen)
- Archivo: `20513613939-09-<T###>-<8 dígitos>.csv`, UTF-8 sin BOM, 13 filas: 1 datos, 2 guía referencia, 3 docs relacionados, 4 conductor, 5 placas, 6 contenedores, 7 remitente, 8 destinatario, 9 proveedor, 10 envío, 11 observaciones, 12 ítems, 13 `FF00FF`.
- Cabecera fila 1: cantidad de ítems, guías ref., docs, conductores, placas, contenedores.
- Motivos: 01 Venta, 02 Compra, 04 Traslado entre establecimientos, 08 Importación, 09 Exportación, 13 Otros, 14 Venta sujeta a confirmación, 18 Itinerante CP, 19 Zona primaria.
- Importación (08): exige DAM (01) y manifiesto (04) en docs relacionados, bultos y puerto/aeropuerto.
- Exportación (09): exige DAM (01).
- Privado (02): conductor (DNI, nombres, apellidos) y placa obligatorios.
- Serie de la guía remitente empieza con `T` (ej. `T001-00000001`).

## 5. Decisiones acordadas
1. Serie nueva en `gre_manual_seriejf` (semilla `T001`; confirmar que no choque con `talonariosjf`).
2. Motivos habilitados: 01, 02, 04, 13, 14. (08 y 09 quedan fuera por ahora.)
3. No mueve stock.
4. Conductor, vehículo y agencia se reutilizan de `tabla_m_detalle` (`tcho`, `TCAR`) y `agenciasjf`, igual que la guía desde pedidos.
5. Destinatario: cliente, proveedor o escrito a mano. Motivo 04 = la misma empresa (automático).
6. Direcciones de partida y llegada: a mano (con buscador de ubigeo y botón "Usar domicilio Vasco").
7. Peso y bultos a mano; **ambos obligatorios**.
8. Listado en vista aparte (`gre-manual`); formulario en `gre-manual-crear`.
9. Acceso: sesión con `facturacion = 1` o `materiaprima = 1`.

## 5b. Guías internas vs electrónicas
- Cada serie tiene `tipo`: `ELECTRONICA` (`T001`, se envía a EFACT) o `INTERNA` (`I001`, nunca genera CSV). La guía hereda el tipo de la serie elegida.
- Internas: sin botón de envío, impresión como "GUÍA DE REMISIÓN INTERNA" sin la nota de EFACT, etiqueta INTERNA en el listado y filtro por tipo.
- Una interna en estado GENERADO se puede **convertir a electrónica** (botón en el listado): toma el siguiente número de la serie electrónica y el número interno queda en `doc_interno`. Luego se envía como cualquier otra.
- Migración sobre lo ya creado: `docs/sql/gre-manual-tipo.sql`.

## 5c. Guía desde Servicios (motivo 13 · SERVICIO DE PRODUCCION)
- Botón de camión en cada fila de **Servicios** → `gre-manual-crear&servicio=<codigo>` (solo para quien puede emitir guías manuales).
- Precarga: motivo 13, descripción "SERVICIO DE PRODUCCION", destinatario = taller, partida = domicilio Vasco, ítems = detalle del servicio **agrupado por modelo** (editable). Peso, bultos y transporte se piden al emitir; serie interna o electrónica a elección.
- **Una guía por servicio** (se ignora la ANULADA): `gre_manualjf.servicio`. Si ya existe, el formulario avisa y enlaza a ella.
- Datos del taller (RUC/DNI, dirección, ubigeo) en `gre_sector_datosjf`; se completan en la primera guía y se recuerdan ("Recordar estos datos"). El destinatario también se puede elegir como "Taller" en cualquier guía manual.
- Los datos fiscales también se registran en **Sectores** (crear/editar, solo tipo Servicio externo): bloque "Datos para guía de remisión", opcional; si se llena, debe estar completo. Misma tabla `gre_sector_datosjf`.
- Migración: `docs/sql/gre-manual-servicio.sql`.
- Pendiente de definir: transporte cuando el taller recoge (ver con contabilidad/EFACT cómo declararlo).

## 5d. Guía desde notas de salida de MP (avíos)
- En **Notas de salida** (MP) hay una columna "Guía": botón **Emitir** (precarga la guía) o el número de la guía si ya existe.
- Precarga: ítems de la nota (`ventas_cab` / `venta_det`), unidades traducidas a códigos SUNAT (heurística en `ctrUnidadSunat`, editable), motivo 13, observación "ENVIO DE MATERIA PRIMA / AVIOS SEGUN NOTA DE SALIDA N° ...". Destinatario: taller registrado con el mismo RUC; si no, el cliente de la nota (hay que completar el ubigeo). Peso y bultos a mano.
- Avíos y prendas **viajan juntos**: si el destinatario ya tiene una guía sin enviar (p. ej. la del servicio), el formulario ofrece **agregar la nota a esa guía**. También se pueden sumar notas desde la pestaña "Nota de salida MP" de cualquier guía.
- Una nota solo puede estar en una guía no anulada (`gre_manual_detjf.nota`). No toca stock ni `EstGuia` de la nota.
- La hoja 2 (orden de servicio) lista aparte la MP/avíos enviados.
- Migración: `docs/sql/gre-manual-nota.sql`.

## 6. Archivos
- `docs/sql/gre-manual.sql` (instalación nueva) y `docs/sql/gre-manual-tipo.sql` (migración a internas/electrónicas)
- `modelos/gre-manual.modelo.php`, `controladores/gre-manual.controlador.php`, `ajax/gre-manual.ajax.php`
- `vistas/modulos/facturacion/gre-manual.php` (listado) y `gre-manual-crear.php` (formulario)
- `vistas/js/gre-manual.js`, `vistas/css/gre-manual.css`
- Cableado: `index.php`, `vistas/plantilla.php`, `vistas/modulos/menu.php` ("Guías manuales")

## 7. Flujo
Guardar (GENERADO, editable) → en el listado "Enviar a EFACT" genera el CSV y lo deja en `DESTINO_GUIA_REMISION` → ENVIADO (ya no editable). Se puede anular solo en GENERADO. Una vez ENVIADO no hay baja desde el sistema.
El CSV copia la estructura de filas de `ctrGenerarGuia` (la que EFACT ya procesa), no el orden literal del PDF.

## 8. Pendiente / riesgos
- Probar 1 CSV por motivo (01, 02, 04, 13, 14) y por modalidad (público/privado) con EFACT.
- Confirmar que `agenciasjf.mtc` existe en la BD real (la usa la guía actual).
- Impresión interna A4 en `vistas/reportes_ticket/gre_manual.php` (botón en el listado; sin QR ni hash, con aviso de documento interno). El PDF firmado de EFACT queda fuera de alcance por ahora.
- Sin confirmación de aceptación de EFACT en el sistema (igual que el flujo actual).
