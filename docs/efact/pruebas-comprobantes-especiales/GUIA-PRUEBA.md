# Guía de prueba — Comprobantes especiales (tipo Gratuita) con eFact

Rama `joel`. Nada de esto toca el flujo de ventas. **Probar siempre con una serie provisional (BG01 / FG01), a nombre de Joel, y dar de baja el comprobante en eFact al terminar.**

**Estado (20-nov-2026):** la **boleta** gratuita (afectación 16) fue **validada con éxito** por eFact. El CSV de §3 no requiere cambios. **Siguiente paso:** §5 factura FG01. Historial: `REGISTRO.md`.

## 0. Preparación (una sola vez)
1. Base de datos:
   - **Instalación nueva:** ejecutar `docs/sql/comprobantes-especiales.sql` (tablas `comp_especialjf`, `comp_especial_detjf`, `comp_especial_seriejf` y series BG01 / FG01).
   - **Si ya habías ejecutado la versión anterior** (tablas `obsequio*`): ejecutar solo `docs/sql/comprobantes-especiales-migracion.sql` (una vez; renombra y conserva datos). **No ejecutar los dos.**
2. Acceso: el módulo solo aparece para los usuarios listados. Por defecto son los ids **70 y 6** (los mismos que corrigen correlativos en Guías manuales). Para dejar **solo tu usuario**, agregar en `controladores/config.php` (no versionado aquí):
   ```php
   define("COMPROBANTES_ESPECIALES_USUARIOS", array("TU_ID"));   // id de sesión ($_SESSION["id"])
   ```
3. Verificar que `BG01` y `FG01` no estén en uso por otra serie del sistema/eFact.
4. El inafecto (sin IGV) está **desactivado**. Solo se activa con `define("GRATUITAS_PERMITE_INAFECTO", true);` y confirmación escrita de Contabilidad.
5. Correlativo: vive en `comp_especial_seriejf.correlativo` (último usado). **No reiniciar** si el número ya llegó a eFact.

## 1. Boleta de prueba (trabajador, con IGV) — ✔ hecha
1. Menú **Comprobantes especiales → Nuevo comprobante**. Tipo: *Operación gratuita (obsequios)*.
2. Destinatarios: *Trabajador* (propone afectación **16**). Motivo: `PRUEBA ENTREGA A TRABAJADORES`.
3. Ítem: descripción `CANASTA PRUEBA`, unidad C62, cantidad 1, valor referencial `67.80`.
4. Receptor: tu DNI → *Completar nombres*.
5. **Crear comprobantes** → queda GENERADO (`BG01-…`).
6. En el listado pulsa el icono de archivo (**Ver CSV**): revisa que sea lo esperado (ver §3). No envía nada.
7. **Enviar** → el CSV queda en la carpeta que vigila eFact.

## 2. Qué mirar en eFact
- ¿Procesó, rechazó o procesó con observaciones? Copiar el mensaje **exacto**.
- ¿El PDF muestra Op. Gratuitas, IGV informativo, total 0.00, la leyenda y el monto en letras?
- Luego **dar de baja** el comprobante en el portal de eFact y, en el listado, pulsar **Marcar baja**.

**Resultado boleta (20-nov-2026):** eFact **validó con éxito**. Sin cambios de columnas.

## 3. CSV esperado (boleta, afectación 16) — confirmado
Con nuestra estructura (9 filas + `FF00FF`). Lo único distinto a una venta normal:

| Dónde | Valor |
|---|---|
| FILA 1: E, F, N, Z, AL, BH, BI, BJ | `0.00` |
| FILA 1: **Y** | total gratuito (67.80) |
| FILA 1: **AO** | IGV informativo (12.20) |
| FILA 6: A / **B** | `CERO Y 00/100 SOLES` / leyenda `TRANSFERENCIA GRATUITA DE UN BIEN Y/O SERVICIO PRESTADO GRATUITAMENTE` |
| Línea: E, T, AP | `0.00` |
| Línea: **G / H** | `67.80` / `02` |
| Línea: I, J | `12.20` |
| Línea: **K / L / M** | `16` / `9996` / `18` |
| Línea: U, X | `67.80` |
| Línea: W | vacía (aceptada) |

## 4. Si eFact rechaza
Anotar en `REGISTRO.md`: archivo CSV, mensaje de error, y qué se cambió. Mandar a eFact el CSV + el error y preguntar qué columna corregir.

En boleta ya no aplican las dudas de AO / W / Z / AL / total 0.00 (quedaron confirmadas). Si falla la **factura**, anotar diferencias de FILA 1 O y FILA 3.

## 5. Segunda prueba: factura (siguiente)
Repetir con un RUC (serie FG01). Revisar FILA 1 columna O (`CONTADO`) y FILA 3 (`ATTACH_DOC`). Registrar resultado en `REGISTRO.md`.
