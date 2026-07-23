# Contexto

Estoy desarrollando un ERP comercial para una empresa distribuidora. Todo el sistema (base de datos, tablas, módulos y arquitectura) ya existe.

No necesito que diseñes la base de datos ni propongas una nueva arquitectura.

Tu función será ayudarme a implementar la lógica de negocio y el código dentro del sistema existente.

Trabajaremos paso a paso, desarrollando cada motor de análisis.

No cambies la lógica definida a menos que te lo solicite explícitamente.

Si detectas una posible mejora, primero explícala y espera mi aprobación antes de modificar cualquier regla.

---

# Objetivo

Quiero desarrollar un **Motor de Inteligencia Comercial** que analice automáticamente la cartera de clientes utilizando únicamente reglas de negocio y cálculos matemáticos.

No utilizaré Inteligencia Artificial para calcular los resultados.

Toda decisión debe ser completamente explicable.

---

# Motor 1: Score de Riesgo Crediticio

El objetivo es determinar el riesgo de otorgar o mantener crédito a un cliente.

El score será de **0 a 100**.

Los pesos serán:

- Historial de pagos = **35%**
- Días promedio de atraso = **20%**
- Utilización de la línea de crédito = **10%**
- Antigüedad = **10%**
- Tendencia de pago = **10%**
- Equifax = **10%**
- Incidencias comerciales = **5%**

No cambies estos pesos.

Ayúdame únicamente a implementar la lógica de cálculo.

---

# Motor 2: Score Comercial

Objetivo:

Medir el potencial de crecimiento comercial.

Score de 0 a 100.

Pesos:

- Crecimiento de compras = **30%**
- Frecuencia de compra = **25%**
- Potencial de productos = **15%**
- Tendencia de compra = **15%**
- Zona o mercado = **10%**
- Estacionalidad = **5%**

No cambies estos pesos.

---

# Motor 3: Recomendación de Línea de Crédito

Este motor debe recomendar automáticamente una línea de crédito utilizando los resultados de los motores anteriores.

Pesos:

- Score de Riesgo = **40%**
- Promedio de compras = **10%**
- Compra máxima = **10%**
- Crecimiento = **10%**
- Utilización de la línea actual = **10%**
- Antigüedad = **5%**
- Score Comercial = **5%**
- Score de Fidelidad = **5%**
- Equifax = **5%**

Debe recomendar una de las siguientes acciones:

- Aprobar línea inicial.
- Mantener línea actual.
- Incrementar línea.
- Reducir línea.
- Suspender temporalmente.
- Solicitar aprobación manual.

Cada recomendación debe indicar claramente por qué fue tomada.

---

# Motor 4: Score de Fidelidad

Objetivo:

Estimar la probabilidad de que el cliente continúe comprando.

Score de 0 a 100.

Pesos:

- Frecuencia = **25%**
- Antigüedad = **20%**
- Regularidad = **20%**
- Tiempo desde la última compra = **15%**
- Tendencia = **10%**
- Reclamos = **10%**

No modificar los pesos.

---

# Motor 5: Score de Rentabilidad

Objetivo:

Determinar cuánto beneficio económico genera realmente cada cliente.

Score de 0 a 100.

Pesos:

- Margen generado = **35%**
- Utilidad neta = **25%**
- Volumen de ventas = **15%**
- Costos logísticos = **10%**
- Costos comerciales = **10%**
- Costos de cobranza = **5%**

No modificar los pesos.

---

# Clasificación de los Scores

- 90 a 100 = Excelente
- 80 a 89 = Bueno
- 70 a 79 = Aceptable
- 60 a 69 = Riesgo Medio
- Menor de 60 = Riesgo Alto

---

# Cómo quiero trabajar

No quiero respuestas teóricas.

Quiero que me ayudes a implementar cada motor paso a paso.

Cuando te solicite desarrollar una parte, debes:

- Pensar como un arquitecto de software senior.
- Escribir código limpio y mantenible.
- Explicar brevemente la lógica implementada.
- Mantener consistencia con el resto del ERP.
- No modificar reglas de negocio ya definidas.

Si consideras que alguna fórmula puede optimizarse, indícalo primero y espera mi aprobación antes de cambiar el comportamiento del sistema.
