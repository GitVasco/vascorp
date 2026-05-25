/**
 * Pantalla escaneo-barcode-pedidocv: cabecera, totales (AJAX) y mismo POST que crear pedido CV.
 */
(function ($) {
  "use strict";

  function pedidoCodigoEscaneo() {
    return (
      ($("#formEscaneoNuevoCodigo").val() ||
        $("#escaneoPedidoCodigoActivo").val() ||
        "") + ""
    ).trim();
  }

  function escaneoCvRefrescarTotales() {
    var cod = pedidoCodigoEscaneo();
    if (!cod || !$("#escaneoHSubA").length) {
      return;
    }
    var datos = new FormData();
    datos.append("ajaxTotalesFormularioEscaneoCv", "1");
    datos.append("pedidoTotalesCv", cod);

    $.ajax({
      url: "ajax/pedidos.ajax.php",
      method: "POST",
      data: datos,
      cache: false,
      contentType: false,
      processData: false,
      dataType: "json",
      success: function (res) {
        if (!res || res.ok !== true) {
          return;
        }
        $("#escaneoHSubA").val(res.nuevoSubTotalA);
        $("#escaneoHDesc").val(res.descTotal);
        $("#escaneoHSubNet").val(res.subTotal);
        $("#escaneoHImp").val(res.impTotal);
        $("#escaneoHTotal").val(res.nuevoTotal);

        $("#escaneoMuestraSub").text(res.nuevoSubTotalA);
        $("#escaneoMuestraDesc").text(res.descTotal);
        $("#escaneoMuestraSubNet").text(res.subTotal);
        $("#escaneoMuestraImp").text(res.impTotal);
        $("#escaneoMuestraTot").text(res.nuevoTotal);
      },
    });
  }

  function num2CantEscaneo(x) {
    var n = parseInt(x, 10);
    return isNaN(n) ? 1 : Math.max(1, n);
  }

  function num2PrecEscaneo(x) {
    var s = String(x != null ? x : "").replace(",", ".");
    var n = parseFloat(s);
    if (isNaN(n) || n < 0) {
      return 0;
    }
    return Math.round(n * 10000) / 10000;
  }

  function num2Fmt2(x) {
    var n = parseFloat(x);
    if (isNaN(n)) {
      return "0.00";
    }
    return n.toFixed(2);
  }

  function appendFilaArticuloEditableEscaneo($tbody, row) {
    var sku = row.articulo != null ? String(row.articulo) : "";
    var pack =
      row.packing != null
        ? String(row.packing).replace(/^\s+|\s+$/g, "")
        : "";
    var desc = pack !== "" ? pack : sku;
    var cant = num2CantEscaneo(row.cantidad != null ? row.cantidad : 1);
    var preDec = num2PrecEscaneo(row.precio != null ? row.precio : 0);

    var totMostrar =
      row.total != null
        ? num2Fmt2(row.total)
        : num2Fmt2(Math.round(cant * preDec * 100) / 100);

    var cantStr = String(cant);
    var precStr = num2Fmt2(preDec);

    var $tr = $('<tr class="escaneoCvFilaLinea"></tr>')
      .attr("data-articulo", sku)
      .attr("data-sync-cant", cantStr)
      .attr("data-sync-prec", precStr);

    $("<td></td>").text(sku).appendTo($tr);
    $("<td></td>").text(desc).appendTo($tr);

    var $cantIn = $("<input>")
      .attr("type", "number")
      .addClass("form-control input-sm escaneoCvInputCant text-right")
      .attr({
        min: 1,
        step: 1,
        autocomplete: "off",
      })
      .val(cantStr);

    $("<td></td>")
      .addClass("text-right")
      .css("max-width", "5.5rem")
      .append($cantIn)
      .appendTo($tr);

    var $precIn = $("<input>")
      .attr("type", "number")
      .addClass("form-control input-sm escaneoCvInputPrecio text-right")
      .attr({
        min: 0,
        step: "0.01",
        autocomplete: "off",
      })
      .val(precStr);

    $("<td></td>")
      .addClass("text-right")
      .css("max-width", "6.5rem")
      .append($precIn)
      .appendTo($tr);

    $("<td></td>")
      .addClass("text-right escaneoCvColTotal")
      .text(totMostrar)
      .appendTo($tr);

    var $del = $("<button></button>")
      .attr({
        type: "button",
        title: "Eliminar línea",
      })
      .addClass("btn btn-danger btn-xs escaneoCvBtnEliminarLinea")
      .html("<i class=\"fa fa-times\"></i>");

    $("<td></td>").addClass("text-center").append($del).appendTo($tr);

    $tbody.append($tr);
  }

  function escaneoCvLineaSinCambioDesdeSync($tr) {
    var scRaw = parseInt($tr.attr("data-sync-cant"), 10);
    var spStr = ($tr.attr("data-sync-prec") || "").toString().replace(",", ".");
    var sp = parseFloat(spStr);

    var cantIn = num2CantEscaneo($tr.find(".escaneoCvInputCant").val());
    var precIn = num2PrecEscaneo($tr.find(".escaneoCvInputPrecio").val());
    var sc = num2CantEscaneo(scRaw);

    if (isNaN(sp)) {
      sp = 0;
    }

    var precDiff = Math.abs(precIn - sp);
    return cantIn === sc && precDiff < 0.0001;
  }

  function escaneoCvMarcarSyncFila($tr, cant, prec) {
    cant = num2CantEscaneo(cant);
    prec = num2PrecEscaneo(prec);
    var cStr = String(cant);
    var pStr = num2Fmt2(prec);
    $tr.attr("data-sync-cant", cStr).attr("data-sync-prec", pStr);
    $tr.find(".escaneoCvInputCant").val(cStr);
    $tr.find(".escaneoCvInputPrecio").val(pStr);
  }

  function escaneoCvGuardarLineaDesdeTr($tr) {
    if (
      !$tr.length ||
      $tr.closest("#escaneoListaArticulosBody").length === 0
    ) {
      return;
    }
    var pedido = pedidoCodigoEscaneo();
    var sku = $.trim(($tr.attr("data-articulo") || "").toString());
    if (!pedido || !sku || $tr.attr("data-esc-guardando") === "1") {
      return;
    }

    if (escaneoCvLineaSinCambioDesdeSync($tr)) {
      return;
    }

    var cant = num2CantEscaneo($tr.find(".escaneoCvInputCant").val());
    var precDec = num2PrecEscaneo($tr.find(".escaneoCvInputPrecio").val());

    $tr.attr("data-esc-guardando", "1");
    $tr.find(".escaneoCvInputCant,.escaneoCvInputPrecio").prop("disabled", true);

    var datos = new FormData();
    datos.append("ajaxActualizarLineaEscaneoCv", "1");
    datos.append("pedidoEscaneoLinea", pedido);
    datos.append("articuloEscaneoLinea", sku);
    datos.append("cantidadEscaneoLinea", String(cant));
    datos.append("precioEscaneoLinea", num2Fmt2(precDec));

    $.ajax({
      url: "ajax/pedidos.ajax.php",
      method: "POST",
      data: datos,
      cache: false,
      contentType: false,
      processData: false,
      dataType: "json",
      complete: function () {
        $tr.removeAttr("data-esc-guardando");
        $tr.find(".escaneoCvInputCant,.escaneoCvInputPrecio").prop("disabled", false);
      },
      success: function (res) {
        if (!res || res.ok !== true) {
          Command: toastr["error"](
            res && res.mensaje ? res.mensaje : "No se pudo guardar la línea."
          );
          return;
        }
        escaneoCvMarcarSyncFila($tr, cant, precDec);

        var totLocal = Math.round(cant * precDec * 100) / 100;

        $tr.find(".escaneoCvColTotal").text(num2Fmt2(totLocal));
        escaneoCvRefrescarTotales();
      },
      error: function () {
        Command: toastr["error"]("Error de conexión al guardar la línea.");
      },
    });
  }

  function escaneoCvRefrescarListadoArticulos() {
    var cod = pedidoCodigoEscaneo();
    var $tbody = $("#escaneoListaArticulosBody");
    var $panel = $("#escaneoPanelListadoArticulos");

    if (!$tbody.length) {
      return;
    }

    if (!cod) {
      if ($panel.length) {
        $panel.hide();
      }
      return;
    }

    var datos = new FormData();
    datos.append("ajaxListadoDetalleEscaneoCv", "1");
    datos.append("pedidoListadoEscaneoCv", cod);

    $.ajax({
      url: "ajax/pedidos.ajax.php",
      method: "POST",
      data: datos,
      cache: false,
      contentType: false,
      processData: false,
      dataType: "json",
      success: function (res) {
        if (!$tbody.length || !res || res.ok !== true) {
          return;
        }
        var items = res.items || [];

        $tbody.empty();

        if (items.length === 0) {

          $tbody.append(
            '<tr><td colspan="6" class="text-muted">Sin artículos aún. Escanee códigos de barras para agregar líneas.</td></tr>'
          );

        }



 else {






          var i;

          for (i = 0; i < items.length; i++) {
            appendFilaArticuloEditableEscaneo($tbody, items[i]);
          }

        }



        if ($panel.length) {
          $panel.show();



        }



      },

    });



  }



  window.escaneoCvRefrescarTotales = escaneoCvRefrescarTotales;

  window.escaneoCvRefrescarListadoArticulos = escaneoCvRefrescarListadoArticulos;


  if (!$("#formularioEscaneoPedidoCv").length) {
    return;
  }

  var vendedoresEspecialesEscaneo = new Set(["08L", "08O"]);



  var escaneoClientesAjaxPending = false;



  var escaneoClientesCatalogoListo = false;

  $("#btnEscaneoNuevoPedidoPhp,#btnEscaneoNuevoPedidoJs").on(
    "click",
    function () {
      window.location = "escaneo-barcode-pedidocv";
    }
  );

  $("#btnEscaneoIrPedidoManual").on("click", function () {
    var v = $.trim($("#pedidoEscaneoCvExistente").val() || "");
    if (!v) {
      Command: toastr["error"](
        "Ingrese el número del pedido temporal (columna «Código» en la lista de pedidos)."
      );
      return;
    }
    window.location =
      "index.php?ruta=escaneo-barcode-pedidocv&pedido=" +
      encodeURIComponent(v);
  });

  $("#pedidoEscaneoCvExistente").on("keydown", function (event) {
    if (event.keyCode === 13 || event.which === 13) {
      event.preventDefault();
      $("#btnEscaneoIrPedidoManual").trigger("click");
    }
  });

  $("#formularioEscaneoPedidoCv").on("submit", function (e) {
    if (!$.trim($("#formEscaneoNuevoCodigo").val())) {
      e.preventDefault();
      Command: toastr["error"](
        "Primero debe crear la cabecera del pedido o abrir uno existente."
      );
      return;
    }
    if (!$.trim($("#escaneoCondicionVenta").val())) {
      e.preventDefault();
      Command: toastr["error"]("Seleccione la condición de venta.");
      return;
    }
    if (!$.trim($("#escaneoAgenciaPost").val())) {
      e.preventDefault();
      Command: toastr["error"](
        "Seleccione la agencia de transporte antes de guardar."
      );
      return;
    }
  });

  $("#btnEscaneoRefresTotales").on("click", function () {
    escaneoCvRefrescarTotales();
    escaneoCvRefrescarListadoArticulos();
  });

  $("#formularioEscaneoPedidoCv").on(
    "keydown",
    ".escaneoCvInputCant, .escaneoCvInputPrecio",
    function (ev) {
      if ((ev.which || ev.keyCode) === 13) {
        ev.preventDefault();
        $(this).trigger("blur");
      }
    }
  );

  $("#formularioEscaneoPedidoCv").on(
    "blur",
    ".escaneoCvInputCant, .escaneoCvInputPrecio",
    function () {
      var $tr = $(this).closest("tr.escaneoCvFilaLinea");
      escaneoCvGuardarLineaDesdeTr($tr);
    }
  );

  $("#formularioEscaneoPedidoCv").on(
    "click",
    ".escaneoCvBtnEliminarLinea",
    function (e) {
      e.preventDefault();

      var pedido = pedidoCodigoEscaneo();
      var $tr = $(this).closest("tr.escaneoCvFilaLinea");
      var sku = $.trim(($tr.attr("data-articulo") || "").toString());

      if (!pedido || !sku || $tr.attr("data-esc-guardando") === "1") {
        return;
      }

      if (
        !window.confirm(
          "¿Eliminar esta línea del temporal (SKU " + sku + ")?"
        )
      ) {
        return;
      }

      $tr.attr("data-esc-guardando", "1");

      var datos = new FormData();
      datos.append("ajaxEliminarLineaEscaneoCv", "1");
      datos.append("pedidoEscaneoLineaEliminar", pedido);
      datos.append("articuloEscaneoLineaEliminar", sku);

      $.ajax({
        url: "ajax/pedidos.ajax.php",
        method: "POST",
        data: datos,
        cache: false,
        contentType: false,
        processData: false,
        dataType: "json",
        complete: function () {
          $tr.removeAttr("data-esc-guardando");
        },
        success: function (res) {
          if (!res || res.ok !== true) {
            Command: toastr["error"](
              res && res.mensaje ? res.mensaje : "No se pudo eliminar la línea."
            );
            return;
          }
          escaneoCvRefrescarListadoArticulos();
          escaneoCvRefrescarTotales();
        },
        error: function () {
          Command: toastr["error"](
            "Error de conexión al eliminar la línea."
          );
        },
      });
    }
  );

  function cargarClientesEscaneo(clientePreseleccionado) {
    var datos = new FormData();
    datos.append("clienteCuenta", clientePreseleccionado);

    $.ajax({
      url: "ajax/clientes.ajax.php",
      method: "POST",
      data: datos,
      cache: false,
      contentType: false,
      processData: false,
      dataType: "json",
      beforeSend: function () {
        escaneoClientesAjaxPending = true;
      },
      success: function (respuesta2) {
        var $sel = $("#escaneoSeleccionarCliente");
        $sel.find("option").remove();
        $sel.append(
          "<option value=''>" + "Seleccionar cliente" + "</option>"
        );
        for (var i = 0; i < respuesta2.length; i++) {
          $sel.append(
            "<option value='" +
              respuesta2[i]["codigo"] +
              "'>" +
              respuesta2[i]["codigo"] +
              " - " +
              respuesta2[i]["nombre"] +
              "</option>"
          );
        }
        if (
          clientePreseleccionado &&
          clientePreseleccionado !== "" &&
          clientePreseleccionado !== "1"
        ) {
          $sel.val(clientePreseleccionado);
        }
        $sel.selectpicker("refresh");
        $("#escaneoPostCliente").val($sel.val() || "");

        escaneoClientesCatalogoListo = true;

      },

      complete: function () {

        escaneoClientesAjaxPending = false;

      },

      error: function () {
        Command: toastr["error"]("No se pudieron cargar los clientes.");
      },

    });


  }



  $("#escaneoSeleccionarCliente").on("show.bs.select", function () {

    var $sel = $("#escaneoSeleccionarCliente");

    if (!$sel.length || $sel.prop("disabled")) {


      return;

    }







    /* Si ya cargó (incluso lista vacía) o hay opciones cargadas */

    var nOptsIni = $sel.find("option").length;

    if (
      escaneoClientesAjaxPending ||
      escaneoClientesCatalogoListo ||
      nOptsIni > 1
    ) {
      return;
    }






    cargarClientesEscaneo("1");



  });



  function cargarAgenciaEscaneo(codigoCliente) {
    if (!codigoCliente) {
      return;
    }
    var datos = new FormData();
    datos.append("codigo", codigoCliente);
    $.ajax({
      url: "ajax/clientes.ajax.php",
      method: "POST",
      data: datos,
      cache: false,
      contentType: false,
      processData: false,
      dataType: "json",
      success: function (respuesta) {
        if (respuesta && respuesta.agencia !== "" && respuesta.agencia != null) {
          $("#escaneoAgenciaCab").val(respuesta.agencia);
          $("#escaneoAgenciaCab").selectpicker("refresh");
          $("#escaneoAgenciaPost").val(respuesta.agencia);
          $("#escaneoAgenciaPost").selectpicker("refresh");
        }
      },
    });
  }

  $("#escaneoSeleccionarCliente").on("change", function () {
    var cliList = $("#escaneoSeleccionarCliente").val();
    if (!cliList || cliList === "") {
      $("#escaneoListaPreciosCab").val("");
      return;
    }
    $("#escaneoPostCliente").val(cliList);
    cargarAgenciaEscaneo(cliList);

    var fechaActual = new Date();
    var precio = "no";
    if (
      fechaActual.getDate() === 9 &&
      fechaActual.getMonth() === 2 &&
      fechaActual.getFullYear() === 2023
    ) {
      precio = "ok";
    }

    var pedidosDummy = [];
    var datosLista = new FormData();
    datosLista.append("cliList", cliList);

    $.ajax({
      url: "ajax/pedidos.ajax.php",
      method: "POST",
      data: datosLista,
      cache: false,
      contentType: false,
      processData: false,
      dataType: "json",
      success: function (respuestaDet) {
        var nuevoCodigo = "";
        if (pedidosDummy.includes(nuevoCodigo)) {
          $("#escaneoListaPreciosCab").val(respuestaDet.lista_precios);
        } else if (
          (respuestaDet.vendedor === "08" ||
            respuestaDet.vendedor === "08R") &&
          precio === "ok"
        ) {
          $("#escaneoListaPreciosCab").val("precio2");
        } else {
          $("#escaneoListaPreciosCab").val(respuestaDet.lista_precios);
        }
        $("#escaneoSeleccionarVendedor").trigger("change");
      },
    });
  });

  $("#escaneoSeleccionarVendedor").on("change", function () {
    var cliList = $("#escaneoSeleccionarCliente").val();
    var vendedor = $("#escaneoSeleccionarVendedor").val();
    $("#escaneoPostVendedor").val(vendedor || "");

    if (!cliList) {
      return;
    }

    var datosLista = new FormData();
    datosLista.append("cliList", cliList);

    $.ajax({
      url: "ajax/pedidos.ajax.php",
      method: "POST",
      data: datosLista,
      cache: false,
      contentType: false,
      processData: false,
      dataType: "json",
      success: function (respuestaDet) {
        var listaPrecio = vendedoresEspecialesEscaneo.has(vendedor)
          ? "precio6"
          : respuestaDet.lista_precios;
        $("#escaneoListaPreciosCab").val(listaPrecio);
      },
    });
  });

  $("#btnEscaneoCrearCabTemporal").on("click", function () {
    var $btn = $(this);
    var cliente = ($("#escaneoSeleccionarCliente").val() || "").trim();
    var vendedor = ($("#escaneoSeleccionarVendedor").val() || "").trim();
    var lista = ($("#escaneoListaPreciosCab").val() || "").trim();
    var agencia = ($("#escaneoAgenciaCab").val() || "").trim();

    if (!cliente) {
      Command: toastr["error"]("Seleccione un cliente del listado.");
      return;
    }
    if (!vendedor) {
      Command: toastr["error"]("Seleccione un vendedor.");
      return;
    }
    if (!lista) {
      Command: toastr["error"](



        "Lista de precios vacía; vuelva a elegir cliente o vendedor."
      );
      return;
    }
    if (!agencia) {
      Command: toastr["error"]("Seleccione la agencia en la cabecera.");
      return;
    }

    $("#escaneoPostCliente").val(cliente);
    $("#escaneoPostVendedor").val(vendedor);

    $btn.prop("disabled", true);

    var datos = new FormData();
    datos.append("crearCabeceraEscaneoCv", "1");
    datos.append("clienteEscaneoCab", cliente);
    datos.append("vendedorEscaneoCab", vendedor);
    datos.append("listaEscaneoCab", lista);
    datos.append("agenciaEscaneoCab", agencia);

    $.ajax({
      url: "ajax/pedidos.ajax.php",
      method: "POST",
      data: datos,
      cache: false,
      contentType: false,
      processData: false,
      dataType: "json",
      success: function (res) {
        if (res && res.ok === true && res.pedido) {
          $("#escaneoPedidoCodigoActivo").val(res.pedido);
          $("#formEscaneoNuevoCodigo").val(res.pedido);

          $("#escaneoResumenCabPhp").hide();
          $("#escaneoResumenCabJs").show();
          $("#textoEscaneoResumenJs").text(
            "Pedido " +
              res.pedido +
              " · Cliente " +
              cliente +
              (lista !== "" ? " · Lista " + lista : "")
          );

          $("#lnkEscaneoIrCrearJs").attr(
            "href",
            "index.php?ruta=crear-pedidocv&pedido=" +
              encodeURIComponent(res.pedido)
          );

          $("#escaneoBloqueFormularioCab").slideUp();
          $("#panelEscaneoCvLector").slideDown();
          $("#inputBarcodePedCv").prop("disabled", false);

          $("#escaneoAgenciaPost").val(agencia);
          $("#escaneoAgenciaPost").selectpicker("refresh");

          $("#panelEscaneoCvCierre").show();
          $("#btnEscaneoGuardarPedido").prop("disabled", false);

          $("#escaneoCondicionVenta").selectpicker("refresh");

          $("#escaneoPanelListadoArticulos").show();




          Command: toastr["success"](

            "Cabecera lista. Ya puede escanear códigos abajo sin cambiar de pantalla."






          );

          escaneoCvRefrescarTotales();
          escaneoCvRefrescarListadoArticulos();

          window.setTimeout(function () {
            $("#inputBarcodePedCv").trigger("focus");
          }, 200);
          $btn.prop("disabled", false);
        } else {
          Command: toastr["error"](
            res && res.mensaje ? res.mensaje : "No se creó la cabecera."
          );
          $btn.prop("disabled", false);
        }
      },
      error: function () {
        Command: toastr["error"]("Error de conexión.");
        $btn.prop("disabled", false);
      },
    });
  });

  $(function () {
    if (!pedidoCodigoEscaneo()) {
      return;
    }
    $("#lnkEscaneoIrCrearJs").attr(
      "href",
      "index.php?ruta=crear-pedidocv&pedido=" +
        encodeURIComponent(pedidoCodigoEscaneo())
    );
    /* Totales vía AJAX (ligero) para alinear con el servidor */
    escaneoCvRefrescarTotales();
    /*
     * Si la vista ya trajo el detalle en HTML (apertura con ?pedido=),
     * no volvemos a pedir el mismo listado al cargar: evita doble consulta
     * y la sensación de “pantalla colgada” en pedidos con muchas líneas.
     * Recalcular / borrar línea / escanear siguen refrescando el listado.
     */
    var $tb = $("#escaneoListaArticulosBody");
    if ($tb.length && $tb.data("escSkipIniListado") === 1) {
      $tb.removeAttr("data-esc-skip-ini-listado");
    } else {
      escaneoCvRefrescarListadoArticulos();
    }
    var $lec = $("#inputBarcodePedCv");
    if ($lec.length && !$lec.prop("disabled")) {
      window.setTimeout(function () {
        $lec.trigger("focus");
      }, 200);
    }
  });

})(jQuery);
