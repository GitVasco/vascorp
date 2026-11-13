<?php

require_once "conexion.php";

/**
 * Facturas de exportación (docs/efact/EXPORTACION-diseno.md).
 * Fase 1: país del cliente (clientesjf.pais_iso) y partida arancelaria por línea (exp_partida_lineajf).
 * De clientesjf solo se lee o se actualiza pais_iso; articulojf y talonariosjf solo se leen.
 */
class ModeloExportacion
{
    public static function mdlTablasListas()
    {
        $pdo = Conexion::conectar();
        $faltan = array();
        $stmt = $pdo->query("SHOW TABLES LIKE 'exp_partida_lineajf'");
        if (!$stmt || !$stmt->fetchColumn()) {
            $faltan[] = "exp_partida_lineajf";
        }
        if (!self::mdlTienePais()) {
            $faltan[] = "clientesjf.pais_iso";
        }
        return $faltan;
    }

    public static function mdlTienePais()
    {
        static $tiene = null;
        if ($tiene === null) {
            $stmt = Conexion::conectar()->query("SHOW COLUMNS FROM clientesjf LIKE 'pais_iso'");
            $tiene = $stmt && $stmt->fetch() ? true : false;
        }
        return $tiene;
    }

    /** Series de factura de exportación (FX...) registradas en talonariosjf. */
    public static function mdlSeriesFX()
    {
        $sql = "SELECT TRIM(serie_factura) AS serie, COALESCE(facturas, 0) AS correlativo
                FROM talonariosjf
                WHERE TRIM(serie_factura) LIKE 'FX%'
                ORDER BY serie_factura";
        return Conexion::conectar()->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    }

    /* =====================================================
       CLIENTES DEL EXTERIOR (clientesjf.pais_iso)
    ===================================================== */

    /** Clientes con país distinto de Perú. */
    public static function mdlClientesExterior()
    {
        $sql = "SELECT c.codigo, c.nombre, c.pais_iso, c.tipo_documento, c.documento, c.direccion, c.email,
                    c.lista_precios, c.estado
                FROM clientesjf c
                WHERE c.pais_iso IS NOT NULL AND c.pais_iso <> '' AND c.pais_iso <> 'PE'
                ORDER BY c.nombre";
        return Conexion::conectar()->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    }

    /** Clientes que ya tienen facturas de exportación y aún no tienen país. */
    public static function mdlClientesFXSinPais()
    {
        $sql = "SELECT v.cliente AS codigo, c.nombre, COUNT(*) AS facturas, MAX(v.fecha) AS ultima
                FROM ventajf v
                INNER JOIN clientesjf c ON c.codigo = v.cliente
                WHERE v.exportacion = 1 AND (c.pais_iso IS NULL OR c.pais_iso = '' OR c.pais_iso = 'PE')
                GROUP BY v.cliente, c.nombre
                ORDER BY ultima DESC";
        return Conexion::conectar()->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function mdlPaisCliente($codigo)
    {
        if (!self::mdlTienePais()) {
            return "";
        }
        $stmt = Conexion::conectar()->prepare("SELECT IFNULL(pais_iso, '') FROM clientesjf WHERE codigo = :c LIMIT 1");
        $stmt->bindValue(":c", $codigo, PDO::PARAM_STR);
        $stmt->execute();
        $v = $stmt->fetchColumn();
        return $v === false ? "" : strtoupper(trim($v));
    }

    /** Facturas de exportación ya emitidas (ventajf.exportacion = 1). Líneas: movimientosjf_2026, igual que el CSV actual. */
    public static function mdlFacturasFX($desde, $hasta)
    {
        $sql = "SELECT v.documento, v.fecha, v.cliente, c.nombre, IFNULL(c.pais_iso, '') AS pais_iso,
                    v.total, v.tipo_moneda,
                    (SELECT COUNT(*) FROM movimientosjf_2026 m WHERE m.tipo = 'S03' AND m.documento = v.documento) AS lineas
                FROM ventajf v
                LEFT JOIN clientesjf c ON c.codigo = v.cliente
                WHERE v.tipo = 'S03' AND v.exportacion = 1 AND v.documento LIKE 'FX%'
                  AND v.fecha BETWEEN :desde AND :hasta
                ORDER BY v.fecha DESC, v.documento DESC
                LIMIT 500";
        $stmt = Conexion::conectar()->prepare($sql);
        $stmt->bindValue(":desde", $desde, PDO::PARAM_STR);
        $stmt->bindValue(":hasta", $hasta, PDO::PARAM_STR);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /* =====================================================
       PEDIDOS (temporaljf): solo lectura
    ===================================================== */

    /** codigo => pais_iso de los clientes del exterior (marca en las tablas de pedidos). */
    public static function mdlMapaClientesExterior()
    {
        $r = array();
        if (!self::mdlTienePais()) {
            return $r;
        }
        $rows = Conexion::conectar()->query(
            "SELECT TRIM(codigo) AS codigo, pais_iso FROM clientesjf
             WHERE pais_iso IS NOT NULL AND pais_iso <> '' AND pais_iso <> 'PE'"
        )->fetchAll(PDO::FETCH_ASSOC);
        foreach ($rows as $row) {
            $r[$row["codigo"]] = strtoupper($row["pais_iso"]);
        }
        return $r;
    }

    /** Cliente con su país y lista de precios (pantalla crear pedido). */
    public static function mdlClienteExportacion($codigo)
    {
        if (!self::mdlTienePais()) {
            return null;
        }
        $stmt = Conexion::conectar()->prepare(
            "SELECT TRIM(codigo) AS codigo, nombre, IFNULL(pais_iso, '') AS pais_iso, lista_precios
             FROM clientesjf WHERE codigo = :c LIMIT 1"
        );
        $stmt->bindValue(":c", $codigo, PDO::PARAM_STR);
        $stmt->execute();
        $r = $stmt->fetch(PDO::FETCH_ASSOC);
        return $r ?: null;
    }

    /** Pedido con el país del cliente (validación antes de facturar). */
    public static function mdlPedidoExportacion($codigoPedido)
    {
        if (!self::mdlTienePais()) {
            return null;
        }
        $stmt = Conexion::conectar()->prepare(
            "SELECT t.codigo, TRIM(t.cliente) AS cliente, t.lista, c.nombre, IFNULL(c.pais_iso, '') AS pais_iso
             FROM temporaljf t
             LEFT JOIN clientesjf c ON c.codigo = t.cliente
             WHERE t.codigo = :p LIMIT 1"
        );
        $stmt->bindValue(":p", $codigoPedido, PDO::PARAM_STR);
        $stmt->execute();
        $r = $stmt->fetch(PDO::FETCH_ASSOC);
        return $r ?: null;
    }

    /* =====================================================
       FASE 3B: EMITIR DESDE PEDIDO (exp_facturajf)
    ===================================================== */

    public static function mdlTablaFacturas()
    {
        $stmt = Conexion::conectar()->query("SHOW TABLES LIKE 'exp_facturajf'");
        return $stmt && $stmt->fetchColumn() ? true : false;
    }

    /** Pedidos de clientes del exterior listos para facturar (mismos estados que el modal Facturar). */
    public static function mdlBandejaPedidos()
    {
        $sql = "SELECT t.codigo, DATE(t.fecha) AS fecha, t.estado, TRIM(t.cliente) AS cliente, c.nombre,
                    c.pais_iso, t.lista, t.vendedor, t.total, cv.descripcion AS condicion,
                    (SELECT COALESCE(SUM(d.cantidad), 0) FROM detalle_temporal d WHERE d.codigo = t.codigo) AS unidades,
                    (SELECT COUNT(*) FROM detalle_temporal d WHERE d.codigo = t.codigo AND (d.precio IS NULL OR d.precio <= 0)) AS sin_precio
                FROM temporaljf t
                INNER JOIN clientesjf c ON c.codigo = t.cliente
                LEFT JOIN condiciones_ventajf cv ON cv.id = t.condicion_venta
                WHERE t.estado IN ('APROBADO', 'APT', 'CONFIRMADO')
                  AND c.pais_iso IS NOT NULL AND c.pais_iso <> '' AND c.pais_iso <> 'PE'
                ORDER BY t.fecha DESC, t.codigo DESC";
        return Conexion::conectar()->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    }

    /** Cabecera del pedido para emitir (solo lectura). */
    public static function mdlPedidoEmitir($codigo)
    {
        $sql = "SELECT t.codigo, DATE(t.fecha) AS fecha, t.estado, TRIM(t.cliente) AS cliente, c.nombre,
                    c.tipo_documento, c.documento, c.direccion, c.email, IFNULL(c.pais_iso, '') AS pais_iso,
                    t.lista, t.vendedor, t.op_gravada, t.descuento_total, t.igv, t.total,
                    IF(t.op_gravada > 0, ROUND(t.descuento_total / t.op_gravada * 100, 2), 0) AS dscto,
                    t.condicion_venta, cv.descripcion AS condicion, cv.dias
                FROM temporaljf t
                LEFT JOIN clientesjf c ON c.codigo = t.cliente
                LEFT JOIN condiciones_ventajf cv ON cv.id = t.condicion_venta
                WHERE t.codigo = :p LIMIT 1";
        $stmt = Conexion::conectar()->prepare($sql);
        $stmt->bindValue(":p", $codigo, PDO::PARAM_STR);
        $stmt->execute();
        $r = $stmt->fetch(PDO::FETCH_ASSOC);
        return $r ?: null;
    }

    /** Detalle agrupado por modelo y precio, con stock del almacén 01. */
    public static function mdlDetallePedido($codigo)
    {
        $sql = "SELECT a.modelo, MAX(a.nombre) AS nombre, MAX(a.linea) AS linea, d.precio,
                    SUM(d.cantidad) AS cantidad, SUM(d.total) AS total,
                    SUM(IF(COALESCE(a.stock01, 0) < d.cantidad, 1, 0)) AS sin_stock,
                    COUNT(*) AS skus
                FROM detalle_temporal d
                LEFT JOIN articulojf a ON a.articulo = d.articulo
                WHERE d.codigo = :p
                GROUP BY a.modelo, d.precio
                ORDER BY a.modelo";
        $stmt = Conexion::conectar()->prepare($sql);
        $stmt->bindValue(":p", $codigo, PDO::PARAM_STR);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function mdlFacturaPorPedido($pedido)
    {
        $stmt = Conexion::conectar()->prepare("SELECT * FROM exp_facturajf WHERE pedido = :p ORDER BY id DESC LIMIT 1");
        $stmt->bindValue(":p", $pedido, PDO::PARAM_STR);
        $stmt->execute();
        $r = $stmt->fetch(PDO::FETCH_ASSOC);
        return $r ?: null;
    }

    public static function mdlFactura($id)
    {
        $stmt = Conexion::conectar()->prepare("SELECT * FROM exp_facturajf WHERE id = :id LIMIT 1");
        $stmt->bindValue(":id", (int) $id, PDO::PARAM_INT);
        $stmt->execute();
        $r = $stmt->fetch(PDO::FETCH_ASSOC);
        return $r ?: null;
    }

    public static function mdlRegistrarFactura($d)
    {
        $sql = "INSERT INTO exp_facturajf (tipo, documento, pedido, cliente, pais_iso, moneda, tipo_cambio, total, estado, usuario_registro)
                VALUES ('S03', :documento, :pedido, :cliente, :pais, 'USD', :tc, :total, 'GENERADO', :usuario)";
        $stmt = Conexion::conectar()->prepare($sql);
        $stmt->bindValue(":documento", $d["documento"], PDO::PARAM_STR);
        $stmt->bindValue(":pedido", $d["pedido"], PDO::PARAM_STR);
        $stmt->bindValue(":cliente", $d["cliente"], PDO::PARAM_STR);
        $stmt->bindValue(":pais", $d["pais_iso"], PDO::PARAM_STR);
        if ($d["tipo_cambio"] === null) {
            $stmt->bindValue(":tc", null, PDO::PARAM_NULL);
        } else {
            $stmt->bindValue(":tc", $d["tipo_cambio"], PDO::PARAM_STR);
        }
        $stmt->bindValue(":total", $d["total"], PDO::PARAM_STR);
        $stmt->bindValue(":usuario", $d["usuario"], PDO::PARAM_STR);
        return $stmt->execute();
    }

    /** Emitidas desde la pantalla nueva, con el estado de la venta (ventajf) para avisar si alguien la mandó por el camino antiguo. */
    public static function mdlFacturasEmitidas($desde, $hasta)
    {
        $sql = "SELECT f.id, f.documento, f.pedido, f.cliente, c.nombre, f.pais_iso, f.moneda, f.tipo_cambio, f.total,
                    f.estado, f.archivo, f.usuario_registro, f.creado_en, f.enviado_en, v.estado AS estado_venta
                FROM exp_facturajf f
                LEFT JOIN clientesjf c ON c.codigo = f.cliente
                LEFT JOIN ventajf v ON v.tipo = f.tipo AND v.documento = f.documento
                WHERE DATE(f.creado_en) BETWEEN :desde AND :hasta
                ORDER BY f.id DESC
                LIMIT 500";
        $stmt = Conexion::conectar()->prepare($sql);
        $stmt->bindValue(":desde", $desde, PDO::PARAM_STR);
        $stmt->bindValue(":hasta", $hasta, PDO::PARAM_STR);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function mdlGuardarTipoCambio($id, $tc)
    {
        $stmt = Conexion::conectar()->prepare("UPDATE exp_facturajf SET tipo_cambio = :tc WHERE id = :id");
        if ($tc === null) {
            $stmt->bindValue(":tc", null, PDO::PARAM_NULL);
        } else {
            $stmt->bindValue(":tc", $tc, PDO::PARAM_STR);
        }
        $stmt->bindValue(":id", (int) $id, PDO::PARAM_INT);
        return $stmt->execute();
    }

    /** Pasa a ENVIADO solo si seguía GENERADO (evita doble envío). */
    public static function mdlMarcarEnviada($id, $archivo, $usuario)
    {
        $stmt = Conexion::conectar()->prepare(
            "UPDATE exp_facturajf SET estado = 'ENVIADO', archivo = :archivo, usuario_envio = :u, enviado_en = NOW()
             WHERE id = :id AND estado = 'GENERADO'"
        );
        $stmt->bindValue(":archivo", $archivo, PDO::PARAM_STR);
        $stmt->bindValue(":u", $usuario, PDO::PARAM_STR);
        $stmt->bindValue(":id", (int) $id, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->rowCount() > 0;
    }

    /** Candado por pedido (GET_LOCK) en una conexión propia, para que no se emita dos veces. */
    public static function mdlBloquearPedido($pdo, $pedido)
    {
        $stmt = $pdo->prepare("SELECT GET_LOCK(:k, 5)");
        $stmt->bindValue(":k", "exp_pedido_" . $pedido, PDO::PARAM_STR);
        $stmt->execute();
        return (int) $stmt->fetchColumn() === 1;
    }

    public static function mdlLiberarPedido($pdo, $pedido)
    {
        $stmt = $pdo->prepare("SELECT RELEASE_LOCK(:k)");
        $stmt->bindValue(":k", "exp_pedido_" . $pedido, PDO::PARAM_STR);
        $stmt->execute();
    }

    /** Tipo de cambio del día en totalesjf (si existe la fila del día). */
    public static function mdlTipoCambioTotales($fecha)
    {
        $stmt = Conexion::conectar()->prepare(
            "SELECT DATE(fecha) AS fecha, ROUND(COALESCE(cambio_compra, 0), 4) AS cambio_compra,
                    ROUND(COALESCE(cambio_venta, 0), 4) AS cambio_venta
             FROM totalesjf WHERE DATE(fecha) = :f LIMIT 1"
        );
        $stmt->bindValue(":f", $fecha, PDO::PARAM_STR);
        $stmt->execute();
        $r = $stmt->fetch(PDO::FETCH_ASSOC);
        return $r ?: null;
    }

    /** Mismo campo que completa Utilidades desde totalesjf; los reportes lo usan para pasar USD a soles. */
    public static function mdlGuardarTipoCambioVenta($documento, $tc)
    {
        $stmt = Conexion::conectar()->prepare("UPDATE ventajf SET tipo_cambio = :tc WHERE tipo = 'S03' AND documento = :d");
        if ($tc === null) {
            $stmt->bindValue(":tc", 0, PDO::PARAM_STR);
        } else {
            $stmt->bindValue(":tc", $tc, PDO::PARAM_STR);
        }
        $stmt->bindValue(":d", $documento, PDO::PARAM_STR);
        return $stmt->execute();
    }

    /** Si el archivo no llegó a la carpeta de eFact, se deja otra vez para enviar. */
    public static function mdlVolverGenerada($id)
    {
        $stmt = Conexion::conectar()->prepare(
            "UPDATE exp_facturajf SET estado = 'GENERADO', archivo = NULL, usuario_envio = NULL, enviado_en = NULL WHERE id = :id"
        );
        $stmt->bindValue(":id", (int) $id, PDO::PARAM_INT);
        return $stmt->execute();
    }

    /** Solo actualiza pais_iso del cliente (NULL = Perú). */
    public static function mdlGuardarPaisCliente($codigo, $paisIso)
    {
        $stmt = Conexion::conectar()->prepare("UPDATE clientesjf SET pais_iso = :pais WHERE codigo = :codigo");
        if ($paisIso === null) {
            $stmt->bindValue(":pais", null, PDO::PARAM_NULL);
        } else {
            $stmt->bindValue(":pais", $paisIso, PDO::PARAM_STR);
        }
        $stmt->bindValue(":codigo", $codigo, PDO::PARAM_STR);
        return $stmt->execute();
    }

    /* =====================================================
       MODELOS ACTIVOS Y PRECIO DE LISTA 1 (preciojf.precio1)
    ===================================================== */

    /** Modelos activos con el precio de lista 1 vigente (última fila de preciojf, como Maestros/Ficha). */
    public static function mdlModelosPrecio1()
    {
        $sql = "SELECT t.modelo, t.nombre, m.marca, t.linea, t.tipo,
                    COALESCE(NULLIF(TRIM(t.cod_unidad), ''), 'C62') AS cod_unidad, t.imagen,
                    p.precio1, IF(p.id IS NULL, 0, 1) AS tiene_precio
                FROM modelojf t
                LEFT JOIN marcasjf m ON m.id = t.id_marca
                LEFT JOIN (
                    SELECT TRIM(modelo) AS modelo, MAX(id) AS id
                    FROM preciojf
                    GROUP BY TRIM(modelo)
                ) up ON up.modelo = TRIM(t.modelo)
                LEFT JOIN preciojf p ON p.id = up.id
                WHERE UPPER(TRIM(t.estado)) = 'ACTIVO'
                ORDER BY t.modelo";
        return Conexion::conectar()->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    }

    /** Solo cambia precio1 del modelo (mismo criterio que ModeloModelos::mdlEditarPrecio: WHERE modelo). */
    public static function mdlGuardarPrecio1($modelo, $precio)
    {
        $stmt = Conexion::conectar()->prepare("UPDATE preciojf SET precio1 = :precio WHERE TRIM(modelo) = :modelo");
        $stmt->bindValue(":precio", $precio, PDO::PARAM_STR);
        $stmt->bindValue(":modelo", $modelo, PDO::PARAM_STR);
        if (!$stmt->execute()) {
            return false;
        }
        return $stmt->rowCount() > 0 || self::mdlTieneFilaPrecio($modelo);
    }

    /** Precio1 vigente (última fila); para verificar que lo guardado no se recortó por el tipo de columna. */
    public static function mdlPrecio1Actual($modelo)
    {
        $stmt = Conexion::conectar()->prepare("SELECT precio1 FROM preciojf WHERE TRIM(modelo) = :modelo ORDER BY id DESC LIMIT 1");
        $stmt->bindValue(":modelo", $modelo, PDO::PARAM_STR);
        $stmt->execute();
        $v = $stmt->fetchColumn();
        return $v === false ? null : (float) $v;
    }

    public static function mdlTieneFilaPrecio($modelo)
    {
        $stmt = Conexion::conectar()->prepare("SELECT COUNT(*) FROM preciojf WHERE TRIM(modelo) = :modelo");
        $stmt->bindValue(":modelo", $modelo, PDO::PARAM_STR);
        $stmt->execute();
        return (int) $stmt->fetchColumn() > 0;
    }

    public static function mdlModeloActivo($modelo)
    {
        $stmt = Conexion::conectar()->prepare("SELECT COUNT(*) FROM modelojf WHERE TRIM(modelo) = :modelo AND UPPER(TRIM(estado)) = 'ACTIVO'");
        $stmt->bindValue(":modelo", $modelo, PDO::PARAM_STR);
        $stmt->execute();
        return (int) $stmt->fetchColumn() > 0;
    }

    /* =====================================================
       PARTIDA ARANCELARIA POR LÍNEA
    ===================================================== */

    /** Todas las líneas de articulojf con su partida (si tiene). */
    public static function mdlListarPartidas()
    {
        $sql = "SELECT l.linea, p.id, p.partida, p.descripcion, IFNULL(p.activo, 0) AS activo, p.usuario_registro, p.actualizado_en
                FROM (SELECT DISTINCT linea FROM articulojf WHERE linea IS NOT NULL AND linea <> '') l
                LEFT JOIN exp_partida_lineajf p ON p.linea = l.linea
                UNION
                SELECT p.linea, p.id, p.partida, p.descripcion, p.activo, p.usuario_registro, p.actualizado_en
                FROM exp_partida_lineajf p
                WHERE p.linea NOT IN (SELECT DISTINCT linea FROM articulojf WHERE linea IS NOT NULL)
                ORDER BY linea";
        return Conexion::conectar()->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function mdlGuardarPartida($d, $usuario)
    {
        $sql = "INSERT INTO exp_partida_lineajf (linea, partida, descripcion, activo, usuario_registro)
                VALUES (:linea, :partida, :descripcion, :activo, :usuario)
                ON DUPLICATE KEY UPDATE
                    partida = VALUES(partida), descripcion = VALUES(descripcion),
                    activo = VALUES(activo), usuario_registro = VALUES(usuario_registro)";
        $stmt = Conexion::conectar()->prepare($sql);
        $stmt->bindValue(":linea", $d["linea"], PDO::PARAM_STR);
        $stmt->bindValue(":partida", $d["partida"], PDO::PARAM_STR);
        $stmt->bindValue(":descripcion", $d["descripcion"], PDO::PARAM_STR);
        $stmt->bindValue(":activo", (int) $d["activo"], PDO::PARAM_INT);
        $stmt->bindValue(":usuario", $usuario, PDO::PARAM_STR);
        return $stmt->execute();
    }

    /** linea => partida (solo activas); para el CSV de fases siguientes. */
    public static function mdlMapaPartidas()
    {
        $r = array();
        $rows = Conexion::conectar()->query("SELECT linea, partida FROM exp_partida_lineajf WHERE activo = 1 AND partida <> ''")->fetchAll(PDO::FETCH_ASSOC);
        foreach ($rows as $row) {
            $r[$row["linea"]] = $row["partida"];
        }
        return $r;
    }
}
