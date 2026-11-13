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
