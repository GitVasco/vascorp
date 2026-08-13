<?php

class ControladorProyeccionComercialModelos
{
	static private function ctrPuede($accion = "ver")
	{
		return function_exists("usuarioPuedeModulo")
			&& usuarioPuedeModulo("gestion_comercial", "proyeccion_comercial_modelos", $accion);
	}

	static private function ctrPuedeVer()
	{
		return function_exists("usuarioPuedeVerModulo")
			&& usuarioPuedeVerModulo("gestion_comercial", "proyeccion_comercial_modelos");
	}

	static private function ctrUsuario()
	{
		return isset($_SESSION["id"]) ? (int) $_SESSION["id"] : 0;
	}

	static private function ctrModeloCodigo($post)
	{
		$modelo = trim(isset($post["modelo"]) ? $post["modelo"] : "");
		if ($modelo === "" || strlen($modelo) > 50 || !preg_match('/^[A-Za-z0-9._-]+$/', $modelo)) {
			return null;
		}
		return $modelo;
	}

	static private function ctrPeriodoPlan($post)
	{
		$desde = isset($post["desde"]) ? trim((string) $post["desde"]) : "";
		$hasta = isset($post["hasta"]) ? trim((string) $post["hasta"]) : "";
		if ($desde === "" && $hasta === "") {
			$anio = (int) date("Y");
			$mes = (int) date("n");
			$desde = sprintf("%04d-%02d", $anio, $mes);
			$fin = ModeloProyeccionComercialModelos::mdlSumarMeses($anio, $mes, 5);
			$hasta = sprintf("%04d-%02d", $fin["anio"], $fin["mes"]);
		} elseif ($desde === "" || $hasta === "") {
			return null;
		}
		return ModeloProyeccionComercialModelos::mdlConstruirPeriodoPlan($desde, $hasta);
	}

	static private function ctrExigeFiltroAcotado($post)
	{
		$idMarca = isset($post["id_marca"]) ? (int) $post["id_marca"] : 0;
		$q = trim(isset($post["q"]) ? $post["q"] : "");
		if ($idMarca <= 0 && strlen($q) < 2) {
			return "Elige una marca o escribe al menos 2 caracteres del modelo (evita cargar todo el catálogo).";
		}
		if (strlen($q) > 100) {
			return "Búsqueda inválida";
		}
		return null;
	}

	static public function ctrCatalogo($post)
	{
		if (!self::ctrPuedeVer()) {
			return array("ok" => false, "mensaje" => "Sin permiso");
		}
		$idMarca = isset($post["id_marca"]) ? (int) $post["id_marca"] : 0;
		$q = trim(isset($post["q"]) ? $post["q"] : "");
		if (strlen($q) > 100) {
			return array("ok" => false, "mensaje" => "Búsqueda inválida");
		}
		try {
			$catalogo = ModeloProyeccionComercialModelos::mdlCatalogo($idMarca, $q);
			return array(
				"ok" => true,
				"modelos" => $catalogo["modelos"],
				"marcas" => $catalogo["marcas"],
				"categorias" => $catalogo["categorias"],
				"permisos" => array(
					"ver" => true,
					"editar" => self::ctrPuede("editar"),
					"publicar" => self::ctrPuede("publicar"),
					"cerrar" => self::ctrPuede("cerrar"),
					"reabrir" => self::ctrPuede("reabrir")
				)
			);
		} catch (Exception $e) {
			return array("ok" => false, "mensaje" => "No se pudo cargar el catálogo");
		}
	}

	static public function ctrMatriz($post)
	{
		if (!self::ctrPuedeVer()) {
			return array("ok" => false, "mensaje" => "Sin permiso");
		}
		$filtroMsg = self::ctrExigeFiltroAcotado($post);
		if ($filtroMsg !== null) {
			return array("ok" => false, "mensaje" => $filtroMsg);
		}
		$periodo = self::ctrPeriodoPlan($post);
		if ($periodo === null) {
			return array(
				"ok" => false,
				"mensaje" => "Rango de plan inválido. Usa hasta 12 meses consecutivos y no más de 18 meses hacia adelante."
			);
		}
		$idMarca = isset($post["id_marca"]) ? (int) $post["id_marca"] : 0;
		$q = trim(isset($post["q"]) ? $post["q"] : "");
		$mesesHist = isset($post["meses_historial"]) ? (int) $post["meses_historial"] : 12;
		if ($mesesHist < 6) {
			$mesesHist = 6;
		}
		if ($mesesHist > 24) {
			$mesesHist = 24;
		}
		try {
			@set_time_limit(90);
			$matriz = ModeloProyeccionComercialModelos::mdlMatrizContexto(
				$periodo,
				$idMarca,
				$q,
				$mesesHist,
				40
			);
			return array(
				"ok" => true,
				"fase" => 1,
				"matriz" => $matriz,
				"consultado_en" => date("Y-m-d H:i:s")
			);
		} catch (Exception $e) {
			return array("ok" => false, "mensaje" => "No se pudo armar la matriz de contexto");
		}
	}

	static public function ctrContextoModelo($post)
	{
		if (!self::ctrPuedeVer()) {
			return array("ok" => false, "mensaje" => "Sin permiso");
		}
		$modelo = self::ctrModeloCodigo($post);
		$periodo = self::ctrPeriodoPlan($post);
		if ($modelo === null || $periodo === null) {
			return array("ok" => false, "mensaje" => "Modelo o rango de plan inválidos");
		}
		$mesesHist = isset($post["meses_historial"]) ? (int) $post["meses_historial"] : 12;
		if ($mesesHist < 6) {
			$mesesHist = 6;
		}
		if ($mesesHist > 24) {
			$mesesHist = 24;
		}
		try {
			@set_time_limit(60);
			$ctx = ModeloProyeccionComercialModelos::mdlContextoModelo($modelo, $periodo, $mesesHist);
			if ($ctx === null) {
				return array("ok" => false, "mensaje" => "El modelo no existe o no está activo");
			}
			return array(
				"ok" => true,
				"contexto" => $ctx,
				"consultado_en" => date("Y-m-d H:i:s")
			);
		} catch (Exception $e) {
			return array("ok" => false, "mensaje" => "No se pudo cargar el contexto del modelo");
		}
	}

	static public function ctrConciliar($post)
	{
		if (!self::ctrPuedeVer()) {
			return array("ok" => false, "mensaje" => "Sin permiso");
		}
		$modelo = self::ctrModeloCodigo($post);
		$anio = isset($post["anio"]) ? (int) $post["anio"] : 0;
		$mes = isset($post["mes"]) ? (int) $post["mes"] : 0;
		if ($modelo === null || $anio < 2021 || $mes < 1 || $mes > 12) {
			return array("ok" => false, "mensaje" => "Modelo o período inválidos para conciliar");
		}
		try {
			$resultado = ModeloProyeccionComercialModelos::mdlConciliarContraFicha($modelo, $anio, $mes);
			return array(
				"ok" => true,
				"conciliacion" => $resultado,
				"consultado_en" => date("Y-m-d H:i:s")
			);
		} catch (Exception $e) {
			return array("ok" => false, "mensaje" => "No se pudo conciliar contra la ficha gerencial");
		}
	}

	static public function ctrListarPlanes($post)
	{
		if (!self::ctrPuedeVer()) {
			return array("ok" => false, "mensaje" => "Sin permiso");
		}
		try {
			return array(
				"ok" => true,
				"planes" => ModeloProyeccionComercialModelos::mdlListarPeriodos(50),
				"permisos" => array(
					"editar" => self::ctrPuede("editar"),
					"publicar" => self::ctrPuede("publicar")
				)
			);
		} catch (Exception $e) {
			return array("ok" => false, "mensaje" => "No se pudieron listar los planes");
		}
	}

	static public function ctrCrearPlan($post)
	{
		if (!self::ctrPuede("editar")) {
			return array("ok" => false, "mensaje" => "Sin permiso para editar");
		}
		$desde = isset($post["desde"]) ? trim((string) $post["desde"]) : "";
		$hasta = isset($post["hasta"]) ? trim((string) $post["hasta"]) : "";
		$nombre = isset($post["nombre"]) ? trim((string) $post["nombre"]) : "";
		try {
			return ModeloProyeccionComercialModelos::mdlCrearPeriodo(
				$desde,
				$hasta,
				$nombre,
				self::ctrUsuario()
			);
		} catch (Exception $e) {
			return array("ok" => false, "mensaje" => "No se pudo crear el plan");
		}
	}

	static public function ctrEliminarPlan($post)
	{
		if (!self::ctrPuede("editar")) {
			return array("ok" => false, "mensaje" => "Sin permiso para editar");
		}
		$idPeriodo = isset($post["id_periodo"]) ? (int) $post["id_periodo"] : 0;
		if ($idPeriodo <= 0) {
			return array("ok" => false, "mensaje" => "Plan inválido");
		}
		try {
			return ModeloProyeccionComercialModelos::mdlEliminarPeriodo(
				$idPeriodo,
				self::ctrUsuario()
			);
		} catch (Exception $e) {
			return array("ok" => false, "mensaje" => "No se pudo eliminar el plan");
		}
	}

	static public function ctrGenerarLineas($post)
	{
		if (!self::ctrPuede("editar")) {
			return array("ok" => false, "mensaje" => "Sin permiso para editar");
		}
		$filtroMsg = self::ctrExigeFiltroAcotado($post);
		if ($filtroMsg !== null) {
			return array("ok" => false, "mensaje" => $filtroMsg);
		}
		$idPeriodo = isset($post["id_periodo"]) ? (int) $post["id_periodo"] : 0;
		if ($idPeriodo <= 0) {
			return array("ok" => false, "mensaje" => "Plan inválido");
		}
		$idMarca = isset($post["id_marca"]) ? (int) $post["id_marca"] : 0;
		$q = trim(isset($post["q"]) ? $post["q"] : "");
		try {
			@set_time_limit(120);
			return ModeloProyeccionComercialModelos::mdlGenerarLineasPeriodo(
				$idPeriodo,
				$idMarca,
				$q,
				self::ctrUsuario(),
				40
			);
		} catch (Exception $e) {
			return array("ok" => false, "mensaje" => "No se pudieron generar las líneas");
		}
	}

	static public function ctrCargarPlan($post)
	{
		if (!self::ctrPuedeVer()) {
			return array("ok" => false, "mensaje" => "Sin permiso");
		}
		$idPeriodo = isset($post["id_periodo"]) ? (int) $post["id_periodo"] : 0;
		if ($idPeriodo <= 0) {
			return array("ok" => false, "mensaje" => "Plan inválido");
		}
		$anio = isset($post["anio"]) ? (int) $post["anio"] : 0;
		$mes = isset($post["mes"]) ? (int) $post["mes"] : 0;
		$q = trim(isset($post["q"]) ? $post["q"] : "");
		try {
			$cab = ModeloProyeccionComercialModelos::mdlObtenerPeriodo($idPeriodo);
			if (!$cab) {
				return array("ok" => false, "mensaje" => "Plan no encontrado");
			}
			$lineas = ModeloProyeccionComercialModelos::mdlListarLineasPeriodo($idPeriodo, $anio, $mes, $q);
			$stats = ModeloProyeccionComercialModelos::mdlEstadisticasPeriodo($idPeriodo);
			$proyectados = ModeloProyeccionComercialModelos::mdlModelosProyectadosPeriodo($idPeriodo);
			return array(
				"ok" => true,
				"plan" => $cab,
				"lineas" => $lineas,
				"stats" => $stats,
				"modelos_proyectados" => $proyectados,
				"permisos" => array(
					"editar" => self::ctrPuede("editar"),
					"publicar" => self::ctrPuede("publicar")
				),
				"consultado_en" => date("Y-m-d H:i:s")
			);
		} catch (Exception $e) {
			return array("ok" => false, "mensaje" => "No se pudo cargar el plan");
		}
	}

	static public function ctrGuardarLineas($post)
	{
		if (!self::ctrPuede("editar")) {
			return array("ok" => false, "mensaje" => "Sin permiso para editar");
		}
		$idPeriodo = isset($post["id_periodo"]) ? (int) $post["id_periodo"] : 0;
		$motivo = isset($post["motivo"]) ? trim((string) $post["motivo"]) : "";
		$raw = isset($post["cambios"]) ? $post["cambios"] : "[]";
		if (is_string($raw)) {
			$cambios = json_decode($raw, true);
		} else {
			$cambios = $raw;
		}
		if ($idPeriodo <= 0 || !is_array($cambios)) {
			return array("ok" => false, "mensaje" => "Datos inválidos");
		}
		try {
			return ModeloProyeccionComercialModelos::mdlGuardarLineas(
				$idPeriodo,
				$cambios,
				self::ctrUsuario(),
				$motivo
			);
		} catch (Exception $e) {
			return array("ok" => false, "mensaje" => "No se pudieron guardar las líneas");
		}
	}

	static public function ctrPublicar($post)
	{
		if (!self::ctrPuede("publicar")) {
			return array("ok" => false, "mensaje" => "Sin permiso para publicar");
		}
		$idPeriodo = isset($post["id_periodo"]) ? (int) $post["id_periodo"] : 0;
		$anio = isset($post["anio"]) ? (int) $post["anio"] : 0;
		$mes = isset($post["mes"]) ? (int) $post["mes"] : 0;
		$modelo = isset($post["modelo"]) ? trim((string) $post["modelo"]) : "";
		if ($idPeriodo <= 0) {
			return array("ok" => false, "mensaje" => "Plan inválido");
		}
		if ($modelo !== "" && !preg_match('/^[A-Za-z0-9._-]+$/', $modelo)) {
			return array("ok" => false, "mensaje" => "Modelo inválido");
		}
		try {
			return ModeloProyeccionComercialModelos::mdlPublicarLineas(
				$idPeriodo,
				$anio,
				$mes,
				self::ctrUsuario(),
				$modelo
			);
		} catch (Exception $e) {
			return array("ok" => false, "mensaje" => "No se pudo publicar");
		}
	}

	static public function ctrAuditoria($post)
	{
		if (!self::ctrPuedeVer()) {
			return array("ok" => false, "mensaje" => "Sin permiso");
		}
		$idLinea = isset($post["id_linea"]) ? (int) $post["id_linea"] : 0;
		if ($idLinea <= 0) {
			return array("ok" => false, "mensaje" => "Línea inválida");
		}
		try {
			return array(
				"ok" => true,
				"auditoria" => ModeloProyeccionComercialModelos::mdlAuditoriaLinea($idLinea, 80)
			);
		} catch (Exception $e) {
			return array("ok" => false, "mensaje" => "No se pudo cargar la auditoría");
		}
	}

	static public function ctrConsultaOficial($post)
	{
		if (!self::ctrPuedeVer()) {
			return array("ok" => false, "mensaje" => "Sin permiso");
		}
		$desde = isset($post["desde"]) ? trim((string) $post["desde"]) : "";
		$hasta = isset($post["hasta"]) ? trim((string) $post["hasta"]) : "";
		$q = trim(isset($post["q"]) ? $post["q"] : "");
		try {
			return ModeloProyeccionComercialModelos::mdlConsultaOficial($desde, $hasta, $q, 500);
		} catch (Exception $e) {
			return array("ok" => false, "mensaje" => "No se pudo consultar lo oficial");
		}
	}

	static public function ctrTiposFactor($post)
	{
		if (!self::ctrPuedeVer()) {
			return array("ok" => false, "mensaje" => "Sin permiso");
		}
		return array(
			"ok" => true,
			"tipos" => ModeloProyeccionComercialModelos::mdlTiposFactor(),
			"umbral_pct" => ModeloProyeccionComercialModelos::UMBRAL_DESVIACION_PCT,
			"umbral_abs" => ModeloProyeccionComercialModelos::UMBRAL_DESVIACION_ABS
		);
	}

	static public function ctrListarFactores($post)
	{
		if (!self::ctrPuedeVer()) {
			return array("ok" => false, "mensaje" => "Sin permiso");
		}
		$idLinea = isset($post["id_linea"]) ? (int) $post["id_linea"] : 0;
		if ($idLinea <= 0) {
			return array("ok" => false, "mensaje" => "Línea inválida");
		}
		try {
			$detalle = ModeloProyeccionComercialModelos::mdlListarFactores($idLinea);
			if (!$detalle["linea"]) {
				return array("ok" => false, "mensaje" => "Línea no encontrada");
			}
			return array("ok" => true, "detalle" => $detalle);
		} catch (Exception $e) {
			return array("ok" => false, "mensaje" => "No se pudieron listar los factores");
		}
	}

	static public function ctrGuardarFactor($post)
	{
		if (!self::ctrPuede("editar")) {
			return array("ok" => false, "mensaje" => "Sin permiso para editar");
		}
		$idLinea = isset($post["id_linea"]) ? (int) $post["id_linea"] : 0;
		if ($idLinea <= 0) {
			return array("ok" => false, "mensaje" => "Línea inválida");
		}
		$motivo = isset($post["motivo"]) ? trim((string) $post["motivo"]) : "";
		try {
			return ModeloProyeccionComercialModelos::mdlGuardarFactor(
				$idLinea,
				$post,
				self::ctrUsuario(),
				$motivo
			);
		} catch (Exception $e) {
			return array("ok" => false, "mensaje" => "No se pudo guardar el factor");
		}
	}

	static public function ctrEliminarFactor($post)
	{
		if (!self::ctrPuede("editar")) {
			return array("ok" => false, "mensaje" => "Sin permiso para editar");
		}
		$idFactor = isset($post["id_factor"]) ? (int) $post["id_factor"] : 0;
		$motivo = isset($post["motivo"]) ? trim((string) $post["motivo"]) : "";
		if ($idFactor <= 0) {
			return array("ok" => false, "mensaje" => "Factor inválido");
		}
		try {
			return ModeloProyeccionComercialModelos::mdlEliminarFactor(
				$idFactor,
				self::ctrUsuario(),
				$motivo
			);
		} catch (Exception $e) {
			return array("ok" => false, "mensaje" => "No se pudo eliminar el factor");
		}
	}

	static public function ctrAplicarSugMasAjustes($post)
	{
		if (!self::ctrPuede("editar")) {
			return array("ok" => false, "mensaje" => "Sin permiso para editar");
		}
		$idLinea = isset($post["id_linea"]) ? (int) $post["id_linea"] : 0;
		$motivo = isset($post["motivo"]) ? trim((string) $post["motivo"]) : "";
		if ($idLinea <= 0) {
			return array("ok" => false, "mensaje" => "Línea inválida");
		}
		try {
			return ModeloProyeccionComercialModelos::mdlAplicarSugerenciaMasAjustes(
				$idLinea,
				self::ctrUsuario(),
				$motivo
			);
		} catch (Exception $e) {
			return array("ok" => false, "mensaje" => "No se pudo aplicar sugerencia + ajustes");
		}
	}

	static public function ctrEspacioModelo($post)
	{
		if (!self::ctrPuedeVer()) {
			return array("ok" => false, "mensaje" => "Sin permiso");
		}
		$idPeriodo = isset($post["id_periodo"]) ? (int) $post["id_periodo"] : 0;
		$modelo = self::ctrModeloCodigo($post);
		if ($idPeriodo <= 0 || $modelo === null) {
			return array("ok" => false, "mensaje" => "Plan o modelo inválidos");
		}
		$asegurar = !empty($post["asegurar"]) && self::ctrPuede("editar");
		try {
			@set_time_limit(90);
			$cab = ModeloProyeccionComercialModelos::mdlObtenerPeriodo($idPeriodo);
			if (!$cab) {
				return array("ok" => false, "mensaje" => "Plan no encontrado");
			}
			$periodoPlan = ModeloProyeccionComercialModelos::mdlPeriodoDesdeFila($cab);
			if ($periodoPlan === null) {
				return array("ok" => false, "mensaje" => "Rango del plan inválido");
			}

			$lineas = ModeloProyeccionComercialModelos::mdlListarLineasPeriodo($idPeriodo, 0, 0, $modelo);
			$lineasModelo = array();
			foreach ($lineas as $l) {
				if (strcasecmp(trim($l["modelo"]), $modelo) === 0) {
					$lineasModelo[] = $l;
				}
			}

			$generado = null;
			// Siempre recalcula sugerencias estacionales de borradores al abrir el modelo
			// (corrige líneas viejas que quedaron con la misma cifra en todos los meses).
			if (self::ctrPuede("editar") && count($lineasModelo) > 0) {
				$generado = ModeloProyeccionComercialModelos::mdlGenerarLineasPeriodo(
					$idPeriodo,
					0,
					$modelo,
					self::ctrUsuario(),
					5
				);
				$lineas = ModeloProyeccionComercialModelos::mdlListarLineasPeriodo($idPeriodo, 0, 0, $modelo);
				$lineasModelo = array();
				foreach ($lineas as $l) {
					if (strcasecmp(trim($l["modelo"]), $modelo) === 0) {
						$lineasModelo[] = $l;
					}
				}
			} elseif ($asegurar && count($lineasModelo) < (int) $periodoPlan["meses"]) {
				$generado = ModeloProyeccionComercialModelos::mdlGenerarLineasPeriodo(
					$idPeriodo,
					0,
					$modelo,
					self::ctrUsuario(),
					5
				);
				$lineas = ModeloProyeccionComercialModelos::mdlListarLineasPeriodo($idPeriodo, 0, 0, $modelo);
				$lineasModelo = array();
				foreach ($lineas as $l) {
					if (strcasecmp(trim($l["modelo"]), $modelo) === 0) {
						$lineasModelo[] = $l;
					}
				}
			}

			$ctx = ModeloProyeccionComercialModelos::mdlContextoModelo($modelo, $periodoPlan, 12);
			if ($ctx === null) {
				return array("ok" => false, "mensaje" => "El modelo no existe o no está activo");
			}

			// La sugerencia visible es la estacional recalculada (por mes), no un snapshot viejo.
			foreach ($lineasModelo as &$lin) {
				$per = sprintf("%04d-%02d", (int) $lin["anio"], (int) $lin["mes"]);
				if (isset($ctx["sugerencias"][$per]["unidades"])) {
					$lin["unidades_sugeridas_calc"] = (int) $ctx["sugerencias"][$per]["unidades"];
					$lin["unidades_sugeridas"] = (int) $ctx["sugerencias"][$per]["unidades"];
				}
			}
			unset($lin);

			return array(
				"ok" => true,
				"plan" => $cab,
				"contexto" => $ctx,
				"lineas" => $lineasModelo,
				"factores_por_mes" => ModeloProyeccionComercialModelos::mdlResumenFactoresPorModelo($idPeriodo, $modelo),
				"generado" => $generado,
				"catalogo_factores" => ModeloProyeccionComercialModelos::mdlListarCatalogoFactores(true),
				"motivos_desviacion" => ModeloProyeccionComercialModelos::mdlMotivosDesviacion(),
				"umbral_desviacion_pct" => ModeloProyeccionComercialModelos::UMBRAL_DESVIACION_PCT,
				"permisos" => array(
					"editar" => self::ctrPuede("editar"),
					"publicar" => self::ctrPuede("publicar")
				),
				"consultado_en" => date("Y-m-d H:i:s")
			);
		} catch (Exception $e) {
			return array("ok" => false, "mensaje" => "No se pudo cargar el espacio del modelo");
		}
	}

	static public function ctrBuscarModelos($post)
	{
		if (!self::ctrPuedeVer()) {
			return array("ok" => false, "mensaje" => "Sin permiso");
		}
		$idMarca = isset($post["id_marca"]) ? (int) $post["id_marca"] : 0;
		$q = trim(isset($post["q"]) ? $post["q"] : "");
		if ($idMarca <= 0 && strlen($q) < 1) {
			return array("ok" => true, "modelos" => array());
		}
		if (strlen($q) > 100) {
			return array("ok" => false, "mensaje" => "Búsqueda inválida");
		}
		try {
			$catalogo = ModeloProyeccionComercialModelos::mdlCatalogo($idMarca, $q);
			return array("ok" => true, "modelos" => array_slice($catalogo["modelos"], 0, 30));
		} catch (Exception $e) {
			return array("ok" => false, "mensaje" => "No se pudo buscar");
		}
	}

	static public function ctrModelosPendientes($post)
	{
		if (!self::ctrPuedeVer()) {
			return array("ok" => false, "mensaje" => "Sin permiso");
		}
		$idPeriodo = isset($post["id_periodo"]) ? (int) $post["id_periodo"] : 0;
		$idMarca = isset($post["id_marca"]) ? (int) $post["id_marca"] : 0;
		$q = trim(isset($post["q"]) ? $post["q"] : "");
		if ($idPeriodo <= 0) {
			return array("ok" => false, "mensaje" => "Plan inválido");
		}
		if (strlen($q) > 100) {
			return array("ok" => false, "mensaje" => "Búsqueda inválida");
		}
		try {
			$modelos = ModeloProyeccionComercialModelos::mdlModelosPendientesPeriodo(
				$idPeriodo,
				$idMarca,
				$q,
				800
			);
			return array(
				"ok" => true,
				"modelos" => $modelos,
				"total" => count($modelos),
				"stats" => ModeloProyeccionComercialModelos::mdlEstadisticasPeriodo($idPeriodo),
				"modelos_proyectados" => ModeloProyeccionComercialModelos::mdlModelosProyectadosPeriodo($idPeriodo)
			);
		} catch (Exception $e) {
			return array("ok" => false, "mensaje" => "No se pudieron listar modelos pendientes");
		}
	}

	static public function ctrResumenFactoresModelo($post)
	{
		if (!self::ctrPuedeVer()) {
			return array("ok" => false, "mensaje" => "Sin permiso");
		}
		$idPeriodo = isset($post["id_periodo"]) ? (int) $post["id_periodo"] : 0;
		$modelo = isset($post["modelo"]) ? trim((string) $post["modelo"]) : "";
		if ($idPeriodo <= 0 || $modelo === "") {
			return array("ok" => false, "mensaje" => "Datos inválidos");
		}
		return array(
			"ok" => true,
			"factores_por_mes" => ModeloProyeccionComercialModelos::mdlResumenFactoresPorModelo($idPeriodo, $modelo)
		);
	}

	static public function ctrListarCatalogo($post)
	{
		if (!self::ctrPuedeVer()) {
			return array("ok" => false, "mensaje" => "Sin permiso");
		}
		$todos = !empty($post["todos"]);
		return array(
			"ok" => true,
			"catalogo" => ModeloProyeccionComercialModelos::mdlListarCatalogoFactores(!$todos),
			"tipos" => ModeloProyeccionComercialModelos::mdlTiposFactor()
		);
	}

	static public function ctrGuardarCatalogo($post)
	{
		if (!self::ctrPuede("editar")) {
			return array("ok" => false, "mensaje" => "Sin permiso");
		}
		return ModeloProyeccionComercialModelos::mdlGuardarCatalogoFactor($post, self::ctrUsuario());
	}

	static public function ctrDesactivarCatalogo($post)
	{
		if (!self::ctrPuede("editar")) {
			return array("ok" => false, "mensaje" => "Sin permiso");
		}
		$id = isset($post["id"]) ? (int) $post["id"] : 0;
		if ($id <= 0) {
			return array("ok" => false, "mensaje" => "ID inválido");
		}
		return ModeloProyeccionComercialModelos::mdlDesactivarCatalogoFactor($id, self::ctrUsuario());
	}

	static public function ctrCatalogoLinea($post)
	{
		if (!self::ctrPuedeVer()) {
			return array("ok" => false, "mensaje" => "Sin permiso");
		}
		$idLinea = isset($post["id_linea"]) ? (int) $post["id_linea"] : 0;
		if ($idLinea <= 0) {
			return array("ok" => false, "mensaje" => "Línea inválida");
		}
		return ModeloProyeccionComercialModelos::mdlCatalogoParaLinea($idLinea);
	}

	static public function ctrToggleCatalogoLinea($post)
	{
		if (!self::ctrPuede("editar")) {
			return array("ok" => false, "mensaje" => "Sin permiso");
		}
		$idLinea = isset($post["id_linea"]) ? (int) $post["id_linea"] : 0;
		$idCatalogo = isset($post["id_catalogo"]) ? (int) $post["id_catalogo"] : 0;
		$aplicar = !empty($post["aplicar"]);
		$motivo = isset($post["motivo"]) ? trim((string) $post["motivo"]) : "";
		$ajuste = isset($post["ajuste_unidades"]) && $post["ajuste_unidades"] !== ""
			? $post["ajuste_unidades"] : null;
		if ($idLinea <= 0 || $idCatalogo <= 0) {
			return array("ok" => false, "mensaje" => "Datos inválidos");
		}
		return ModeloProyeccionComercialModelos::mdlToggleCatalogoEnLinea(
			$idLinea,
			$idCatalogo,
			$aplicar,
			self::ctrUsuario(),
			$motivo,
			$ajuste
		);
	}
}
