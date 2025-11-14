# Manual de Preferencias de Impresión de Letras

## Descripción General
Este sistema permite a cada usuario configurar su preferencia personal para la impresión de letras, pudiendo elegir entre impresión **con fondo** o **sin fondo**. La preferencia se guarda automáticamente en el navegador del usuario.

## Características Implementadas

### 1. **Botón de Impresión con Configuración**
Cada letra (tipo_doc = "85") ahora tiene un botón de impresión dividido:
- **Botón principal** (verde con ícono de impresora): Imprime usando la preferencia guardada
- **Botón de configuración** (engranaje): Despliega menú con opciones

### 2. **Menú de Configuración**
Al hacer clic en el engranaje, aparece un menú con las siguientes opciones:
- 🖼️ **Con fondo**: Imprime usando `letra_full.php` (con diseño completo y fondo)
- 📄 **Sin fondo**: Imprime usando `imprimir_letra.php` (versión simple)
- 🔄 **Restablecer preferencia**: Borra la preferencia guardada

### 3. **Flujo de Uso Primera Vez**
Cuando un usuario imprime por primera vez:
1. Se muestra un diálogo preguntando su preferencia
2. El usuario elige "Con fondo" o "Sin fondo"
3. La preferencia se guarda automáticamente en `localStorage`
4. La letra se imprime con la opción seleccionada

### 4. **Flujo de Uso Subsecuente**
Una vez guardada la preferencia:
1. Al hacer clic en imprimir, se usa automáticamente la preferencia guardada
2. Se muestra una notificación breve indicando qué preferencia se está usando
3. El usuario puede cambiar su preferencia en cualquier momento desde el menú

## Archivos Modificados

### PHP (Backend)
1. **`ajax/cuentas-corrientes/tabla-cuentas.ajax.php`**
   - Agregado botón de configuración con dropdown
   - Modificado en líneas 42-51

2. **`ajax/cuentas-corrientes/tabla-cuentas-pendientes.ajax.php`**
   - Agregado botón de configuración con dropdown
   - Modificado en líneas 51-67

3. **`ajax/cuentas-corrientes/tabla-cuentas-canceladas.ajax.php`**
   - Agregado botón de configuración con dropdown
   - Modificado en líneas 44-55

### JavaScript (Frontend)
**`vistas/js/cuentas.js`**

Funciones agregadas:
- `imprimirLetraConPreferencia(numCuenta, tipoImpresion)` - Imprime según preferencia
- Manejadores para `.btnConfigImpresion` - Guarda la preferencia
- Manejadores para `.btnResetPreferencia` - Borra la preferencia
- Manejadores mejorados para `.btnImprimirLetra` - Verifica y usa preferencia

Se implementó para 3 tablas:
- `.tablaCuentas`
- `.tablaCuentasPendientes`
- `.tablaCuentasAprobadas`

## Almacenamiento de Preferencias

La preferencia se guarda en `localStorage` con la clave:
```javascript
localStorage.setItem("preferenciaImpresionLetra", "fondo"); // o "simple"
```

**Ventajas:**
- ✅ Persistente entre sesiones
- ✅ Específico por navegador/computadora
- ✅ No requiere base de datos
- ✅ Fácil de resetear

## Experiencia de Usuario (UX)

### Primera Impresión
```
Usuario hace clic en [🖨️]
    ↓
[Aparece diálogo]
"¿Cómo deseas imprimir la letra?"
  [📄 Sin fondo]  [🖼️ Con fondo]
    ↓
Se guarda preferencia y se imprime
```

### Impresiones Subsecuentes
```
Usuario hace clic en [🖨️]
    ↓
[Notificación rápida (1.5s)]
"Imprimiendo..."
"Usando preferencia guardada: Con fondo"
    ↓
Se abre nueva ventana con impresión
```

### Cambiar Preferencia
```
Usuario hace clic en [⚙️]
    ↓
[Menú desplegable]
  🖼️ Con fondo
  📄 Sin fondo
  ──────────
  🔄 Restablecer preferencia
    ↓
Selecciona opción
    ↓
[Notificación]
"Preferencia guardada"
```

## Ventajas de esta Implementación

1. **Personalización por Usuario**: Cada persona configura según su necesidad
2. **No Invasivo**: La primera vez pregunta amablemente
3. **Fácil de Cambiar**: Menú accesible siempre visible
4. **Feedback Claro**: Notificaciones informan qué está pasando
5. **Memoria del Sistema**: No molesta preguntando cada vez
6. **Fácil Reset**: Opción de volver al estado inicial

## Casos de Uso

### Caso 1: Usuario de Oficina
- Prefiere **sin fondo** para ahorrar tinta
- Configura una vez y todas sus impresiones son sin fondo
- Reduce costos de impresión

### Caso 2: Usuario Comercial
- Prefiere **con fondo** para presentaciones a clientes
- Impresiones más profesionales y con diseño
- Mejor imagen corporativa

### Caso 3: Usuario que Necesita Ambos
- Puede cambiar fácilmente desde el menú
- No necesita recordar qué archivo usar
- Sistema se adapta a sus necesidades

## Soporte

Si un usuario tiene problemas:
1. Verificar que el navegador permita `localStorage`
2. Limpiar caché si la preferencia no se guarda
3. Usar "Restablecer preferencia" para volver a empezar

## Futuras Mejoras (Opcionales)

- 🔧 Agregar más estilos de impresión
- 👤 Guardar preferencias en base de datos por usuario
- 📊 Estadísticas de uso de cada tipo de impresión
- ⚡ Opción de vista previa antes de imprimir

