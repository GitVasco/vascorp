# Línea de Crédito

## Propósito

El módulo **Línea de Crédito** permite administrar el cupo de crédito de la cartera activa. Ayuda a definir cuánto crédito puede usar cada cliente o grupo empresarial, tomando como referencia su comportamiento comercial y de pago.

## Cómo funciona

Para cada cliente muestra la línea operativa, la línea recomendada por Inteligencia Comercial, la línea aprobada, deuda actual, deuda vencida, cupo disponible y nivel de riesgo.

La recomendación se calcula con el historial de compras, el comportamiento de pago, los puntajes comerciales y de riesgo, y el piso de la categoría comercial. La línea sugerida es una referencia: la aprobación final la realiza el usuario autorizado.

Cuando un cliente pertenece a un grupo empresarial, la línea aprobada y el cupo se administran de forma consolidada para todo el grupo; los locales se visualizan con su deuda y riesgo individual.

## Cómo se calcula la línea recomendada

La línea recomendada responde a una pregunta sencilla: **¿cuánto crédito necesita el cliente para comprar y cuánto es seguro otorgarle según cómo paga?**

El sistema sigue estos pasos:

1. **Estima cuánto necesita comprar:** toma como referencia el promedio de sus compras y sus compras más altas recientes.
2. **Revisa cómo se comporta comercialmente:** considera su actividad, frecuencia de compra y relación con la empresa.
3. **Evalúa cómo paga:** un cliente puntual y con bajo nivel de deuda puede recibir una recomendación mayor; si tiene mayor riesgo, la propuesta se reduce.
4. **Aplica mínimos de protección:** considera la categoría comercial y evita que la propuesta sea demasiado baja en clientes con buen comportamiento.
5. **Redondea el resultado:** el monto final se presenta en miles para facilitar su gestión.

**Ejemplo sencillo:** si un cliente normalmente compra alrededor de S/ 8,000 al mes, el sistema puede estimar una necesidad de hasta tres meses de compra. Luego ajusta ese monto según su pago y riesgo. Si compra bien y paga puntualmente, la recomendación será más alta; si tiene atrasos, será más conservadora.

> La línea recomendada no se aprueba automáticamente: es una referencia para que el responsable tome la decisión final.

## Acciones disponibles

- **Filtrar por grupo empresarial:** visualiza la cartera consolidada de un grupo o los clientes sin grupo.
- **Ver detalle del cliente:** consulta puntajes, deuda, cupo, recomendación e historial de líneas.
- **Actualizar desde Inteligencia Comercial:** recalcula los datos de un cliente o grupo con la información más reciente.
- **Registrar una línea aprobada:** guarda el monto aprobado y el motivo del cambio; queda registrado en el historial.
- **Gestionar la línea de un grupo:** define una línea única para los clientes que pertenecen al mismo grupo empresarial.
- **Ejecutar cierre mensual:** actualiza la cartera y genera el resumen del período.
- **Exportar a Excel:** descarga la cartera por cliente y por grupo empresarial.

> Las líneas se registran en múltiplos de S/ 1,000 y una línea por debajo del piso de categoría requiere confirmación.

## Mensaje para la exposición

> “Línea de Crédito permite definir y controlar el cupo de cada cliente o grupo empresarial, combinando su capacidad de compra, comportamiento de pago y nivel de riesgo para respaldar decisiones de crédito más seguras.”
