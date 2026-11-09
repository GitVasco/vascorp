# Guía de prueba — Comprobantes especiales (tipo Gratuita) con eFact

Rama `joel`. Nada de esto toca el flujo de ventas. **Probar siempre con una serie provisional (BG01 / FG01), a nombre de Joel, y dar de baja el comprobante en eFact al terminar.**

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

## 1. Boleta de prueba (trabajador, con IGV)
1. Menú **Comprobantes especiales → Nuevo comprobante**. Tipo: *Operación gratuita (obsequios)*.
2. Destinatarios: *Trabajador* (propone afectación **16**). Motivo: `PRUEBA ENTREGA A TRABAJADORES`.
3. Ítem: descripción `CANASTA PRUEBA`, unidad C62, cantidad 1, valor referencial `67.80`.
4. Receptor: tu DNI → *Completar nombres*.
5. **Crear comprobantes** → queda GENERADO (`BG01-00000001`).
6. En el listado pulsa el icono de archivo (**Ver CSV**): revisa que sea lo esperado (ver §3). No envía nada.
7. **Enviar** → el CSV queda en la carpeta que vigila eFact.

## 2. Qué mirar en eFact
- ¿Procesó, rechazó o procesó con observaciones? Copiar el mensaje **exacto**.
- ¿El PDF muestra Op. Gratuitas 67.80, IGV 12.20 (informativo), total 0.00, la leyenda y el monto en letras?
- Luego **dar de baja** el comprobante en el portal de eFact y, en el listado, pulsar **Marcar baja**.

## 3. CSV esperado (boleta, afectación 16)
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

## 4. Si eFact rechaza
Anotar en `REGISTRO.md`: archivo CSV, mensaje de error, y qué se cambió. Mandar a eFact el CSV + el error y preguntar qué columna corregir. Puntos que se espera confirmar (marcados ⚠ en el diseño):
- Dónde va el IGV informativo de cabecera (hoy **AO**; en el layout 2021 es la columna T).
- Si la columna **W** de la línea (hoy vacía) debe llevar valor.
- Si **Z** / **AL** deben ir en `0.00` o vacías.
- Aceptación de dirección `-` / ubigeo vacío en receptor DNI.

## 5. Segunda prueba: factura
Repetir con un RUC (serie FG01). Revisar FILA 1 columna O (`CONTADO`) y FILA 3 (`ATTACH_DOC`).
