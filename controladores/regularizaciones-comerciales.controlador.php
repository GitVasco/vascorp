<?php

require_once __DIR__ . "/permisos-modulos.config.php";

/**
 * Casos de uso y contexto de sesión/auditoría del módulo.
 */
class ControladorRegularizacionesComerciales
{
    const SECTOR = "vasco_online";
    const MODULO = "regularizaciones_comerciales";

    public static function ctrPuede($accion = "ver")
    {
        return function_exists("usuarioPuedeModulo")
            && usuarioPuedeModulo(self::SECTOR, self::MODULO, $accion);
    }

    public static function ctrUsuarioSesionId()
    {
        if (!isset($_SESSION["id"])) {
            return 0;
        }

        return (int) $_SESSION["id"];
    }

    public static function ctrPermisos()
    {
        return array(
            "ver" => self::ctrPuede("ver"),
            "registrar" => self::ctrPuede("registrar"),
            "anular" => self::ctrPuede("anular"),
            "resolver" => self::ctrPuede("resolver"),
        );
    }

    public static function ctrBuscarCargos()
    {
        if (!self::ctrPuede("ver")) {
            return array("ok" => false, "msg" => "Sin permiso para ver regularizaciones.");
        }

        $filtros = array(
            "cuenta_cte_id" => isset($_POST["cuenta_cte_id"]) ? (int) $_POST["cuenta_cte_id"] : 0,
            "cliente" => isset($_POST["cliente"]) ? trim((string) $_POST["cliente"]) : "",
            "tipo_doc" => isset($_POST["tipo_doc"]) ? trim((string) $_POST["tipo_doc"]) : "",
            "num_cta" => isset($_POST["num_cta"]) ? trim((string) $_POST["num_cta"]) : "",
            "q" => isset($_POST["q"]) ? trim((string) $_POST["q"]) : "",
        );

        $cargos = ServicioRegularizacionesComerciales::buscarCargos($filtros);

        return array(
            "ok" => true,
            "cargos" => $cargos,
            "permisos" => self::ctrPermisos(),
        );
    }

    public static function ctrListar()
    {
        if (!self::ctrPuede("ver")) {
            return array("ok" => false, "msg" => "Sin permiso para ver regularizaciones.");
        }

        $filtros = array(
            "estado" => isset($_POST["estado"]) ? trim((string) $_POST["estado"]) : "",
            "cuenta_cte_id" => isset($_POST["cuenta_cte_id"]) ? (int) $_POST["cuenta_cte_id"] : 0,
            "cliente_codigo" => isset($_POST["cliente_codigo"]) ? trim((string) $_POST["cliente_codigo"]) : "",
            "tipo_doc" => isset($_POST["tipo_doc"]) ? trim((string) $_POST["tipo_doc"]) : "",
            "num_cta" => isset($_POST["num_cta"]) ? trim((string) $_POST["num_cta"]) : "",
        );

        return array(
            "ok" => true,
            "items" => ServicioRegularizacionesComerciales::listar($filtros),
            "permisos" => self::ctrPermisos(),
        );
    }

    public static function ctrVer()
    {
        if (!self::ctrPuede("ver")) {
            return array("ok" => false, "msg" => "Sin permiso para ver regularizaciones.");
        }

        $id = isset($_POST["id"]) ? (int) $_POST["id"] : (isset($_GET["id"]) ? (int) $_GET["id"] : 0);
        $resultado = ServicioRegularizacionesComerciales::ver($id);
        if (!empty($resultado["ok"])) {
            $resultado["permisos"] = self::ctrPermisos();
        }

        return $resultado;
    }

    public static function ctrCrear()
    {
        if (!self::ctrPuede("registrar")) {
            return array("ok" => false, "msg" => "Sin permiso para registrar regularizaciones.");
        }

        $input = array(
            "cuenta_cte_id" => isset($_POST["cuenta_cte_id"]) ? (int) $_POST["cuenta_cte_id"] : 0,
            "monto" => isset($_POST["monto"]) ? $_POST["monto"] : 0,
            "fecha_pago_cliente" => isset($_POST["fecha_pago_cliente"]) ? $_POST["fecha_pago_cliente"] : "",
            "motivo" => isset($_POST["motivo"]) ? $_POST["motivo"] : "",
            "sustento_referencia" => isset($_POST["sustento_referencia"]) ? $_POST["sustento_referencia"] : "",
            "observacion" => isset($_POST["observacion"]) ? $_POST["observacion"] : "",
        );

        return ServicioRegularizacionesComerciales::crear($input, self::ctrUsuarioSesionId());
    }

    public static function ctrAnular()
    {
        if (!self::ctrPuede("anular")) {
            return array("ok" => false, "msg" => "Sin permiso para anular regularizaciones.");
        }

        $id = isset($_POST["id"]) ? (int) $_POST["id"] : 0;
        $motivo = isset($_POST["motivo_anulacion"]) ? $_POST["motivo_anulacion"] : "";

        return ServicioRegularizacionesComerciales::anular($id, $motivo, self::ctrUsuarioSesionId());
    }

    public static function ctrReconciliar()
    {
        if (!self::ctrPuede("resolver")) {
            return array("ok" => false, "msg" => "Sin permiso para resolver regularizaciones.");
        }

        $id = isset($_POST["id"]) ? (int) $_POST["id"] : 0;
        $accion = isset($_POST["accion_reconciliar"])
            ? trim((string) $_POST["accion_reconciliar"])
            : "reintentar";

        return ServicioRegularizacionesComerciales::reconciliarManual(
            $id,
            self::ctrUsuarioSesionId(),
            $accion
        );
    }
}
