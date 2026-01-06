<?php

require_once "../../controladores/materiaprima.controlador.php";
require_once "../../modelos/materiaprima.modelo.php";

class TablaMateriaPrimaPaginado
{

    /*=============================================
    MOSTRAR LA TABLA DE MATERIA PRIMA CON PAGINACIÓN SERVIDOR
    =============================================*/

    public function mostrarTablaMateriaPrimaPaginado()
    {
        // Parámetros de DataTables server-side processing
        $draw = isset($_GET['draw']) ? intval($_GET['draw']) : 1;
        $start = isset($_GET['start']) ? intval($_GET['start']) : 0;
        $length = isset($_GET['length']) ? intval($_GET['length']) : 20;
        $search = isset($_GET['search']['value']) ? $_GET['search']['value'] : '';
        $orderColumn = isset($_GET['order'][0]['column']) ? intval($_GET['order'][0]['column']) : 0;
        $orderDir = isset($_GET['order'][0]['dir']) ? $_GET['order'][0]['dir'] : 'desc';

        // Obtener datos paginados
        $resultado = ControladorMateriaPrima::ctrMostrarMateriaPrimaPaginado(
            $start,
            $length,
            $search,
            $orderColumn,
            $orderDir
        );

        $materiaprima = $resultado['data'];
        $recordsTotal = $resultado['recordsTotal'];
        $recordsFiltered = $resultado['recordsFiltered'];

        $datosJson = array();

        if (count($materiaprima) > 0) {
            foreach ($materiaprima as $item) {
                $descripcion = str_replace('"', '', $item["DesPro"]);

                //estado de la materia prima
                if ($item["estpro"] == "Inactivo") {
                    $estado = "<span style='font-size:85%' class='label label-danger'>" . $item["estpro"] . "</span>";
                    $botones = "<div class='btn-group'><button class='btn btn-xs btn-info btnVisualizarArticulos' title='Visualizar Articulos' data-toggle='modal' data-target='#modalVisualizarArticulos' articuloMP='" . $item["CodPro"] . "'><i class='fa fa-eye'></i></button></div>";
                } else {
                    $estado = "<span style='font-size:85%' class='label label-success'>" . $item["estpro"] . "</span>";
                    $botones = "<div class='btn-group'><button class='btn btn-xs btn-info btnVisualizarArticulos' title='Visualizar Articulos' data-toggle='modal' data-target='#modalVisualizarArticulos' articuloMP='" . $item["CodPro"] . "'><i class='fa fa-eye'></i></button><button class='btn btn-xs btn-warning btnEditarMateriaPrima' idMateriaPrima='" . $item["CodPro"] . "' data-toggle='modal' data-target='#modalEditarMateriaPrima' title='Editar Materia Prima'><i class='fa fa-pencil'></i></button><button class='btn btn-xs btn-success btnDuplicarMateriaPrima' idMateriaPrima='" . $item["CodPro"] . "' data-toggle='modal' data-target='#modalDuplicarMateriaPrima' title='Nuevo Color'><i class='fa fa-clone'></i></button><button class='btn btn-xs btn-primary btnEditarCosto' title='Visualizar Costo' data-toggle='modal' data-target='#modalEditarCostos' materiaPrima='" . $item["CodPro"] . "'><i class='fa fa-money'></i></button><button class='btn btn-xs btn-danger btnAnularMateriaPrima' title='Anular Materia Prima' idMateriaPrima='" . $item["CodPro"] . "'><i class='fa fa-times'></i></button></div>";
                }

                $datosJson[] = [
                    $item["CodPro"],
                    $item["CodFab"],
                    $descripcion,
                    $item["Color"],
                    $item["Talla"],
                    $item["Unidad"],
                    $item["Proveedores"],
                    $item["CodAlm01"],
                    $estado,
                    $botones
                ];
            }
        }

        // Formato de respuesta para DataTables server-side processing
        $respuesta = [
            "draw" => $draw,
            "recordsTotal" => $recordsTotal,
            "recordsFiltered" => $recordsFiltered,
            "data" => $datosJson
        ];

        echo json_encode($respuesta);
    }
}

/*=============================================
ACTIVAR TABLA DE MATERIA PRIMA CON PAGINACIÓN
=============================================*/
$activarMateriaPrimaPaginado = new TablaMateriaPrimaPaginado();
$activarMateriaPrimaPaginado->mostrarTablaMateriaPrimaPaginado();

