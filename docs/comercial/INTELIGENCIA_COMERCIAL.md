# Inteligencia Comercial

## Propósito

El módulo **Inteligencia Comercial** convierte la información de ventas, pagos y deuda de un cliente en indicadores fáciles de consultar. Su objetivo es apoyar decisiones comerciales y de crédito con información objetiva.

## Cómo funciona

Se puede analizar a un **cliente** de manera individual o a un **grupo empresarial** de forma consolidada. El sistema procesa la información mediante cuatro motores:

- **Riesgo crediticio:** evalúa el comportamiento de pago, deuda y nivel de utilización del crédito.
- **Comercial:** analiza actividad, frecuencia, evolución y volumen de compra.
- **Fidelidad:** mide la continuidad y relación comercial con la empresa.
- **Línea de crédito:** combina los resultados anteriores para recomendar un cupo de crédito y una acción sugerida.

Cada motor muestra un score, sus factores y el peso de cada uno en el resultado. Así el usuario puede entender por qué un cliente tiene determinado nivel de riesgo o una línea recomendada.

En el análisis por grupo, se consolida la información de todos los locales y se identifica el que requiere mayor atención por su historial de pago.

## Cómo se calcula el análisis

El sistema convierte cada aspecto del cliente en un puntaje de **0 a 100**. No toma una decisión por un único dato: combina varios factores según su importancia.

1. **Pago y riesgo:** revisa si paga a tiempo, sus días de atraso, deuda, uso de línea, antigüedad e incidencias.
2. **Comportamiento comercial:** revisa si compra con frecuencia, si sus compras crecen o disminuyen y su potencial de productos.
3. **Fidelidad:** revisa la continuidad de compra, la regularidad, la fecha de la última compra y los reclamos.
4. **Recomendación de crédito:** usa los resultados anteriores junto con el promedio y máximo de compras, la deuda y la línea actual para sugerir una acción y un cupo.

Los resultados se interpretan de forma simple: un score alto refleja una situación más favorable; un score bajo indica que se debe evaluar el caso con mayor cuidado.

**Ejemplo sencillo:** un cliente compra regularmente, no tiene atrasos y utiliza bien su crédito. Puede obtener un riesgo de **90**, un desempeño comercial de **80** y una fidelidad de **85**. Si además suele comprar S/ 8,000 mensuales, el sistema puede recomendar mantener o incrementar su línea. En cambio, si empieza a retrasarse en sus pagos, el score de riesgo baja y la recomendación será más conservadora, aunque sus ventas continúen siendo buenas.

> La recomendación se calcula con reglas de negocio y datos del ERP; la IA solo genera un resumen en lenguaje claro y no cambia los puntajes ni la recomendación.

## Acciones disponibles

- **Seleccionar cliente o grupo:** consulta el análisis individual o consolidado.
- **Revisar los cuatro motores:** visualiza scores, factores y aportes al resultado.
- **Ver el detalle de un factor:** explica cómo se obtuvo cada indicador.
- **Consultar la recomendación de línea:** muestra deuda, línea operativa, línea recomendada y cupo disponible.
- **Generar resumen ejecutivo con IA:** presenta una lectura breve del análisis, recomendaciones y explicación de la línea; no modifica los scores.
- **Abrir el análisis de un local del grupo:** permite profundizar en cada integrante del grupo empresarial.
- **Imprimir el análisis:** genera una versión para impresión vertical u horizontal.

> El módulo recomienda y explica; la aprobación final de una línea o pedido se realiza en los módulos de crédito correspondientes.

## Mensaje para la exposición

> “Inteligencia Comercial integra ventas, comportamiento de pago y deuda en indicadores claros para conocer el nivel de riesgo, el potencial comercial y la línea de crédito recomendada de cada cliente o grupo.”
