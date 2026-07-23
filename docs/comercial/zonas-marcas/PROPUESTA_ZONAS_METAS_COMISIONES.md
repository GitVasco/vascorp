# Propuesta: Zonas comerciales, metas/retos y comisiones

**Estado:** Fase 0–4 en código — v1.9  
**Fecha:** 2026-07-14  
**Alcance:** solo Vascorp · códigos de zona nuevos · implementación por fases  
**SQL zonas:** `zonas-comerciales*.sql` · **SQL retos:** `docs/sql/metas-retos-fase3.sql`  
**Pantallas:** `zonas-comerciales` · `mapas-zonas` · `metas-retos` (permisos existentes: zonas / metas_vendedor)

---

## 0. Regla transversal: solo vendedores activos

**Todo lo de esta iniciativa (zonas, cobertura, metas/retos, comisiones, mapas y reportes) aplica únicamente a vendedores activos.**

- Fuente: `maestrajf` con `tipo_dato = 'TVEND'` y **`estado_decisiones = 1`**.
- Los inactivos (`estado_decisiones = 0`) no se ofrecen en selects de asignación, no se consideran en cobertura ni en metas/comisiones futuras.
- Listados de vendedores en este módulo: ordenados por **código** ascendente.
- Si un vendedor pasa a inactivo, deja de entrar en flujos nuevos; no se “reinventa” otra definición de activo en cada pantalla.

---

## 1. Visión

Zonas comerciales configurables + asignación realista (grupo / cliente / ubigeo) + varios vendedores **activos** por zona + metas mensuales por vendedor + reporte de comisiones al final. Dos mapas: Lima y Perú sin Lima.

---

## 2. Catálogo de zonas

### 2.1 Lima y alrededores

| Zona | Regla por defecto | Notas |
| ---- | ----------------- | ----- |
| **Lima Centro Comercial** | Distritos del centro comercial (La Victoria, Cercado, Breña, etc. según ubicación) | La Victoria “genérica” cae aquí |
| **Lima Norte** | Distritos norte según ubicación | |
| **Lima Este** | Distritos este según ubicación | |
| **Lima Sur** | Distritos sur según ubicación | |
| **Lima Moderna** | Distritos modernos / residenciales-empresariales de ubicación (San Isidro, Surco, La Molina, Jesús María, Lince, Magdalena, Surquillo, Miraflores, Barranco, San Borja, etc.) | Antes se mezclaba con “Económica”; ya no |
| **Zona Económica (Gamarra)** | **No es un distrito completo.** Solo clientes cuyo negocio está **en Gamarra** (dentro de La Victoria) | Por ubigeo no se puede detectar solo; casi siempre **edición manual** (grupo o cliente) |
| **Callao** | Toda la Provincia Constitucional del Callao | |
| **Norte Chico** | Todo Norte Chico, **incluye Huaura / Huacho** | Huaura no es zona aparte |

### 2.2 Fuera de Lima (bloques)

| Zona | Departamentos propuestos (Fase 0 — a aprobar) |
| ---- | ----------------------------------------------- |
| **Norte del Perú** | Tumbes, Piura, Lambayeque, La Libertad, Cajamarca, Amazonas, San Martín, Loreto, Ucayali, Ancash *(Ancash a veces se trata “centro-norte”; si prefieren moverlo, se ajusta)* |
| **Sur del Perú** | Ica, Arequipa, Moquegua, Tacna, Puno, Cusco, Apurímac, Madre de Dios, Ayacucho, Huancavelica, Junín, Huánuco, Pasco *(centro-sur / sierra; Junín-Huánuco-Pasco se pueden mover a Norte si lo ven más natural)* |

**Lima (departamento) y Callao** no entran en Norte/Sur: se ven en el mapa de Lima.

Si quieren un criterio más simple:  
- **Norte** = costa/sierra/selva al norte de Lima (sin Lima/Callao).  
- **Sur** = todo lo demás del país fuera de Lima/Callao/Norte Chico.  
La tabla de arriba se puede recortar en Fase 0 cuando aprueben.

### 2.3 Distritos “sin clientes aún”

Van a la **zona que corresponda por ubicación**, no todos a Económica.  
**Zona Económica = solo Gamarra** (La Victoria, pero exclusivamente el polo comercial Gamarra).

---

## 3. Cómo se define la zona de un cliente

### 3.1 Resolución (opción A — confirmada)

1. Si el **cliente** tiene zona guardada a mano → **esa manda**.  
2. Si no, y pertenece a un **grupo** con zona → hereda la del grupo.  
3. Si no → se calcula por el **ubigeo de la dirección principal** (tabla de reglas ubigeo → zona).  

Ejemplo: grupo en Centro; un RUC en Arequipa → se edita ese cliente a Sur; el resto del grupo sigue en Centro.

### 3.2 Quién edita

Solo usuarios **105** (jefa) y **6** (Joel).

Casos típicos: Gamarra → Zona Económica; fiscal ≠ operación → corregir zona real.

---

## 3bis. Modelo de datos (respuesta: ¿tabla o campo?)

**Sí: hace falta guardar la zona aparte del ubigeo.** El ubigeo sigue siendo la dirección; la zona comercial es otra cosa.

No es “tabla **o** campo”: conviene **las dos cosas**:

| Pieza | Para qué |
| ----- | -------- |
| **Tabla catálogo de zonas** | Lista configurable: Centro, Norte, Gamarra, Callao, Norte Perú, etc. (alta/baja/nombre/mapa) |
| **Tabla (o filas) ubigeo → zona** | Regla automática: “este distrito/departamento → esta zona” |
| **Campo nuevo en grupo empresarial** (`zona_id` o código, nullable) | Zona del grupo cuando no quieren depender solo del ubigeo de cada RUC |
| **Campo nuevo en cliente** (`zona_id` o código, nullable) | Override del cliente (Gamarra, Arequipa excepcional, etc.) |

Cómo se interpreta el campo:

- **Vacío (NULL)** = “usa la regla siguiente” (grupo o ubigeo). No obliga a llenar a mano todos los clientes.  
- **Con valor** = “esta zona es la oficial; **no** uses el ubigeo para este caso”.

Flujo mental:

```
¿cliente.zona llena? → usar esa
  si no → ¿grupo.zona llena? → usar esa
    si no → buscar zona por ubigeo dirección principal
```

Por qué no solo ubigeo: Gamarra no es un ubigeo distinto de La Victoria; San Borja fiscal ≠ negocio en Gamarra.

Por qué no solo una tabla puente cliente-zona sin campo: un campo nullable en cliente/grupo es más simple de editar en ficha y de consultar. La tabla puente sí sirve más adelante para **vendedores ↔ zonas** (muchos a muchos).

Extra recomendado (Fase 1 o 2):

- Flag o auditoría: `zona_manual` / quién editó / cuándo (para saber si fue override o automático).  
- Listado “clientes sin zona resoluble” o “zona automática vs manual”.

Cuando se implemente, Fase 0 = catálogo + ubigeo→zona; Fase 1 = campos en cliente y grupo + pantalla de edición.

---

## 4. Vendedores, metas y comisiones

| Tema | Decisión |
| ---- | -------- |
| Alcance vendedores | **Solo activos** (`estado_decisiones = 1`). Ver §0 |
| Orden en UI | Por **código** de vendedor |
| Zona ↔ vendedores | Varios vendedores por zona; un vendedor en varias zonas |
| Metas | **Por vendedor activo**, mensuales |
| Quién se lleva la venta / meta / comisión | El **vendedor del maestro del cliente** (ej. Juan). La zona organiza cobertura; **no cambia** a quién se imputa la venta |
| Cobranza | No entra en este esquema |
| MVP retos | 1) Monto ventas 2) Clientes nuevos 3) Modelos activos 4) Modelo especial (1/vend-mes, docenas=cant/12, comisión %) |
| Cliente nuevo | Primera compra en la vida, **salvo** que el cliente se agregue a un **grupo ya existente** (no cuenta) |
| Modelos activos | Conteo de **modelos distintos vendidos en el mes** por el vendedor |
| Cumplimiento | Configurable (parcial vs todo-o-nada) |
| Comisión total | Suma de tipos cumplidos |
| Reporte comisiones | Solo al final del proyecto; solo reporte (no liquidar) |
| Esquema | Igual para todos los vendedores activos (por ahora) |

---

## 5. Mapas

1. **Mapa Lima** — Centro, Norte, Este, Sur, Moderna, Económica (Gamarra), Callao, Norte Chico.  
2. **Mapa Perú sin Lima** — Norte del Perú, Sur del Perú.

---

## 6. Fases

| Fase | Entrega |
| ---- | ------- |
| **0** | Catálogo zonas + mapear ubigeos (Lima completa por ubicación; Callao; Norte Chico+Huaura; Norte/Sur por departamentos) |
| **1** | Zona en grupo y cliente + herencia + edición solo 105/6 + listado revisar (Gamarra / excepciones) |
| **2** | Asignación vendedores ↔ zonas ✅ |
| **3** | Metas/retos mensuales por vendedor (monto, clientes nuevos, modelos activos) ✅ `metas-retos` |
| **4** | Dos mapas ✅ `mapas-zonas` (Lima + Perú sin Lima; ficha con vendedores activos y #clientes zona efectiva) |
| **5** | Reporte de comisiones |

Solo Vascorp. Sin liquidación de pago. Sin Vasco Online.

---

## 7. Fuera de alcance

Dirección fiscal, cobranza como meta, liquidar comisiones, Vasco Online, GIS/GPS avanzado, código (aún).

---

## 8. Decisiones cerradas recientes

- **Opción A:** override del cliente gana sobre el grupo.  
- **Datos:** catálogo de zonas + reglas ubigeo→zona + campo nullable en cliente y en grupo. NULL = seguir cascada; con valor = no usar ubigeo para ese caso.

No quedan preguntas bloqueantes para diseñar Fase 0–1. Cuando pidas implementación, se empieza por ahí (aún sin código hasta que lo indiques).

---

## 9. Resumen

- **Solo vendedores activos** (`estado_decisiones = 1`) en toda la iniciativa.  
- Económica = **solo Gamarra**; Moderna = San Isidro y similares por ubicación.  
- Resolución zona: **cliente (si tiene campo) → grupo (si tiene campo) → ubigeo**.  
- Campos nuevos en cliente y grupo + tablas de catálogo/reglas (no solo ubigeo).  
- Metas/comisiones → vendedor del maestro. Modelos activos = distintos del mes.  
- Edición zona: 105 y 6. Por fases; solo Vascorp.
