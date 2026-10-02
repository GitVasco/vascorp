<?php

require_once "conexion.php";

/**
 * GRE remitente manual. Tablas propias (gre_manualjf, gre_manual_detjf,
 * gre_manual_seriejf). No toca ventajf ni movimientos: no mueve stock.
 */
class ModeloGreManual
{
    public static function mdlTablasListas()
    {
        $pdo = Conexion::conectar();
        $faltan = array();
        foreach (array("gre_manualjf", "gre_manual_detjf", "gre_manual_seriejf") as $tabla) {
            $stmt = $pdo->query("SHOW TABLES LIKE " . $pdo->quote($tabla));
            if (!$stmt || !$stmt->fetchColumn()) {
                $faltan[] = $tabla;
            }
        }
        if (!count($faltan)) {
            $columnas = array(array("gre_manual_seriejf", "tipo"), array("gre_manualjf", "tipo"), array("gre_manualjf", "doc_interno"), array("gre_manualjf", "servicio"), array("gre_manual_detjf", "nota"));
            foreach ($columnas as $tc) {
                $stmt = $pdo->query("SHOW COLUMNS FROM " . $tc[0] . " LIKE " . $pdo->quote($tc[1]));
                if (!$stmt || !$stmt->fetch()) {
                    $faltan[] = $tc[0] . "." . $tc[1] . ($tc[1] === "servicio" ? " (ejecutar docs/sql/gre-manual-servicio.sql)" : ($tc[1] === "nota" ? " (ejecutar docs/sql/gre-manual-nota.sql)" : " (ejecutar docs/sql/gre-manual-tipo.sql)"));
                }
            }
            $stmt = $pdo->query("SHOW TABLES LIKE 'gre_sector_datosjf'");
            if (!$stmt || !$stmt->fetchColumn()) {
                $faltan[] = "gre_sector_datosjf (ejecutar docs/sql/gre-manual-servicio.sql)";
            }
        }
        return $faltan;
    }

    public static function mdlSeries()
    {
        $stmt = Conexion::conectar()->query("SELECT serie, correlativo, tipo FROM gre_manual_seriejf WHERE activo = 1 ORDER BY tipo, serie");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /*=============================================
    BÚSQUEDAS
    =============================================*/

    public static function mdlBuscarClientes($q)
    {
        $stmt = Conexion::conectar()->prepare("SELECT c.codigo, c.nombre, c.tipo_documento AS tipo_doc, c.documento,
                c.direccion, c.email, IF(LENGTH(c.ubigeo) = 6, c.ubigeo, '') AS ubigeo,
                u.departamento, u.provincia, u.distrito
            FROM clientesjf c
            LEFT JOIN ubigeo u ON c.ubigeo = u.codigo
            WHERE c.nombre LIKE :q OR c.documento LIKE :q OR c.codigo LIKE :q
            ORDER BY c.nombre LIMIT 30");
        $like = "%" . $q . "%";
        $stmt->bindParam(":q", $like, PDO::PARAM_STR);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function mdlBuscarProveedores($q)
    {
        $stmt = Conexion::conectar()->prepare("SELECT CodRuc AS codigo, RazPro AS nombre, '6' AS tipo_doc,
                RucPro AS documento, DirPro AS direccion, EmaPro AS email
            FROM proveedor
            WHERE EstPro = '1' AND (RazPro LIKE :q OR RucPro LIKE :q)
            ORDER BY RazPro LIMIT 30");
        $like = "%" . $q . "%";
        $stmt->bindParam(":q", $like, PDO::PARAM_STR);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function mdlBuscarUbigeos($q)
    {
        $stmt = Conexion::conectar()->prepare("SELECT codigo, departamento, provincia, distrito
            FROM ubigeo
            WHERE codigo <> '000000' AND (codigo LIKE :q OR distrito LIKE :q OR provincia LIKE :q)
            ORDER BY departamento, provincia, distrito LIMIT 30");
        $like = "%" . $q . "%";
        $stmt->bindParam(":q", $like, PDO::PARAM_STR);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function mdlBuscarModelos($q)
    {
        $stmt = Conexion::conectar()->prepare("SELECT modelo AS codigo, nombre AS descripcion,
                COALESCE(NULLIF(TRIM(cod_unidad), ''), 'C62') AS unidad
            FROM modelojf
            WHERE modelo LIKE :q OR nombre LIKE :q
            ORDER BY modelo LIMIT 30");
        $like = "%" . $q . "%";
        $stmt->bindParam(":q", $like, PDO::PARAM_STR);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function mdlBuscarArticulos($q)
    {
        $stmt = Conexion::conectar()->prepare("SELECT a.articulo AS codigo, a.nombre AS descripcion,
                COALESCE(NULLIF(TRIM(m.cod_unidad), ''), 'C62') AS unidad
            FROM articulojf a
            LEFT JOIN modelojf m ON a.modelo = m.modelo
            WHERE a.articulo LIKE :q OR a.nombre LIKE :q
            ORDER BY a.articulo LIMIT 30");
        $like = "%" . $q . "%";
        $stmt->bindParam(":q", $like, PDO::PARAM_STR);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function mdlBuscarMateriaPrima($q)
    {
        $stmt = Conexion::conectar()->prepare("SELECT p.CodPro AS codigo,
                CONCAT_WS(' - ', p.DesPro, tbcol.des_larga) AS descripcion
            FROM producto p
            LEFT JOIN tabla_m_detalle tbcol ON tbcol.cod_tabla = 'TCOL' AND tbcol.cod_argumento = p.ColPro
            WHERE p.CodPro LIKE :q OR p.DesPro LIKE :q
            ORDER BY p.CodPro LIMIT 30");
        $like = "%" . $q . "%";
        $stmt->bindParam(":q", $like, PDO::PARAM_STR);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function mdlUnidades()
    {
        $stmt = Conexion::conectar()->query("SELECT codigo, descripcion FROM unidades_medidajf ORDER BY descripcion");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /** Mismas tablas que usa la guía desde pedidos (tabla_m_detalle). */
    public static function mdlChoferes()
    {
        $stmt = Conexion::conectar()->query("SELECT cod_argumento AS codigo, valor_3 AS doc, des_larga AS nombres,
                des_corta AS apellidos, valor_4 AS licencia
            FROM tabla_m_detalle WHERE cod_tabla = 'tcho' ORDER BY des_larga");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function mdlVehiculos()
    {
        $stmt = Conexion::conectar()->query("SELECT cod_argumento AS codigo, valor_3 AS placa, des_larga AS descripcion
            FROM tabla_m_detalle WHERE cod_tabla = 'TCAR' ORDER BY valor_3");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function mdlAgencias()
    {
        $stmt = Conexion::conectar()->query("SELECT nombre, ruc, mtc FROM agenciasjf ORDER BY nombre");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /*=============================================
    CRUD
    =============================================*/

    public static function mdlListar($desde, $hasta, $estado, $tipo = "")
    {
        $sql = "SELECT g.id, g.documento, g.tipo, g.doc_interno, g.servicio, g.fecha_emision, g.fecha_traslado, g.motivo_cod, g.motivo_desc,
                    g.modalidad, g.dest_nombre, g.dest_doc, g.lle_dist, g.peso_kg, g.estado,
                    g.usuario_registro, g.fecha_envio,
                    (SELECT COUNT(*) FROM gre_manual_detjf d WHERE d.id_gre = g.id) AS items
                FROM gre_manualjf g
                WHERE g.fecha_emision BETWEEN :desde AND :hasta";
        if ($estado !== "") {
            $sql .= " AND g.estado = :estado";
        }
        if ($tipo !== "") {
            $sql .= " AND g.tipo = :tipo";
        }
        $sql .= " ORDER BY g.id DESC LIMIT 1000";
        $stmt = Conexion::conectar()->prepare($sql);
        $stmt->bindParam(":desde", $desde, PDO::PARAM_STR);
        $stmt->bindParam(":hasta", $hasta, PDO::PARAM_STR);
        if ($estado !== "") {
            $stmt->bindParam(":estado", $estado, PDO::PARAM_STR);
        }
        if ($tipo !== "") {
            $stmt->bindParam(":tipo", $tipo, PDO::PARAM_STR);
        }
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function mdlObtener($id)
    {
        $pdo = Conexion::conectar();
        $stmt = $pdo->prepare("SELECT * FROM gre_manualjf WHERE id = :id");
        $stmt->bindParam(":id", $id, PDO::PARAM_INT);
        $stmt->execute();
        $cab = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$cab) {
            return null;
        }
        $stmt = $pdo->prepare("SELECT origen, nota, codigo, descripcion, unidad_cod, unidad_desc, cantidad
            FROM gre_manual_detjf WHERE id_gre = :id ORDER BY item");
        $stmt->bindParam(":id", $id, PDO::PARAM_INT);
        $stmt->execute();
        $cab["items"] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return $cab;
    }

    /**
     * Inserta (id = 0) o actualiza (solo GENERADO). El correlativo se toma
     * con bloqueo dentro de la misma transacción.
     */
    public static function mdlGuardar($id, $serie, $c, $items, $usuario)
    {
        $pdo = Conexion::conectar();
        $pdo->beginTransaction();
        try {
            $cols = array(
                "fecha_emision", "fecha_traslado", "motivo_cod", "motivo_desc", "modalidad", "peso_kg", "bultos",
                "observaciones", "dest_origen", "dest_codigo", "dest_nombre", "dest_tipo_doc", "dest_doc",
                "dest_email", "par_ubigeo", "par_direccion", "par_dpto", "par_prov", "par_dist", "lle_ubigeo",
                "lle_direccion", "lle_dpto", "lle_prov", "lle_dist", "transp_ruc", "transp_nombre", "transp_mtc",
                "chofer_tipo_doc", "chofer_doc", "chofer_nombres", "chofer_apellidos", "chofer_licencia",
                "placa", "docs_rel", "servicio",
            );

            if ($id > 0) {
                $stmt = $pdo->prepare("SELECT estado FROM gre_manualjf WHERE id = :id FOR UPDATE");
                $stmt->bindParam(":id", $id, PDO::PARAM_INT);
                $stmt->execute();
                $estado = $stmt->fetchColumn();
                if ($estado !== "GENERADO") {
                    $pdo->rollBack();
                    return array("ok" => false, "msg" => "Solo se puede editar una guía en estado GENERADO.");
                }
                $set = array();
                foreach ($cols as $col) {
                    $set[] = "$col = :$col";
                }
                $stmt = $pdo->prepare("UPDATE gre_manualjf SET " . implode(", ", $set) . " WHERE id = :id");
                foreach ($cols as $col) {
                    $stmt->bindValue(":" . $col, $c[$col]);
                }
                $stmt->bindValue(":id", $id, PDO::PARAM_INT);
                $stmt->execute();
                $pdo->prepare("DELETE FROM gre_manual_detjf WHERE id_gre = " . (int) $id)->execute();
            } else {
                $stmt = $pdo->prepare("SELECT id, correlativo, tipo FROM gre_manual_seriejf WHERE serie = :serie AND activo = 1 FOR UPDATE");
                $stmt->bindParam(":serie", $serie, PDO::PARAM_STR);
                $stmt->execute();
                $s = $stmt->fetch(PDO::FETCH_ASSOC);
                if (!$s) {
                    $pdo->rollBack();
                    return array("ok" => false, "msg" => "La serie no existe o está inactiva.");
                }
                if (!empty($c["servicio"])) {
                    $stmt = $pdo->prepare("SELECT documento FROM gre_manualjf WHERE servicio = :sv AND estado <> 'ANULADO' LIMIT 1 FOR UPDATE");
                    $stmt->bindValue(":sv", $c["servicio"]);
                    $stmt->execute();
                    $ya = $stmt->fetchColumn();
                    if ($ya) {
                        $pdo->rollBack();
                        return array("ok" => false, "msg" => "El servicio " . $c["servicio"] . " ya tiene la guía " . $ya . ".");
                    }
                }
                $numero = (int) $s["correlativo"] + 1;
                $documento = $serie . "-" . str_pad($numero, 8, "0", STR_PAD_LEFT);

                $stmt = $pdo->prepare("INSERT INTO gre_manualjf (serie, numero, documento, tipo, usuario_registro, " . implode(", ", $cols) . ")
                    VALUES (:serie, :numero, :documento, :tipo, :usuario, :" . implode(", :", $cols) . ")");
                foreach ($cols as $col) {
                    $stmt->bindValue(":" . $col, $c[$col]);
                }
                $stmt->bindValue(":serie", $serie);
                $stmt->bindValue(":numero", $numero, PDO::PARAM_INT);
                $stmt->bindValue(":documento", $documento);
                $stmt->bindValue(":tipo", $s["tipo"]);
                $stmt->bindValue(":usuario", $usuario);
                $stmt->execute();
                $id = (int) $pdo->lastInsertId();

                $pdo->prepare("UPDATE gre_manual_seriejf SET correlativo = :n WHERE id = :id")
                    ->execute(array(":n" => $numero, ":id" => $s["id"]));
            }

            // Una nota de salida no puede estar en dos guías vigentes
            $notasGuia = array();
            foreach ($items as $it) {
                if (!empty($it["nota"])) {
                    $notasGuia[$it["nota"]] = true;
                }
            }
            foreach (array_keys($notasGuia) as $nk) {
                $stmt = $pdo->prepare("SELECT g.documento FROM gre_manual_detjf d JOIN gre_manualjf g ON g.id = d.id_gre
                    WHERE d.nota = :n AND g.estado <> 'ANULADO' AND g.id <> :id LIMIT 1");
                $stmt->execute(array(":n" => $nk, ":id" => $id));
                $otra = $stmt->fetchColumn();
                if ($otra) {
                    $pdo->rollBack();
                    return array("ok" => false, "msg" => "La nota de salida " . $nk . " ya está en la guía " . $otra . ".");
                }
            }

            $stmt = $pdo->prepare("INSERT INTO gre_manual_detjf (id_gre, item, origen, nota, codigo, descripcion, unidad_cod, unidad_desc, cantidad)
                VALUES (:id, :item, :origen, :nota, :codigo, :descripcion, :unidad_cod, :unidad_desc, :cantidad)");
            $n = 0;
            foreach ($items as $it) {
                $n++;
                $stmt->execute(array(
                    ":id" => $id,
                    ":item" => $n,
                    ":origen" => $it["origen"],
                    ":nota" => !empty($it["nota"]) ? $it["nota"] : null,
                    ":codigo" => $it["codigo"],
                    ":descripcion" => $it["descripcion"],
                    ":unidad_cod" => $it["unidad_cod"],
                    ":unidad_desc" => $it["unidad_desc"],
                    ":cantidad" => $it["cantidad"],
                ));
            }

            $pdo->commit();
            $stmt = $pdo->prepare("SELECT documento FROM gre_manualjf WHERE id = :id");
            $stmt->bindParam(":id", $id, PDO::PARAM_INT);
            $stmt->execute();
            return array("ok" => true, "id" => $id, "documento" => $stmt->fetchColumn());
        } catch (Exception $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            error_log("[GRE_MANUAL] " . $e->getMessage());
            return array("ok" => false, "msg" => "No se pudo guardar la guía.");
        }
    }

    public static function mdlMarcarEnviado($id, $archivo)
    {
        $stmt = Conexion::conectar()->prepare("UPDATE gre_manualjf SET estado = 'ENVIADO', archivo_csv = :a, fecha_envio = NOW()
            WHERE id = :id AND estado = 'GENERADO' AND tipo = 'ELECTRONICA'");
        $stmt->bindParam(":a", $archivo, PDO::PARAM_STR);
        $stmt->bindParam(":id", $id, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->rowCount() === 1;
    }

    public static function mdlAnular($id)
    {
        $stmt = Conexion::conectar()->prepare("UPDATE gre_manualjf SET estado = 'ANULADO' WHERE id = :id AND estado = 'GENERADO'");
        $stmt->bindParam(":id", $id, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->rowCount() === 1;
    }


    /**
     * Convierte una guía INTERNA (GENERADO) en ELECTRONICA: toma el siguiente
     * número de la serie electrónica y guarda el número interno en doc_interno.
     */
    public static function mdlConvertirAElectronica($id, $serieDestino)
    {
        $pdo = Conexion::conectar();
        $pdo->beginTransaction();
        try {
            $stmt = $pdo->prepare("SELECT documento, tipo, estado FROM gre_manualjf WHERE id = :id FOR UPDATE");
            $stmt->bindParam(":id", $id, PDO::PARAM_INT);
            $stmt->execute();
            $g = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$g || $g["tipo"] !== "INTERNA" || $g["estado"] !== "GENERADO") {
                $pdo->rollBack();
                return array("ok" => false, "msg" => "Solo se convierte una guía INTERNA en estado GENERADO.");
            }
            $stmt = $pdo->prepare("SELECT id, correlativo FROM gre_manual_seriejf WHERE serie = :s AND activo = 1 AND tipo = 'ELECTRONICA' FOR UPDATE");
            $stmt->bindParam(":s", $serieDestino, PDO::PARAM_STR);
            $stmt->execute();
            $s = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$s) {
                $pdo->rollBack();
                return array("ok" => false, "msg" => "La serie electrónica no existe o está inactiva.");
            }
            $numero = (int) $s["correlativo"] + 1;
            $documento = $serieDestino . "-" . str_pad($numero, 8, "0", STR_PAD_LEFT);
            $pdo->prepare("UPDATE gre_manualjf SET serie = :serie, numero = :numero, documento = :doc, tipo = 'ELECTRONICA', doc_interno = :int WHERE id = :id")
                ->execute(array(":serie" => $serieDestino, ":numero" => $numero, ":doc" => $documento, ":int" => $g["documento"], ":id" => $id));
            $pdo->prepare("UPDATE gre_manual_seriejf SET correlativo = :n WHERE id = :id")
                ->execute(array(":n" => $numero, ":id" => $s["id"]));
            $pdo->commit();
            return array("ok" => true, "documento" => $documento, "doc_interno" => $g["documento"]);
        } catch (Exception $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            error_log("[GRE_MANUAL] " . $e->getMessage());
            return array("ok" => false, "msg" => "No se pudo convertir la guía.");
        }
    }

    /** Elimina una guía no enviada (GENERADO o ANULADO). No toca el correlativo. */
    public static function mdlEliminar($id)
    {
        $pdo = Conexion::conectar();
        $pdo->beginTransaction();
        try {
            $stmt = $pdo->prepare("SELECT estado FROM gre_manualjf WHERE id = :id FOR UPDATE");
            $stmt->bindParam(":id", $id, PDO::PARAM_INT);
            $stmt->execute();
            $estado = $stmt->fetchColumn();
            if ($estado !== "GENERADO" && $estado !== "ANULADO") {
                $pdo->rollBack();
                return false;
            }
            $pdo->prepare("DELETE FROM gre_manual_detjf WHERE id_gre = " . (int) $id)->execute();
            $pdo->prepare("DELETE FROM gre_manualjf WHERE id = " . (int) $id)->execute();
            $pdo->commit();
            return true;
        } catch (Exception $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            error_log("[GRE_MANUAL] " . $e->getMessage());
            return false;
        }
    }

    /** Series con su último número usado y el mayor número realmente emitido. */
    public static function mdlSeriesCorrelativo()
    {
        $stmt = Conexion::conectar()->query("SELECT s.serie, s.correlativo, s.tipo,
                IFNULL((SELECT MAX(g.numero) FROM gre_manualjf g WHERE g.serie = s.serie), 0) AS max_emitido
            FROM gre_manual_seriejf s WHERE s.activo = 1 ORDER BY s.tipo, s.serie");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Fija el último número usado de la serie (el próximo será n + 1).
     * No puede ser menor al mayor número ya emitido: chocaría con una guía existente.
     */
    public static function mdlFijarCorrelativo($serie, $n)
    {
        $pdo = Conexion::conectar();
        $pdo->beginTransaction();
        try {
            $stmt = $pdo->prepare("SELECT id FROM gre_manual_seriejf WHERE serie = :s AND activo = 1 FOR UPDATE");
            $stmt->bindParam(":s", $serie, PDO::PARAM_STR);
            $stmt->execute();
            $idSerie = $stmt->fetchColumn();
            if (!$idSerie) {
                $pdo->rollBack();
                return array("ok" => false, "msg" => "La serie no existe.");
            }
            $stmt = $pdo->prepare("SELECT IFNULL(MAX(numero), 0) FROM gre_manualjf WHERE serie = :s");
            $stmt->bindParam(":s", $serie, PDO::PARAM_STR);
            $stmt->execute();
            $max = (int) $stmt->fetchColumn();
            if ($n < $max) {
                $pdo->rollBack();
                return array("ok" => false, "msg" => "No puede ser menor a $max (ya existe esa guía en la serie $serie).");
            }
            $pdo->prepare("UPDATE gre_manual_seriejf SET correlativo = :n WHERE id = :id")
                ->execute(array(":n" => $n, ":id" => $idSerie));
            $pdo->commit();
            return array("ok" => true, "proximo" => $serie . "-" . str_pad($n + 1, 8, "0", STR_PAD_LEFT));
        } catch (Exception $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            error_log("[GRE_MANUAL] " . $e->getMessage());
            return array("ok" => false, "msg" => "No se pudo actualizar el correlativo.");
        }
    }

    /*=============================================
    SERVICIOS / TALLERES
    =============================================*/

    public static function mdlBuscarTalleres($q)
    {
        $stmt = Conexion::conectar()->prepare("SELECT s.cod_sector AS codigo, s.nom_sector AS nombre,
                d.razon_social, d.tipo_doc, d.doc AS documento, d.email, d.direccion, d.ubigeo, d.dpto AS departamento,
                d.prov AS provincia, d.dist AS distrito
            FROM sectorjf s
            LEFT JOIN gre_sector_datosjf d ON d.cod_sector = s.cod_sector
            WHERE s.tipo IS NOT NULL AND s.tipo <> 0 AND (s.nom_sector LIKE :q OR s.cod_sector LIKE :q)
            ORDER BY s.nom_sector LIMIT 30");
        $like = "%" . $q . "%";
        $stmt->bindParam(":q", $like, PDO::PARAM_STR);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /** Datos fiscales guardados de un taller (o null). Tolera que la tabla aún no exista. */
    public static function mdlDatosTaller($cod)
    {
        try {
            $stmt = Conexion::conectar()->prepare("SELECT razon_social, tipo_doc, doc, email, direccion, ubigeo, dpto, prov, dist
                FROM gre_sector_datosjf WHERE cod_sector = :c");
            if (!$stmt) {
                return null;
            }
            $stmt->bindParam(":c", $cod, PDO::PARAM_STR);
            if (!$stmt->execute()) {
                return null;
            }
            $f = $stmt->fetch(PDO::FETCH_ASSOC);
            return $f ? $f : null;
        } catch (Exception $e) {
            return null;
        }
    }

    /** Todos los datos fiscales indexados por cod_sector (para el listado de Sectores). */
    public static function mdlTodosDatosTaller()
    {
        try {
            $stmt = Conexion::conectar()->query("SELECT cod_sector, razon_social, tipo_doc, doc, direccion, dist FROM gre_sector_datosjf");
            if (!$stmt) {
                return array();
            }
            $mapa = array();
            foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $f) {
                $mapa[$f["cod_sector"]] = $f;
            }
            return $mapa;
        } catch (Exception $e) {
            return array();
        }
    }

    public static function mdlUbigeoPorCodigo($cod)
    {
        $stmt = Conexion::conectar()->prepare("SELECT codigo, departamento, provincia, distrito FROM ubigeo WHERE codigo = :c LIMIT 1");
        $stmt->bindParam(":c", $cod, PDO::PARAM_STR);
        $stmt->execute();
        $f = $stmt->fetch(PDO::FETCH_ASSOC);
        return $f ? $f : null;
    }

    public static function mdlGuardarDatosTaller($cod, $d, $usuario)
    {
        $stmt = Conexion::conectar()->prepare("INSERT INTO gre_sector_datosjf
                (cod_sector, razon_social, tipo_doc, doc, email, direccion, ubigeo, dpto, prov, dist, usuario)
            VALUES (:cod, :rs, :td, :doc, :email, :dir, :ubi, :dpto, :prov, :dist, :usu)
            ON DUPLICATE KEY UPDATE razon_social = VALUES(razon_social), tipo_doc = VALUES(tipo_doc), doc = VALUES(doc),
                email = VALUES(email), direccion = VALUES(direccion), ubigeo = VALUES(ubigeo), dpto = VALUES(dpto),
                prov = VALUES(prov), dist = VALUES(dist), usuario = VALUES(usuario)");
        return $stmt->execute(array(
            ":cod" => $cod, ":rs" => $d["razon_social"], ":td" => $d["tipo_doc"], ":doc" => $d["doc"], ":email" => $d["email"],
            ":dir" => $d["direccion"], ":ubi" => $d["ubigeo"], ":dpto" => $d["dpto"], ":prov" => $d["prov"], ":dist" => $d["dist"],
            ":usu" => $usuario,
        ));
    }

    /** Servicio + taller + ítems agrupados por modelo + guía existente (si la hay). */
    public static function mdlServicioParaGuia($codigo)
    {
        $pdo = Conexion::conectar();
        $stmt = $pdo->prepare("SELECT se.codigo, se.taller, se.fecha, s.nom_sector, s.tipo
            FROM serviciosjf se LEFT JOIN sectorjf s ON se.taller = s.cod_sector WHERE se.codigo = :c");
        $stmt->bindParam(":c", $codigo, PDO::PARAM_STR);
        $stmt->execute();
        $sv = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$sv) {
            return null;
        }
        $stmt = $pdo->prepare("SELECT a.modelo AS codigo, IFNULL(NULLIF(TRIM(m.nombre), ''), MIN(a.nombre)) AS descripcion,
                COALESCE(NULLIF(TRIM(m.cod_unidad), ''), 'C62') AS unidad, SUM(sd.cantidad) AS cantidad
            FROM servicios_detallejf sd
            LEFT JOIN articulojf a ON sd.articulo = a.articulo
            LEFT JOIN modelojf m ON a.modelo = m.modelo
            WHERE sd.codigo = :c
            GROUP BY a.modelo, m.nombre, m.cod_unidad
            HAVING SUM(sd.cantidad) > 0
            ORDER BY a.modelo");
        $stmt->bindParam(":c", $codigo, PDO::PARAM_STR);
        $stmt->execute();
        $sv["items"] = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // ¿El servicio viene de un corte? (sus detalles se registran con cabecera_taller)
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM servicios_detallejf WHERE codigo = :c AND cabecera_taller IS NOT NULL AND cabecera_taller <> 0");
        $stmt->bindParam(":c", $codigo, PDO::PARAM_STR);
        $stmt->execute();
        $sv["desde_corte"] = (int) $stmt->fetchColumn() > 0;
        $sv["observacion"] = $sv["desde_corte"]
            ? "SERVICIO DE PRODUCCION N° " . $sv["codigo"] . " (CORTE). PRENDAS CORTADAS ENVIADAS AL TALLER PARA SU CONFECCION; RETORNAN TERMINADAS A CORPORACION VASCO S.A.C."
            : "SERVICIO DE PRODUCCION N° " . $sv["codigo"] . ".";

        $stmt = $pdo->prepare("SELECT cod_sector AS codigo, razon_social, tipo_doc, doc AS documento, email, direccion, ubigeo,
                dpto AS departamento, prov AS provincia, dist AS distrito
            FROM gre_sector_datosjf WHERE cod_sector = :c");
        $stmt->bindParam(":c", $sv["taller"], PDO::PARAM_STR);
        $stmt->execute();
        $sv["datos_taller"] = $stmt->fetch(PDO::FETCH_ASSOC);

        $stmt = $pdo->prepare("SELECT id, documento, estado FROM gre_manualjf WHERE servicio = :c AND estado <> 'ANULADO' ORDER BY id DESC LIMIT 1");
        $stmt->bindParam(":c", $codigo, PDO::PARAM_STR);
        $stmt->execute();
        $sv["guia"] = $stmt->fetch(PDO::FETCH_ASSOC);
        return $sv;
    }

    /**
     * Matriz del servicio por modelo/color (tallas 1..8) con el precio a pagar
     * (precio_serviciojf.precio_doc es por docena: importe = total / 12 * precio).
     */
    public static function mdlServicioMatriz($codigo)
    {
        $sumas = array();
        for ($t = 1; $t <= 8; $t++) {
            $sumas[] = "SUM(CASE WHEN a.cod_talla = '$t' THEN sd.cantidad ELSE 0 END) AS t$t";
        }
        $stmt = Conexion::conectar()->prepare("SELECT a.modelo, IFNULL(NULLIF(TRIM(m.nombre), ''), MIN(a.nombre)) AS nombre, a.color, "
            . implode(", ", $sumas) . ", SUM(sd.cantidad) AS total, ps.precio_doc
            FROM servicios_detallejf sd
            LEFT JOIN articulojf a ON sd.articulo = a.articulo
            LEFT JOIN modelojf m ON a.modelo = m.modelo
            LEFT JOIN serviciosjf se ON se.codigo = sd.codigo
            LEFT JOIN precio_serviciojf ps ON ps.taller = se.taller AND ps.modelo = a.modelo
            WHERE sd.codigo = :c
            GROUP BY a.modelo, m.nombre, a.cod_color, a.color, ps.precio_doc
            HAVING SUM(sd.cantidad) > 0
            ORDER BY a.modelo, a.color");
        $stmt->bindParam(":c", $codigo, PDO::PARAM_STR);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /*=============================================
    NOTAS DE SALIDA DE MP
    =============================================*/

    /** Notas de salida (NS) aún sin guía vigente, por número o razón social. */
    public static function mdlBuscarNotas($q)
    {
        $stmt = Conexion::conectar()->prepare("SELECT CONCAT(vc.Tip, '-', vc.Ser, '-', vc.Nro) AS codigo,
                CONCAT(vc.Nro, ' · ', IFNULL(c.RazCli, ''), ' · ', DATE(vc.FecEmi)) AS descripcion, 'C62' AS unidad
            FROM ventas_cab vc
            LEFT JOIN Clientes c ON c.Ruc = vc.Ruc
            WHERE vc.Tip = 'NS' AND vc.EstVta NOT LIKE 'A'
                AND YEAR(vc.FecEmi) >= YEAR(NOW()) - 1
                AND (vc.Nro LIKE :q OR c.RazCli LIKE :q)
                AND NOT EXISTS (SELECT 1 FROM gre_manual_detjf d JOIN gre_manualjf g ON g.id = d.id_gre
                    WHERE d.nota = CONCAT(vc.Tip, '-', vc.Ser, '-', vc.Nro) AND g.estado <> 'ANULADO')
            ORDER BY vc.FecEmi DESC LIMIT 30");
        $like = "%" . $q . "%";
        $stmt->bindParam(":q", $like, PDO::PARAM_STR);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /** Mapa nota => guía vigente, para marcar el listado de notas de salida. */
    public static function mdlNotasGuiadas()
    {
        try {
            $stmt = Conexion::conectar()->query("SELECT d.nota, MIN(g.id) AS id, MIN(g.documento) AS documento
                FROM gre_manual_detjf d JOIN gre_manualjf g ON g.id = d.id_gre
                WHERE d.nota IS NOT NULL AND g.estado <> 'ANULADO' GROUP BY d.nota");
            if (!$stmt) {
                return array();
            }
            $mapa = array();
            foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $f) {
                $mapa[$f["nota"]] = $f;
            }
            return $mapa;
        } catch (Exception $e) {
            return array();
        }
    }

    /**
     * Cabecera + ítems de una nota de salida, destinatario sugerido (por RUC: taller
     * registrado o cliente legado) y guías abiertas del mismo destinatario.
     */
    public static function mdlNotaParaGuia($tip, $ser, $nro)
    {
        $pdo = Conexion::conectar();
        $stmt = $pdo->prepare("SELECT vc.Tip, vc.Ser, vc.Nro, vc.Ruc, DATE(vc.FecEmi) AS fecha, vc.EstNota, vc.observacion,
                c.RazCli, c.DirCli
            FROM ventas_cab vc LEFT JOIN Clientes c ON c.Ruc = vc.Ruc
            WHERE vc.Tip = :t AND vc.Ser = :s AND vc.Nro = :n AND vc.EstVta NOT LIKE 'A' LIMIT 1");
        $stmt->execute(array(":t" => $tip, ":s" => $ser, ":n" => $nro));
        $n = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$n) {
            return null;
        }
        $n["clave"] = $tip . "-" . $ser . "-" . $nro;

        $stmt = $pdo->prepare("SELECT det.CodPro AS codigo,
                CONCAT_WS(' - ', p.DesPro, tbcol.Des_Larga) AS descripcion, det.CanVta AS cantidad,
                und.Des_Larga AS und_larga, und.Des_Corta AS und_corta
            FROM venta_det det
            LEFT JOIN producto p ON p.CodPro = det.CodPro
            LEFT JOIN tabla_m_detalle tbcol ON tbcol.Cod_Tabla = 'TCOL' AND tbcol.Cod_Argumento = p.ColPro
            LEFT JOIN tabla_m_detalle und ON und.Cod_Tabla = 'TUND' AND und.Cod_Argumento = p.UndPro
            WHERE det.Tip = :t AND det.Ser = :s AND det.Nro = :n
            ORDER BY det.Item");
        $stmt->execute(array(":t" => $tip, ":s" => $ser, ":n" => $nro));
        $n["items"] = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Destinatario sugerido: taller con datos fiscales registrados (mismo RUC) o el cliente de la nota
        $n["destino"] = null;
        $stmt = $pdo->prepare("SELECT d.cod_sector AS codigo, d.razon_social AS nombre, d.tipo_doc, d.doc AS documento, d.email,
                d.direccion, d.ubigeo, d.dpto AS departamento, d.prov AS provincia, d.dist AS distrito
            FROM gre_sector_datosjf d WHERE d.doc = :r LIMIT 1");
        $stmt->execute(array(":r" => $n["Ruc"]));
        $dst = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($dst) {
            $dst["origen"] = "TALLER";
            $n["destino"] = $dst;
        } elseif ($n["RazCli"]) {
            $n["destino"] = array("origen" => "MANUAL", "codigo" => "", "nombre" => $n["RazCli"],
                "tipo_doc" => strlen($n["Ruc"]) === 8 ? "1" : "6", "documento" => $n["Ruc"], "direccion" => $n["DirCli"]);
        }

        // Guía vigente de esta nota y guías abiertas (sin enviar) del mismo destinatario
        $stmt = $pdo->prepare("SELECT g.id, g.documento FROM gre_manual_detjf d JOIN gre_manualjf g ON g.id = d.id_gre
            WHERE d.nota = :k AND g.estado <> 'ANULADO' LIMIT 1");
        $stmt->execute(array(":k" => $n["clave"]));
        $n["guia"] = $stmt->fetch(PDO::FETCH_ASSOC);

        $stmt = $pdo->prepare("SELECT id, documento, servicio FROM gre_manualjf WHERE estado = 'GENERADO' AND dest_doc = :r ORDER BY id DESC LIMIT 5");
        $stmt->execute(array(":r" => $n["Ruc"]));
        $n["guias_abiertas"] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return $n;
    }
}
