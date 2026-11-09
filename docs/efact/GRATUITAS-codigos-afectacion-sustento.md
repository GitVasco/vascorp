# Obsequios 2026: qué código usamos y por qué

**Para:** Contabilidad · **De:** Sistemas · **Fecha:** 9-nov-2026
*Criterio tomado de fuentes SUNAT; no es asesoría tributaria. Pedimos su validación y la de su asesor.*

## 1. La idea en una frase
Para el IGV, **regalar un bien es "retirarlo" y un retiro cuenta como venta gravada**, aunque no se cobre. Por eso el obsequio se emite como **operación gratuita con IGV informativo y total S/ 0.00**.

## 2. Qué dice la norma
- El retiro de bienes (incluye **obsequios**) se considera **venta** → Ley del IGV, art. 3; Reglamento, art. 2 (SUNAT, [Informe 096-2013](https://www.sunat.gob.pe/legislacion/oficios/2013/informe-oficios/i096-2013.pdf)).
- **Vasco asume el IGV**: no se le puede cobrar a quien recibe el regalo (Reglamento art. 2 num. 6).
- **Quien recibe no lo usa** como crédito fiscal ni como gasto (Ley del IGV, art. 20).
- **No es retiro** lo entregado a trabajadores como **condición de trabajo indispensable** (uniformes, EPP). Lo de **libre disposición** (canasta, regalo) **sí es retiro**.

## 3. Códigos que usaremos (Catálogo 07 de SUNAT)

| Caso 2026 | Comprobante | Código | Por qué |
|---|---|---|---|
| **Panetones a clientes** | Factura (RUC) o boleta (DNI), uno por cliente | **13** · Gravado – Retiro (**14** si lleva logo/publicidad de Vasco) | Obsequio a terceros = retiro gravado |
| **Canastas a trabajadores** | Boleta con DNI, una por trabajador | **16** · Gravado – Retiro por entrega a trabajadores | Libre disposición, no indispensable para trabajar |

En ambos: IGV 18 % **informativo**, importe total **0.00**, leyenda *"Transferencia gratuita de un bien y/o servicio prestado gratuitamente"*.

## 4. Por qué no "sin IGV"
Los códigos **inafectos (31–36)** son para excepciones con sustento legal específico. **eFact y SUNAT no validan si el código es correcto, solo que el archivo sea coherente**: si se elige "sin IGV" por comodidad y era gravado, ese IGV queda **sin declarar** (reparo e intereses). Hasta que ustedes indiquen lo contrario, usamos siempre el gravado (11–16).

## 4b. Qué ejemplo seguimos y cuál no
- **Seguimos Autonort** (casaca a colaboradora: IGV 6.95 informativo, total 0.00): coincide con SUNAT, que trata la entrega a trabajadores de libre disposición como **retiro gravado**.
- **No seguimos Virazon** (canasta, IGV 0.00) **por ahora**: la foto no muestra qué código usaron; si fue inafecto, no tenemos sustento verificado y quedaría IGV sin declarar. **Lo habilitamos si ustedes confirman el caso por escrito.**
- Ante la duda, "con IGV": si sobra IGV se declara de más; si falta, queda sin declarar.

## 5. Lo que necesitamos que confirmen
1. ¿Panetones a clientes → **13** (o **14** si llevan logo)? ¿Canastas a trabajadores → **16**?
2. ¿Aplica algún caso **inafecto (31–36)** en Vasco? ¿Con qué sustento? Ejemplo: la boleta de **Virazon** (canasta a trabajadores) sale con IGV 0.00; el PDF no muestra el código usado. ¿Pueden conseguir su XML/CSV, o su asesor confirma si esa entrega califica como inafecta (¿34 convenio colectivo?)?
3. ¿Con qué **valor** se emite cada obsequio (costo de compra sin IGV, valor de mercado)?
4. **IGV de la compra** de panetones/canastas: ¿crédito fiscal? ¿Tratamiento y límites como gasto (clientes: atención/representación)?
5. El sistema registra **automáticamente el usuario** que emite cada comprobante. ¿Necesitan además un autorizante formal por cada obsequio?

*Detalle técnico y fuentes: `GRATUITAS-codigos-afectacion-sustento-detalle.md`.*
