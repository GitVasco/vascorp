# Línea de crédito recomendada — explicación de negocio

Documento para el equipo de análisis, jefatura de ventas y jefatura de cuentas corrientes.  
Objetivo: entender **cómo el sistema propone una línea**, para poder opinar si las reglas tienen sentido comercial.

---

## Idea en una frase

La línea recomendada responde a esta pregunta:

> **¿Cuánto crédito necesita el cliente para operar con normalidad, según lo que realmente compra, y cuánto crédito merecemos darle según cómo paga?**

No es un “premio” ni un castigo automático: es una **propuesta calculada** que luego puede aprobarse, ajustarse o rechazarse.

---

## Qué mira el sistema (en lenguaje simple)

| Qué mira | Para qué sirve | Quién suele preocuparse más |
|----------|----------------|-----------------------------|
| Cuánto compra al mes | Estimar la necesidad de cupo | Ventas |
| Cuál fue su compra más grande (reciente y en el año) | No dejarle corto en un pedido fuerte | Ventas |
| Cómo paga (score de riesgo) | No abrir cupo si el pago es malo | Cuentas corrientes |
| Cómo viene comercialmente (score compuesto) | Subir o bajar la propuesta según desempeño | Ambos |
| Categoría comercial / piso mínimo | Evitar líneas irreales en clientes buenos | Ambos |

---

## Cómo se calcula (paso a paso)

### Paso 1 — Capacidad de compra (base económica)

Se toma el **mayor** de estos tres montos:

1. **Promedio mensual de compras × 3 meses**  
   (lo que suele comprar, cubierto por 3 meses de operación)
2. **Compra máxima de los últimos 6 meses**
3. **Compra máxima de los últimos 12 meses**  
   (sirve cuando el cliente “pausó” por letras o cobranza, pero antes compraba más)

Ese mayor valor es la **base económica**: el tamaño de negocio del cliente.

### Paso 2 — Ajuste por calidad comercial

Se multiplica la base por un factor según el **score final del motor de línea** (0 a 100):

- Score 100 → se usa el 100% de la base  
- Score 70 → se usa el 70%  
- Score bajo → como mínimo se usa el **35%** (no se va a cero solo por score comercial)

### Paso 3 — Ajuste por capacidad de pago

Se multiplica otra vez por un factor según el **score de riesgo / cómo paga**:

- Score 100 → 100%  
- Score 80 → 80%  
- Score bajo → como mínimo el **50%** (sí baja, pero no anula del todo la base)

### Paso 4 — Piso mínimo (protección)

Si el cálculo sale muy bajo, el sistema puede aplicar un **piso**:

- **Buen pagador con poca deuda usada:**  
  riesgo alto (≥ 75), utilización de línea menor a 30%  
  → piso = lo mayor entre `deuda × 1,5` y `línea actual × 20%`
- **Piso por categoría comercial** del cliente (si aplica)

Se queda con el **mayor** entre el cálculo y esos pisos.

### Paso 5 — Redondeo

El monto final se redondea **hacia abajo al múltiplo de S/ 1.000**.  
Ejemplo: S/ 12.750 → **S/ 12.000**.

Así la propuesta es limpia y conservadora.

---

## Fórmula resumida

```
Línea recomendada =
  redondeo a miles hacia abajo de:

  el mayor entre:
    (base económica × factor comercial × factor de pago)
    piso buen pagador
    piso por categoría
```

---

## Ejemplo numérico (cliente ficticio)

**Datos del cliente “Distribuidora Los Andes”**

| Dato | Valor |
|------|------:|
| Compras últimos 6 meses | S/ 48.000 |
| Promedio mensual (48.000 ÷ 6) | S/ 8.000 |
| Compra máxima últimos 6 meses | S/ 15.000 |
| Compra máxima últimos 12 meses | S/ 18.000 |
| Score comercial / motor de línea | 80 |
| Score de riesgo (cómo paga) | 90 |
| Deuda actual | S/ 4.000 |
| Línea operativa actual | S/ 20.000 |
| Categoría | sin piso especial |

### 1) Base económica

- Promedio × 3 meses = 8.000 × 3 = **S/ 24.000**
- Compra máx. 6 meses = **S/ 15.000**
- Compra máx. 12 meses = **S/ 18.000**

**Base = S/ 24.000** (el mayor)

### 2) Factores

- Factor comercial = 80 ÷ 100 = **0,80**
- Factor de pago = 90 ÷ 100 = **0,90**

### 3) Cálculo bruto

```
24.000 × 0,80 × 0,90 = S/ 17.280
```

### 4) ¿Aplica piso?

- Riesgo 90 ≥ 75 → sí cumple riesgo  
- Utilización = 4.000 / 20.000 = 20% < 30% → sí cumple  
- Piso = max(4.000 × 1,5 ; 20.000 × 0,20) = max(6.000 ; 4.000) = **S/ 6.000**

El cálculo (17.280) ya es mayor que el piso → **se mantiene 17.280**.

### 5) Redondeo

```
S/ 17.280 → S/ 17.000
```

### Resultado

**Línea recomendada: S/ 17.000**

Interpretación para el equipo:

- Compra con ritmo de ~S/ 8.000 al mes → 3 meses de cobertura dan S/ 24.000 de necesidad bruta.  
- Como paga bien (90) y el perfil comercial es bueno (80), se propone cerca de esa necesidad, un poco debajo.  
- Queda un poco bajo la línea actual (20.000): el sistema sugiere **ajustar a la baja de forma moderada**, no cortar fuerte.

---

## Otro ejemplo rápido: mal pagador

Mismos montos de compra (base S/ 24.000), pero:

- Score comercial = 70 → factor 0,70  
- Score de riesgo = 45 → factor **0,50** (mínimo permitido)

```
24.000 × 0,70 × 0,50 = S/ 8.400 → redondeo S/ 8.000
```

**Mensaje de negocio:** aunque compra bien, **cómo paga limita el cupo**. Ventas puede pedirlo; cuentas corrientes tiene argumento claro para no subir.

---

## Qué NO hace esta fórmula

- No aprueba sola la línea (es una **recomendación**).  
- No mira solo la deuda del día: mira historial de compra y de pago.  
- No castiga automáticamente a un cliente que pausó pedidos por letras si antes compraba más (usa la compra máxima de 12 meses).  
- No entrega montos “raros” (siempre miles redondos hacia abajo).

---

## Preguntas para opinar (ventas y cuentas corrientes)

1. ¿**3 meses** de cobertura del promedio es el horizonte correcto, o prefieren 2 o 4?
2. ¿El piso del buen pagador (deuda × 1,5 / 20% de la línea) es razonable o muy alto/bajo?
3. ¿Les parece bien que un mal pagador baje como mínimo al **50%** de la base, o debería bajar más?
4. ¿La compra máxima de 12 meses debería tener más o menos peso cuando hay pausa por cobranza?
5. ¿El redondeo a miles hacia abajo es suficientemente conservador?

---

## Nota operativa

Esta recomendación sale del **Motor 3 (Inteligencia Comercial / Línea de crédito)**.  
En pantalla se puede ver el desglose del cálculo por cliente para validar caso a caso antes de opinar sobre la regla general.

*Documento de negocio — julio 2026.*
