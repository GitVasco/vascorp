<?php

require_once "conexion.php";

class ModeloCompras{

	static public function mdlCantidadDocumentos(){

        $stmt = Conexion::conectar()->prepare("SELECT 
                    COUNT(*) AS cantidad 
                FROM
                    reg_compras r 
                WHERE r.tipo_documento IN ('01', '03', '07', '08') 
                    AND r.estado = '0'");

        $stmt -> execute();

        return $stmt -> fetch();

		$stmt -> close();

		$stmt = null;

	}

	static public function mdlComprasSinVerificar($i){

        if($i == "1"){

            $stmt = Conexion::conectar()->prepare("SELECT 
                    CONCAT(
                    r.ruc,
                    '|',
                    r.tipo_documento,
                    '|',
                    r.serie_doc,
                    '|',
                    r.num_doc,
                    '|',
                    DATE_FORMAT(r.fecha_emision, '%d/%m/%Y'),
                    '|',
                    CASE
                        WHEN LEFT(r.serie_doc, 1) = '0' 
                        THEN '' 
                        ELSE (
                        CASE
                            WHEN r.tipo_documento = '07' 
                            THEN (
                            CASE
                                WHEN r.moneda = 'D' 
                                THEN ROUND(r.total / r.tipo_cambio * - 1, 2) 
                                ELSE ROUND(r.total * - 1, 2) 
                            END
                            ) 
                            ELSE (
                            CASE
                                WHEN r.moneda = 'D' 
                                THEN ROUND(r.total / r.tipo_cambio, 2) 
                                ELSE ROUND(r.total, 2) 
                            END
                            ) 
                        END
                        ) 
                    END
                    ) AS sunat 
                FROM
                    reg_compras r 
                WHERE r.tipo_documento IN ('01', '03', '07', '08') 
                    AND r.estado = '0' 
                ORDER BY MONTH(r.fecha_emision),
                    r.origen,
                    r.voucher
                LIMIT 100 OFFSET 0");

            $stmt -> execute();

            return $stmt -> fetchAll();            

        }else if($i == "2"){

            $stmt = Conexion::conectar()->prepare("SELECT 
                    CONCAT(
                    r.ruc,
                    '|',
                    r.tipo_documento,
                    '|',
                    r.serie_doc,
                    '|',
                    r.num_doc,
                    '|',
                    DATE_FORMAT(r.fecha_emision, '%d/%m/%Y'),
                    '|',
                    CASE
                        WHEN LEFT(r.serie_doc, 1) = '0' 
                        THEN '' 
                        ELSE (
                        CASE
                            WHEN r.tipo_documento = '07' 
                            THEN (
                            CASE
                                WHEN r.moneda = 'D' 
                                THEN ROUND(r.total / r.tipo_cambio * - 1, 2) 
                                ELSE ROUND(r.total * - 1, 2) 
                            END
                            ) 
                            ELSE (
                            CASE
                                WHEN r.moneda = 'D' 
                                THEN ROUND(r.total / r.tipo_cambio, 2) 
                                ELSE ROUND(r.total, 2) 
                            END
                            ) 
                        END
                        ) 
                    END
                    ) AS sunat 
                FROM
                    reg_compras r 
                WHERE r.tipo_documento IN ('01', '03', '07', '08') 
                    AND r.estado = '0' 
                ORDER BY MONTH(r.fecha_emision),
                    r.origen,
                    r.voucher
                LIMIT 100 OFFSET 100");

            $stmt -> execute();

            return $stmt -> fetchAll(); 

        }else if($i == "3"){

            $stmt = Conexion::conectar()->prepare("SELECT 
                    CONCAT(
                    r.ruc,
                    '|',
                    r.tipo_documento,
                    '|',
                    r.serie_doc,
                    '|',
                    r.num_doc,
                    '|',
                    DATE_FORMAT(r.fecha_emision, '%d/%m/%Y'),
                    '|',
                    CASE
                        WHEN LEFT(r.serie_doc, 1) = '0' 
                        THEN '' 
                        ELSE (
                        CASE
                            WHEN r.tipo_documento = '07' 
                            THEN (
                            CASE
                                WHEN r.moneda = 'D' 
                                THEN ROUND(r.total / r.tipo_cambio * - 1, 2) 
                                ELSE ROUND(r.total * - 1, 2) 
                            END
                            ) 
                            ELSE (
                            CASE
                                WHEN r.moneda = 'D' 
                                THEN ROUND(r.total / r.tipo_cambio, 2) 
                                ELSE ROUND(r.total, 2) 
                            END
                            ) 
                        END
                        ) 
                    END
                    ) AS sunat 
                FROM
                    reg_compras r 
                WHERE r.tipo_documento IN ('01', '03', '07', '08') 
                    AND r.estado = '0' 
                ORDER BY MONTH(r.fecha_emision),
                    r.origen,
                    r.voucher
                LIMIT 100 OFFSET 200");

            $stmt -> execute();

            return $stmt -> fetchAll();             

        }else if($i == "4"){

            $stmt = Conexion::conectar()->prepare("SELECT 
                    CONCAT(
                    r.ruc,
                    '|',
                    r.tipo_documento,
                    '|',
                    r.serie_doc,
                    '|',
                    r.num_doc,
                    '|',
                    DATE_FORMAT(r.fecha_emision, '%d/%m/%Y'),
                    '|',
                    CASE
                        WHEN LEFT(r.serie_doc, 1) = '0' 
                        THEN '' 
                        ELSE (
                        CASE
                            WHEN r.tipo_documento = '07' 
                            THEN (
                            CASE
                                WHEN r.moneda = 'D' 
                                THEN ROUND(r.total / r.tipo_cambio * - 1, 2) 
                                ELSE ROUND(r.total * - 1, 2) 
                            END
                            ) 
                            ELSE (
                            CASE
                                WHEN r.moneda = 'D' 
                                THEN ROUND(r.total / r.tipo_cambio, 2) 
                                ELSE ROUND(r.total, 2) 
                            END
                            ) 
                        END
                        ) 
                    END
                    ) AS sunat 
                FROM
                    reg_compras r 
                WHERE r.tipo_documento IN ('01', '03', '07', '08') 
                    AND r.estado = '0' 
                ORDER BY MONTH(r.fecha_emision),
                    r.origen,
                    r.voucher
                LIMIT 100 OFFSET 300");

            $stmt -> execute();

            return $stmt -> fetchAll();             

        }else if($i == "5"){

            $stmt = Conexion::conectar()->prepare("SELECT 
                    CONCAT(
                    r.ruc,
                    '|',
                    r.tipo_documento,
                    '|',
                    r.serie_doc,
                    '|',
                    r.num_doc,
                    '|',
                    DATE_FORMAT(r.fecha_emision, '%d/%m/%Y'),
                    '|',
                    CASE
                        WHEN LEFT(r.serie_doc, 1) = '0' 
                        THEN '' 
                        ELSE (
                        CASE
                            WHEN r.tipo_documento = '07' 
                            THEN (
                            CASE
                                WHEN r.moneda = 'D' 
                                THEN ROUND(r.total / r.tipo_cambio * - 1, 2) 
                                ELSE ROUND(r.total * - 1, 2) 
                            END
                            ) 
                            ELSE (
                            CASE
                                WHEN r.moneda = 'D' 
                                THEN ROUND(r.total / r.tipo_cambio, 2) 
                                ELSE ROUND(r.total, 2) 
                            END
                            ) 
                        END
                        ) 
                    END
                    ) AS sunat 
                FROM
                    reg_compras r 
                WHERE r.tipo_documento IN ('01', '03', '07', '08') 
                    AND r.estado = '0' 
                ORDER BY MONTH(r.fecha_emision),
                    r.origen,
                    r.voucher
                LIMIT 100 OFFSET 400");

            $stmt -> execute();

            return $stmt -> fetchAll();             

        }else if($i == "6"){

            $stmt = Conexion::conectar()->prepare("SELECT 
                    CONCAT(
                    r.ruc,
                    '|',
                    r.tipo_documento,
                    '|',
                    r.serie_doc,
                    '|',
                    r.num_doc,
                    '|',
                    DATE_FORMAT(r.fecha_emision, '%d/%m/%Y'),
                    '|',
                    CASE
                        WHEN LEFT(r.serie_doc, 1) = '0' 
                        THEN '' 
                        ELSE (
                        CASE
                            WHEN r.tipo_documento = '07' 
                            THEN (
                            CASE
                                WHEN r.moneda = 'D' 
                                THEN ROUND(r.total / r.tipo_cambio * - 1, 2) 
                                ELSE ROUND(r.total * - 1, 2) 
                            END
                            ) 
                            ELSE (
                            CASE
                                WHEN r.moneda = 'D' 
                                THEN ROUND(r.total / r.tipo_cambio, 2) 
                                ELSE ROUND(r.total, 2) 
                            END
                            ) 
                        END
                        ) 
                    END
                    ) AS sunat 
                FROM
                    reg_compras r 
                WHERE r.tipo_documento IN ('01', '03', '07', '08') 
                    AND r.estado = '0' 
                ORDER BY MONTH(r.fecha_emision),
                    r.origen,
                    r.voucher
                LIMIT 100 OFFSET 500");

            $stmt -> execute();

            return $stmt -> fetchAll();             

        }else if($i == "7"){

            $stmt = Conexion::conectar()->prepare("SELECT 
                    CONCAT(
                    r.ruc,
                    '|',
                    r.tipo_documento,
                    '|',
                    r.serie_doc,
                    '|',
                    r.num_doc,
                    '|',
                    DATE_FORMAT(r.fecha_emision, '%d/%m/%Y'),
                    '|',
                    CASE
                        WHEN LEFT(r.serie_doc, 1) = '0' 
                        THEN '' 
                        ELSE (
                        CASE
                            WHEN r.tipo_documento = '07' 
                            THEN (
                            CASE
                                WHEN r.moneda = 'D' 
                                THEN ROUND(r.total / r.tipo_cambio * - 1, 2) 
                                ELSE ROUND(r.total * - 1, 2) 
                            END
                            ) 
                            ELSE (
                            CASE
                                WHEN r.moneda = 'D' 
                                THEN ROUND(r.total / r.tipo_cambio, 2) 
                                ELSE ROUND(r.total, 2) 
                            END
                            ) 
                        END
                        ) 
                    END
                    ) AS sunat 
                FROM
                    reg_compras r 
                WHERE r.tipo_documento IN ('01', '03', '07', '08') 
                    AND r.estado = '0' 
                ORDER BY MONTH(r.fecha_emision),
                    r.origen,
                    r.voucher
                LIMIT 100 OFFSET 600");

            $stmt -> execute();

            return $stmt -> fetchAll();             

        }

		$stmt -> close();

		$stmt = null;

	}    

	static public function mdlActualizarRegCompras($datos){

		$stmt = Conexion::conectar()->prepare("UPDATE 
                                                    reg_compras 
                                                SET
                                                    comprobante = :comprobante,
                                                    contribuyente = :contribuyente,
                                                    condicion = :condicion,
                                                    estado = :estado 
                                                WHERE ruc = :ruc 
                                                    AND serie_doc = :serie_doc 
                                                    AND num_doc = :num_doc");

		$stmt->bindParam(":ruc", $datos["ruc"], PDO::PARAM_STR);
		$stmt->bindParam(":serie_doc", $datos["serie_doc"], PDO::PARAM_STR);
		$stmt->bindParam(":num_doc", $datos["num_doc"], PDO::PARAM_STR);
        $stmt->bindParam(":comprobante", $datos["comprobante"], PDO::PARAM_STR);
		$stmt->bindParam(":contribuyente", $datos["contribuyente"], PDO::PARAM_STR);
		$stmt->bindParam(":condicion", $datos["condicion"], PDO::PARAM_STR);
        $stmt->bindParam(":estado", $datos["estado"], PDO::PARAM_STR);


		if ($stmt->execute()) {

			return "ok";

		} else {

			return $stmt->errorInfo();
		}

		$stmt->close();
		$stmt = null;

	}    

	static public function mdlActualizarDiario($datos){

		$stmt = Conexion::conectar()->prepare("UPDATE 
                                    diario 
                                SET
                                    comprobante = :comprobante,
                                    contribuyente = :contribuyente,
                                    condicion = :condicion 
                                WHERE ruc = :ruc 
                                    AND serie_doc = :serie_doc 
                                    AND num_doc = :num_doc");

		$stmt->bindParam(":ruc", $datos["ruc"], PDO::PARAM_STR);
		$stmt->bindParam(":serie_doc", $datos["serie_doc"], PDO::PARAM_STR);
		$stmt->bindParam(":num_doc", $datos["num_doc"], PDO::PARAM_STR);
        $stmt->bindParam(":comprobante", $datos["comprobante"], PDO::PARAM_STR);
		$stmt->bindParam(":contribuyente", $datos["contribuyente"], PDO::PARAM_STR);
		$stmt->bindParam(":condicion", $datos["condicion"], PDO::PARAM_STR);


		if ($stmt->execute()) {

			return "ok";

		} else {

			return $stmt->errorInfo();
		}

		$stmt->close();
		$stmt = null;

	}  
    
	static public function mdlTraerCompra($ruc, $serie, $numero){

        $stmt = Conexion::conectar()->prepare("SELECT 
                                                    r.id,
                                                    r.origen,
                                                    r.voucher,
                                                    r.fecha_emision,
                                                    r.fecha_vencimiento,
                                                    r.tipo_documento,
                                                    CASE
                                                        WHEN r.tipo_documento = '01' 
                                                        THEN CONCAT(r.tipo_documento, ' - FACTURA') 
                                                        WHEN r.tipo_documento = '03' 
                                                        THEN CONCAT(r.tipo_documento, ' - BOLETA') 
                                                        WHEN r.tipo_documento = '07' 
                                                        THEN CONCAT(
                                                        r.tipo_documento,
                                                        ' - NOTA CREDITO'
                                                        ) 
                                                        WHEN r.tipo_documento = '08' 
                                                        THEN CONCAT(
                                                        r.tipo_documento,
                                                        ' - NOTA DEBITO'
                                                        ) 
                                                        ELSE 'REVISAR' 
                                                    END AS tipo,
                                                    r.serie_doc,
                                                    r.num_doc,
                                                    r.ruc,
                                                    r.razon_social,
                                                    r.base,
                                                    r.igv,
                                                    ROUND(
                                                        CASE
                                                        WHEN r.moneda = 'S' 
                                                        THEN 0 
                                                        ELSE r.total / r.tipo_cambio 
                                                        END,
                                                        2
                                                    ) AS totalD,
                                                    FORMAT(
                                                        ROUND(
                                                        CASE
                                                            WHEN r.moneda = 'S' 
                                                            THEN 0 
                                                            ELSE r.total / r.tipo_cambio 
                                                        END,
                                                        2
                                                        ),
                                                        2
                                                    ) AS totalFD,
                                                    r.total AS totalS,
                                                    FORMAT(r.total, 2) AS totalFS,
                                                    CASE
                                                        WHEN r.moneda = 'S' 
                                                        THEN 'PEN' 
                                                        ELSE 'USD' 
                                                    END AS moneda,
                                                    r.tipo_cambio,
                                                    r.concepto,
                                                    r.comprobante,
                                                    r.contribuyente,
                                                    r.condicion,
                                                    r.alerta,
                                                    r.estado 
                                                FROM
                                                    reg_compras r
                                                WHERE r.ruc = :ruc 
                                                    AND r.serie_doc = :serie 
                                                    AND r.num_doc = :numero");

        $stmt->bindParam(":ruc", $ruc, PDO::PARAM_STR);
        $stmt->bindParam(":serie", $serie, PDO::PARAM_STR);
        $stmt->bindParam(":numero", $numero, PDO::PARAM_STR);                                                    

        $stmt -> execute();

        return $stmt -> fetch();

		$stmt -> close();

		$stmt = null;

	}    

}