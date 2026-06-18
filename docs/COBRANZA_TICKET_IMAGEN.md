# Cobranzas — Ticket virtual como imagen (post-MVP)

**Estado:** planificado, **no incluido en el MVP** de cobranzas en efectivo.

**Objetivo:** que el vendedor pueda **descargar o compartir** el ticket virtual como PNG/JPEG, y que la misma imagen sirva como adjunto en WhatsApp (Evolution API) y en el futuro portal del cliente.

**Contexto producto:** `docs/PRODUCTO.md` (Fase 1, ítem 6).  
**Notificaciones:** `docs/COBRANZA_NOTIFICACIONES.md` (plantilla WhatsApp con adjunto).

---

## Estado actual (MVP)

| Qué | Implementado |
|-----|--------------|
| Ticket virtual en HTML | Código listo; **oculto en UI** (`VisitCollectUiHelper::isTicketVirtualUiVisible() === false`) |
| Compartir ticket | Código listo; botón oculto mientras el flag esté en `false` |
| QR escaneable | No — ícono decorativo (`ri-qr-code-line`) |
| Imagen PNG guardada | No — columna BD lista, sin uso |
| Descarga de imagen | No |
| Códigos `TKT-*` / `COB-*` en BD | Sí — el backend sigue generándolos aunque no se muestren |

**Pantallas afectadas (código conservado, render condicional):**

- `/visit/collect` — paso 2 del wizard (vista previa + copy de ticket virtual)
- `/visit/collect-success?collection_id=…`
- `/visit/collection?collection_id=…`

### Flag de visibilidad (reactivar más adelante)

Archivo único:

```php
// admin/src/Helpers/VisitCollectUiHelper.php
public static function isTicketVirtualUiVisible(): bool
{
    return false; // → true al implementar ticket imagen / preview definitivo
}
```

Cuando pase a `true`, vuelven a mostrarse:

- Bloque `.vasco-visit-ticket-preview` (wizard, éxito, detalle)
- Checkbox «Le mostré o le compartí el ticket» (paso confirmar)
- Botón «Compartir ticket»
- Badge «Ticket mostrado o compartido»
- Fila «Ticket virtual (previo)» en resumen del wizard

**JS:** `data-ticket-ui-visible` en `[data-vasco-visit-collect]` (`visit-collect.js`).

Mientras está oculto, éxito y detalle muestran **monto** (y ticket físico si aplica) en el resumen en lugar del preview.

---

## Por qué no está en el MVP

- El vendedor ya puede dar constancia en campo con el ticket en pantalla y compartir texto.
- La generación en servidor (layout + QR real + storage + auditoría) suma esfuerzo sin bloquear el flujo de cobro.
- WhatsApp automático (Evolution) puede arrancar con mensaje de texto; el adjunto se activa cuando exista `ticket_image_path_collection`.

---

## Enfoques posibles

### A — Recomendado: generación en servidor (PHP)

Generar el PNG al registrar la cobranza (o justo después, en la misma transacción o job ligero).

| Ventajas | Desventajas |
|----------|-------------|
| Misma imagen para descarga, WhatsApp y portal | Requiere lib gráfica (GD/Imagick) + lib QR |
| QR real y consistente en todos los dispositivos | Layout del ticket hay que mantenerlo en PHP |
| Persistencia en `storage/` con URL pública | Más código backend |

### B — Alternativa rápida: solo navegador (html2canvas)

Capturar el DOM del ticket y ofrecer descarga/compartir sin guardar en servidor.

| Ventajas | Desventajas |
|----------|-------------|
| Implementación corta (~1–2 h) | No alimenta WhatsApp Evolution ni portal |
| Sin cambios en BD/storage | Calidad variable en móviles |
| | QR seguiría siendo falso sin otra lib en cliente |

**Decisión documentada:** implementar **A** cuando se priorice ticket imagen; **B** solo si se necesita un parche temporal antes de A.

---

## Modelo de datos (ya existente)

Migración `0012_create_collections_tables.sql`:

```sql
`ticket_image_path_collection` varchar(500) DEFAULT NULL
  COMMENT 'Ruta pública en storage del ticket virtual como imagen (fase posterior).'
```

Auditoría (`collection_audits`):

- Acción: `ticket_image_saved` (constante `CollectionRepository::AUDIT_TICKET_IMAGE_SAVED`)
- Label UI: `CollectionService::auditActionLabel()` → «Imagen de ticket guardada»

**Valor almacenado en `ticket_image_path_collection`:** ruta **relativa** bajo `storage/`, por ejemplo:

```
collections/2026/06/TKT-2026-014201.png
```

**URL pública:** `{STORAGE_HOST}/collections/2026/06/TKT-2026-014201.png`  
(`STORAGE_HOST` en `.env`, ej. `http://storage.vasco.io:8084`)

---

## Generación de imagen (servidor)

### Contenido del ticket (PNG)

Alinear con el preview HTML actual:

| Campo | Fuente |
|-------|--------|
| Marca | «Vasco» |
| Tipo | «Ticket virtual» |
| Código ticket | `ticket_code_collection` (`TKT-YYYY-######`) |
| Monto | `amount_collection` formateado `S/ X,XXX.XX` |
| Cliente | nombre legal/comercial |
| Fecha/hora | `date_created_collection` (zona `America/Lima`) |
| Medio | «Efectivo» |
| Ticket físico | `physical_ticket_code_collection` si existe |
| Código cobranza | `code_collection` (`COB-YYYY-####`) — pie pequeño o metadata QR |

### QR

- **Payload sugerido (texto):** `TKT-2026-014201` o URL futura de verificación, ej. `https://cliente.vasco.io/ticket/{ticket_code}` (definir en portal fase 2).
- **MVP imagen:** mínimo el código `ticket_code_collection` para trazabilidad interna.
- Librería PHP sugerida: `endroid/qr-code` o equivalente liviano (evaluar sin Composer pesado si el proyecto lo permite).

### Servicio sugerido

```
admin/src/Services/CollectionTicketImageService.php
```

Responsabilidades:

1. Recibir datos de cobranza ya persistidos.
2. Renderizar imagen (GD o Imagick).
3. Escribir archivo en `BASE_PATH_STORAGE/collections/{Y}/{m}/`.
4. Devolver ruta relativa.
5. No exponer paths arbitrarios; nombre de archivo = `{ticket_code_collection}.png` (sanitizado).

Invocación:

- Opción 1 (preferida): al final de `CollectionService::register()` tras commit, si falla imagen → log + cobranza OK (no rollback).
- Opción 2: endpoint/job `POST /ajax/collections/generate-ticket-image.php` llamado desde éxito (segunda petición).

### Actualización BD + auditoría

Tras guardar archivo:

1. `UPDATE collections SET ticket_image_path_collection = :path WHERE id_collection = :id`
2. Insert `collection_audits` con `action = ticket_image_saved`, `metadata` → `{ "path": "...", "mime": "image/png", "bytes": N }`, mismo `trace_id`.

---

## Storage

| Aspecto | Regla |
|---------|--------|
| Raíz | `BASE_PATH_STORAGE` (`storage/` en monorepo) |
| Subcarpeta | `collections/{YYYY}/{MM}/` |
| Nombre archivo | `{ticket_code_collection}.png` |
| Permisos | `0644` archivo, `0755` directorios |
| MIME | `image/png` (JPEG opcional si se prioriza peso) |
| Tamaño orientativo | ~50–150 KB (ancho ~600–800 px) |

Misma línea que comprobantes de transferencia: archivos **públicos** servidos por vhost `storage` (sin auth en MVP; en producción valorar URLs firmadas si el negocio lo exige).

---

## UI admin (visita)

### Pantalla de éxito y detalle

Archivos:

- `admin/views/pages/visit/actions/collect-success.php`
- `admin/views/pages/visit/actions/collection.php`
- `admin/public/customs/js/visit/visit-collect-success.js` (renombrar o ampliar a ticket-share)

**Botones propuestos:**

| Acción | Comportamiento |
|--------|----------------|
| Compartir ticket | Si hay imagen: `navigator.share({ files: [File] })` o descarga; si no: texto (comportamiento actual) |
| Descargar imagen | `<a download>` con URL de storage o blob generado |

Atributos `data-*` útiles en el root:

- `data-ticket-image-url` — URL pública cuando exista `ticket_image_path_collection`

**Estados:**

- Imagen generándose: spinner breve o mensaje «Preparando ticket…» (solo si generación es async).
- Sin imagen (fallo): mantener compartir texto + toast discreto en logs.

### Mockup de referencia

`admin/public/mockups/visita/cobranza-exito.html` — botón «Compartir ticket».

---

## Integración WhatsApp (Evolution API)

Cuando exista imagen, ampliar `WhatsappNotificationService` (fase 1.3):

```
POST Evolution: mensaje texto + mediaUrl o upload del PNG
```

Plantilla en `docs/COBRANZA_NOTIFICACIONES.md`:

> Adjunto imagen ticket cuando exista `ticket_image_path_collection`.

Cola `notification_queue`: en `payload` incluir `ticket_image_url`.

---

## API / portal (futuro)

| Consumidor | Uso |
|------------|-----|
| Portal cliente (fase 2) | Mostrar thumbnail o enlace de descarga en detalle de cobranza |
| API lectura | Campo `ticket_image_url` en respuesta de colección |

---

## Archivos a crear o modificar (checklist implementación)

```
admin/src/Services/CollectionTicketImageService.php     (nuevo)
admin/src/Services/CollectionService.php              (register + mapStoredCollection + URL pública)
admin/src/Repositories/CollectionRepository.php       (update ticket_image_path)
admin/views/pages/visit/actions/collect-success.php   (botón descargar / data-ticket-image-url)
admin/views/pages/visit/actions/collection.php        (idem)
admin/public/customs/js/visit/visit-collect-success.js (share con File / download)
storage/collections/.gitkeep                          (estructura)
docs/VISITA_VENDEDOR_CHECKLIST.md                     (marcar hecho)
docs/COBRANZA_NOTIFICACIONES.md                       (fase WhatsApp adjunto)
composer.json                                         (solo si se añade lib QR; evaluar)
```

**Dependencia PHP:** extensión `gd` o `imagick` en imagen Docker (verificar `docker/web/Dockerfile`).

---

## QA

- [ ] Tras registrar cobranza, existe PNG en `storage/collections/YYYY/MM/TKT-….png`
- [ ] `ticket_image_path_collection` poblado en BD
- [ ] Auditoría `ticket_image_saved` con `trace_id`
- [ ] Fallo al generar imagen **no** anula la cobranza
- [ ] Botón «Descargar» abre/guarda PNG en móvil (iOS Safari, Android Chrome)
- [ ] «Compartir» envía imagen por WhatsApp/Telegram cuando el SO lo permite
- [ ] Detalle de cobranza muestra la misma imagen que éxito
- [ ] QR escaneable devuelve al menos `ticket_code_collection`
- [ ] URL pública accesible vía `STORAGE_HOST`

---

## Orden respecto a otras entregas de cobranzas

| Prioridad | Entrega | Relación con ticket imagen |
|-----------|---------|----------------------------|
| Hecho MVP | Registro + ticket HTML + compartir texto | Base UI |
| Siguiente | Rendición `delivered` | Independiente |
| Siguiente | WhatsApp texto (Evolution) | Puede ir antes; adjunto después |
| **Esta doc** | Ticket PNG + descarga/compartir imagen | Habilita adjunto WhatsApp |
| Después | Portal cliente | Reutiliza URL de storage |

**Issue sugerido:** `#18-feat: ticket virtual como imagen PNG (storage + compartir)`

---

## Referencias

- BD: `migrations/0012_create_collections_tables.sql`
- UI ticket: `admin/views/pages/visit/actions/collect-success.php`, `actions/collection.php`
- Compartir texto: `admin/public/customs/js/visit/visit-collect-success.js`
- CSS ticket: `admin/public/customs/css/visit/visit.css` (`.vasco-visit-ticket-preview`)
- Producto: `docs/PRODUCTO.md`
- Notificaciones: `docs/COBRANZA_NOTIFICACIONES.md`
- Checklist visita: `docs/VISITA_VENDEDOR_CHECKLIST.md` (paso 10)
