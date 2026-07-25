<?php

require_once "conexion.php";

class ModeloCuentas
{

	/*=============================================
	CREAR CUENTA
	=============================================*/

	static public function mdlIngresarCuenta($tabla, $datos)
	{

		$stmt = Conexion::conectar()->prepare("INSERT INTO $tabla (
			tipo_doc,
			num_cta,
			cliente,
			vendedor,
			fecha,
			fecha_ven,
			fecha_cep,
			tip_mon,
			monto,
			tip_cambio,
			estado,
			notas,
			cod_pago,
			doc_origen,
			renovacion,
			protesta,
			usuario,
			saldo,
			ult_pago,
			estado_doc,
			banco,
			num_unico,
			fecha_envio,
			fecha_abono,
			tip_mov,
			usureg,
			pcreg,
			fecha_ori
		  ) 
		  VALUES
			(
			  :tipo_doc,
			  :num_cta,
			  :cliente,
			  :vendedor,
			  :fecha,
			  :fecha_ven,
			  :fecha_cep,
			  :tip_mon,
			  :monto,
			  :tip_cambio,
			  :estado,
			  :notas,
			  :cod_pago,
			  :doc_origen,
			  :renovacion,
			  :protesta,
			  :usuario,
			  :saldo,
			  :ult_pago,
			  :estado_doc,
			  :banco,
			  :num_unico,
			  :fecha_envio,
			  :fecha_abono,
			  :tip_mov,
			  :usureg,
			  :pcreg,
			  :fecha_ori
			)");

		$stmt->bindParam(":tipo_doc", $datos["tipo_doc"], PDO::PARAM_STR);
		$stmt->bindParam(":num_cta", $datos["num_cta"], PDO::PARAM_STR);
		$stmt->bindParam(":cliente", $datos["cliente"], PDO::PARAM_STR);
		$stmt->bindParam(":vendedor", $datos["vendedor"], PDO::PARAM_STR);
		$stmt->bindParam(":fecha", $datos["fecha"], PDO::PARAM_STR);
		$stmt->bindParam(":fecha_ven", $datos["fecha_ven"], PDO::PARAM_STR);
		$stmt->bindParam(":fecha_cep", $datos["fecha_cep"], PDO::PARAM_STR);
		$stmt->bindParam(":tip_mon", $datos["tip_mon"], PDO::PARAM_STR);
		$stmt->bindParam(":monto", $datos["monto"], PDO::PARAM_STR);
		$stmt->bindParam(":tip_cambio", $datos["tip_cambio"], PDO::PARAM_STR);
		$stmt->bindParam(":estado", $datos["estado"], PDO::PARAM_STR);
		$stmt->bindParam(":notas", $datos["notas"], PDO::PARAM_STR);
		$stmt->bindParam(":cod_pago", $datos["cod_pago"], PDO::PARAM_STR);
		$stmt->bindParam(":doc_origen", $datos["doc_origen"], PDO::PARAM_STR);
		$stmt->bindParam(":renovacion", $datos["renovacion"], PDO::PARAM_STR);
		$stmt->bindParam(":protesta", $datos["protesta"], PDO::PARAM_STR);
		$stmt->bindParam(":usuario", $datos["usuario"], PDO::PARAM_STR);
		$stmt->bindParam(":saldo", $datos["saldo"], PDO::PARAM_STR);
		$stmt->bindParam(":ult_pago", $datos["ult_pago"], PDO::PARAM_STR);
		$stmt->bindParam(":estado_doc", $datos["estado_doc"], PDO::PARAM_STR);
		$stmt->bindParam(":banco", $datos["banco"], PDO::PARAM_STR);
		$stmt->bindParam(":num_unico", $datos["num_unico"], PDO::PARAM_STR);
		$stmt->bindParam(":fecha_envio", $datos["fecha_envio"], PDO::PARAM_STR);
		$stmt->bindParam(":fecha_abono", $datos["fecha_abono"], PDO::PARAM_STR);
		$stmt->bindParam(":tip_mov", $datos["tip_mov"], PDO::PARAM_STR);
		$stmt->bindParam(":usureg", $datos["usureg"], PDO::PARAM_STR);
		$stmt->bindParam(":pcreg", $datos["pcreg"], PDO::PARAM_STR);
		$stmt->bindParam(":fecha_ori", $datos["fecha_ori"], PDO::PARAM_STR);


		if ($stmt->execute()) {

			return "ok";
		} else {

			return "error";
		}

		$stmt->close();
		$stmt = null;
	}

	static public function mdlIngresarCuentaBckp($tabla, $datos)
	{

		$stmt = Conexion::conectar()->prepare("INSERT INTO cuenta_cte_bkpjf 
		(SELECT 
		  *,
		  :usuario_bkp,
		  :fecha_bkp 
		FROM
		  cuenta_ctejf
		  WHERE num_cta = :num_cta) ;");

		$stmt->bindParam(":usuario_bkp", $datos["usuario_bkp"], PDO::PARAM_STR);
		$stmt->bindParam(":fecha_bkp", $datos["fecha_bkp"], PDO::PARAM_STR);
		$stmt->bindParam(":num_cta", $datos["num_cta"], PDO::PARAM_STR);


		if ($stmt->execute()) {

			return "ok";
		} else {

			return "error";
		}

		$stmt->close();
		$stmt = null;
	}

	static public function mdlIngresarCuentaBckp2($tabla, $datos)
	{

		$stmt = Conexion::conectar()->prepare("INSERT INTO cuenta_cte_bkpjf 
		(SELECT 
		  *,
		  :usuario_bkp,
		  :fecha_bkp,
		  :pc_bkp 
		FROM
		  cuenta_ctejf
		  WHERE id = :id) ;");

		$stmt->bindParam(":usuario_bkp", $datos["usuario_bkp"], PDO::PARAM_STR);
		$stmt->bindParam(":fecha_bkp", $datos["fecha_bkp"], PDO::PARAM_STR);
		$stmt->bindParam(":pc_bkp", $datos["pc_bkp"], PDO::PARAM_STR);
		$stmt->bindParam(":id", $datos["id"], PDO::PARAM_STR);


		if ($stmt->execute()) {

			return "ok";
		} else {

			return $stmt->errorInfo();
		}

		$stmt->close();
		$stmt = null;
	}


	/*=============================================
	MOSTRAR CUENTAS
	=============================================*/

	static public function mdlMostrarCuentas($tabla, $item, $valor)
	{

		if ($item != null) {

			$stmt = Conexion::conectar()->prepare("SELECT c.*,cli.nombre FROM $tabla c LEFT JOIN clientesjf cli ON c.cliente=cli.codigo WHERE c.tip_mov ='+' AND c.$item = :$item ");

			$stmt->bindParam(":" . $item, $valor, PDO::PARAM_STR);

			$stmt->execute();

			return $stmt->fetch();
		} else {

			$stmt = Conexion::conectar()->prepare("SELECT c.*,cli.nombre FROM $tabla c LEFT JOIN clientesjf cli ON c.cliente=cli.codigo WHERE c.tip_mov ='+'");

			$stmt->execute();

			return $stmt->fetchAll();
		}

		$stmt->close();

		$stmt = null;
	}

	static public function mdlMostrarCuentasNroUnico($documento)
	{

		$stmt = Conexion::conectar()->prepare("SELECT 
		cc.tipo_doc,
		cc.num_cta,
		cc.fecha 
	  FROM
		cuenta_ctejf cc 
	  WHERE cC.estado = 'PENDIENTE' 
		AND cc.tip_mov = '+' 
		AND tipo_doc = '85' 
		AND (
		  cc.num_unico IS NULL 
		  OR cc.num_unico = ''
		) 
		AND cc.estado_doc = '01' 
		AND cc.banco = '02' 
		AND REPLACE(num_cta, '-', '') = :documento");

		$stmt->bindParam(":documento", $documento, PDO::PARAM_STR);

		$stmt->execute();

		return $stmt->fetch();


		$stmt->close();

		$stmt = null;
	}

	/*=============================================
	MOSTRAR CUENTAS V2
	=============================================*/

	static public function mdlMostrarCuentasV2($numCta, $tipoDoc)
	{

		$stmt = Conexion::conectar()->prepare("SELECT 
						c.id,
						c.num_cta,
						c.tipo_doc,
						c.num_cta,
						c.fecha,
						c.fecha_ven,
						c.cliente,
						cli.nombre ,
						c.vendedor ,
						c.estado,
						c.saldo ,
						c.num_unico ,
						c.monto
						FROM
							cuenta_ctejf c 
							LEFT JOIN clientesjf cli 
							ON c.cliente = cli.codigo 
						WHERE c.tip_mov = '+' 
							AND c.num_cta = :numCta
							AND c.tipo_doc = :tipoDoc");

		$stmt->bindParam(":numCta", $numCta, PDO::PARAM_STR);
		$stmt->bindParam(":tipoDoc", $tipoDoc, PDO::PARAM_STR);

		$stmt->execute();

		return $stmt->fetch();

		$stmt->close();

		$stmt = null;
	}

	static public function mdlNotificacionesPendientes()
	{
		$stmt = Conexion::conectar()->prepare("SELECT
				cc.tipo_doc ,
				cc.num_cta ,
				cc.doc_origen ,
				cc.fecha ,
				cc.fecha_ven ,
				case
					when cc.fecha_ven = CURDATE() then 'VENCIMIENTO'
					when cc.fecha_ven = DATE_SUB(CURDATE(), interval 5 day) then 'RECORDATORIO'
				end as tipo_notificacion,
				cc.vendedor ,
				cc.monto ,
				cc.saldo ,
				cc.num_unico ,
				cc.cliente ,
				c.nombre ,
				replace(c.telefono, ' ', '') as telefono,
				case when cc.num_unico = '' || c.telefono = '' then 'NO' else 'SI' end as notificacion
			from
				cuenta_ctejf cc
			left join clientesjf c 
				on
				cc.cliente = c.codigo
			where
				cc.estado = 'PENDIENTE'
				and cc.tip_mov = '+'
				and cc.tipo_doc = '85'
				and cc.vendedor in ('04', '05')
				and cc.protesta = '0'
				and cc.fecha_ven in (DATE_ADD(CURDATE(), interval -5 day) , curdate())
			order by
				cc.fecha_ven");

		$stmt->execute();

		return $stmt->fetchAll();

		$stmt->close();

		$stmt = null;
	}

	/*=============================================
	LETRAS ENVIADAS AL BANCO - PLAZO DE PROTESTO
	=============================================*/

	static private function esDiaHabil($fecha)
	{
		$dow = (int)(new DateTime($fecha))->format('N');
		return $dow < 6;
	}

	static public function sumarDiasHabiles($fecha, $dias)
	{
		$date = new DateTime($fecha);
		$agregados = 0;

		while ($agregados < $dias) {
			$date->modify('+1 day');
			if (self::esDiaHabil($date->format('Y-m-d'))) {
				$agregados++;
			}
		}

		return $date->format('Y-m-d');
	}

	static public function contarDiasHabilesEntre($fechaInicio, $fechaFin)
	{
		$start = new DateTime($fechaInicio);
		$end = new DateTime($fechaFin);

		if ($end <= $start) {
			return 0;
		}

		$count = 0;
		$current = clone $start;

		while ($current < $end) {
			$current->modify('+1 day');
			if (self::esDiaHabil($current->format('Y-m-d'))) {
				$count++;
			}
		}

		return $count;
	}

	static public function calcularPlazoProtesto($fechaVen, $diasGracia = 8)
	{
		date_default_timezone_set('America/Lima');
		$hoy = date('Y-m-d');
		$fechaLimite = self::sumarDiasHabiles($fechaVen, $diasGracia);

		if ($hoy < $fechaVen) {
			$diasRestantes = self::contarDiasHabilesEntre($hoy, $fechaLimite);

			return [
				'fecha_limite_protesto' => $fechaLimite,
				'dias_transcurridos' => 0,
				'dias_restantes' => $diasRestantes,
				'estado' => 'POR VENCER'
			];
		}

		$diasTranscurridos = self::contarDiasHabilesEntre($fechaVen, $hoy);

		if ($hoy > $fechaLimite) {
			return [
				'fecha_limite_protesto' => $fechaLimite,
				'dias_transcurridos' => $diasTranscurridos,
				'dias_restantes' => 0,
				'estado' => 'PLAZO VENCIDO'
			];
		}

		if ($hoy === $fechaLimite) {
			return [
				'fecha_limite_protesto' => $fechaLimite,
				'dias_transcurridos' => $diasTranscurridos,
				'dias_restantes' => 0,
				'estado' => 'ULTIMO DIA'
			];
		}

		$diasRestantes = self::contarDiasHabilesEntre($hoy, $fechaLimite);
		$estado = $diasRestantes <= 2 ? 'URGENTE' : 'EN PLAZO';

		return [
			'fecha_limite_protesto' => $fechaLimite,
			'dias_transcurridos' => $diasTranscurridos,
			'dias_restantes' => $diasRestantes,
			'estado' => $estado
		];
	}

	static public function esLetraPagableHoy($fechaVen, $diasGracia = 8)
	{
		$plazo = self::calcularPlazoProtesto($fechaVen, $diasGracia);

		return in_array($plazo['estado'], ['EN PLAZO', 'URGENTE', 'ULTIMO DIA'], true);
	}

	static private function filtrarLetrasPagablesHoy($letras)
	{
		$filtradas = [];

		foreach ($letras as $letra) {
			if (self::esLetraPagableHoy($letra['fecha_ven'])) {
				$filtradas[] = $letra;
			}
		}

		usort($filtradas, function ($a, $b) {
			$cmpVendedor = strcmp($a['vendedor'], $b['vendedor']);

			if ($cmpVendedor !== 0) {
				return $cmpVendedor;
			}

			return strcmp($a['fecha_ven'], $b['fecha_ven']);
		});

		return $filtradas;
	}

	static public function mdlLetrasPlazoProtesto()
	{
		$stmt = Conexion::conectar()->prepare("SELECT
				cc.id,
				cc.tipo_doc,
				cc.num_cta,
				cc.doc_origen,
				cc.fecha,
				cc.fecha_ven,
				cc.fecha_envio,
				cc.vendedor,
				cc.monto,
				cc.saldo,
				cc.num_unico,
				cc.cliente,
				cc.banco,
				c.nombre,
				REPLACE(c.telefono, ' ', '') AS telefono
			FROM cuenta_ctejf cc
			LEFT JOIN clientesjf c ON cc.cliente = c.codigo
			WHERE cc.estado = 'PENDIENTE'
				AND cc.tip_mov = '+'
				AND cc.tipo_doc = '85'
				AND cc.protesta = '0'
				AND cc.saldo > 0
				AND cc.fecha_envio IS NOT NULL
				AND cc.fecha_envio <> ''
				AND cc.fecha_envio <> '-'
				AND UPPER(TRIM(cc.num_unico)) NOT LIKE '%CARTERA%'
				AND cc.fecha_ven <= CURDATE()
			ORDER BY cc.vendedor ASC, cc.fecha_ven ASC");

		$stmt->execute();

		$resultados = $stmt->fetchAll();

		return self::filtrarLetrasPagablesHoy($resultados);

		$stmt->close();

		$stmt = null;
	}

	static public function mdlLetrasPlazoProtestoUrgentes()
	{
		$letras = self::mdlLetrasPlazoProtesto();
		$urgentes = [];

		foreach ($letras as $letra) {
			$plazo = self::calcularPlazoProtesto($letra['fecha_ven']);

			if (!in_array($plazo['estado'], ['URGENTE', 'ULTIMO DIA'], true)) {
				continue;
			}

			$letra['fecha_limite_protesto'] = $plazo['fecha_limite_protesto'];
			$letra['dias_transcurridos'] = $plazo['dias_transcurridos'];
			$letra['dias_restantes'] = $plazo['dias_restantes'];
			$letra['estado_plazo'] = $plazo['estado'];
			$urgentes[] = $letra;
		}

		return $urgentes;
	}


	/*=============================================
	VALIDAR CUENTA
	=============================================*/

	static public function mdlValidarCuenta($tabla, $item, $valor, $item2, $valor2)
	{


		$stmt = Conexion::conectar()->prepare("SELECT c.*,cli.nombre FROM $tabla c LEFT JOIN clientesjf cli ON c.cliente=cli.codigo WHERE c.tip_mov ='+' AND c.$item = :$item AND c.$item2 = :$item2 ");

		$stmt->bindParam(":" . $item, $valor, PDO::PARAM_STR);
		$stmt->bindParam(":" . $item2, $valor2, PDO::PARAM_STR);

		$stmt->execute();

		return $stmt->fetch();


		$stmt->close();

		$stmt = null;
	}

	/*=============================================
	MOSTRAR CUENTAS LETRAS IMPRESION
	=============================================*/

	static public function mdlMostrarCuentasLetras($tabla, $item, $valor)
	{

		if ($item != null) {

			$stmt = Conexion::conectar()->prepare("SELECT 
			c.num_cta,
			c.doc_origen,
			c.fecha_ven,
			c.fecha,
			c.monto,
			c.saldo,
			cli.codigo,
			cli.nombre,
			cli.direccion,
			uc.ubcli,
			cli.documento,
			cli.telefono,
			cli.ubigeo,
			cli.aval_nombre,
			cli.aval_dir,
			cli.aval_postal,
			ua.ubaval,
			cli.aval_telf,
			cli.aval_ruc 
		  FROM
			cuenta_ctejf c 
			LEFT JOIN clientesjf cli 
			  ON c.cliente = cli.codigo 
			LEFT JOIN 
			  (SELECT 
				codigo,
				CONCAT(
				  Departamento,
				  '/',
				  provincia,
				  '/',
				  distrito
				) AS ubcli 
			  FROM
				ubigeo) AS uc 
			  ON cli.ubigeo = uc.codigo 
			LEFT JOIN 
			  (SELECT 
				codigo,
				CONCAT(
				  Departamento,
				  '/',
				  provincia,
				  '/',
				  distrito
				) AS ubaval 
			  FROM
				ubigeo) AS ua 
			  ON cli.aval_postal = ua.codigo 
			  WHERE c.tip_mov ='+' 
			  AND c.$item = :$item ");

			$stmt->bindParam(":" . $item, $valor, PDO::PARAM_STR);

			$stmt->execute();

			return $stmt->fetch();
		} else {

			$stmt = Conexion::conectar()->prepare("SELECT c.num_cta,c.doc_origen,c.fecha_ven,c.fecha,c.monto,cli.nombre,cli.direccion,cli.documento,cli.telefono FROM $tabla c LEFT JOIN clientesjf cli ON c.cliente=cli.codigo WHERE c.tip_mov ='+'");

			$stmt->execute();

			return $stmt->fetchAll();
		}

		$stmt->close();

		$stmt = null;
	}

	/*=============================================
	MOSTRAR CUENTAS GENERA LETRAS IMPRESION
	=============================================*/

	static public function mdlMostrarCuentasGeneradosLetras($tabla, $item, $valor)
	{

		if ($item != null) {

			$stmt = Conexion::conectar()->prepare("SELECT 
			c.num_cta,
			c.doc_origen,
			c.fecha_ven,
			c.fecha,
			c.monto,
			cli.codigo,
			cli.nombre,
			cli.direccion,
			uc.ubcli,
			cli.documento,
			cli.telefono,
			cli.ubigeo,
			cli.aval_nombre,
			cli.aval_dir,
			cli.aval_postal,
			ua.ubaval,
			cli.aval_telf,
			cli.aval_ruc 
		  FROM
			cuenta_ctejf c 
			LEFT JOIN clientesjf cli 
			  ON c.cliente = cli.codigo 
			LEFT JOIN 
			  (SELECT 
				codigo,
				CONCAT(
				  Departamento,
				  '/',
				  provincia,
				  '/',
				  distrito
				) AS ubcli 
			  FROM
				ubigeo) AS uc 
			  ON cli.ubigeo = uc.codigo 
			LEFT JOIN 
			  (SELECT 
				codigo,
				CONCAT(
				  Departamento,
				  '/',
				  provincia,
				  '/',
				  distrito
				) AS ubaval 
			  FROM
				ubigeo) AS ua 
			  ON cli.aval_postal = ua.codigo 
			  WHERE c.tip_mov ='+' 
			  AND c.$item = :$item ");

			$stmt->bindParam(":" . $item, $valor, PDO::PARAM_STR);

			$stmt->execute();

			return $stmt->fetchAll();
		} else {

			$stmt = Conexion::conectar()->prepare("SELECT c.num_cta,c.doc_origen,c.fecha_ven,c.fecha,c.monto,cli.nombre,cli.direccion,cli.documento,cli.telefono FROM $tabla c LEFT JOIN clientesjf cli ON c.cliente=cli.codigo WHERE c.tip_mov ='+'");

			$stmt->execute();

			return $stmt->fetchAll();
		}

		$stmt->close();

		$stmt = null;
	}

	/*=============================================
	MOSTRAR CUENTAS
	=============================================*/

	static public function mdlMostrarCuentasUnicos($tabla, $item, $valor)
	{

		if ($item != null) {
			$stmt = Conexion::conectar()->prepare("SELECT c.*,CONCAT(
				SUBSTR(c.fecha_ven, 9, 2),
				SUBSTR(c.fecha_ven, 6, 2),
				SUBSTR(c.fecha_ven, 3, 2)
			  ) AS fechaVen,
			  REPLACE(c.num_cta,'-','') AS cuenta,
			  cli.nombre,
			  cli.ape_paterno,
			  cli.ape_materno,
			  cli.nombres,
			  cli.documento 
			  FROM $tabla c 
			LEFT JOIN clientesjf cli ON c.cliente=cli.codigo
			WHERE c.tip_mov ='+'
			AND c.tipo_doc = '85'
			AND c.estado= 'PENDIENTE'
			AND c.id = $valor");
			$stmt->execute();

			return $stmt->fetch();
		} else {
			$stmt = Conexion::conectar()->prepare("SELECT 
												c.*,
												REPLACE(
												DATE_FORMAT(c.fecha_ven, '%d-%m-%Y'),
												'-',
												''
												) AS fechaVen,
												REPLACE(c.num_cta, '-', '') AS cuenta,
												cli.nombre,
												cli.ape_paterno,
												cli.ape_materno,
												cli.nombres,
												cli.documento 
											FROM
												cuenta_ctejf c 
												LEFT JOIN clientesjf cli 
												ON c.cliente = cli.codigo 
											WHERE c.tip_mov = '+' 
												AND c.tipo_doc = '85' 
												AND c.estado = 'PENDIENTE' 
												AND (
												c.estado_doc IS NULL 
												OR c.estado_doc = ''
												) 
												AND c.protesta = 0 
												AND (
												c.num_unico <> 'Cartera' 
												OR c.num_unico IS NULL 
												OR c.num_unico = ''
												) 
												AND DATE(c.fecha_ven) > DATE(NOW())");


			$stmt->execute();

			return $stmt->fetchAll();
		}



		$stmt->close();

		$stmt = null;
	}



	static public function mdlMostrarPagos($tabla, $item, $valor)
	{

		if ($item != null) {

			$stmt = Conexion::conectar()->prepare("SELECT * FROM $tabla WHERE $item = :$item ORDER BY codigo");

			$stmt->bindParam(":" . $item, $valor, PDO::PARAM_STR);

			$stmt->execute();

			return $stmt->fetchAll();
		} else {

			$stmt = Conexion::conectar()->prepare("SELECT * FROM $tabla");

			$stmt->execute();

			return $stmt->fetchAll();
		}

		$stmt->close();

		$stmt = null;
	}

	static public function mdlMostrarCancelaciones($tabla, $item, $valor)
	{

		if ($item != null) {

			$stmt = Conexion::conectar()->prepare("SELECT * FROM $tabla WHERE $item = :$item AND tip_mov = '-'");

			$stmt->bindParam(":" . $item, $valor, PDO::PARAM_STR);

			$stmt->execute();

			return $stmt->fetchAll();
		} else {

			$stmt = Conexion::conectar()->prepare("SELECT * FROM $tabla WHERE tip_mov = '-' ");

			$stmt->execute();

			return $stmt->fetchAll();
		}

		$stmt->close();

		$stmt = null;
	}


	static public function mdlMostrarCancelacionesV2($numCta, $codCta)
	{


		$stmt = Conexion::conectar()->prepare("SELECT 
													c.id,
													c.cod_pago,
													c.doc_origen,
													c.fecha,
													c.notas,
													c.monto
												FROM
													cuenta_ctejf c 
												WHERE c.tip_mov = '-' 
													AND c.tipo_doc = :codCta 
													AND c.num_cta = :numCta");

		$stmt->bindParam(":numCta", $numCta, PDO::PARAM_STR);
		$stmt->bindParam(":codCta", $codCta, PDO::PARAM_STR);

		$stmt->execute();

		return $stmt->fetchAll();


		$stmt->close();

		$stmt = null;
	}

	static public function mdlMostrarCancelacion($tabla, $item, $valor)
	{


		$stmt = Conexion::conectar()->prepare("SELECT * FROM $tabla WHERE $item = :$item AND tip_mov = '-' ");

		$stmt->bindParam(":" . $item, $valor, PDO::PARAM_STR);

		$stmt->execute();

		return $stmt->fetch();

		$stmt->close();

		$stmt = null;
	}
	/*=============================================
	EDITAR TIPO DE PAGO
	=============================================*/

	static public function mdlEditarCuenta($tabla, $datos)
	{

		$stmt = Conexion::conectar()->prepare("UPDATE $tabla SET tipo_doc=:tipo_doc,num_cta=:num_cta,cliente=:cliente,vendedor=:vendedor,fecha=:fecha,fecha_ven=:fecha_ven,fecha_cep=:fecha_cep,tip_mon=:tip_mon,monto=:monto,tip_cambio=:tip_cambio,estado=:estado,notas=:notas,cod_pago=:cod_pago,doc_origen=:doc_origen,renovacion=:renovacion,protesta=:protesta,usuario=:usuario,saldo=:saldo,ult_pago=:ult_pago,estado_doc=:estado_doc,banco=:banco,num_unico=:num_unico,fecha_envio=:fecha_envio,fecha_abono=:fecha_abono WHERE id = :id");

		$stmt->bindParam(":id", $datos["id"], PDO::PARAM_INT);
		$stmt->bindParam(":tipo_doc", $datos["tipo_doc"], PDO::PARAM_STR);
		$stmt->bindParam(":num_cta", $datos["num_cta"], PDO::PARAM_STR);
		$stmt->bindParam(":cliente", $datos["cliente"], PDO::PARAM_STR);
		$stmt->bindParam(":vendedor", $datos["vendedor"], PDO::PARAM_STR);
		$stmt->bindParam(":fecha", $datos["fecha"], PDO::PARAM_STR);
		$stmt->bindParam(":fecha_ven", $datos["fecha_ven"], PDO::PARAM_STR);
		$stmt->bindParam(":fecha_cep", $datos["fecha_cep"], PDO::PARAM_STR);
		$stmt->bindParam(":tip_mon", $datos["tip_mon"], PDO::PARAM_STR);
		$stmt->bindParam(":monto", $datos["monto"], PDO::PARAM_STR);
		$stmt->bindParam(":tip_cambio", $datos["tip_cambio"], PDO::PARAM_STR);
		$stmt->bindParam(":estado", $datos["estado"], PDO::PARAM_STR);
		$stmt->bindParam(":notas", $datos["notas"], PDO::PARAM_STR);
		$stmt->bindParam(":cod_pago", $datos["cod_pago"], PDO::PARAM_STR);
		$stmt->bindParam(":doc_origen", $datos["doc_origen"], PDO::PARAM_STR);
		$stmt->bindParam(":renovacion", $datos["renovacion"], PDO::PARAM_STR);
		$stmt->bindParam(":protesta", $datos["protesta"], PDO::PARAM_STR);
		$stmt->bindParam(":usuario", $datos["usuario"], PDO::PARAM_STR);
		$stmt->bindParam(":saldo", $datos["saldo"], PDO::PARAM_STR);
		$stmt->bindParam(":ult_pago", $datos["ult_pago"], PDO::PARAM_STR);
		$stmt->bindParam(":estado_doc", $datos["estado_doc"], PDO::PARAM_STR);
		$stmt->bindParam(":banco", $datos["banco"], PDO::PARAM_STR);
		$stmt->bindParam(":num_unico", $datos["num_unico"], PDO::PARAM_STR);
		$stmt->bindParam(":fecha_envio", $datos["fecha_envio"], PDO::PARAM_STR);
		$stmt->bindParam(":fecha_abono", $datos["fecha_abono"], PDO::PARAM_STR);

		if ($stmt->execute()) {

			return "ok";
		} else {

			return "error";
		}

		$stmt->close();
		$stmt = null;
	}

	/*=============================================
	EDITAR TULTIMO PAGO
	=============================================*/

	static public function mdlEditarUltPago($numCta, $tipoDoc)
	{

		$stmt = Conexion::conectar()->prepare("UPDATE 
								cuenta_ctejf c1 
								LEFT JOIN 
								(SELECT 
									num_cta,
									tipo_doc,
									MAX(fecha) AS ult_pago 
								FROM
									cuenta_ctejf 
								WHERE num_cta = :numCta 
									AND tipo_doc = :tipoDoc 
									AND tip_mov = '-') AS c2 
								ON c1.num_cta = c2.num_cta 
								AND c1.tipo_doc = c2.tipo_doc 
								SET c1.ult_pago = c2.ult_pago 
							WHERE c1.num_cta = :numCta 
								AND c1.tipo_doc = :tipoDoc 
								AND c1.tip_mov = '+'");

		$stmt->bindParam(":numCta", $numCta, PDO::PARAM_STR);
		$stmt->bindParam(":tipoDoc", $tipoDoc, PDO::PARAM_STR);


		if ($stmt->execute()) {

			return "ok";
		} else {

			return "error";
		}

		$stmt->close();
		$stmt = null;
	}

	static public function mdlEditarCancelacion($tabla, $datos)
	{

		$stmt = Conexion::conectar()->prepare("UPDATE 
												$tabla 
											SET
												tipo_doc = :tipo_doc,
												cod_pago = :cod_pago,
												num_cta = :num_cta,
												doc_origen = :doc_origen,
												cliente = :cliente,
												vendedor = :vendedor,
												fecha = :fecha,
												monto = :monto,
												notas = :notas,
												usuario = :usuario,
												usureg = :usureg,
												pcreg = :pcreg 
											WHERE id = :id");

		$stmt->bindParam(":id", $datos["id"], PDO::PARAM_INT);
		$stmt->bindParam(":tipo_doc", $datos["tipo_doc"], PDO::PARAM_STR);
		$stmt->bindParam(":cod_pago", $datos["cod_pago"], PDO::PARAM_STR);
		$stmt->bindParam(":num_cta", $datos["num_cta"], PDO::PARAM_STR);
		$stmt->bindParam(":doc_origen", $datos["doc_origen"], PDO::PARAM_STR);
		$stmt->bindParam(":cliente", $datos["cliente"], PDO::PARAM_STR);
		$stmt->bindParam(":vendedor", $datos["vendedor"], PDO::PARAM_STR);
		$stmt->bindParam(":fecha", $datos["fecha"], PDO::PARAM_STR);
		$stmt->bindParam(":monto", $datos["monto"], PDO::PARAM_STR);
		$stmt->bindParam(":notas", $datos["notas"], PDO::PARAM_STR);
		$stmt->bindParam(":usuario", $datos["usuario"], PDO::PARAM_STR);
		$stmt->bindParam(":usureg", $datos["usureg"], PDO::PARAM_STR);
		$stmt->bindParam(":pcreg", $datos["pcreg"], PDO::PARAM_STR);

		if ($stmt->execute()) {

			return "ok";
		} else {

			return $stmt->errorInfo();
		}

		$stmt->close();
		$stmt = null;
	}

	/*=============================================
	ELIMINAR CUENTA
	=============================================*/

	static public function mdlEliminarCuenta($tabla, $datos)
	{

		$stmt = Conexion::conectar()->prepare("DELETE FROM $tabla WHERE id = :id");

		$stmt->bindParam(":id", $datos, PDO::PARAM_INT);

		if ($stmt->execute()) {

			return "ok";
		} else {

			return "error";
		}

		$stmt->close();

		$stmt = null;
	}

	/*=============================================
	ELIMINAR TIPO DE PAGO
	=============================================*/

	static public function mdlEliminarCuentaCancelacion($tabla, $datos)
	{

		$stmt = Conexion::conectar()->prepare("DELETE FROM $tabla WHERE num_cta = :num_cta");

		$stmt->bindParam(":num_cta", $datos, PDO::PARAM_STR);

		if ($stmt->execute()) {

			return "ok";
		} else {

			return "error";
		}

		$stmt->close();

		$stmt = null;
	}

	/* 
	* Método para actualizar un dato CON EL articulo
	*/
	static public function mdlActualizarUnDato($tabla, $item1, $valor1, $valor2)
	{

		$sql = "UPDATE $tabla SET $item1=:$item1 WHERE id=:id";

		$stmt = Conexion::conectar()->prepare($sql);

		$stmt->bindParam(":" . $item1, $valor1, PDO::PARAM_STR);
		$stmt->bindParam(":id", $valor2, PDO::PARAM_STR);

		if ($stmt->execute()) {

			return "ok";
		} else {

			return $stmt->errorInfo();
		}

		$stmt = null;
	}

	static public function mdlActualizarCuenta($saldoNuevo, $estado, $ult_pago, $id)
	{
		$sql = "UPDATE cuenta_ctejf SET saldo=:saldoNuevo, estado=:estado, ult_pago=:ult_pago WHERE id=:id";

		$stmt = Conexion::conectar()->prepare($sql);

		$stmt->bindParam(":saldoNuevo", $saldoNuevo, PDO::PARAM_STR);
		$stmt->bindParam(":estado", $estado, PDO::PARAM_STR);
		$stmt->bindParam(":ult_pago", $ult_pago, PDO::PARAM_STR);
		$stmt->bindParam(":id", $id, PDO::PARAM_STR);

		if ($stmt->execute()) {

			return "ok";
		} else {

			return $stmt->errorInfo();
		}
	}

	//* ACTUALIZAR ESTADO DE CUENTA
	static public function mdlActualizarEstado($valor2)
	{

		$sql = "UPDATE cuenta_ctejf SET estado='PENDIENTE' WHERE id=:id";

		$stmt = Conexion::conectar()->prepare($sql);

		$stmt->bindParam(":id", $valor2, PDO::PARAM_INT);

		if ($stmt->execute()) {

			return $sql;
		} else {

			return $stmt->errorInfo();
		}

		$stmt = null;
	}
	/*=============================================
	ELIMINAR TIPO DE PAGO
	=============================================*/

	static public function mdlMostrarCuentasPendientes($tabla, $saldo)
	{

		if ($saldo != "null") {
			$stmt = Conexion::conectar()->prepare("SELECT 
  c.saldo,
  c.monto,
  c.id,
  c.tipo_doc,
  c.num_cta,
  c.cliente,
  cli.documento,
  c.fecha,
  c.fecha_ven,
  cli.nombre,
  '1' AS rank 
		  FROM
			cuenta_ctejf c 
			LEFT JOIN clientesjf cli 
			  ON c.cliente = cli.codigo 
		  WHERE saldo > 0 
			AND tip_mov = '+' 
			AND saldo LIKE '%$saldo%' 
		  UNION
		  (SELECT 
			c.saldo,
			c.monto,
			c.id,
			c.tipo_doc,
			c.num_cta,
			c.cliente,
			cli.documento,
			c.fecha,
			c.fecha_ven,
			cli.nombre,
			'2' AS rank 
		  FROM
			cuenta_ctejf c 
			LEFT JOIN clientesjf cli 
			  ON c.cliente = cli.codigo 
		  WHERE saldo > 0 
			AND tip_mov = '+' 
			AND (
			  saldo BETWEEN ($saldo-1000) 
			  AND ($saldo+1000)
			) 
		  ORDER BY c.saldo) 
		  UNION
		  (SELECT 
			c.saldo,
			c.monto,
			c.id,
			c.tipo_doc,
			c.num_cta,
			c.cliente,
			cli.documento,
			c.fecha,
			c.fecha_ven,
			cli.nombre,
			'3' AS rank 
		  FROM
			cuenta_ctejf c 
			LEFT JOIN clientesjf cli 
			  ON c.cliente = cli.codigo 
		  WHERE saldo > 0 
			AND tip_mov = '+' 
		  ORDER BY c.saldo)");

			$stmt->execute();

			return $stmt->fetchAll();
		} else {
			$stmt = Conexion::conectar()->prepare("SELECT
			c.id, 
			c.tipo_doc,
			c.num_cta,
			c.cliente,
			cli.documento,
			c.fecha,
			c.fecha_ven,
			c.monto,
			c.saldo,
			cli.nombre 
		  FROM
			cuenta_ctejf  c
		  LEFT JOIN clientesjf cli
		  ON c.cliente = cli.codigo
		WHERE saldo > 0 
			AND tip_mov ='+' 
		ORDER BY saldo ASC ");

			$stmt->execute();

			return $stmt->fetchAll();
		}
		$stmt->close();

		$stmt = null;
	}

	/*=============================================
	MOSTRAR CUENTAS
	=============================================*/

	static public function mdlMostrarTipoCuentas($tabla, $item, $valor)
	{

		if ($valor != "null") {

			$stmt = Conexion::conectar()->prepare("SELECT 
			c.*,cli.nombre,IFNULL(DATEDIFF(c.ult_pago, c.fecha_ven), 0) AS diferencia,
			DATE_FORMAT(c.fecha, '%d-%m-%Y')AS nuevaFecha, 
			DATE_FORMAT(c.fecha_ven, '%d-%m-%Y')AS nuevaFechaVen,
			DATE_FORMAT(c.ult_pago, '%d-%m-%Y')AS nuevaFechaPago  
			FROM $tabla c LEFT JOIN clientesjf cli ON c.cliente=cli.codigo 
			WHERE c.tip_mov ='+' AND c.$item = :$item 
			ORDER BY c.fecha_ven DESC, c.id DESC");

			$stmt->bindParam(":" . $item, $valor, PDO::PARAM_STR);

			$stmt->execute();

			return $stmt->fetchAll();
		} else {
			$stmt = Conexion::conectar()->prepare("SELECT 
			c.*,
			cli.nombre,
			IFNULL(DATEDIFF(c.ult_pago, c.fecha_ven), 0) AS diferencia,
			DATE_FORMAT(c.fecha, '%d-%m-%Y')AS nuevaFecha, 
			DATE_FORMAT(c.fecha_ven, '%d-%m-%Y')AS nuevaFechaVen,
			DATE_FORMAT(c.ult_pago, '%d-%m-%Y')AS nuevaFechaPago 
		  FROM
			cuenta_ctejf c 
			LEFT JOIN clientesjf cli 
			  ON c.cliente = cli.codigo 
		  WHERE c.tip_mov = '+' 
		  ORDER BY c.id ASC");

			$stmt->execute();

			return $stmt->fetchAll();
		}

		$stmt->close();

		$stmt = null;
	}

	/*=============================================
	RANGO FECHAS
	=============================================*/

	static public function mdlRangoFechasCuentas($tabla, $ano)
	{

		if ($ano == "null") {

			$stmt = Conexion::conectar()->prepare("SELECT 
				c.id,
				c.tipo_doc,
				c.num_cta ,
				c.cod_pago ,
				c.doc_origen ,
				c.fecha,
				c.fecha_ven,
				c.monto,
				c.saldo ,
				c.tip_cambio ,
				c.ult_pago ,
				c.cliente ,
				c.vendedor ,
				c.notas,
				c.fecha_cep ,
				c.banco ,
				c.num_unico ,
				c.renovacion ,
				c.protesta ,
				c.usuario ,
				c.tip_mon ,
				c.estado ,
				c.estado_doc ,
				c.fecha_envio ,
				c.fecha_abono ,
				c.fecha_creacion ,
				c.usureg ,
				c.pcreg ,
				c.fecha_ori ,
				c.fecha_ori_ven,
				cli.nombre 
			FROM $tabla c LEFT JOIN clientesjf cli ON c.cliente=cli.codigo WHERE c.tip_mov ='+'  AND YEAR(c.fecha) = '2022' ORDER BY c.id ASC");

			$stmt->execute();

			return $stmt->fetchAll();
		} else {

			$stmt = Conexion::conectar()->prepare("SELECT
					c.id,
					c.tipo_doc ,
					c.num_cta ,
					c.cod_pago,
					c.doc_origen,
					c.fecha,
					c.fecha_ven,
					c.monto,
					c.saldo,
					c.tip_cambio,
					c.ult_pago,
					c.cliente,
					cli.nombre,
					c.vendedor,
					c.fecha_cep,
					c.banco,
					c.num_unico,
					c.renovacion,
					c.protesta,
					c.tip_mon,
					c.estado,
					c.estado_doc,
					c.fecha_envio,
					c.fecha_creacion
					from
						cuenta_ctejf c
					left join clientesjf cli on
						c.cliente = cli.codigo
					where YEAR(c.fecha) = '" . $ano . "' AND c.tip_mov ='+'");

			$stmt->bindParam(":fecha", $fechaFinal, PDO::PARAM_STR);

			$stmt->execute();

			return $stmt->fetchAll();
		}
	}

	/*=============================================
	RANGO FECHAS
	=============================================*/

	static public function mdlRangoFechasCuentasPendientes($tabla, $ano)
	{

		if ($ano == "null") {

			$stmt = Conexion::conectar()->prepare("SELECT c.*,cli.nombre FROM $tabla c LEFT JOIN clientesjf cli ON c.cliente=cli.codigo WHERE c.tip_mov ='+'  AND c.estado='PENDIENTE' ORDER BY c.id ASC");

			$stmt->execute();

			return $stmt->fetchAll();
		} else {

			$stmt = Conexion::conectar()->prepare("SELECT 
				c.id,
				c.tipo_doc,
				c.num_cta ,
				c.cod_pago ,
				c.doc_origen ,
				c.fecha,
				c.fecha_ven,
				c.monto,
				c.saldo ,
				c.tip_cambio ,
				c.ult_pago ,
				c.cliente ,
				c.vendedor ,
				c.notas,
				c.fecha_cep ,
				c.banco ,
				c.num_unico ,
				c.renovacion ,
				c.protesta ,
				c.usuario ,
				c.tip_mon ,
				c.estado ,
				c.estado_doc ,
				c.fecha_envio ,
				c.fecha_abono ,
				c.fecha_creacion ,
				c.usureg ,
				c.pcreg ,
				c.fecha_ori ,
				c.fecha_ori_ven,
				cli.nombre
			FROM $tabla c LEFT JOIN clientesjf cli ON c.cliente=cli.codigo WHERE YEAR(c.fecha) = '" . $ano . "' AND c.tip_mov ='+' AND c.estado='PENDIENTE' ");

			$stmt->bindParam(":fecha", $fechaFinal, PDO::PARAM_STR);

			$stmt->execute();

			return $stmt->fetchAll();
		}
	}

	/*=============================================
	RANGO FECHAS
	=============================================*/

	static public function mdlRangoFechasCuentasAprobadas($tabla, $ano)
	{

		if ($ano == "null") {

			$stmt = Conexion::conectar()->prepare("SELECT c.*,cli.nombre FROM $tabla c LEFT JOIN clientesjf cli ON c.cliente=cli.codigo WHERE c.tip_mov ='+' AND c.estado='CANCELADO' AND YEAR(c.fecha) = YEAR(NOW())  ORDER BY id ASC");

			$stmt->execute();

			return $stmt->fetchAll();
		} else {

			$stmt = Conexion::conectar()->prepare("SELECT c.*,cli.nombre FROM $tabla c LEFT JOIN clientesjf cli ON c.cliente=cli.codigo WHERE YEAR(c.fecha) = '" . $ano . "' AND c.tip_mov ='+' AND c.estado='CANCELADO' ");

			$stmt->bindParam(":fecha", $fechaFinal, PDO::PARAM_STR);

			$stmt->execute();

			return $stmt->fetchAll();
		}
	}

	static public function mdlMostrarCuentaCredito($tabla, $valor)
	{


		$stmt = Conexion::conectar()->prepare("SELECT 
		c.cliente,
		FORMAT(SUM(monto),2) AS total_credito 
		FROM
			cuenta_ctejf c 
		WHERE c.cliente = :cliente
		AND c.tip_mov = '+'
		GROUP BY c.cliente ");

		$stmt->bindParam(":cliente", $valor, PDO::PARAM_STR);

		$stmt->execute();

		return $stmt->fetch();

		$stmt->close();

		$stmt = null;
	}

	static public function mdlMostrarCuentaDeuda($tabla, $valor)
	{


		$stmt = Conexion::conectar()->prepare("SELECT 
		c.cliente,
		IFNULL(SUM(saldo),0) AS total_deuda
		FROM
			cuenta_ctejf c 
		WHERE c.cliente = :cliente
		AND c.estado = 'pendiente' 
		AND c.tip_mov = '+'
		GROUP BY c.cliente ");

		$stmt->bindParam(":cliente", $valor, PDO::PARAM_STR);

		$stmt->execute();

		return $stmt->fetch();

		$stmt->close();

		$stmt = null;
	}


	static public function mdlMostrarCuentaDeudaVencida($tabla, $valor)
	{


		$stmt = Conexion::conectar()->prepare("SELECT 
		c.cliente,
		IFNULL(SUM(saldo),0) AS total_vencido
		FROM
			cuenta_ctejf c 
		WHERE c.cliente = :cliente 
		AND c.estado = 'pendiente' 
		AND c.fecha_ven < NOW() 
		AND c.tip_mov = '+'
		GROUP BY c.cliente  ");

		$stmt->bindParam(":cliente", $valor, PDO::PARAM_STR);

		$stmt->execute();

		return $stmt->fetch();

		$stmt->close();

		$stmt = null;
	}


	/*=============================================
	RANGO FECHAS ENVIOS DE CUENTAS
	=============================================*/

	static public function mdlRangoFechaEnvioCuentas($tabla, $fechaInicial, $fechaFinal)
	{

		if ($fechaInicial == "null") {

			$stmt = Conexion::conectar()->prepare("SELECT e.*,u.nombre FROM $tabla e LEFT JOIN usuariosjf u ON e.usuario=u.id  ORDER BY e.id ASC");

			$stmt->execute();

			return $stmt->fetchAll();
		} else if ($fechaInicial == $fechaFinal) {

			$stmt = Conexion::conectar()->prepare("SELECT e.*,u.nombre FROM $tabla e LEFT JOIN usuariosjf u ON e.usuario=u.id WHERE e.fecha like '%$fechaFinal%' ");

			$stmt->bindParam(":fecha", $fechaFinal, PDO::PARAM_STR);

			$stmt->execute();

			return $stmt->fetchAll();
		} else {

			$fechaActual = new DateTime();
			$fechaActual->add(new DateInterval("P1D"));
			$fechaActualMasUno = $fechaActual->format("Y-m-d");

			$fechaFinal2 = new DateTime($fechaFinal);
			$fechaFinal2->add(new DateInterval("P1D"));
			$fechaFinalMasUno = $fechaFinal2->format("Y-m-d");

			if ($fechaFinalMasUno == $fechaActualMasUno) {

				$stmt = Conexion::conectar()->prepare("SELECT e.*,u.nombre FROM $tabla e LEFT JOIN usuariosjf u ON e.usuario=u.id  WHERE e.fecha BETWEEN '$fechaInicial' AND '$fechaFinalMasUno' ");
			} else {


				$stmt = Conexion::conectar()->prepare("SELECT e.*,u.nombre FROM $tabla e LEFT JOIN usuariosjf u ON e.usuario=u.id  WHERE e.fecha BETWEEN '$fechaInicial' AND '$fechaFinal'");
			}

			$stmt->execute();

			return $stmt->fetchAll();
		}
	}

	/*=============================================
	CREAR ENVIO LETRA CABECERA
	=============================================*/

	static public function mdlIngresarEnvioLetra($tabla, $datos)
	{

		$stmt = Conexion::conectar()->prepare("INSERT INTO $tabla(codigo,fecha,usuario,cantidad,archivo) VALUES (:codigo,:fecha,:usuario,:cantidad,:archivo)");

		$stmt->bindParam(":codigo", $datos["codigo"], PDO::PARAM_STR);
		$stmt->bindParam(":fecha", $datos["fecha"], PDO::PARAM_STR);
		$stmt->bindParam(":usuario", $datos["usuario"], PDO::PARAM_STR);
		$stmt->bindParam(":cantidad", $datos["cantidad"], PDO::PARAM_STR);
		$stmt->bindParam(":archivo", $datos["archivo"], PDO::PARAM_STR);

		if ($stmt->execute()) {

			return "ok";
		} else {

			return "error";
		}

		$stmt->close();
		$stmt = null;
	}

	/*=============================================
	CREAR ENVIO LETRA DETALLE
	=============================================*/

	static public function mdlIngresarEnvioLetraDetalle($tabla, $datos)
	{

		$stmt = Conexion::conectar()->prepare("INSERT INTO $tabla(num_cta,codigo) VALUES (:num_cta,:codigo)");

		$stmt->bindParam(":num_cta", $datos["num_cta"], PDO::PARAM_STR);
		$stmt->bindParam(":codigo", $datos["codigo"], PDO::PARAM_STR);

		if ($stmt->execute()) {

			return "ok";
		} else {

			return "error";
		}

		$stmt->close();
		$stmt = null;
	}

	static public function mdlMostrarReporteCobrar($tabla, $orden1, $orden2, $tip_doc, $cli, $vend, $banco, $fin)
	{

		if ($orden1 == 'tipo' && $orden2 == 'ordNumCuenta') {
			$stmt = Conexion::conectar()->prepare("SELECT 
			cc.tipo_doc,
			cc.num_cta,
			cc.banco,
			cc.fecha,
			cc.vendedor,
			cc.fecha_ven,
			cc.cliente,
			c.nombre,
			FORMAT(cc.saldo, 2) AS saldo,
			CASE
			  WHEN cc.protesta = 0 
			  THEN '' 
			  ELSE 'Si' 
			END AS protesta,
			IFNULL(cc.num_unico, '') AS num_unico 
		  FROM
			cuenta_ctejf cc 
			LEFT JOIN clientesjf c 
			  ON cc.cliente = c.codigo 
		  WHERE cc.tip_mov = '+' 
			AND cc.estado = 'Pendiente' 
		  ORDER BY cc.tipo_doc,
			cc.num_cta ");

			$stmt->execute();

			return $stmt->fetchAll();
		} else if ($orden1 == 'tipo' && $orden2 == 'ordVencimiento') {

			$stmt = Conexion::conectar()->prepare("SELECT 
			cc.tipo_doc,
			cc.num_cta,
			cc.banco,
			cc.fecha,
			cc.vendedor,
			cc.fecha_ven,
			cc.cliente,
			c.nombre,
			FORMAT(cc.saldo, 2) AS saldo,
			CASE
			  WHEN cc.protesta = 0 
			  THEN '' 
			  ELSE 'Si' 
			END AS protesta,
			IFNULL(cc.num_unico, '') AS num_unico 
		  FROM
			cuenta_ctejf cc 
			LEFT JOIN clientesjf c 
			  ON cc.cliente = c.codigo 
		  WHERE cc.tip_mov = '+' 
			AND cc.estado = 'Pendiente' 
		  ORDER BY cc.tipo_doc,cc.num_cta,
			cc.fecha_ven ");

			$stmt->execute();

			return $stmt->fetchAll();
		} else if ($orden1 == 'tipo' && $orden2 == 'ordCliente') {
			$stmt = Conexion::conectar()->prepare("SELECT 
			cc.tipo_doc,
			cc.num_cta,
			cc.banco,
			cc.fecha,
			cc.vendedor,
			cc.fecha_ven,
			cc.cliente,
			c.nombre,
			FORMAT(cc.saldo, 2) AS saldo,
			CASE
			  WHEN cc.protesta = 0 
			  THEN '' 
			  ELSE 'Si' 
			END AS protesta,
			IFNULL(cc.num_unico, '') AS num_unico 
		  FROM
			cuenta_ctejf cc 
			LEFT JOIN clientesjf c 
			  ON cc.cliente = c.codigo 
		  WHERE cc.tip_mov = '+' 
			AND cc.estado = 'Pendiente' 
		  ORDER BY cc.tipo_doc,cc.num_cta,
			cc.cliente ");

			$stmt->execute();

			return $stmt->fetchAll();
		} else if ($orden1 == 'cliente' && $orden2 == 'ordNumCuenta' && $cli != 'todo') {
			$stmt = Conexion::conectar()->prepare("SELECT 
			cc.tipo_doc,
			cc.num_cta,
			cc.fecha,
			cc.fecha_ven,
			cc.vendedor,
			cc.doc_origen,
			FORMAT(cc.saldo,2) AS saldo
		  FROM
			cuenta_ctejf cc 
			LEFT JOIN clientesjf c 
			  ON cc.cliente = c.codigo 
		  WHERE cc.tip_mov = '+' 
			AND cc.estado = 'Pendiente' 
			AND cc.cliente = '" . $cli . "' 
		  ORDER BY cc.tipo_doc ");

			$stmt->execute();

			return $stmt->fetchAll();
		} else if ($orden1 == 'cliente' && $orden2 == 'ordNumCuenta' && $cli == 'todo') {
			$stmt = Conexion::conectar()->prepare("SELECT 
			cc.cliente,
			cc.tipo_doc,
			cc.num_cta,
			cc.fecha,
			cc.fecha_ven,
			cc.vendedor,
			cc.doc_origen,
			CASE
			  WHEN cc.estado_doc = '01' 
			  THEN 'COBRANZA' 
			  ELSE '' 
			END AS estado_doc,
			CASE
			  WHEN cc.banco = '02' 
			  THEN 'BCP' 
			  ELSE '' 
			END AS banco,
			cc.num_unico,
			FORMAT(cc.saldo, 2) AS saldo,
			CASE
			  WHEN cc.protesta = 0 
			  THEN '' 
			  ELSE 'Si' 
			END AS protesta 
		  FROM
			cuenta_ctejf cc 
			LEFT JOIN clientesjf c 
			  ON cc.cliente = c.codigo 
		  WHERE cc.tip_mov = '+' 
			AND cc.estado = 'Pendiente' 
		  UNION
		  SELECT 
			cc.cliente,
			c.nombre,
			'',
			'0000-00-00',
			'',
			'',
			'',
			'',
			'',
			'',
			'',
			'' 
		  FROM
			cuenta_ctejf cc 
			LEFT JOIN clientesjf c 
			  ON cc.cliente = c.codigo 
		  WHERE cc.tip_mov = '+' 
			AND cc.estado = 'Pendiente' 
		  GROUP BY cc.cliente 
		  UNION
		  SELECT 
			cc.cliente,
			'',
			'',
			'9999-12-31',
			'',
			'',
			'',
			'',
			'',
			'Total Cliente',
			FORMAT(SUM(cc.saldo), 2) AS saldo_total,
			'' 
		  FROM
			cuenta_ctejf cc 
			LEFT JOIN clientesjf c 
			  ON cc.cliente = c.codigo 
		  WHERE cc.tip_mov = '+' 
			AND cc.estado = 'Pendiente' 
		  GROUP BY cc.cliente 
		  ORDER BY cliente,
			fecha ");

			$stmt->execute();

			return $stmt->fetchAll();
		} else if ($orden1 == 'vendedor' && $orden2 == 'ordNumCuenta' && $vend == 'todo') {
			$stmt = Conexion::conectar()->prepare("SELECT 
			cc.tipo_doc,
			cc.num_cta,
			cc.banco,
			cc.fecha,
			cc.vendedor,
			cc.fecha_ven,
			cc.cliente,
			c.nombre,
			FORMAT(cc.saldo, 2) AS saldo,
			CASE
			  WHEN cc.protesta = 0 
			  THEN '' 
			  ELSE 'Si' 
			END AS protesta,
			IFNULL(cc.num_unico, '') AS num_unico 
		  FROM
			cuenta_ctejf cc 
			LEFT JOIN clientesjf c 
			  ON cc.cliente = c.codigo 
		  WHERE cc.tip_mov = '+' 
			AND cc.estado = 'Pendiente' 
		  ORDER BY cc.tipo_doc,
			cc.num_cta ");

			$stmt->execute();

			return $stmt->fetchAll();
		} else if ($orden1 == 'vendedor' && $orden2 == 'ordNumCuenta' && $vend != 'todo') {
			$stmt = Conexion::conectar()->prepare("SELECT 
			cc.tipo_doc,
			cc.num_cta,
			cc.fecha,
			cc.fecha_ven,
			cc.doc_origen,
			cc.cliente,
			c.nombre,
			FORMAT(cc.saldo, 2) AS saldo,
			CASE
			  WHEN cc.protesta = 0 
			  THEN '' 
			  ELSE 'Si' 
			END AS protesta 
		  FROM
			cuenta_ctejf cc 
			LEFT JOIN clientesjf c 
			  ON cc.cliente = c.codigo 
			LEFT JOIN 
			  (SELECT 
				* 
			  FROM
				maestrajf 
			  WHERE tipo_dato = 'tvend') v 
			  ON cc.vendedor = v.codigo 
		  WHERE cc.tip_mov = '+' 
			AND cc.estado = 'Pendiente' 
			AND cc.vendedor = '" . $vend . "' 
		  ORDER BY cc.tipo_doc");

			$stmt->execute();

			return $stmt->fetchAll();
		} else if ($orden1 == 'vendedor' && $orden2 == 'ordCliente' && $vend == 'todo') {
			$stmt = Conexion::conectar()->prepare("SELECT 
			cc.tipo_doc,
			cc.num_cta,
			cc.banco,
			cc.fecha,
			cc.vendedor,
			cc.fecha_ven,
			cc.cliente,
			c.nombre,
			FORMAT(cc.saldo, 2) AS saldo,
			CASE
			  WHEN cc.protesta = 0 
			  THEN '' 
			  ELSE 'Si' 
			END AS protesta,
			IFNULL(cc.num_unico, '') AS num_unico 
		  FROM
			cuenta_ctejf cc 
			LEFT JOIN clientesjf c 
			  ON cc.cliente = c.codigo 
		  WHERE cc.tip_mov = '+' 
			AND cc.estado = 'Pendiente' 
		  ORDER BY cc.tipo_doc,
		  cc.cliente,
		  cc.num_cta
			 ");

			$stmt->execute();

			return $stmt->fetchAll();
		} else if ($orden1 == 'fecha_ven' && $orden2 == 'ordNumCuenta') {
			$stmt = Conexion::conectar()->prepare("SELECT 
			cc.tipo_doc,
			cc.num_cta,
			cc.banco,
			cc.fecha,
			cc.vendedor,
			cc.fecha_ven,
			cc.cliente,
			c.nombre,
			FORMAT(cc.saldo, 2) AS saldo,
			CASE
			  WHEN cc.protesta = 0 
			  THEN '' 
			  ELSE 'Si' 
			END AS protesta,
			IFNULL(cc.num_unico, '') AS num_unico 
		  FROM
			cuenta_ctejf cc 
			LEFT JOIN clientesjf c 
			  ON cc.cliente = c.codigo 
		  WHERE cc.tip_mov = '+' 
			AND cc.estado = 'Pendiente' 
		  ORDER BY cc.tipo_doc,
			cc.fecha_ven,
			cc.num_cta ");

			$stmt->execute();

			return $stmt->fetchAll();
		} else if ($orden1 == 'fecha_ven' && $orden2 == 'ordVencimiento') {

			$stmt = Conexion::conectar()->prepare("SELECT 
			cc.tipo_doc,
			cc.num_cta,
			cc.fecha,
			cc.fecha_ven,
			cc.vendedor,
			cc.cliente,
			c.nombre,
			cc.saldo,
			cc.num_unico,
			CASE
			  WHEN cc.protesta = '1' 
			  THEN 'SI' 
			  ELSE '' 
			END AS protesta, 
			CASE
			  WHEN cc.banco = '02' 
			  THEN 'BCP' 
			  ELSE '' 
			END AS banco 
		  FROM
			cuenta_ctejf cc 
			LEFT JOIN clientesjf c 
			  ON cc.cliente = c.codigo 
		  WHERE cc.tip_mov = '+' 
			AND cc.estado = 'PENDIENTE' 
			AND cc.tipo_doc = '85'
			AND cc.banco = '02' 
			AND cc.fecha_ven <= '$fin' 
			ORDER BY cc.fecha_ven DESC");

			$stmt->bindParam(":tip_doc", $tip_doc, PDO::PARAM_STR);
			$stmt->bindParam(":banco", $banco, PDO::PARAM_STR);
			$stmt->bindParam(":fin", $fin, PDO::PARAM_STR);


			$stmt->execute();

			return $stmt->fetchAll();
		}


		$stmt->close();

		$stmt = null;
	}

	static public function mdlMostrarReporteVencidos($tabla, $orden1, $orden2, $tip_doc, $cli, $vend, $banco)
	{

		if ($orden1 == 'tipo' && $orden2 == 'ordNumCuenta') {
			$stmt = Conexion::conectar()->prepare("SELECT 
			cc.tipo_doc,
			cc.num_cta,
			cc.banco,
			cc.fecha,
			cc.vendedor,
			cc.fecha_ven,
			cc.cliente,
			c.nombre,
			FORMAT(cc.saldo, 2) AS saldo,
			CASE
			  WHEN cc.protesta = 0 
			  THEN '' 
			  ELSE 'Si' 
			END AS protesta,
			IFNULL(cc.num_unico, '') AS num_unico 
		  FROM
			cuenta_ctejf cc 
			LEFT JOIN clientesjf c 
			  ON cc.cliente = c.codigo 
		  WHERE cc.tip_mov = '+' 
			AND cc.estado = 'Pendiente' 
		    AND NOW()>cc.fecha_ven  
		  ORDER BY cc.tipo_doc,
			cc.num_cta ");

			$stmt->execute();

			return $stmt->fetchAll();
		} else if ($orden1 == 'tipo' && $orden2 == 'ordVencimiento') {

			$stmt = Conexion::conectar()->prepare("SELECT 
			cc.tipo_doc,
			cc.num_cta,
			cc.banco,
			cc.fecha,
			cc.vendedor,
			cc.fecha_ven,
			cc.cliente,
			c.nombre,
			FORMAT(cc.saldo, 2) AS saldo,
			CASE
			  WHEN cc.protesta = 0 
			  THEN '' 
			  ELSE 'Si' 
			END AS protesta,
			IFNULL(cc.num_unico, '') AS num_unico 
		  FROM
			cuenta_ctejf cc 
			LEFT JOIN clientesjf c 
			  ON cc.cliente = c.codigo 
		  WHERE cc.tip_mov = '+' 
			AND cc.estado = 'Pendiente' 
		    AND NOW()>cc.fecha_ven 
		  ORDER BY cc.tipo_doc,cc.num_cta,
			cc.fecha_ven ");

			$stmt->execute();

			return $stmt->fetchAll();
		} else if ($orden1 == 'tipo' && $orden2 == 'ordCliente') {
			$stmt = Conexion::conectar()->prepare("SELECT 
			cc.tipo_doc,
			cc.num_cta,
			cc.banco,
			cc.fecha,
			cc.vendedor,
			cc.fecha_ven,
			cc.cliente,
			c.nombre,
			FORMAT(cc.saldo, 2) AS saldo,
			CASE
			  WHEN cc.protesta = 0 
			  THEN '' 
			  ELSE 'Si' 
			END AS protesta,
			IFNULL(cc.num_unico, '') AS num_unico 
		  FROM
			cuenta_ctejf cc 
			LEFT JOIN clientesjf c 
			  ON cc.cliente = c.codigo 
		  WHERE cc.tip_mov = '+' 
			AND cc.estado = 'Pendiente' 
		    AND NOW()>cc.fecha_ven  
		  ORDER BY cc.tipo_doc,cc.num_cta,
			cc.cliente ");

			$stmt->execute();

			return $stmt->fetchAll();
		} else if ($orden1 == 'cliente' && $orden2 == 'ordNumCuenta' && $cli != 'todo') {
			$stmt = Conexion::conectar()->prepare("SELECT 
			cc.tipo_doc,
			cc.num_cta,
			cc.fecha,
			cc.fecha_ven,
			cc.vendedor,
			cc.doc_origen,
			FORMAT(cc.saldo,2) AS saldo
		  FROM
			cuenta_ctejf cc 
			LEFT JOIN clientesjf c 
			  ON cc.cliente = c.codigo 
		  WHERE cc.tip_mov = '+' 
			AND cc.estado = 'Pendiente' 
			AND cc.cliente = '" . $cli . "' 
		    AND NOW()>cc.fecha_ven  
		  ORDER BY cc.tipo_doc ");

			$stmt->execute();

			return $stmt->fetchAll();
		} else if ($orden1 == 'cliente' && $orden2 == 'ordNumCuenta' && $cli == 'todo') {
			$stmt = Conexion::conectar()->prepare("SELECT 
			cc.cliente,
			cc.tipo_doc,
			cc.num_cta,
			cc.fecha,
			cc.fecha_ven,
			cc.vendedor,
			cc.doc_origen,
			CASE
			  WHEN cc.estado_doc = '01' 
			  THEN 'COBRANZA' 
			  ELSE '' 
			END AS estado_doc,
			CASE
			  WHEN cc.banco = '02' 
			  THEN 'BCP' 
			  ELSE '' 
			END AS banco,
			cc.num_unico,
			FORMAT(cc.saldo, 2) AS saldo,
			CASE
			  WHEN cc.protesta = 0 
			  THEN '' 
			  ELSE 'Si' 
			END AS protesta 
		  FROM
			cuenta_ctejf cc 
			LEFT JOIN clientesjf c 
			  ON cc.cliente = c.codigo 
		  WHERE cc.tip_mov = '+' 
			AND cc.estado = 'Pendiente' 
		  UNION
		  SELECT 
			cc.cliente,
			c.nombre,
			'',
			'0000-00-00',
			'',
			'',
			'',
			'',
			'',
			'',
			'',
			'' 
		  FROM
			cuenta_ctejf cc 
			LEFT JOIN clientesjf c 
			  ON cc.cliente = c.codigo 
		  WHERE cc.tip_mov = '+' 
			AND cc.estado = 'Pendiente' 
		  GROUP BY cc.cliente 
		  UNION
		  SELECT 
			cc.cliente,
			'',
			'',
			'9999-12-31',
			'',
			'',
			'',
			'',
			'',
			'Total Cliente',
			FORMAT(SUM(cc.saldo), 2) AS saldo_total,
			'' 
		  FROM
			cuenta_ctejf cc 
			LEFT JOIN clientesjf c 
			  ON cc.cliente = c.codigo 
		  WHERE cc.tip_mov = '+' 
			AND cc.estado = 'Pendiente' 
		    AND NOW()>cc.fecha_ven  
		  GROUP BY cc.cliente 
		  ORDER BY cliente,
			fecha ");

			$stmt->execute();

			return $stmt->fetchAll();
		} else if ($orden1 == 'vendedor' && $orden2 == 'ordNumCuenta' && $vend == 'todo') {
			$stmt = Conexion::conectar()->prepare("SELECT 
			cc.tipo_doc,
			cc.num_cta,
			cc.banco,
			cc.fecha,
			cc.vendedor,
			cc.fecha_ven,
			cc.cliente,
			c.nombre,
			FORMAT(cc.saldo, 2) AS saldo,
			CASE
			  WHEN cc.protesta = 0 
			  THEN '' 
			  ELSE 'Si' 
			END AS protesta,
			IFNULL(cc.num_unico, '') AS num_unico 
		  FROM
			cuenta_ctejf cc 
			LEFT JOIN clientesjf c 
			  ON cc.cliente = c.codigo 
		  WHERE cc.tip_mov = '+' 
			AND cc.estado = 'Pendiente'  
		    AND NOW()>cc.fecha_ven 
		  ORDER BY cc.tipo_doc,
			cc.num_cta ");

			$stmt->execute();

			return $stmt->fetchAll();
		} else if ($orden1 == 'vendedor' && $orden2 == 'ordNumCuenta' && $vend != 'todo') {
			$stmt = Conexion::conectar()->prepare("SELECT 
			cc.tipo_doc,
			cc.num_cta,
			cc.fecha,
			cc.fecha_ven,
			cc.doc_origen,
			cc.cliente,
			c.nombre,
			FORMAT(cc.saldo, 2) AS saldo,
			CASE
			  WHEN cc.protesta = 0 
			  THEN '' 
			  ELSE 'Si' 
			END AS protesta 
		  FROM
			cuenta_ctejf cc 
			LEFT JOIN clientesjf c 
			  ON cc.cliente = c.codigo 
			LEFT JOIN 
			  (SELECT 
				* 
			  FROM
				maestrajf 
			  WHERE tipo_dato = 'tvend') v 
			  ON cc.vendedor = v.codigo 
		  WHERE cc.tip_mov = '+' 
			AND cc.estado = 'Pendiente' 
			AND cc.vendedor = '" . $vend . "'  
		    AND NOW()>cc.fecha_ven 
		  ORDER BY cc.tipo_doc");

			$stmt->execute();

			return $stmt->fetchAll();
		} else if ($orden1 == 'vendedor' && $orden2 == 'ordCliente' && $vend == 'todo') {
			$stmt = Conexion::conectar()->prepare("SELECT 
			cc.tipo_doc,
			cc.num_cta,
			cc.banco,
			cc.fecha,
			cc.vendedor,
			cc.fecha_ven,
			cc.cliente,
			c.nombre,
			FORMAT(cc.saldo, 2) AS saldo,
			CASE
			  WHEN cc.protesta = 0 
			  THEN '' 
			  ELSE 'Si' 
			END AS protesta,
			IFNULL(cc.num_unico, '') AS num_unico 
		  FROM
			cuenta_ctejf cc 
			LEFT JOIN clientesjf c 
			  ON cc.cliente = c.codigo 
		  WHERE cc.tip_mov = '+' 
			AND cc.estado = 'Pendiente' 
		    AND NOW()>cc.fecha_ven  
		  ORDER BY cc.tipo_doc,
		  cc.cliente,
		  cc.num_cta
			 ");

			$stmt->execute();

			return $stmt->fetchAll();
		} else if ($orden1 == 'fecha_ven' && $orden2 == 'ordNumCuenta') {
			$stmt = Conexion::conectar()->prepare("SELECT 
			cc.tipo_doc,
			cc.num_cta,
			cc.banco,
			cc.fecha,
			cc.vendedor,
			cc.fecha_ven,
			cc.cliente,
			c.nombre,
			FORMAT(cc.saldo, 2) AS saldo,
			CASE
			  WHEN cc.protesta = 0 
			  THEN '' 
			  ELSE 'Si' 
			END AS protesta,
			IFNULL(cc.num_unico, '') AS num_unico 
		  FROM
			cuenta_ctejf cc 
			LEFT JOIN clientesjf c 
			  ON cc.cliente = c.codigo 
		  WHERE cc.tip_mov = '+' 
			AND cc.estado = 'Pendiente' 
		    AND NOW()>cc.fecha_ven  
		  ORDER BY cc.tipo_doc,
			cc.fecha_ven,
			cc.num_cta ");

			$stmt->execute();

			return $stmt->fetchAll();
		}

		$stmt->close();

		$stmt = null;
	}

	static public function mdlMostrarSaldosFecha($fin)
	{


		$stmt = Conexion::conectar()->prepare("SELECT 
						cc.cliente,
						c.nombre,
						cc.tipo_doc,
						cc.num_cta,
						cc.cod_pago,
						cc.doc_origen,
						cc.fecha,
						cc.fecha_ven,
						cc.monto,
						cc.saldo AS saldo_actual,
						cc.estado,
						IFNULL(cc1.monto, 0) AS pagos_fecha,
						(cc.monto - IFNULL(cc1.monto, 0)) saldo,
						cc.vendedor,
						cc.protesta,
						cc.num_unico,
						cc.banco 
					FROM
						cuenta_ctejf cc 
						LEFT JOIN 
						(SELECT 
							cc.num_cta,
							SUM(cc.monto) AS monto 
						FROM
							cuenta_ctejf cc 
						WHERE  cc.tip_mov = '-' 
							AND cc.fecha <= '$fin' 
						GROUP BY cc.num_cta) AS cc1 
						ON cc.num_cta = cc1.num_cta 
						LEFT JOIN clientesjf c 
						ON cc.cliente = c.codigo 
					WHERE cc.tip_mov = '+' 
						AND cc.fecha <= '$fin' 
						AND (cc.monto - IFNULL(cc1.monto, 0)) > 0");

		$stmt->execute();

		return $stmt->fetchAll();



		$stmt->close();

		$stmt = null;
	}

	static public function mdlMostrarReporteNoVencidos($tabla, $orden1, $orden2, $tip_doc, $cli, $vend, $banco)
	{

		if ($orden1 == 'tipo' && $orden2 == 'ordNumCuenta') {
			$stmt = Conexion::conectar()->prepare("SELECT 
			cc.tipo_doc,
			cc.num_cta,
			cc.banco,
			cc.fecha,
			cc.vendedor,
			cc.fecha_ven,
			cc.cliente,
			c.nombre,
			FORMAT(cc.saldo, 2) AS saldo,
			CASE
			  WHEN cc.protesta = 0 
			  THEN '' 
			  ELSE 'Si' 
			END AS protesta,
			IFNULL(cc.num_unico, '') AS num_unico 
		  FROM
			cuenta_ctejf cc 
			LEFT JOIN clientesjf c 
			  ON cc.cliente = c.codigo 
		  WHERE cc.tip_mov = '+' 
			AND cc.estado = 'Pendiente' 
		    AND NOW()<cc.fecha_ven  
		  ORDER BY cc.tipo_doc,
			cc.num_cta ");

			$stmt->execute();

			return $stmt->fetchAll();
		} else if ($orden1 == 'tipo' && $orden2 == 'ordVencimiento') {

			$stmt = Conexion::conectar()->prepare("SELECT 
			cc.tipo_doc,
			cc.num_cta,
			cc.banco,
			cc.fecha,
			cc.vendedor,
			cc.fecha_ven,
			cc.cliente,
			c.nombre,
			FORMAT(cc.saldo, 2) AS saldo,
			CASE
			  WHEN cc.protesta = 0 
			  THEN '' 
			  ELSE 'Si' 
			END AS protesta,
			IFNULL(cc.num_unico, '') AS num_unico 
		  FROM
			cuenta_ctejf cc 
			LEFT JOIN clientesjf c 
			  ON cc.cliente = c.codigo 
		  WHERE cc.tip_mov = '+' 
			AND cc.estado = 'Pendiente' 
		    AND NOW()<cc.fecha_ven 
		  ORDER BY cc.tipo_doc,cc.num_cta,
			cc.fecha_ven ");

			$stmt->execute();

			return $stmt->fetchAll();
		} else if ($orden1 == 'tipo' && $orden2 == 'ordCliente') {
			$stmt = Conexion::conectar()->prepare("SELECT 
			cc.tipo_doc,
			cc.num_cta,
			cc.banco,
			cc.fecha,
			cc.vendedor,
			cc.fecha_ven,
			cc.cliente,
			c.nombre,
			FORMAT(cc.saldo, 2) AS saldo,
			CASE
			  WHEN cc.protesta = 0 
			  THEN '' 
			  ELSE 'Si' 
			END AS protesta,
			IFNULL(cc.num_unico, '') AS num_unico 
		  FROM
			cuenta_ctejf cc 
			LEFT JOIN clientesjf c 
			  ON cc.cliente = c.codigo 
		  WHERE cc.tip_mov = '+' 
			AND cc.estado = 'Pendiente' 
		    AND NOW()<cc.fecha_ven  
		  ORDER BY cc.tipo_doc,cc.num_cta,
			cc.cliente ");

			$stmt->execute();

			return $stmt->fetchAll();
		} else if ($orden1 == 'cliente' && $orden2 == 'ordNumCuenta' && $cli != 'todo') {
			$stmt = Conexion::conectar()->prepare("SELECT 
			cc.tipo_doc,
			cc.num_cta,
			cc.fecha,
			cc.fecha_ven,
			cc.vendedor,
			cc.doc_origen,
			FORMAT(cc.saldo,2) AS saldo
		  FROM
			cuenta_ctejf cc 
			LEFT JOIN clientesjf c 
			  ON cc.cliente = c.codigo 
		  WHERE cc.tip_mov = '+' 
			AND cc.estado = 'Pendiente' 
			AND cc.cliente = '" . $cli . "' 
		    AND NOW()<cc.fecha_ven  
		  ORDER BY cc.tipo_doc ");

			$stmt->execute();

			return $stmt->fetchAll();
		} else if ($orden1 == 'cliente' && $orden2 == 'ordNumCuenta' && $cli == 'todo') {
			$stmt = Conexion::conectar()->prepare("SELECT 
			cc.cliente,
			cc.tipo_doc,
			cc.num_cta,
			cc.fecha,
			cc.fecha_ven,
			cc.vendedor,
			cc.doc_origen,
			CASE
			  WHEN cc.estado_doc = '01' 
			  THEN 'COBRANZA' 
			  ELSE '' 
			END AS estado_doc,
			CASE
			  WHEN cc.banco = '02' 
			  THEN 'BCP' 
			  ELSE '' 
			END AS banco,
			cc.num_unico,
			FORMAT(cc.saldo, 2) AS saldo,
			CASE
			  WHEN cc.protesta = 0 
			  THEN '' 
			  ELSE 'Si' 
			END AS protesta 
		  FROM
			cuenta_ctejf cc 
			LEFT JOIN clientesjf c 
			  ON cc.cliente = c.codigo 
		  WHERE cc.tip_mov = '+' 
			AND cc.estado = 'Pendiente' 
		  UNION
		  SELECT 
			cc.cliente,
			c.nombre,
			'',
			'0000-00-00',
			'',
			'',
			'',
			'',
			'',
			'',
			'',
			'' 
		  FROM
			cuenta_ctejf cc 
			LEFT JOIN clientesjf c 
			  ON cc.cliente = c.codigo 
		  WHERE cc.tip_mov = '+' 
			AND cc.estado = 'Pendiente' 
		  GROUP BY cc.cliente 
		  UNION
		  SELECT 
			cc.cliente,
			'',
			'',
			'9999-12-31',
			'',
			'',
			'',
			'',
			'',
			'Total Cliente',
			FORMAT(SUM(cc.saldo), 2) AS saldo_total,
			'' 
		  FROM
			cuenta_ctejf cc 
			LEFT JOIN clientesjf c 
			  ON cc.cliente = c.codigo 
		  WHERE cc.tip_mov = '+' 
			AND cc.estado = 'Pendiente' 
		    AND NOW()<cc.fecha_ven  
		  GROUP BY cc.cliente 
		  ORDER BY cliente,
			fecha ");

			$stmt->execute();

			return $stmt->fetchAll();
		} else if ($orden1 == 'vendedor' && $orden2 == 'ordNumCuenta' && $vend == 'todo') {
			$stmt = Conexion::conectar()->prepare("SELECT 
			cc.tipo_doc,
			cc.num_cta,
			cc.banco,
			cc.fecha,
			cc.vendedor,
			cc.fecha_ven,
			cc.cliente,
			c.nombre,
			FORMAT(cc.saldo, 2) AS saldo,
			CASE
			  WHEN cc.protesta = 0 
			  THEN '' 
			  ELSE 'Si' 
			END AS protesta,
			IFNULL(cc.num_unico, '') AS num_unico 
		  FROM
			cuenta_ctejf cc 
			LEFT JOIN clientesjf c 
			  ON cc.cliente = c.codigo 
		  WHERE cc.tip_mov = '+' 
			AND cc.estado = 'Pendiente'  
		    AND NOW()<cc.fecha_ven 
		  ORDER BY cc.tipo_doc,
			cc.num_cta ");

			$stmt->execute();

			return $stmt->fetchAll();
		} else if ($orden1 == 'vendedor' && $orden2 == 'ordNumCuenta' && $vend != 'todo') {
			$stmt = Conexion::conectar()->prepare("SELECT 
			cc.tipo_doc,
			cc.num_cta,
			cc.fecha,
			cc.fecha_ven,
			cc.doc_origen,
			cc.cliente,
			c.nombre,
			FORMAT(cc.saldo, 2) AS saldo,
			CASE
			  WHEN cc.protesta = 0 
			  THEN '' 
			  ELSE 'Si' 
			END AS protesta 
		  FROM
			cuenta_ctejf cc 
			LEFT JOIN clientesjf c 
			  ON cc.cliente = c.codigo 
			LEFT JOIN 
			  (SELECT 
				* 
			  FROM
				maestrajf 
			  WHERE tipo_dato = 'tvend') v 
			  ON cc.vendedor = v.codigo 
		  WHERE cc.tip_mov = '+' 
			AND cc.estado = 'Pendiente' 
			AND cc.vendedor = '" . $vend . "'  
		    AND NOW()<cc.fecha_ven 
		  ORDER BY cc.tipo_doc");

			$stmt->execute();

			return $stmt->fetchAll();
		} else if ($orden1 == 'vendedor' && $orden2 == 'ordCliente' && $vend == 'todo') {
			$stmt = Conexion::conectar()->prepare("SELECT 
			cc.tipo_doc,
			cc.num_cta,
			cc.banco,
			cc.fecha,
			cc.vendedor,
			cc.fecha_ven,
			cc.cliente,
			c.nombre,
			FORMAT(cc.saldo, 2) AS saldo,
			CASE
			  WHEN cc.protesta = 0 
			  THEN '' 
			  ELSE 'Si' 
			END AS protesta,
			IFNULL(cc.num_unico, '') AS num_unico 
		  FROM
			cuenta_ctejf cc 
			LEFT JOIN clientesjf c 
			  ON cc.cliente = c.codigo 
		  WHERE cc.tip_mov = '+' 
			AND cc.estado = 'Pendiente' 
		    AND NOW()<cc.fecha_ven  
		  ORDER BY cc.tipo_doc,
		  cc.cliente,
		  cc.num_cta
			 ");

			$stmt->execute();

			return $stmt->fetchAll();
		} else if ($orden1 == 'fecha_ven' && $orden2 == 'ordNumCuenta') {
			$stmt = Conexion::conectar()->prepare("SELECT 
			cc.tipo_doc,
			cc.num_cta,
			cc.banco,
			cc.fecha,
			cc.vendedor,
			cc.fecha_ven,
			cc.cliente,
			c.nombre,
			FORMAT(cc.saldo, 2) AS saldo,
			CASE
			  WHEN cc.protesta = 0 
			  THEN '' 
			  ELSE 'Si' 
			END AS protesta,
			IFNULL(cc.num_unico, '') AS num_unico 
		  FROM
			cuenta_ctejf cc 
			LEFT JOIN clientesjf c 
			  ON cc.cliente = c.codigo 
		  WHERE cc.tip_mov = '+' 
			AND cc.estado = 'Pendiente' 
		    AND NOW()<cc.fecha_ven  
		  ORDER BY cc.tipo_doc,
			cc.fecha_ven,
			cc.num_cta ");

			$stmt->execute();

			return $stmt->fetchAll();
		}

		$stmt->close();

		$stmt = null;
	}

	static public function mdlMostrarReporteProtestados($tabla, $orden1, $orden2, $tip_doc, $cli, $vend, $banco)
	{

		if ($orden1 == 'tipo' && $orden2 == 'ordNumCuenta') {

			$stmt = Conexion::conectar()->prepare("SELECT 
							cc.tipo_doc,
							cc.num_cta,
							cc.banco,
							cc.fecha,
							cc.vendedor,
							cc.fecha_ven,
							cc.cliente,
							c.nombre,
							FORMAT(cc.saldo, 2) AS saldo,
							CASE
							WHEN cc.protesta = 0 
							THEN '' 
							ELSE 'Si' 
							END AS protesta,
							IFNULL(cc.num_unico, '') AS num_unico 
						FROM
							cuenta_ctejf cc 
							LEFT JOIN clientesjf c 
							ON cc.cliente = c.codigo 
						WHERE cc.tip_mov = '+' 
							AND cc.estado = 'Pendiente' 
							AND cc.protesta = 1  
						ORDER BY cc.tipo_doc,
							cc.num_cta ");

			$stmt->execute();

			return $stmt->fetchAll();
		} else if ($orden1 == 'tipo' && $orden2 == 'ordVencimiento') {

			$stmt = Conexion::conectar()->prepare("SELECT 
							cc.tipo_doc,
							cc.num_cta,
							cc.banco,
							cc.fecha,
							cc.vendedor,
							cc.fecha_ven,
							cc.cliente,
							c.nombre,
							FORMAT(cc.saldo, 2) AS saldo,
							CASE
							WHEN cc.protesta = 0 
							THEN '' 
							ELSE 'Si' 
							END AS protesta,
							IFNULL(cc.num_unico, '') AS num_unico 
						FROM
							cuenta_ctejf cc 
							LEFT JOIN clientesjf c 
							ON cc.cliente = c.codigo 
						WHERE cc.tip_mov = '+' 
							AND cc.estado = 'Pendiente' 
							AND cc.protesta = 1   
						ORDER BY cc.tipo_doc,cc.num_cta,
							cc.fecha_ven ");

			$stmt->execute();

			return $stmt->fetchAll();
		} else if ($orden1 == 'tipo' && $orden2 == 'ordCliente') {
			$stmt = Conexion::conectar()->prepare("SELECT 
							cc.tipo_doc,
							cc.num_cta,
							cc.banco,
							cc.fecha,
							cc.vendedor,
							cc.fecha_ven,
							cc.cliente,
							c.nombre,
							FORMAT(cc.saldo, 2) AS saldo,
							CASE
							WHEN cc.protesta = 0 
							THEN '' 
							ELSE 'Si' 
							END AS protesta,
							IFNULL(cc.num_unico, '') AS num_unico 
						FROM
							cuenta_ctejf cc 
							LEFT JOIN clientesjf c 
							ON cc.cliente = c.codigo 
						WHERE cc.tip_mov = '+' 
							AND cc.estado = 'Pendiente' 
							AND cc.protesta = 1   
						ORDER BY cc.tipo_doc,cc.num_cta,
							cc.cliente ");

			$stmt->execute();

			return $stmt->fetchAll();
		} else if ($orden1 == 'cliente' && $orden2 == 'ordNumCuenta' && $cli != 'todo') {
			$stmt = Conexion::conectar()->prepare("SELECT 
							cc.tipo_doc,
							cc.num_cta,
							cc.fecha,
							cc.fecha_ven,
							cc.vendedor,
							cc.doc_origen,
							FORMAT(cc.saldo,2) AS saldo
						FROM
							cuenta_ctejf cc 
							LEFT JOIN clientesjf c 
							ON cc.cliente = c.codigo 
						WHERE cc.tip_mov = '+' 
							AND cc.estado = 'Pendiente' 
							AND cc.cliente = '" . $cli . "'  
							AND cc.protesta = 1  
						ORDER BY cc.tipo_doc ");

			$stmt->execute();

			return $stmt->fetchAll();
		} else if ($orden1 == 'cliente' && $orden2 == 'ordNumCuenta' && $cli == 'todo') {
			$stmt = Conexion::conectar()->prepare("SELECT 
							cc.cliente,
							cc.tipo_doc,
							cc.num_cta,
							cc.fecha,
							cc.fecha_ven,
							cc.vendedor,
							cc.doc_origen,
							CASE
							WHEN cc.estado_doc = '01' 
							THEN 'COBRANZA' 
							ELSE '' 
							END AS estado_doc,
							CASE
							WHEN cc.banco = '02' 
							THEN 'BCP' 
							ELSE '' 
							END AS banco,
							cc.num_unico,
							FORMAT(cc.saldo, 2) AS saldo,
							CASE
							WHEN cc.protesta = 0 
							THEN '' 
							ELSE 'Si' 
							END AS protesta 
						FROM
							cuenta_ctejf cc 
							LEFT JOIN clientesjf c 
							ON cc.cliente = c.codigo 
						WHERE cc.tip_mov = '+' 
							AND cc.estado = 'Pendiente' 
						UNION
						SELECT 
							cc.cliente,
							c.nombre,
							'',
							'0000-00-00',
							'',
							'',
							'',
							'',
							'',
							'',
							'',
							'' 
						FROM
							cuenta_ctejf cc 
							LEFT JOIN clientesjf c 
							ON cc.cliente = c.codigo 
						WHERE cc.tip_mov = '+' 
							AND cc.estado = 'Pendiente' 
						GROUP BY cc.cliente 
						UNION
						SELECT 
							cc.cliente,
							'',
							'',
							'9999-12-31',
							'',
							'',
							'',
							'',
							'',
							'Total Cliente',
							FORMAT(SUM(cc.saldo), 2) AS saldo_total,
							'' 
						FROM
							cuenta_ctejf cc 
							LEFT JOIN clientesjf c 
							ON cc.cliente = c.codigo 
						WHERE cc.tip_mov = '+' 
							AND cc.estado = 'Pendiente' 
							AND cc.protesta = 1   
						GROUP BY cc.cliente 
						ORDER BY cliente,
							fecha ");

			$stmt->execute();

			return $stmt->fetchAll();
		} else if ($orden1 == 'vendedor' && $orden2 == 'ordNumCuenta' && $vend == 'todo') {

			$stmt = Conexion::conectar()->prepare("SELECT 
											cc.tipo_doc,
											cc.num_cta,
											cc.banco,
											cc.fecha,
											cc.vendedor,
											cc.fecha_ven,
											cc.cliente,
											c.nombre,
											FORMAT(cc.saldo, 2) AS saldo,
											CASE
											WHEN cc.protesta = 0 
											THEN '' 
											ELSE 'Si' 
											END AS protesta,
											IFNULL(cc.num_unico, '') AS num_unico 
										FROM
											cuenta_ctejf cc 
											LEFT JOIN clientesjf c 
											ON cc.cliente = c.codigo 
										WHERE cc.tip_mov = '+' 
											AND cc.estado = 'Pendiente' 
											AND cc.protesta = 1   
										ORDER BY cc.tipo_doc,
											cc.num_cta ");

			$stmt->execute();

			return $stmt->fetchAll();
		} else if ($orden1 == 'vendedor' && $orden2 == 'ordNumCuenta' && $vend != 'todo') {
			$stmt = Conexion::conectar()->prepare("SELECT 
											cc.tipo_doc,
											cc.num_cta,
											cc.banco,
											cc.fecha,
											cc.vendedor AS vendedor,
											cc.fecha_ven,
											cc.cliente,
											c.nombre,
											FORMAT(cc.saldo, 2) AS saldo,
											CASE
											WHEN cc.protesta = 0 
											THEN '' 
											ELSE 'Si' 
											END AS protesta,
											-- IFNULL(cc.num_unico, '') AS num_unico,
											c.ubigeo,
											LEFT(
											(SELECT 
											u.nombre 
											FROM
											ubigeo u 
											WHERE u.codigo = c.ubigeo),
											12
										) AS num_unico 
										FROM
											cuenta_ctejf cc 
											LEFT JOIN clientesjf c 
											ON cc.cliente = c.codigo 
										WHERE cc.tip_mov = '+' 
											AND cc.estado = 'Pendiente' 
											AND cc.protesta = 1 
											AND cc.vendedor = '$vend'
										ORDER BY c.ubigeo,
											cc.tipo_doc,
											cc.cliente,
											cc.num_cta");

			$stmt->execute();

			return $stmt->fetchAll();
		} else if ($orden1 == 'vendedor' && $orden2 == 'ordCliente' && $vend == 'todo') {
			$stmt = Conexion::conectar()->prepare("SELECT 
							cc.tipo_doc,
							cc.num_cta,
							cc.banco,
							cc.fecha,
							cc.vendedor,
							cc.fecha_ven,
							cc.cliente,
							c.nombre,
							FORMAT(cc.saldo, 2) AS saldo,
							CASE
							WHEN cc.protesta = 0 
							THEN '' 
							ELSE 'Si' 
							END AS protesta,
							IFNULL(cc.num_unico, '') AS num_unico 
						FROM
							cuenta_ctejf cc 
							LEFT JOIN clientesjf c 
							ON cc.cliente = c.codigo 
						WHERE cc.tip_mov = '+' 
							AND cc.estado = 'Pendiente' 
							AND cc.protesta = 1   
						ORDER BY cc.tipo_doc,
						cc.cliente,
						cc.num_cta
							");

			$stmt->execute();

			return $stmt->fetchAll();
		} else if ($orden1 == 'fecha_ven' && $orden2 == 'ordNumCuenta') {
			$stmt = Conexion::conectar()->prepare("SELECT 
							cc.tipo_doc,
							cc.num_cta,
							cc.banco,
							cc.fecha,
							cc.vendedor,
							cc.fecha_ven,
							cc.cliente,
							c.nombre,
							FORMAT(cc.saldo, 2) AS saldo,
							CASE
							WHEN cc.protesta = 0 
							THEN '' 
							ELSE 'Si' 
							END AS protesta,
							IFNULL(cc.num_unico, '') AS num_unico 
						FROM
							cuenta_ctejf cc 
							LEFT JOIN clientesjf c 
							ON cc.cliente = c.codigo 
						WHERE cc.tip_mov = '+' 
							AND cc.estado = 'Pendiente' 
							AND cc.protesta = 1   
						ORDER BY cc.tipo_doc,
							cc.fecha_ven,
							cc.num_cta ");

			$stmt->execute();

			return $stmt->fetchAll();
		}

		$stmt->close();

		$stmt = null;
	}

	static public function mdlMostrarReportePagos($tabla, $orden1, $orden2, $canc, $vend, $inicio, $fin)
	{

		if ($orden1 == "fecha_pag" && $orden2 == "ordNumCuenta" && $canc == "todo") {

			$stmt = Conexion::conectar()->prepare("SELECT 
							'-1' AS tipo_doc,
							'Fecha de pago:' AS num_cta,
							cc.fecha,
							'' AS cliente,
							'' AS nombre,
							'' as vendedor,
							'' AS cod_pago,
							'' AS doc_origen,
							'' AS fact,
							'' AS letra,
							'' AS notas 
						FROM
							cuenta_ctejf cc 
							LEFT JOIN clientesjf c 
							ON cc.cliente = c.codigo 
							LEFT JOIN 
							(SELECT 
								* 
							FROM
								maestrajf 
							WHERE tipo_dato = 'tvend') v 
							ON cc.vendedor = v.codigo 
						WHERE cc.tip_mov = '-' 
							AND (
							cc.fecha BETWEEN '$inicio' 
							AND '$fin'
							) 
						GROUP BY cc.fecha 
						UNION
						SELECT 
							cc.tipo_doc,
							cc.num_cta,
							cc.fecha,
							cc.cliente,
							c.nombre,
							cc.vendedor,
							cc.cod_pago,
							cc.doc_origen,
							CASE
							WHEN cc.tipo_doc IN ('01', '03', '09', '08', '07') 
							THEN FORMAT(cc.monto, 2) 
							ELSE '' 
							END AS fact,
							CASE
							WHEN cc.tipo_doc IN ('85') 
							THEN FORMAT(cc.monto, 2) 
							ELSE '' 
							END AS letra,
							cc.notas 
						FROM
							cuenta_ctejf cc 
							LEFT JOIN clientesjf c 
							ON cc.cliente = c.codigo 
							LEFT JOIN 
							(SELECT 
								* 
							FROM
								maestrajf 
							WHERE tipo_dato = 'tvend') v 
							ON cc.vendedor = v.codigo 
						WHERE cc.tip_mov = '-' 
							AND (
							cc.fecha BETWEEN '$inicio' 
							AND '$fin'
							) 
							UNION
							SELECT 
							'999' AS tipo_doc,
							'Fecha de pago:',
							cc.fecha,
							'',
							'',
							'',
							'',
							'',
							FORMAT(
								SUM(
								CASE
									WHEN cc.tipo_doc IN ('01', '03', '09', '08', '07') 
									THEN cc.monto 
									ELSE '' 
								END
								),
								2
							) AS fact,
							FORMAT(
								SUM(
								CASE
									WHEN cc.tipo_doc IN ('85') 
									THEN cc.monto 
									ELSE '' 
								END
								),
								2
							) AS letra,
							'' AS notas 
							FROM
							cuenta_ctejf cc 
							LEFT JOIN clientesjf c 
								ON cc.cliente = c.codigo 
							LEFT JOIN 
								(SELECT 
								* 
								FROM
								maestrajf 
								WHERE tipo_dato = 'tvend') v 
								ON cc.vendedor = v.codigo 
							WHERE cc.tip_mov = '-' 
							AND (
								cc.fecha BETWEEN '$inicio' 
								AND '$fin'
							) 
							GROUP BY cc.fecha 
							ORDER BY fecha,
							tipo_doc");
		} else if ($orden1 == "vendedor" && $orden2 == "ordNumCuenta") {

			$stmt = Conexion::conectar()->prepare("SELECT 
							'-1' AS tipo_doc,
							'Vendedor: ' AS num_cta,
							CONCAT( m.codigo , ' ', m.descripcion) AS fecha,
							'' AS cliente,
							'' AS nombre,
							'' AS vendedor,
							'' AS cod_pago,
							'' AS doc_origen,
							'' AS fact,
							'' AS letra,
							'' as notas 
						FROM
							maestrajf m 
						WHERE m.tipo_dato = 'tvend' 
							AND m.codigo = '$vend' 
					UNION
						SELECT 
							cc.tipo_doc,
							cc.num_cta,
							cc.fecha,
							cc.cliente,
							c.nombre,
							cc.vendedor,
							cc.cod_pago,
							cc.doc_origen,
							CASE
							WHEN cc.tipo_doc IN ('01', '03', '09', '08', '07') 
							THEN FORMAT(cc.monto, 2) 
							ELSE '' 
							END AS fact,
							CASE
							WHEN cc.tipo_doc IN ('85') 
							THEN FORMAT(cc.monto, 2) 
							ELSE '' 
							END AS letra,
							cc.notas  
						FROM
							cuenta_ctejf cc 
							LEFT JOIN clientesjf c 
							ON cc.cliente = c.codigo 
							LEFT JOIN 
							(SELECT 
								* 
							FROM
								maestrajf 
							WHERE tipo_dato = 'tvend') v 
							ON cc.vendedor = v.codigo 
						WHERE cc.tip_mov = '-' 
							AND (
							cc.fecha BETWEEN '$inicio' 
							AND '$fin'
							) 
							AND cc.vendedor = '$vend'  
				UNION
						SELECT 
							'999' AS tipo_doc,
							'' AS num_cta,
							'9999-12-31' AS fecha,
							'' AS cliente,
							'' AS nombre,
							'' AS vendedor,
							cc.cod_pago,
							'' AS doc_origen,
							FORMAT(
							SUM(
								CASE
								WHEN cc.tipo_doc IN ('01', '03', '09', '08', '07') 
								THEN cc.monto 
								ELSE '' 
								END
							),
							2
							) AS fact,
							FORMAT(
							SUM(
								CASE
								WHEN cc.tipo_doc IN ('85') 
								THEN cc.monto 
								ELSE '' 
								END
							),
							2
							) AS letra,
							'' as notas
						FROM
							cuenta_ctejf cc 
							LEFT JOIN clientesjf c 
							ON cc.cliente = c.codigo 
							LEFT JOIN 
							(SELECT 
								* 
							FROM
								maestrajf 
							WHERE tipo_dato = 'tvend') v 
							ON cc.vendedor = v.codigo 
						WHERE cc.tip_mov = '-' 
							AND (
							cc.fecha BETWEEN '$inicio' 
							AND '$fin'
							) 
							AND cc.vendedor = '$vend' 
						GROUP BY cc.cod_pago 
						ORDER BY cod_pago,
							tipo_doc,
							fecha,
							num_cta");
		} else if ($orden1 == "fecha_pag" && $orden2 == "ordVencimiento" && $canc != "todo") {

			$stmt = Conexion::conectar()->prepare("SELECT 
					cc.tipo_doc,
					cc.num_cta,
					cc.fecha,
					cc.cliente,
					c.nombre,
					cc.vendedor,
					cc.cod_pago,
					cc.doc_origen,
					CASE
					WHEN cc.tipo_doc IN ('01', '03', '09', '08', '07') 
					THEN FORMAT(cc.monto, 2) 
					ELSE '' 
					END AS fact,
					CASE
					WHEN cc.tipo_doc IN ('85') 
					THEN FORMAT(cc.monto, 2) 
					ELSE '' 
					END AS letra,
					cc.notas 
				FROM
					cuenta_ctejf cc 
					LEFT JOIN clientesjf c 
					ON cc.cliente = c.codigo 
					LEFT JOIN 
					(SELECT 
						* 
					FROM
						maestrajf 
					WHERE tipo_dato = 'tvend') v 
					ON cc.vendedor = v.codigo 
				WHERE cc.tip_mov = '-' 
					AND (
					cc.fecha BETWEEN '$inicio' 
					AND '$fin'
					) 
					AND cc.cod_pago = '$canc' 
				UNION ALL
				SELECT 
					'-1' AS tipo_doc,
					'Fecha de pago:' AS num_cta,
					cc.fecha,
					'' AS cliente,
					'' AS nombre,
					'' AS vendedor,
					'' AS cod_pago,
					'' AS doc_origen,
					'' AS fact,
					'' AS letra,
					'' as notas 
				FROM
					cuenta_ctejf cc 
					LEFT JOIN clientesjf c 
					ON cc.cliente = c.codigo 
					LEFT JOIN 
					(SELECT 
						* 
					FROM
						maestrajf 
					WHERE tipo_dato = 'tvend') v 
					ON cc.vendedor = v.codigo 
				WHERE cc.tip_mov = '-' 
					AND (
					cc.fecha BETWEEN '$inicio' 
					AND '$fin'
					) 
					AND cc.cod_pago = '$canc' 
				GROUP BY cc.fecha 
				UNION ALL
				SELECT 
					'999' AS tipo_doc,
					'Fecha de pago:',
					cc.fecha,
					'',
					'',
					'',
					'',
					'',
					FORMAT(
					SUM(
						CASE
						WHEN cc.tipo_doc IN ('01', '03', '09', '08', '07') 
						THEN cc.monto 
						ELSE '' 
						END
					),
					2
					) AS fact,
					FORMAT(
					SUM(
						CASE
						WHEN cc.tipo_doc IN ('85') 
						THEN cc.monto 
						ELSE '' 
						END
					),
					2
					) AS letra,
					'' as notas 
				FROM
					cuenta_ctejf cc 
					LEFT JOIN clientesjf c 
					ON cc.cliente = c.codigo 
					LEFT JOIN 
					(SELECT 
						* 
					FROM
						maestrajf 
					WHERE tipo_dato = 'tvend') v 
					ON cc.vendedor = v.codigo 
				WHERE cc.tip_mov = '-' 
					AND (
					cc.fecha BETWEEN '$inicio' 
					AND '$fin'
					) 
					AND cc.cod_pago = '$canc' 
					GROUP BY cc.fecha 
					ORDER BY fecha,
					tipo_doc,
					num_cta");
		} else if ($orden1 == "fecha_pag" && $orden2 == "ordNumCuenta" && $canc != "todo") {

			$stmt = Conexion::conectar()->prepare("SELECT 
					cc.tipo_doc,
					cc.num_cta,
					cc.fecha,
					cc.cliente,
					c.nombre,
					cc.vendedor,
					cc.cod_pago,
					cc.doc_origen,
					CASE
					WHEN cc.tipo_doc IN ('01', '03', '09', '08', '07') 
					THEN FORMAT(cc.monto, 2) 
					ELSE '' 
					END AS fact,
					CASE
					WHEN cc.tipo_doc IN ('85') 
					THEN FORMAT(cc.monto, 2) 
					ELSE '' 
					END AS letra,
					cc.notas 
				FROM
					cuenta_ctejf cc 
					LEFT JOIN clientesjf c 
					ON cc.cliente = c.codigo 
					LEFT JOIN 
					(SELECT 
						* 
					FROM
						maestrajf 
					WHERE tipo_dato = 'tvend') v 
					ON cc.vendedor = v.codigo 
				WHERE cc.tip_mov = '-' 
					AND (
					cc.fecha BETWEEN '$inicio' 
					AND '$fin'
					) 
					AND cc.cod_pago = '$canc' 
				UNION ALL
				SELECT 
					'-1' AS tipo_doc,
					'Fecha de pago:' AS num_cta,
					cc.fecha,
					'' AS cliente,
					'' AS nombre,
					'' AS vendedor,
					'' AS cod_pago,
					'' AS doc_origen,
					'' AS fact,
					'' AS letra,
					'' as notas 
				FROM
					cuenta_ctejf cc 
					LEFT JOIN clientesjf c 
					ON cc.cliente = c.codigo 
					LEFT JOIN 
					(SELECT 
						* 
					FROM
						maestrajf 
					WHERE tipo_dato = 'tvend') v 
					ON cc.vendedor = v.codigo 
				WHERE cc.tip_mov = '-' 
					AND (
					cc.fecha BETWEEN '$inicio' 
					AND '$fin'
					) 
					AND cc.cod_pago = '$canc' 
				GROUP BY cc.fecha 
				UNION ALL
				SELECT 
					'999' AS tipo_doc,
					'Fecha de pago:',
					cc.fecha,
					'',
					'',
					'',
					'',
					'',
					FORMAT(
					SUM(
						CASE
						WHEN cc.tipo_doc IN ('01', '03', '09', '08', '07') 
						THEN cc.monto 
						ELSE '' 
						END
					),
					2
					) AS fact,
					FORMAT(
					SUM(
						CASE
						WHEN cc.tipo_doc IN ('85') 
						THEN cc.monto 
						ELSE '' 
						END
					),
					2
					) AS letra,
					'' as notas 
				FROM
					cuenta_ctejf cc 
					LEFT JOIN clientesjf c 
					ON cc.cliente = c.codigo 
					LEFT JOIN 
					(SELECT 
						* 
					FROM
						maestrajf 
					WHERE tipo_dato = 'tvend') v 
					ON cc.vendedor = v.codigo 
				WHERE cc.tip_mov = '-' 
					AND (
					cc.fecha BETWEEN '$inicio' 
					AND '$fin'
					) 
					AND cc.cod_pago = '$canc' 
					GROUP BY cc.fecha 
					ORDER BY fecha,
					tipo_doc,
					num_cta");
		}

		$stmt->execute();

		return $stmt->fetchAll();

		// $stmt->close();

		// $stmt = null;
	}

	static public function mdlMostrarReporteTotalCobrar($tabla, $orden1, $orden2, $tip_doc, $cli, $vend, $banco)
	{


		$stmt = Conexion::conectar()->prepare("SELECT 
		FORMAT(SUM(cc.saldo), 2) AS saldo_total 
		FROM
		cuenta_ctejf cc 
		LEFT JOIN clientesjf c 
		ON cc.cliente = c.codigo 
		WHERE cc.tip_mov = '+' 
		AND cc.estado = 'Pendiente' 
		ORDER BY cc.tipo_doc ");
		// $stmt -> bindParam(":cliente", $valor, PDO::PARAM_STR);

		$stmt->execute();

		return $stmt->fetch();


		$stmt->close();

		$stmt = null;
	}

	static public function mdlMostrarReporteTotalOct($tip_doc, $banco, $fin)
	{


		$stmt = Conexion::conectar()->prepare("SELECT 
		FORMAT(SUM(cc.saldo), 2) AS saldo_total 
	  FROM
		cuenta_ctejf cc 
		LEFT JOIN clientesjf c 
		  ON cc.cliente = c.codigo 
	  WHERE cc.tip_mov = '+' 
		AND cc.estado = 'PENDIENTE' 
		AND cc.tipo_doc = '85' 
		AND cc.banco = '02' 
		AND cc.fecha_ven <= '$fin' 
	  GROUP BY '+'");


		$stmt->bindParam(":fin", $fin, PDO::PARAM_STR);

		$stmt->execute();

		return $stmt->fetch();


		$stmt->close();

		$stmt = null;
	}

	static public function mdlMostrarReporteTotalVencidos($tabla, $orden1, $orden2, $tip_doc, $cli, $vend, $banco)
	{


		$stmt = Conexion::conectar()->prepare("SELECT 
			FORMAT(SUM(cc.saldo), 2) AS saldo_total 
			FROM
			cuenta_ctejf cc 
			LEFT JOIN clientesjf c 
			ON cc.cliente = c.codigo 
			WHERE cc.tip_mov = '+' 
			AND cc.estado = 'Pendiente' 
		    AND NOW()>cc.fecha_ven  
			ORDER BY cc.tipo_doc ");
		// $stmt -> bindParam(":cliente", $valor, PDO::PARAM_STR);

		$stmt->execute();

		return $stmt->fetch();

		$stmt->close();

		$stmt = null;
	}

	static public function mdlMostrarReporteTotalNoVencidos($tabla, $orden1, $orden2, $tip_doc, $cli, $vend, $banco)
	{

		$stmt = Conexion::conectar()->prepare("SELECT 
				FORMAT(SUM(cc.saldo), 2) AS saldo_total 
				FROM
				cuenta_ctejf cc 
				LEFT JOIN clientesjf c 
				ON cc.cliente = c.codigo 
				WHERE cc.tip_mov = '+' 
				AND cc.estado = 'Pendiente' 
				AND NOW()<cc.fecha_ven  
				ORDER BY cc.tipo_doc ");
		// $stmt -> bindParam(":cliente", $valor, PDO::PARAM_STR);

		$stmt->execute();

		return $stmt->fetch();


		$stmt->close();

		$stmt = null;
	}

	static public function mdlMostrarReporteTotalProtestados($tabla, $orden1, $orden2, $tip_doc, $cli, $vend, $banco)
	{


		$stmt = Conexion::conectar()->prepare("SELECT 
				FORMAT(SUM(cc.saldo), 2) AS saldo_total 
				FROM
				cuenta_ctejf cc 
				LEFT JOIN clientesjf c 
				ON cc.cliente = c.codigo 
				WHERE cc.tip_mov = '+' 
				AND cc.estado = 'Pendiente'
				AND cc.protesta = 1  
				ORDER BY cc.tipo_doc ");
		// $stmt -> bindParam(":cliente", $valor, PDO::PARAM_STR);

		$stmt->execute();

		return $stmt->fetch();


		$stmt->close();

		$stmt = null;
	}

	static public function mdlMostrarReporteTotalPagos($tabla, $orden1, $orden2, $canc, $vend, $inicio, $fin)
	{

		if ($orden1 == "fecha_pag" && $orden2 == "ordNumCuenta") {
			$stmt = Conexion::conectar()->prepare("SELECT 
					'Total General' AS total_gral,
					FORMAT(
					  SUM(
						CASE
						  WHEN cc.tipo_doc IN ('01', '03', '09', '08', '07') 
						  THEN cc.monto 
						  ELSE '' 
						END
					  ),
					  2
					) AS fact,
					FORMAT(
					  SUM(
						CASE
						  WHEN cc.tipo_doc IN ('85') 
						  THEN cc.monto 
						  ELSE '' 
						END
					  ),
					  2
					) AS letra 
				  FROM
					cuenta_ctejf cc 
					LEFT JOIN clientesjf c 
					  ON cc.cliente = c.codigo 
					LEFT JOIN 
					  (SELECT 
						* 
					  FROM
						maestrajf 
					  WHERE tipo_dato = 'tvend') v 
					  ON cc.vendedor = v.codigo 
				  WHERE cc.tip_mov = '-' 
					AND (
					  cc.fecha BETWEEN '" . $inicio . "' 
					 AND '" . $fin . "'
					) AND cc.cod_pago = '" . $canc . "' ");
			// $stmt -> bindParam(":cliente", $valor, PDO::PARAM_STR);

			$stmt->execute();

			return $stmt->fetch();
		} else if ($canc != "" && $orden2 == "ordVencimiento") {
			$stmt = Conexion::conectar()->prepare("SELECT 
					'Total General' AS total_gral,
					cc.vendedor, 
					FORMAT(
					  SUM(
						CASE
						  WHEN cc.tipo_doc IN ('01', '03', '09', '08', '07') 
						  THEN cc.monto 
						  ELSE '' 
						END
					  ),
					  2
					) AS fact,
					FORMAT(
					  SUM(
						CASE
						  WHEN cc.tipo_doc IN ('85') 
						  THEN cc.monto 
						  ELSE '' 
						END
					  ),
					  2
					) AS letra 
				  FROM
					cuenta_ctejf cc 
					LEFT JOIN clientesjf c 
					  ON cc.cliente = c.codigo 
					LEFT JOIN 
					  (SELECT 
						* 
					  FROM
						maestrajf 
					  WHERE tipo_dato = 'tvend') v 
					  ON cc.vendedor = v.codigo 
				  WHERE cc.tip_mov = '-' 
					AND (
					  cc.fecha BETWEEN '" . $inicio . "' 
					 AND '" . $fin . "'
					) AND cc.vendedor= '" . $vend . "'  
				  GROUP BY cc.vendedor ");
			// $stmt -> bindParam(":cliente", $valor, PDO::PARAM_STR);

			$stmt->execute();

			return $stmt->fetch();
		}



		$stmt->close();

		$stmt = null;
	}

	static public function mdlMostrarReporteNombre($tabla, $cli, $vend)
	{

		if (isset($cli)) {
			$stmt = Conexion::conectar()->prepare("SELECT 
			cc.cliente,
			c.nombre,
			FORMAT(SUM(cc.saldo), 2) AS saldo_total 
			FROM
				cuenta_ctejf cc 
				LEFT JOIN clientesjf c 
				ON cc.cliente = c.codigo 
			WHERE cc.tip_mov = '+' 
				AND cc.estado = 'Pendiente' 
				AND cc.cliente = '" . $cli . "' 
			GROUP BY cc.cliente 
			  ORDER BY cc.tipo_doc");

			// $stmt -> bindParam(":cliente", $valor, PDO::PARAM_STR);

			$stmt->execute();

			return $stmt->fetch();
		} else {
			$stmt = Conexion::conectar()->prepare("SELECT 
			cc.vendedor,
			v.descripcion,
			FORMAT(SUM(cc.saldo),2) AS total_general
		  FROM
			cuenta_ctejf cc 
			LEFT JOIN clientesjf c 
			  ON cc.cliente = c.codigo 
			LEFT JOIN 
			  (SELECT 
				* 
			  FROM
				maestrajf 
			  WHERE tipo_dato = 'tvend') v 
			  ON cc.vendedor = v.codigo 
		  WHERE cc.tip_mov = '+' 
			AND cc.estado = 'Pendiente' 
			AND cc.vendedor = '" . $vend . "' 
		  GROUP BY cc.vendedor 
		  ORDER BY cc.tipo_doc ");

			// $stmt -> bindParam(":cliente", $valor, PDO::PARAM_STR);

			$stmt->execute();

			return $stmt->fetch();
		}


		$stmt->close();

		$stmt = null;
	}

	static public function mdlMostrarReporteNombreVencidos($tabla, $cli, $vend)
	{

		if (isset($cli)) {
			$stmt = Conexion::conectar()->prepare("SELECT 
			cc.cliente,
			c.nombre,
			FORMAT(SUM(cc.saldo), 2) AS saldo_total 
			FROM
				cuenta_ctejf cc 
				LEFT JOIN clientesjf c 
				ON cc.cliente = c.codigo 
			WHERE cc.tip_mov = '+' 
				AND cc.estado = 'Pendiente' 
				AND cc.cliente = '" . $cli . "' 
		    	AND NOW()>cc.fecha_ven  
			GROUP BY cc.cliente 
			  ORDER BY cc.tipo_doc ");

			// $stmt -> bindParam(":cliente", $valor, PDO::PARAM_STR);

			$stmt->execute();

			return $stmt->fetch();
		} else {
			$stmt = Conexion::conectar()->prepare("SELECT 
			cc.vendedor,
			v.descripcion,
			FORMAT(SUM(cc.saldo),2) AS total_general
		  FROM
			cuenta_ctejf cc 
			LEFT JOIN clientesjf c 
			  ON cc.cliente = c.codigo 
			LEFT JOIN 
			  (SELECT 
				* 
			  FROM
				maestrajf 
			  WHERE tipo_dato = 'tvend') v 
			  ON cc.vendedor = v.codigo 
		  WHERE cc.tip_mov = '+' 
			AND cc.estado = 'Pendiente' 
			AND cc.vendedor = '" . $vend . "'
			AND NOW()>cc.fecha_ven  
		  GROUP BY cc.vendedor 
		  ORDER BY cc.tipo_doc ;");

			// $stmt -> bindParam(":cliente", $valor, PDO::PARAM_STR);

			$stmt->execute();

			return $stmt->fetch();
		}


		$stmt->close();

		$stmt = null;
	}

	static public function mdlMostrarReporteNombreNoVencidos($tabla, $cli, $vend)
	{

		if (isset($cli)) {
			$stmt = Conexion::conectar()->prepare("SELECT 
			cc.cliente,
			c.nombre
			FORMAT(SUM(cc.saldo), 2) AS saldo_total 
			FROM
				cuenta_ctejf cc 
				LEFT JOIN clientesjf c 
				ON cc.cliente = c.codigo 
			WHERE cc.tip_mov = '+' 
				AND cc.estado = 'Pendiente' 
				AND cc.cliente = '" . $cli . "' 
				AND NOW()<cc.fecha_ven  
			GROUP BY cc.cliente 
			  ORDER BY cc.tipo_doc ");

			// $stmt -> bindParam(":cliente", $valor, PDO::PARAM_STR);

			$stmt->execute();

			return $stmt->fetch();
		} else {
			$stmt = Conexion::conectar()->prepare("SELECT 
			cc.vendedor,
			v.descripcion,
			FORMAT(SUM(cc.saldo),2) AS total_general
		  FROM
			cuenta_ctejf cc 
			LEFT JOIN clientesjf c 
			  ON cc.cliente = c.codigo 
			LEFT JOIN 
			  (SELECT 
				* 
			  FROM
				maestrajf 
			  WHERE tipo_dato = 'tvend') v 
			  ON cc.vendedor = v.codigo 
		  WHERE cc.tip_mov = '+' 
			AND cc.estado = 'Pendiente' 
			AND cc.vendedor = '" . $vend . "'
			AND NOW()<cc.fecha_ven  
		  GROUP BY cc.vendedor 
		  ORDER BY cc.tipo_doc ;");

			// $stmt -> bindParam(":cliente", $valor, PDO::PARAM_STR);

			$stmt->execute();

			return $stmt->fetch();
		}


		$stmt->close();

		$stmt = null;
	}

	static public function mdlMostrarReporteNombreProtestados($tabla, $cli, $vend)
	{

		if (!empty($cli)) {

			$stmt = Conexion::conectar()->prepare("SELECT 
							cc.cliente,
							c.nombre,
							FORMAT(SUM(cc.saldo), 2) AS saldo_total 
							FROM
								cuenta_ctejf cc 
								LEFT JOIN clientesjf c 
								ON cc.cliente = c.codigo 
							WHERE cc.tip_mov = '+' 
								AND cc.estado = 'Pendiente' 
								AND cc.cliente = '" . $cli . "'
								AND cc.protesta = 1   
							GROUP BY cc.cliente 
							ORDER BY cc.tipo_doc");

			// $stmt -> bindParam(":cliente", $valor, PDO::PARAM_STR);

			$stmt->execute();

			return $stmt->fetch();
		} else {

			$stmt = Conexion::conectar()->prepare("SELECT 
							cc.vendedor,
							v.descripcion,
							FORMAT(SUM(cc.saldo),2) AS total_general
						FROM
							cuenta_ctejf cc 
							LEFT JOIN clientesjf c 
							ON cc.cliente = c.codigo 
							LEFT JOIN 
							(SELECT 
								* 
							FROM
								maestrajf 
							WHERE tipo_dato = 'tvend') v 
							ON cc.vendedor = v.codigo 
						WHERE cc.tip_mov = '+' 
							AND cc.estado = 'Pendiente' 
							AND cc.vendedor = '" . $vend . "'
							AND cc.protesta = 1   
						GROUP BY cc.vendedor 
						ORDER BY cc.tipo_doc ");

			// $stmt -> bindParam(":cliente", $valor, PDO::PARAM_STR);

			$stmt->execute();

			return $stmt->fetch();
		}


		$stmt->close();

		$stmt = null;
	}


	/*
	* REGISTAR CANCELACION LETRAS 
	*/
	static public function mdlRegistrarCancelacionLetras($detalle)
	{

		$stmt = Conexion::conectar()->prepare("INSERT INTO cuenta_ctejf (
													tipo_doc,
													num_cta,
													cliente,
													vendedor,
													fecha,
													fecha_ven,
													tip_mon,
													monto,
													notas,
													estado,
													cod_pago,
													doc_origen,
													usuario,
													saldo,
													num_unico,
													tip_mov,
													usureg,
													pcreg
												) VALUES
                                                    $detalle");
		if ($stmt->execute()) {

			return "ok";
		} else {

			return $stmt->errorInfo();
		}

		$stmt->close();

		$stmt = null;
	}

	//* ESTADO DE CEUNTA CABECERA
	static public function ctrEstadoCuentaCab($cliente, $vendedor, $soloProtestados = false)
	{
		$filtroProtesta = $soloProtestados ? " AND cc.protesta = '1' " : "";

		$stmt = Conexion::conectar()->prepare("SELECT 
												cc.cliente,
												c.nombre,
												c.direccion,
												c.ubigeo,
												(SELECT 
													nombre 
												FROM
													ubigeo u 
												WHERE c.ubigeo = u.codigo) AS nom_ubigeo,
												c.documento,
												c.telefono,
												SUM(cc.saldo) AS saldo,
												SUM(
													CASE
													WHEN cc.protesta = '1' 
													THEN 85 
													ELSE 0 
													END
												) AS gastos,
												(
													SUM(cc.saldo) + SUM(
													CASE
														WHEN cc.protesta = '1' 
														THEN 85 
														ELSE 0 
													END
													)
												) AS monto_total 
														FROM
															cuenta_ctejf cc 
															LEFT JOIN clientesjf c 
															ON cc.cliente = c.codigo 
														WHERE cc.cliente = :cliente 
															AND cc.tip_mov = '+' 
															AND cc.estado = 'PENDIENTE'
															AND cc.vendedor IN ($vendedor)
															$filtroProtesta
														GROUP BY cc.cliente");

		$stmt->bindParam(":cliente", $cliente, PDO::PARAM_STR);

		$stmt->execute();

		return $stmt->fetch();



		$stmt->close();

		$stmt = null;
	}

	//* DOCUMENTOS PENDIENTES CONTADO
	static public function mdlContadoPendientes()
	{

		$stmt = Conexion::conectar()->prepare("SELECT 
											cc.vendedor,
											c.ubigeo,
											(SELECT 
											nombre 
											FROM
											ubigeo u 
											WHERE c.ubigeo = u.codigo) AS nom_ubigeo,
											cc.cliente,
											c.nombre,
											cc.tipo_doc,
											CASE
											WHEN cc.tipo_doc = '01' 
											THEN 'FACTURA' 
											WHEN cc.tipo_doc = '03' 
											THEN 'BOLETA' 
											WHEN cc.tipo_doc = '85' 
											THEN 'LETRA' 
											WHEN cc.tipo_doc = '09' 
											THEN 'PROFORMA' 
											ELSE 'NTCD' 
											END AS nom_doc,
											cc.num_cta,
											cc.fecha,
											cc.fecha_ven,
											cc.ult_pago,
											cc.monto,
											cc.saldo 
										FROM
											cuenta_ctejf cc 
											LEFT JOIN clientesjf c 
											ON cc.cliente = c.codigo 
										WHERE cc.tip_mov = '+' 
											AND cc.estado = 'PENDIENTE' 
											AND cc.tipo_doc <> '85' 
											AND cc.fecha = cc.fecha_ven 
											AND cc.vendedor NOT LIKE '%08%' AND cc.vendedor NOT LIKE '%06%'
										ORDER BY cc.vendedor,
											cc.fecha");
		$stmt->execute();

		return $stmt->fetchAll();



		$stmt->close();

		$stmt = null;
	}

	//* LETRAS POR ACEPTAR
	static public function mdlLetrasAceptar($vendedor, $mes, $ini, $fin)
	{

		if ($mes == null) {
			$stmt = Conexion::conectar()->prepare("SELECT 
													cc.tipo_doc,
													cc.num_cta,
													cc.cod_pago,
													cc.doc_origen,
													cc.fecha,
													cc.fecha_ven,
													cc.monto,
													cc.saldo,
													cc.cliente,
													c.nombre,
													c.ubigeo,
													u.nombre AS nom_ubigeo,
													cc.vendedor 
												FROM
													cuenta_ctejf cc 
													LEFT JOIN clientesjf c 
													ON cc.cliente = c.codigo 
													LEFT JOIN ubigeo u 
													ON c.ubigeo = u.codigo 
												WHERE cc.tipo_doc = '85' 
													AND cc.estado = 'PENDIENTE' 
													AND cc.tip_mov = '+' 
													AND (cc.banco <> '02' 
													OR cc.banco IS NULL) 
													AND (
													cc.num_unico = '' 
													OR cc.num_unico IS NULL
													) 
													AND cc.protesta <> '1' 
													AND cc.vendedor = :vendedor 
												ORDER BY cc.vendedor,
													cc.cliente,
													cc.doc_origen,
													cc.fecha_ven
												LIMIT $ini, $fin");
		} else {
			$stmt = Conexion::conectar()->prepare("SELECT 
						cc.tipo_doc,
						cc.num_cta,
						cc.cod_pago,
						cc.doc_origen,
						cc.fecha,
						cc.fecha_ven,
						cc.monto,
						cc.saldo,
						cc.cliente,
						c.nombre,
						c.ubigeo,
						u.nombre AS nom_ubigeo,
						cc.vendedor 
					FROM
						cuenta_ctejf cc 
						LEFT JOIN clientesjf c 
						ON cc.cliente = c.codigo 
						LEFT JOIN ubigeo u 
						ON c.ubigeo = u.codigo 
					WHERE cc.tipo_doc = '85' 
						AND cc.estado = 'PENDIENTE' 
						AND cc.tip_mov = '+' 
						AND (cc.banco <> '02' 
						OR cc.banco IS NULL) 
						AND (
						cc.num_unico = '' 
						OR cc.num_unico IS NULL
						) 
						AND cc.protesta <> '1' 
						AND cc.vendedor = :vendedor 
						and MONTH(cc.fecha) = '$mes'
					ORDER BY cc.vendedor,
						cc.cliente,
						cc.doc_origen,
						cc.fecha_ven
					LIMIT $ini, $fin");
		}

		$stmt->bindParam(":vendedor", $vendedor, PDO::PARAM_STR);

		$stmt->execute();

		return $stmt->fetchAll();

		$stmt->close();

		$stmt = null;
	}

	//* LETRAS POR ACEPTAR
	static public function mdlLetrasAceptarTotal($vendedor, $mes)
	{

		if ($mes === null) {
			$stmt = Conexion::conectar()->prepare("SELECT 
							cc.vendedor,
							SUM(cc.saldo) AS saldo 
						FROM
							cuenta_ctejf cc 
							LEFT JOIN clientesjf c 
							ON cc.cliente = c.codigo 
						WHERE cc.tipo_doc = '85' 
							AND cc.estado = 'PENDIENTE' 
							AND cc.tip_mov = '+' 
							AND (cc.banco <> '02' 
							OR cc.banco IS NULL) 
							AND (
							cc.num_unico = '' 
							OR cc.num_unico IS NULL
							) 
							AND cc.protesta <> '1' 
							AND cc.vendedor = '$vendedor'
						ORDER BY cc.vendedor,
							cc.cliente,
							cc.doc_origen,
							cc.fecha_ven");
		} else {

			$stmt = Conexion::conectar()->prepare("SELECT 
							cc.vendedor,
							SUM(cc.saldo) AS saldo 
						FROM
							cuenta_ctejf cc 
							LEFT JOIN clientesjf c 
							ON cc.cliente = c.codigo 
						WHERE cc.tipo_doc = '85' 
							AND cc.estado = 'PENDIENTE' 
							AND cc.tip_mov = '+' 
							AND (cc.banco <> '02' 
							OR cc.banco IS NULL) 
							AND (
							cc.num_unico = '' 
							OR cc.num_unico IS NULL
							) 
							AND cc.protesta <> '1' 
							AND cc.vendedor = '$vendedor'
							and MONTH(cc.fecha) = '$mes'	
						ORDER BY cc.vendedor,
							cc.cliente,
							cc.doc_origen,
							cc.fecha_ven");
		}


		$stmt->bindParam(":vendedor", $vendedor, PDO::PARAM_STR);

		$stmt->execute();

		return $stmt->fetch();

		$stmt->close();

		$stmt = null;
	}

	//* ESTADO DE CEUNTA DETALLE
	static public function ctrEstadoCuentaDet($cliente, $vendedor, $soloProtestados = false)
	{
		$filtroProtesta = $soloProtestados ? " AND c.protesta = '1' " : "";

		$stmt = Conexion::conectar()->prepare("SELECT 
											CASE
												WHEN c.tipo_doc = '01' 
												THEN 'FACTURA' 
												WHEN c.tipo_doc = '03' 
												THEN 'BOLETA' 
												WHEN c.tipo_doc = '07' 
												THEN 'NC' 
												WHEN c.tipo_doc = '08' 
												THEN 'ND' 
												WHEN c.tipo_doc = '09' 
												THEN 'PROFORMA' 
												ELSE 'LETRA' 
											END AS tipo_documento,
											c.num_cta,
											c.fecha,
											c.fecha_ven,
											c.vendedor,
											c.num_unico,
											CASE
												WHEN c.banco = '02' 
												THEN 'BCP' 
												ELSE '' 
											END AS banco,
											c.monto,
											c.saldo,
											CASE
												WHEN c.protesta = '1' 
												THEN 'SI' 
												ELSE 'NO' 
											END AS protesta,
											CASE
												WHEN c.protesta = '1' 
												THEN 85 
												ELSE 0 
											END AS gasto,
											(
												c.saldo + 
												CASE
												WHEN c.protesta = '1' 
												THEN 85 
												ELSE 0 
												END
											) AS monto_total 
												FROM
													cuenta_ctejf c 
												WHERE c.cliente = :cliente 
													AND c.tip_mov = '+' 
													AND c.estado = 'PENDIENTE'
													AND c.vendedor IN ($vendedor)
													$filtroProtesta
												ORDER BY c.tipo_doc,
													c.fecha_ven");

		$stmt->bindParam(":cliente", $cliente, PDO::PARAM_STR);

		$stmt->execute();

		return $stmt->fetchAll();

		$stmt->close();

		$stmt = null;
	}

	//* ESTADO DE CEUNTA DETALLE
	static public function mdlProyeccionPagos()
	{

		$stmt = Conexion::conectar()->prepare("SELECT 
					YEAR(cc.fecha_ven) AS anno,
					WEEK(cc.fecha_ven) AS semana,
					MIN(cc.fecha_ven) AS inicio,
					MAX(cc.fecha_ven) AS fin,
					SUM(
					CASE
						WHEN cc.tipo_doc IN ('01', '03', '07', '08', '09') 
						THEN cc.saldo 
						ELSE 0 
					END
					) AS 'facturas',
					SUM(
					CASE
						WHEN cc.tipo_doc IN ('85') 
						AND cc.banco = '02' 
						THEN cc.saldo 
						ELSE 0 
					END
					) AS 'letras',
					SUM(cc.saldo) AS total 
				FROM
					cuenta_ctejf cc 
				WHERE cc.tip_mov = '+' 
					AND cc.estado = 'PENDIENTE' 
					AND cc.fecha_ven >= DATE(NOW()) 
				GROUP BY YEAR(cc.fecha_ven),
					WEEK(cc.fecha_ven)");

		$stmt->execute();

		return $stmt->fetchAll();

		$stmt->close();

		$stmt = null;
	}

	//* ESTADO DE CEUNTA DETALLE PROTESTO
	static public function mdlEstadoCuentaProt($num_cta)
	{

		$stmt = Conexion::conectar()->prepare("SELECT 
											CASE
												WHEN c.tipo_doc = '01' 
												THEN 'FACTURA' 
												WHEN c.tipo_doc = '03' 
												THEN 'BOLETA' 
												WHEN c.tipo_doc = '07' 
												THEN 'NC' 
												WHEN c.tipo_doc = '08' 
												THEN 'ND' 
												WHEN c.tipo_doc = '09' 
												THEN 'PROFORMA' 
												ELSE 'LETRA' 
											END AS tipo_documento,
											c.num_cta,
											c.fecha,
											c.fecha_ven,
											c.vendedor,
											c.num_unico,
											c.monto,
											c.saldo,
											CASE
												WHEN c.protesta = '1' 
												THEN 'SI' 
												ELSE 'NO' 
											END AS protesta,
											CASE
												WHEN c.protesta = '1' 
												THEN 85 
												ELSE 0 
											END AS gasto,
											(
												c.saldo + 
												CASE
												WHEN c.protesta = '1' 
												THEN 85 
												ELSE 0 
												END
											) AS monto_total 
												FROM
													cuenta_ctejf c 
												WHERE c.num_cta = :num_cta 
													AND c.tip_mov = '+' 
													AND c.estado = 'PENDIENTE' 
												ORDER BY c.tipo_doc,
													c.fecha_ven");

		$stmt->bindParam(":num_cta", $num_cta, PDO::PARAM_STR);

		$stmt->execute();

		return $stmt->fetch();

		$stmt->close();

		$stmt = null;
	}

	//* ESTADO DE CEUNTA VENDEDOR
	static public function mdlEstadoCtaVdor($vendedor)
	{

		$stmt = Conexion::conectar()->prepare("SELECT 
									cc.tipo_doc,
									cc.num_cta,
									cc.cod_pago,
									cc.doc_origen,
									cc.fecha,
									cc.fecha_ven,
									cc.cliente,
									c.nombre,
									cc.monto,
									cc.saldo,
									CASE
									WHEN cc.protesta = 1 
									THEN 'SI' 
									ELSE '' 
									END AS protesta,
									cc.num_unico,
									CASE
									WHEN cc.banco = '02' 
									THEN 'BCP' 
									ELSE '' 
									END AS banco 
								FROM
									cuenta_ctejf cc 
									LEFT JOIN clientesjf c 
									ON cc.cliente = c.codigo 
								WHERE cc.tip_mov = '+' 
									AND cc.estado = 'PENDIENTE' 
									AND cc.vendedor = :vendedor 
								UNION
								SELECT 
									'00' AS tipo_doc,
									'' AS num_cta,
									'' AS cod_pago,
									'' AS doc_origen,
									'' AS fecha,
									'' AS fecha_ven,
									cc.cliente,
									c.nombre,
									'' AS monto,
									'' AS saldo,
									'' AS protesta,
									'' AS num_unico,
									'' AS banco 
								FROM
									cuenta_ctejf cc 
									LEFT JOIN clientesjf c 
									ON cc.cliente = c.codigo 
								WHERE cc.tip_mov = '+' 
									AND cc.estado = 'PENDIENTE' 
									AND cc.vendedor = :vendedor 
								GROUP BY cc.cliente 
								UNION
								SELECT 
									'98' AS tipo_doc,
									'' AS num_cta,
									'' AS cod_pago,
									'' AS doc_origen,
									'' AS fecha,
									'' AS fecha_ven,
									cc.cliente,
									c.nombre,
									SUM(cc.monto) AS monto,
									SUM(cc.saldo) AS saldo,
									'' AS protesta,
									'' AS num_unico,
									'' AS banco 
								FROM
									cuenta_ctejf cc 
									LEFT JOIN clientesjf c 
									ON cc.cliente = c.codigo 
								WHERE cc.tip_mov = '+' 
									AND cc.estado = 'PENDIENTE' 
									AND cc.vendedor = :vendedor 
								GROUP BY cc.cliente 
								UNION
								SELECT 
								'99' AS tipo_doc,
								'' AS num_cta,
								'' AS cod_pago,
								'' AS doc_origen,
								'' AS fecha,
								'' AS fecha_ven,
								'Z9999999' AS cliente,
								'' AS nombre,
								SUM(cc.monto) AS monto,
								SUM(cc.saldo) AS saldo,
								'' AS protesta,
								'' AS num_unico,
								'' AS banco 
								FROM
								cuenta_ctejf cc 
								LEFT JOIN clientesjf c 
									ON cc.cliente = c.codigo 
								WHERE cc.tip_mov = '+' 
								AND cc.estado = 'PENDIENTE' 
								AND cc.vendedor = :vendedor 
								GROUP BY cc.vendedor 
								ORDER BY cliente,
								tipo_doc,
								fecha_ven");

		$stmt->bindParam(":vendedor", $vendedor, PDO::PARAM_STR);

		$stmt->execute();

		return $stmt->fetchAll();

		$stmt->close();

		$stmt = null;
	}

	//* ESTADO DE CEUNTA VENDEDOR VENDIDOS POR ZONA
	static public function mdlEstadoCtaVdorVdos($vendedor)
	{

		if ($vendedor == "08") {

			$stmt = Conexion::conectar()->prepare("SELECT 
						c.tipo_doc,
						c.num_cta,
						c.fecha,
						c.fecha_ven,
						CASE
						WHEN c.tipo_doc = '85' 
						THEN DATE_ADD(c.fecha_ven, INTERVAL 8 DAY) 
						ELSE c.fecha_ven 
						END AS fec_ven2,
						c.doc_origen,
						c.cliente,
						cc.nombre,
						c.monto,
						c.saldo,
						cc.ubigeo,
						(SELECT 
						nombre 
						FROM
						ubigeo u 
						WHERE cc.ubigeo = u.codigo) AS nom_ubigeo,
						CASE
						WHEN c.protesta = '1' 
						THEN 'SI' 
						ELSE '' 
						END AS protesta 
					FROM
						cuenta_ctejf c 
						LEFT JOIN clientesjf cc 
						ON c.cliente = cc.codigo 
					WHERE c.tip_mov = '+' 
						AND c.estado = 'PENDIENTE' 
						AND 
						CASE
						WHEN c.tipo_doc = '85' 
						THEN DATE_ADD(c.fecha_ven, INTERVAL 8 DAY) 
						ELSE c.fecha_ven 
						END < DATE(NOW()) 
						AND c.vendedor = '08' 
					UNION
					SELECT 
						'ZZ' AS tipo_doc,
						'' AS num_cta,
						'' AS fecha,
						'9999-12-31' AS fecha_ven,
						'' AS fec_ven2,
						'' AS doc_origen,
						'' cliente,
						'' AS nombre,
						SUM(c.monto) AS monto,
						SUM(c.saldo) AS saldo,
						'ZZ' AS ubigeo,
						'' AS nom_ubigeo,
						'' AS protesta 
					FROM
						cuenta_ctejf c 
						LEFT JOIN clientesjf cc 
						ON c.cliente = cc.codigo 
					WHERE c.tip_mov = '+' 
						AND c.estado = 'PENDIENTE' 
						AND 
						CASE
						WHEN c.tipo_doc = '85' 
						THEN DATE_ADD(c.fecha_ven, INTERVAL 8 DAY) 
						ELSE c.fecha_ven 
						END < DATE(NOW()) 
						AND c.vendedor = '08' 
					GROUP BY c.vendedor 
					ORDER BY fecha_ven");

			$stmt->bindParam(":vendedor", $vendedor, PDO::PARAM_STR);

			$stmt->execute();

			return $stmt->fetchAll();
		} else {

			$stmt = Conexion::conectar()->prepare("SELECT 
						c.tipo_doc,
						c.num_cta,
						c.fecha,
						c.fecha_ven,
						CASE
						WHEN c.tipo_doc = '85' 
						THEN DATE_ADD(c.fecha_ven, INTERVAL 8 DAY) 
						ELSE c.fecha_ven 
						END AS fec_ven2,
						c.doc_origen,
						c.cliente,
						cc.nombre,
						c.monto,
						c.saldo,
						cc.ubigeo,
						(SELECT 
						nombre 
						FROM
						ubigeo u 
						WHERE cc.ubigeo = u.codigo) AS nom_ubigeo,
						CASE
						WHEN c.protesta = '1' 
						THEN 'SI' 
						ELSE '' 
						END AS protesta 
					FROM
						cuenta_ctejf c 
						LEFT JOIN clientesjf cc 
						ON c.cliente = cc.codigo 
					WHERE c.tip_mov = '+' 
						AND c.estado = 'PENDIENTE' 
						AND 
						CASE
						WHEN c.tipo_doc = '85' 
						THEN DATE_ADD(c.fecha_ven, INTERVAL 8 DAY) 
						ELSE c.fecha_ven 
						END < DATE(NOW()) 
						AND c.vendedor = :vendedor 
					UNION
					SELECT 
						'ZZ' AS tipo_doc,
						'' AS num_cta,
						'' AS fecha,
						'' AS fecha_ven,
						'' AS fec_ven2,
						'' AS doc_origen,
						'' cliente,
						'' AS nombre,
						SUM(c.monto) AS monto,
						SUM(c.saldo) AS saldo,
						'ZZ' AS ubigeo,
						'' AS nom_ubigeo,
						'' AS protesta 
					FROM
						cuenta_ctejf c 
						LEFT JOIN clientesjf cc 
						ON c.cliente = cc.codigo 
					WHERE c.tip_mov = '+' 
						AND c.estado = 'PENDIENTE' 
						AND 
						CASE
						WHEN c.tipo_doc = '85' 
						THEN DATE_ADD(c.fecha_ven, INTERVAL 8 DAY) 
						ELSE c.fecha_ven 
						END < DATE(NOW()) 
						AND c.vendedor = :vendedor 
					GROUP BY c.vendedor 
					ORDER BY ubigeo,
						cliente,
						fecha_ven");

			$stmt->bindParam(":vendedor", $vendedor, PDO::PARAM_STR);

			$stmt->execute();

			return $stmt->fetchAll();
		}

		$stmt->close();

		$stmt = null;
	}

	//* ESTADO DE CEUNTA VENDEDOR NO VENDIDOS POR ZONA
	static public function mdlEstadoCtaVdorNoVdos($vendedor)
	{

		if ($vendedor == "08") {

			$stmt = Conexion::conectar()->prepare("SELECT 
						c.tipo_doc,
						c.num_cta,
						c.fecha,
						c.fecha_ven,
						CASE
						WHEN c.tipo_doc = '85' 
						THEN DATE_ADD(c.fecha_ven, INTERVAL 8 DAY) 
						ELSE c.fecha_ven 
						END AS fec_ven2,
						c.doc_origen,
						c.cliente,
						cc.nombre,
						c.saldo,
						cc.ubigeo,
						(SELECT 
						nombre 
						FROM
						ubigeo u 
						WHERE cc.ubigeo = u.codigo) AS nom_ubigeo,
						CASE
						WHEN c.protesta = '1' 
						THEN 'SI' 
						ELSE '' 
						END AS protesta 
					FROM
						cuenta_ctejf c 
						LEFT JOIN clientesjf cc 
						ON c.cliente = cc.codigo 
					WHERE c.tip_mov = '+' 
						AND c.estado = 'PENDIENTE' 
						AND 
						CASE
						WHEN c.tipo_doc = '85' 
						THEN DATE_ADD(c.fecha_ven, INTERVAL 8 DAY) 
						ELSE c.fecha_ven 
						END > DATE(NOW()) 
						AND c.vendedor = '08' 
						AND c.tipo_doc <>'85'
					UNION
					SELECT 
						'ZZ' AS tipo_doc,
						'' AS num_cta,
						'' AS fecha,
						'9999-12-31' AS fecha_ven,
						'' AS fec_ven2,
						'' AS doc_origen,
						'' cliente,
						'' AS nombre,
						SUM(c.saldo) AS saldo,
						'ZZ' AS ubigeo,
						'' AS nom_ubigeo,
						'' AS protesta 
					FROM
						cuenta_ctejf c 
						LEFT JOIN clientesjf cc 
						ON c.cliente = cc.codigo 
					WHERE c.tip_mov = '+' 
						AND c.estado = 'PENDIENTE' 
						AND 
						CASE
						WHEN c.tipo_doc = '85' 
						THEN DATE_ADD(c.fecha_ven, INTERVAL 8 DAY) 
						ELSE c.fecha_ven 
						END > DATE(NOW()) 
						AND c.vendedor = '08' 
						AND c.tipo_doc <>'85'
					GROUP BY c.vendedor 
					ORDER BY fecha_ven");

			$stmt->bindParam(":vendedor", $vendedor, PDO::PARAM_STR);

			$stmt->execute();

			return $stmt->fetchAll();
		} else {

			$stmt = Conexion::conectar()->prepare("SELECT 
					c.tipo_doc,
					c.num_cta,
					c.fecha,
					c.fecha_ven,
					CASE
					WHEN c.tipo_doc = '85' 
					THEN DATE_ADD(c.fecha_ven, INTERVAL 8 DAY) 
					ELSE c.fecha_ven 
					END AS fec_ven2,
					c.doc_origen,
					c.cliente,
					cc.nombre,
					c.saldo,
					cc.ubigeo,
					(SELECT 
					nombre 
					FROM
					ubigeo u 
					WHERE cc.ubigeo = u.codigo) AS nom_ubigeo,
					CASE
					WHEN c.protesta = '1' 
					THEN 'SI' 
					ELSE '' 
					END AS protesta 
				FROM
					cuenta_ctejf c 
					LEFT JOIN clientesjf cc 
					ON c.cliente = cc.codigo 
				WHERE c.tip_mov = '+' 
					AND c.estado = 'PENDIENTE' 
					AND 
					CASE
					WHEN c.tipo_doc = '85' 
					THEN DATE_ADD(c.fecha_ven, INTERVAL 8 DAY) 
					ELSE c.fecha_ven 
					END > DATE(NOW()) 
					AND c.vendedor = :vendedor 
					AND c.tipo_doc <>'85'
				UNION
				SELECT 
					'ZZ' AS tipo_doc,
					'' AS num_cta,
					'' AS fecha,
					'' AS fecha_ven,
					'' AS fec_ven2,
					'' AS doc_origen,
					'' cliente,
					'' AS nombre,
					SUM(c.saldo) AS saldo,
					'ZZ' AS ubigeo,
					'' AS nom_ubigeo,
					'' AS protesta 
				FROM
					cuenta_ctejf c 
					LEFT JOIN clientesjf cc 
					ON c.cliente = cc.codigo 
				WHERE c.tip_mov = '+' 
					AND c.estado = 'PENDIENTE' 
					AND 
					CASE
					WHEN c.tipo_doc = '85' 
					THEN DATE_ADD(c.fecha_ven, INTERVAL 8 DAY) 
					ELSE c.fecha_ven 
					END > DATE(NOW()) 
					AND c.vendedor = :vendedor 
					AND c.tipo_doc <>'85'
				GROUP BY c.vendedor 
				ORDER BY ubigeo,
					cliente,
					fecha_ven");

			$stmt->bindParam(":vendedor", $vendedor, PDO::PARAM_STR);

			$stmt->execute();

			return $stmt->fetchAll();
		}

		$stmt->close();

		$stmt = null;
	}

	//* DOCUMENTOS ESTADO DE CUENTA
	static public function mdlEstadoCuenta($fin)
	{

		$stmt = Conexion::conectar()->prepare("SELECT 
						'A' AS orden,
						'' AS tipo_doc,
						'' AS num_cta,
						cc.cliente,
						c.nombre AS nom_cliente,
						DATE_SUB(:fin, INTERVAL 1 DAY) AS fecha,
						'' AS fecha_ven,
						'' AS doc_origen,
						'' AS tip_mov,
						ROUND(SUM(cc.monto - IFNULL(c1.monto, 0)), 2) AS monto 
					FROM
						cuenta_ctejf cc 
						LEFT JOIN 
						(SELECT 
							cc.tipo_doc,
							cc.num_cta,
							SUM(cc.monto) AS monto 
						FROM
							cuenta_ctejf cc 
						WHERE cc.tip_mov = '-' 
							AND cc.fecha <= DATE_SUB(:fin, INTERVAL 1 DAY) 
						GROUP BY cc.tipo_doc,
							cc.num_cta) AS c1 
						ON cc.tipo_doc = c1.tipo_doc 
						AND cc.num_cta = c1.num_cta 
						LEFT JOIN clientesjf AS c 
						ON cc.cliente = c.codigo 
					WHERE cc.tip_mov = '+' 
						AND cc.fecha <= DATE_SUB(:fin, INTERVAL 1 DAY) 
					GROUP BY c.codigo 
					UNION
					SELECT 
						'B' AS orden,
						cc.tipo_doc,
						cc.num_cta,
						cc.cliente,
						c.nombre AS nom_cliente,
						cc.fecha,
						cc.fecha_ven,
						cc.doc_origen,
						cc.tip_mov,
						ROUND(cc.monto, 2) AS monto 
					FROM
						cuenta_ctejf cc 
						LEFT JOIN clientesjf c 
						ON cc.cliente = c.codigo 
					WHERE cc.fecha >= DATE_SUB(:fin, INTERVAL 1 DAY) 
					UNION
					SELECT 
						'C' AS orden,
						'' AS tipo_doc,
						'' AS num_cta,
						cc.cliente,
						'TOTAL' AS nom_cliente,
						'' AS fecha,
						'' AS fecha_ven,
						'' AS doc_origen,
						'' AS tip_mov,
						ROUND(SUM(saldo), 2) AS saldo 
					FROM
						cuenta_ctejf cc 
					WHERE cc.tip_mov = '+' 
					GROUP BY cc.cliente 
					ORDER BY cliente,
						orden,
						tipo_doc,
						num_cta,
						fecha,
						tip_mov");

		$stmt->bindParam(":fin", $fin, PDO::PARAM_STR);

		$stmt->execute();

		return $stmt->fetchAll();

		$stmt->close();

		$stmt = null;
	}

	//* ESTADO DE CEUNTA DETALLE PROTESTO
	static public function mdlControlFechas()
	{

		$stmt = Conexion::conectar()->prepare("SELECT 
							MIN(fecha) AS inicio,
							MAX(fecha) AS fin
						FROM
							cuenta_ctejf c 
						WHERE c.tip_mov = '-' 
							AND c.cod_pago = '82' 
							AND YEAR(c.fecha) >= '2021'");


		$stmt->execute();

		return $stmt->fetch();

		$stmt->close();

		$stmt = null;
	}

	static public function mdlUltPagos($cliente)
	{


		$stmt = Conexion::conectar()->prepare("SELECT 
		@i := @i + 1 AS contador, 
		  YEAR(c.fecha) AS anno,
		  CASE
			WHEN MONTH(c.fecha) = 1 
			THEN 'ENERO' 
			WHEN MONTH(c.fecha) = 2 
			THEN 'FEBRERO' 
			WHEN MONTH(c.fecha) = 3 
			THEN 'MARZO' 
			WHEN MONTH(c.fecha) = 4 
			THEN 'ABRIL' 
			WHEN MONTH(c.fecha) = 5 
			THEN 'MAYO' 
			WHEN MONTH(c.fecha) = 6 
			THEN 'JUNIO' 
			WHEN MONTH(c.fecha) = 7 
			THEN 'JULIO' 
			WHEN MONTH(c.fecha) = 8 
			THEN 'AGOSTO' 
			WHEN MONTH(c.fecha) = 9 
			THEN 'SEPTIEMBRE' 
			WHEN MONTH(c.fecha) = 10 
			THEN 'OCTUBRE' 
			WHEN MONTH(c.fecha) = 11 
			THEN 'NOVIEMBRE' 
			ELSE 'DICIEMBRE' 
		  END AS mes,
		  FORMAT(SUM(c.monto),2) AS monto,
		  FORMAT(SUM(CASE WHEN TRIM(c.vendedor) IN ('00', '00B', '00b', '04', '05', '19', '22', '27') THEN c.monto ELSE 0 END),2) AS monto_jackyform,
		  FORMAT(SUM(CASE WHEN TRIM(c.vendedor) IN ('24', '26', '28') THEN c.monto ELSE 0 END),2) AS monto_rosalinda
		FROM
		  cuenta_ctejf c 
		  CROSS JOIN (SELECT @i := 0) r
		WHERE c.tip_mov = '-' 
		  AND c.fecha BETWEEN DATE_SUB(NOW(), INTERVAL 7 MONTH) 
		  AND NOW() 
		  AND c.cliente = :cliente 
		  AND c.cod_pago IN ('00', '05', '06', '14', '80', '82') 
		GROUP BY YEAR(c.fecha),
		  MONTH(c.fecha) 
		ORDER BY YEAR(c.fecha) DESC,
		  MONTH(c.fecha) DESC
		  LIMIT 6");

		$stmt->bindParam(":cliente", $cliente, PDO::PARAM_STR);

		$stmt->execute();

		return $stmt->fetchAll();

		$stmt->close();

		$stmt = null;
	}

	static public function mdlSaldoFecha($inicio, $fin)
	{


		$stmt = Conexion::conectar()->prepare("SELECT DISTINCT 
							'00' AS tipo_doc,
							'' AS num_cta,
							'' AS doc_origen,
							'' AS fecha,
							'' AS fecha_ven,
							'' AS monto,
							'' AS saldo,
							cc.cliente,
							c.nombre,
							'' AS documento,
							'' AS vendedor,
							'' AS estado,
							'' AS saldoFecha 
						FROM
							cuenta_ctejf cc 
							LEFT JOIN 
							(SELECT 
								cc.tipo_doc,
								cc.num_cta,
								SUM(cc.monto) AS monto 
							FROM
								cuenta_ctejf cc 
							WHERE cc.tip_mov = '-' 
								AND cc.fecha <= '$fin' 
							GROUP BY cc.tipo_doc,
								cc.num_cta) AS c1 
							ON cc.tipo_doc = c1.tipo_doc 
							AND cc.num_cta = c1.num_cta 
							LEFT JOIN clientesjf c 
							ON cc.cliente = c.codigo 
						WHERE cc.tip_mov = '+' 
							AND cc.fecha BETWEEN '$inicio' 
							AND '$fin' 
							AND (cc.monto - IFNULL(c1.monto, 0)) > 0 
						UNION
						SELECT 
							cc.tipo_doc,
							cc.num_cta,
							cc.doc_origen,
							cc.fecha,
							cc.fecha_ven,
							cc.monto,
							cc.saldo,
							cc.cliente,
							c.nombre,
							c.documento,
							cc.vendedor,
							cc.estado,
							(cc.monto - IFNULL(c1.monto, 0)) AS saldoFecha 
						FROM
							cuenta_ctejf cc 
							LEFT JOIN 
							(SELECT 
								cc.tipo_doc,
								cc.num_cta,
								SUM(cc.monto) AS monto 
							FROM
								cuenta_ctejf cc 
							WHERE cc.tip_mov = '-' 
								AND cc.fecha <= '$fin' 
							GROUP BY cc.tipo_doc,
								cc.num_cta) AS c1 
							ON cc.tipo_doc = c1.tipo_doc 
							AND cc.num_cta = c1.num_cta 
							LEFT JOIN clientesjf c 
							ON cc.cliente = c.codigo 
						WHERE cc.tip_mov = '+' 
							AND cc.fecha BETWEEN '$inicio' 
							AND '$fin' 
							AND (cc.monto - IFNULL(c1.monto, 0)) > 0 
						UNION
						SELECT 
							'99' AS tipo_doc,
							'' AS num_cta,
							'' AS doc_origen,
							'' AS fecha,
							'' AS fecha_ven,
							'' AS monto,
							'' AS saldo,
							cc.cliente,
							'' AS nombre,
							'' AS documento,
							'' AS vendedor,
							'' AS estado,
							SUM(cc.monto - IFNULL(c1.monto, 0)) AS saldoFecha 
						FROM
							cuenta_ctejf cc 
							LEFT JOIN 
							(SELECT 
								cc.tipo_doc,
								cc.num_cta,
								SUM(cc.monto) AS monto 
							FROM
								cuenta_ctejf cc 
							WHERE cc.tip_mov = '-' 
								AND cc.fecha <= '$fin' 
							GROUP BY cc.tipo_doc,
								cc.num_cta) AS c1 
							ON cc.tipo_doc = c1.tipo_doc 
							AND cc.num_cta = c1.num_cta 
							LEFT JOIN clientesjf c 
							ON cc.cliente = c.codigo 
						WHERE cc.tip_mov = '+' 
							AND cc.fecha BETWEEN '$inicio' 
							AND '$fin' 
							AND (cc.monto - IFNULL(c1.monto, 0)) > 0 
						GROUP BY cc.cliente 
						ORDER BY cliente,
								tipo_doc,
								fecha,
								num_cta");


		$stmt->execute();

		return $stmt->fetchAll();

		$stmt->close();

		$stmt = null;
	}

	static public function ctrVerCredipagos()
	{

		$stmt = Conexion::conectar()
			->prepare("SELECT 
				cc.id ,
				cc.tipo_doc,
				cc.num_cta,
				cc.doc_origen,
				cc.fecha,
				cc.monto,
				cc.cliente,
				c.nombre,
				cc.vendedor,
				cc.notas
			from
				cuenta_ctejf cc
				left join clientesjf c 
				on cc.cliente=c.codigo
			where
				cc.tip_mov = '-'
				and cc.cod_pago = '82'
			and cc.fecha > '2015-12-31'");


		$stmt->execute();

		return $stmt->fetchAll();

		$stmt->close();

		$stmt = null;
	}

	/*=============================================
	RANGO FECHAS CUENTAS CON PAGINACIÓN SERVIDOR
	=============================================*/

	static public function mdlRangoFechasCuentasPaginado($tabla, $ano, $start, $length, $search, $orderColumn, $orderDir)
	{
		// Construir WHERE clause
		$where = "c.tip_mov = '+'";

		if ($ano != "null" && $ano != null) {
			$where .= " AND YEAR(c.fecha) = :ano";
		}

		// Construir búsqueda
		$searchWhere = "";
		if (!empty($search)) {
			$searchWhere = " AND (
				c.num_cta LIKE :search1 OR 
				c.cliente LIKE :search2 OR 
				cli.nombre LIKE :search3 OR 
				c.vendedor LIKE :search4 OR 
				c.tipo_doc LIKE :search5 OR
				c.doc_origen LIKE :search6 OR
				c.num_unico LIKE :search7
			)";
		}

		// Mapeo de columnas para ordenamiento
		$columns = [
			0 => "c.tipo_doc",
			1 => "c.num_cta",
			2 => "CONCAT(c.cliente, ' - ', cli.nombre)",
			3 => "c.vendedor",
			4 => "c.fecha",
			5 => "c.fecha_ven",
			6 => "c.monto",
			7 => "c.saldo",
			8 => "c.estado",
			9 => "c.num_unico",
			10 => "c.protesta",
			11 => "c.doc_origen"
		];

		$orderBy = isset($columns[$orderColumn]) ? $columns[$orderColumn] : "c.fecha";
		$orderDir = strtoupper($orderDir) == "ASC" ? "ASC" : "DESC";

		// Consulta para obtener total de registros
		$sqlTotal = "SELECT COUNT(*) as total 
			FROM $tabla c 
			LEFT JOIN clientesjf cli ON c.cliente = cli.codigo 
			WHERE $where";

		$stmtTotal = Conexion::conectar()->prepare($sqlTotal);
		if ($ano != "null" && $ano != null) {
			$stmtTotal->bindParam(":ano", $ano, PDO::PARAM_STR);
		}
		$stmtTotal->execute();
		$totalRecords = $stmtTotal->fetch(PDO::FETCH_ASSOC)["total"];

		// Consulta para obtener total filtrado
		$sqlFiltered = "SELECT COUNT(*) as total 
			FROM $tabla c 
			LEFT JOIN clientesjf cli ON c.cliente = cli.codigo 
			WHERE $where $searchWhere";

		$stmtFiltered = Conexion::conectar()->prepare($sqlFiltered);
		if ($ano != "null" && $ano != null) {
			$stmtFiltered->bindParam(":ano", $ano, PDO::PARAM_STR);
		}
		if (!empty($search)) {
			$searchParam = "%$search%";
			$stmtFiltered->bindParam(":search1", $searchParam, PDO::PARAM_STR);
			$stmtFiltered->bindParam(":search2", $searchParam, PDO::PARAM_STR);
			$stmtFiltered->bindParam(":search3", $searchParam, PDO::PARAM_STR);
			$stmtFiltered->bindParam(":search4", $searchParam, PDO::PARAM_STR);
			$stmtFiltered->bindParam(":search5", $searchParam, PDO::PARAM_STR);
			$stmtFiltered->bindParam(":search6", $searchParam, PDO::PARAM_STR);
			$stmtFiltered->bindParam(":search7", $searchParam, PDO::PARAM_STR);
		}
		$stmtFiltered->execute();
		$filteredRecords = $stmtFiltered->fetch(PDO::FETCH_ASSOC)["total"];

		// Consulta principal con paginación
		$sql = "SELECT 
			c.id,
			c.tipo_doc,
			c.num_cta,
			c.cod_pago,
			c.doc_origen,
			c.fecha,
			c.fecha_ven,
			c.monto,
			c.saldo,
			c.tip_cambio,
			c.ult_pago,
			c.cliente,
			cli.nombre,
			c.vendedor,
			c.fecha_cep,
			c.banco,
			c.num_unico,
			c.renovacion,
			c.protesta,
			c.tip_mon,
			c.estado,
			c.estado_doc,
			c.fecha_envio,
			c.fecha_creacion
		FROM $tabla c
		LEFT JOIN clientesjf cli ON c.cliente = cli.codigo
		WHERE $where $searchWhere
		ORDER BY $orderBy $orderDir
		LIMIT :start, :length";

		$stmt = Conexion::conectar()->prepare($sql);

		if ($ano != "null" && $ano != null) {
			$stmt->bindParam(":ano", $ano, PDO::PARAM_STR);
		}
		if (!empty($search)) {
			$searchParam = "%$search%";
			$stmt->bindParam(":search1", $searchParam, PDO::PARAM_STR);
			$stmt->bindParam(":search2", $searchParam, PDO::PARAM_STR);
			$stmt->bindParam(":search3", $searchParam, PDO::PARAM_STR);
			$stmt->bindParam(":search4", $searchParam, PDO::PARAM_STR);
			$stmt->bindParam(":search5", $searchParam, PDO::PARAM_STR);
			$stmt->bindParam(":search6", $searchParam, PDO::PARAM_STR);
			$stmt->bindParam(":search7", $searchParam, PDO::PARAM_STR);
		}
		$stmt->bindParam(":start", $start, PDO::PARAM_INT);
		$stmt->bindParam(":length", $length, PDO::PARAM_INT);

		$stmt->execute();
		$data = $stmt->fetchAll(PDO::FETCH_ASSOC);

		return [
			"data" => $data,
			"recordsTotal" => (int)$totalRecords,
			"recordsFiltered" => (int)$filteredRecords
		];
	}
}
