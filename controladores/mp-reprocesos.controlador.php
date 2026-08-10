<?php

class ControladorMpReprocesos
{
	static private function asegurarTimezone()
	{
		date_default_timezone_set("America/Lima");
	}

	static private function usuarioActual()
	{
		if (isset($_SESSION["nombre"]) && trim((string) $_SESSION["nombre"]) !== "") {
			return (string) $_SESSION["nombre"];
		}
		if (isset($_SESSION["usuario"]) && trim((string) $_SESSION["usuario"]) !== "") {
			return (string) $_SESSION["usuario"];
		}
		return "";
	}

	static private function procesosActivosMap()
	{
		$config = ModeloMpReprocesos::mdlLeerConfig();
		$map = array();
		$lista = isset($config["procesos"]) && is_array($config["procesos"]) ? $config["procesos"] : array();
		foreach ($lista as $p) {
			if (!is_array($p) || empty($p["codigo"])) {
				continue;
			}
			$activo = !isset($p["activo"]) || $p["activo"];
			if (!$activo) {
				continue;
			}
			$map[(string) $p["codigo"]] = array(
				"codigo" => (string) $p["codigo"],
				"etiqueta" => isset($p["etiqueta"]) ? (string) $p["etiqueta"] : (string) $p["codigo"],
				"descripcion" => isset($p["descripcion"]) ? (string) $p["descripcion"] : ""
			);
		}
		return $map;
	}

	static public function ctrProcesos()
	{
		return array(
			"ok" => true,
			"procesos" => array_values(self::procesosActivosMap())
		);
	}

	static public function ctrListar()
	{
		$procesos = self::procesosActivosMap();
		$filas = ModeloMpReprocesos::mdlListar();
		$items = array();

		foreach ($filas as $row) {
			$codigoProceso = isset($row["proceso"]) ? (string) $row["proceso"] : "";
			$items[] = array(
				"id" => (string) $row["id"],
				"cod_pro_origen" => (string) $row["cod_pro_origen"],
				"cod_fab_origen" => (string) $row["cod_fab_origen"],
				"des_origen" => (string) $row["des_origen"],
				"color_origen" => (string) $row["color_origen"],
				"proceso" => $codigoProceso,
				"proceso_etiqueta" => isset($procesos[$codigoProceso])
					? $procesos[$codigoProceso]["etiqueta"]
					: $codigoProceso,
				"cod_pro_destino" => (string) $row["cod_pro_destino"],
				"cod_fab_destino" => (string) $row["cod_fab_destino"],
				"des_destino" => (string) $row["des_destino"],
				"color_destino" => (string) $row["color_destino"],
				"observacion" => (string) $row["observacion"],
				"activo" => (int) $row["activo"] === 1,
				"usu_reg" => (string) $row["usu_reg"],
				"fec_reg" => $row["fec_reg"],
				"usu_mod" => (string) $row["usu_mod"],
				"fec_mod" => $row["fec_mod"]
			);
		}

		return array("ok" => true, "items" => $items);
	}

	static public function ctrBuscarMp($post)
	{
		$termino = isset($post["termino"]) ? trim((string) $post["termino"]) : "";
		if ($termino === "") {
			return array("ok" => false, "mensaje" => "Ingrese código o descripción");
		}

		$resultado = ModeloMpReprocesos::mdlBuscarMp($termino);
		if ($resultado === null) {
			return array("ok" => false, "mensaje" => "Sin resultados");
		}

		if (isset($resultado["codpro"])) {
			return array("ok" => true, "mp" => $resultado, "opciones" => array());
		}

		if (is_array($resultado) && count($resultado) === 1) {
			return array("ok" => true, "mp" => $resultado[0], "opciones" => array());
		}

		if (is_array($resultado) && count($resultado) > 1) {
			return array("ok" => true, "mp" => null, "opciones" => $resultado);
		}

		return array("ok" => false, "mensaje" => "No se encontró materia prima");
	}

	static public function ctrGuardar($post)
	{
		self::asegurarTimezone();

		$id = isset($post["id"]) ? trim((string) $post["id"]) : "";
		$codOrigen = isset($post["cod_pro_origen"]) ? trim((string) $post["cod_pro_origen"]) : "";
		$codDestino = isset($post["cod_pro_destino"]) ? trim((string) $post["cod_pro_destino"]) : "";
		$proceso = isset($post["proceso"]) ? trim((string) $post["proceso"]) : "";
		$observacion = isset($post["observacion"]) ? trim((string) $post["observacion"]) : "";

		$procesos = self::procesosActivosMap();
		if ($codOrigen === "" || $codDestino === "" || $proceso === "") {
			return array("ok" => false, "mensaje" => "Complete MP origen, proceso y MP destino");
		}
		if (!isset($procesos[$proceso])) {
			return array("ok" => false, "mensaje" => "Proceso no válido");
		}
		if ($codOrigen === $codDestino) {
			return array("ok" => false, "mensaje" => "Origen y destino deben ser distintos");
		}

		$mpOrigen = ModeloMpReprocesos::mdlObtenerMpPorCodPro($codOrigen);
		$mpDestino = ModeloMpReprocesos::mdlObtenerMpPorCodPro($codDestino);
		if (!$mpOrigen) {
			return array("ok" => false, "mensaje" => "MP origen no encontrada o inactiva");
		}
		if (!$mpDestino) {
			return array("ok" => false, "mensaje" => "MP destino no encontrada o inactiva");
		}

		if (ModeloMpReprocesos::mdlExisteDuplicado($codOrigen, $proceso, $codDestino, $id !== "" ? $id : null)) {
			return array("ok" => false, "mensaje" => "Ya existe esa relación origen + proceso + destino");
		}

		$ahora = date("Y-m-d H:i:s");
		$usuario = self::usuarioActual();

		$datos = array(
			"cod_pro_origen" => (string) $mpOrigen["codpro"],
			"proceso" => $proceso,
			"cod_pro_destino" => (string) $mpDestino["codpro"],
			"observacion" => $observacion,
			"usu_mod" => $usuario,
			"fec_mod" => $ahora
		);

		if ($id !== "") {
			$existente = ModeloMpReprocesos::mdlObtenerPorId($id);
			if (!$existente || (int) $existente["estado"] !== 1) {
				return array("ok" => false, "mensaje" => "Registro a editar no encontrado");
			}
			$datos["id"] = $id;
			if (!ModeloMpReprocesos::mdlActualizar($datos)) {
				return array("ok" => false, "mensaje" => "No se pudo actualizar");
			}
			return array("ok" => true, "mensaje" => "Actualizado", "id" => $id);
		}

		$datos["usu_reg"] = $usuario;
		$datos["fec_reg"] = $ahora;

		$prev = ModeloMpReprocesos::mdlBuscarIncluyendoInactivo(
			$datos["cod_pro_origen"],
			$datos["proceso"],
			$datos["cod_pro_destino"]
		);
		if ($prev && (int) $prev["estado"] === 0) {
			$datos["id"] = $prev["id"];
			if (!ModeloMpReprocesos::mdlReactivar($datos)) {
				return array("ok" => false, "mensaje" => "No se pudo reactivar el registro");
			}
			return array("ok" => true, "mensaje" => "Registrado", "id" => (string) $prev["id"]);
		}

		$nuevoId = ModeloMpReprocesos::mdlInsertar($datos);
		if (!$nuevoId) {
			return array("ok" => false, "mensaje" => "No se pudo registrar (¿tabla creada?)");
		}

		return array("ok" => true, "mensaje" => "Registrado", "id" => (string) $nuevoId);
	}

	static public function ctrGuardarLote($post)
	{
		self::asegurarTimezone();

		$codOrigen = isset($post["cod_pro_origen"]) ? trim((string) $post["cod_pro_origen"]) : "";
		$proceso = isset($post["proceso"]) ? trim((string) $post["proceso"]) : "";
		$observacion = isset($post["observacion"]) ? trim((string) $post["observacion"]) : "";
		$destinosRaw = isset($post["destinos"]) ? $post["destinos"] : "[]";

		if (is_string($destinosRaw)) {
			$destinos = json_decode($destinosRaw, true);
		} else {
			$destinos = $destinosRaw;
		}
		if (!is_array($destinos)) {
			$destinos = array();
		}

		$codigos = array();
		foreach ($destinos as $d) {
			$cod = is_array($d)
				? (isset($d["codpro"]) ? trim((string) $d["codpro"]) : "")
				: trim((string) $d);
			if ($cod !== "") {
				$codigos[$cod] = true;
			}
		}
		$codigos = array_keys($codigos);

		$procesos = self::procesosActivosMap();
		if ($codOrigen === "" || $proceso === "" || !count($codigos)) {
			return array("ok" => false, "mensaje" => "Complete origen, proceso y al menos un destino");
		}
		if (!isset($procesos[$proceso])) {
			return array("ok" => false, "mensaje" => "Proceso no válido");
		}

		$mpOrigen = ModeloMpReprocesos::mdlObtenerMpPorCodPro($codOrigen);
		if (!$mpOrigen) {
			return array("ok" => false, "mensaje" => "MP origen no encontrada o inactiva");
		}

		$ahora = date("Y-m-d H:i:s");
		$usuario = self::usuarioActual();
		$ok = 0;
		$omitidos = 0;
		$errores = array();

		foreach ($codigos as $codDestino) {
			if ($codDestino === $codOrigen) {
				$omitidos++;
				$errores[] = $codDestino . ": igual al origen";
				continue;
			}

			$mpDestino = ModeloMpReprocesos::mdlObtenerMpPorCodPro($codDestino);
			if (!$mpDestino) {
				$omitidos++;
				$errores[] = $codDestino . ": no encontrada";
				continue;
			}

			if (ModeloMpReprocesos::mdlExisteDuplicado($codOrigen, $proceso, $codDestino, null)) {
				$omitidos++;
				continue;
			}

			$datos = array(
				"cod_pro_origen" => (string) $mpOrigen["codpro"],
				"proceso" => $proceso,
				"cod_pro_destino" => (string) $mpDestino["codpro"],
				"observacion" => $observacion,
				"usu_reg" => $usuario,
				"fec_reg" => $ahora,
				"usu_mod" => $usuario,
				"fec_mod" => $ahora
			);

			$prev = ModeloMpReprocesos::mdlBuscarIncluyendoInactivo(
				$datos["cod_pro_origen"],
				$datos["proceso"],
				$datos["cod_pro_destino"]
			);
			if ($prev && (int) $prev["estado"] === 0) {
				$datos["id"] = $prev["id"];
				if (ModeloMpReprocesos::mdlReactivar($datos)) {
					$ok++;
				} else {
					$omitidos++;
					$errores[] = $codDestino . ": no se pudo reactivar";
				}
				continue;
			}

			$nuevoId = ModeloMpReprocesos::mdlInsertar($datos);
			if ($nuevoId) {
				$ok++;
			} else {
				$omitidos++;
				$errores[] = $codDestino . ": error al guardar";
			}
		}

		if ($ok < 1) {
			$msg = "No se registró ninguna relación";
			if ($omitidos > 0) {
				$msg .= " (ya existían o no válidas)";
			}
			return array(
				"ok" => false,
				"mensaje" => $msg,
				"registrados" => $ok,
				"omitidos" => $omitidos,
				"errores" => $errores
			);
		}

		$mensaje = $ok === 1 ? "1 relación registrada" : ($ok . " relaciones registradas");
		if ($omitidos > 0) {
			$mensaje .= " (" . $omitidos . " omitida" . ($omitidos === 1 ? "" : "s") . ")";
		}

		return array(
			"ok" => true,
			"mensaje" => $mensaje,
			"registrados" => $ok,
			"omitidos" => $omitidos,
			"errores" => $errores
		);
	}

	static public function ctrEliminar($post)
	{
		self::asegurarTimezone();

		$id = isset($post["id"]) ? trim((string) $post["id"]) : "";
		if ($id === "") {
			return array("ok" => false, "mensaje" => "ID requerido");
		}

		if (!ModeloMpReprocesos::mdlEliminar($id, self::usuarioActual(), date("Y-m-d H:i:s"))) {
			return array("ok" => false, "mensaje" => "Registro no encontrado o no se pudo eliminar");
		}

		return array("ok" => true, "mensaje" => "Eliminado");
	}
}
