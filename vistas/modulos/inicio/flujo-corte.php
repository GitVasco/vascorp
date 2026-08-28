<?php

$anioFlujo = date("Y");
$mesFlujo = date("n");
$flujoCorte = ControladorDashboardFlujoCorte::ctrDatos($anioFlujo, $mesFlujo);

$stocks = $flujoCorte["stocks"];
$ocRes = $flujoCorte["oc_resumen"];
$cob = $flujoCorte["cobertura"];

if (!function_exists("flujoCoberturaClase")) {
    function flujoCoberturaClase($dias)
    {
        if ($dias === null) {
            return "flujo-cover-ok";
        }
        if ($dias < 10) {
            return "flujo-cover-danger";
        }
        if ($dias < 15) {
            return "flujo-cover-warn";
        }
        return "flujo-cover-ok";
    }
}
?>

<style>
    #box-flujo-corte > .box-header { padding: 8px 12px; }
    #box-flujo-corte > .box-body { padding: 8px 12px 10px; }
    #box-flujo-corte .flujo-pipeline {
        display: flex;
        flex-direction: column;
        gap: 4px;
    }
    #box-flujo-corte #flujo-alertas {
        display: flex;
        flex-direction: column;
        gap: 4px;
    }
    #box-flujo-corte .flujo-chip {
        color: #fff;
        border-radius: 3px;
        padding: 7px 10px;
        line-height: 1.2;
    }
    #box-flujo-corte .flujo-chip .flujo-lbl {
        display: block;
        font-size: 11px;
        opacity: 0.92;
    }
    #box-flujo-corte .flujo-chip strong {
        font-size: 18px;
        font-weight: 700;
    }
    #box-flujo-corte .flujo-chip .flujo-sub {
        display: block;
        font-size: 10px;
        opacity: 0.9;
        margin-top: 1px;
    }
    #box-flujo-corte .flujo-destinos {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 4px;
    }
    #box-flujo-corte .flujo-arrow {
        text-align: center;
        color: #666;
        font-size: 10px;
        font-weight: 600;
        line-height: 1.2;
        padding: 2px 0;
    }
    #box-flujo-corte .flujo-arrow i {
        display: block;
        font-size: 14px;
        margin-bottom: 1px;
        color: #888;
    }
    #box-flujo-corte .flujo-oc { background: #f39c12; }
    #box-flujo-corte .flujo-alm { background: #605ca8; }
    #box-flujo-corte .flujo-taller { background: #00a65a; }
    #box-flujo-corte .flujo-serv { background: #3c8dbc; }
    #box-flujo-corte .flujo-cover {
        border-radius: 3px;
        padding: 7px 10px;
        color: #fff;
    }
    #box-flujo-corte .flujo-cover .flujo-cover-lbl {
        font-size: 11px;
        font-weight: 600;
        opacity: 0.95;
        margin: 0 0 1px;
    }
    #box-flujo-corte .flujo-cover strong {
        display: block;
        font-size: 22px;
        line-height: 1.1;
        font-weight: 700;
    }
    #box-flujo-corte .flujo-cover .flujo-cover-sub {
        display: block;
        font-size: 10px;
        margin-top: 2px;
        opacity: 0.95;
    }
    #box-flujo-corte .flujo-cover-danger { background: #dd4b39; }
    #box-flujo-corte .flujo-cover-warn { background: #f39c12; }
    #box-flujo-corte .flujo-cover-ok { background: #00a65a; }
    #box-flujo-corte .flujo-cover-corte.flujo-cover-ok { background: #605ca8; }
    #box-flujo-corte .chart {
        height: 420px;
        position: relative;
    }
    #box-flujo-corte .flujo-chart-title {
        font-weight: 600;
        font-size: 12px;
        margin: 0 0 4px;
    }
</style>

<div class="box box-warning" id="box-flujo-corte">
    <div class="box-header with-border">
        <h3 class="box-title">
            <i class="fa fa-scissors"></i>
            Flujo de corte
            <small id="flujo-corte-periodo">— <?php echo htmlspecialchars($flujoCorte["periodo"]); ?></small>
        </h3>
        <div class="box-tools pull-right">
            <a href="ordencorte" class="btn btn-xs btn-warning">Órdenes de corte</a>
            <a href="almacencorte" class="btn btn-xs btn-default">Consumo de corte</a>
            <a href="en-cortes" class="btn btn-xs btn-default">Almacén de corte</a>
            <a href="enviados-taller" class="btn btn-xs btn-default">Enviados</a>
        </div>
    </div>
    <div class="box-body">
        <div class="row">
            <div class="col-md-3">
                <div class="flujo-pipeline">
                    <div class="flujo-chip flujo-oc">
                        <span class="flujo-lbl">Orden de corte</span>
                        <strong id="flujo-stock-oc"><?php echo ControladorDashboardFlujoCorte::ctrFmt($stocks["oc"]); ?></strong>
                        <span class="flujo-sub" id="flujo-oc-resumen"><?php echo (int) $ocRes["ordenes"]; ?> abiertas (<?php echo (int) $ocRes["pendientes"]; ?> pend. / <?php echo (int) $ocRes["parciales"]; ?> parc.)</span>
                    </div>
                    <div class="flujo-arrow">
                        <i class="fa fa-arrow-down"></i>
                        baja por consumo de corte
                    </div>
                    <div class="flujo-chip flujo-alm">
                        <span class="flujo-lbl">Almacén de corte</span>
                        <strong id="flujo-stock-corte"><?php echo ControladorDashboardFlujoCorte::ctrFmt($stocks["corte"]); ?></strong>
                        <span class="flujo-sub">cortado, pendiente de enviar</span>
                    </div>
                    <div class="flujo-arrow">
                        <i class="fa fa-arrow-down"></i>
                        baja por envío a taller o servicios
                    </div>
                    <div class="flujo-destinos">
                        <div class="flujo-chip flujo-taller">
                            <span class="flujo-lbl">Taller interno</span>
                            <strong id="flujo-stock-taller"><?php echo ControladorDashboardFlujoCorte::ctrFmt($stocks["taller"]); ?></strong>
                            <span class="flujo-sub">en proceso</span>
                        </div>
                        <div class="flujo-chip flujo-serv">
                            <span class="flujo-lbl">Servicios</span>
                            <strong id="flujo-stock-servicio"><?php echo ControladorDashboardFlujoCorte::ctrFmt($stocks["servicio"]); ?></strong>
                            <span class="flujo-sub">externos</span>
                        </div>
                    </div>
                    <div id="flujo-alertas">
                    <div id="flujo-cover-oc" class="flujo-cover <?php echo flujoCoberturaClase($cob["dias_oc"]); ?>">
                        <p class="flujo-cover-lbl">Lo programado dura</p>
                        <strong><span id="flujo-dias-oc"><?php echo $cob["dias_oc"] === null ? "—" : $cob["dias_oc"]; ?></span> días</strong>
                        <span class="flujo-cover-sub">
                            Ritmo corte <b id="flujo-ritmo-corte"><?php echo ControladorDashboardFlujoCorte::ctrFmt($cob["ritmo_corte"]); ?></b> und/día
                        </span>
                    </div>
                    <div id="flujo-cover-corte" class="flujo-cover flujo-cover-corte <?php echo flujoCoberturaClase($cob["dias_corte"]); ?>">
                        <p class="flujo-cover-lbl">Lo cortado dura</p>
                        <strong><span id="flujo-dias-corte"><?php echo $cob["dias_corte"] === null ? "—" : $cob["dias_corte"]; ?></span> días</strong>
                        <span class="flujo-cover-sub">
                            Ritmo envío <b id="flujo-ritmo-envio"><?php echo ControladorDashboardFlujoCorte::ctrFmt($cob["ritmo_envio"]); ?></b> und/día
                        </span>
                    </div>
                    </div>
                </div>
            </div>
            <div class="col-md-9">
                <p class="flujo-chart-title">Tendencia de saldos</p>
                <div class="chart">
                    <canvas id="flujoSaldoChart"></canvas>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@2.9.4/dist/Chart.min.js"></script>
<script>
    var flujoSaldoChart = null;
    var flujoDatosIniciales = <?php echo json_encode($flujoCorte["diario"], JSON_UNESCAPED_UNICODE); ?>;

    function flujoFmt(n) {
        n = parseFloat(n) || 0;
        return n.toFixed(0).replace(/\B(?=(\d{3})+(?!\d))/g, ",");
    }

    function flujoOpcionesChart() {
        return {
            responsive: true,
            maintainAspectRatio: false,
            legend: { display: true, position: "top", labels: { boxWidth: 10, fontSize: 10, padding: 6 } },
            tooltips: {
                mode: "index",
                intersect: false,
                callbacks: {
                    label: function(tooltipItem, data) {
                        return data.datasets[tooltipItem.datasetIndex].label + ": " + flujoFmt(tooltipItem.yLabel);
                    }
                }
            },
            scales: {
                yAxes: [{
                    ticks: {
                        beginAtZero: true,
                        fontSize: 10,
                        callback: function(value) { return flujoFmt(value); }
                    }
                }]
            }
        };
    }

    function flujoSerie(arr) {
        if (!arr || !arr.length) {
            return [];
        }
        return arr;
    }

    function flujoCrearGraficos(diario) {
        if (typeof Chart === "undefined") {
            return;
        }

        if (flujoSaldoChart && typeof flujoSaldoChart.destroy === "function") {
            flujoSaldoChart.destroy();
            flujoSaldoChart = null;
        }

        var canvasSaldo = document.getElementById("flujoSaldoChart");
        if (!canvasSaldo) {
            return;
        }

        flujoSaldoChart = new Chart(canvasSaldo.getContext("2d"), {
            type: "line",
            data: {
                labels: diario.labels,
                datasets: [
                    {
                        label: "Orden de corte",
                        data: flujoSerie(diario.saldo_oc),
                        borderColor: "#f39c12",
                        backgroundColor: "rgba(243,156,18,0.12)",
                        pointRadius: 1,
                        borderWidth: 2,
                        fill: false,
                        lineTension: 0.15
                    },
                    {
                        label: "Almacén de corte",
                        data: flujoSerie(diario.saldo_corte),
                        borderColor: "#605ca8",
                        backgroundColor: "rgba(96,92,168,0.12)",
                        pointRadius: 1,
                        borderWidth: 2,
                        fill: false,
                        lineTension: 0.15
                    },
                    {
                        label: "Taller interno",
                        data: flujoSerie(diario.saldo_taller),
                        borderColor: "#00a65a",
                        backgroundColor: "rgba(0,166,90,0.12)",
                        pointRadius: 1,
                        borderWidth: 2,
                        fill: false,
                        lineTension: 0.15
                    },
                    {
                        label: "Servicios",
                        data: flujoSerie(diario.saldo_servicio),
                        borderColor: "#3c8dbc",
                        backgroundColor: "rgba(60,141,188,0.12)",
                        pointRadius: 1,
                        borderWidth: 2,
                        fill: false,
                        lineTension: 0.15
                    }
                ]
            },
            options: flujoOpcionesChart()
        });
    }

    function flujoPintarDatos(datos) {
        $("#flujo-corte-periodo").text("— " + datos.periodo);

        $("#flujo-stock-oc").text(flujoFmt(datos.stocks.oc));
        $("#flujo-stock-corte").text(flujoFmt(datos.stocks.corte));
        $("#flujo-stock-taller").text(flujoFmt(datos.stocks.taller));
        $("#flujo-stock-servicio").text(flujoFmt(datos.stocks.servicio));
        $("#flujo-oc-resumen").text(
            datos.oc_resumen.ordenes + " abiertas (" +
            datos.oc_resumen.pendientes + " pend. / " +
            datos.oc_resumen.parciales + " parc.)"
        );

        $("#flujo-ritmo-corte").text(flujoFmt(datos.cobertura.ritmo_corte));
        $("#flujo-ritmo-envio").text(flujoFmt(datos.cobertura.ritmo_envio));
        $("#flujo-dias-oc").text(datos.cobertura.dias_oc === null ? "—" : datos.cobertura.dias_oc);
        $("#flujo-dias-corte").text(datos.cobertura.dias_corte === null ? "—" : datos.cobertura.dias_corte);

        function flujoCoverClase(dias) {
            if (dias === null) return "flujo-cover-ok";
            if (dias < 10) return "flujo-cover-danger";
            if (dias < 15) return "flujo-cover-warn";
            return "flujo-cover-ok";
        }

        $("#flujo-cover-oc")
            .removeClass("flujo-cover-ok flujo-cover-warn flujo-cover-danger")
            .addClass(flujoCoverClase(datos.cobertura.dias_oc));
        $("#flujo-cover-corte")
            .removeClass("flujo-cover-ok flujo-cover-warn flujo-cover-danger")
            .addClass(flujoCoverClase(datos.cobertura.dias_corte));

        if (datos.es_mes_actual) {
            $("#flujo-alertas").css("display", "flex");
        } else {
            $("#flujo-alertas").hide();
        }

        flujoCrearGraficos(datos.diario);
    }

    function actualizarFlujoCorte(mes, año) {
        var añoConsulta;
        var mesConsulta;
        if (mes === "null" || mes === null || mes === "" || mes === "0") {
            var ahora = new Date();
            añoConsulta = ahora.getFullYear();
            mesConsulta = ahora.getMonth() + 1;
        } else {
            añoConsulta = (año && año !== "") ? parseInt(año, 10) : new Date().getFullYear();
            mesConsulta = parseInt(mes, 10);
        }

        $.ajax({
            url: "ajax/inicio.ajax.php",
            method: "POST",
            data: {
                mes: mesConsulta,
                año: añoConsulta,
                accion: "flujoCorte"
            },
            dataType: "json",
            success: function(respuesta) {
                if (respuesta && respuesta.stocks) {
                    flujoPintarDatos(respuesta);
                }
            },
            error: function(xhr, status, error) {
                console.error("Error al actualizar flujo de corte:", error);
            }
        });
    }

    $(document).ready(function() {
        flujoCrearGraficos(flujoDatosIniciales);

        $(document).on("change", "#selectMes", function() {
            var mesSeleccionado = $(this).val();
            var añoSeleccionado = $(this).find("option:selected").data("año");
            actualizarFlujoCorte(mesSeleccionado, añoSeleccionado);
        });
    });
</script>
