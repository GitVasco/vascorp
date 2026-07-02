<?php

require_once "../../controladores/cuentas.controlador.php";
require_once "../../modelos/cuentas.modelo.php";

class TablaLetrasPlazoProtesto
{

	public function mostrarTablaLetrasPlazoProtesto()
	{
		$letras = ControladorCuentas::ctrLetrasPlazoProtesto();

		if (count($letras) > 0) {

			$datosJson = '{
			"data": [';

			foreach ($letras as $letra) {
				$plazo = ModeloCuentas::calcularPlazoProtesto($letra["fecha_ven"]);

				switch ($plazo["estado"]) {
					case 'EN PLAZO':
						$estado = "<span class='label bg-green'>En plazo</span>";
						break;
					case 'URGENTE':
						$estado = "<span class='label bg-yellow'>Urgente</span>";
						break;
					case 'ULTIMO DIA':
						$estado = "<span class='label bg-red'>Último día</span>";
						break;
					default:
						$estado = "<span class='label bg-gray'>-</span>";
						break;
				}

				$datosJson .= '[
					"' . $letra["num_cta"] . '",
					"' . $letra["cliente"] . ' - ' . $letra["nombre"] . '",
					"' . $letra["telefono"] . '",
					"' . $letra["vendedor"] . '",
					"' . $letra["fecha"] . '",
					"' . $letra["fecha_ven"] . '",
					"' . $plazo["fecha_limite_protesto"] . '",
					"' . $plazo["dias_transcurridos"] . '",
					"' . $plazo["dias_restantes"] . '",
					"S/. ' . number_format($letra["saldo"], 2) . '",
					"' . $letra["num_unico"] . '",
					"' . $estado . '"
				],';
			}

			$datosJson = substr($datosJson, 0, -1);
			$datosJson .= ']
			}';

			echo $datosJson;
		} else {
			echo '{
				"data":[]
			}';
		}
	}
}

$activarTabla = new TablaLetrasPlazoProtesto();
$activarTabla->mostrarTablaLetrasPlazoProtesto();
